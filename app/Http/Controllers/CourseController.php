<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $courses = Course::with('teacher')->latest()->get();
        } elseif ($user->role === 'teacher') {
            $courses = Course::with('teacher')->where('teacher_id', $user->id)->latest()->get();
        } else {
            // STUDENT: Lấy khóa học kèm theo modules và lessons để tính tiến độ
            $classIds = $user->classes()->pluck('classes.id');

            $courses = Course::with(['teacher', 'modules.lessons'])
                ->whereHas('classes', function ($query) use ($classIds) {
                    $query->whereIn('classes.id', $classIds);
                })
                ->latest()
                ->get();

            // Tính toán tiến độ cho từng khóa học
            foreach ($courses as $course) {
                $totalLessons = $course->modules->flatMap->lessons->count();
                $courseLessonIds = $course->modules->flatMap->lessons->pluck('id')->toArray();

                $completedLessons = $user->lessons()->whereIn('lesson_id', $courseLessonIds)->whereNotNull('lesson_user.completed_at')->count();

                $course->progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
            }
        }

        return view('courses.index', compact('courses'));
    }

    public function show($id)
    {
        // Load khóa học cùng giáo viên, bài học và bài tập của từng bài học
        $course = Course::with(['teacher', 'modules.lessons.assignments'])->findOrFail($id);

        $completedLessonIds = [];
        $progress = 0;
        $totalLessons = 0;
        $completedCount = 0;
        $userSubmissions = collect(); // Khởi tạo collection trống để tránh lỗi Undefined

        if (auth()->check() && auth()->user()->role === 'student') {
            $user = auth()->user();

            // 1. Tính toán tiến độ học tập (Lessons)
            $courseLessonIds = $course->modules->flatMap->lessons->pluck('id')->toArray();
            $totalLessons = count($courseLessonIds);

            $completedLessonIds = $user->lessons()->whereIn('lesson_id', $courseLessonIds)->whereNotNull('lesson_user.completed_at')->pluck('lessons.id')->toArray();

            $completedCount = count($completedLessonIds);
            $progress = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;

            // 2. Lấy dữ liệu bài nộp (Assignments) - PHẦN QUAN TRỌNG BỊ THIẾU
            // Lấy tất cả ID bài tập thuộc khóa học này
            $assignmentIds = \App\Models\Assignments::where('course_id', $id)->pluck('id')->toArray();

            // Lấy danh sách bài nộp của chính học sinh này cho các bài tập đó
            $userSubmissions = \App\Models\AssignmentSubmission::where('user_id', $user->id)->whereIn('assignment_id', $assignmentIds)->get()->keyBy('assignment_id'); // Key hóa theo ID bài tập để View check cực nhanh
        }

        return view(
            'courses.show',
            compact(
                'course',
                'completedLessonIds',
                'progress',
                'totalLessons',
                'completedCount',
                'userSubmissions', // Đưa biến này ra View
            ),
        );
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
