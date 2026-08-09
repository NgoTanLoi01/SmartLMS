<?php

namespace App\Queries\Grading;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherWorkQueueQuery
{
    /**
     * @param  Collection<int,int>  $courseIds
     * @param  array{type?:string,course_id?:int|string,urgency?:string,q?:string}  $filters
     */
    public function paginate(Collection $courseIds, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $classByCourse = DB::table('class_course')
            ->join('classes', 'classes.id', '=', 'class_course.class_id')
            ->where(function (Builder $query): void {
                $query->whereNull('classes.status')->orWhere('classes.status', 'active');
            })
            ->groupBy('class_course.course_id')
            ->select('class_course.course_id', DB::raw('MIN(classes.name) as class_name'));
        $overdueBefore = now()->subDays(7);

        $assignments = DB::table('assignment_submissions')
            ->join('assignments', 'assignments.id', '=', 'assignment_submissions.assignment_id')
            ->join('courses', 'courses.id', '=', 'assignments.course_id')
            ->join('users', 'users.id', '=', 'assignment_submissions.user_id')
            ->leftJoinSub($classByCourse, 'work_classes', fn ($join) => $join->on('work_classes.course_id', '=', 'courses.id'))
            ->whereIn('assignments.course_id', $courseIds)
            ->whereNull('assignment_submissions.grade')
            ->whereNull('assignments.deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('assignments.status')->orWhere('assignments.status', '!=', 'archived'))
            ->select([
                'assignment_submissions.id as record_id',
                'assignments.title as title',
                'assignments.course_id as course_id',
                'courses.title as course_title',
                'work_classes.class_name as class_name',
                'users.name as student_name',
                'assignments.due_date as deadline',
                DB::raw('COALESCE(assignment_submissions.submitted_at, assignment_submissions.created_at) as queued_at'),
                DB::raw('NULL as attempt_number'),
            ])
            ->addSelect(DB::raw("'assignment' as type"))
            ->addSelect(DB::raw("'ready_to_grade' as reason"))
            ->selectRaw('CASE WHEN COALESCE(assignment_submissions.submitted_at, assignment_submissions.created_at) <= ? THEN ? ELSE ? END as urgency', [$overdueBefore, 'overdue', 'ready']);

        $quizzes = DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->join('courses', 'courses.id', '=', 'quizzes.course_id')
            ->join('users', 'users.id', '=', 'quiz_attempts.user_id')
            ->leftJoinSub($classByCourse, 'work_classes', fn ($join) => $join->on('work_classes.course_id', '=', 'courses.id'))
            ->whereIn('quizzes.course_id', $courseIds)
            ->where('quiz_attempts.status', 'pending_grading')
            ->where(fn (Builder $query) => $query->whereNull('quizzes.status')->orWhere('quizzes.status', '!=', 'archived'))
            ->select([
                'quiz_attempts.id as record_id',
                'quizzes.title as title',
                'quizzes.course_id as course_id',
                'courses.title as course_title',
                'work_classes.class_name as class_name',
                'users.name as student_name',
                DB::raw('NULL as deadline'),
                DB::raw('COALESCE(quiz_attempts.completed_at, quiz_attempts.updated_at) as queued_at'),
                'quiz_attempts.attempt_number as attempt_number',
            ])
            ->addSelect(DB::raw("'quiz' as type"))
            ->addSelect(DB::raw("'manual_questions_pending' as reason"))
            ->selectRaw('CASE WHEN COALESCE(quiz_attempts.completed_at, quiz_attempts.updated_at) <= ? THEN ? ELSE ? END as urgency', [$overdueBefore, 'overdue', 'ready']);

        $query = DB::query()->fromSub($assignments->unionAll($quizzes), 'work_items')
            ->when(in_array($filters['type'] ?? null, ['assignment', 'quiz'], true), fn (Builder $query) => $query->where('type', $filters['type']))
            ->when(! empty($filters['course_id']), fn (Builder $query) => $query->where('course_id', (int) $filters['course_id']))
            ->when(in_array($filters['urgency'] ?? null, ['ready', 'overdue'], true), fn (Builder $query) => $query->where('urgency', $filters['urgency']))
            ->when(trim((string) ($filters['q'] ?? '')) !== '', function (Builder $query) use ($filters): void {
                $search = '%'.trim((string) $filters['q']).'%';
                $query->where(fn (Builder $match) => $match->where('title', 'like', $search)->orWhere('student_name', 'like', $search));
            })
            ->orderByRaw("CASE urgency WHEN 'overdue' THEN 0 ELSE 1 END")
            ->orderBy('queued_at')
            ->orderBy('type')
            ->orderBy('record_id');

        return $query->paginate($perPage)->withQueryString()->through(function (object $item): object {
            $item->action_url = $item->type === 'assignment'
                ? route('assignments.submissions.review', $item->record_id)
                : route('quiz-attempts.grade', $item->record_id);
            $item->reason_label = $item->type === 'assignment' ? 'Bài đã nộp, chưa có điểm' : 'Có câu hỏi cần chấm thủ công';

            return $item;
        });
    }
}
