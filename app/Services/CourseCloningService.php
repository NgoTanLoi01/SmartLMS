<?php

namespace App\Services;

use App\Models\Assignments;
use App\Models\Course;
use App\Models\LearningMaterialAssignment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizPassage;
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

    public function cloneModule(Module $sourceModule, array $options = []): array
    {
        return DB::transaction(function () use ($sourceModule, $options) {
            $sourceModule->loadMissing(['course', 'lessons.assignments']);
            $copyAssignments = (bool) ($options['copy_assignments'] ?? false);
            $copyMaterials = (bool) ($options['copy_materials'] ?? false);
            $copyRubrics = (bool) ($options['copy_rubrics'] ?? false);

            $targetModule = Module::create([
                'course_id' => $sourceModule->course_id,
                'template_origin_id' => null,
                'title' => $this->copyTitle($sourceModule->title),
                'order' => Module::query()->where('course_id', $sourceModule->course_id)->notArchived()->max('order') + 1,
                'status' => Module::STATUS_DRAFT,
            ]);

            $lessonMap = [];
            $assignmentCount = 0;
            foreach ($sourceModule->lessons as $sourceLesson) {
                $targetLesson = $this->cloneLessonRecord($sourceLesson, $targetModule, $copyMaterials, false);
                $lessonMap[$sourceLesson->id] = $targetLesson->id;

                if ($copyAssignments) {
                    foreach ($sourceLesson->assignments as $sourceAssignment) {
                        $this->cloneAssignmentRecord($sourceAssignment, $targetLesson, $copyRubrics, false);
                        $assignmentCount++;
                    }
                }
            }

            $materialCount = $copyMaterials
                ? $this->cloneMaterialAssignments($sourceModule->course, $lessonMap)
                : 0;

            return [
                'type' => 'module',
                'model' => $targetModule,
                'lessons' => count($lessonMap),
                'assignments' => $assignmentCount,
                'materials' => $materialCount,
            ];
        }, 3);
    }

    public function cloneLesson(Lesson $sourceLesson, Module $targetModule, array $options = []): array
    {
        return DB::transaction(function () use ($sourceLesson, $targetModule, $options) {
            $sourceLesson->loadMissing(['module.course', 'assignments']);
            $copyAssignments = (bool) ($options['copy_assignments'] ?? false);
            $copyMaterials = (bool) ($options['copy_materials'] ?? false);
            $copyRubrics = (bool) ($options['copy_rubrics'] ?? false);
            $targetLesson = $this->cloneLessonRecord($sourceLesson, $targetModule, $copyMaterials);
            $assignmentCount = 0;

            if ($copyAssignments) {
                foreach ($sourceLesson->assignments as $sourceAssignment) {
                    $this->cloneAssignmentRecord($sourceAssignment, $targetLesson, $copyRubrics, false);
                    $assignmentCount++;
                }
            }

            $materialCount = $copyMaterials
                ? $this->cloneMaterialAssignments($targetModule->course, [$sourceLesson->id => $targetLesson->id])
                : 0;

            return [
                'type' => 'lesson',
                'model' => $targetLesson,
                'lessons' => 1,
                'assignments' => $assignmentCount,
                'materials' => $materialCount,
            ];
        }, 3);
    }

    public function cloneAssignment(Assignments $sourceAssignment, Lesson $targetLesson, bool $copyRubric): array
    {
        return DB::transaction(function () use ($sourceAssignment, $targetLesson, $copyRubric) {
            $targetAssignment = $this->cloneAssignmentRecord($sourceAssignment, $targetLesson, $copyRubric);

            return [
                'type' => 'assignment',
                'model' => $targetAssignment,
                'assignments' => 1,
            ];
        }, 3);
    }

    public function cloneQuiz(Quiz $sourceQuiz, Course $targetCourse, bool $copyQuestions): array
    {
        return DB::transaction(function () use ($sourceQuiz, $targetCourse, $copyQuestions) {
            $sourceQuiz->refresh();
            $sourceQuiz->loadMissing('course.questionBanks');
            $targetQuiz = Quiz::create([
                'course_id' => $targetCourse->id,
                'template_origin_id' => null,
                'title' => $this->copyTitle($sourceQuiz->title),
                'time_limit' => $sourceQuiz->time_limit,
                'max_attempts' => $sourceQuiz->max_attempts ?: 1,
                'is_random' => $sourceQuiz->is_random,
                'easy_count' => $sourceQuiz->easy_count,
                'medium_count' => $sourceQuiz->medium_count,
                'hard_count' => $sourceQuiz->hard_count,
                'question_distribution' => $sourceQuiz->question_distribution,
                'status' => Quiz::STATUS_DRAFT,
                'published_at' => null,
                'available_from' => null,
            ]);

            $questionCount = $copyQuestions
                ? $this->cloneQuestionPool($sourceQuiz->course, $targetCourse)
                : 0;

            return [
                'type' => 'quiz',
                'model' => $targetQuiz,
                'quizzes' => 1,
                'questions' => $questionCount,
            ];
        }, 3);
    }

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
                    'tags' => $sourceQuestion->tags,
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
                'tags' => $source->tags,
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

    private function cloneLessonRecord(
        Lesson $sourceLesson,
        Module $targetModule,
        bool $copyMaterials,
        bool $appendCopyLabel = true
    ): Lesson {
        $attachment = $copyMaterials
            ? $this->copyLessonAttachment($sourceLesson)
            : ['attachment' => null, 'attachment_disk' => null];

        return Lesson::create([
            'module_id' => $targetModule->id,
            'template_origin_id' => null,
            'title' => $appendCopyLabel ? $this->copyTitle($sourceLesson->title) : $sourceLesson->title,
            'content' => $sourceLesson->content,
            'video_url' => $sourceLesson->video_url,
            'attachment_path' => $copyMaterials ? $sourceLesson->attachment_path : null,
            'attachment' => $attachment['attachment'],
            'attachment_disk' => $attachment['attachment_disk'],
            'attachment_original_name' => $copyMaterials ? $sourceLesson->attachment_original_name : null,
            'attachment_mime_type' => $copyMaterials ? $sourceLesson->attachment_mime_type : null,
            'attachment_size' => $copyMaterials ? $sourceLesson->attachment_size : null,
            'order' => Lesson::query()->where('module_id', $targetModule->id)->notArchived()->max('order') + 1,
            'status' => Lesson::STATUS_DRAFT,
            'published_at' => null,
            'available_from' => null,
        ]);
    }

    private function cloneAssignmentRecord(
        Assignments $sourceAssignment,
        Lesson $targetLesson,
        bool $copyRubric,
        bool $appendCopyLabel = true
    ): Assignments {
        $sourceAssignment->refresh();
        $targetLesson->loadMissing('module');

        return Assignments::create([
            'course_id' => $targetLesson->module->course_id,
            'template_origin_id' => null,
            'lesson_id' => $targetLesson->id,
            'type' => $sourceAssignment->type ?: 'file',
            'title' => $appendCopyLabel ? $this->copyTitle($sourceAssignment->title) : $sourceAssignment->title,
            'instructions' => $sourceAssignment->instructions,
            'grading_rubric' => $copyRubric ? $sourceAssignment->grading_rubric : null,
            'grading_scale' => $sourceAssignment->grading_scale,
            'ai_grading_enabled' => $copyRubric && $sourceAssignment->ai_grading_enabled,
            'due_date' => $sourceAssignment->due_date,
            'allowed_extensions' => $sourceAssignment->allowed_extensions,
            'max_file_size' => $sourceAssignment->max_file_size,
            'status' => Assignments::STATUS_DRAFT,
            'published_at' => null,
            'available_from' => null,
        ]);
    }

    private function cloneMaterialAssignments(Course $targetCourse, array $lessonMap): int
    {
        if ($lessonMap === []) {
            return 0;
        }

        $sourceAssignments = LearningMaterialAssignment::query()
            ->notArchived()
            ->whereIn('lesson_id', array_keys($lessonMap))
            ->get();

        foreach ($sourceAssignments as $sourceAssignment) {
            LearningMaterialAssignment::create([
                'learning_material_id' => $sourceAssignment->learning_material_id,
                'course_id' => $targetCourse->id,
                'class_id' => null,
                'lesson_id' => $lessonMap[$sourceAssignment->lesson_id],
                'unlock_when_lesson_id' => $lessonMap[$sourceAssignment->unlock_when_lesson_id] ?? null,
                'available_from' => null,
                'status' => LearningMaterialAssignment::STATUS_HIDDEN,
                'sort_order' => $sourceAssignment->sort_order,
            ]);
        }

        return $sourceAssignments->count();
    }

    private function cloneQuestionPool(Course $sourceCourse, Course $targetCourse): int
    {
        if ($sourceCourse->is($targetCourse)) {
            return 0;
        }

        $sourceCourse->loadMissing('questionBanks');
        $targetCourse->questionBanks()->syncWithoutDetaching($sourceCourse->questionBanks->pluck('id')->all());

        $questions = Question::query()
            ->with(['options', 'passage'])
            ->notArchived()
            ->where('course_id', $sourceCourse->id)
            ->whereNull('question_bank_id')
            ->get();

        $passageMap = [];
        foreach ($questions as $sourceQuestion) {
            $targetPassageId = null;
            if ($sourceQuestion->passage) {
                $targetPassageId = $passageMap[$sourceQuestion->quiz_passage_id] ??= QuizPassage::create([
                    'course_id' => $targetCourse->id,
                    'title' => $sourceQuestion->passage->title,
                    'content' => $sourceQuestion->passage->content,
                    'source_label' => $sourceQuestion->passage->source_label,
                ])->id;
            }

            $targetQuestion = Question::create([
                'course_id' => $targetCourse->id,
                'template_origin_id' => null,
                'question_bank_id' => null,
                'quiz_passage_id' => $targetPassageId,
                'question_type' => $sourceQuestion->question_type,
                'question_text' => $sourceQuestion->question_text,
                'answer_config' => $sourceQuestion->answer_config,
                'difficulty' => $sourceQuestion->difficulty,
                'tags' => $sourceQuestion->tags,
                'status' => $sourceQuestion->status ?? Question::STATUS_PUBLISHED,
            ]);

            foreach ($sourceQuestion->options as $sourceOption) {
                $targetQuestion->options()->create([
                    'option_text' => $sourceOption->option_text,
                    'is_correct' => $sourceOption->is_correct,
                ]);
            }
        }

        return $questions->count();
    }

    private function copyTitle(string $title): string
    {
        return Str::limit($title, 244, '').' (Bản sao)';
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
