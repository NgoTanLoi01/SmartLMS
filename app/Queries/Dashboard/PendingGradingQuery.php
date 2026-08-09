<?php

namespace App\Queries\Dashboard;

use App\Models\QuizAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PendingGradingQuery
{
    /**
     * @param  Collection<int, int>  $courseIds
     * @return array{pending_assignment_grades: int, pending_quiz_grades: int, pending_grades: int, graded_assignment_count: int, grading_queue: Collection}
     */
    public function forTeacher(Collection $courseIds): array
    {
        $gradeCounts = DB::table('assignment_submissions')
            ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
            ->whereIn('assignments.course_id', $courseIds)
            ->whereNull('assignments.deleted_at')
            ->where($this->notArchivedColumn('assignments.status'))
            ->selectRaw('SUM(CASE WHEN assignment_submissions.grade IS NULL THEN 1 ELSE 0 END) as pending_count')
            ->selectRaw('SUM(CASE WHEN assignment_submissions.grade IS NOT NULL THEN 1 ELSE 0 END) as graded_count')
            ->first();
        $pendingAssignmentGrades = (int) ($gradeCounts->pending_count ?? 0);
        $gradedAssignmentCount = (int) ($gradeCounts->graded_count ?? 0);

        $pendingQuizGrades = QuizAttempt::query()
            ->where('status', QuizAttempt::STATUS_PENDING_GRADING)
            ->whereHas('quiz', fn ($query) => $query->whereIn('course_id', $courseIds))
            ->count();

        $assignmentQueue = DB::table('assignment_submissions')
            ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->join('users', 'assignment_submissions.user_id', '=', 'users.id')
            ->whereIn('assignments.course_id', $courseIds)
            ->whereNull('assignment_submissions.grade')
            ->whereNull('assignments.deleted_at')
            ->where($this->notArchivedColumn('assignments.status'))
            ->select(
                'assignment_submissions.*',
                'assignments.title as assignment_title',
                'assignments.due_date',
                'users.name as student_name',
                'courses.title as course_title',
                'courses.id as course_id'
            )
            ->orderByRaw('COALESCE(assignment_submissions.submitted_at, assignment_submissions.created_at) ASC')
            ->take(8)
            ->get()
            ->map(fn ($submission) => (object) [
                'type' => 'assignment',
                'title' => $submission->assignment_title,
                'student_name' => $submission->student_name,
                'course_title' => $submission->course_title,
                'queued_at' => $submission->submitted_at ?? $submission->created_at,
                'due_date' => $submission->due_date,
                'attempt_number' => null,
                'action_url' => route('assignments.submissions.review', $submission->id),
            ]);

        $quizQueue = QuizAttempt::query()
            ->with(['quiz.course:id,title', 'user:id,name'])
            ->where('status', QuizAttempt::STATUS_PENDING_GRADING)
            ->whereHas('quiz', fn ($query) => $query->whereIn('course_id', $courseIds))
            ->oldest('completed_at')
            ->take(8)
            ->get()
            ->map(fn (QuizAttempt $attempt) => (object) [
                'type' => 'quiz',
                'title' => $attempt->quiz?->title ?? 'Bài kiểm tra',
                'student_name' => $attempt->user?->name ?? 'Học viên',
                'course_title' => $attempt->quiz?->course?->title ?? 'Khóa học',
                'queued_at' => $attempt->completed_at ?? $attempt->updated_at,
                'due_date' => null,
                'attempt_number' => $attempt->attempt_number,
                'action_url' => route('quiz-attempts.grade', $attempt),
            ]);

        return [
            'pending_assignment_grades' => $pendingAssignmentGrades,
            'pending_quiz_grades' => $pendingQuizGrades,
            'pending_grades' => $pendingAssignmentGrades + $pendingQuizGrades,
            'graded_assignment_count' => $gradedAssignmentCount,
            'grading_queue' => $assignmentQueue
                ->concat($quizQueue)
                ->sortBy('queued_at')
                ->take(5)
                ->values(),
        ];
    }

    private function notArchivedColumn(string $column): \Closure
    {
        return function ($query) use ($column) {
            $query->whereNull($column)
                ->orWhere($column, '!=', 'archived');
        };
    }
}
