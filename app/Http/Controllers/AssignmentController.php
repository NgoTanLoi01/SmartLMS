<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeAssignmentSubmission;
use App\Models\AiOperation;
use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\AuditLogger;
use App\Services\NotificationCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipStream\ZipStream;

class AssignmentController extends Controller
{
    // Hiển thị danh sách
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $assignments = Assignments::with('course')
                ->notArchived()
                ->whereHas('course', fn ($query) => $query->notArchived())
                ->latest()
                ->get();
        } elseif ($user->role === 'teacher') {
            $courseIds = Course::where('teacher_id', $user->id)->notArchived()->pluck('id');
            $assignments = Assignments::with('course')->notArchived()->whereIn('course_id', $courseIds)->latest()->get();
        } else {
            // Học sinh: Chỉ lấy bài tập trạng thái 'published' và thuộc lớp đang học
            $classIds = $user->classes()->where('classes.status', 'active')->pluck('classes.id');
            $courseIds = Course::visibleToStudents()
                ->whereHas('classes', function ($q) use ($classIds) {
                    $q->where('classes.status', 'active')->whereIn('classes.id', $classIds);
                })
                ->pluck('id');

            $assignments = Assignments::with([
                'course',
                'submissions' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                },
            ])
                ->whereIn('course_id', $courseIds)
                ->visibleToStudents()
                ->latest()
                ->get();
        }

        if ($user->role === 'teacher') {
            $courses = Course::with('modules.lessons')->where('teacher_id', $user->id)->notArchived()->get();
        } else {
            $courses = Course::with('modules.lessons')->notArchived()->get();
        }

        return view('assignments.index', compact('assignments', 'courses'));
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
            'allowed_extensions' => 'nullable|string',
            'max_file_size' => 'nullable|integer',
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
                route('courses.show', $assignment->course_id),
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
        $oldValues = AuditLogger::snapshot($submission, ['grade', 'feedback']);

        $request->validate([
            'grade' => 'required|numeric|min:0|max:'.$scale,
            'feedback' => 'nullable|string',
            'action' => 'nullable|in:save,save_next',
        ]);

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
        ]);

        app(NotificationCenter::class)->notifyUser(
            $submission->user_id,
            'grade',
            'Bài tập đã được chấm',
            "Bài \"{$submission->assignment->title}\" đã có điểm {$submission->grade}/{$scale}".($submission->feedback ? ' và nhận xét mới.' : '.'),
            route('students.grades'),
            ['assignment_id' => $submission->assignment_id, 'submission_id' => $submission->id],
            "grade:submission:{$submission->id}:".md5($submission->updated_at.'|'.$submission->grade.'|'.$submission->feedback)
        );

        AuditLogger::log(
            AuditLogger::GRADE_UPDATED,
            $submission,
            $oldValues,
            AuditLogger::snapshot($submission->fresh(), ['grade', 'feedback']),
            [
                'assignment_id' => $submission->assignment_id,
                'assignment_title' => $submission->assignment?->title,
                'student_id' => $submission->user_id,
            ],
            'Giáo viên cập nhật điểm và nhận xét bài nộp.'
        );

        if ($request->input('action') === 'save_next') {
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
                    ->with('success', 'Đã lưu. Đang chuyển sang bài chưa chấm tiếp theo.');
            }

            return redirect()
                ->route('assignments.submissions.review', $submission)
                ->with('success', 'Đã lưu điểm. Không còn bài nộp nào chờ chấm.');
        }

        return back()->with('success', 'Đã lưu điểm và nhận xét!');
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

        $fileUrl = $this->submissionFileUrl($submission);
        $filePreviewUrl = $this->submissionFilePreviewUrl($submission);
        $filePreviewType = $this->submissionPreviewType($submission);
        $fileName = $submission->original_filename ?: ($submission->file_path ? basename($submission->file_path) : null);

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
                'is_current' => $studentSubmission?->id === $submission->id,
                'status' => ! $studentSubmission ? 'missing' : ($studentSubmission->grade === null ? 'pending' : 'graded'),
            ];
        });
        $queueStats = [
            'total' => $gradingQueue->count(),
            'submitted' => $gradingQueue->whereNotNull('submission_id')->count(),
            'pending' => $gradingQueue->where('status', 'pending')->count(),
            'graded' => $gradingQueue->where('status', 'graded')->count(),
        ];

        return view('assignments.submission_review', compact(
            'submission',
            'assignment',
            'course',
            'assignmentTypeLabel',
            'fileUrl',
            'filePreviewUrl',
            'filePreviewType',
            'fileName',
            'gradingQueue',
            'queueStats',
        ));
    }

    // 1. Hàm lấy danh sách bài nộp cho Giáo viên (Dùng AJAX để load vào Modal)
    public function listSubmissions($id)
    {
        $assignment = Assignments::with('course.classes.students')->notArchived()->findOrFail($id);
        Gate::authorize('update', $assignment);

        // Lấy danh sách ID học sinh thuộc các lớp có gán khóa học này
        $students = $assignment->course->classes->flatMap->students->unique('id');

        // Lấy danh sách các bài đã nộp cho assignment này
        $submissions = AssignmentSubmission::where('assignment_id', $id)->get()->keyBy('user_id');

        // Kết hợp dữ liệu: Học sinh + Bài nộp (nếu có)
        $data = $students->map(function ($student) use ($submissions) {
            $submission = $submissions->get($student->id);

            return [
                'student_name' => $student->name,
                'student_code' => $student->student_code,
                'student_email' => $student->email,
                'submitted_at' => $submission ? $submission->formatSubmittedAt('d/m/Y H:i:s') : null,
                'file_url' => $submission ? $this->submissionFileUrl($submission) : null,
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
            'total_students' => $students->count(),
            'submitted_count' => $data->filter(fn ($row) => ! empty($row['submission_id']))->count(),
            'download_url' => route('assignments.submissions.download', $assignment->id),
            'submissions' => $data,
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

        $archiveName = 'Bai_nop_'.Str::slug($assignment->title, '_').'_'.now()->format('Y-m-d_His').'.zip';

        return response()->streamDownload(function () use ($submissions, $assignment) {
            $zip = new ZipStream(outputStream: fopen('php://output', 'wb'), sendHttpHeaders: false);
            $usedNames = [];
            $csv = fopen('php://temp', 'w+b');
            fwrite($csv, "\xEF\xBB\xBF");
            fputcsv($csv, ['Mã học sinh', 'Họ và tên', 'Email', 'Thời gian nộp', 'Trạng thái', 'Tên file', 'Điểm', 'Nhận xét'], ',', '"', '');

            foreach ($submissions as $submission) {
                $student = $submission->user;
                $isLate = $assignment->due_date && $submission->submitted_at?->gt($assignment->due_date);
                $archiveFileName = '';

                if ($submission->file_path) {
                    $disk = Storage::disk($this->submissionDisk($submission));
                    if ($disk->exists($submission->file_path)) {
                        $originalName = $submission->original_filename ?: basename($submission->file_path);
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                        $studentName = Str::slug($student?->name ?: 'hoc_sinh', '_');
                        $archiveFileName = $studentName.($extension ? '.'.strtolower($extension) : '');
                        $archiveFileName = $this->uniqueArchiveName($archiveFileName, $usedNames);
                        $stream = $disk->readStream($submission->file_path);

                        if (is_resource($stream)) {
                            $zip->addFileFromStream(fileName: 'files/'.$archiveFileName, stream: $stream);
                            fclose($stream);
                        } else {
                            $archiveFileName = '';
                        }
                    }
                }

                fputcsv($csv, [
                    $student?->student_code,
                    $student?->name,
                    $student?->email,
                    $submission->formatSubmittedAt('d/m/Y H:i:s'),
                    $isLate ? 'Nộp muộn' : 'Đúng hạn',
                    $archiveFileName ?: ($submission->file_path ? 'Không tìm thấy file' : 'Chỉ nộp nội dung tự luận'),
                    $submission->grade,
                    $submission->feedback,
                ], ',', '"', '');
            }

            rewind($csv);
            $zip->addFileFromStream(fileName: 'Danh_sach_bai_nop.csv', stream: $csv);
            fclose($csv);
            $zip->finish();
        }, $archiveName, ['Content-Type' => 'application/zip']);
    }

    private function uniqueArchiveName(string $name, array &$usedNames): string
    {
        $candidate = $name;
        $counter = 2;
        while (isset($usedNames[Str::lower($candidate)])) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $stem = pathinfo($name, PATHINFO_FILENAME);
            $candidate = $stem.'_'.$counter++.($extension ? '.'.$extension : '');
        }

        $usedNames[Str::lower($candidate)] = true;

        return $candidate;
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
            return back()->withErrors(['Bài tập này chưa được mở cho học sinh.']);
        }

        // 1. Lấy thông tin bài nộp cũ nếu có
        $oldSubmission = AssignmentSubmission::where('assignment_id', $id)->where('user_id', $user->id)->first();

        // 2. Validate nội dung theo loại bài tập
        $allowed = $assignment->allowed_extensions ?? 'pdf,docx,txt,md,html,htm,css,js,php,png,jpg,jpeg';
        $maxSize = $assignment->max_file_size ?? 10240;
        $rules = [];

        $hasExistingFile = $oldSubmission && ! empty($oldSubmission->file_path);

        if (in_array($assignment->type, ['file', 'mixed'], true) && ! $hasExistingFile) {
            $rules['file'] = 'required|file|mimes:'.str_replace(' ', '', $allowed)."|max:{$maxSize}";
        } else {
            $rules['file'] = 'nullable|file|mimes:'.str_replace(' ', '', $allowed)."|max:{$maxSize}";
        }

        if (in_array($assignment->type, ['essay', 'mixed'], true)) {
            $rules['text_answer'] = 'required|string|min:10';
        } else {
            $rules['text_answer'] = 'nullable|string';
        }

        $request->validate($rules);

        // 3. Nếu đã có bài nộp cũ, thực hiện xóa file cũ trước khi lưu file mới
        if ($request->hasFile('file') && $oldSubmission && $oldSubmission->file_path) {
            $this->deleteSubmissionFile($oldSubmission);
        }

        // 4. Lưu file mới vào folder assignments
        $filePath = $oldSubmission?->file_path;
        $fileDisk = $oldSubmission?->file_disk ?: 'public';
        $originalFilename = $oldSubmission?->original_filename;
        $mimeType = $oldSubmission?->mime_type;
        $fileSize = $oldSubmission?->file_size;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileDisk = config('filesystems.submission_disk', env('SUBMISSION_FILESYSTEM_DISK', 'public'));
            $originalFilename = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();
            $extension = $file->getClientOriginalExtension();
            $safeName = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) ?: 'submission';
            $storedName = $safeName.'-'.now()->format('YmdHis').'-'.Str::random(8).($extension ? '.'.$extension : '');
            $filePath = $file->storeAs("assignments/{$assignment->id}/students/{$user->id}", $storedName, $fileDisk);
        } elseif ($assignment->type === 'essay') {
            $filePath = null;
            $fileDisk = 'public';
            $originalFilename = null;
            $mimeType = null;
            $fileSize = null;
        }

        // 5. Cập nhật hoặc tạo mới record trong Database
        AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $id, 'user_id' => $user->id],
            [
                'file_path' => $filePath,
                'file_disk' => $filePath ? $fileDisk : 'public',
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'text_answer' => $request->input('text_answer'),
                'submitted_at' => now(),
            ],
        );

        return back()->with('success', 'Bạn đã cập nhật bài nộp thành công!');
    }

    // Học sinh hủy bài đã nộp
    public function deleteSubmission($id)
    {
        $submission = AssignmentSubmission::where('id', $id)
            ->where('user_id', auth()->id()) // Chỉ cho phép xóa bài của chính mình
            ->firstOrFail();
        Gate::authorize('delete', $submission);

        // Không cho phép xóa nếu đã có điểm
        if ($submission->grade !== null) {
            return back()->withErrors(['Không thể hủy bài nộp vì giáo viên đã chấm điểm!']);
        }

        // Xóa file vật lý trong storage
        $this->deleteSubmissionFile($submission);

        // Xóa record trong DB
        $submission->delete();

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
        ]);

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
                route('courses.show', $assignment->course_id), ['assignment_id' => $assignment->id],
                "assignment:{$assignment->id}:published"
            );
        } elseif ($wasPublished && $assignment->status === Assignments::STATUS_PUBLISHED && (! $oldDueDate || ! $oldDueDate->equalTo($assignment->due_date))) {
            app(NotificationCenter::class)->notifyCourseStudents(
                $assignment->course_id, 'assignment', 'Hạn nộp bài đã thay đổi',
                "Bài \"{$assignment->title}\" có hạn nộp mới: {$assignment->due_date->format('H:i d/m/Y')}.",
                route('courses.show', $assignment->course_id), ['assignment_id' => $assignment->id],
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

        if (! $submission->file_path) {
            abort(404, 'Bài nộp không có file đính kèm.');
        }

        $diskName = $this->submissionDisk($submission);
        $disk = Storage::disk($diskName);

        if (! $disk->exists($submission->file_path)) {
            abort(404, 'Không tìm thấy file bài nộp.');
        }

        return $disk->download(
            $submission->file_path,
            $submission->original_filename ?: basename($submission->file_path)
        );
    }

    public function previewSubmissionFile($id)
    {
        $submission = AssignmentSubmission::with(['assignment.course', 'user'])->findOrFail($id);

        Gate::authorize('view', $submission);

        if (! $submission->file_path || ! $this->submissionPreviewType($submission)) {
            abort(404, 'File này không hỗ trợ xem trước.');
        }

        $disk = Storage::disk($this->submissionDisk($submission));
        if (! $disk->exists($submission->file_path)) {
            abort(404, 'Không tìm thấy file bài nộp.');
        }

        $stream = $disk->readStream($submission->file_path);
        if ($stream === false) {
            abort(404, 'Không thể đọc file bài nộp.');
        }

        $fileName = str_replace(["\r", "\n", '"'], ['', '', "'"], $submission->original_filename ?: basename($submission->file_path));

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $this->previewContentType($submission),
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function submissionFileUrl(?AssignmentSubmission $submission): ?string
    {
        return $submission && $submission->file_path
            ? route('assignments.submissions.file', $submission->id)
            : null;
    }

    private function submissionFilePreviewUrl(?AssignmentSubmission $submission): ?string
    {
        return $submission && $submission->file_path && $this->submissionPreviewType($submission)
            ? route('assignments.submissions.preview', $submission->id)
            : null;
    }

    private function submissionPreviewType(?AssignmentSubmission $submission): ?string
    {
        if (! $submission || ! $submission->file_path) {
            return null;
        }

        $extension = strtolower(pathinfo($submission->original_filename ?: $submission->file_path, PATHINFO_EXTENSION));
        $mimeType = strtolower((string) $submission->mime_type);

        return match (true) {
            $extension === 'pdf' || $mimeType === 'application/pdf' => 'pdf',
            in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true) || str_starts_with($mimeType, 'image/') => 'image',
            in_array($extension, ['html', 'htm'], true) || in_array($mimeType, ['text/html', 'application/xhtml+xml'], true) => 'html',
            default => null,
        };
    }

    private function previewContentType(AssignmentSubmission $submission): string
    {
        return match ($this->submissionPreviewType($submission)) {
            'pdf' => 'application/pdf',
            'image' => $submission->mime_type ?: $this->imageContentTypeFromExtension($submission),
            'html' => 'text/html; charset=UTF-8',
            default => 'application/octet-stream',
        };
    }

    private function imageContentTypeFromExtension(AssignmentSubmission $submission): string
    {
        return match (strtolower(pathinfo($submission->original_filename ?: $submission->file_path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    private function submissionDisk(AssignmentSubmission $submission): string
    {
        return $submission->file_disk ?: 'public';
    }

    private function deleteSubmissionFile(AssignmentSubmission $submission): void
    {
        if (! $submission->file_path) {
            return;
        }

        $disk = Storage::disk($this->submissionDisk($submission));
        if ($disk->exists($submission->file_path)) {
            $disk->delete($submission->file_path);
        }
    }
}
