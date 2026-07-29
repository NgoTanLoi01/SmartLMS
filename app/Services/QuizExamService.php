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
    public function __construct(
        private QuizQuestionSelectionService $questionSelector,
        private QuestionDifficultyAnalyticsService $difficultyAnalytics,
        private HtmlCssCodeFormatter $codeFormatter,
    ) {}

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
                    'grading_mode' => $question->isManuallyGraded() ? 'manual' : 'auto',
                    'max_score' => (float) ($question->answer_config['max_score'] ?? 1),
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
                        'grading_status' => 'ungraded',
                        'score' => null,
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
        $submitted = DB::transaction(function () use ($attempt) {
            $lockedAttempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if (! $lockedAttempt->isInProgress()) {
                return $lockedAttempt;
            }

            $questions = $lockedAttempt->attemptQuestions()->with('answer')->get();
            $autoEarned = 0.0;
            $studentAnswers = [];
            $manualQuestions = $questions->where('grading_mode', 'manual');
            $totalMaxScore = max((float) $questions->sum('max_score'), 1.0);

            foreach ($questions as $question) {
                $payload = $question->answer?->answer_payload ?? $question->answer?->selected_option_id;
                $studentAnswers[$question->question_id ?: 'snapshot_'.$question->id] = $payload;

                if ($question->requiresManualGrading()) {
                    QuizAttemptAnswer::updateOrCreate(
                        [
                            'quiz_attempt_id' => $lockedAttempt->id,
                            'quiz_attempt_question_id' => $question->id,
                        ],
                        [
                            'answer_payload' => $payload,
                            'is_correct' => null,
                            'grading_status' => 'pending',
                            'score' => null,
                            'answered_at' => $question->answer?->answered_at,
                        ]
                    );

                    continue;
                }

                $isCorrect = $this->gradeAnswer($question, $payload);
                $earned = $isCorrect ? (float) $question->max_score : 0.0;
                $autoEarned += $earned;
                QuizAttemptAnswer::updateOrCreate(
                    [
                        'quiz_attempt_id' => $lockedAttempt->id,
                        'quiz_attempt_question_id' => $question->id,
                    ],
                    [
                        'answer_payload' => $payload,
                        'selected_option_id' => $question->question_type === Question::TYPE_SINGLE_CHOICE ? $payload : null,
                        'is_correct' => $isCorrect,
                        'grading_status' => 'auto_graded',
                        'score' => $earned,
                        'graded_at' => now(),
                        'answered_at' => $question->answer?->answered_at,
                    ]
                );
            }

            $autoScore = round(($autoEarned / $totalMaxScore) * 10, 2);
            $needsManualGrading = $manualQuestions->isNotEmpty();
            $releaseNow = ! $lockedAttempt->quiz_session_id
                || $lockedAttempt->session?->result_release_policy === QuizSession::RELEASE_IMMEDIATE;
            $completedAt = $lockedAttempt->expires_at?->isPast() ? $lockedAttempt->expires_at : now();
            $status = $needsManualGrading
                ? QuizAttempt::STATUS_PENDING_GRADING
                : ($releaseNow ? QuizAttempt::STATUS_RELEASED : QuizAttempt::STATUS_GRADED);

            $lockedAttempt->update([
                'status' => $status,
                'score' => $needsManualGrading ? null : $autoScore,
                'auto_score' => $autoScore,
                'manual_score' => $needsManualGrading ? null : 0,
                'student_answers' => $studentAnswers,
                'completed_at' => $completedAt,
                'graded_at' => $needsManualGrading ? null : now(),
                'last_seen_at' => now(),
                'result_released_at' => ! $needsManualGrading && $releaseNow ? now() : null,
            ]);

            return $lockedAttempt->fresh(['quiz', 'session']);
        }, 3);

        $this->difficultyAnalytics->refreshForQuestionIds(
            $submitted->attemptQuestions()->pluck('question_id')
        );

        return $submitted;
    }

    public function gradeManualAnswer(
        QuizAttempt $attempt,
        QuizAttemptAnswer $answer,
        array $rubricScores,
        ?string $feedback,
        User $grader
    ): QuizAttempt {
        return $this->persistManualGrade($attempt, $answer, $rubricScores, $feedback, $grader, true);
    }

    public function saveManualAnswerDraft(
        QuizAttempt $attempt,
        QuizAttemptAnswer $answer,
        array $rubricScores,
        ?string $feedback,
        User $grader
    ): QuizAttempt {
        return $this->persistManualGrade($attempt, $answer, $rubricScores, $feedback, $grader, false);
    }

    public function releaseResult(QuizAttempt $attempt): QuizAttempt
    {
        return DB::transaction(function () use ($attempt) {
            $lockedAttempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($lockedAttempt->status === QuizAttempt::STATUS_RELEASED) {
                return $lockedAttempt;
            }

            if ($lockedAttempt->status !== QuizAttempt::STATUS_GRADED || $lockedAttempt->score === null) {
                throw ValidationException::withMessages([
                    'attempt' => 'Chỉ có thể công bố bài đã chấm hoàn tất.',
                ]);
            }

            $lockedAttempt->update([
                'status' => QuizAttempt::STATUS_RELEASED,
                'result_released_at' => now(),
            ]);

            return $lockedAttempt->fresh(['session']);
        }, 3);
    }

    private function persistManualGrade(
        QuizAttempt $attempt,
        QuizAttemptAnswer $answer,
        array $rubricScores,
        ?string $feedback,
        User $grader,
        bool $complete
    ): QuizAttempt {
        return DB::transaction(function () use ($attempt, $answer, $rubricScores, $feedback, $grader, $complete) {
            $lockedAttempt = QuizAttempt::query()->lockForUpdate()->with('session')->findOrFail($attempt->id);
            if ($lockedAttempt->status === QuizAttempt::STATUS_RELEASED) {
                throw ValidationException::withMessages([
                    'attempt' => 'Kết quả đã được công bố nên không thể thay đổi điểm.',
                ]);
            }

            $question = QuizAttemptQuestion::query()
                ->where('quiz_attempt_id', $lockedAttempt->id)
                ->where('grading_mode', 'manual')
                ->findOrFail($answer->quiz_attempt_question_id);

            if ((int) $answer->quiz_attempt_id !== (int) $lockedAttempt->id) {
                throw ValidationException::withMessages(['answer' => 'Câu trả lời không thuộc bài làm này.']);
            }

            $rubric = collect($question->gradingRubric());
            $normalizedScores = $rubric->map(function ($criterion, $index) use ($rubricScores, $complete) {
                $rawValue = $rubricScores[$index] ?? null;
                if ($rawValue === null || $rawValue === '') {
                    if ($complete) {
                        throw ValidationException::withMessages([
                            "rubric_scores.{$index}" => 'Vui lòng chấm đủ tất cả tiêu chí trước khi hoàn tất.',
                        ]);
                    }

                    return null;
                }

                $value = (float) $rawValue;
                $maximum = (float) ($criterion['max_score'] ?? 0);
                if ($value < 0 || $value > $maximum) {
                    throw ValidationException::withMessages([
                        "rubric_scores.{$index}" => 'Điểm tiêu chí phải nằm trong thang điểm đã cấu hình.',
                    ]);
                }

                return $value;
            })->values()->all();
            $score = $complete ? round(array_sum($normalizedScores), 2) : null;

            $answer->update([
                'grading_status' => $complete ? 'graded' : 'pending',
                'score' => $score,
                'rubric_scores' => $normalizedScores,
                'teacher_feedback' => trim((string) $feedback) ?: null,
                'graded_by' => $grader->id,
                'graded_at' => $complete ? now() : null,
            ]);

            if (! $complete) {
                $lockedAttempt->update([
                    'status' => QuizAttempt::STATUS_PENDING_GRADING,
                    'score' => null,
                    'graded_at' => null,
                ]);

                return $lockedAttempt->fresh(['session']);
            }

            return $this->finalizeGradingIfComplete($lockedAttempt);
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
        $remaining = now()->diffInSeconds(Carbon::parse($attempt->expires_at), false);

        return max(0, (int) floor($remaining));
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
            Question::TYPE_ESSAY => [
                [
                    'grading_mode' => 'manual',
                    'rubric' => $question->answer_config['rubric'] ?? [],
                ],
                [
                    'word_limit' => (int) ($question->answer_config['word_limit'] ?? 500),
                    'allow_attachments' => (bool) ($question->answer_config['allow_attachments'] ?? false),
                    'allowed_extensions' => $question->answer_config['allowed_extensions'] ?? [],
                    'max_files' => (int) ($question->answer_config['max_files'] ?? 3),
                    'max_file_size_kb' => (int) ($question->answer_config['max_file_size_kb'] ?? 10240),
                ],
            ],
            Question::TYPE_CODE_DEBUG => [
                [
                    'grading_mode' => 'manual',
                    'rubric' => $question->answer_config['rubric'] ?? [],
                ],
                [
                    'language' => 'html_css',
                    'starter_code' => $this->codeFormatter->format((string) ($question->answer_config['starter_code'] ?? '')),
                    'explanation_mode' => (string) ($question->answer_config['explanation_mode'] ?? 'optional'),
                    'explanation_word_limit' => (int) ($question->answer_config['explanation_word_limit'] ?? 150),
                ],
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

        if ($question->question_type === Question::TYPE_ESSAY) {
            $text = is_array($payload) ? ($payload['text'] ?? '') : $payload;
            $text = trim((string) $text);
            $wordLimit = (int) ($question->response_schema_snapshot['word_limit'] ?? 500);
            if ($this->wordCount($text) > $wordLimit) {
                throw ValidationException::withMessages(['answer' => "Bài tự luận không được vượt quá {$wordLimit} từ."]);
            }

            return ['text' => mb_substr($text, 0, 50000)];
        }

        if ($question->question_type === Question::TYPE_CODE_DEBUG) {
            $values = is_array($payload) ? $payload : [];
            $code = mb_substr((string) ($values['code'] ?? ''), 0, 50000);
            $explanation = trim(mb_substr((string) ($values['explanation'] ?? ''), 0, 20000));
            $mode = (string) ($question->response_schema_snapshot['explanation_mode'] ?? 'optional');
            $wordLimit = (int) ($question->response_schema_snapshot['explanation_word_limit'] ?? 150);
            if ($mode === 'required' && $explanation === '') {
                throw ValidationException::withMessages(['answer' => 'Câu này yêu cầu giải thích nguyên nhân lỗi.']);
            }
            if ($wordLimit > 0 && $this->wordCount($explanation) > $wordLimit) {
                throw ValidationException::withMessages(['answer' => "Phần giải thích không được vượt quá {$wordLimit} từ."]);
            }

            return ['code' => $code, 'explanation' => $mode === 'disabled' ? '' : $explanation];
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
            Question::TYPE_ESSAY => trim((string) ($payload['text'] ?? '')) === '',
            Question::TYPE_CODE_DEBUG => trim((string) ($payload['code'] ?? '')) === '',
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

    private function finalizeGradingIfComplete(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->load(['attemptQuestions.answer', 'session']);
        $manualQuestions = $attempt->attemptQuestions->where('grading_mode', 'manual');
        $pending = $manualQuestions->contains(fn ($question) => $question->answer?->grading_status !== 'graded');
        if ($pending) {
            $attempt->update(['status' => QuizAttempt::STATUS_PENDING_GRADING, 'score' => null]);

            return $attempt->fresh();
        }

        $totalMax = max((float) $attempt->attemptQuestions->sum('max_score'), 1.0);
        $manualEarned = (float) $manualQuestions->sum(fn ($question) => (float) ($question->answer?->score ?? 0));
        $autoEarned = (float) $attempt->attemptQuestions->where('grading_mode', 'auto')
            ->sum(fn ($question) => (float) ($question->answer?->score ?? 0));
        $finalScore = round((($autoEarned + $manualEarned) / $totalMax) * 10, 2);
        $manualScore = round(($manualEarned / $totalMax) * 10, 2);
        $releaseNow = ! $attempt->quiz_session_id
            || $attempt->session?->result_release_policy === QuizSession::RELEASE_IMMEDIATE
            || $attempt->session?->results_released_at?->lte(now())
            || ($attempt->session?->result_release_policy === QuizSession::RELEASE_AFTER_SESSION && $attempt->session?->ends_at?->isPast());

        $attempt->update([
            'status' => $releaseNow ? QuizAttempt::STATUS_RELEASED : QuizAttempt::STATUS_GRADED,
            'score' => $finalScore,
            'manual_score' => $manualScore,
            'graded_at' => now(),
            'result_released_at' => $releaseNow ? now() : null,
        ]);

        return $attempt->fresh(['session']);
    }

    private function wordCount(string $text): int
    {
        $text = trim(strip_tags($text));

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);
    }
}
