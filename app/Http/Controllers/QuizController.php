<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;

class QuizController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'time_limit' => 'required|integer|min:1',
        ]);

        Quiz::create([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'time_limit' => $request->time_limit,
        ]);

        return back()->with('success', 'Đã thêm bài kiểm tra trắc nghiệm thành công!');
    }
    public function show($id)
    {
        // Lấy đề thi kèm theo câu hỏi và các lựa chọn đáp án
        $quiz = Quiz::with('questions.options')->findOrFail($id);

        // Kiểm tra quyền (chỉ giáo viên của khóa học hoặc admin mới được xem)
        if (auth()->user()->role !== 'admin' && auth()->id() !== $quiz->course->teacher_id) {
            return redirect()->route('dashboard')->with('error', 'Bạn không có quyền truy cập!');
        }

        return view('quizzes.show', compact('quiz'));
    }
    public function destroy($id)
    {
        $quiz = Quiz::findOrFail($id);

        $quiz->delete();

        return back()->with('success', 'Đã xóa bài kiểm tra thành công!');
    }
    public function submissions($id)
    {
        // Lấy thông tin bài kiểm tra kèm danh sách người đã nộp bài (sắp xếp mới nhất lên đầu)
        $quiz = \App\Models\Quiz::with(['course.teacher', 'attempts.user'])->findOrFail($id);

        // Bảo mật: Chỉ giáo viên dạy khóa này hoặc admin mới được xem
        if (auth()->id() !== $quiz->course->teacher_id && auth()->user()->role !== 'admin') {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        // Lấy danh sách điểm
        $attempts = $quiz->attempts()->orderBy('completed_at', 'desc')->get();

        return view('quizzes.submissions', compact('quiz', 'attempts'));
    }
}
