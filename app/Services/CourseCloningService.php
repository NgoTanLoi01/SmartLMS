<?php

namespace App\Services;

use App\Models\Assignments;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourseCloningService
{
    public const SECTION_LABELS = [
        'content' => 'Chương và bài học',
        'assignments' => 'Bài tập',
        'quizzes' => 'Bài kiểm tra',
        'question_banks' => 'Liên kết ngân hàng câu hỏi',
    ];

    public function cloneContent(Course $sourceCourse, Course $targetCourse): void
    {
        $sourceCourse->loadMissing(['modules.lessons', 'assignments', 'quizzes', 'questionBanks']);
        $lessonIdMap = [];

        foreach ($sourceCourse->modules as $sourceModule) {
            $targetModule = Module::create([
                'course_id' => $targetCourse->id,
                'template_origin_id' => $sourceModule->id,
                'title' => $sourceModule->title,
                'order' => $sourceModule->order,
                'status' => $sourceModule->status ?? Module::STATUS_PUBLISHED,
            ]);

            foreach ($sourceModule->lessons as $sourceLesson) {
                $copiedAttachment = $this->copyLessonAttachment($sourceLesson);
                $targetLesson = Lesson::create([
                    'module_id' => $targetModule->id,
                    'template_origin_id' => $sourceLesson->id,
                    'title' => $sourceLesson->title,
                    'content' => $sourceLesson->content,
                    'video_url' => $sourceLesson->video_url,
                    'attachment_path' => $sourceLesson->attachment_path,
                    'attachment' => $copiedAttachment['attachment'],
                    'attachment_disk' => $copiedAttachment['attachment_disk'],
                    'attachment_original_name' => $sourceLesson->attachment_original_name,
                    'attachment_mime_type' => $sourceLesson->attachment_mime_type,
                    'attachment_size' => $sourceLesson->attachment_size,
                    'order' => $sourceLesson->order,
                    'status' => $sourceLesson->status,
                    'published_at' => $sourceLesson->published_at,
                    'available_from' => $sourceLesson->available_from,
                ]);

                $lessonIdMap[$sourceLesson->id] = $targetLesson->id;
            }
        }

        foreach ($sourceCourse->assignments as $sourceAssignment) {
            Assignments::create([
                'course_id' => $targetCourse->id,
                'template_origin_id' => $sourceAssignment->id,
                'lesson_id' => $sourceAssignment->lesson_id ? ($lessonIdMap[$sourceAssignment->lesson_id] ?? null) : null,
                'type' => $sourceAssignment->type,
                'title' => $sourceAssignment->title,
                'instructions' => $sourceAssignment->instructions,
                'grading_rubric' => $sourceAssignment->grading_rubric,
                'grading_scale' => $sourceAssignment->grading_scale,
                'ai_grading_enabled' => $sourceAssignment->ai_grading_enabled,
                'due_date' => $sourceAssignment->due_date,
                'allowed_extensions' => $sourceAssignment->allowed_extensions,
                'max_file_size' => $sourceAssignment->max_file_size,
                'status' => $sourceAssignment->status,
                'published_at' => $sourceAssignment->published_at,
                'available_from' => $sourceAssignment->available_from,
            ]);
        }

        foreach ($sourceCourse->quizzes as $sourceQuiz) {
            Quiz::create([
                'course_id' => $targetCourse->id,
                'template_origin_id' => $sourceQuiz->id,
                'title' => $sourceQuiz->title,
                'time_limit' => $sourceQuiz->time_limit,
                'max_attempts' => $sourceQuiz->max_attempts ?: 1,
                'is_random' => $sourceQuiz->is_random,
                'easy_count' => $sourceQuiz->easy_count,
                'medium_count' => $sourceQuiz->medium_count,
                'hard_count' => $sourceQuiz->hard_count,
                'question_distribution' => $sourceQuiz->question_distribution,
                'status' => $sourceQuiz->status,
                'published_at' => $sourceQuiz->published_at,
                'available_from' => $sourceQuiz->available_from,
            ]);
        }

        $targetCourse->questionBanks()->syncWithoutDetaching(
            $sourceCourse->questionBanks->pluck('id')->all()
        );

        $this->cloneCourseSpecificQuestions($sourceCourse, $targetCourse);

        if ($sourceCourse->isTemplate() && ! $targetCourse->isTemplate()) {
            $version = max(1, (int) $sourceCourse->template_version);
            $sectionVersions = $this->sectionVersions($sourceCourse);
            $targetCourse->update([
                'source_template_id' => $sourceCourse->id,
                'synced_template_version' => $version,
                'template_sync_state' => $sectionVersions,
            ]);
        }
    }

    public function syncFromTemplate(Course $template, Course $target, array $sections): array
    {
        if (! $template->isTemplate() || $target->isTemplate() || (int) $target->source_template_id !== (int) $template->id) {
            throw ValidationException::withMessages([
                'sections' => 'Quan hệ giữa khóa mẫu và khóa đang triển khai không hợp lệ.',
            ]);
        }

        $sections = collect($sections)->unique()->values();
        if ($sections->isEmpty() || $sections->diff(array_keys(self::SECTION_LABELS))->isNotEmpty()) {
            throw ValidationException::withMessages([
                'sections' => 'Vui lòng chọn ít nhất một nhóm nội dung hợp lệ để đồng bộ.',
            ]);
        }

        return DB::transaction(function () use ($template, $target, $sections) {
            $template->load(['modules.lessons', 'assignments', 'quizzes', 'questionBanks']);
            $counts = [];

            if ($sections->contains('content')) {
                $counts['content'] = $this->syncModulesAndLessons($template, $target);
            }
            if ($sections->contains('assignments')) {
                $counts['assignments'] = $this->syncAssignments($template, $target);
            }
            if ($sections->contains('quizzes')) {
                $counts['quizzes'] = $this->syncQuizzes($template, $target);
            }
            if ($sections->contains('question_banks')) {
                $existingBankIds = $target->questionBanks()->pluck('question_banks.id');
                $sourceBankIds = $template->questionBanks->pluck('id');
                $target->questionBanks()->syncWithoutDetaching($sourceBankIds->all());
                $counts['question_banks'] = $sourceBankIds->diff($existingBankIds)->count()
                    + $this->syncCourseSpecificQuestions($template, $target);
            }

            $version = max(1, (int) $template->template_version);
            $sectionVersions = $this->sectionVersions($template);
            $state = $target->template_sync_state ?? [];
            foreach ($sections as $section) {
                $state[$section] = $sectionVersions[$section];
            }
            $fullySynced = collect(array_keys(self::SECTION_LABELS))
                ->every(fn ($section) => (int) ($state[$section] ?? 0) === (int) $sectionVersions[$section]);

            $target->update([
                'template_sync_state' => $state,
                'synced_template_version' => $fullySynced ? $version : $target->synced_template_version,
            ]);

            return [
                'template_version' => $version,
                'fully_synced' => $fullySynced,
                'counts' => $counts,
            ];
        }, 3);
    }

    private function sectionVersions(Course $template): array
    {
        $globalVersion = max(1, (int) $template->template_version);
        $stored = $template->template_section_versions ?? [];

        return collect(array_keys(self::SECTION_LABELS))->mapWithKeys(fn ($section) => [
            $section => max(1, (int) ($stored[$section] ?? $globalVersion)),
        ])->all();
    }

    private function syncModulesAndLessons(Course $template, Course $target): int
    {
        $changed = 0;
        $sourceModuleIds = $template->modules->pluck('id');
        $targetModules = Module::query()
            ->where('course_id', $target->id)
            ->whereNotNull('template_origin_id')
            ->get()
            ->keyBy('template_origin_id');

        foreach ($template->modules as $sourceModule) {
            $targetModule = $targetModules->get($sourceModule->id);
            $moduleData = [
                'course_id' => $target->id,
                'template_origin_id' => $sourceModule->id,
                'title' => $sourceModule->title,
                'order' => $sourceModule->order,
                'status' => $sourceModule->status ?? Module::STATUS_PUBLISHED,
            ];
            if ($targetModule) {
                $targetModule->update($moduleData);
            } else {
                $targetModule = Module::create($moduleData);
            }
            $changed++;

            $sourceLessonIds = $sourceModule->lessons->pluck('id');
            $targetLessons = Lesson::query()
                ->where('module_id', $targetModule->id)
                ->whereNotNull('template_origin_id')
                ->get()
                ->keyBy('template_origin_id');

            foreach ($sourceModule->lessons as $sourceLesson) {
                $targetLesson = $targetLessons->get($sourceLesson->id);
                $attachment = $this->copyLessonAttachment($sourceLesson, $targetLesson);
                $lessonData = [
                    'module_id' => $targetModule->id,
                    'template_origin_id' => $sourceLesson->id,
                    'title' => $sourceLesson->title,
                    'content' => $sourceLesson->content,
                    'video_url' => $sourceLesson->video_url,
                    'attachment_path' => $sourceLesson->attachment_path,
                    'attachment' => $attachment['attachment'],
                    'attachment_disk' => $attachment['attachment_disk'],
                    'attachment_original_name' => $sourceLesson->attachment_original_name,
                    'attachment_mime_type' => $sourceLesson->attachment_mime_type,
                    'attachment_size' => $sourceLesson->attachment_size,
                    'order' => $sourceLesson->order,
                    'status' => $sourceLesson->status,
                    'published_at' => $sourceLesson->published_at,
                    'available_from' => $sourceLesson->available_from,
                ];
                $targetLesson ? $targetLesson->update($lessonData) : Lesson::create($lessonData);
                $changed++;
            }

            Lesson::query()
                ->where('module_id', $targetModule->id)
                ->whereNotNull('template_origin_id')
                ->when($sourceLessonIds->isNotEmpty(), fn ($query) => $query->whereNotIn('template_origin_id', $sourceLessonIds))
                ->when($sourceLessonIds->isEmpty(), fn ($query) => $query)
                ->update(['status' => Lesson::STATUS_ARCHIVED, 'published_at' => null]);
        }

        Module::query()
            ->where('course_id', $target->id)
            ->whereNotNull('template_origin_id')
            ->when($sourceModuleIds->isNotEmpty(), fn ($query) => $query->whereNotIn('template_origin_id', $sourceModuleIds))
            ->update(['status' => Module::STATUS_ARCHIVED]);

        return $changed;
    }

    private function syncAssignments(Course $template, Course $target): int
    {
        $sourceIds = $template->assignments->pluck('id');
        $lessonMap = Lesson::query()
            ->whereHas('module', fn ($query) => $query->where('course_id', $target->id))
            ->whereNotNull('template_origin_id')
            ->pluck('id', 'template_origin_id');
        $targets = Assignments::query()
            ->where('course_id', $target->id)
            ->whereNotNull('template_origin_id')
            ->get()
            ->keyBy('template_origin_id');

        foreach ($template->assignments as $source) {
            $data = [
                'course_id' => $target->id,
                'template_origin_id' => $source->id,
                'lesson_id' => $source->lesson_id ? $lessonMap->get($source->lesson_id) : null,
                'type' => $source->type,
                'title' => $source->title,
                'instructions' => $source->instructions,
                'grading_rubric' => $source->grading_rubric,
                'grading_scale' => $source->grading_scale,
                'ai_grading_enabled' => $source->ai_grading_enabled,
                'due_date' => $source->due_date,
                'allowed_extensions' => $source->allowed_extensions,
                'max_file_size' => $source->max_file_size,
                'status' => $source->status,
                'published_at' => $source->published_at,
                'available_from' => $source->available_from,
            ];
            $targetAssignment = $targets->get($source->id);
            $targetAssignment ? $targetAssignment->update($data) : Assignments::create($data);
        }

        Assignments::query()
            ->where('course_id', $target->id)
            ->whereNotNull('template_origin_id')
            ->when($sourceIds->isNotEmpty(), fn ($query) => $query->whereNotIn('template_origin_id', $sourceIds))
            ->update(['status' => Assignments::STATUS_ARCHIVED, 'published_at' => null]);

        return $template->assignments->count();
    }

    private function syncQuizzes(Course $template, Course $target): int
    {
        $sourceIds = $template->quizzes->pluck('id');
        $targets = Quiz::query()
            ->where('course_id', $target->id)
            ->whereNotNull('template_origin_id')
            ->get()
            ->keyBy('template_origin_id');

        foreach ($template->quizzes as $source) {
            $data = [
                'course_id' => $target->id,
                'template_origin_id' => $source->id,
                'title' => $source->title,
                'time_limit' => $source->time_limit,
                'max_attempts' => $source->max_attempts ?: 1,
                'is_random' => $source->is_random,
                'easy_count' => $source->easy_count,
                'medium_count' => $source->medium_count,
                'hard_count' => $source->hard_count,
                'question_distribution' => $source->question_distribution,
                'status' => $source->status,
                'published_at' => $source->published_at,
                'available_from' => $source->available_from,
            ];
            $targetQuiz = $targets->get($source->id);
            $targetQuiz ? $targetQuiz->update($data) : Quiz::create($data);
        }

        Quiz::query()
            ->where('course_id', $target->id)
            ->whereNotNull('template_origin_id')
            ->when($sourceIds->isNotEmpty(), fn ($query) => $query->whereNotIn('template_origin_id', $sourceIds))
            ->update(['status' => Quiz::STATUS_ARCHIVED, 'published_at' => null]);

        return $template->quizzes->count();
    }

    private function cloneCourseSpecificQuestions(Course $sourceCourse, Course $targetCourse): void
    {
        Question::with('options')
            ->notArchived()
            ->where('course_id', $sourceCourse->id)
            ->whereNull('question_bank_id')
            ->get()
            ->each(function ($sourceQuestion) use ($targetCourse) {
                $targetQuestion = Question::create([
                    'course_id' => $targetCourse->id,
                    'template_origin_id' => $sourceQuestion->id,
                    'question_bank_id' => null,
                    'question_type' => $sourceQuestion->question_type,
                    'question_text' => $sourceQuestion->question_text,
                    'answer_config' => $sourceQuestion->answer_config,
                    'difficulty' => $sourceQuestion->difficulty,
                    'status' => $sourceQuestion->status ?? Question::STATUS_PUBLISHED,
                ]);

                foreach ($sourceQuestion->options as $sourceOption) {
                    $targetQuestion->options()->create([
                        'option_text' => $sourceOption->option_text,
                        'is_correct' => $sourceOption->is_correct,
                    ]);
                }
            });
    }

    private function syncCourseSpecificQuestions(Course $template, Course $target): int
    {
        $sources = Question::with('options')
            ->notArchived()
            ->where('course_id', $template->id)
            ->whereNull('question_bank_id')
            ->get();
        $sourceIds = $sources->pluck('id');
        $targets = Question::with('options')
            ->where('course_id', $target->id)
            ->whereNotNull('template_origin_id')
            ->get()
            ->keyBy('template_origin_id');

        foreach ($sources as $source) {
            $data = [
                'course_id' => $target->id,
                'template_origin_id' => $source->id,
                'question_bank_id' => null,
                'question_type' => $source->question_type,
                'question_text' => $source->question_text,
                'answer_config' => $source->answer_config,
                'difficulty' => $source->difficulty,
                'status' => $source->status ?? Question::STATUS_PUBLISHED,
            ];
            $targetQuestion = $targets->get($source->id);
            if ($targetQuestion) {
                $targetQuestion->update($data);
                $targetQuestion->options()->delete();
            } else {
                $targetQuestion = Question::create($data);
            }

            foreach ($source->options as $option) {
                $targetQuestion->options()->create([
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                ]);
            }
        }

        Question::query()
            ->where('course_id', $target->id)
            ->whereNotNull('template_origin_id')
            ->when($sourceIds->isNotEmpty(), fn ($query) => $query->whereNotIn('template_origin_id', $sourceIds))
            ->update(['status' => Question::STATUS_ARCHIVED]);

        return $sources->count();
    }

    private function copyLessonAttachment(Lesson $lesson, ?Lesson $targetLesson = null): array
    {
        $path = $lesson->attachment;
        $sourceDisk = $lesson->attachment_disk ?: 'public';
        $targetDisk = config('filesystems.lesson_attachment_disk', $sourceDisk);

        $result = ['attachment' => null, 'attachment_disk' => $sourceDisk];

        if (! $path) {
            if ($targetLesson?->attachment) {
                Storage::disk($targetLesson->attachment_disk ?: 'public')->delete($targetLesson->attachment);
            }

            return $result;
        }

        if (! Storage::disk($sourceDisk)->exists($path)) {
            return $targetLesson
                ? ['attachment' => $targetLesson->attachment, 'attachment_disk' => $targetLesson->attachment_disk ?: 'public']
                : ['attachment' => $path, 'attachment_disk' => $sourceDisk];
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = Str::uuid().($extension ? '.'.$extension : '');
        $targetPath = 'lessons/attachments/'.$filename;

        Storage::disk($targetDisk)->put($targetPath, Storage::disk($sourceDisk)->get($path));

        if ($targetLesson?->attachment) {
            Storage::disk($targetLesson->attachment_disk ?: 'public')->delete($targetLesson->attachment);
        }

        return [
            'attachment' => $targetPath,
            'attachment_disk' => $targetDisk,
        ];
    }
}
