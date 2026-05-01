<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\Question;

class QuizController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'time_limit' => 'required|integer|min:1',
            'easy_count' => 'required|integer|min:0',
            'medium_count' => 'required|integer|min:0',
            'hard_count' => 'required|integer|min:0',
        ]);

        Quiz::create([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'time_limit' => $request->time_limit,
            'is_random' => true,
            'easy_count' => $request->easy_count,
            'medium_count' => $request->medium_count,
            'hard_count' => $request->hard_count,
        ]);

        return back()->with('success', 'Đã tạo cấu hình bài kiểm tra ngẫu nhiên thành công!');
    }

    public function show($id)
    {
        $quiz = Quiz::findOrFail($id);

        // Kiểm tra quyền (chỉ giáo viên của khóa học, học sinh của khóa, hoặc admin mới được xem)
        // ... (Giữ nguyên logic phân quyền của bạn nếu có)

        // ==========================================
        // THUẬT TOÁN LẤY ĐỀ NGẪU NHIÊN & XÁO TRỘN
        // ==========================================
        if ($quiz->is_random) {
            // 1. Bốc ngẫu nhiên câu hỏi từ Ngân hàng theo độ khó
            $easyQuestions = Question::with('options')->where('course_id', $quiz->course_id)->where('difficulty', 'easy')->inRandomOrder()->limit($quiz->easy_count)->get();

            $mediumQuestions = Question::with('options')->where('course_id', $quiz->course_id)->where('difficulty', 'medium')->inRandomOrder()->limit($quiz->medium_count)->get();

            $hardQuestions = Question::with('options')->where('course_id', $quiz->course_id)->where('difficulty', 'hard')->inRandomOrder()->limit($quiz->hard_count)->get();

            // 2. Gộp tất cả lại thành 1 đề thi duy nhất
            $examQuestions = $easyQuestions->merge($mediumQuestions)->merge($hardQuestions);

            // 3. Xáo trộn thứ tự các CÂU HỎI
            $examQuestions = $examQuestions->shuffle();

            // 4. Xáo trộn thứ tự các ĐÁP ÁN bên trong mỗi câu hỏi
            foreach ($examQuestions as $question) {
                // Biến Collection options thành một Collection mới đã xáo trộn
                $question->setRelation('options', $question->options->shuffle());
            }
        }

        // Truyền $examQuestions ra View (thay vì $quiz->questions như trước kia)
        return view('quizzes.show', compact('quiz', 'examQuestions'));
    }

    public function destroy($id)
    {
        Quiz::findOrFail($id)->delete();
        return back()->with('success', 'Đã xóa bài kiểm tra thành công!');
    }

    public function submissions($id)
    {
        $quiz = Quiz::with(['course.teacher', 'attempts.user'])->findOrFail($id);

        if (auth()->id() !== $quiz->course->teacher_id && auth()->user()->role !== 'admin') {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        $attempts = $quiz->attempts()->orderBy('completed_at', 'desc')->get();
        return view('quizzes.submissions', compact('quiz', 'attempts'));
    }
}
