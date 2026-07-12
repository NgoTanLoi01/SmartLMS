<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\Classroom;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        $data['dashboard_week_label'] = Carbon::parse($startOfWeek)->format('d/m') . ' - ' . Carbon::parse($endOfWeek)->format('d/m/Y');
        // ==========================================
        // 1. DỮ LIỆU CHO ADMIN
        // ==========================================
        if ($user->role === 'admin') {
            $roleCounts = User::query()
                ->select('role', DB::raw('COUNT(*) as aggregate'))
                ->whereIn('role', ['student', 'teacher', 'admin'])
                ->groupBy('role')
                ->pluck('aggregate', 'role');
            $data['total_students'] = (int) ($roleCounts['student'] ?? 0);
            $data['total_teachers'] = (int) ($roleCounts['teacher'] ?? 0);
            $data['total_classes'] = DB::table('classes')->where($this->notArchivedColumn('status'))->count();
            $data['total_courses'] = Course::where('course_type', 'delivery')->notArchived()->count();
            $data['recent_users'] = User::orderBy('created_at', 'desc')->take(7)->get();
            $data['chart_role_labels'] = ['Học sinh', 'Giáo viên', 'Admin'];
            $data['chart_role_data'] = [$data['total_students'], $data['total_teachers'], (int) ($roleCounts['admin'] ?? 0)];
            $data['pending_grades'] = DB::table('assignment_submissions')->whereNull('grade')->count();
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
            $data['draft_courses_count'] = Course::where('status', Course::STATUS_DRAFT)->count();
            $data['archived_courses_count'] = Course::where('status', Course::STATUS_ARCHIVED)->count();
            $data['classes_without_teacher_count'] = Classroom::notArchived()->whereNull('teacher_id')->count();
            $data['classes_without_courses_count'] = Classroom::notArchived()->whereDoesntHave('courses')->count();
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
                ->selectRaw('SUM(CASE WHEN assignment_submissions.grade IS NULL THEN 1 ELSE 0 END) as pending_count')
                ->selectRaw('SUM(CASE WHEN assignment_submissions.grade IS NOT NULL THEN 1 ELSE 0 END) as graded_count')
                ->first();
            $data['pending_grades'] = (int) ($gradeCounts->pending_count ?? 0);
            $gradedCount = (int) ($gradeCounts->graded_count ?? 0);

            // Tổng học sinh
            $data['total_students'] = DB::table('class_user')
                ->join('classes', 'class_user.class_id', '=', 'classes.id')
                ->where('classes.teacher_id', $user->id)
                ->where($this->notArchivedColumn('classes.status'))
                ->distinct('class_user.user_id')
                ->count();

            $data['chart_submission_labels'] = ['Đã chấm', 'Chờ chấm'];
            $data['chart_submission_data'] = [$gradedCount, $data['pending_grades']];

            // Bài nộp gần đây
            $data['recent_submissions'] = DB::table('assignment_submissions')->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')->join('courses', 'assignments.course_id', '=', 'courses.id')->join('users', 'assignment_submissions.user_id', '=', 'users.id')->whereIn('assignments.course_id', $courseIds)->whereNull('assignment_submissions.grade')->select('assignment_submissions.*', 'assignments.title as assignment_title', 'users.name as student_name', 'courses.title as course_title', 'courses.id as course_id')->latest('assignment_submissions.created_at')->take(5)->get();

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

            $data['priority_submissions'] = DB::table('assignment_submissions')
                ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->join('users', 'assignment_submissions.user_id', '=', 'users.id')
                ->whereIn('assignments.course_id', $courseIds)
                ->whereNull('assignment_submissions.grade')
                ->select(
                    'assignment_submissions.*',
                    'assignments.title as assignment_title',
                    'assignments.due_date',
                    'users.name as student_name',
                    'courses.title as course_title',
                    'courses.id as course_id'
                )
                ->orderByRaw('COALESCE(assignment_submissions.submitted_at, assignment_submissions.created_at) DESC')
                ->orderBy('assignment_submissions.id', 'desc')
                ->take(5)
                ->get();

            $gradeSummary = DB::table('assignment_submissions')
                ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
                ->whereIn('assignments.course_id', $courseIds)
                ->select('assignment_submissions.user_id', DB::raw('AVG(assignment_submissions.grade) as avg_grade'))
                ->groupBy('assignment_submissions.user_id');

            $data['attention_students'] = DB::table('class_user')
                ->join('classes', 'class_user.class_id', '=', 'classes.id')
                ->join('users', 'class_user.user_id', '=', 'users.id')
                ->leftJoinSub($gradeSummary, 'grade_summary', function ($join) {
                    $join->on('grade_summary.user_id', '=', 'users.id');
                })
                ->where('classes.teacher_id', $user->id)
                ->where($this->notArchivedColumn('classes.status'))
                ->where('users.role', 'student')
                ->select('users.id', 'users.name', 'users.email', 'classes.id as class_id', 'classes.name as class_name', 'grade_summary.avg_grade')
                ->whereNotNull('grade_summary.avg_grade')
                ->where('grade_summary.avg_grade', '<', 5)
                ->orderBy('avg_grade')
                ->take(5)
                ->get();

            $data['attention_students_count'] = $data['attention_students']->count();

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
                ->leftJoin('modules', 'modules.course_id', '=', 'courses.id')
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
                ->groupBy('courses.id', 'courses.title')
                ->select('courses.id', 'courses.title')
                ->selectRaw('COUNT(DISTINCT lessons.id) as lesson_total')
                ->selectRaw('COUNT(DISTINCT lesson_user.lesson_id) as lesson_completed')
                ->orderByDesc('courses.created_at')
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

            $data['upcoming_deadlines'] = DB::table('assignments')

                ->join('courses', 'assignments.course_id', '=', 'courses.id')

                ->whereIn('assignments.course_id', $courseIds)
                ->where('assignments.status', 'published')
                ->where(function ($q) use ($now) {
                    $q->whereNull('assignments.available_from')
                        ->orWhere('assignments.available_from', '<=', $now);
                })

                // FIX SOFT DELETE
                ->whereNull('assignments.deleted_at')

                ->where('assignments.due_date', '>=', $now)
                ->whereNotExists(function ($query) use ($user) {
                    $query->selectRaw('1')
                        ->from('assignment_submissions')
                        ->whereColumn('assignment_submissions.assignment_id', 'assignments.id')
                        ->where('assignment_submissions.user_id', $user->id);
                })

                ->select('assignments.*', 'courses.title as course_title', 'courses.id as course_id')

                ->orderBy('assignments.due_date', 'asc')

                ->take(5)

                ->get();
            $data['missing_assignments_count'] = DB::table('assignments')
                ->whereIn('course_id', $courseIds)
                ->where('status', 'published')
                ->where(function ($q) use ($now) {
                    $q->whereNull('available_from')
                        ->orWhere('available_from', '<=', $now);
                })
                ->whereNull('deleted_at')
                ->whereNotExists(function ($query) use ($user) {
                    $query->selectRaw('1')
                        ->from('assignment_submissions')
                        ->whereColumn('assignment_submissions.assignment_id', 'assignments.id')
                        ->where('assignment_submissions.user_id', $user->id);
                })
                ->count();

            // ==========================================
            // BÀI KIỂM TRA CHƯA LÀM
            // ==========================================

            $data['pending_quizzes'] = DB::table('quizzes')

                ->join('courses', 'quizzes.course_id', '=', 'courses.id')

                ->whereIn('quizzes.course_id', $courseIds)
                ->where('quizzes.status', 'published')
                ->where(function ($q) use ($now) {
                    $q->whereNull('quizzes.available_from')
                        ->orWhere('quizzes.available_from', '<=', $now);
                })

                ->whereNotExists(function ($query) use ($user) {
                    $query->selectRaw('1')
                        ->from('quiz_attempts')
                        ->whereColumn('quiz_attempts.quiz_id', 'quizzes.id')
                        ->where('quiz_attempts.user_id', $user->id);
                })

                ->select('quizzes.*', 'courses.title as course_title', 'courses.id as course_id')

                ->orderBy('quizzes.created_at', 'desc')

                ->take(5)

                ->get();
            $data['pending_quizzes_count'] = DB::table('quizzes')
                ->whereIn('course_id', $courseIds)
                ->where('status', 'published')
                ->where(function ($q) use ($now) {
                    $q->whereNull('available_from')
                        ->orWhere('available_from', '<=', $now);
                })
                ->whereNotExists(function ($query) use ($user) {
                    $query->selectRaw('1')
                        ->from('quiz_attempts')
                        ->whereColumn('quiz_attempts.quiz_id', 'quizzes.id')
                        ->where('quiz_attempts.user_id', $user->id);
                })
                ->count();
            // ==========================================
            // ĐIỂM TRUNG BÌNH QUIZ
            // ==========================================

            $avgQuizScore = DB::table('quiz_attempts')

                ->where('user_id', $user->id)

                ->avg('score');

            $data['average_score'] = $avgQuizScore ? round($avgQuizScore, 1) : 0;

            $data['recent_feedback'] = DB::table('assignment_submissions')
                ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->where('assignment_submissions.user_id', $user->id)
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

            $recentQuizzes = DB::table('quiz_attempts')

                ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')

                ->where('quiz_attempts.user_id', $user->id)

                ->select('quizzes.title', 'quiz_attempts.score')

                ->orderBy('quiz_attempts.completed_at', 'asc')

                ->take(5)

                ->get();

            $data['chart_quiz_labels'] = $recentQuizzes->pluck('title')->toArray();

            $data['chart_quiz_data'] = $recentQuizzes->pluck('score')->toArray();

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
        $prioritySubmissions = $data['priority_submissions'] ?? collect();
        $attentionStudents = $data['attention_students'] ?? collect();
        $nextSchedule = $data['next_schedule'] ?? null;

        if ($prioritySubmissions->isNotEmpty()) {
            $firstSubmission = $prioritySubmissions->first();
            $dueDate = $firstSubmission->due_date ? Carbon::parse($firstSubmission->due_date) : null;
            $isOverdue = $dueDate && $dueDate->lt($now);

            $suggestions[] = [
                'type' => $isOverdue ? 'danger' : 'warning',
                'icon' => 'fas fa-pen',
                'title' => $isOverdue ? 'Ưu tiên chấm bài quá hạn' : 'Nên chấm bài sắp đến hạn trước',
                'body' => "{$firstSubmission->student_name} đang chờ chấm bài \"{$firstSubmission->assignment_title}\".",
                'action_label' => 'Chấm bài',
                'action_url' => route('assignments.submissions.review', $firstSubmission->id),
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
            $scheduleStart = Carbon::parse($nextSchedule->schedule_date . ' ' . $nextSchedule->start_time);
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
            $suggestions[] = [
                'type' => 'warning',
                'icon' => 'fas fa-user-clock',
                'title' => 'Theo dõi học sinh cần hỗ trợ',
                'body' => "{$student->name} thuộc {$student->class_name}, điểm TB " . ($student->avg_grade !== null ? round($student->avg_grade, 1) : 'chưa có dữ liệu') . '.',
                'action_label' => 'Xem hồ sơ',
                'action_url' => route('classes.students.show', ['classId' => $student->class_id, 'studentId' => $student->id]),
            ];
        }

        return array_slice($suggestions, 0, 3);
    }
}
