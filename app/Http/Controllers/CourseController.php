<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use App\Models\Classroom;
use App\Models\LearningProgram;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseController extends Controller
{

    public function index()
    {
        $user = auth()->user();
        $filters = [
            'search' => trim(request('search', '')),
            'program_id' => request('program_id'),
            'course_type' => request('course_type'),
            'status' => request('status'),
            'class_id' => request('class_id'),
        ];

        // Khởi tạo query cơ bản kèm đếm số lượng bài học (lessons)
        $query = Course::with(['teacher', 'classes', 'learningProgram'])
            ->withCount('modules') // Đếm số module
            // Đếm tổng bài học của tất cả các module trong khóa học
            ->withCount([
                'modules as lessons_count' => function ($query) {
                    $query->leftJoin('lessons', 'modules.id', '=', 'lessons.module_id')->select(\DB::raw('count(lessons.id)'));
                },
            ]);

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($filters['program_id']) {
            $query->where('learning_program_id', $filters['program_id']);
        }

        if ($filters['course_type'] && in_array($filters['course_type'], ['delivery', 'template'])) {
            $query->where('course_type', $filters['course_type']);
        }

        if ($filters['status'] && in_array($filters['status'], ['draft', 'published', 'hidden', 'archived'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->notArchived();
        }

        if ($filters['class_id']) {
            $query->whereHas('classes', function ($q) use ($filters) {
                $q->where('classes.id', $filters['class_id']);
            });
        }

        if ($user->role === 'admin') {
            $courses = $query->latest()->get();
        } elseif ($user->role === 'teacher') {
            $courses = $query->where('teacher_id', $user->id)->latest()->get();
        } else {
            // Học sinh
            $classIds = $user->classes()->where('classes.status', Classroom::STATUS_ACTIVE)->pluck('classes.id');
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

        $deliveryCourses = $courses->where('course_type', 'delivery')->values();
        $templateCourses = $courses->where('course_type', 'template')->values();
        $filterPrograms = $this->availablePrograms();
        $filterClasses = $this->availableClasses();

        return view('courses.index', compact('courses', 'deliveryCourses', 'templateCourses', 'filters', 'filterPrograms', 'filterClasses'));
    }

    public function show($id)
    {
        // Load khóa học cùng giáo viên, bài học và bài tập của từng bài học
        $course = Course::with([
            'teacher',
            'classes',
            'modules.lessons.assignments',
            'quizzes',
        ])->findOrFail($id);
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
            $assignmentIds = Assignments::where('course_id', $id)->notArchived()->pluck('id')->toArray();
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
        $this->authorizeCourseCreation();

        $programs = $this->availablePrograms();
        $templateCourses = $this->availableTemplateCourses(request('template_course_id'));
        $availableClasses = $this->availableClasses();

        return view('courses.create', compact('programs', 'templateCourses', 'availableClasses'));
    }

    public function store(Request $request)
    {
        $this->authorizeCourseCreation();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'learning_program_id' => 'nullable|exists:learning_programs,id',
            'course_type' => 'required|in:delivery,template',
            'template_course_id' => 'nullable|exists:courses,id',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:classes,id',
            'status' => 'nullable|in:draft,published,hidden,archived',
            'available_from' => 'nullable|date',
        ]);

        $this->authorizeProgramSelection($request->input('learning_program_id'));
        $this->authorizeClassSelection($request->input('class_ids', []));
        $templateCourse = $request->filled('template_course_id')
            ? $this->authorizedTemplateCourse($request->template_course_id)
            : null;

        $attachedClassCount = 0;

        DB::transaction(function () use ($request, $templateCourse, &$attachedClassCount) {
            $course = Course::create([
                'title' => $request->title,
                'description' => $request->description,
                'teacher_id' => auth()->id(),
                'learning_program_id' => $request->learning_program_id,
                'course_type' => $request->course_type,
                'status' => $request->input('status', 'published'),
                'published_at' => $request->input('status', 'published') === 'published' ? now() : null,
                'available_from' => $request->available_from,
            ]);

            if ($templateCourse) {
                $this->cloneCourseContent($templateCourse, $course);
            }

            if ($request->course_type === 'delivery' && $request->filled('class_ids')) {
                $course->classes()->syncWithoutDetaching($request->class_ids);
                $attachedClassCount = count($request->class_ids);
            }
        });

        $message = $templateCourse
            ? 'Tạo khóa học từ mẫu thành công!'
            : 'Tạo khóa học thành công!';
        if ($attachedClassCount > 0) {
            $message .= " Đã gắn {$attachedClassCount} lớp.";
        }

        return redirect()->route('courses.index')->with('success', $message);
    }

    public function edit($id)
    {
        $course = Course::findOrFail($id);

        // Chỉ cho phép giáo viên của khóa học hoặc admin sửa
        $this->authorizeCourseOwner($course);
        $programs = $this->availablePrograms($course);

        return view('courses.edit', compact('course', 'programs'));
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $this->authorizeCourseOwner($course);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'learning_program_id' => 'nullable|exists:learning_programs,id',
            'course_type' => 'required|in:delivery,template',
            'status' => 'nullable|in:draft,published,hidden,archived',
            'available_from' => 'nullable|date',
        ]);

        $this->authorizeProgramSelection($request->input('learning_program_id'), $course);

        $course->update([
            'title' => $request->title,
            'description' => $request->description,
            'learning_program_id' => $request->learning_program_id,
            'course_type' => $request->course_type,
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

        $course->update([
            'status' => Course::STATUS_ARCHIVED,
            'published_at' => null,
        ]);

        return redirect()->route('courses.index')->with('success', 'Đã lưu trữ khóa học. Dữ liệu học tập vẫn được giữ lại.');
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

            $studentClassIds = $user->classes()->where('classes.status', Classroom::STATUS_ACTIVE)->pluck('classes.id');
            $hasAccess = $course->classes()
                ->where('classes.status', Classroom::STATUS_ACTIVE)
                ->whereIn('classes.id', $studentClassIds)
                ->exists();

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

    private function authorizeCourseCreation(): void
    {
        if (!in_array(auth()->user()->role, ['admin', 'teacher'])) {
            abort(403, 'Bạn không có quyền tạo khóa học.');
        }
    }

    private function availablePrograms(?Course $course = null)
    {
        $query = LearningProgram::orderBy('name');

        if (auth()->user()->role === 'teacher') {
            $query->where('teacher_id', auth()->id());
        }

        $programs = $query->get();

        if ($course?->learning_program_id && !$programs->contains('id', $course->learning_program_id)) {
            $programs->push($course->learningProgram);
        }

        return $programs->filter()->sortBy('name')->values();
    }

    private function availableTemplateCourses($selectedCourseId = null)
    {
        $query = Course::with('learningProgram')
            ->where('course_type', 'template')
            ->notArchived()
            ->orderBy('title');

        if (auth()->user()->role === 'teacher') {
            $query->where('teacher_id', auth()->id());
        }

        $courses = $query->get();

        if ($selectedCourseId && !$courses->contains('id', (int) $selectedCourseId)) {
            $courses->push($this->authorizedTemplateCourse($selectedCourseId));
        }

        return $courses->sortBy('title')->values();
    }

    private function authorizedTemplateCourse($courseId): Course
    {
        $query = Course::with([
            'modules.lessons',
            'assignments',
            'quizzes',
            'questionBanks',
        ]);

        if (auth()->user()->role === 'teacher') {
            $query->where('teacher_id', auth()->id());
        }

        return $query->findOrFail($courseId);
    }

    private function availableClasses()
    {
        $query = Classroom::with('teacher')->orderBy('name');
        $query->notArchived();

        if (auth()->user()->role === 'teacher') {
            $query->where('teacher_id', auth()->id());
        }

        return $query->get();
    }

    private function authorizeClassSelection(array $classIds): void
    {
        $classIds = collect($classIds)->filter()->map(fn ($id) => (int) $id)->unique();

        if ($classIds->isEmpty() || auth()->user()->role === 'admin') {
            return;
        }

        $allowedCount = Classroom::whereIn('id', $classIds)
            ->where('teacher_id', auth()->id())
            ->count();

        if ($allowedCount !== $classIds->count()) {
            abort(403, 'Bạn không có quyền gắn khóa học vào một hoặc nhiều lớp đã chọn.');
        }
    }

    private function authorizeProgramSelection($programId, ?Course $course = null): void
    {
        if (!$programId || auth()->user()->role === 'admin') {
            return;
        }

        if ($course && (int) $course->learning_program_id === (int) $programId) {
            return;
        }

        $ownsProgram = LearningProgram::where('id', $programId)
            ->where('teacher_id', auth()->id())
            ->exists();

        if (!$ownsProgram) {
            abort(403, 'Bạn không có quyền gắn khóa học vào chương trình này.');
        }
    }

    private function cloneCourseContent(Course $sourceCourse, Course $targetCourse): void
    {
        $lessonIdMap = [];

        foreach ($sourceCourse->modules as $sourceModule) {
            $targetModule = Module::create([
                'course_id' => $targetCourse->id,
                'title' => $sourceModule->title,
                'order' => $sourceModule->order,
            ]);

            foreach ($sourceModule->lessons as $sourceLesson) {
                $copiedAttachment = $this->copyLessonAttachment($sourceLesson);
                $targetLesson = Lesson::create([
                    'module_id' => $targetModule->id,
                    'title' => $sourceLesson->title,
                    'content' => $sourceLesson->content,
                    'video_url' => $sourceLesson->video_url,
                    'attachment_path' => $sourceLesson->attachment_path,
                    'attachment' => $copiedAttachment['attachment'],
                    'attachment_disk' => $copiedAttachment['attachment_disk'],
                    'attachment_original_name' => $sourceLesson->attachment_original_name,
                    'attachment_mime_type' => $sourceLesson->attachment_mime_type,
                    'attachment_size' => $sourceLesson->attachment_size,
                    'order' => $sourceLesson->order,
                    'status' => $sourceLesson->status,
                    'published_at' => $sourceLesson->published_at,
                    'available_from' => $sourceLesson->available_from,
                ]);

                $lessonIdMap[$sourceLesson->id] = $targetLesson->id;
            }
        }

        foreach ($sourceCourse->assignments as $sourceAssignment) {
            Assignments::create([
                'course_id' => $targetCourse->id,
                'lesson_id' => $sourceAssignment->lesson_id ? ($lessonIdMap[$sourceAssignment->lesson_id] ?? null) : null,
                'type' => $sourceAssignment->type,
                'title' => $sourceAssignment->title,
                'instructions' => $sourceAssignment->instructions,
                'grading_rubric' => $sourceAssignment->grading_rubric,
                'grading_scale' => $sourceAssignment->grading_scale,
                'ai_grading_enabled' => $sourceAssignment->ai_grading_enabled,
                'due_date' => $sourceAssignment->due_date,
                'allowed_extensions' => $sourceAssignment->allowed_extensions,
                'max_file_size' => $sourceAssignment->max_file_size,
                'status' => $sourceAssignment->status,
                'published_at' => $sourceAssignment->published_at,
                'available_from' => $sourceAssignment->available_from,
            ]);
        }

        foreach ($sourceCourse->quizzes as $sourceQuiz) {
            Quiz::create([
                'course_id' => $targetCourse->id,
                'title' => $sourceQuiz->title,
                'time_limit' => $sourceQuiz->time_limit,
                'is_random' => $sourceQuiz->is_random,
                'easy_count' => $sourceQuiz->easy_count,
                'medium_count' => $sourceQuiz->medium_count,
                'hard_count' => $sourceQuiz->hard_count,
                'status' => $sourceQuiz->status,
                'published_at' => $sourceQuiz->published_at,
                'available_from' => $sourceQuiz->available_from,
            ]);
        }

        $targetCourse->questionBanks()->syncWithoutDetaching(
            $sourceCourse->questionBanks->pluck('id')->all()
        );

        $this->cloneCourseSpecificQuestions($sourceCourse, $targetCourse);
    }

    private function cloneCourseSpecificQuestions(Course $sourceCourse, Course $targetCourse): void
    {
        Question::with('options')
            ->where('course_id', $sourceCourse->id)
            ->whereNull('question_bank_id')
            ->get()
            ->each(function ($sourceQuestion) use ($targetCourse) {
                $targetQuestion = Question::create([
                    'course_id' => $targetCourse->id,
                    'question_bank_id' => null,
                    'question_text' => $sourceQuestion->question_text,
                    'difficulty' => $sourceQuestion->difficulty,
                ]);

                foreach ($sourceQuestion->options as $sourceOption) {
                    $targetQuestion->options()->create([
                        'option_text' => $sourceOption->option_text,
                        'is_correct' => $sourceOption->is_correct,
                    ]);
                }
            });
    }

    private function copyLessonAttachment(Lesson $lesson): array
    {
        $path = $lesson->attachment;
        $sourceDisk = $lesson->attachment_disk ?: 'public';
        $targetDisk = config('filesystems.lesson_attachment_disk', $sourceDisk);

        $result = [
            'attachment' => $path,
            'attachment_disk' => $sourceDisk,
        ];

        if (!$path || !Storage::disk($sourceDisk)->exists($path)) {
            return $result;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = Str::uuid() . ($extension ? '.' . $extension : '');
        $targetPath = 'lessons/attachments/' . $filename;

        $contents = Storage::disk($sourceDisk)->get($path);
        Storage::disk($targetDisk)->put($targetPath, $contents);

        return [
            'attachment' => $targetPath,
            'attachment_disk' => $targetDisk,
        ];
    }

    private function buildCourseDashboard(Course $course): array
    {
        $studentIds = DB::table('class_user')
            ->whereIn('class_id', $course->classes->pluck('id'))
            ->distinct()
            ->pluck('user_id');
        $lessonIds = $course->modules->flatMap->lessons->pluck('id');
        $assignmentIds = Assignments::where('course_id', $course->id)->notArchived()->pluck('id');
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
            ->notArchived()
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
