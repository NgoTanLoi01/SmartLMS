<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Option;

class QuizAttemptController extends Controller
{
    // Mở giao diện làm bài thi
    public function create($quiz_id)
    {
        $quiz = Quiz::with('questions.options')->findOrFail($quiz_id);

        // Kiểm tra xem học sinh đã làm bài này chưa (tránh làm lại nhiều lần)
        $existingAttempt = QuizAttempt::where('quiz_id', $quiz_id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingAttempt) {
            return redirect()->route('courses.show', $quiz->course_id)->with('error', 'Bạn đã hoàn thành bài kiểm tra này. Không thể làm lại!');
        }

        return view('quizzes.attempt', compact('quiz'));
    }

    // Xử lý nộp bài và chấm điểm
    public function store(Request $request, $quiz_id)
    {
        $quiz = Quiz::with('questions')->findOrFail($quiz_id);

        // 1. Lấy dữ liệu học sinh chọn (mảng chứa id câu hỏi => id đáp án)
        $answers = $request->input('answers', []);

        $correctAnswersCount = 0;
        $totalQuestions = $quiz->questions->count();

        // 2. Chấm điểm
        foreach ($quiz->questions as $question) {
            $selectedOptionId = $answers[$question->id] ?? null;

            if ($selectedOptionId) {
                $isCorrect = Option::where('id', $selectedOptionId)->where('question_id', $question->id)->value('is_correct');

                if ($isCorrect) {
                    $correctAnswersCount++;
                }
            }
        }

        // Tính điểm thang 10 (vd: 8/10 đúng => 8 điểm)
        $score = $totalQuestions > 0 ? round(($correctAnswersCount / $totalQuestions) * 10, 1) : 0;

        // 3. Lưu kết quả vào DB
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => auth()->id(),
            'score' => $score,
            'started_at' => now(), // Tạm thời dùng now(), nếu muốn chặt chẽ thì lưu started_at lúc vừa mở form
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('courses.show', $quiz->course_id)
            ->with('success', "Bạn đã nộp bài thành công! Số điểm của bạn là: {$score}/10");
    }
}
