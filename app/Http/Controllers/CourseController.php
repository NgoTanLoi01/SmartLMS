<?php

namespace App\Http\Controllers;

use App\Models\Course;

use Illuminate\Http\Request;

class CourseController extends Controller
{

    public function index()
    {
        $user = auth()->user();

        // Khởi tạo query cơ bản kèm đếm số lượng bài học (lessons)
        $query = Course::with(['teacher', 'classes'])
            ->withCount('modules') // Đếm số module
            // Đếm tổng bài học của tất cả các module trong khóa học
            ->withCount([
                'modules as lessons_count' => function ($query) {
                    $query->leftJoin('lessons', 'modules.id', '=', 'lessons.module_id')->select(\DB::raw('count(lessons.id)'));
                },
            ]);

        if ($user->role === 'admin') {
            $courses = $query->latest()->get();
        } elseif ($user->role === 'teacher') {
            $courses = $query->where('teacher_id', $user->id)->latest()->get();
        } else {
            // Học sinh
            $classIds = $user->classes()->pluck('classes.id');
            $courses = $query
                ->whereHas('classes', function ($q) use ($classIds) {
                    $q->whereIn('classes.id', $classIds);
                })
                ->with(['modules.lessons'])
                ->latest()
                ->get();

            // Giữ nguyên logic tính progress của thầy
            foreach ($courses as $course) {
                $totalLessons = $course->modules->flatMap->lessons->count();
                $courseLessonIds = $course->modules->flatMap->lessons->pluck('id')->toArray();
                $completedLessons = $user->lessons()->whereIn('lesson_id', $courseLessonIds)->whereNotNull('lesson_user.completed_at')->count();
                $course->progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
            }
        }

        // Logic đếm Học sinh (Students) dựa trên quan hệ classes của thầy
        foreach ($courses as $course) {
            // Đếm số lượng user duy nhất tham gia các lớp của khóa học này
            $course->students_count = \DB::table('class_user')
                ->whereIn('class_id', $course->classes->pluck('id'))
                ->distinct('user_id')
                ->count();
        }

        return view('courses.index', compact('courses'));
    }

    public function show($id)
    {
        // Load khóa học cùng giáo viên, bài học và bài tập của từng bài học
        $course = Course::with(['teacher', 'modules.lessons.assignments', 'quizzes'])->findOrFail($id);

        $completedLessonIds = [];
        $progress = 0;
        $totalLessons = 0;
        $completedCount = 0;
        $userSubmissions = collect();

        if (auth()->check() && auth()->user()->role === 'student') {
            $user = auth()->user();

            // 1. Tính toán tiến độ học tập (Lessons)
            $courseLessonIds = $course->modules->flatMap->lessons->pluck('id')->toArray();
            $totalLessons = count($courseLessonIds);

            $completedLessonIds = $user->lessons()->whereIn('lesson_id', $courseLessonIds)->whereNotNull('lesson_user.completed_at')->pluck('lessons.id')->toArray();

            $completedCount = count($completedLessonIds);
            $progress = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;

            // 2. Lấy dữ liệu bài nộp (Assignments)
            $assignmentIds = \App\Models\Assignments::where('course_id', $id)->pluck('id')->toArray();
            $userSubmissions = \App\Models\AssignmentSubmission::where('user_id', $user->id)->whereIn('assignment_id', $assignmentIds)->get()->keyBy('assignment_id'); // Key hóa theo ID bài tập để View check cực nhanh
        }
        $userQuizAttempts = [];
        if (auth()->check() && auth()->user()->role === 'student') {
            $userQuizAttempts = \App\Models\QuizAttempt::where('user_id', auth()->id())
                ->whereIn('quiz_id', $course->quizzes->pluck('id'))
                ->get()
                ->keyBy('quiz_id'); // Gom nhóm theo ID bài kiểm tra để blade dễ kiểm tra
        }

        return view('courses.show', compact('course', 'completedLessonIds', 'progress', 'totalLessons', 'completedCount', 'userSubmissions', 'userQuizAttempts'));
    }

    public function create()
    {
        return view('courses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
        ]);

        Course::create([
            'title' => $request->title,
            'description' => $request->description,
            'teacher_id' => auth()->id(),
        ]);

        return redirect()->route('courses.index')->with('success', 'Tạo khóa học thành công!');
    }

    public function edit($id)
    {
        $course = Course::findOrFail($id);

        // Chỉ cho phép giáo viên của khóa học hoặc admin sửa
        if (auth()->id() !== $course->teacher_id && auth()->user()->role !== 'admin') {
            return redirect()->route('courses.index')->with('error', 'Bạn không có quyền sửa khóa học này.');
        }

        return view('courses.edit', compact('course'));
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
        ]);

        $course->update($request->only(['title', 'description']));

        return redirect()->route('courses.index')->with('success', 'Cập nhật thành công!');
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        // Cần kiểm tra quyền trước khi xóa (giống hàm edit) để bảo mật
        if (auth()->id() !== $course->teacher_id && auth()->user()->role !== 'admin') {
            return redirect()->route('courses.index')->with('error', 'Bạn không có quyền xóa khóa học này.');
        }

        $course->delete();
        return redirect()->route('courses.index')->with('success', 'Đã xóa khóa học.');
    }
}
