<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Option;
use App\Models\Question; // Bổ sung Model Question

class QuizAttemptController extends Controller
{
    // ==========================================
    // 1. MỞ GIAO DIỆN LÀM BÀI VÀ SINH ĐỀ NGẪU NHIÊN
    // ==========================================
    public function create($quiz_id)
    {
        $quiz = Quiz::findOrFail($quiz_id);

        // Kiểm tra xem học sinh đã làm bài này chưa (tránh làm lại nhiều lần)
        $existingAttempt = QuizAttempt::where('quiz_id', $quiz_id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingAttempt) {
            return redirect()->route('courses.show', $quiz->course_id)->with('error', 'Bạn đã hoàn thành bài kiểm tra này. Không thể làm lại!');
        }

        // --- THUẬT TOÁN BỐC ĐỀ NGẪU NHIÊN ---
        $easyQuestions = Question::with('options')->where('course_id', $quiz->course_id)->where('difficulty', 'easy')->inRandomOrder()->limit($quiz->easy_count)->get();

        $mediumQuestions = Question::with('options')->where('course_id', $quiz->course_id)->where('difficulty', 'medium')->inRandomOrder()->limit($quiz->medium_count)->get();

        $hardQuestions = Question::with('options')->where('course_id', $quiz->course_id)->where('difficulty', 'hard')->inRandomOrder()->limit($quiz->hard_count)->get();

        // Trộn tất cả câu hỏi lại
        $examQuestions = $easyQuestions->merge($mediumQuestions)->merge($hardQuestions)->shuffle();

        // Trộn luôn thứ tự các đáp án (A,B,C,D) trong mỗi câu
        foreach ($examQuestions as $question) {
            $question->setRelation('options', $question->options->shuffle());
        }

        return view('quizzes.attempt', compact('quiz', 'examQuestions'));
    }
    // ==========================================
    // 2. NỘP BÀI VÀ CHẤM ĐIỂM
    // ==========================================
    public function store(Request $request, $quiz_id)
    {
        $quiz = Quiz::findOrFail($quiz_id);

        // 1. Lấy danh sách toàn bộ ID câu hỏi đã phát cho học sinh (từ thẻ hidden)
        $presentedQuestionIds = $request->input('question_ids', []);
        $answers = $request->input('answers', []); // Câu nào có đánh dấu mới xuất hiện ở đây

        $correctAnswersCount = 0;
        $totalQuestions = count($presentedQuestionIds); // Lấy tổng số câu dựa trên đề thực tế đã bốc

        $fullStudentAnswers = [];

        // 2. Chấm điểm và tạo mảng lưu trữ đầy đủ (kể cả câu không trả lời)
        foreach ($presentedQuestionIds as $questionId) {
            $selectedOptionId = $answers[$questionId] ?? null;

            // Lưu lại: có chọn thì lưu ID, bỏ trống thì lưu null
            $fullStudentAnswers[$questionId] = $selectedOptionId;

            if ($selectedOptionId) {
                $isCorrect = Option::where('id', $selectedOptionId)->where('question_id', $questionId)->value('is_correct');
                if ($isCorrect) {
                    $correctAnswersCount++;
                }
            }
        }

        // Tính điểm thang 10
        $score = $totalQuestions > 0 ? round(($correctAnswersCount / $totalQuestions) * 10, 1) : 0;

        // 3. Lưu kết quả
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => auth()->id(),
            'score' => $score,
            'student_answers' => $fullStudentAnswers, // Bây giờ nó sẽ lưu đầy đủ cả câu bỏ trống
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('courses.show', $quiz->course_id)
            ->with('success', "Bạn đã nộp bài thành công! Số điểm của bạn là: {$score}/10");
    }

    // ==========================================
    // 3. XEM LẠI BÀI ĐÃ LÀM
    // ==========================================
    public function review($attempt_id)
    {
        $attempt = QuizAttempt::with('quiz.course')->findOrFail($attempt_id);

        if (auth()->id() !== $attempt->user_id && auth()->id() !== $attempt->quiz->course->teacher_id && auth()->user()->role !== 'admin') {
            abort(403, 'Bạn không có quyền xem bài làm này.');
        }

        $studentAnswers = is_string($attempt->student_answers) ? json_decode($attempt->student_answers, true) : $attempt->student_answers ?? [];
        $questionIds = is_array($studentAnswers) ? array_keys($studentAnswers) : [];

        // Lấy câu hỏi từ Database và sắp xếp lại đúng theo thứ tự đã phát lúc làm bài
        if (!empty($questionIds)) {
            $questions = \App\Models\Question::with('options')
                ->whereIn('id', $questionIds)
                ->get()
                ->sortBy(function ($model) use ($questionIds) {
                    return array_search($model->id, $questionIds);
                });
        } else {
            $questions = collect([]);
        }

        return view('quizzes.review', compact('attempt', 'studentAnswers', 'questions'));
    }
}
