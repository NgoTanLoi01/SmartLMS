<?php

namespace App\Http\Controllers;

use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class StudentGradesController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'student') {
            abort(403, 'Trang này chỉ dành cho học sinh.');
        }

        $activeClassIds = $user->classes()
            ->where('classes.status', 'active')
            ->pluck('classes.id');

        $courses = Course::with('teacher')
            ->visibleToStudents()
            ->whereHas('classes', function ($query) use ($activeClassIds) {
                $query->where('classes.status', 'active')
                    ->whereIn('classes.id', $activeClassIds);
            })
            ->orderBy('title')
            ->get();

        $courseIds = $courses->pluck('id');
        $selectedCourseId = $request->filled('course_id') && $courseIds->contains((int) $request->course_id)
            ? (int) $request->course_id
            : null;

        $assignmentSubmissions = AssignmentSubmission::with(['assignment.course'])
            ->where('user_id', $user->id)
            ->whereHas('assignment', function ($query) use ($courseIds, $selectedCourseId) {
                $query->notArchived()
                    ->whereIn('course_id', $courseIds)
                    ->when($selectedCourseId, fn ($q) => $q->where('course_id', $selectedCourseId));
            })
            ->latest('submitted_at')
            ->get();

        $quizAttempts = QuizAttempt::with(['quiz.course', 'session', 'attemptQuestions.answer.grader'])
            ->where('user_id', $user->id)
            ->resultsReleased()
            ->whereHas('quiz', function ($query) use ($courseIds, $selectedCourseId) {
                $query->whereIn('course_id', $courseIds)
                    ->when($selectedCourseId, fn ($q) => $q->where('course_id', $selectedCourseId));
            })
            ->latest('completed_at')
            ->get();

        $assignmentGrades = $assignmentSubmissions
            ->filter(fn ($submission) => $submission->grade !== null)
            ->map(fn ($submission) => [
                'score' => (float) $submission->grade,
                'scale' => (float) ($submission->assignment?->grading_scale ?: 10),
            ]);

        $normalizedAssignmentScores = collect($assignmentGrades
            ->filter(fn ($item) => $item['scale'] > 0)
            ->map(fn ($item) => round(($item['score'] / $item['scale']) * 10, 2))
            ->values()
            ->all());

        $quizScores = collect($quizAttempts
            ->pluck('score')
            ->filter(fn ($score) => $score !== null)
            ->map(fn ($score) => (float) $score)
            ->values()
            ->all());

        $allScores = $normalizedAssignmentScores->concat($quizScores);

        $assignmentFeedback = $assignmentSubmissions
            ->filter(fn ($submission) => trim((string) $submission->feedback) !== '')
            ->map(function ($submission) {
                $assignment = $submission->assignment;

                return [
                    'type' => 'assignment',
                    'type_label' => 'Bài tập',
                    'title' => $assignment?->title ?? 'Bài tập',
                    'course' => $assignment?->course?->title ?? '—',
                    'feedback' => trim((string) $submission->feedback),
                    'score' => $submission->grade !== null ? (float) $submission->grade : null,
                    'scale' => (float) ($assignment?->grading_scale ?: 10),
                    'grader' => null,
                    'date' => $submission->updated_at,
                    'url' => $assignment?->course_id ? route('courses.show', $assignment->course_id) : null,
                ];
            });

        $quizFeedback = $quizAttempts->flatMap(function ($attempt) {
            return $attempt->attemptQuestions
                ->filter(fn ($question) => trim((string) $question->answer?->teacher_feedback) !== '')
                ->map(fn ($question) => [
                    'type' => 'quiz',
                    'type_label' => 'Bài kiểm tra',
                    'title' => $attempt->quiz?->title ?? 'Bài kiểm tra',
                    'subtitle' => 'Câu '.$question->position.': '.$question->question_text,
                    'course' => $attempt->quiz?->course?->title ?? '—',
                    'feedback' => trim((string) $question->answer->teacher_feedback),
                    'score' => $question->answer->score !== null ? (float) $question->answer->score : null,
                    'scale' => (float) ($question->max_score ?: 1),
                    'grader' => $question->answer->grader?->name,
                    'date' => $question->answer->graded_at ?? $attempt->graded_at ?? $attempt->completed_at,
                    'url' => route('quizzes.review', $attempt->id),
                ]);
        });

        $feedbackItems = collect($assignmentFeedback->values()->all())
            ->concat($quizFeedback->values()->all())
            ->sortByDesc(fn ($item) => $item['date']?->timestamp ?? 0)
            ->values();

        $stats = [
            'average_score' => $allScores->isNotEmpty() ? round($allScores->avg(), 1) : null,
            'assignment_average' => $normalizedAssignmentScores->isNotEmpty() ? round($normalizedAssignmentScores->avg(), 1) : null,
            'quiz_average' => $quizScores->isNotEmpty() ? round($quizScores->avg(), 1) : null,
            'graded_assignments' => $assignmentGrades->count(),
            'pending_assignments' => $assignmentSubmissions->whereNull('grade')->count(),
            'completed_quizzes' => $quizAttempts->count(),
            'feedback_count' => $feedbackItems->count(),
        ];

        $recentFeedback = $feedbackItems->take(8)->values();

        $filters = [
            'course_id' => $selectedCourseId,
        ];

        return view('students.grades', compact(
            'courses',
            'assignmentSubmissions',
            'quizAttempts',
            'stats',
            'recentFeedback',
            'filters'
        ));
    }
}
