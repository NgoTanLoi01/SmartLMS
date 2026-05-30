<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QuizAttemptController extends Controller
{
    // ==========================================
    // 1. MỞ GIAO DIỆN LÀM BÀI VÀ SINH ĐỀ NGẪU NHIÊN
    // ==========================================
    public function create($quiz_id)
    {
        $quiz = Quiz::findOrFail($quiz_id);
        $userId = auth()->id();

        // Chặn làm lại bài đã hoàn thành
        $existingAttempt = QuizAttempt::where('quiz_id', $quiz_id)->where('user_id', $userId)->first();

        if ($existingAttempt) {
            return redirect()->route('courses.show', $quiz->course_id)->with('error', 'Bạn đã hoàn thành bài kiểm tra này. Không thể làm lại!');
        }

        // [CHỐNG GIAN LẬN] Chặn mở nhiều tab / session đồng thời
        $activeKey = "quiz_active_{$quiz_id}_{$userId}";
        if (Cache::has($activeKey)) {
            return redirect()->route('courses.show', $quiz->course_id)->with('error', 'Bạn đang có một phiên làm bài đang mở. Không thể mở thêm tab!');
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
                'started_at' => now()->timestamp,
                'time_limit' => $quiz->time_limit * 60,
            ],
            $ttl,
        );

        Cache::put($activeKey, true, $ttl);

        return view('quizzes.attempt', compact('quiz', 'examQuestions'));
    }

    // ==========================================
    // 2. NỘP BÀI VÀ CHẤM ĐIỂM
    // ==========================================
    public function store(Request $request, $quiz_id)
    {
        $quiz = Quiz::findOrFail($quiz_id);
        $userId = auth()->id();

        // [CHỐNG GIAN LẬN] Lấy đề từ Cache — không tin question_ids từ form
        $cacheKey = "quiz_session_{$quiz_id}_{$userId}";
        $sessionData = Cache::get($cacheKey);

        if (!$sessionData) {
            Log::warning("[QUIZ] User {$userId} nộp bài {$quiz_id} không có session hợp lệ.");
            return redirect()->route('courses.show', $quiz->course_id)->with('error', 'Phiên làm bài không hợp lệ hoặc đã hết hạn. Kết quả không được ghi nhận!');
        }

        // [CHỐNG GIAN LẬN] Kiểm tra nộp bài sau khi hết giờ (cho phép 30s đệm)
        $elapsed = now()->timestamp - $sessionData['started_at'];
        $timeLimit = $sessionData['time_limit'];

        if ($elapsed > $timeLimit + 30) {
            Log::warning("[QUIZ] User {$userId} nộp bài {$quiz_id} sau khi hết giờ ({$elapsed}s / {$timeLimit}s).");
        }

        $authorizedIds = $sessionData['question_ids'];
        $answers = $request->input('answers', []);

        // [CHỐNG GIAN LẬN] Lọc câu trả lời ngoài đề đã phát
        $filteredAnswers = array_filter($answers, fn($qid) => in_array((int) $qid, $authorizedIds), ARRAY_FILTER_USE_KEY);

        if (count($filteredAnswers) < count($answers)) {
            Log::warning("[QUIZ] User {$userId} gửi answers ngoài đề cho quiz {$quiz_id}.");
        }

        // Chấm điểm
        [$score, $fullAnswers] = $this->grade($authorizedIds, $filteredAnswers, $userId, $quiz_id);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $userId,
            'score' => $score,
            'student_answers' => $fullAnswers,
            'started_at' => now()->subSeconds($elapsed),
            'completed_at' => now(),
        ]);

        // Dọn Cache
        Cache::forget($cacheKey);
        Cache::forget("quiz_active_{$quiz_id}_{$userId}");

        return redirect()
            ->route('courses.show', $quiz->course_id)
            ->with('success', "Nộp bài thành công! Điểm của bạn: {$score}/10");
    }

    // ==========================================
    // 3. XEM LẠI BÀI ĐÃ LÀM
    // ==========================================
    public function review($attempt_id)
    {
        $attempt = QuizAttempt::with('quiz.course')->findOrFail($attempt_id);

        $isOwner = auth()->id() === $attempt->user_id;
        $isTeacher = auth()->id() === $attempt->quiz->course->teacher_id;
        $isAdmin = auth()->user()->role === 'admin';

        if (!$isOwner && !$isTeacher && !$isAdmin) {
            abort(403, 'Bạn không có quyền xem bài làm này.');
        }

        $studentAnswers = is_string($attempt->student_answers) ? json_decode($attempt->student_answers, true) : $attempt->student_answers ?? [];

        $questionIds = array_keys($studentAnswers);

        $questions = !empty($questionIds) ? Question::with('options')->whereIn('id', $questionIds)->get()->sortBy(fn($q) => array_search($q->id, $questionIds)) : collect([]);

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
        $pick = fn($difficulty, $limit) => Question::with('options')->where('course_id', $quiz->course_id)->where('difficulty', $difficulty)->inRandomOrder()->limit($limit)->get();

        $examQuestions = $pick('easy', $quiz->easy_count)
            ->merge($pick('medium', $quiz->medium_count))
            ->merge($pick('hard', $quiz->hard_count))
            ->shuffle();

        foreach ($examQuestions as $question) {
            $question->setRelation('options', $question->options->shuffle());
        }

        return $examQuestions;
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

            if (!$selectedOptionId) {
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
