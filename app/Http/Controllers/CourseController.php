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
        $course = Course::with(['teacher', 'modules.lessons'])->findOrFail($id);

        $completedLessonIds = [];
        $progress = 0;
        $totalLessons = 0;
        $completedCount = 0;

        // Nếu là học sinh, tính toán dữ liệu tiến độ để hiển thị ra View
        if (auth()->check() && auth()->user()->role === 'student') {
            $user = auth()->user();
            // Lấy ID của tất cả bài học trong khóa này
            $courseLessonIds = $course->modules->flatMap->lessons->pluck('id')->toArray();
            $totalLessons = count($courseLessonIds);

            // Lấy ra danh sách ID các bài học mà user này đã hoàn thành
            $completedLessonIds = $user->lessons()->whereIn('lesson_id', $courseLessonIds)->whereNotNull('lesson_user.completed_at')->pluck('lessons.id')->toArray();

            $completedCount = count($completedLessonIds);
            $progress = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;
        }

        return view('courses.show', compact('course', 'completedLessonIds', 'progress', 'totalLessons', 'completedCount'));
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
