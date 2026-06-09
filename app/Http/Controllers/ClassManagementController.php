<?php

namespace App\Http\Controllers;

use App\Imports\StudentImport;
use App\Models\AssignmentSubmission;
use App\Models\Assignments;
use App\Models\AttendanceColumn;
use App\Models\AttendanceData;
use App\Models\User;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\DeepSeekService;
use App\Support\StudentLoginCode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class ClassManagementController extends Controller
{
    public function storeStudent(Request $request, $classId)
    {
        $classroom = Classroom::findOrFail($classId);

        if (auth()->user()->role !== 'admin' && auth()->id() !== $classroom->teacher_id) {
            return response()->json(['message' => 'Không có quyền'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $username = StudentLoginCode::generateForClass($classroom);

        $student = User::create([
            'name' => $request->name,
            'username' => $username,
            'email' => $request->filled('email') ? $request->email : StudentLoginCode::emailFromUsername($username),
            'password' => Hash::make($request->password),
            'role' => 'student',
        ]);

        $classroom->students()->attach($student->id);

        return back()->with('success', "Đã tạo học sinh và gán vào lớp thành công! Mã đăng nhập: {$username}");
    }

    public function getStudentsByClass($classId)
    {
        $classroom = Classroom::with(['students', 'teacher', 'courses'])->findOrFail($classId);
        $this->authorizeClassroomAccess($classroom);

        $filters = [
            'search' => trim(request('search', '')),
            'course_id' => request('course_id'),
            'status' => request('status', 'all'),
        ];

        $availableCourses = $classroom->courses;
        $selectedCourseIds = $filters['course_id']
            ? [(int) $filters['course_id']]
            : $availableCourses->pluck('id')->all();

        $students = $classroom->students;

        if ($filters['search'] !== '') {
            $keyword = mb_strtolower($filters['search']);
            $students = $students->filter(function ($student) use ($keyword) {
                return str_contains(mb_strtolower($student->name), $keyword)
                    || str_contains(mb_strtolower($student->email), $keyword)
                    || str_contains(mb_strtolower($student->username ?? ''), $keyword);
            });
        }

        $allStudentSummaries = $students
            ->map(fn ($student) => $this->buildStudentSnapshot($classroom, $student, $selectedCourseIds))
            ->values();

        $studentSummaries = $allStudentSummaries;
        if ($filters['status'] !== 'all') {
            $studentSummaries = $studentSummaries
                ->filter(fn ($summary) => $this->matchesStudentStatus($summary, $filters['status']))
                ->values();
        }

        $classStats = [
            'total' => $classroom->students->count(),
            'shown' => $studentSummaries->count(),
            'needs_attention' => $allStudentSummaries->where('needs_attention', true)->count(),
            'missing_assignments' => $allStudentSummaries->filter(fn ($summary) => $summary['assignment_missing_count'] > 0)->count(),
            'low_score' => $allStudentSummaries->filter(fn ($summary) => $summary['quiz_average'] !== null && $summary['quiz_average'] < 5)->count(),
            'absent' => $allStudentSummaries->filter(fn ($summary) => $summary['absence_count'] > 0)->count(),
        ];

        return view('classes.students', compact('classroom', 'availableCourses', 'studentSummaries', 'classStats', 'filters'));
    }

    public function showStudent($classId, $studentId)
    {
        $classroom = Classroom::with(['students', 'teacher', 'courses'])->findOrFail($classId);
        $this->authorizeClassroomAccess($classroom);

        $student = $classroom->students()
            ->where('users.id', $studentId)
            ->where('role', 'student')
            ->firstOrFail();

        $availableCourses = $classroom->courses;
        $selectedCourseIds = request('course_id')
            ? [(int) request('course_id')]
            : $availableCourses->pluck('id')->all();

        $filters = [
            'course_id' => request('course_id'),
        ];

        $studentProfile = $this->buildStudentSnapshot($classroom, $student, $selectedCourseIds);

        return view('classes.student-profile', compact('classroom', 'student', 'availableCourses', 'studentProfile', 'filters'));
    }

    public function showProgress($classId)
    {
        $classroom = Classroom::with(['students', 'teacher', 'courses'])->findOrFail($classId);
        $this->authorizeClassroomAccess($classroom);

        $filters = [
            'course_id' => request('course_id'),
            'attention_only' => request()->boolean('attention_only'),
        ];

        $availableCourses = $classroom->courses;
        $selectedCourseIds = $filters['course_id']
            ? [(int) $filters['course_id']]
            : $availableCourses->pluck('id')->all();

        $allStudentProgress = $classroom->students
            ->map(fn ($student) => $this->buildStudentSnapshot($classroom, $student, $selectedCourseIds))
            ->values();

        $studentProgress = $filters['attention_only']
            ? $allStudentProgress->where('needs_attention', true)->values()
            : $allStudentProgress;

        $classReport = $this->buildClassProgressReport($allStudentProgress);
        $courseReports = $availableCourses
            ->map(function ($course) use ($classroom) {
                $courseProgress = $classroom->students
                    ->map(fn ($student) => $this->buildStudentSnapshot($classroom, $student, [$course->id]))
                    ->values();

                return [
                    'course' => $course,
                    'report' => $this->buildClassProgressReport($courseProgress),
                ];
            })
            ->values();

        return view('classes.progress', compact('classroom', 'availableCourses', 'studentProgress', 'classReport', 'courseReports', 'filters'));
    }

    public function analyzeLearningWithAi(Request $request, DeepSeekService $deepSeekService, $classId)
    {
        $classroom = Classroom::with(['students', 'teacher', 'courses'])->findOrFail($classId);
        $this->authorizeClassroomAccess($classroom);

        $courseIds = $request->filled('course_id')
            ? [(int) $request->input('course_id')]
            : $classroom->courses->pluck('id')->all();

        $students = $classroom->students;
        if ($request->filled('student_id')) {
            $students = $students->where('id', (int) $request->input('student_id'))->values();
        }

        $studentSummaries = $students
            ->map(fn ($student) => $this->buildStudentSnapshot($classroom, $student, $courseIds))
            ->values();

        $payload = [
            'scope' => $request->filled('student_id') ? 'student' : 'class',
            'class' => [
                'name' => $classroom->name,
                'code' => $classroom->code,
                'teacher' => $classroom->teacher?->name,
            ],
            'courses' => Course::whereIn('id', $courseIds)
                ->get(['id', 'title'])
                ->map(fn ($course) => ['id' => $course->id, 'title' => $course->title])
                ->values(),
            'class_report' => $this->buildClassProgressReport($studentSummaries),
            'students' => $studentSummaries
                ->map(fn ($summary) => $this->formatStudentAiPayload($summary))
                ->values(),
        ];

        $result = $deepSeekService->analyzeLearning($payload);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function index()
    {
        $user = auth()->user();
        $teachers = User::where('role', 'teacher')->get();

        if ($user->role === 'admin') {
            // Admin: Thấy tất cả lớp và tất cả khóa học
            $classes = Classroom::withCount('students')
                ->with(['teacher', 'courses'])
                ->get();
            $courses = Course::all();
        } else {
            // Giáo viên: Chỉ thấy lớp mình dạy và khóa học mình tạo
            $classes = Classroom::where('teacher_id', $user->id)->withCount('students')->with('courses')->get();

            // GIẢ SỬ: Bảng courses của thầy có cột 'teacher_id'
            // để xác định ai là người tạo khóa học đó
            $courses = Course::where('teacher_id', $user->id)->get();
        }

        return view('classes.index', compact('classes', 'teachers', 'courses'));
    }

    public function store(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'teacher'])) {
            return back()->with('error', 'Bạn không có quyền tạo lớp học.');
        }

        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:classes,code',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ];

        // Nếu là admin thì mới bắt buộc chọn teacher_id từ request
        if (auth()->user()->role === 'admin') {
            $rules['teacher_id'] = 'required|exists:users,id';
        }

        $request->validate($rules);

        // Trích đoạn cập nhật trong hàm store()
        $classroom = Classroom::create([
            'name' => $request->name,
            'code' => $request->code,
            // Gán teacher_id từ form nếu là admin, gán ID hiện tại nếu là giáo viên
            'teacher_id' => auth()->user()->role === 'admin' ? $request->teacher_id : auth()->id(),
        ]);

        if ($request->has('course_ids')) {
            $classroom->courses()->attach($request->course_ids);
        }

        return back()->with('success', 'Đã tạo lớp học và phân bổ khóa học thành công!');
    }

    public function update(Request $request, $id)
    {
        $classroom = Classroom::findOrFail($id);

        if (auth()->user()->role !== 'admin' && auth()->id() !== $classroom->teacher_id) {
            return back()->with('error', 'Bạn không có quyền sửa lớp này.');
        }

        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:classes,code,' . $id,
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ];

        if (auth()->user()->role === 'admin') {
            $rules['teacher_id'] = 'required|exists:users,id';
        }

        $request->validate($rules);

        $classroom->name = $request->name;
        $classroom->code = $request->code;
        if (auth()->user()->role === 'admin') {
            $classroom->teacher_id = $request->teacher_id;
        }
        $classroom->save();

        // sync() sẽ tự động xóa các khóa học cũ không còn được chọn và thêm khóa học mới
        if ($request->has('course_ids')) {
            $classroom->courses()->sync($request->course_ids);
        } else {
            $classroom->courses()->detach(); // Xóa sạch nếu không chọn gì
        }

        return back()->with('success', 'Đã cập nhật thông tin lớp học.');
    }

    // Xóa lớp học
    public function destroy($id)
    {
        $classroom = Classroom::findOrFail($id);

        // Phân quyền
        if (auth()->user()->role !== 'admin' && auth()->id() !== $classroom->teacher_id) {
            return back()->with('error', 'Bạn không có quyền xóa lớp này.');
        }

        $classroom->delete();

        return back()->with('success', 'Đã xóa lớp học thành công.');
    }

    // Xóa học sinh khỏi lớp (Chỉ gỡ liên kết trong bảng class_user)
    public function removeStudent($classId, $studentId)
    {
        $classroom = Classroom::findOrFail($classId);

        // Phân quyền
        if (auth()->user()->role !== 'admin' && auth()->id() !== $classroom->teacher_id) {
            return back()->with('error', 'Bạn không có quyền thực hiện thao tác này.');
        }

        // detach() sẽ gỡ kết nối học sinh khỏi lớp mà không xóa tài khoản
        $classroom->students()->detach($studentId);

        return back()->with('success', 'Đã xóa học sinh khỏi lớp.');
    }

    public function importStudents(Request $request, $classId)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120', // Tối đa 5MB
        ]);

        try {
            // Gọi thư viện Excel để đọc file và chạy file StudentImport
            Excel::import(new StudentImport($classId), $request->file('file'));

            return back()->with('success', 'Đã nhập danh sách học viên từ file Excel thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    private function authorizeClassroomAccess(Classroom $classroom): void
    {
        if (auth()->user()->role !== 'admin' && auth()->id() !== $classroom->teacher_id) {
            abort(403, 'Bạn không có quyền xem lớp này.');
        }
    }

    private function matchesStudentStatus(array $summary, string $status): bool
    {
        return match ($status) {
            'needs_attention' => $summary['needs_attention'],
            'missing_assignments' => $summary['assignment_missing_count'] > 0,
            'low_score' => $summary['quiz_average'] !== null && $summary['quiz_average'] < 5,
            'absent' => $summary['absence_count'] > 0,
            'no_activity' => $summary['last_activity_at'] === null,
            default => true,
        };
    }

    private function buildClassProgressReport($studentSummaries): array
    {
        $lessonTotal = $studentSummaries->sum('lesson_total');
        $lessonCompleted = $studentSummaries->sum('lesson_completed');
        $assignmentTotal = $studentSummaries->sum('assignment_total');
        $assignmentSubmitted = $studentSummaries->sum('assignment_submitted_count');
        $scoreAverage = $studentSummaries->pluck('score_average')->filter(fn ($score) => $score !== null)->avg();

        return [
            'student_count' => $studentSummaries->count(),
            'needs_attention_count' => $studentSummaries->where('needs_attention', true)->count(),
            'lesson_completion_rate' => $lessonTotal > 0 ? round(($lessonCompleted / $lessonTotal) * 100) : 0,
            'lesson_completed' => $lessonCompleted,
            'lesson_total' => $lessonTotal,
            'assignment_submission_rate' => $assignmentTotal > 0 ? round(($assignmentSubmitted / $assignmentTotal) * 100) : 0,
            'assignment_submitted' => $assignmentSubmitted,
            'assignment_total' => $assignmentTotal,
            'score_average' => $scoreAverage !== null ? round($scoreAverage, 1) : null,
            'absence_total' => $studentSummaries->sum('absence_count'),
            'missing_assignment_total' => $studentSummaries->sum('assignment_missing_count'),
            'pending_quiz_total' => $studentSummaries->sum('quiz_pending_count'),
        ];
    }

    private function formatStudentAiPayload(array $summary): array
    {
        return [
            'name' => $summary['student']->name,
            'email' => $summary['student']->email,
            'lesson_progress_percent' => $summary['lesson_progress'],
            'lessons_completed' => $summary['lesson_completed'],
            'lessons_total' => $summary['lesson_total'],
            'assignments_submitted' => $summary['assignment_submitted_count'],
            'assignments_total' => $summary['assignment_total'],
            'assignments_missing' => $summary['assignment_missing_count'],
            'assignments_overdue_missing' => $summary['assignment_overdue_missing_count'],
            'missing_assignments' => $summary['assignment_details']
                ->where('status', 'missing')
                ->take(8)
                ->map(fn ($assignment) => [
                    'title' => $assignment['title'],
                    'course' => $assignment['course_title'],
                    'is_overdue' => $assignment['is_overdue'],
                ])
                ->values(),
            'quizzes_attempted' => $summary['quiz_attempted_count'],
            'quizzes_total' => $summary['quiz_total'],
            'quizzes_pending' => $summary['quiz_pending_count'],
            'pending_quizzes' => $summary['quiz_details']
                ->where('status', 'pending')
                ->take(8)
                ->map(fn ($quiz) => [
                    'title' => $quiz['title'],
                    'course' => $quiz['course_title'],
                ])
                ->values(),
            'assignment_average' => $summary['assignment_average'],
            'quiz_average' => $summary['quiz_average'],
            'score_average' => $summary['score_average'],
            'score_trend' => $summary['score_trend'],
            'score_events' => $summary['score_events']->slice(-10)->values(),
            'absence_count' => $summary['absence_count'],
            'notes' => $summary['notes']
                ->take(5)
                ->map(fn ($note) => [
                    'course' => $note['course_title'],
                    'note' => $note['value'],
                ])
                ->values(),
            'last_activity_at' => $summary['last_activity_at']?->format('Y-m-d H:i'),
            'system_alerts' => collect($summary['alerts'])->pluck('text')->values(),
        ];
    }

    private function buildStudentSnapshot(Classroom $classroom, User $student, array $courseIds): array
    {
        $courses = Course::whereIn('id', $courseIds)->get()->keyBy('id');

        $assignments = Assignments::whereIn('course_id', $courseIds)
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get();
        $assignmentIds = $assignments->pluck('id');
        $submissions = AssignmentSubmission::where('user_id', $student->id)
            ->whereIn('assignment_id', $assignmentIds)
            ->get()
            ->keyBy('assignment_id');

        $submittedCount = $submissions->count();
        $missingCount = max($assignments->count() - $submittedCount, 0);
        $overdueMissingCount = $assignments->filter(function ($assignment) use ($submissions) {
            return $assignment->due_date
                && Carbon::parse($assignment->due_date)->isPast()
                && !$submissions->has($assignment->id);
        })->count();
        $assignmentAverage = $submissions->pluck('grade')->filter(fn ($grade) => $grade !== null)->avg();

        $assignmentDetails = $assignments->map(function ($assignment) use ($submissions, $courses) {
            $submission = $submissions->get($assignment->id);

            return [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'course_title' => $courses->get($assignment->course_id)?->title ?? 'Chưa rõ khóa học',
                'due_date' => $assignment->due_date,
                'is_overdue' => $assignment->due_date && Carbon::parse($assignment->due_date)->isPast() && !$submission,
                'submitted_at' => $submission?->submitted_at,
                'grade' => $submission?->grade,
                'feedback' => $submission?->feedback,
                'status' => $submission ? 'submitted' : 'missing',
            ];
        })->values();

        $quizzes = Quiz::whereIn('course_id', $courseIds)->get();
        $quizIds = $quizzes->pluck('id');
        $quizAttempts = QuizAttempt::where('user_id', $student->id)
            ->whereIn('quiz_id', $quizIds)
            ->orderByDesc('completed_at')
            ->get();
        $attempts = $quizAttempts->groupBy('quiz_id');
        $latestAttempts = $attempts->map(fn ($quizAttempts) => $quizAttempts->first());
        $quizAverage = $latestAttempts->pluck('score')->filter(fn ($score) => $score !== null)->avg();

        $quizDetails = $quizzes->map(function ($quiz) use ($latestAttempts, $courses) {
            $attempt = $latestAttempts->get($quiz->id);

            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'course_title' => $courses->get($quiz->course_id)?->title ?? 'Chưa rõ khóa học',
                'score' => $attempt?->score,
                'completed_at' => $attempt?->completed_at,
                'status' => $attempt ? 'attempted' : 'pending',
            ];
        })->values();

        $lessons = Lesson::query()
            ->select('lessons.*', 'modules.course_id', 'modules.title as module_title')
            ->join('modules', 'lessons.module_id', '=', 'modules.id')
            ->whereIn('modules.course_id', $courseIds)
            ->orderBy('modules.order')
            ->orderBy('lessons.order')
            ->get();
        $lessonIds = $lessons->pluck('id');
        $completedLessons = DB::table('lesson_user')
            ->where('user_id', $student->id)
            ->whereIn('lesson_id', $lessonIds)
            ->get();
        $completedLessonIds = $completedLessons->pluck('lesson_id');
        $lessonTotal = $lessonIds->count();
        $lessonCompleted = $completedLessons->count();
        $lessonProgress = $lessonTotal > 0 ? round(($lessonCompleted / $lessonTotal) * 100) : 0;
        $lessonDetails = $lessons->map(function ($lesson) use ($completedLessonIds, $completedLessons, $courses) {
            $completion = $completedLessons->firstWhere('lesson_id', $lesson->id);

            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'module_title' => $lesson->module_title,
                'course_title' => $courses->get($lesson->course_id)?->title ?? 'Chưa rõ khóa học',
                'is_completed' => $completedLessonIds->contains($lesson->id),
                'completed_at' => $completion?->completed_at,
            ];
        })->values();

        $attendanceColumns = AttendanceColumn::whereIn('course_id', $courseIds)->get()->keyBy('id');
        $attendanceData = AttendanceData::where('user_id', $student->id)
            ->whereIn('attendance_column_id', $attendanceColumns->keys())
            ->get();
        $absenceCount = $attendanceData
            ->filter(function ($entry) use ($attendanceColumns) {
                $column = $attendanceColumns->get($entry->attendance_column_id);
                return $column?->type === 'attendance' && $this->isAbsentValue($entry->value);
            })
            ->count();
        $notes = $attendanceData
            ->filter(function ($entry) use ($attendanceColumns) {
                $column = $attendanceColumns->get($entry->attendance_column_id);
                return $column?->type === 'note' && filled($entry->value);
            })
            ->map(function ($entry) use ($attendanceColumns, $courses) {
                $column = $attendanceColumns->get($entry->attendance_column_id);

                return [
                    'title' => $column?->name ?? 'Ghi chú',
                    'course_title' => $courses->get($column?->course_id)?->title ?? 'Chưa rõ khóa học',
                    'value' => $entry->value,
                    'updated_at' => $entry->updated_at,
                ];
            })
            ->values();

        $lastSubmissionAt = $submissions->pluck('submitted_at')->filter()->max();
        $lastQuizAt = $latestAttempts->pluck('completed_at')->filter()->max();
        $lastLessonAt = $completedLessons->pluck('completed_at')->filter()->max();
        $lastActivityAt = collect([$lastSubmissionAt, $lastQuizAt, $lastLessonAt])
            ->filter()
            ->map(fn ($date) => Carbon::parse($date))
            ->sortByDesc(fn ($date) => $date->timestamp)
            ->first();

        $alerts = [];
        if ($overdueMissingCount > 0) {
            $alerts[] = ['level' => 'danger', 'text' => "Có {$overdueMissingCount} bài quá hạn chưa nộp"];
        } elseif ($missingCount > 0) {
            $alerts[] = ['level' => 'warning', 'text' => "Còn {$missingCount} bài chưa nộp"];
        }

        if ($quizAverage !== null && $quizAverage < 5) {
            $alerts[] = ['level' => 'danger', 'text' => 'Điểm quiz trung bình dưới 5'];
        }

        if ($absenceCount >= 2) {
            $alerts[] = ['level' => 'warning', 'text' => "Có {$absenceCount} lượt vắng/nghỉ"];
        }

        if (!$lastActivityAt && ($assignments->count() > 0 || $quizzes->count() > 0 || $lessonTotal > 0)) {
            $alerts[] = ['level' => 'secondary', 'text' => 'Chưa có hoạt động học tập'];
        }

        $scoreAverage = collect([$assignmentAverage, $quizAverage])
            ->filter(fn ($score) => $score !== null)
            ->avg();
        $scoreEvents = $this->buildScoreEvents($submissions, $assignments, $quizAttempts, $quizzes, $courses);

        return [
            'student' => $student,
            'courses' => $courses->values(),
            'course_count' => $courses->count(),
            'assignment_total' => $assignments->count(),
            'assignment_submitted_count' => $submittedCount,
            'assignment_missing_count' => $missingCount,
            'assignment_overdue_missing_count' => $overdueMissingCount,
            'assignment_average' => $assignmentAverage !== null ? round($assignmentAverage, 1) : null,
            'assignment_details' => $assignmentDetails,
            'quiz_total' => $quizzes->count(),
            'quiz_attempted_count' => $latestAttempts->count(),
            'quiz_pending_count' => max($quizzes->count() - $latestAttempts->count(), 0),
            'quiz_average' => $quizAverage !== null ? round($quizAverage, 1) : null,
            'quiz_details' => $quizDetails,
            'lesson_total' => $lessonTotal,
            'lesson_completed' => $lessonCompleted,
            'lesson_progress' => $lessonProgress,
            'lesson_details' => $lessonDetails,
            'absence_count' => $absenceCount,
            'note_count' => $notes->count(),
            'notes' => $notes,
            'last_activity_at' => $lastActivityAt,
            'alerts' => $alerts,
            'needs_attention' => collect($alerts)->whereIn('level', ['danger', 'warning'])->isNotEmpty(),
            'score_average' => $scoreAverage !== null ? round($scoreAverage, 1) : null,
            'score_events' => $scoreEvents,
            'score_trend' => $this->detectScoreTrend($scoreEvents),
        ];
    }

    private function buildScoreEvents($submissions, $assignments, $quizAttempts, $quizzes, $courses)
    {
        $assignmentsById = $assignments->keyBy('id');
        $quizzesById = $quizzes->keyBy('id');

        $assignmentScoreEvents = $submissions
            ->filter(fn ($submission) => $submission->grade !== null)
            ->map(function ($submission) use ($assignmentsById, $courses) {
                $assignment = $assignmentsById->get($submission->assignment_id);

                return [
                    'type' => 'assignment',
                    'title' => $assignment?->title ?? 'Bài tập',
                    'course' => $courses->get($assignment?->course_id)?->title ?? 'Chưa rõ khóa học',
                    'score' => round((float) $submission->grade, 1),
                    'date' => optional($submission->submitted_at)->format('Y-m-d H:i'),
                ];
            })
            ->values();

        $quizScoreEvents = $quizAttempts
            ->filter(fn ($attempt) => $attempt->score !== null)
            ->map(function ($attempt) use ($quizzesById, $courses) {
                $quiz = $quizzesById->get($attempt->quiz_id);

                return [
                    'type' => 'quiz',
                    'title' => $quiz?->title ?? 'Quiz',
                    'course' => $courses->get($quiz?->course_id)?->title ?? 'Chưa rõ khóa học',
                    'score' => round((float) $attempt->score, 1),
                    'date' => $attempt->completed_at ? Carbon::parse($attempt->completed_at)->format('Y-m-d H:i') : null,
                ];
            })
            ->values();

        return collect($assignmentScoreEvents)
            ->merge($quizScoreEvents)
            ->sortBy(fn ($event) => $event['date'] ?? '9999-12-31')
            ->values();
    }

    private function detectScoreTrend($scoreEvents): string
    {
        $scores = $scoreEvents->pluck('score')->filter(fn ($score) => $score !== null)->values();

        if ($scores->count() < 3) {
            return 'insufficient_data';
        }

        $half = (int) floor($scores->count() / 2);
        $earlyAverage = $scores->take($half)->avg();
        $recentAverage = $scores->slice(-$half)->avg();

        if ($recentAverage <= $earlyAverage - 1) {
            return 'declining';
        }

        if ($recentAverage >= $earlyAverage + 1) {
            return 'improving';
        }

        return 'stable';
    }

    private function isAbsentValue($value): bool
    {
        if (!filled($value)) {
            return false;
        }

        $normalized = mb_strtolower(trim((string) $value));

        return in_array($normalized, ['0', 'no', 'false', 'abs', 'absent', 'v', 'vang', 'vắng', 'nghi', 'nghỉ'], true)
            || str_contains($normalized, 'vắng')
            || str_contains($normalized, 'vang')
            || str_contains($normalized, 'nghỉ')
            || str_contains($normalized, 'nghi')
            || str_contains($normalized, 'absent');
    }
}
