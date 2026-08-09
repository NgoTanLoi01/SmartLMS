<?php

namespace App\Application\Assessment;

use App\Models\Assignments;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\NotificationCenter;
use App\Support\AssignmentUploadTypes;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreateAssignment
{
    public function __construct(private NotificationCenter $notifications) {}

    /** @param array<string, mixed> $input */
    public function handle(Course $course, array $input): Assignments
    {
        $lesson = Lesson::with('module')->findOrFail((int) $input['lesson_id']);
        abort_unless((int) $lesson->module?->course_id === (int) $course->id, 422, 'Bài học không thuộc khóa học đã chọn.');

        $input['allowed_extensions'] = $this->normalizeAllowedExtensions($input['allowed_extensions'] ?? null);
        $input['grading_scale'] = $input['grading_scale'] ?? 10;
        $input['ai_grading_enabled'] = (bool) ($input['ai_grading_enabled'] ?? false);
        $input['published_at'] = $input['status'] === Assignments::STATUS_PUBLISHED ? now() : null;

        $assignment = Assignments::create($input);
        if ($assignment->status === Assignments::STATUS_PUBLISHED) {
            $this->notifications->notifyCourseStudents(
                $assignment->course_id,
                'assignment',
                'Có bài tập mới',
                "Bài tập \"{$assignment->title}\" vừa được đăng.",
                route('courses.show', ['course' => $assignment->course_id, 'assignment_id' => $assignment->id]),
                ['assignment_id' => $assignment->id],
                "assignment:{$assignment->id}:published"
            );
        }

        return $assignment;
    }

    private function normalizeAllowedExtensions(?string $extensions): string
    {
        try {
            return AssignmentUploadTypes::normalize($extensions);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'allowed_extensions' => $exception->getMessage(),
            ]);
        }
    }
}
