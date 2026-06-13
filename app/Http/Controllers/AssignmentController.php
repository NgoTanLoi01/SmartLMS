<?php

namespace App\Http\Controllers;

use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        $data = $request->all();
        $data['grading_scale'] = $data['grading_scale'] ?? 10;
        $data['ai_grading_enabled'] = $request->boolean('ai_grading_enabled');
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        Assignments::create($data);
        return back()->with('success', 'Đã tạo bài tập thành công!');
    }

    // Giáo viên chấm điểm
    public function grade(Request $request, $submissionId)
    {
        $submission = AssignmentSubmission::with('assignment')->findOrFail($submissionId);
        $scale = $submission->assignment?->grading_scale ?? 10;

        $request->validate([
            'grade' => 'required|numeric|min:0|max:' . $scale,
            'feedback' => 'nullable|string',
        ]);

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
        ]);

        return back()->with('success', 'Đã lưu điểm và nhận xét!');
    }

    public function analyzeSubmissionWithAi($submissionId, DeepSeekService $deepSeekService)
    {
        $submission = AssignmentSubmission::with(['assignment.course', 'user'])->findOrFail($submissionId);
        $assignment = $submission->assignment;
        $course = $assignment->course;

        if (!$this->canManageAssignmentCourse($course)) {
            abort(403, 'Bạn không có quyền phân tích bài nộp này.');
        }

        if (!$assignment->ai_grading_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'AI hỗ trợ chấm đang tắt cho bài tập này.',
            ], 422);
        }

        if (!trim((string) $submission->text_answer)) {
            return response()->json([
                'success' => false,
                'message' => 'AI chỉ phân tích được bài có nội dung tự luận. Với bài chỉ nộp file, giáo viên cần xem file thủ công.',
            ], 422);
        }

        $payload = [
            'assignment' => [
                'title' => $assignment->title,
                'type' => $assignment->type ?? 'file',
                'instructions' => trim(strip_tags($assignment->instructions)),
                'grading_rubric' => trim((string) $assignment->grading_rubric),
                'grading_scale' => $assignment->grading_scale ?? 10,
                'ai_grading_enabled' => (bool) $assignment->ai_grading_enabled,
                'due_date' => $assignment->due_date?->format('d/m/Y H:i'),
                'course' => $course->title,
            ],
            'student' => [
                'name' => $submission->user?->name,
                'email' => $submission->user?->email,
                'submitted_at' => $submission->submitted_at?->format('d/m/Y H:i'),
            ],
            'submission' => [
                'text_answer' => $submission->text_answer,
                'has_file' => !empty($submission->file_path),
                'current_grade' => $submission->grade,
                'current_feedback' => $submission->feedback,
            ],
        ];

        $result = $deepSeekService->analyzeAssignmentSubmission($payload);

        if ($result['success']) {
            $analysis = $result['analysis'] ?? [];

            $submission->update([
                'ai_suggested_score' => $analysis['suggested_score'] ?? null,
                'ai_feedback' => $analysis['feedback'] ?? null,
                'ai_rubric_breakdown' => $analysis['rubric_breakdown'] ?? null,
                'ai_grading_notes' => $analysis['grading_notes'] ?? null,
                'ai_analyzed_at' => now(),
            ]);
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function reviewSubmission($submissionId)
    {
        $submission = AssignmentSubmission::with(['assignment.course', 'assignment.lesson', 'user'])->findOrFail($submissionId);
        $assignment = $submission->assignment;
        $course = $assignment->course;

        if (!$this->canManageAssignmentCourse($course)) {
            abort(403, 'Bạn không có quyền xem bài nộp này.');
        }

        $assignmentTypeLabel = match ($assignment->type ?? 'file') {
            'essay' => 'Tự luận',
            'mixed' => 'File + tự luận',
            default => 'Nộp file',
        };

        $fileUrl = $this->submissionFileUrl($submission);
        $filePreviewUrl = $this->submissionFilePreviewUrl($submission);
        $filePreviewType = $this->submissionPreviewType($submission);
        $fileName = $submission->original_filename ?: ($submission->file_path ? basename($submission->file_path) : null);

        return view('assignments.submission_review', compact(
            'submission',
            'assignment',
            'course',
            'assignmentTypeLabel',
            'fileUrl',
            'filePreviewUrl',
            'filePreviewType',
            'fileName',
        ));
    }
    // 1. Hàm lấy danh sách bài nộp cho Giáo viên (Dùng AJAX để load vào Modal)
    public function listSubmissions($id)
    {
        $assignment = Assignments::with('course.classes.students')->notArchived()->findOrFail($id);

        // Lấy danh sách ID học sinh thuộc các lớp có gán khóa học này
        $students = $assignment->course->classes->flatMap->students->unique('id');

        // Lấy danh sách các bài đã nộp cho assignment này
        $submissions = AssignmentSubmission::where('assignment_id', $id)->get()->keyBy('user_id');

        // Kết hợp dữ liệu: Học sinh + Bài nộp (nếu có)
        $data = $students->map(function ($student) use ($submissions) {
            $submission = $submissions->get($student->id);
            return [
                'student_name' => $student->name,
                'student_email' => $student->email,
                'submitted_at' => $submission ? $submission->submitted_at->format('d/m/Y H:i') : null,
                'file_url' => $submission ? $this->submissionFileUrl($submission) : null,
                'text_answer' => $submission ? $submission->text_answer : null,
                'grade' => $submission ? $submission->grade : null,
                'submission_id' => $submission ? $submission->id : null,
                'feedback' => $submission ? $submission->feedback : null,
            ];
        });

        return response()->json([
            'assignment_title' => $assignment->title,
            'submissions' => $data,
        ]);
    }

    public function submit(Request $request, $id)
    {
        $assignment = Assignments::with('course.classes')->notArchived()->findOrFail($id);
        $user = auth()->user();

        $studentClassIds = $user->classes()->where('classes.status', 'active')->pluck('classes.id');
        $hasAccess = $assignment->course->classes
            ->where('status', 'active')
            ->pluck('id')
            ->intersect($studentClassIds)
            ->isNotEmpty();

        if (!$hasAccess || !$assignment->course->isVisibleToStudents() || !$assignment->isVisibleToStudents()) {
            return back()->withErrors(['Bài tập này chưa được mở cho học sinh.']);
        }

        // 1. Lấy thông tin bài nộp cũ nếu có
        $oldSubmission = AssignmentSubmission::where('assignment_id', $id)->where('user_id', $user->id)->first();

        // 2. Validate nội dung theo loại bài tập
        $allowed = $assignment->allowed_extensions ?? 'pdf,docx,zip,png,jpg,jpeg,html,htm';
        $maxSize = $assignment->max_file_size ?? 10240;
        $rules = [];

        $hasExistingFile = $oldSubmission && !empty($oldSubmission->file_path);

        if (in_array($assignment->type, ['file', 'mixed'], true) && !$hasExistingFile) {
            $rules['file'] = 'required|file|mimes:' . str_replace(' ', '', $allowed) . "|max:{$maxSize}";
        } else {
            $rules['file'] = 'nullable|file|mimes:' . str_replace(' ', '', $allowed) . "|max:{$maxSize}";
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
            $storedName = $safeName . '-' . now()->format('YmdHis') . '-' . Str::random(8) . ($extension ? '.' . $extension : '');
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

        // SỬA CHỖ NÀY
        $assignment = Assignments::findOrFail($id);

        $validated['status'] = $validated['status'] ?? $assignment->status;
        $validated['grading_scale'] = $validated['grading_scale'] ?? 10;
        $validated['ai_grading_enabled'] = $request->boolean('ai_grading_enabled');
        $validated['published_at'] = $validated['status'] === 'published' ? ($assignment->published_at ?? now()) : null;

        $assignment->update($validated);

        return back()->with('success', 'Đã cập nhật bài tập thành công!');
    }

    // Hàm xử lý xóa bài tập (Xóa)
    public function destroy($id)
    {
        $assignment = Assignments::findOrFail($id);

        $assignment->update([
            'status' => Assignments::STATUS_ARCHIVED,
            'published_at' => null,
        ]);

        return back()->with('success', 'Đã lưu trữ bài tập. Bài nộp và điểm số vẫn được giữ lại.');
    }

    private function canManageAssignmentCourse(Course $course): bool
    {
        $user = auth()->user();

        return $user->role === 'admin' || ($user->role === 'teacher' && $course->teacher_id === $user->id);
    }

    public function downloadSubmissionFile($id)
    {
        $submission = AssignmentSubmission::with(['assignment.course', 'user'])->findOrFail($id);

        if (!$this->canViewSubmission($submission)) {
            abort(403, 'Bạn không có quyền tải bài nộp này.');
        }

        if (!$submission->file_path) {
            abort(404, 'Bài nộp không có file đính kèm.');
        }

        $diskName = $this->submissionDisk($submission);
        $disk = Storage::disk($diskName);

        if (!$disk->exists($submission->file_path)) {
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

        if (!$this->canViewSubmission($submission)) {
            abort(403, 'Bạn không có quyền xem bài nộp này.');
        }

        if (!$submission->file_path || !$this->submissionPreviewType($submission)) {
            abort(404, 'File này không hỗ trợ xem trước.');
        }

        $disk = Storage::disk($this->submissionDisk($submission));
        if (!$disk->exists($submission->file_path)) {
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
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
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
        if (!$submission || !$submission->file_path) {
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
        if (!$submission->file_path) {
            return;
        }

        $disk = Storage::disk($this->submissionDisk($submission));
        if ($disk->exists($submission->file_path)) {
            $disk->delete($submission->file_path);
        }
    }

    private function canViewSubmission(AssignmentSubmission $submission): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if ($user->role === 'admin' || $submission->user_id === $user->id) {
            return true;
        }

        return $user->role === 'teacher'
            && $submission->assignment?->course?->teacher_id === $user->id;
    }
}
