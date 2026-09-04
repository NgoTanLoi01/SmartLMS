<?php

namespace App\Http\Controllers;

use App\Exports\AssignmentGradesExport;
use App\Imports\AssignmentGradesImport;
use App\Jobs\AnalyzeAssignmentSubmission;
use App\Models\AiOperation;
use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\GradingFeedbackTemplate;
use App\Models\Lesson;
use App\Models\User;
use App\Rules\SafeSpreadsheet;
use App\Services\AuditLogger;
use App\Services\NotificationCenter;
use App\Services\SubmissionArchiveService;
use App\Services\SubmissionFileService;
use App\Support\AssignmentUploadTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;

class AssignmentController extends Controller
{
    public function __construct(
        private SubmissionFileService $submissionFiles,
        private SubmissionArchiveService $submissionArchives,
    ) {}

    // Hiển thị danh sách
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $assignmentQuery = Assignments::query()
                ->with('course')
                ->withCount(['submissions', 'submissions as pending_grading_count' => fn ($query) => $query->whereNull('grade')])
                ->notArchived()
                ->whereHas('course', fn ($query) => $query->notArchived());
        } elseif ($user->role === 'teacher') {
            $courseIds = Course::where('teacher_id', $user->id)->notArchived()->pluck('id');
            $assignmentQuery = Assignments::query()
                ->with('course')
                ->withCount(['submissions', 'submissions as pending_grading_count' => fn ($query) => $query->whereNull('grade')])
                ->notArchived()
                ->whereIn('course_id', $courseIds);
        } else {
            // Học viên: Chỉ lấy bài tập trạng thái 'published' và thuộc lớp đang học
            $classIds = $user->classes()->where('classes.status', 'active')->pluck('classes.id');
            $courseIds = Course::visibleToStudents()
                ->whereHas('classes', function ($q) use ($classIds) {
                    $q->where('classes.status', 'active')->whereIn('classes.id', $classIds);
                })
                ->pluck('id');

            $assignmentQuery = Assignments::query()
                ->with([
                    'course',
                    'submissions' => function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    },
                ])
                ->whereIn('course_id', $courseIds)
                ->visibleToStudents();
        }

        $statsQuery = clone $assignmentQuery;
        $assignmentStats = [
            'total' => (clone $statsQuery)->count(),
            'upcoming' => (clone $statsQuery)->where('due_date', '>=', now())->count(),
            'due_soon' => (clone $statsQuery)->whereBetween('due_date', [now(), now()->addDays(7)])->count(),
            'overdue' => (clone $statsQuery)->where('due_date', '<', now())->count(),
        ];

        if ($user->isStudent()) {
            $assignmentStats['submitted'] = (clone $statsQuery)
                ->whereHas('submissions', fn ($query) => $query->where('user_id', $user->id))
                ->count();
            $assignmentStats['pending'] = (clone $statsQuery)
                ->where('due_date', '>=', now())
                ->whereDoesntHave('submissions', fn ($query) => $query->where('user_id', $user->id))
                ->count();
            $assignmentStats['overdue'] = (clone $statsQuery)
                ->where('due_date', '<', now())
                ->whereDoesntHave('submissions', fn ($query) => $query->where('user_id', $user->id))
                ->count();
        } else {
            $assignmentStats['published'] = (clone $statsQuery)
                ->where('status', Assignments::STATUS_PUBLISHED)
                ->count();
        }

        $assignmentQuery
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->input('q'));
                $query->where(function ($match) use ($search) {
                    $match->where('title', 'like', "%{$search}%")
                        ->orWhere('instructions', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('course_id'), fn ($query) => $query->where('course_id', $request->integer('course_id')));

        $status = (string) $request->input('status');
        if ($user->isStudent()) {
            if ($status === 'submitted') {
                $assignmentQuery->whereHas('submissions', fn ($query) => $query->where('user_id', $user->id));
            } elseif ($status === 'pending') {
                $assignmentQuery->where('due_date', '>=', now())
                    ->whereDoesntHave('submissions', fn ($query) => $query->where('user_id', $user->id));
            } elseif ($status === 'overdue') {
                $assignmentQuery->where('due_date', '<', now())
                    ->whereDoesntHave('submissions', fn ($query) => $query->where('user_id', $user->id));
            }
        } elseif (in_array($status, [Assignments::STATUS_DRAFT, Assignments::STATUS_PUBLISHED, Assignments::STATUS_HIDDEN], true)) {
            $assignmentQuery->where('status', $status);
        } elseif ($status === 'upcoming') {
            $assignmentQuery->where('due_date', '>=', now());
        } elseif ($status === 'overdue') {
            $assignmentQuery->where('due_date', '<', now());
        }

        $assignments = $assignmentQuery
            ->latest()
            ->paginate(18)
            ->withQueryString();

        if ($user->role === 'teacher') {
            $courses = Course::with('modules.lessons')->where('teacher_id', $user->id)->notArchived()->get();
        } elseif ($user->role === 'student') {
            $courses = Course::with('modules.lessons')->whereIn('id', $courseIds)->notArchived()->get();
        } else {
            $courses = Course::with('modules.lessons')->notArchived()->get();
        }

        return view('assignments.index', compact('assignments', 'courses', 'assignmentStats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lesson_id' => 'required|exists:lessons,id',
            'type' => 'required|in:file,essay,mixed',
            'title' => 'required|string|max:255',
            'instructions' => 'required|string',
            'grading_rubric' => 'nullable|string',
            'grading_scale' => 'nullable|integer|min:1|max:100',
            'ai_grading_enabled' => 'nullable|boolean',
            'due_date' => 'required|date',
            'allowed_extensions' => 'nullable|array|min:1|max:20',
            'allowed_extensions.*' => 'string|max:10',
            'max_file_size' => 'nullable|integer|min:1|max:20480',
            'status' => 'required|in:draft,published,hidden,archived',
            'available_from' => 'nullable|date',
        ]);

        $course = Course::findOrFail($request->integer('course_id'));
        Gate::authorize('create', [Assignments::class, $course]);
        $lesson = Lesson::with('module')->findOrFail($request->integer('lesson_id'));
        abort_unless((int) $lesson->module?->course_id === (int) $course->id, 422, 'Bài học không thuộc khóa học đã chọn.');

        $data = $request->only([
            'course_id', 'lesson_id', 'type', 'title', 'instructions', 'grading_rubric',
            'grading_scale', 'due_date', 'allowed_extensions', 'max_file_size', 'status', 'available_from',
        ]);
        $data['allowed_extensions'] = $this->normalizeAllowedExtensions($data['allowed_extensions'] ?? null);
        $data['max_file_size'] = $data['max_file_size'] ?? 20480;
        $data['grading_scale'] = $data['grading_scale'] ?? 10;
        $data['ai_grading_enabled'] = $request->boolean('ai_grading_enabled');
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        $assignment = Assignments::create($data);
        if ($assignment->status === Assignments::STATUS_PUBLISHED) {
            app(NotificationCenter::class)->notifyCourseStudents(
                $assignment->course_id,
                'assignment',
                'Có bài tập mới',
                "Bài tập \"{$assignment->title}\" vừa được đăng.",
                route('courses.show', ['course' => $assignment->course_id, 'assignment_id' => $assignment->id]),
                ['assignment_id' => $assignment->id],
                "assignment:{$assignment->id}:published"
            );
        }

        return back()->with('success', 'Đã tạo bài tập thành công!');
    }

    // Giáo viên chấm điểm
    public function grade(Request $request, $submissionId)
    {
        $submission = AssignmentSubmission::with('assignment.course')->findOrFail($submissionId);
        Gate::authorize('grade', $submission);
        $scale = $submission->assignment?->grading_scale ?? 10;
        $oldValues = AuditLogger::snapshot($submission, ['grade', 'feedback', 'grading_status', 'rubric_scores', 'grade_published_at']);

        $validated = $request->validate([
            'grade' => 'required|numeric|min:0|max:'.$scale,
            'feedback' => 'nullable|string|max:5000',
            'action' => 'nullable|in:save_draft,publish,publish_next,save,save_next',
            'rubric_scores' => 'nullable|array|max:20',
            'rubric_scores.*.score' => 'nullable|numeric|min:0',
        ]);
        $action = $validated['action'] ?? 'publish';
        $publish = in_array($action, ['publish', 'publish_next', 'save', 'save_next'], true);
        $shouldNotify = $publish && (
            ! $submission->isGradePublished()
            || abs((float) $submission->grade - (float) $validated['grade']) > 0.00001
            || (string) $submission->feedback !== (string) ($validated['feedback'] ?? '')
        );
        $rubricScores = $this->normalizeRubricScores(
            $submission->assignment,
            $validated['rubric_scores'] ?? [],
            (float) $validated['grade']
        );

        $submission->update([
            'grade' => $validated['grade'],
            'feedback' => $validated['feedback'] ?? null,
            'rubric_scores' => $rubricScores,
            'grading_status' => $publish
                ? AssignmentSubmission::GRADING_PUBLISHED
                : AssignmentSubmission::GRADING_DRAFT,
            'grade_published_at' => $publish
                ? ($shouldNotify ? now() : ($submission->grade_published_at ?? now()))
                : null,
        ]);

        if ($shouldNotify) {
            $this->notifyPublishedGrade($submission, $scale);
        }

        AuditLogger::log(
            AuditLogger::GRADE_UPDATED,
            $submission,
            $oldValues,
            AuditLogger::snapshot($submission->fresh(), ['grade', 'feedback', 'grading_status', 'rubric_scores', 'grade_published_at']),
            [
                'assignment_id' => $submission->assignment_id,
                'assignment_title' => $submission->assignment?->title,
                'student_id' => $submission->user_id,
            ],
            $publish
                ? 'Giáo viên chấm và công bố điểm bài nộp.'
                : 'Giáo viên lưu nháp điểm bài nộp.'
        );

        if (in_array($action, ['publish_next', 'save_next'], true)) {
            $nextSubmission = AssignmentSubmission::query()
                ->where('assignment_id', $submission->assignment_id)
                ->whereNull('grade')
                ->where('id', '!=', $submission->id)
                ->orderByRaw('submitted_at IS NULL')
                ->orderBy('submitted_at')
                ->orderBy('id')
                ->first();

            if ($nextSubmission) {
                return redirect()
                    ->route('assignments.submissions.review', $nextSubmission)
                    ->with('success', 'Đã công bố điểm. Đang chuyển sang bài chưa chấm tiếp theo.');
            }

            return redirect()
                ->route('assignments.submissions.review', $submission)
                ->with('success', 'Đã công bố điểm. Không còn bài nộp nào chờ chấm.');
        }

        return back()->with('success', $publish
            ? 'Đã công bố điểm và nhận xét cho học viên.'
            : 'Đã lưu nháp điểm. Học viên chưa nhìn thấy kết quả.');
    }

    public function analyzeSubmissionWithAi($submissionId)
    {
        $submission = AssignmentSubmission::with(['assignment.course', 'user'])->findOrFail($submissionId);
        $assignment = $submission->assignment;
        $course = $assignment->course;

        Gate::authorize('analyze', $submission);

        if (! $assignment->ai_grading_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'AI hỗ trợ chấm đang tắt cho bài tập này.',
            ], 422);
        }

        if (trim((string) $submission->text_answer) === '' && ! $submission->file_path) {
            return response()->json([
                'success' => false,
                'message' => 'AI chưa có nội dung văn bản để phân tích bài nộp này.',
            ], 422);
        }
        $operation = AiOperation::create([
            'user_id' => auth()->id(), 'feature' => 'assignment_grading', 'provider' => 'deepseek',
            'model' => config('services.deepseek.model', 'deepseek-v4-flash'), 'status' => AiOperation::STATUS_QUEUED,
            'subject_type' => AssignmentSubmission::class, 'subject_id' => $submission->id,
            'metadata' => ['assignment_id' => $assignment->id, 'student_id' => $submission->user_id],
        ]);
        AnalyzeAssignmentSubmission::dispatch($operation->id, $submission->id)->afterCommit();

        return response()->json(['success' => true, 'queued' => true, 'operation_id' => $operation->uuid, 'status_url' => route('ai-operations.show', $operation->uuid)], 202);
    }

    public function reviewSubmission($submissionId)
    {
        $submission = AssignmentSubmission::with(['assignment.course', 'assignment.lesson', 'user'])->findOrFail($submissionId);
        $assignment = $submission->assignment;
        $course = $assignment->course;

        Gate::authorize('view', $submission);

        $assignmentTypeLabel = match ($assignment->type ?? 'file') {
            'essay' => 'Tự luận',
            'mixed' => 'File + tự luận',
            default => 'Nộp file',
        };

        $fileUrl = $this->submissionFiles->url($submission);
        $filePreviewUrl = $this->submissionFiles->previewUrl($submission);
        $filePreviewType = $this->submissionFiles->previewType($submission);
        $fileName = $submission->original_filename ?: ($submission->file_path ? basename($submission->file_path) : null);

        $canGrade = Gate::allows('grade', $submission);
        $gradeVisible = $canGrade || $submission->isGradePublished();
        $gradingQueue = collect();
        $queueStats = [
            'total' => 0,
            'submitted' => 0,
            'pending' => 0,
            'draft' => 0,
            'published' => 0,
        ];
        $previousSubmissionId = null;
        $nextSubmissionId = null;
        $feedbackTemplates = collect();
        $rubricCriteria = [];

        // Dữ liệu lớp và hàng đợi chấm là dữ liệu riêng của giáo viên/admin.
        // Học viên xem bài của mình không được tải dữ liệu của các bạn cùng lớp.
        if ($canGrade) {
            $students = $course->classes()
                ->where('classes.status', 'active')
                ->with('students')
                ->get()
                ->flatMap->students
                ->unique('id')
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
            $submissions = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->orderBy('submitted_at')
                ->get()
                ->keyBy('user_id');
            $gradingQueue = $students->map(function ($student) use ($submissions, $submission) {
                $studentSubmission = $submissions->get($student->id);

                return [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'student_code' => $student->student_code,
                    'submission_id' => $studentSubmission?->id,
                    'submitted_at' => $studentSubmission?->submitted_at,
                    'grade' => $studentSubmission?->grade,
                    'grading_status' => $studentSubmission?->grading_status,
                    'is_current' => $studentSubmission?->id === $submission->id,
                    'status' => ! $studentSubmission
                        ? 'missing'
                        : ($studentSubmission->grade === null
                            ? 'pending'
                            : ($studentSubmission->isGradePublished() ? 'published' : 'draft')),
                ];
            });
            $queueStats = [
                'total' => $gradingQueue->count(),
                'submitted' => $gradingQueue->whereNotNull('submission_id')->count(),
                'pending' => $gradingQueue->where('status', 'pending')->count(),
                'draft' => $gradingQueue->where('status', 'draft')->count(),
                'published' => $gradingQueue->where('status', 'published')->count(),
            ];

            $submittedIds = $gradingQueue->pluck('submission_id')->filter()->values();
            $currentPosition = $submittedIds->search($submission->id);
            if ($currentPosition !== false) {
                $previousSubmissionId = $submittedIds->get($currentPosition - 1);
                $nextSubmissionId = $submittedIds->get($currentPosition + 1);
            }

            $feedbackTemplates = GradingFeedbackTemplate::query()
                ->where('user_id', auth()->id())
                ->latest('updated_at')
                ->orderBy('title')
                ->get();
            $rubricCriteria = $this->rubricCriteria($assignment);
        }

        return view('assignments.submission_review', compact(
            'submission',
            'assignment',
            'course',
            'assignmentTypeLabel',
            'fileUrl',
            'filePreviewUrl',
            'filePreviewType',
            'fileName',
            'canGrade',
            'gradeVisible',
            'gradingQueue',
            'queueStats',
            'previousSubmissionId',
            'nextSubmissionId',
            'feedbackTemplates',
            'rubricCriteria',
        ));
    }

    // 1. Hàm lấy danh sách bài nộp cho Giáo viên (Dùng AJAX để load vào Modal)
    public function listSubmissions(Request $request, $id)
    {
        $assignment = Assignments::with('course')->notArchived()->findOrFail($id);
        Gate::authorize('update', $assignment);

        $studentsQuery = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->whereHas('classes.courses', fn ($query) => $query->where('courses.id', $assignment->course_id));
        $submittedCount = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->whereIn('user_id', (clone $studentsQuery)->select('users.id'))
            ->count();
        $students = $studentsQuery
            ->with(['submissions' => fn ($query) => $query
                ->where('assignment_id', $assignment->id)
                ->select([
                    'id', 'assignment_id', 'user_id', 'file_path', 'file_disk', 'text_answer',
                    'grade', 'feedback', 'submitted_at', 'created_at', 'updated_at',
                ])])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $data = $students->getCollection()->map(function ($student) {
            $submission = $student->submissions->first();

            return [
                'student_name' => $student->name,
                'student_code' => $student->student_code,
                'student_email' => $student->email,
                'submitted_at' => $submission ? $submission->formatSubmittedAt('d/m/Y H:i:s') : null,
                'file_url' => $this->submissionFiles->url($submission),
                'text_answer' => $submission ? $submission->text_answer : null,
                'grade' => $submission ? $submission->grade : null,
                'submission_id' => $submission ? $submission->id : null,
                'has_file' => (bool) ($submission?->file_path),
                'review_url' => $submission ? route('assignments.submissions.review', $submission->id) : null,
                'feedback' => $submission ? $submission->feedback : null,
            ];
        });

        return response()->json([
            'assignment_title' => $assignment->title,
            'course_title' => $assignment->course->title,
            'total_students' => $students->total(),
            'submitted_count' => $submittedCount,
            'download_url' => route('assignments.submissions.download', $assignment->id),
            'submissions' => $data->values(),
            'pagination' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'from' => $students->firstItem(),
                'to' => $students->lastItem(),
                'prev_page_url' => $students->previousPageUrl(),
                'next_page_url' => $students->nextPageUrl(),
            ],
        ]);
    }

    public function downloadSubmissionsArchive(Request $request, $id)
    {
        $assignment = Assignments::with('course')->notArchived()->findOrFail($id);
        Gate::authorize('update', $assignment);

        $validated = $request->validate([
            'mode' => 'required|in:all,ungraded,selected',
            'submission_ids' => 'nullable|array',
            'submission_ids.*' => 'integer',
        ]);

        $submissions = AssignmentSubmission::with('user')
            ->where('assignment_id', $assignment->id)
            ->when($validated['mode'] === 'ungraded', fn ($query) => $query->whereNull('grade'))
            ->when($validated['mode'] === 'selected', function ($query) use ($validated) {
                $query->whereIn('id', $validated['submission_ids'] ?? [-1]);
            })
            ->orderBy('submitted_at')
            ->get();

        if ($submissions->isEmpty()) {
            return back()->with('error', 'Không có bài nộp phù hợp để tải.');
        }

        return $this->submissionArchives->download($assignment, $submissions);
    }

    public function exportGrades($id)
    {
        $assignment = Assignments::with('course')->notArchived()->findOrFail($id);
        Gate::authorize('update', $assignment);
        $filename = 'diem-'.str($assignment->title)->slug()->limit(80, '')->toString().'.xlsx';

        return Excel::download(new AssignmentGradesExport($assignment), $filename);
    }

    public function importGrades(Request $request, $id)
    {
        $assignment = Assignments::with('course')->notArchived()->findOrFail($id);
        Gate::authorize('update', $assignment);
        $request->validate([
            'file' => ['required', 'file', 'max:5120', new SafeSpreadsheet],
        ]);

        $import = new AssignmentGradesImport($assignment);
        try {
            Excel::import($import, $request->file('file'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => 'Không thể đọc file điểm. Hãy dùng đúng file được xuất từ bài tập này.',
            ]);
        }

        foreach ($import->publishedSubmissions as $publishedSubmission) {
            $this->notifyPublishedGrade($publishedSubmission, $assignment->grading_scale ?? 10);
        }

        AuditLogger::log(
            AuditLogger::GRADES_IMPORTED,
            $assignment,
            null,
            ['updated_count' => $import->updatedCount],
            ['published_count' => count($import->publishedSubmissions)],
            'Giáo viên nhập điểm cho một bài tập từ bảng tính.'
        );

        return back()->with('success', "Đã nhập và cập nhật {$import->updatedCount} bài nộp.");
    }

    public function bulkGradeStatus(Request $request, $id)
    {
        $assignment = Assignments::with('course')->notArchived()->findOrFail($id);
        Gate::authorize('update', $assignment);
        $validated = $request->validate([
            'action' => 'required|in:publish,draft',
            'submission_ids' => 'required|array|min:1|max:500',
            'submission_ids.*' => 'required|integer|distinct',
        ]);

        $submissions = AssignmentSubmission::query()
            ->with('assignment.course')
            ->where('assignment_id', $assignment->id)
            ->whereIn('id', $validated['submission_ids'])
            ->get();

        if ($submissions->count() !== count($validated['submission_ids'])) {
            throw ValidationException::withMessages([
                'submission_ids' => 'Danh sách chứa bài nộp không thuộc bài tập hiện tại.',
            ]);
        }
        if ($submissions->contains(fn ($submission) => $submission->grade === null)) {
            throw ValidationException::withMessages([
                'submission_ids' => 'Chỉ có thể công bố hoặc thu hồi những bài đã có điểm.',
            ]);
        }

        $targetStatus = $validated['action'] === 'publish'
            ? AssignmentSubmission::GRADING_PUBLISHED
            : AssignmentSubmission::GRADING_DRAFT;
        $changed = $submissions->filter(fn ($submission) => $submission->grading_status !== $targetStatus);

        DB::transaction(function () use ($changed, $targetStatus): void {
            foreach ($changed as $submission) {
                $submission->update([
                    'grading_status' => $targetStatus,
                    'grade_published_at' => $targetStatus === AssignmentSubmission::GRADING_PUBLISHED ? now() : null,
                ]);
            }
        });

        if ($targetStatus === AssignmentSubmission::GRADING_PUBLISHED) {
            foreach ($changed as $submission) {
                $this->notifyPublishedGrade($submission, $assignment->grading_scale ?? 10);
            }
        }

        AuditLogger::log(
            AuditLogger::GRADES_BULK_STATUS_UPDATED,
            $assignment,
            null,
            ['grading_status' => $targetStatus],
            ['submission_ids' => $changed->pluck('id')->all(), 'count' => $changed->count()],
            $targetStatus === AssignmentSubmission::GRADING_PUBLISHED
                ? 'Giáo viên công bố điểm hàng loạt.'
                : 'Giáo viên thu hồi điểm hàng loạt về bản nháp.'
        );

        $message = $targetStatus === AssignmentSubmission::GRADING_PUBLISHED
            ? 'Đã công bố điểm cho '
            : 'Đã chuyển về nháp ';

        return back()->with('success', $message.$changed->count().' bài nộp.');
    }

    public function submit(Request $request, $id)
    {
        $assignment = Assignments::with('course.classes')->notArchived()->findOrFail($id);
        $user = auth()->user();
        Gate::authorize('submit', $assignment);

        $studentClassIds = $user->classes()->where('classes.status', 'active')->pluck('classes.id');
        $hasAccess = $assignment->course->classes
            ->where('status', 'active')
            ->pluck('id')
            ->intersect($studentClassIds)
            ->isNotEmpty();

        if (! $hasAccess || ! $assignment->course->isVisibleToStudents() || ! $assignment->isVisibleToStudents()) {
            return back()->withErrors(['Bài tập này chưa được mở cho học viên.']);
        }

        // 1. Lấy thông tin bài nộp cũ nếu có
        $oldSubmission = AssignmentSubmission::where('assignment_id', $id)->where('user_id', $user->id)->first();

        $this->ensureSubmissionCanBeChanged($assignment, $oldSubmission);

        // 2. Validate nội dung theo loại bài tập
        $allowed = AssignmentUploadTypes::safeExtensions($assignment->allowed_extensions);
        $maxSize = $assignment->max_file_size ?? 20480;
        $rules = [];

        if ($allowed === []) {
            throw ValidationException::withMessages([
                'file' => 'Bài tập chưa có định dạng tệp an toàn. Vui lòng liên hệ giáo viên.',
            ]);
        }

        $hasExistingFile = $oldSubmission && ! empty($oldSubmission->file_path);

        if (in_array($assignment->type, ['file', 'mixed'], true) && ! $hasExistingFile) {
            $rules['file'] = 'required|file|mimes:'.implode(',', $allowed)."|max:{$maxSize}";
        } else {
            $rules['file'] = 'nullable|file|mimes:'.implode(',', $allowed)."|max:{$maxSize}";
        }

        if (in_array($assignment->type, ['essay', 'mixed'], true)) {
            $rules['text_answer'] = 'required|string|min:10';
        } else {
            $rules['text_answer'] = 'nullable|string';
        }

        $request->validate($rules, [
            'file.mimes' => 'File không đúng định dạng yêu cầu. Chỉ chấp nhận: '.implode(', ', array_map(
                static fn (string $extension): string => '.'.strtoupper($extension),
                $allowed
            )).'.',
            'file.max' => 'File vượt quá dung lượng tối đa '.number_format($maxSize / 1024, 1).' MB.',
        ]);

        // 3. Upload và kiểm tra file mới trước; file cũ chỉ bị xóa sau khi DB cập nhật thành công.
        $filePath = $oldSubmission?->file_path;
        $fileDisk = $oldSubmission?->file_disk ?: config('filesystems.submission_disk', 'local');
        $originalFilename = $oldSubmission?->original_filename;
        $mimeType = $oldSubmission?->mime_type;
        $fileSize = $oldSubmission?->file_size;
        $checksum = $oldSubmission?->checksum_sha256;
        $newUpload = null;

        if ($request->hasFile('file')) {
            try {
                $newUpload = $this->submissionFiles->store($request->file('file'), $assignment->id, $user->id);
            } catch (\Throwable $exception) {
                report($exception);

                throw ValidationException::withMessages([
                    'file' => 'Không thể lưu file bài nộp vào kho dữ liệu. Vui lòng thử lại.',
                ]);
            }
            $filePath = $newUpload['path'];
            $fileDisk = $newUpload['disk'];
            $originalFilename = $newUpload['original_filename'];
            $mimeType = $newUpload['mime_type'];
            $fileSize = $newUpload['file_size'];
            $checksum = $newUpload['checksum_sha256'];
        } elseif ($assignment->type === 'essay') {
            $filePath = null;
            $fileDisk = config('filesystems.submission_disk', 'local');
            $originalFilename = null;
            $mimeType = null;
            $fileSize = null;
            $checksum = null;
        }

        // 4. Cập nhật hoặc tạo mới record trong Database
        $submissionData = [
            'file_path' => $filePath,
            'file_disk' => $filePath ? $fileDisk : config('filesystems.submission_disk', 'local'),
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'checksum_sha256' => $checksum,
            'text_answer' => $request->input('text_answer'),
            'submitted_at' => now(),
        ];

        // Một học viên có đúng một bản nộp hiện hành cho mỗi bài tập. Upsert dựa trên
        // unique key ở migration để hai request đồng thời không thể tạo hai record.
        try {
            DB::transaction(function () use ($id, $user, $submissionData): void {
                $lockedAssignment = Assignments::query()->lockForUpdate()->findOrFail($id);
                $lockedSubmission = AssignmentSubmission::query()
                    ->where('assignment_id', $id)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                // Kiểm tra lại bên trong transaction để việc chấm điểm hoặc deadline
                // thay đổi đồng thời không thể bị một request nộp bài ghi đè.
                $this->ensureSubmissionCanBeChanged($lockedAssignment, $lockedSubmission);

                AssignmentSubmission::query()->upsert(
                    [[
                        'assignment_id' => $id,
                        'user_id' => $user->id,
                        ...$submissionData,
                    ]],
                    ['assignment_id', 'user_id'],
                    array_keys($submissionData),
                );
            });
        } catch (\Throwable $exception) {
            if ($newUpload) {
                $this->submissionFiles->deletePath($newUpload['path'], $newUpload['disk']);
            }

            throw $exception;
        }

        if ($oldSubmission?->file_path
            && ($oldSubmission->file_path !== $filePath || $oldSubmission->file_disk !== $fileDisk)) {
            $this->submissionFiles->delete($oldSubmission);
        }

        return back()->with('success', 'Bạn đã cập nhật bài nộp thành công!');
    }

    // Học viên hủy bài đã nộp
    public function deleteSubmission($id)
    {
        DB::transaction(function () use ($id): void {
            $submission = AssignmentSubmission::with('assignment')->where('id', $id)
                ->where('user_id', auth()->id()) // Chỉ cho phép xóa bài của chính mình
                ->lockForUpdate()
                ->firstOrFail();

            // Kiểm tra trạng thái nghiệp vụ tại backend để request trực tiếp không thể bypass UI.
            if ($submission->grade !== null) {
                throw ValidationException::withMessages([
                    'submission' => 'Bài nộp đã được chấm điểm nên không thể hủy.',
                ]);
            }

            if ($submission->assignment->isSubmissionDeadlinePassed()) {
                throw ValidationException::withMessages([
                    'submission' => 'Bài tập đã quá hạn từ '.$submission->assignment->due_date->format('H:i d/m/Y').'. Bạn không thể hủy bài nộp.',
                ]);
            }

            Gate::authorize('delete', $submission);

            // Xóa file vật lý và record trong cùng vùng khóa để tránh bài bị chấm
            // sau khi đã kiểm tra nhưng trước lúc thao tác hủy hoàn tất.
            $this->submissionFiles->delete($submission);
            $submission->delete();
        });

        return back()->with('success', 'Đã hủy bài nộp thành công!');
    }

    // Hàm xử lý cập nhật bài tập (Sửa)
    public function update(Request $request, $id)
    {
        $assignment = Assignments::with('course')->findOrFail($id);
        Gate::authorize('update', $assignment);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'required|string',
            'grading_rubric' => 'nullable|string',
            'grading_scale' => 'nullable|integer|min:1|max:100',
            'ai_grading_enabled' => 'nullable|boolean',
            'due_date' => 'required|date',
            'lesson_id' => 'required|exists:lessons,id',
            'type' => 'nullable|in:file,essay,mixed',
            'status' => 'nullable|in:draft,published,hidden,archived',
            'available_from' => 'nullable|date',
            'allowed_extensions' => 'nullable|array|min:1|max:20',
            'allowed_extensions.*' => 'string|max:10',
            'max_file_size' => 'nullable|integer|min:1|max:20480',
        ]);

        if ($request->has('allowed_extensions')) {
            $validated['allowed_extensions'] = $this->normalizeAllowedExtensions($validated['allowed_extensions'] ?? null);
        }

        $lesson = Lesson::with('module')->findOrFail($validated['lesson_id']);
        abort_unless((int) $lesson->module?->course_id === (int) $assignment->course_id, 422, 'Bài học không thuộc khóa học của bài tập.');
        $wasPublished = $assignment->status === Assignments::STATUS_PUBLISHED;
        $oldDueDate = $assignment->due_date?->copy();

        $validated['status'] = $validated['status'] ?? $assignment->status;
        $validated['grading_scale'] = $validated['grading_scale'] ?? 10;
        $validated['ai_grading_enabled'] = $request->boolean('ai_grading_enabled');
        $validated['published_at'] = $validated['status'] === 'published' ? ($assignment->published_at ?? now()) : null;

        $assignment->update($validated);

        if (! $wasPublished && $assignment->status === Assignments::STATUS_PUBLISHED) {
            app(NotificationCenter::class)->notifyCourseStudents(
                $assignment->course_id, 'assignment', 'Có bài tập mới',
                "Bài tập \"{$assignment->title}\" vừa được đăng.",
                route('courses.show', ['course' => $assignment->course_id, 'assignment_id' => $assignment->id]), ['assignment_id' => $assignment->id],
                "assignment:{$assignment->id}:published"
            );
        } elseif ($wasPublished && $assignment->status === Assignments::STATUS_PUBLISHED && (! $oldDueDate || ! $oldDueDate->equalTo($assignment->due_date))) {
            app(NotificationCenter::class)->notifyCourseStudents(
                $assignment->course_id, 'assignment', 'Hạn nộp bài đã thay đổi',
                "Bài \"{$assignment->title}\" có hạn nộp mới: {$assignment->due_date->format('H:i d/m/Y')}.",
                route('courses.show', ['course' => $assignment->course_id, 'assignment_id' => $assignment->id]), ['assignment_id' => $assignment->id],
                "assignment:{$assignment->id}:due:{$assignment->due_date->timestamp}"
            );
        }

        return back()->with('success', 'Đã cập nhật bài tập thành công!');
    }

    // Hàm xử lý xóa bài tập (Xóa)
    public function destroy($id)
    {
        $assignment = Assignments::findOrFail($id);
        Gate::authorize('delete', $assignment);

        $assignment->update([
            'status' => Assignments::STATUS_ARCHIVED,
            'published_at' => null,
        ]);

        return back()->with('success', 'Đã lưu trữ bài tập. Bài nộp và điểm số vẫn được giữ lại.');
    }

    public function downloadSubmissionFile($id)
    {
        $submission = AssignmentSubmission::with(['assignment.course', 'user'])->findOrFail($id);

        Gate::authorize('view', $submission);

        return $this->submissionFiles->download($submission);
    }

    public function previewSubmissionFile($id)
    {
        $submission = AssignmentSubmission::with(['assignment.course', 'user'])->findOrFail($id);

        Gate::authorize('view', $submission);

        return $this->submissionFiles->preview($submission);
    }

    private function notifyPublishedGrade(AssignmentSubmission $submission, int|float $scale): void
    {
        $submission->loadMissing('assignment');
        $assignment = $submission->assignment;
        $score = rtrim(rtrim(number_format((float) $submission->grade, 2, '.', ''), '0'), '.');
        $maxScore = rtrim(rtrim(number_format((float) $scale, 2, '.', ''), '0'), '.');
        $message = "Bài \"{$assignment->title}\" đã được chấm {$score}/{$maxScore}.";

        if (filled($submission->feedback)) {
            $message .= ' Nhận xét: '.str($submission->feedback)->limit(180);
        }

        app(NotificationCenter::class)->notifyUser(
            $submission->user_id,
            'grade',
            'Điểm bài tập đã được công bố',
            $message,
            route('students.grades'),
            [
                'assignment_id' => $submission->assignment_id,
                'submission_id' => $submission->id,
                'grade' => (float) $submission->grade,
            ],
            'assignment-grade:'.$submission->id.':'.($submission->grade_published_at?->timestamp ?? now()->timestamp)
        );
    }

    /** @return array<int, array{key: int, label: string, max_score: float|null}> */
    private function rubricCriteria(Assignments $assignment): array
    {
        return collect(preg_split('/\R/u', (string) $assignment->grading_rubric) ?: [])
            ->map(fn (string $line) => trim(preg_replace('/^[\s\-*•\d.)]+/u', '', $line) ?? $line))
            ->filter()
            ->take(20)
            ->values()
            ->map(function (string $line, int $index): array {
                $maxScore = null;
                if (preg_match('/(?:[:\-(]|\/)\s*(\d+(?:[.,]\d+)?)\s*(?:điểm|pts?|\))?\s*$/iu', $line, $matches)) {
                    $maxScore = (float) str_replace(',', '.', $matches[1]);
                }

                return [
                    'key' => $index,
                    'label' => $line,
                    'max_score' => $maxScore !== null && $maxScore > 0 ? $maxScore : null,
                ];
            })
            ->all();
    }

    /** @return array<int, array{criterion: string, score: float, max_score: float|null}> */
    private function normalizeRubricScores(Assignments $assignment, array $submittedScores, float $grade): array
    {
        $criteria = $this->rubricCriteria($assignment);
        if ($criteria === [] || $submittedScores === []) {
            return [];
        }

        $normalized = [];
        foreach ($criteria as $index => $criterion) {
            $rawScore = data_get($submittedScores, "{$index}.score");
            if ($rawScore === null || $rawScore === '') {
                continue;
            }

            $score = (float) $rawScore;
            if ($criterion['max_score'] !== null && $score > $criterion['max_score']) {
                throw ValidationException::withMessages([
                    "rubric_scores.{$index}.score" => "Điểm tiêu chí không được vượt quá {$criterion['max_score']}.",
                ]);
            }

            $normalized[] = [
                'criterion' => $criterion['label'],
                'score' => $score,
                'max_score' => $criterion['max_score'],
            ];
        }

        if ($normalized !== []) {
            $total = collect($normalized)->sum('score');
            $scale = (float) ($assignment->grading_scale ?: 10);
            if ($total > $scale + 0.00001) {
                throw ValidationException::withMessages([
                    'rubric_scores' => "Tổng điểm rubric không được vượt quá thang điểm {$scale}.",
                ]);
            }
            if (abs($total - $grade) > 0.009) {
                throw ValidationException::withMessages([
                    'rubric_scores' => 'Tổng điểm rubric phải bằng điểm cuối cùng.',
                ]);
            }
        }

        return $normalized;
    }

    private function normalizeAllowedExtensions(string|array|null $extensions): string
    {
        try {
            return AssignmentUploadTypes::normalize($extensions);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'allowed_extensions' => $exception->getMessage(),
            ]);
        }
    }

    private function ensureSubmissionCanBeChanged(Assignments $assignment, ?AssignmentSubmission $submission): void
    {
        if ($submission?->grade !== null) {
            throw ValidationException::withMessages([
                'submission' => 'Bài nộp đã được chấm điểm nên không thể cập nhật.',
            ]);
        }

        if ($assignment->isSubmissionDeadlinePassed()) {
            throw ValidationException::withMessages([
                'submission' => 'Bài tập đã quá hạn từ '.$assignment->due_date->format('H:i d/m/Y').'. Bạn không thể nộp mới hoặc cập nhật bài làm.',
            ]);
        }
    }
}
