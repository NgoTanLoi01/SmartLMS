<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\NotificationCenter;
use App\Services\QuizQuestionSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    public function __construct(private QuizQuestionSelectionService $questionSelector) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'time_limit' => 'required|integer|min:1|max:480',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'question_distribution' => ['required', 'array'],
            'question_distribution.*' => ['array'],
            'question_distribution.*.*' => ['nullable', 'integer', 'min:0', 'max:500'],
            'status' => 'nullable|in:draft,published,hidden,archived',
            'available_from' => 'nullable|date',
        ]);

        $course = Course::findOrFail((int) $data['course_id']);
        Gate::authorize('create', [Quiz::class, $course]);
        $distribution = $this->questionSelector->normalizeDistribution($data['question_distribution']);
        $this->questionSelector->assertAvailable($course, $distribution);
        $totals = $this->questionSelector->totalsByDifficulty($distribution);

        $quiz = Quiz::create([
            'course_id' => $data['course_id'],
            'title' => $data['title'],
            'time_limit' => $data['time_limit'],
            'max_attempts' => $data['max_attempts'] ?? 1,
            'is_random' => true,
            'easy_count' => $totals['easy'],
            'medium_count' => $totals['medium'],
            'hard_count' => $totals['hard'],
            'question_distribution' => $distribution,
            'status' => $data['status'] ?? Quiz::STATUS_PUBLISHED,
            'published_at' => ($data['status'] ?? Quiz::STATUS_PUBLISHED) === Quiz::STATUS_PUBLISHED ? now() : null,
            'available_from' => $data['available_from'] ?? null,
        ]);

        if ($quiz->status === Quiz::STATUS_PUBLISHED) {
            app(NotificationCenter::class)->notifyCourseStudents(
                $quiz->course_id,
                'quiz',
                'Có bài kiểm tra mới',
                "Bài kiểm tra \"{$quiz->title}\" vừa được đăng.",
                route('courses.show', $quiz->course_id),
                ['quiz_id' => $quiz->id],
                "quiz:{$quiz->id}:published"
            );
        }

        return back()->with('success', 'Đã tạo cấu hình bài kiểm tra ngẫu nhiên thành công!');
    }

    public function edit(Quiz $quiz)
    {
        Gate::authorize('update', $quiz);
        $quiz->load('course');

        return view('quizzes.edit', [
            'quiz' => $quiz,
            'availability' => $this->questionSelector->availableCounts($quiz->course),
            'typeLabels' => Question::typeLabels(),
            'difficultyLabels' => QuizQuestionSelectionService::DIFFICULTIES,
        ]);
    }

    public function update(Request $request, Quiz $quiz)
    {
        Gate::authorize('update', $quiz);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'time_limit' => ['required', 'integer', 'min:1', 'max:480'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'question_distribution' => ['required', 'array'],
            'question_distribution.*' => ['array'],
            'question_distribution.*.*' => ['nullable', 'integer', 'min:0', 'max:500'],
            'status' => ['required', 'in:draft,published,hidden'],
            'available_from' => ['nullable', 'date'],
        ]);

        $distribution = $this->questionSelector->normalizeDistribution($data['question_distribution']);
        $this->questionSelector->assertAvailable($quiz->course, $distribution);
        $totals = $this->questionSelector->totalsByDifficulty($distribution);
        $wasPublished = $quiz->status === Quiz::STATUS_PUBLISHED;

        $quiz->update([
            'title' => $data['title'],
            'time_limit' => $data['time_limit'],
            'max_attempts' => $data['max_attempts'],
            'easy_count' => $totals['easy'],
            'medium_count' => $totals['medium'],
            'hard_count' => $totals['hard'],
            'question_distribution' => $distribution,
            'status' => $data['status'],
            'published_at' => $data['status'] === Quiz::STATUS_PUBLISHED ? ($quiz->published_at ?? now()) : null,
            'available_from' => $data['available_from'] ?? null,
        ]);

        if (! $wasPublished && $quiz->status === Quiz::STATUS_PUBLISHED) {
            app(NotificationCenter::class)->notifyCourseStudents(
                $quiz->course_id,
                'quiz',
                'Có bài kiểm tra mới',
                "Bài kiểm tra \"{$quiz->title}\" vừa được đăng.",
                route('courses.show', $quiz->course_id),
                ['quiz_id' => $quiz->id],
                "quiz:{$quiz->id}:published"
            );
        }

        return redirect()->route('quizzes.show', $quiz)->with('success', 'Đã cập nhật cấu hình bài kiểm tra. Các bài đang làm tiếp tục dùng đề và thời hạn đã được cấp.');
    }

    public function show($id)
    {
        $quiz = Quiz::findOrFail($id);
        Gate::authorize('view', $quiz);

        // ==========================================
        // THUẬT TOÁN LẤY ĐỀ NGẪU NHIÊN & XÁO TRỘN
        // ==========================================
        if ($quiz->is_random) {
            $examQuestions = $this->questionSelector->selectForQuiz($quiz);

            // Xáo trộn thứ tự đáp án cho các câu lựa chọn.
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
        $quiz = Quiz::findOrFail($id);
        Gate::authorize('delete', $quiz);
        $quiz->update([
            'status' => Quiz::STATUS_ARCHIVED,
            'published_at' => null,
        ]);

        return back()->with('success', 'Đã lưu trữ bài kiểm tra. Lịch sử làm bài và điểm số vẫn được giữ lại.');
    }

    public function archived(Course $course)
    {
        Gate::authorize('manageContent', $course);
        $archivedQuizzes = Quiz::query()
            ->where('course_id', $course->id)
            ->where('status', Quiz::STATUS_ARCHIVED)
            ->withCount(['sessions', 'attempts'])
            ->latest('updated_at')
            ->get();

        return view('quizzes.archived', compact('course', 'archivedQuizzes'));
    }

    public function restore($id)
    {
        $quiz = Quiz::where('status', Quiz::STATUS_ARCHIVED)->findOrFail($id);
        Gate::authorize('update', $quiz);
        $quiz->update([
            'status' => Quiz::STATUS_DRAFT,
            'published_at' => null,
        ]);

        return redirect()->route('courses.show', $quiz->course_id)
            ->with('success', 'Đã khôi phục bài kiểm tra về trạng thái bản nháp.');
    }

    public function forceDestroy(Request $request, $id)
    {
        $quiz = Quiz::where('status', Quiz::STATUS_ARCHIVED)->findOrFail($id);
        Gate::authorize('delete', $quiz);
        $request->validate(['confirmation' => ['required', 'string']]);

        if (! hash_equals($quiz->title, (string) $request->confirmation)) {
            throw ValidationException::withMessages([
                'confirmation' => 'Tên bài kiểm tra xác nhận không chính xác.',
            ]);
        }

        if ($quiz->attempts()->exists()) {
            return back()->with('error', 'Không thể xóa vĩnh viễn vì bài kiểm tra đã có bài làm. Bạn có thể tiếp tục giữ trong kho lưu trữ.');
        }

        $courseId = $quiz->course_id;
        DB::transaction(fn () => $quiz->delete());

        return redirect()->route('quizzes.archived', $courseId)
            ->with('success', 'Đã xóa vĩnh viễn bài kiểm tra và các ca thi chưa phát sinh bài làm.');
    }

    public function submissions(Request $request, $id)
    {
        $quiz = Quiz::with(['course.teacher'])->findOrFail($id);
        Gate::authorize('viewSubmissions', $quiz);

        $allowedStatuses = [
            QuizAttempt::STATUS_PENDING_GRADING,
            QuizAttempt::STATUS_GRADED,
            QuizAttempt::STATUS_RELEASED,
            QuizAttempt::STATUS_SUBMITTED,
        ];
        $status = in_array($request->input('status'), $allowedStatuses, true)
            ? $request->input('status')
            : 'all';
        $search = mb_substr(trim((string) $request->input('search')), 0, 100);
        $sessionId = $request->integer('session_id') ?: null;
        if ($sessionId && ! $quiz->sessions()->whereKey($sessionId)->exists()) {
            $sessionId = null;
        }

        $baseQuery = $quiz->attempts()->whereNotNull('completed_at');
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', QuizAttempt::STATUS_PENDING_GRADING)->count(),
            'graded' => (clone $baseQuery)->where('status', QuizAttempt::STATUS_GRADED)->count(),
            'released' => (clone $baseQuery)->where('status', QuizAttempt::STATUS_RELEASED)->count(),
        ];

        $attempts = $baseQuery
            ->with(['user', 'session'])
            ->withCount([
                'attemptQuestions as manual_questions_count' => fn ($query) => $query->where('grading_mode', 'manual'),
                'attemptQuestions as graded_manual_questions_count' => fn ($query) => $query
                    ->where('grading_mode', 'manual')
                    ->whereHas('answer', fn ($answer) => $answer->where('grading_status', 'graded')),
            ])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($sessionId, fn ($query) => $query->where('quiz_session_id', $sessionId))
            ->when($search !== '', fn ($query) => $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where(function ($candidate) use ($search) {
                    $candidate->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('student_code', 'like', "%{$search}%");
                });
            }))
            ->orderByRaw("CASE status WHEN 'pending_grading' THEN 0 WHEN 'graded' THEN 1 WHEN 'released' THEN 2 ELSE 3 END")
            ->orderByDesc('completed_at')
            ->paginate(15)
            ->withQueryString();
        $sessions = $quiz->sessions()->orderByDesc('starts_at')->get();

        return view('quizzes.submissions', compact('quiz', 'attempts', 'stats', 'sessions', 'status', 'search', 'sessionId'));
    }
}
