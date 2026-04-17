<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('teacher')->latest()->get();
        return view('courses.index', compact('courses'));
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

    public function show($id)
    {
        $course = Course::with(['teacher', 'modules.lessons'])->findOrFail($id);
        return view('courses.show', compact('course'));
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
        $course->delete();
        return redirect()->route('courses.index')->with('success', 'Đã xóa khóa học.');
    }
}
