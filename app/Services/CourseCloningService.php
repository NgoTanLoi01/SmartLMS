<?php

namespace App\Services;

use App\Models\Assignments;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseCloningService
{
    public function cloneContent(Course $sourceCourse, Course $targetCourse): void
    {
        $lessonIdMap = [];

        foreach ($sourceCourse->modules as $sourceModule) {
            $targetModule = Module::create([
                'course_id' => $targetCourse->id,
                'title' => $sourceModule->title,
                'order' => $sourceModule->order,
                'status' => $sourceModule->status ?? Module::STATUS_PUBLISHED,
            ]);

            foreach ($sourceModule->lessons as $sourceLesson) {
                $copiedAttachment = $this->copyLessonAttachment($sourceLesson);
                $targetLesson = Lesson::create([
                    'module_id' => $targetModule->id,
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
                'title' => $sourceQuiz->title,
                'time_limit' => $sourceQuiz->time_limit,
                'is_random' => $sourceQuiz->is_random,
                'easy_count' => $sourceQuiz->easy_count,
                'medium_count' => $sourceQuiz->medium_count,
                'hard_count' => $sourceQuiz->hard_count,
                'status' => $sourceQuiz->status,
                'published_at' => $sourceQuiz->published_at,
                'available_from' => $sourceQuiz->available_from,
            ]);
        }

        $targetCourse->questionBanks()->syncWithoutDetaching(
            $sourceCourse->questionBanks->pluck('id')->all()
        );

        $this->cloneCourseSpecificQuestions($sourceCourse, $targetCourse);
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
                    'question_bank_id' => null,
                    'question_text' => $sourceQuestion->question_text,
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

    private function copyLessonAttachment(Lesson $lesson): array
    {
        $path = $lesson->attachment;
        $sourceDisk = $lesson->attachment_disk ?: 'public';
        $targetDisk = config('filesystems.lesson_attachment_disk', $sourceDisk);

        $result = [
            'attachment' => $path,
            'attachment_disk' => $sourceDisk,
        ];

        if (! $path || ! Storage::disk($sourceDisk)->exists($path)) {
            return $result;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = Str::uuid().($extension ? '.'.$extension : '');
        $targetPath = 'lessons/attachments/'.$filename;

        Storage::disk($targetDisk)->put($targetPath, Storage::disk($sourceDisk)->get($path));

        return [
            'attachment' => $targetPath,
            'attachment_disk' => $targetDisk,
        ];
    }
}
