<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\QuizExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuizSessionController extends Controller
{
    public function index(Quiz $quiz)
    {
        Gate::authorize('update', $quiz);

        $quiz->load(['course.classes.students', 'sessions.candidates', 'sessions.attempts.user']);
        $candidatePool = $quiz->course->classes
            ->flatMap->students
            ->filter(fn (User $user) => $user->isStudent() && $user->canAccessSystem())
            ->unique('id')
            ->sortBy('name')
            ->values();

        return view('quizzes.sessions', compact('quiz', 'candidatePool'));
    }

    public function store(Request $request, Quiz $quiz)
    {
        Gate::authorize('update', $quiz);
        $data = $this->validated($request, $quiz);
        $this->ensureCandidatesAreAvailable($quiz, $data['candidate_ids']);

        DB::transaction(function () use ($quiz, $data) {
            $session = $quiz->sessions()->create([
                'name' => $data['name'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'status' => $data['status'],
                'result_release_policy' => $data['result_release_policy'],
            ]);
            $session->candidates()->sync($this->candidateSyncPayload($data));
        });

        return back()->with('success', 'Đã tạo ca thi và phân công thí sinh.');
    }

    public function update(Request $request, QuizSession $session)
    {
        Gate::authorize('update', $session->quiz);
        $data = $this->validated($request, $session->quiz);
        $this->ensureCandidatesAreAvailable($session->quiz, $data['candidate_ids'], $session);

        $startedCandidateIds = $session->attempts()->pluck('user_id');
        $removedStartedCandidates = $startedCandidateIds->diff($data['candidate_ids']);
        if ($removedStartedCandidates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'candidate_ids' => 'Không thể bỏ thí sinh đã bắt đầu làm bài khỏi ca thi.',
            ]);
        }

        DB::transaction(function () use ($session, $data) {
            $session->update([
                'name' => $data['name'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'status' => $data['status'],
                'result_release_policy' => $data['result_release_policy'],
            ]);
            $session->candidates()->sync($this->candidateSyncPayload($data));
        });

        return back()->with('success', 'Đã cập nhật ca thi.');
    }

    public function destroy(QuizSession $session)
    {
        Gate::authorize('update', $session->quiz);
        if ($session->attempts()->exists()) {
            return back()->with('error', 'Không thể xóa ca thi đã có thí sinh bắt đầu làm bài.');
        }

        $session->delete();

        return back()->with('success', 'Đã xóa ca thi.');
    }

    public function monitor(QuizSession $session, QuizExamService $examService)
    {
        Gate::authorize('viewSubmissions', $session->quiz);
        $examService->submitExpiredForSession($session);
        $session->load(['quiz.course', 'candidates', 'attempts.user']);

        return view('quizzes.monitor', compact('session'));
    }

    public function monitorData(QuizSession $session, QuizExamService $examService)
    {
        Gate::authorize('viewSubmissions', $session->quiz);
        $examService->submitExpiredForSession($session);
        $session->load([
            'candidates',
            'attempts' => fn ($query) => $query->withCount(['answers', 'attemptQuestions']),
        ]);
        $attempts = $session->attempts->keyBy('user_id');

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'summary' => $this->summary($session),
            'candidates' => $session->candidates->map(function (User $candidate) use ($attempts) {
                $attempt = $attempts->get($candidate->id);
                $status = 'not_started';
                if (in_array($attempt?->status, [QuizAttempt::STATUS_PENDING_GRADING, QuizAttempt::STATUS_GRADED, QuizAttempt::STATUS_RELEASED, QuizAttempt::STATUS_SUBMITTED], true)) {
                    $status = $attempt->status;
                } elseif ($attempt?->status === 'in_progress') {
                    $status = $attempt->last_seen_at?->lt(now()->subSeconds(45)) ? 'disconnected' : 'in_progress';
                }

                return [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'student_code' => $candidate->student_code,
                    'status' => $status,
                    'started_at' => $attempt?->started_at?->format('H:i:s'),
                    'last_seen_at' => $attempt?->last_seen_at?->format('H:i:s'),
                    'completed_at' => $attempt?->completed_at?->format('H:i:s'),
                    'answered' => $attempt?->answers_count ?? 0,
                    'total' => $attempt?->attempt_questions_count ?? 0,
                ];
            })->values(),
        ]);
    }

    public function release(QuizSession $session)
    {
        Gate::authorize('viewSubmissions', $session->quiz);
        $releasedAt = now();
        DB::transaction(function () use ($session, $releasedAt) {
            $session->update(['results_released_at' => $releasedAt]);
            $session->attempts()
                ->whereNotNull('completed_at')
                ->whereNotNull('score')
                ->whereIn('status', [QuizAttempt::STATUS_SUBMITTED, QuizAttempt::STATUS_GRADED, QuizAttempt::STATUS_RELEASED])
                ->update(['status' => QuizAttempt::STATUS_RELEASED, 'result_released_at' => $releasedAt]);
        });

        $pending = $session->attempts()->where('status', QuizAttempt::STATUS_PENDING_GRADING)->count();

        return back()->with('success', $pending > 0
            ? "Đã công bố các bài chấm xong; còn {$pending} bài đang chờ giáo viên chấm."
            : 'Đã công bố kết quả cho ca thi.');
    }

    private function validated(Request $request, Quiz $quiz): array
    {
        $candidateIds = $quiz->course->classes()->with('students:id')->get()
            ->flatMap->students->pluck('id')->unique()->all();

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['required', Rule::in([
                QuizSession::STATUS_SCHEDULED,
                QuizSession::STATUS_OPEN,
                QuizSession::STATUS_CLOSED,
                QuizSession::STATUS_CANCELLED,
            ])],
            'result_release_policy' => ['required', Rule::in([
                QuizSession::RELEASE_IMMEDIATE,
                QuizSession::RELEASE_AFTER_SESSION,
                QuizSession::RELEASE_MANUAL,
            ])],
            'candidate_ids' => ['required', 'array', 'min:1', 'max:100'],
            'candidate_ids.*' => ['integer', Rule::in($candidateIds)],
            'extra_time_minutes' => ['nullable', 'array'],
            'extra_time_minutes.*' => ['nullable', 'integer', 'min:0', 'max:180'],
        ]);
    }

    private function candidateSyncPayload(array $data): array
    {
        return collect($data['candidate_ids'])->mapWithKeys(fn ($userId) => [
            $userId => ['extra_time_minutes' => (int) ($data['extra_time_minutes'][$userId] ?? 0)],
        ])->all();
    }

    private function ensureCandidatesAreAvailable(Quiz $quiz, array $candidateIds, ?QuizSession $except = null): void
    {
        $assigned = DB::table('quiz_session_user')
            ->join('quiz_sessions', 'quiz_sessions.id', '=', 'quiz_session_user.quiz_session_id')
            ->where('quiz_sessions.quiz_id', $quiz->id)
            ->when($except, fn ($query) => $query->where('quiz_sessions.id', '!=', $except->id))
            ->whereIn('quiz_session_user.user_id', $candidateIds)
            ->pluck('quiz_session_user.user_id');

        if ($assigned->isNotEmpty()) {
            throw ValidationException::withMessages([
                'candidate_ids' => 'Một hoặc nhiều thí sinh đã được xếp vào ca thi khác của đề này.',
            ]);
        }
    }

    private function summary(QuizSession $session): array
    {
        $attempts = $session->attempts;
        $active = $attempts->where('status', 'in_progress');

        return [
            'total' => $session->candidates->count(),
            'not_started' => $session->candidates->count() - $attempts->count(),
            'in_progress' => $active->filter(fn ($attempt) => $attempt->last_seen_at?->gte(now()->subSeconds(45)))->count(),
            'disconnected' => $active->filter(fn ($attempt) => ! $attempt->last_seen_at || $attempt->last_seen_at->lt(now()->subSeconds(45)))->count(),
            'submitted' => $attempts->whereIn('status', [QuizAttempt::STATUS_SUBMITTED, QuizAttempt::STATUS_PENDING_GRADING, QuizAttempt::STATUS_GRADED, QuizAttempt::STATUS_RELEASED])->count(),
            'pending_grading' => $attempts->where('status', QuizAttempt::STATUS_PENDING_GRADING)->count(),
            'graded' => $attempts->where('status', QuizAttempt::STATUS_GRADED)->count(),
            'released' => $attempts->where('status', QuizAttempt::STATUS_RELEASED)->count(),
        ];
    }
}
