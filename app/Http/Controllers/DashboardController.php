<?php

namespace App\Http\Controllers;

use App\Models\BackupRun;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private const DASHBOARD_TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function index()
    {
        $user = auth()->user();
        $data = [];

        // Thiết lập khoảng thời gian tuần hiện tại (Thứ 2 đến Chủ nhật)
        $now = Carbon::now(self::DASHBOARD_TIMEZONE);
        $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endOfWeek = $now->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
        $todayDate = $now->toDateString();

        $data['dashboard_timezone'] = self::DASHBOARD_TIMEZONE;
        $data['dashboard_today'] = $todayDate;
        $data['dashboard_week_label'] = Carbon::parse($startOfWeek)->format('d/m').' - '.Carbon::parse($endOfWeek)->format('d/m/Y');
        // ==========================================
        // 1. DỮ LIỆU CHO ADMIN
        // ==========================================
        if ($user->role === 'admin') {
            $roleCounts = User::query()
                ->select('role', DB::raw('COUNT(*) as aggregate'))
                ->whereIn('role', ['student', 'teacher', 'admin'])
                ->groupBy('role')
                ->pluck('aggregate', 'role');
            $activeRoleCounts = User::query()
                ->select('role', DB::raw('COUNT(*) as aggregate'))
                ->whereIn('role', ['student', 'teacher', 'admin'])
                ->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', $now))
                ->groupBy('role')
                ->pluck('aggregate', 'role');
            $data['total_accounts'] = (int) $roleCounts->sum();
            $data['total_students'] = (int) ($activeRoleCounts['student'] ?? 0);
            $data['total_teachers'] = (int) ($activeRoleCounts['teacher'] ?? 0);
            $data['inactive_accounts_count'] = User::where('is_active', false)->count();
            $data['expired_accounts_count'] = User::where('is_active', true)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', $now)
                ->count();
            $data['account_attention_count'] = $data['inactive_accounts_count'] + $data['expired_accounts_count'];
            $data['total_classes'] = DB::table('classes')->where($this->notArchivedColumn('status'))->count();
            $data['total_courses'] = Course::where('course_type', 'delivery')->notArchived()->count();
            $data['recent_users'] = User::orderBy('created_at', 'desc')->take(7)->get();
            $data['chart_role_labels'] = ['Học sinh', 'Giáo viên', 'Admin'];
            $data['chart_role_data'] = [$data['total_students'], $data['total_teachers'], (int) ($activeRoleCounts['admin'] ?? 0)];
            $data['pending_assignment_grades'] = DB::table('assignment_submissions')
                ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->whereNull('assignment_submissions.grade')
                ->whereNull('assignments.deleted_at')
                ->where($this->notArchivedColumn('assignments.status'))
                ->where($this->notArchivedColumn('courses.status'))
                ->count();
            $data['pending_quiz_grades'] = QuizAttempt::where('status', QuizAttempt::STATUS_PENDING_GRADING)->count();
            $data['pending_grades'] = $data['pending_assignment_grades'] + $data['pending_quiz_grades'];
            $data['today_schedules'] = DB::table('schedules')
                ->join('courses', 'schedules.course_id', '=', 'courses.id')
                ->join('classes', 'schedules.class_id', '=', 'classes.id')
                ->where($this->activeOrLegacyColumn('schedules.status'))
                ->where($this->notArchivedColumn('classes.status'))
                ->where($this->notArchivedColumn('courses.status'))
                ->whereDate('schedules.schedule_date', $todayDate)
                ->select('schedules.*', 'courses.title as course_title', 'classes.name as class_name')
                ->orderBy('schedules.start_time')
                ->take(6)
                ->get();
            $data['class_overview'] = Classroom::withCount('students')
                ->notArchived()
                ->with(['teacher', 'courses'])
                ->orderByDesc('students_count')
                ->take(5)
                ->get();
            $data['recent_courses'] = Course::with('teacher')->where('course_type', 'delivery')->notArchived()->latest()->take(5)->get();
            $data['draft_courses_count'] = Course::where('course_type', 'delivery')->where('status', Course::STATUS_DRAFT)->count();
            $data['archived_courses_count'] = Course::where('course_type', 'delivery')->where('status', Course::STATUS_ARCHIVED)->count();
            $data['classes_without_teacher_count'] = Classroom::notArchived()->whereNull('teacher_id')->count();
            $data['classes_without_courses_count'] = Classroom::notArchived()->whereDoesntHave('courses')->count();
            $data['template_sync_pending_count'] = DB::table('courses as deliveries')
                ->join('courses as templates', 'deliveries.source_template_id', '=', 'templates.id')
                ->where('deliveries.course_type', 'delivery')
                ->where($this->notArchivedColumn('deliveries.status'))
                ->whereColumn('deliveries.synced_template_version', '<', 'templates.template_version')
                ->count();
            $data['failed_jobs_count'] = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
            $data['latest_backup'] = Schema::hasTable('backup_runs')
                ? BackupRun::latest('started_at')->first()
                : null;
        }

        // ==========================================
        // 2. DỮ LIỆU CHO GIÁO VIÊN
        // ==========================================
        elseif ($user->role === 'teacher') {
            $courseIds = Course::where('teacher_id', $user->id)
                ->where('course_type', 'delivery')
                ->notArchived()
                ->pluck('id');

            $data['total_courses'] = $courseIds->count();
            $data['teacher_classes'] = Classroom::where('teacher_id', $user->id)
                ->notArchived()
                ->withCount('students')
                ->with('courses')
                ->latest()
                ->take(6)
                ->get();

            $gradeCounts = DB::table('assignment_submissions')
                ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
                ->whereIn('assignments.course_id', $courseIds)
                ->whereNull('assignments.deleted_at')
                ->where($this->notArchivedColumn('assignments.status'))
                ->selectRaw('SUM(CASE WHEN assignment_submissions.grade IS NULL THEN 1 ELSE 0 END) as pending_count')
                ->selectRaw('SUM(CASE WHEN assignment_submissions.grade IS NOT NULL THEN 1 ELSE 0 END) as graded_count')
                ->first();
            $data['pending_assignment_grades'] = (int) ($gradeCounts->pending_count ?? 0);
            $gradedCount = (int) ($gradeCounts->graded_count ?? 0);

            $data['pending_quiz_grades'] = QuizAttempt::query()
                ->where('status', QuizAttempt::STATUS_PENDING_GRADING)
                ->whereHas('quiz', fn ($query) => $query->whereIn('course_id', $courseIds))
                ->count();
            $data['pending_grades'] = $data['pending_assignment_grades'] + $data['pending_quiz_grades'];

            // Tổng học sinh
            $data['total_students'] = DB::table('class_user')
                ->join('classes', 'class_user.class_id', '=', 'classes.id')
                ->where('classes.teacher_id', $user->id)
                ->where($this->notArchivedColumn('classes.status'))
                ->distinct('class_user.user_id')
                ->count();

            $data['chart_submission_labels'] = ['Bài tập đã chấm', 'Bài tập chờ chấm', 'Quiz tự luận'];
            $data['chart_submission_data'] = [$gradedCount, $data['pending_assignment_grades'], $data['pending_quiz_grades']];

            // LỊCH DẠY
            $data['week_schedule'] = DB::table('schedules')
                ->join('courses', 'schedules.course_id', '=', 'courses.id')
                ->join('classes', 'schedules.class_id', '=', 'classes.id')
                ->where('classes.teacher_id', $user->id)
                ->where($this->activeOrLegacyColumn('schedules.status'))
                ->where($this->notArchivedColumn('classes.status'))
                ->where($this->notArchivedColumn('courses.status'))
                ->whereDate('schedules.schedule_date', '>=', $startOfWeek)
                ->whereDate('schedules.schedule_date', '<=', $endOfWeek)
                ->select('schedules.*', 'courses.title as course_title', 'classes.name as class_name')
                ->orderBy('schedules.schedule_date', 'asc')
                ->orderBy('schedules.start_time', 'asc')
                ->get();

            $data['today_schedules_count'] = $data['week_schedule']
                ->filter(fn ($slot) => Carbon::parse($slot->schedule_date)->toDateString() === $todayDate)
                ->count();

            $data['next_schedule'] = DB::table('schedules')
                ->join('courses', 'schedules.course_id', '=', 'courses.id')
                ->join('classes', 'schedules.class_id', '=', 'classes.id')
                ->where('classes.teacher_id', $user->id)
                ->where($this->activeOrLegacyColumn('schedules.status'))
                ->where($this->notArchivedColumn('classes.status'))
                ->where($this->notArchivedColumn('courses.status'))
                ->where(function ($query) use ($todayDate, $now) {
                    $query->whereDate('schedules.schedule_date', '>', $todayDate)
                        ->orWhere(function ($todayQuery) use ($todayDate, $now) {
                            $todayQuery->whereDate('schedules.schedule_date', $todayDate)
                                ->where('schedules.start_time', '>=', $now->format('H:i:s'));
                        });
                })
                ->select('schedules.*', 'courses.title as course_title', 'classes.name as class_name')
                ->orderBy('schedules.schedule_date', 'asc')
                ->orderBy('schedules.start_time', 'asc')
                ->first();

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

            $data['grading_queue'] = $assignmentQueue
                ->concat($quizQueue)
                ->sortBy('queued_at')
                ->take(5)
                ->values();

            $gradeSummary = DB::table('assignment_submissions')
                ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
                ->whereIn('assignments.course_id', $courseIds)
                ->whereNotNull('assignment_submissions.grade')
                ->where('assignments.grading_scale', '>', 0)
                ->select('assignment_submissions.user_id', DB::raw('AVG((assignment_submissions.grade * 10.0) / assignments.grading_scale) as avg_grade'))
                ->groupBy('assignment_submissions.user_id');

            $missingSummary = DB::table('assignments')
                ->join('class_course', 'class_course.course_id', '=', 'assignments.course_id')
                ->join('class_user', 'class_user.class_id', '=', 'class_course.class_id')
                ->leftJoin('assignment_submissions', function ($join) {
                    $join->on('assignment_submissions.assignment_id', '=', 'assignments.id')
                        ->on('assignment_submissions.user_id', '=', 'class_user.user_id');
                })
                ->whereIn('assignments.course_id', $courseIds)
                ->where('assignments.status', 'published')
                ->whereNull('assignments.deleted_at')
                ->whereNotNull('assignments.due_date')
                ->where('assignments.due_date', '<', $now)
                ->whereNull('assignment_submissions.id')
                ->select('class_user.user_id', DB::raw('COUNT(DISTINCT assignments.id) as missing_count'))
                ->groupBy('class_user.user_id');

            $absenceSummary = DB::table('attendance_data')
                ->join('attendance_columns', 'attendance_data.attendance_column_id', '=', 'attendance_columns.id')
                ->whereIn('attendance_columns.course_id', $courseIds)
                ->where('attendance_columns.type', 'attendance')
                ->where('attendance_data.value', 'absent')
                ->select('attendance_data.user_id', DB::raw('COUNT(*) as absence_count'))
                ->groupBy('attendance_data.user_id');

            $teacherClassStudents = DB::table('class_user')
                ->join('classes', 'class_user.class_id', '=', 'classes.id')
                ->where('classes.teacher_id', $user->id)
                ->where($this->notArchivedColumn('classes.status'))
                ->select(
                    'class_user.user_id',
                    DB::raw('MIN(classes.id) as class_id'),
                    DB::raw('MIN(classes.name) as class_name')
                )
                ->groupBy('class_user.user_id');

            $attentionQuery = DB::table('users')
                ->joinSub($teacherClassStudents, 'teacher_students', fn ($join) => $join->on('teacher_students.user_id', '=', 'users.id'))
                ->leftJoinSub($gradeSummary, 'grade_summary', function ($join) {
                    $join->on('grade_summary.user_id', '=', 'users.id');
                })
                ->leftJoinSub($missingSummary, 'missing_summary', fn ($join) => $join->on('missing_summary.user_id', '=', 'users.id'))
                ->leftJoinSub($absenceSummary, 'absence_summary', fn ($join) => $join->on('absence_summary.user_id', '=', 'users.id'))
                ->where('users.role', 'student')
                ->where(function ($query) {
                    $query->where('grade_summary.avg_grade', '<', 5)
                        ->orWhere('missing_summary.missing_count', '>', 0)
                        ->orWhere('absence_summary.absence_count', '>=', 3);
                })
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'teacher_students.class_id',
                    'teacher_students.class_name',
                    'grade_summary.avg_grade',
                    DB::raw('COALESCE(missing_summary.missing_count, 0) as missing_count'),
                    DB::raw('COALESCE(absence_summary.absence_count, 0) as absence_count')
                );

            $data['attention_students_count'] = (clone $attentionQuery)->count();
            $data['attention_students'] = $attentionQuery
                ->orderByDesc('absence_count')
                ->orderByDesc('missing_count')
                ->orderBy('avg_grade')
                ->take(5)
                ->get();

            $data['teacher_priority_suggestions'] = $this->buildTeacherPrioritySuggestions($data, $now);
        }

        // ==========================================
        // 3. DỮ LIỆU CHO HỌC SINH
        // ==========================================
        else {
            // ==========================================
            // LẤY DANH SÁCH KHÓA HỌC HỌC SINH THAM GIA
            // ==========================================

            $courseIds = Course::visibleToStudents()
                ->whereHas('classes', function ($q) use ($user) {
                    $q->where('classes.status', 'active')
                        ->whereIn('classes.id', $user->classes()->where('classes.status', 'active')->pluck('classes.id'));
                })
                ->pluck('id');

            $data['total_courses'] = $courseIds->count();
            $data['course_progress'] = DB::table('courses')
                ->leftJoin('modules', function ($join) {
                    $join->on('modules.course_id', '=', 'courses.id')
                        ->where(function ($query) {
                            $query->whereNull('modules.status')
                                ->orWhere('modules.status', 'published');
                        });
                })
                ->leftJoin('lessons', function ($join) use ($now) {
                    $join->on('lessons.module_id', '=', 'modules.id')
                        ->where('lessons.status', 'published')
                        ->where(function ($query) use ($now) {
                            $query->whereNull('lessons.available_from')
                                ->orWhere('lessons.available_from', '<=', $now);
                        });
                })
                ->leftJoin('lesson_user', function ($join) use ($user) {
                    $join->on('lesson_user.lesson_id', '=', 'lessons.id')
                        ->where('lesson_user.user_id', $user->id)
                        ->whereNotNull('lesson_user.completed_at');
                })
                ->whereIn('courses.id', $courseIds)
                ->groupBy('courses.id', 'courses.title', 'courses.updated_at')
                ->select('courses.id', 'courses.title', 'courses.updated_at')
                ->selectRaw('COUNT(DISTINCT lessons.id) as lesson_total')
                ->selectRaw('COUNT(DISTINCT lesson_user.lesson_id) as lesson_completed')
                ->selectRaw('MAX(lesson_user.updated_at) as last_learning_activity')
                ->orderByRaw('MAX(lesson_user.updated_at) DESC')
                ->orderByDesc('courses.updated_at')
                ->limit(5)
                ->get()
                ->map(function ($course) {
                    $course->lesson_total = (int) $course->lesson_total;
                    $course->lesson_completed = (int) $course->lesson_completed;
                    $course->progress = $course->lesson_total > 0
                        ? round(($course->lesson_completed / $course->lesson_total) * 100)
                        : 0;

                    return $course;
                });

            // ==========================================
            // BÀI TẬP SẮP ĐẾN HẠN
            // ==========================================

            $pendingAssignmentsQuery = DB::table('assignments')

                ->join('courses', 'assignments.course_id', '=', 'courses.id')

                ->whereIn('assignments.course_id', $courseIds)
                ->where('assignments.status', 'published')
                ->where(function ($q) use ($now) {
                    $q->whereNull('assignments.available_from')
                        ->orWhere('assignments.available_from', '<=', $now);
                })

                // FIX SOFT DELETE
                ->whereNull('assignments.deleted_at')

                ->whereNotExists(function ($query) use ($user) {
                    $query->selectRaw('1')
                        ->from('assignment_submissions')
                        ->whereColumn('assignment_submissions.assignment_id', 'assignments.id')
                        ->where('assignment_submissions.user_id', $user->id);
                })

                ->select('assignments.*', 'courses.title as course_title', 'courses.id as course_id');

            $data['missing_assignments_count'] = (clone $pendingAssignmentsQuery)->count();
            $data['overdue_assignments_count'] = (clone $pendingAssignmentsQuery)
                ->whereNotNull('assignments.due_date')
                ->where('assignments.due_date', '<', $now)
                ->count();
            $data['pending_assignments'] = $pendingAssignmentsQuery
                ->orderByRaw('CASE WHEN assignments.due_date IS NULL THEN 2 WHEN assignments.due_date < ? THEN 0 ELSE 1 END', [$now])
                ->orderBy('assignments.due_date')
                ->take(5)
                ->get();

            // ==========================================
            // BÀI KIỂM TRA CHƯA LÀM
            // ==========================================

            $actionableQuizzes = Quiz::query()
                ->withCount('sessions')
                ->with([
                    'course:id,title',
                    'attempts' => fn ($query) => $query->where('user_id', $user->id)->orderBy('attempt_number'),
                    'sessions' => fn ($query) => $query
                        ->whereHas('candidates', fn ($candidateQuery) => $candidateQuery->where('users.id', $user->id))
                        ->whereIn('status', [QuizSession::STATUS_SCHEDULED, QuizSession::STATUS_OPEN])
                        ->where('starts_at', '<=', $now)
                        ->where('ends_at', '>=', $now),
                ])
                ->whereIn('course_id', $courseIds)
                ->visibleToStudents()
                ->latest()
                ->get()
                ->map(function (Quiz $quiz) {
                    $session = $quiz->sessions->first();
                    if ($quiz->sessions_count > 0 && ! $session) {
                        return null;
                    }

                    $attempts = $session
                        ? $quiz->attempts->where('quiz_session_id', $session->id)
                        : $quiz->attempts->whereNull('quiz_session_id');
                    $inProgress = $attempts->first(fn (QuizAttempt $attempt) => $attempt->isInProgress());
                    $maxAttempts = max(1, (int) ($quiz->max_attempts ?: 1));
                    if (! $inProgress && $attempts->count() >= $maxAttempts) {
                        return null;
                    }

                    $hasCompletedAttempt = $attempts->contains(fn (QuizAttempt $attempt) => $attempt->completed_at !== null);
                    $quiz->dashboard_action_label = $inProgress
                        ? 'Tiếp tục'
                        : ($hasCompletedAttempt ? 'Làm lại' : 'Làm ngay');
                    $quiz->dashboard_session_name = $session?->name;
                    $quiz->dashboard_attempts_used = $attempts->count();

                    return $quiz;
                })
                ->filter()
                ->values();

            $data['pending_quizzes_count'] = $actionableQuizzes->count();
            $data['pending_quizzes'] = $actionableQuizzes->take(5);
            // ==========================================
            // ĐIỂM TRUNG BÌNH QUIZ
            // ==========================================

            $releasedQuizResults = QuizAttempt::query()
                ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
                ->where('user_id', $user->id)
                ->whereIn('quizzes.course_id', $courseIds)
                ->resultsReleased()
                ->select(
                    'quiz_attempts.quiz_id',
                    'quizzes.title',
                    DB::raw('MAX(quiz_attempts.score) as best_score'),
                    DB::raw('MAX(quiz_attempts.completed_at) as latest_completed_at')
                )
                ->groupBy('quiz_attempts.quiz_id', 'quizzes.title')
                ->orderByDesc('latest_completed_at')
                ->get();

            $averageQuizScore = $releasedQuizResults->avg(fn ($result) => (float) $result->best_score);
            $data['average_score'] = $releasedQuizResults->isNotEmpty() ? round($averageQuizScore, 1) : 0;

            $data['recent_feedback'] = DB::table('assignment_submissions')
                ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->where('assignment_submissions.user_id', $user->id)
                ->whereIn('assignments.course_id', $courseIds)
                ->where(function ($query) {
                    $query->whereNotNull('assignment_submissions.grade')
                        ->orWhereNotNull('assignment_submissions.feedback');
                })
                ->select('assignment_submissions.grade', 'assignment_submissions.feedback', 'assignment_submissions.updated_at', 'assignments.title as assignment_title', 'courses.title as course_title', 'courses.id as course_id')
                ->latest('assignment_submissions.updated_at')
                ->take(4)
                ->get();

            // ==========================================
            // DỮ LIỆU BIỂU ĐỒ QUIZ
            // ==========================================

            $recentQuizzes = $releasedQuizResults
                ->take(5)
                ->reverse()
                ->values();

            $data['chart_quiz_labels'] = $recentQuizzes->pluck('title')->toArray();

            $data['chart_quiz_data'] = $recentQuizzes->pluck('best_score')->map(fn ($score) => (float) $score)->toArray();

            // ==========================================
            // LỊCH HỌC
            // ==========================================

            $data['week_schedule'] = DB::table('schedules')

                ->join('courses', 'schedules.course_id', '=', 'courses.id')

                ->join('classes', 'schedules.class_id', '=', 'classes.id')

                ->join('class_user', 'classes.id', '=', 'class_user.class_id')

                ->where('class_user.user_id', $user->id)
                ->where($this->activeOrLegacyColumn('schedules.status'))
                ->where('classes.status', 'active')
                ->where($this->notArchivedColumn('courses.status'))

                ->whereDate('schedules.schedule_date', '>=', $startOfWeek)

                ->whereDate('schedules.schedule_date', '<=', $endOfWeek)

                ->select('schedules.*', 'courses.title as course_title', 'classes.name as class_name')

                ->orderBy('schedules.schedule_date', 'asc')

                ->orderBy('schedules.start_time', 'asc')

                ->get();

            $data['next_schedule'] = DB::table('schedules')
                ->join('courses', 'schedules.course_id', '=', 'courses.id')
                ->join('classes', 'schedules.class_id', '=', 'classes.id')
                ->join('class_user', 'classes.id', '=', 'class_user.class_id')
                ->where('class_user.user_id', $user->id)
                ->where($this->activeOrLegacyColumn('schedules.status'))
                ->where('classes.status', 'active')
                ->where($this->notArchivedColumn('courses.status'))
                ->where(function ($query) use ($todayDate, $now) {
                    $query->whereDate('schedules.schedule_date', '>', $todayDate)
                        ->orWhere(function ($todayQuery) use ($todayDate, $now) {
                            $todayQuery->whereDate('schedules.schedule_date', $todayDate)
                                ->where('schedules.end_time', '>=', $now->format('H:i:s'));
                        });
                })
                ->select('schedules.*', 'courses.title as course_title', 'classes.name as class_name')
                ->orderBy('schedules.schedule_date')
                ->orderBy('schedules.start_time')
                ->first();

            $data['continue_course'] = $data['course_progress']
                ->first(fn ($course) => $course->lesson_total > 0 && $course->progress < 100)
                ?? $data['course_progress']->first();
        }

        return view('dashboard', compact('data'));
    }

    private function notArchivedColumn(string $column): \Closure
    {
        return function ($query) use ($column) {
            $query->whereNull($column)
                ->orWhere($column, '!=', 'archived');
        };
    }

    private function activeOrLegacyColumn(string $column): \Closure
    {
        return function ($query) use ($column) {
            $query->whereNull($column)
                ->orWhere($column, 'active');
        };
    }

    private function buildTeacherPrioritySuggestions(array $data, Carbon $now): array
    {
        $suggestions = [];
        $pendingGrades = (int) ($data['pending_grades'] ?? 0);
        $todaySchedules = (int) ($data['today_schedules_count'] ?? 0);
        $gradingQueue = $data['grading_queue'] ?? collect();
        $attentionStudents = $data['attention_students'] ?? collect();
        $nextSchedule = $data['next_schedule'] ?? null;

        if ($gradingQueue->isNotEmpty()) {
            $firstItem = $gradingQueue->first();
            $isQuiz = $firstItem->type === 'quiz';

            $suggestions[] = [
                'type' => 'warning',
                'icon' => 'fas fa-pen',
                'title' => $isQuiz ? 'Quiz tự luận chờ lâu nhất' : 'Bài nộp chờ lâu nhất',
                'body' => "{$firstItem->student_name} đang chờ chấm \"{$firstItem->title}\".",
                'action_label' => 'Chấm bài',
                'action_url' => $firstItem->action_url,
            ];
        } elseif ($pendingGrades === 0) {
            $suggestions[] = [
                'type' => 'success',
                'icon' => 'fas fa-check-circle',
                'title' => 'Không còn bài chờ chấm',
                'body' => 'Có thể tranh thủ cập nhật bài học, tạo quiz ôn tập hoặc xem tiến độ lớp.',
                'action_label' => 'Mở khóa học',
                'action_url' => route('courses.index'),
            ];
        }

        if ($nextSchedule) {
            $scheduleStart = Carbon::parse($nextSchedule->schedule_date.' '.$nextSchedule->start_time);
            $suggestions[] = [
                'type' => $scheduleStart->isToday() ? 'primary' : 'info',
                'icon' => 'fas fa-calendar-day',
                'title' => $scheduleStart->isToday() ? 'Chuẩn bị ca dạy kế tiếp' : 'Xem trước lịch dạy gần nhất',
                'body' => "{$nextSchedule->course_title} - {$nextSchedule->class_name}, {$scheduleStart->format('H:i d/m')}.",
                'action_label' => 'Xem lịch',
                'action_url' => route('schedules.index'),
            ];
        } elseif ($todaySchedules === 0) {
            $suggestions[] = [
                'type' => 'muted',
                'icon' => 'fas fa-calendar-check',
                'title' => 'Hôm nay chưa có ca dạy sắp tới',
                'body' => 'Có thể dùng thời gian này để chuẩn bị nội dung hoặc rà soát bài chưa chấm.',
                'action_label' => 'Mở khóa học',
                'action_url' => route('courses.index'),
            ];
        }

        if ($attentionStudents->isNotEmpty()) {
            $student = $attentionStudents->first();
            $signals = collect([
                $student->avg_grade !== null && $student->avg_grade < 5
                    ? 'điểm TB '.round($student->avg_grade, 1)
                    : null,
                ($student->missing_count ?? 0) > 0
                    ? "thiếu {$student->missing_count} bài"
                    : null,
                ($student->absence_count ?? 0) >= 3
                    ? "vắng {$student->absence_count} buổi"
                    : null,
            ])->filter()->implode(' · ');
            $suggestions[] = [
                'type' => 'warning',
                'icon' => 'fas fa-user-clock',
                'title' => 'Theo dõi học sinh cần hỗ trợ',
                'body' => "{$student->name} thuộc {$student->class_name}: {$signals}.",
                'action_label' => 'Xem hồ sơ',
                'action_url' => route('classes.students.show', ['classId' => $student->class_id, 'studentId' => $student->id]),
            ];
        }

        return array_slice($suggestions, 0, 3);
    }
}
