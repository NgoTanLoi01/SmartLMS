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

        $quizAttempts = QuizAttempt::with(['quiz.course'])
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
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

        $normalizedAssignmentScores = $assignmentGrades
            ->filter(fn ($item) => $item['scale'] > 0)
            ->map(fn ($item) => round(($item['score'] / $item['scale']) * 10, 2));

        $quizScores = $quizAttempts
            ->pluck('score')
            ->filter(fn ($score) => $score !== null)
            ->map(fn ($score) => (float) $score);

        $allScores = $normalizedAssignmentScores->merge($quizScores);

        $stats = [
            'average_score' => $allScores->isNotEmpty() ? round($allScores->avg(), 1) : null,
            'assignment_average' => $normalizedAssignmentScores->isNotEmpty() ? round($normalizedAssignmentScores->avg(), 1) : null,
            'quiz_average' => $quizScores->isNotEmpty() ? round($quizScores->avg(), 1) : null,
            'graded_assignments' => $assignmentGrades->count(),
            'pending_assignments' => $assignmentSubmissions->whereNull('grade')->count(),
            'completed_quizzes' => $quizAttempts->count(),
            'feedback_count' => $assignmentSubmissions->filter(fn ($submission) => trim((string) $submission->feedback) !== '')->count(),
        ];

        $recentFeedback = $assignmentSubmissions
            ->filter(fn ($submission) => trim((string) $submission->feedback) !== '')
            ->take(5)
            ->values();

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
