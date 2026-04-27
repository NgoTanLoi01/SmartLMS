<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\Assignments;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $data = [];

        // Thiết lập khoảng thời gian tuần hiện tại (Thứ 2 đến Chủ nhật)
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // 1. DỮ LIỆU CHO ADMIN (Giữ nguyên)
        if ($user->role === 'admin') {
            $data['total_students'] = User::where('role', 'student')->count();
            $data['total_teachers'] = User::where('role', 'teacher')->count();
            $data['total_classes'] = DB::table('classes')->count();
            $data['total_courses'] = Course::count();
            $data['online_users'] = rand(5, 20);
            $data['recent_users'] = User::orderBy('created_at', 'desc')->take(5)->get();
            $data['chart_role_labels'] = ['Học sinh', 'Giáo viên', 'Admin'];
            $data['chart_role_data'] = [$data['total_students'], $data['total_teachers'], User::where('role', 'admin')->count()];
        }

        // 2. DỮ LIỆU CHO GIÁO VIÊN
        elseif ($user->role === 'teacher') {
            $courseIds = Course::where('teacher_id', $user->id)->pluck('id');
            $data['total_courses'] = $courseIds->count();

            // Giữ nguyên thống kê bài tập
            $data['pending_grades'] = DB::table('assignment_submissions')->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')->join('lessons', 'assignments.lesson_id', '=', 'lessons.id')->join('modules', 'lessons.module_id', '=', 'modules.id')->whereIn('modules.course_id', $courseIds)->whereNull('assignment_submissions.grade')->count();

            $gradedCount = DB::table('assignment_submissions')->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')->join('lessons', 'assignments.lesson_id', '=', 'lessons.id')->join('modules', 'lessons.module_id', '=', 'modules.id')->whereIn('modules.course_id', $courseIds)->whereNotNull('assignment_submissions.grade')->count();

            $data['total_students'] = DB::table('class_user')->join('classes', 'class_user.class_id', '=', 'classes.id')->where('classes.teacher_id', $user->id)->distinct('class_user.user_id')->count();

            $data['chart_submission_labels'] = ['Đã chấm', 'Chờ chấm'];
            $data['chart_submission_data'] = [$gradedCount, $data['pending_grades']];
            $data['recent_submissions'] = DB::table('assignment_submissions')
                ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
                ->join('users', 'assignment_submissions.user_id', '=', 'users.id')
                ->whereIn('assignments.lesson_id', function ($q) use ($courseIds) {
                    $q->select('id')
                        ->from('lessons')
                        ->whereIn('module_id', function ($sq) use ($courseIds) {
                            $sq->select('id')->from('modules')->whereIn('course_id', $courseIds);
                        });
                })
                ->whereNull('assignment_submissions.grade')
                ->select('assignment_submissions.*', 'assignments.title as assignment_title', 'users.name as student_name')
                ->orderBy('assignment_submissions.created_at', 'desc')
                ->take(5)
                ->get();

            // CẬP NHẬT: Lấy lịch dạy CẢ TUẦN
            $data['week_schedule'] = DB::table('schedules')
                ->join('courses', 'schedules.course_id', '=', 'courses.id')
                ->join('classes', 'schedules.class_id', '=', 'classes.id')
                ->where('classes.teacher_id', $user->id)
                ->whereBetween('schedules.schedule_date', [$startOfWeek, $endOfWeek]) // Lọc theo tuần
                ->select('schedules.*', 'courses.title as course_title', 'classes.name as class_name')
                ->orderBy('schedules.schedule_date', 'asc')
                ->orderBy('schedules.start_time', 'asc')
                ->get();
        }

        // 3. DỮ LIỆU CHO HỌC SINH
        else {
            $courseIds = DB::table('class_user')->where('user_id', $user->id)->join('class_course', 'class_user.class_id', '=', 'class_course.class_id')->pluck('course_id');
            $data['total_courses'] = $courseIds->count();
            $data['upcoming_deadlines'] = Assignments::whereIn('lesson_id', function ($q) use ($courseIds) {
                $q->select('id')
                    ->from('lessons')
                    ->whereIn('module_id', function ($sq) use ($courseIds) {
                        $sq->select('id')->from('modules')->whereIn('course_id', $courseIds);
                    });
            })
                ->where('due_date', '>=', now())
                ->orderBy('due_date', 'asc')
                ->take(5)
                ->get();

            $avgQuizScore = DB::table('quiz_attempts')->where('user_id', $user->id)->avg('score');
            $data['average_score'] = $avgQuizScore ? round($avgQuizScore, 1) : 0;
            $recentQuizzes = DB::table('quiz_attempts')->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')->where('quiz_attempts.user_id', $user->id)->select('quizzes.title', 'quiz_attempts.score')->orderBy('quiz_attempts.completed_at', 'asc')->take(5)->get();
            $data['chart_quiz_labels'] = $recentQuizzes->pluck('title')->toArray();
            $data['chart_quiz_data'] = $recentQuizzes->pluck('score')->toArray();

            // CẬP NHẬT: Lấy lịch học CẢ TUẦN
            $data['week_schedule'] = DB::table('schedules')
                ->join('courses', 'schedules.course_id', '=', 'courses.id')
                ->join('classes', 'schedules.class_id', '=', 'classes.id')
                ->join('class_user', 'classes.id', '=', 'class_user.class_id')
                ->where('class_user.user_id', $user->id)
                ->whereBetween('schedules.schedule_date', [$startOfWeek, $endOfWeek]) // Lọc theo tuần
                ->select('schedules.*', 'courses.title as course_title', 'classes.name as class_name')
                ->orderBy('schedules.schedule_date', 'asc')
                ->orderBy('schedules.start_time', 'asc')
                ->get();
        }

        return view('dashboard', compact('data'));
    }
}
