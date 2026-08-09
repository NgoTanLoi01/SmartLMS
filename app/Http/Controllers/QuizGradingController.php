<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Services\QuizExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

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

        $manualQuestions = $attempt->attemptQuestions->where('grading_mode', 'manual');
        $gradedManualCount = $manualQuestions->filter(
            fn ($question) => $question->answer?->grading_status === 'graded'
        )->count();
        $gradingProgress = [
            'graded' => $gradedManualCount,
            'total' => $manualQuestions->count(),
            'percent' => $manualQuestions->isEmpty()
                ? 100
                : (int) round(($gradedManualCount / $manualQuestions->count()) * 100),
        ];
        $nextPendingAttempt = QuizAttempt::query()
            ->where('quiz_id', $attempt->quiz_id)
            ->where('status', QuizAttempt::STATUS_PENDING_GRADING)
            ->whereKeyNot($attempt->id)
            ->orderBy('completed_at')
            ->first();

        return view('quizzes.grade', compact('attempt', 'gradingProgress', 'nextPendingAttempt'));
    }

    public function update(
        Request $request,
        QuizAttempt $attempt,
        QuizAttemptAnswer $answer,
        QuizExamService $examService
    ) {
        Gate::authorize('view', $attempt);
        $data = $request->validate([
            'intent' => ['required', Rule::in(['draft', 'complete', 'complete_next'])],
            'rubric_scores' => ['nullable', 'array'],
            'rubric_scores.*' => ['nullable', 'numeric', 'min:0'],
            'teacher_feedback' => ['nullable', 'string', 'max:10000'],
        ]);

        $updated = $data['intent'] === 'draft'
            ? $examService->saveManualAnswerDraft(
                $attempt,
                $answer,
                $data['rubric_scores'] ?? [],
                $data['teacher_feedback'] ?? null,
                $request->user(),
            )
            : $examService->gradeManualAnswer(
                $attempt,
                $answer,
                $data['rubric_scores'] ?? [],
                $data['teacher_feedback'] ?? null,
                $request->user(),
            );

        if ($data['intent'] === 'draft') {
            return back()->with('success', 'Đã lưu nháp điểm và phản hồi. Bài vẫn ở hàng đợi chấm.');
        }

        $message = $updated->status === QuizAttempt::STATUS_PENDING_GRADING
            ? 'Đã lưu điểm và phản hồi. Bài vẫn còn câu chờ chấm.'
            : 'Đã hoàn tất chấm bài. Kết quả sẽ được công bố theo chính sách ca thi.';

        if ($data['intent'] === 'complete_next' && $updated->status !== QuizAttempt::STATUS_PENDING_GRADING) {
            $nextAttempt = QuizAttempt::query()
                ->where('quiz_id', $updated->quiz_id)
                ->where('status', QuizAttempt::STATUS_PENDING_GRADING)
                ->whereKeyNot($updated->id)
                ->orderBy('completed_at')
                ->first();

            if ($nextAttempt) {
                return redirect()->route('quiz-attempts.grade', $nextAttempt)->with('success', $message);
            }
        }

        return back()->with('success', $message);
    }

    public function release(QuizAttempt $attempt, QuizExamService $examService)
    {
        Gate::authorize('view', $attempt);
        $examService->releaseResult($attempt, request()->user());

        return back()->with('success', 'Đã công bố điểm và phản hồi cho học viên.');
    }
}
