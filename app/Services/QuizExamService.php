<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizAttemptQuestion;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizExamService
{
    public function startOrResume(Quiz $quiz, User $student, ?QuizSession $session): QuizAttempt
    {
        return DB::transaction(function () use ($quiz, $student, $session) {
            $attempt = QuizAttempt::query()
                ->where('quiz_id', $quiz->id)
                ->where('user_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($attempt) {
                if (! $attempt->isInProgress()) {
                    throw ValidationException::withMessages([
                        'attempt' => 'Bạn đã nộp bài kiểm tra này.',
                    ]);
                }

                return $attempt->load(['attemptQuestions.answer', 'session']);
            }

            $questions = $this->generateExam($quiz);
            $expected = (int) $quiz->easy_count + (int) $quiz->medium_count + (int) $quiz->hard_count;

            if ($questions->isEmpty() || ($expected > 0 && $questions->count() < $expected)) {
                throw ValidationException::withMessages([
                    'attempt' => "Ngân hàng câu hỏi chưa đủ để phát đề ({$questions->count()}/{$expected} câu).",
                ]);
            }

            $extraMinutes = $session
                ? (int) DB::table('quiz_session_user')
                    ->where('quiz_session_id', $session->id)
                    ->where('user_id', $student->id)
                    ->value('extra_time_minutes')
                : 0;

            $startedAt = now();
            $expiresAt = $startedAt->copy()->addMinutes($quiz->time_limit + $extraMinutes);

            if ($session) {
                $sessionDeadline = $session->ends_at->copy()->addMinutes($extraMinutes);
                $expiresAt = $expiresAt->min($sessionDeadline);
            }

            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'quiz_session_id' => $session?->id,
                'user_id' => $student->id,
                'status' => 'in_progress',
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
                'last_seen_at' => $startedAt,
                'current_position' => 1,
                'flagged_question_ids' => [],
            ]);

            foreach ($questions->values() as $index => $question) {
                $options = $question->options->shuffle()->values();
                $correctOption = $options->firstWhere('is_correct', true);

                if (! $correctOption) {
                    throw ValidationException::withMessages([
                        'attempt' => "Câu hỏi #{$question->id} chưa có đáp án đúng.",
                    ]);
                }

                QuizAttemptQuestion::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'position' => $index + 1,
                    'question_text' => $question->question_text,
                    'passage_title' => $question->passage?->title,
                    'passage_content' => $question->passage?->content,
                    'passage_source_label' => $question->passage?->source_label,
                    'option_snapshot' => $options->map(fn ($option) => [
                        'id' => (int) $option->id,
                        'text' => $option->option_text,
                    ])->all(),
                    'correct_option_id' => $correctOption->id,
                ]);
            }

            return $attempt->load(['attemptQuestions.answer', 'session']);
        }, 3);
    }

    public function saveAnswer(
        QuizAttempt $attempt,
        int $attemptQuestionId,
        ?int $selectedOptionId,
        bool $flagged,
        int $currentPosition
    ): QuizAttempt {
        return DB::transaction(function () use ($attempt, $attemptQuestionId, $selectedOptionId, $flagged, $currentPosition) {
            $lockedAttempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            $this->ensureAttemptIsWritable($lockedAttempt);

            $question = QuizAttemptQuestion::query()
                ->where('quiz_attempt_id', $lockedAttempt->id)
                ->findOrFail($attemptQuestionId);

            $allowedOptionIds = collect($question->option_snapshot)->pluck('id')->map(fn ($id) => (int) $id);
            if ($selectedOptionId !== null && ! $allowedOptionIds->contains($selectedOptionId)) {
                throw ValidationException::withMessages(['answer' => 'Đáp án không thuộc câu hỏi đã được phát.']);
            }

            if ($selectedOptionId === null) {
                QuizAttemptAnswer::query()
                    ->where('quiz_attempt_id', $lockedAttempt->id)
                    ->where('quiz_attempt_question_id', $question->id)
                    ->delete();
            } else {
                QuizAttemptAnswer::updateOrCreate(
                    [
                        'quiz_attempt_id' => $lockedAttempt->id,
                        'quiz_attempt_question_id' => $question->id,
                    ],
                    [
                        'selected_option_id' => $selectedOptionId,
                        'answered_at' => now(),
                    ]
                );
            }

            $flags = collect($lockedAttempt->flagged_question_ids ?? [])->map(fn ($id) => (int) $id);
            $flags = $flagged ? $flags->push($question->id)->unique() : $flags->reject(fn ($id) => $id === $question->id);

            $lockedAttempt->update([
                'flagged_question_ids' => $flags->values()->all(),
                'current_position' => max(1, $currentPosition),
                'last_seen_at' => now(),
            ]);

            return $lockedAttempt->fresh(['answers']);
        }, 3);
    }

    public function heartbeat(QuizAttempt $attempt, int $currentPosition): QuizAttempt
    {
        $this->ensureAttemptIsWritable($attempt);
        $attempt->update([
            'current_position' => max(1, $currentPosition),
            'last_seen_at' => now(),
        ]);

        return $attempt->fresh();
    }

    public function submit(QuizAttempt $attempt): QuizAttempt
    {
        return DB::transaction(function () use ($attempt) {
            $lockedAttempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if (! $lockedAttempt->isInProgress()) {
                return $lockedAttempt;
            }

            $questions = $lockedAttempt->attemptQuestions()->with('answer')->get();
            $correct = 0;
            $studentAnswers = [];

            foreach ($questions as $question) {
                $selectedOptionId = $question->answer?->selected_option_id;
                $studentAnswers[$question->question_id ?: 'snapshot_'.$question->id] = $selectedOptionId;

                if ($selectedOptionId !== null && (int) $selectedOptionId === (int) $question->correct_option_id) {
                    $correct++;
                }
            }

            $score = $questions->isNotEmpty() ? round(($correct / $questions->count()) * 10, 1) : 0;
            $releaseNow = ! $lockedAttempt->quiz_session_id
                || $lockedAttempt->session?->result_release_policy === QuizSession::RELEASE_IMMEDIATE;
            $completedAt = $lockedAttempt->expires_at?->isPast() ? $lockedAttempt->expires_at : now();

            $lockedAttempt->update([
                'status' => 'submitted',
                'score' => $score,
                'student_answers' => $studentAnswers,
                'completed_at' => $completedAt,
                'last_seen_at' => now(),
                'result_released_at' => $releaseNow ? now() : null,
            ]);

            return $lockedAttempt->fresh(['quiz', 'session']);
        }, 3);
    }

    public function submitExpiredForSession(QuizSession $session): int
    {
        $count = 0;
        QuizAttempt::query()
            ->where('quiz_session_id', $session->id)
            ->where('status', 'in_progress')
            ->where('expires_at', '<=', now())
            ->each(function (QuizAttempt $attempt) use (&$count) {
                $this->submit($attempt);
                $count++;
            });

        return $count;
    }

    public function remainingSeconds(QuizAttempt $attempt): int
    {
        return max(0, now()->diffInSeconds(Carbon::parse($attempt->expires_at), false));
    }

    private function ensureAttemptIsWritable(QuizAttempt $attempt): void
    {
        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages(['attempt' => 'Bài thi đã được nộp.']);
        }

        if (! $attempt->expires_at || $attempt->expires_at->lte(now())) {
            throw ValidationException::withMessages(['attempt' => 'Bài thi đã hết thời gian và được tự động nộp.']);
        }
    }

    private function generateExam(Quiz $quiz)
    {
        $bankIds = $quiz->course->questionBanks()->pluck('question_banks.id');
        $pick = fn (string $difficulty, int $limit) => Question::with(['options', 'passage'])
            ->notArchived()
            ->where(function ($query) use ($quiz, $bankIds) {
                if ($bankIds->isNotEmpty()) {
                    $query->whereIn('question_bank_id', $bankIds);
                }

                $query->orWhere('course_id', $quiz->course_id);
            })
            ->where('difficulty', $difficulty)
            ->whereHas('options', fn ($query) => $query->where('is_correct', true))
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        return $pick('easy', (int) $quiz->easy_count)
            ->merge($pick('medium', (int) $quiz->medium_count))
            ->merge($pick('hard', (int) $quiz->hard_count))
            ->shuffle()
            ->values();
    }
}
