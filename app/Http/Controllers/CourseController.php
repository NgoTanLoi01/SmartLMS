<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{

    public function index()
    {
        $user = auth()->user();

        // Khởi tạo query cơ bản kèm đếm số lượng bài học (lessons)
        $query = Course::with(['teacher', 'classes'])
            ->withCount('modules') // Đếm số module
            // Đếm tổng bài học của tất cả các module trong khóa học
            ->withCount([
                'modules as lessons_count' => function ($query) {
                    $query->leftJoin('lessons', 'modules.id', '=', 'lessons.module_id')->select(\DB::raw('count(lessons.id)'));
                },
            ]);

        if ($user->role === 'admin') {
            $courses = $query->latest()->get();
        } elseif ($user->role === 'teacher') {
            $courses = $query->where('teacher_id', $user->id)->latest()->get();
        } else {
            // Học sinh
            $classIds = $user->classes()->pluck('classes.id');
            $courses = $query
                ->whereHas('classes', function ($q) use ($classIds) {
                    $q->whereIn('classes.id', $classIds);
                })
                ->visibleToStudents()
                ->with(['modules.lessons'])
                ->latest()
                ->get();

            // Giữ nguyên logic tính progress của thầy
            foreach ($courses as $course) {
                $visibleLessons = $course->modules->flatMap->lessons->filter(fn ($lesson) => $lesson->isVisibleToStudents());
                $totalLessons = $visibleLessons->count();
                $courseLessonIds = $visibleLessons->pluck('id')->toArray();
                $completedLessons = $user->lessons()->whereIn('lesson_id', $courseLessonIds)->whereNotNull('lesson_user.completed_at')->count();
                $course->progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
            }
        }

        // Logic đếm Học sinh (Students) dựa trên quan hệ classes của thầy
        foreach ($courses as $course) {
            // Đếm số lượng user duy nhất tham gia các lớp của khóa học này
            $course->students_count = \DB::table('class_user')
                ->whereIn('class_id', $course->classes->pluck('id'))
                ->distinct('user_id')
                ->count();
        }

        return view('courses.index', compact('courses'));
    }

    public function show($id)
    {
        // Load khóa học cùng giáo viên, bài học và bài tập của từng bài học
        $course = Course::with(['teacher', 'classes', 'modules.lessons.assignments', 'quizzes'])->findOrFail($id);
        $this->authorizeCourseAccess($course);

        if (auth()->user()->role === 'student') {
            $course->setRelation(
                'modules',
                $course->modules
                    ->map(function ($module) {
                        $module->setRelation(
                            'lessons',
                            $module->lessons
                                ->filter(fn ($lesson) => $lesson->isVisibleToStudents())
                                ->map(function ($lesson) {
                                    $lesson->setRelation(
                                        'assignments',
                                        $lesson->assignments->filter(fn ($assignment) => $assignment->isVisibleToStudents())->values()
                                    );

                                    return $lesson;
                                })
                                ->values()
                        );

                        return $module;
                    })
                    ->filter(fn ($module) => $module->lessons->isNotEmpty())
                    ->values()
            );
            $course->setRelation('quizzes', $course->quizzes->filter(fn ($quiz) => $quiz->isVisibleToStudents())->values());
        }

        $completedLessonIds = [];
        $progress = 0;
        $totalLessons = 0;
        $completedCount = 0;
        $userSubmissions = collect();
        $courseDashboard = $this->buildCourseDashboard($course);
        $studentNextAction = null;
        $studentTodoItems = collect();

        if (auth()->check() && auth()->user()->role === 'student') {
            $user = auth()->user();

            // 1. Tính toán tiến độ học tập (Lessons)
            $courseLessonIds = $course->modules->flatMap->lessons->pluck('id')->toArray();
            $totalLessons = count($courseLessonIds);

            $completedLessonIds = $user->lessons()->whereIn('lesson_id', $courseLessonIds)->whereNotNull('lesson_user.completed_at')->pluck('lessons.id')->toArray();

            $completedCount = count($completedLessonIds);
            $progress = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;

            // 2. Lấy dữ liệu bài nộp (Assignments)
            $assignmentIds = Assignments::where('course_id', $id)->pluck('id')->toArray();
            $userSubmissions = AssignmentSubmission::where('user_id', $user->id)->whereIn('assignment_id', $assignmentIds)->get()->keyBy('assignment_id'); // Key hóa theo ID bài tập để View check cực nhanh
            $studentTodoItems = $this->buildStudentCourseTodos($course, $user, $completedLessonIds, $userSubmissions);
            $studentNextAction = $studentTodoItems->first();
        }
        $userQuizAttempts = [];
        if (auth()->check() && auth()->user()->role === 'student') {
            $userQuizAttempts = QuizAttempt::where('user_id', auth()->id())
                ->whereIn('quiz_id', $course->quizzes->pluck('id'))
                ->get()
                ->keyBy('quiz_id');
        }

        return view('courses.show', compact('course', 'completedLessonIds', 'progress', 'totalLessons', 'completedCount', 'userSubmissions', 'userQuizAttempts', 'courseDashboard', 'studentNextAction', 'studentTodoItems'));
    }

    public function create()
    {
        return view('courses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'status' => 'nullable|in:draft,published,hidden',
            'available_from' => 'nullable|date',
        ]);

        Course::create([
            'title' => $request->title,
            'description' => $request->description,
            'teacher_id' => auth()->id(),
            'status' => $request->input('status', 'published'),
            'published_at' => $request->input('status', 'published') === 'published' ? now() : null,
            'available_from' => $request->available_from,
        ]);

        return redirect()->route('courses.index')->with('success', 'Tạo khóa học thành công!');
    }

    public function edit($id)
    {
        $course = Course::findOrFail($id);

        // Chỉ cho phép giáo viên của khóa học hoặc admin sửa
        $this->authorizeCourseOwner($course);

        return view('courses.edit', compact('course'));
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $this->authorizeCourseOwner($course);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'status' => 'nullable|in:draft,published,hidden',
            'available_from' => 'nullable|date',
        ]);

        $course->update([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->input('status', $course->status),
            'published_at' => $request->input('status', $course->status) === 'published' ? ($course->published_at ?? now()) : null,
            'available_from' => $request->available_from,
        ]);

        return redirect()->route('courses.index')->with('success', 'Cập nhật thành công!');
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        // Cần kiểm tra quyền trước khi xóa (giống hàm edit) để bảo mật
        $this->authorizeCourseOwner($course);

        $course->delete();
        return redirect()->route('courses.index')->with('success', 'Đã xóa khóa học.');
    }

    private function authorizeCourseAccess(Course $course): void
    {
        $user = auth()->user();

        if ($user->role === 'admin' || $user->id === $course->teacher_id) {
            return;
        }

        if ($user->role === 'student') {
            if (!$course->isVisibleToStudents()) {
                abort(403, 'Khóa học này chưa được xuất bản.');
            }

            $studentClassIds = $user->classes()->pluck('classes.id');
            $hasAccess = $course->classes()->whereIn('classes.id', $studentClassIds)->exists();

            if ($hasAccess) {
                return;
            }
        }

        abort(403, 'Bạn không có quyền xem khóa học này.');
    }

    private function authorizeCourseOwner(Course $course): void
    {
        if (auth()->user()->role !== 'admin' && auth()->id() !== $course->teacher_id) {
            abort(403, 'Bạn không có quyền thao tác khóa học này.');
        }
    }

    private function buildCourseDashboard(Course $course): array
    {
        $studentIds = DB::table('class_user')
            ->whereIn('class_id', $course->classes->pluck('id'))
            ->distinct()
            ->pluck('user_id');
        $lessonIds = $course->modules->flatMap->lessons->pluck('id');
        $assignmentIds = Assignments::where('course_id', $course->id)->pluck('id');
        $quizIds = $course->quizzes->pluck('id');

        $lessonTotal = $lessonIds->count() * $studentIds->count();
        $lessonCompleted = DB::table('lesson_user')
            ->whereIn('user_id', $studentIds)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->count();

        $assignmentSubmitted = AssignmentSubmission::whereIn('user_id', $studentIds)
            ->whereIn('assignment_id', $assignmentIds)
            ->count();
        $assignmentTotal = $assignmentIds->count() * $studentIds->count();

        $quizAttempted = QuizAttempt::whereIn('user_id', $studentIds)
            ->whereIn('quiz_id', $quizIds)
            ->select('user_id', 'quiz_id')
            ->distinct()
            ->get()
            ->count();
        $quizTotal = $quizIds->count() * $studentIds->count();

        $pendingGrades = AssignmentSubmission::whereIn('assignment_id', $assignmentIds)
            ->whereNull('grade')
            ->count();

        return [
            'students_count' => $studentIds->count(),
            'modules_count' => $course->modules->count(),
            'lessons_count' => $lessonIds->count(),
            'assignments_count' => $assignmentIds->count(),
            'quizzes_count' => $quizIds->count(),
            'lesson_completion_rate' => $lessonTotal > 0 ? round(($lessonCompleted / $lessonTotal) * 100) : 0,
            'assignment_submission_rate' => $assignmentTotal > 0 ? round(($assignmentSubmitted / $assignmentTotal) * 100) : 0,
            'quiz_completion_rate' => $quizTotal > 0 ? round(($quizAttempted / $quizTotal) * 100) : 0,
            'pending_grades' => $pendingGrades,
            'average_score' => QuizAttempt::whereIn('user_id', $studentIds)->whereIn('quiz_id', $quizIds)->avg('score'),
        ];
    }

    private function buildStudentCourseTodos(Course $course, $user, array $completedLessonIds, $userSubmissions)
    {
        $lessonTodos = collect($course->modules
            ->flatMap(function ($module) use ($completedLessonIds) {
                return $module->lessons
                    ->filter(fn ($lesson) => !in_array($lesson->id, $completedLessonIds))
                    ->map(fn ($lesson) => [
                        'type' => 'lesson',
                        'priority' => 1,
                        'label' => 'Tiếp tục học',
                        'title' => $lesson->title,
                        'meta' => $module->title,
                        'target_id' => $lesson->id,
                    ]);
            })
            ->values());

        $assignmentTodos = collect(Assignments::where('course_id', $course->id)
            ->visibleToStudents()
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get()
            ->filter(fn ($assignment) => !$userSubmissions->has($assignment->id))
            ->map(fn ($assignment) => [
                'type' => 'assignment',
                'priority' => $assignment->due_date && $assignment->due_date->isPast() ? 0 : 2,
                'label' => $assignment->due_date && $assignment->due_date->isPast() ? 'Quá hạn' : 'Cần nộp bài',
                'title' => $assignment->title,
                'meta' => $assignment->due_date ? 'Hạn: ' . $assignment->due_date->format('d/m/Y H:i') : 'Không có hạn nộp',
                'target_id' => $assignment->id,
            ])
            ->values());

        $attemptedQuizIds = QuizAttempt::where('user_id', $user->id)
            ->whereIn('quiz_id', $course->quizzes->pluck('id'))
            ->pluck('quiz_id');
        $quizTodos = collect($course->quizzes
            ->whereNotIn('id', $attemptedQuizIds)
            ->map(fn ($quiz) => [
                'type' => 'quiz',
                'priority' => 3,
                'label' => 'Cần làm quiz',
                'title' => $quiz->title,
                'meta' => $quiz->time_limit . ' phút',
                'target_id' => $quiz->id,
            ])
            ->values());

        return collect($assignmentTodos)
            ->merge($lessonTodos)
            ->merge($quizTodos)
            ->sortBy('priority')
            ->values();
    }
}
