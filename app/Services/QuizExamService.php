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
    public function __construct(private QuizQuestionSelectionService $questionSelector) {}

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

            $questions = $this->questionSelector->selectForQuiz($quiz);
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
                $type = $question->question_type ?: Question::TYPE_SINGLE_CHOICE;
                $options = in_array($type, [Question::TYPE_SINGLE_CHOICE, Question::TYPE_MULTIPLE_CHOICE], true)
                    ? $question->options->shuffle()->values()
                    : $question->options->values();
                [$answerKey, $responseSchema] = $this->snapshotDefinition($question, $options);
                $correctOptionId = (int) ($options->firstWhere('is_correct', true)?->id ?? 0);

                QuizAttemptQuestion::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'question_type' => $type,
                    'position' => $index + 1,
                    'question_text' => $question->question_text,
                    'passage_title' => $question->passage?->title,
                    'passage_content' => $question->passage?->content,
                    'passage_source_label' => $question->passage?->source_label,
                    'option_snapshot' => $options->map(fn ($option) => [
                        'id' => (int) $option->id,
                        'text' => $option->option_text,
                    ])->all(),
                    'answer_key_snapshot' => $answerKey,
                    'response_schema_snapshot' => $responseSchema,
                    'correct_option_id' => $correctOptionId,
                ]);
            }

            return $attempt->load(['attemptQuestions.answer', 'session']);
        }, 3);
    }

    public function saveAnswer(
        QuizAttempt $attempt,
        int $attemptQuestionId,
        mixed $answerPayload,
        bool $flagged,
        int $currentPosition
    ): QuizAttempt {
        return DB::transaction(function () use ($attempt, $attemptQuestionId, $answerPayload, $flagged, $currentPosition) {
            $lockedAttempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            $this->ensureAttemptIsWritable($lockedAttempt);

            $question = QuizAttemptQuestion::query()
                ->where('quiz_attempt_id', $lockedAttempt->id)
                ->findOrFail($attemptQuestionId);

            $normalizedPayload = $this->normalizeAnswerPayload($question, $answerPayload);

            if ($this->answerIsEmpty($question, $normalizedPayload)) {
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
                        'selected_option_id' => $question->question_type === Question::TYPE_SINGLE_CHOICE ? $normalizedPayload : null,
                        'answer_payload' => $normalizedPayload,
                        'is_correct' => null,
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
                $payload = $question->answer?->answer_payload ?? $question->answer?->selected_option_id;
                $isCorrect = $this->gradeAnswer($question, $payload);
                $studentAnswers[$question->question_id ?: 'snapshot_'.$question->id] = $payload;
                $question->answer?->update(['is_correct' => $isCorrect]);

                if ($isCorrect) {
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

    private function snapshotDefinition(Question $question, $options): array
    {
        return match ($question->question_type) {
            Question::TYPE_MULTIPLE_CHOICE => [
                ['option_ids' => $options->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all()],
                ['selection' => 'multiple'],
            ],
            Question::TYPE_TRUE_FALSE_GROUP => [
                ['statements' => $options->mapWithKeys(fn ($option) => [(string) $option->id => (bool) $option->is_correct])->all()],
                ['statement_ids' => $options->pluck('id')->map(fn ($id) => (int) $id)->all()],
            ],
            Question::TYPE_FILL_BLANK => [
                [
                    'blanks' => $question->answer_config['blanks'] ?? [],
                    'case_sensitive' => (bool) ($question->answer_config['case_sensitive'] ?? false),
                ],
                ['blank_count' => count($question->answer_config['blanks'] ?? [])],
            ],
            Question::TYPE_NUMERIC => [
                [
                    'target' => (float) ($question->answer_config['target'] ?? 0),
                    'tolerance' => (float) ($question->answer_config['tolerance'] ?? 0),
                ],
                ['unit' => (string) ($question->answer_config['unit'] ?? '')],
            ],
            default => [
                ['option_ids' => [(int) $options->firstWhere('is_correct', true)?->id]],
                ['selection' => 'single'],
            ],
        };
    }

    private function normalizeAnswerPayload(QuizAttemptQuestion $question, mixed $payload): mixed
    {
        $allowedOptionIds = collect($question->option_snapshot)->pluck('id')->map(fn ($id) => (int) $id);

        if ($question->question_type === Question::TYPE_SINGLE_CHOICE) {
            if ($payload === null || $payload === '') {
                return null;
            }
            $selected = filter_var($payload, FILTER_VALIDATE_INT);
            if ($selected === false || ! $allowedOptionIds->contains($selected)) {
                throw ValidationException::withMessages(['answer' => 'Đáp án không thuộc câu hỏi đã được phát.']);
            }

            return (int) $selected;
        }

        if ($question->question_type === Question::TYPE_MULTIPLE_CHOICE) {
            $selected = collect(is_array($payload) ? $payload : [])->map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT));
            if ($selected->contains(false) || $selected->unique()->count() !== $selected->count() || $selected->contains(fn ($id) => ! $allowedOptionIds->contains($id))) {
                throw ValidationException::withMessages(['answer' => 'Danh sách đáp án không hợp lệ.']);
            }

            return $selected->map(fn ($id) => (int) $id)->sort()->values()->all();
        }

        if ($question->question_type === Question::TYPE_TRUE_FALSE_GROUP) {
            $answers = collect(is_array($payload) ? $payload : [])->mapWithKeys(function ($value, $id) use ($allowedOptionIds) {
                $statementId = filter_var($id, FILTER_VALIDATE_INT);
                if ($statementId === false || ! $allowedOptionIds->contains($statementId) || ! in_array($value, [true, false, 1, 0, '1', '0'], true)) {
                    throw ValidationException::withMessages(['answer' => 'Câu trả lời Đúng/Sai không hợp lệ.']);
                }

                return [(string) $statementId => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false];
            });

            return $answers->all();
        }

        if ($question->question_type === Question::TYPE_FILL_BLANK) {
            $blankCount = (int) ($question->response_schema_snapshot['blank_count'] ?? 0);
            $answers = collect(is_array($payload) ? $payload : [])->take($blankCount)
                ->map(fn ($value) => mb_substr(trim((string) $value), 0, 1000));

            return $blankCount > 0
                ? collect(range(0, $blankCount - 1))->map(fn ($index) => $answers->get($index, ''))->all()
                : [];
        }

        if ($question->question_type === Question::TYPE_NUMERIC) {
            return mb_substr(trim((string) ($payload ?? '')), 0, 100);
        }

        throw ValidationException::withMessages(['answer' => 'Loại câu hỏi không được hỗ trợ.']);
    }

    private function answerIsEmpty(QuizAttemptQuestion $question, mixed $payload): bool
    {
        return match ($question->question_type) {
            Question::TYPE_SINGLE_CHOICE => $payload === null,
            Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_TRUE_FALSE_GROUP => empty($payload),
            Question::TYPE_FILL_BLANK => collect($payload)->every(fn ($value) => trim((string) $value) === ''),
            Question::TYPE_NUMERIC => trim((string) $payload) === '',
            default => true,
        };
    }

    private function gradeAnswer(QuizAttemptQuestion $question, mixed $payload): bool
    {
        if ($this->answerIsEmpty($question, $payload)) {
            return false;
        }

        $key = $question->answer_key_snapshot ?? ['option_ids' => [(int) $question->correct_option_id]];

        return match ($question->question_type) {
            Question::TYPE_SINGLE_CHOICE => (int) $payload === (int) ($key['option_ids'][0] ?? $question->correct_option_id),
            Question::TYPE_MULTIPLE_CHOICE => collect($payload)->map(fn ($id) => (int) $id)->sort()->values()->all()
                === collect($key['option_ids'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all(),
            Question::TYPE_TRUE_FALSE_GROUP => collect($key['statements'] ?? [])->every(
                fn ($expected, $id) => array_key_exists((string) $id, $payload) && (bool) $payload[(string) $id] === (bool) $expected
            ),
            Question::TYPE_FILL_BLANK => $this->gradeFillBlank($key, $payload),
            Question::TYPE_NUMERIC => $this->gradeNumeric($key, $payload),
            default => false,
        };
    }

    private function gradeFillBlank(array $key, array $payload): bool
    {
        $caseSensitive = (bool) ($key['case_sensitive'] ?? false);

        return collect($key['blanks'] ?? [])->every(function ($blank, $index) use ($payload, $caseSensitive) {
            $studentValue = $this->normalizeText($payload[$index] ?? '', $caseSensitive);

            return collect($blank['accepted'] ?? [])->contains(
                fn ($accepted) => $this->normalizeText($accepted, $caseSensitive) === $studentValue
            );
        });
    }

    private function gradeNumeric(array $key, mixed $payload): bool
    {
        $normalized = str_replace(',', '.', trim((string) $payload));
        if (! is_numeric($normalized)) {
            return false;
        }

        return abs((float) $normalized - (float) ($key['target'] ?? 0)) <= (float) ($key['tolerance'] ?? 0);
    }

    private function normalizeText(mixed $value, bool $caseSensitive): string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $caseSensitive ? $value : mb_strtolower($value);
    }
}
