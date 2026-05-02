<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
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

        // ==========================================
        // 1. DỮ LIỆU CHO ADMIN
        // ==========================================
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

        // ==========================================
        // 2. DỮ LIỆU CHO GIÁO VIÊN
        // ==========================================
        elseif ($user->role === 'teacher') {
            $courseIds = Course::where('teacher_id', $user->id)->pluck('id');
            $data['total_courses'] = $courseIds->count();

            // Đếm bài tập chờ chấm và đã chấm (Tối ưu query)
            $data['pending_grades'] = DB::table('assignment_submissions')->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')->whereIn('assignments.course_id', $courseIds)->whereNull('assignment_submissions.grade')->count();

            $gradedCount = DB::table('assignment_submissions')->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')->whereIn('assignments.course_id', $courseIds)->whereNotNull('assignment_submissions.grade')->count();

            $data['total_students'] = DB::table('class_user')->join('classes', 'class_user.class_id', '=', 'classes.id')->where('classes.teacher_id', $user->id)->distinct('class_user.user_id')->count();

            $data['chart_submission_labels'] = ['Đã chấm', 'Chờ chấm'];
            $data['chart_submission_data'] = [$gradedCount, $data['pending_grades']];

            // Danh sách bài nộp chờ chấm (Bổ sung lấy Tên khóa học và ID khóa học)
            $data['recent_submissions'] = DB::table('assignment_submissions')->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')->join('courses', 'assignments.course_id', '=', 'courses.id')->join('users', 'assignment_submissions.user_id', '=', 'users.id')->whereIn('assignments.course_id', $courseIds)->whereNull('assignment_submissions.grade')->select('assignment_submissions.*', 'assignments.title as assignment_title', 'users.name as student_name', 'courses.title as course_title', 'courses.id as course_id')->orderBy('assignment_submissions.created_at', 'desc')->take(5)->get();

            // Lịch dạy trong tuần
            $data['week_schedule'] = DB::table('schedules')
                ->join('courses', 'schedules.course_id', '=', 'courses.id')
                ->join('classes', 'schedules.class_id', '=', 'classes.id')
                ->where('classes.teacher_id', $user->id)
                ->whereBetween('schedules.schedule_date', [$startOfWeek, $endOfWeek])
                ->select('schedules.*', 'courses.title as course_title', 'classes.name as class_name')
                ->orderBy('schedules.schedule_date', 'asc')
                ->orderBy('schedules.start_time', 'asc')
                ->get();
        }

        // ==========================================
        // 3. DỮ LIỆU CHO HỌC SINH
        // ==========================================
        else {
            // Lấy các khóa học mà học sinh đang tham gia
            $courseIds = DB::table('class_user')->where('user_id', $user->id)->join('class_course', 'class_user.class_id', '=', 'class_course.class_id')->pluck('course_id');

            $data['total_courses'] = $courseIds->count();

            // Bài tập sắp đến hạn (Deadline) - Lấy thêm Tên khóa học
            $data['upcoming_deadlines'] = DB::table('assignments')->join('courses', 'assignments.course_id', '=', 'courses.id')->whereIn('assignments.course_id', $courseIds)->where('assignments.due_date', '>=', now())->select('assignments.*', 'courses.title as course_title', 'courses.id as course_id')->orderBy('assignments.due_date', 'asc')->take(5)->get();

            // BÀI KIỂM TRA CHƯA LÀM (Tính năng mới)
            $attemptedQuizIds = DB::table('quiz_attempts')->where('user_id', $user->id)->pluck('quiz_id');

            $data['pending_quizzes'] = DB::table('quizzes')
                ->join('courses', 'quizzes.course_id', '=', 'courses.id')
                ->whereIn('quizzes.course_id', $courseIds)
                ->whereNotIn('quizzes.id', $attemptedQuizIds) // Loại trừ những bài đã làm
                ->select('quizzes.*', 'courses.title as course_title', 'courses.id as course_id')
                ->orderBy('quizzes.created_at', 'desc')
                ->take(5)
                ->get();

            // Điểm trung bình Quiz và biểu đồ
            $avgQuizScore = DB::table('quiz_attempts')->where('user_id', $user->id)->avg('score');
            $data['average_score'] = $avgQuizScore ? round($avgQuizScore, 1) : 0;

            $recentQuizzes = DB::table('quiz_attempts')->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')->where('quiz_attempts.user_id', $user->id)->select('quizzes.title', 'quiz_attempts.score')->orderBy('quiz_attempts.completed_at', 'asc')->take(5)->get();

            $data['chart_quiz_labels'] = $recentQuizzes->pluck('title')->toArray();
            $data['chart_quiz_data'] = $recentQuizzes->pluck('score')->toArray();

            // Lịch học trong tuần
            $data['week_schedule'] = DB::table('schedules')
                ->join('courses', 'schedules.course_id', '=', 'courses.id')
                ->join('classes', 'schedules.class_id', '=', 'classes.id')
                ->join('class_user', 'classes.id', '=', 'class_user.class_id')
                ->where('class_user.user_id', $user->id)
                ->whereBetween('schedules.schedule_date', [$startOfWeek, $endOfWeek])
                ->select('schedules.*', 'courses.title as course_title', 'classes.name as class_name')
                ->orderBy('schedules.schedule_date', 'asc')
                ->orderBy('schedules.start_time', 'asc')
                ->get();
        }

        return view('dashboard', compact('data'));
    }
}
