<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class QuizAttemptController extends Controller
{
    private const SUBMISSION_GRACE_SECONDS = 30;

    // ==========================================
    // 1. MỞ GIAO DIỆN LÀM BÀI VÀ SINH ĐỀ NGẪU NHIÊN
    // ==========================================
    public function create($quiz_id)
    {
        $quiz = Quiz::findOrFail($quiz_id);
        $userId = auth()->id();
        $this->authorizeStudentCanAttempt($quiz);

        // Chặn làm lại bài đã hoàn thành
        $existingAttempt = QuizAttempt::where('quiz_id', $quiz_id)
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->first();

        if ($existingAttempt) {
            return redirect()->route('courses.show', $quiz->course_id)->with('error', 'Bạn đã hoàn thành bài kiểm tra này. Không thể làm lại!');
        }

        $cacheKey = "quiz_session_{$quiz_id}_{$userId}";
        $activeKey = "quiz_active_{$quiz_id}_{$userId}";
        $sessionData = Cache::get($cacheKey);

        if ($sessionData) {
            $elapsed = now()->timestamp - $sessionData['started_at'];
            $remainingSeconds = max(($sessionData['time_limit'] ?? ($quiz->time_limit * 60)) - $elapsed, 0);

            if ($remainingSeconds <= 0) {
                Cache::forget($cacheKey);
                Cache::forget($activeKey);

                return redirect()->route('courses.show', $quiz->course_id)->with('error', 'Phiên làm bài đã hết thời gian. Vui lòng liên hệ giáo viên nếu cần hỗ trợ.');
            }

            $examQuestions = $this->loadExamFromSession($sessionData);

            Cache::put($activeKey, true, now()->addSeconds($remainingSeconds + 300));

            return view('quizzes.attempt', compact('quiz', 'examQuestions', 'remainingSeconds'));
        }

        if (Cache::has($activeKey)) {
            Cache::forget($activeKey);
        }

        // Sinh đề ngẫu nhiên
        $examQuestions = $this->generateExam($quiz);

        // [CHỐNG GIAN LẬN] Lưu đề đã phát lên Cache server-side
        // → bỏ qua hoàn toàn question_ids từ form khi chấm bài
        $ttl = now()->addMinutes($quiz->time_limit + 5);

        Cache::put(
            "quiz_session_{$quiz_id}_{$userId}",
            [
                'question_ids' => $examQuestions->pluck('id')->toArray(),
                'option_ids' => $examQuestions->mapWithKeys(fn ($question) => [
                    $question->id => $question->options->pluck('id')->toArray(),
                ])->toArray(),
                'started_at' => now()->timestamp,
                'time_limit' => $quiz->time_limit * 60,
            ],
            $ttl,
        );

        Cache::put($activeKey, true, $ttl);
        $remainingSeconds = $quiz->time_limit * 60;

        return view('quizzes.attempt', compact('quiz', 'examQuestions', 'remainingSeconds'));
    }

    // ==========================================
    // 2. NỘP BÀI VÀ CHẤM ĐIỂM
    // ==========================================
    public function store(Request $request, $quiz_id)
    {
        $quiz = Quiz::findOrFail($quiz_id);
        $userId = auth()->id();
        $this->authorizeStudentCanAttempt($quiz);

        $cacheKey = "quiz_session_{$quiz_id}_{$userId}";
        $activeKey = "quiz_active_{$quiz_id}_{$userId}";
        $submissionLock = Cache::lock("quiz_submission_{$quiz_id}_{$userId}", 15);

        if (! $submissionLock->get()) {
            return redirect()->route('courses.show', $quiz->course_id)
                ->with('error', 'Bài kiểm tra đang được xử lý. Vui lòng không gửi lại yêu cầu.');
        }

        try {
            // Không tin question_ids từ form; phiên đề và việc nộp được khóa theo quiz/học viên.
            $sessionData = Cache::get($cacheKey);

            if (! $sessionData) {
                Log::warning("[QUIZ] User {$userId} nộp bài {$quiz_id} không có session hợp lệ.");

                return redirect()->route('courses.show', $quiz->course_id)->with('error', 'Phiên làm bài không hợp lệ hoặc đã hết hạn. Kết quả không được ghi nhận!');
            }

            $startedAt = filter_var($sessionData['started_at'] ?? null, FILTER_VALIDATE_INT);
            $timeLimit = filter_var($sessionData['time_limit'] ?? null, FILTER_VALIDATE_INT);
            if ($startedAt === false || $timeLimit === false || $timeLimit <= 0) {
                Cache::forget($cacheKey);
                Cache::forget($activeKey);

                return redirect()->route('courses.show', $quiz->course_id)->with('error', 'Phiên làm bài bị lỗi. Kết quả không được ghi nhận!');
            }

            $elapsed = max(now()->timestamp - $startedAt, 0);
            if ($elapsed > $timeLimit + self::SUBMISSION_GRACE_SECONDS) {
                Log::warning("[QUIZ] User {$userId} nộp bài {$quiz_id} sau khi hết giờ ({$elapsed}s / {$timeLimit}s).");
                Cache::forget($cacheKey);
                Cache::forget($activeKey);

                return redirect()->route('courses.show', $quiz->course_id)
                    ->with('error', 'Đã quá thời gian nộp bài. Kết quả không được ghi nhận!');
            }

            $authorizedIds = array_map('intval', $sessionData['question_ids'] ?? []);
            $answers = $request->input('answers', []);
            $filteredAnswers = array_filter($answers, fn ($qid) => in_array((int) $qid, $authorizedIds, true), ARRAY_FILTER_USE_KEY);

            if (count($filteredAnswers) < count($answers)) {
                Log::warning("[QUIZ] User {$userId} gửi answers ngoài đề cho quiz {$quiz_id}.");
            }

            [$score, $fullAnswers] = $this->grade($authorizedIds, $filteredAnswers, $userId, $quiz_id);

            try {
                $attempt = DB::transaction(function () use ($quiz, $userId, $score, $fullAnswers, $startedAt) {
                    $existingAttempt = QuizAttempt::query()
                        ->where('quiz_id', $quiz->id)
                        ->where('user_id', $userId)
                        ->lockForUpdate()
                        ->first();

                    if ($existingAttempt) {
                        return null;
                    }

                    return QuizAttempt::create([
                        'quiz_id' => $quiz->id,
                        'user_id' => $userId,
                        'score' => $score,
                        'student_answers' => $fullAnswers,
                        'started_at' => now()->setTimestamp($startedAt),
                        'completed_at' => now(),
                    ]);
                }, 3);
            } catch (QueryException $exception) {
                if (! QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $userId)->exists()) {
                    throw $exception;
                }

                $attempt = null;
            }

            if (! $attempt) {
                Cache::forget($cacheKey);
                Cache::forget($activeKey);

                return redirect()->route('courses.show', $quiz->course_id)
                    ->with('error', 'Bài kiểm tra này đã được ghi nhận trước đó. Không tạo thêm kết quả trùng lặp.');
            }

            Cache::forget($cacheKey);
            Cache::forget($activeKey);

            return redirect()
                ->route('courses.show', $quiz->course_id)
                ->with('success', "Nộp bài thành công! Điểm của bạn: {$score}/10");
        } finally {
            $submissionLock->release();
        }
    }

    // ==========================================
    // 3. XEM LẠI BÀI ĐÃ LÀM
    // ==========================================
    public function review($attempt_id)
    {
        $attempt = QuizAttempt::with('quiz.course')->findOrFail($attempt_id);

        Gate::authorize('view', $attempt);

        $studentAnswers = is_string($attempt->student_answers) ? json_decode($attempt->student_answers, true) : $attempt->student_answers ?? [];

        $questionIds = array_keys($studentAnswers);

        $questions = ! empty($questionIds) ? Question::with('options')->whereIn('id', $questionIds)->get()->sortBy(fn ($q) => array_search($q->id, $questionIds)) : collect([]);

        return view('quizzes.review', compact('attempt', 'studentAnswers', 'questions'));
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * Sinh đề ngẫu nhiên theo cấu trúc quiz.
     */
    private function generateExam(Quiz $quiz)
    {
        $bankIds = $quiz->course->questionBanks()->pluck('question_banks.id');
        $pick = fn ($difficulty, $limit) => Question::with('options')
            ->where(function ($q) use ($quiz, $bankIds) {
                if ($bankIds->isNotEmpty()) {
                    $q->whereIn('question_bank_id', $bankIds);
                }

                $q->orWhere('course_id', $quiz->course_id);
            })
            ->where('difficulty', $difficulty)
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        $examQuestions = $pick('easy', $quiz->easy_count)
            ->merge($pick('medium', $quiz->medium_count))
            ->merge($pick('hard', $quiz->hard_count))
            ->shuffle();

        foreach ($examQuestions as $question) {
            $question->setRelation('options', $question->options->shuffle());
        }

        return $examQuestions;
    }

    private function authorizeStudentCanAttempt(Quiz $quiz): void
    {
        Gate::authorize('attempt', $quiz);
    }

    private function loadExamFromSession(array $sessionData)
    {
        $questionIds = $sessionData['question_ids'] ?? [];
        $optionIdsByQuestion = $sessionData['option_ids'] ?? [];

        return Question::with('options')
            ->whereIn('id', $questionIds)
            ->get()
            ->sortBy(fn ($question) => array_search($question->id, $questionIds))
            ->map(function ($question) use ($optionIdsByQuestion) {
                $optionIds = $optionIdsByQuestion[$question->id] ?? [];

                if (! empty($optionIds)) {
                    $question->setRelation(
                        'options',
                        $question->options->sortBy(fn ($option) => array_search($option->id, $optionIds))->values()
                    );
                }

                return $question;
            })
            ->values();
    }

    /**
     * Chấm điểm, trả về [$score, $fullAnswers].
     * Xác minh từng option_id thuộc đúng question tương ứng
     * để chống inject option của câu khác.
     */
    private function grade(array $authorizedIds, array $filteredAnswers, int $userId, $quizId): array
    {
        $total = count($authorizedIds);
        $correct = 0;
        $full = [];

        foreach ($authorizedIds as $questionId) {
            $selectedOptionId = $filteredAnswers[$questionId] ?? null;
            $full[$questionId] = $selectedOptionId;

            if (! $selectedOptionId) {
                continue;
            }

            // Bắt buộc option phải thuộc đúng question — chống inject option_id câu khác
            $isCorrect = Option::where('id', $selectedOptionId)->where('question_id', $questionId)->value('is_correct');

            if ($isCorrect === null) {
                Log::warning("[QUIZ] User {$userId} gửi option {$selectedOptionId} không thuộc question {$questionId} (quiz {$quizId}).");

                continue;
            }

            if ($isCorrect) {
                $correct++;
            }
        }

        $score = $total > 0 ? round(($correct / $total) * 10, 1) : 0;

        return [$score, $full];
    }
}
