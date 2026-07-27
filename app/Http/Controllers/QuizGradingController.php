<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Services\QuizExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuizGradingController extends Controller
{
    public function show(QuizAttempt $attempt)
    {
        Gate::authorize('view', $attempt);
        abort_if($attempt->isInProgress(), 422, 'Bài thi chưa được nộp.');

        $attempt->load([
            'quiz.course',
            'user',
            'session',
            'attemptQuestions.answer.grader',
            'attemptQuestions.attachments',
        ]);

        return view('quizzes.grade', compact('attempt'));
    }

    public function update(
        Request $request,
        QuizAttempt $attempt,
        QuizAttemptAnswer $answer,
        QuizExamService $examService
    ) {
        Gate::authorize('view', $attempt);
        $data = $request->validate([
            'rubric_scores' => ['required', 'array'],
            'rubric_scores.*' => ['required', 'numeric', 'min:0'],
            'teacher_feedback' => ['nullable', 'string', 'max:10000'],
        ]);

        $updated = $examService->gradeManualAnswer(
            $attempt,
            $answer,
            $data['rubric_scores'],
            $data['teacher_feedback'] ?? null,
            $request->user(),
        );

        $message = $updated->status === QuizAttempt::STATUS_PENDING_GRADING
            ? 'Đã lưu điểm và phản hồi. Bài vẫn còn câu chờ chấm.'
            : 'Đã hoàn tất chấm bài. Kết quả sẽ được công bố theo chính sách ca thi.';

        return back()->with('success', $message);
    }
}
