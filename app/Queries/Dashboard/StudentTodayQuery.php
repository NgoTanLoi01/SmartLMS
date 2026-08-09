<?php

namespace App\Queries\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentTodayQuery
{
    /** @param array<string,mixed> $data
     * @return Collection<int,object>
     */
    public function build(array $data, Carbon $now): Collection
    {
        $items = collect();

        foreach ($data['pending_assignments'] ?? [] as $assignment) {
            $deadline = $assignment->due_date ? Carbon::parse($assignment->due_date) : null;
            $priority = $this->deadlinePriority($deadline, $now);
            if ($priority === null) {
                continue;
            }
            $items->push((object) [
                'key' => 'assignment:'.$assignment->id,
                'type' => 'assignment',
                'priority' => $priority,
                'title' => $assignment->title,
                'context' => $assignment->course_title ?? 'Khóa học',
                'deadline' => $deadline,
                'status' => $priority === 1 ? 'Quá hạn' : ($priority === 2 ? 'Hạn hôm nay' : 'Hạn trong 3 ngày'),
                'cta' => 'Nộp bài',
                'url' => route('courses.show', $assignment->course_id),
            ]);
        }

        foreach ($data['pending_quizzes'] ?? [] as $quiz) {
            $deadline = $quiz->dashboard_deadline ? Carbon::parse($quiz->dashboard_deadline) : null;
            $items->push((object) [
                'key' => 'quiz:'.$quiz->id,
                'type' => 'exam',
                'priority' => $deadline && $deadline->isToday() ? 2 : 3,
                'title' => $quiz->title,
                'context' => $quiz->course?->title ?? 'Khóa học',
                'deadline' => $deadline,
                'status' => $quiz->dashboard_action_label === 'Tiếp tục' ? 'Đang làm' : 'Có thể làm ngay',
                'cta' => $quiz->dashboard_action_label,
                'url' => route('quizzes.attempt', $quiz),
            ]);
        }

        foreach (($data['week_schedule'] ?? collect())->filter(fn ($slot) => Carbon::parse($slot->schedule_date)->isToday()) as $slot) {
            $items->push((object) [
                'key' => 'schedule:'.$slot->id,
                'type' => 'schedule',
                'priority' => 4,
                'title' => $slot->course_title,
                'context' => $slot->class_name,
                'deadline' => Carbon::parse($slot->schedule_date.' '.$slot->start_time),
                'status' => 'Lịch học hôm nay',
                'cta' => 'Mở khóa học',
                'url' => route('courses.show', $slot->course_id),
            ]);
        }

        if ($course = $data['continue_course'] ?? null) {
            $items->push((object) [
                'key' => 'course:'.$course->id,
                'type' => 'learning',
                'priority' => 5,
                'title' => $course->title,
                'context' => "Đã hoàn thành {$course->lesson_completed}/{$course->lesson_total} bài",
                'deadline' => null,
                'status' => "Tiến độ {$course->progress}%",
                'cta' => 'Tiếp tục học',
                'url' => route('courses.show', $course->id),
            ]);
        }

        if ($feedback = collect($data['recent_feedback'] ?? [])->first()) {
            $items->push((object) [
                'key' => 'feedback:'.md5($feedback->assignment_title.'|'.$feedback->updated_at),
                'type' => 'feedback',
                'priority' => 6,
                'title' => $feedback->assignment_title,
                'context' => $feedback->course_title,
                'deadline' => $feedback->updated_at ? Carbon::parse($feedback->updated_at) : null,
                'status' => 'Phản hồi gần đây',
                'cta' => 'Xem phản hồi',
                'url' => route('students.grades'),
            ]);
        }

        return $items->sort(function (object $left, object $right): int {
            if ($left->priority !== $right->priority) {
                return $left->priority <=> $right->priority;
            }
            if ($left->deadline && $right->deadline) {
                return $left->deadline <=> $right->deadline;
            }

            return $left->deadline ? -1 : ($right->deadline ? 1 : $left->key <=> $right->key);
        })->take(8)->values();
    }

    private function deadlinePriority(?Carbon $deadline, Carbon $now): ?int
    {
        if (! $deadline) {
            return null;
        }
        if ($deadline->lt($now)) {
            return 1;
        }
        if ($deadline->isSameDay($now)) {
            return 2;
        }

        return $deadline->lte($now->copy()->addDays(3)) ? 3 : null;
    }
}
