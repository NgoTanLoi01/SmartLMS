<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Assignments;
use App\Models\Lesson;
use Illuminate\Support\Str;

class LessonController extends Controller
{
    // 1. Cập nhật hàm store (Thêm mới)
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'module_id' => 'required|exists:modules,id',
            'attachment' => 'nullable|file|max:20480', // Max 20MB
            'status' => 'nullable|in:draft,published,hidden,archived',
            'available_from' => 'nullable|date',
        ]);
        $data['status'] = $data['status'] ?? 'published';
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        if ($request->hasFile('attachment')) {
            $data = array_merge($data, $this->storeAttachment($request));
        }

        Lesson::create($data);
        return back()->with('success', 'Đã thêm bài học thành công.');
    }

    // 2. Cập nhật hàm update (Sửa)
    public function update(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'module_id' => 'required|exists:modules,id',
            'attachment' => 'nullable|file|max:20480',
            'status' => 'nullable|in:draft,published,hidden,archived',
            'available_from' => 'nullable|date',
        ]);
        $data['status'] = $data['status'] ?? $lesson->status;
        $data['published_at'] = $data['status'] === 'published' ? ($lesson->published_at ?? now()) : null;

        if ($request->hasFile('attachment')) {
            $this->deleteAttachment($lesson);
            $data = array_merge($data, $this->storeAttachment($request));
        } else {
            // ✅ Quan trọng: Không có file mới thì giữ nguyên file cũ
            unset($data['attachment']);
        }

        $lesson->update($data);
        return back()->with('success', 'Đã cập nhật bài học.');
    }

    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->update([
            'status' => Lesson::STATUS_ARCHIVED,
            'published_at' => null,
        ]);
        Assignments::where('lesson_id', $lesson->id)->update([
            'status' => Assignments::STATUS_ARCHIVED,
            'published_at' => null,
        ]);

        return back()->with('success', 'Đã lưu trữ bài học. File bài giảng, bài tập và dữ liệu học tập vẫn được giữ lại.');
    }

    public function downloadAttachment($id)
    {
        $lesson = Lesson::with('module.course.classes')->findOrFail($id);

        if (!$lesson->attachment || !$this->canViewLesson($lesson)) {
            abort(404);
        }

        $disk = Storage::disk($lesson->attachment_disk ?: 'public');
        if (!$disk->exists($lesson->attachment)) {
            abort(404, 'Không tìm thấy file bài giảng.');
        }

        return $disk->download(
            $lesson->attachment,
            $lesson->attachment_original_name ?: basename($lesson->attachment)
        );
    }

    public function toggleComplete($id)
    {
        $user = auth()->user();
        $lesson = Lesson::with('module.course.classes')->findOrFail($id);

        if ($user->role === 'student') {
            $studentClassIds = $user->classes()->where('classes.status', 'active')->pluck('classes.id');
            $hasAccess = $lesson->module->course->classes
                ->where('status', 'active')
                ->pluck('id')
                ->intersect($studentClassIds)
                ->isNotEmpty();

            if (!$hasAccess || !$lesson->module->course->isVisibleToStudents() || !$lesson->isVisibleToStudents()) {
                return response()->json(['message' => 'Bài học này chưa được mở.'], 403);
            }
        }

        // Cập nhật cột completed_at bằng thời gian hiện tại
        $user->lessons()->syncWithoutDetaching([
            $id => ['completed_at' => now()],
        ]);

        return response()->json(['message' => 'Đã đánh dấu hoàn thành bài học!']);
    }

    private function storeAttachment(Request $request): array
    {
        $file = $request->file('attachment');
        $disk = config('filesystems.lesson_attachment_disk', 'public');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'lesson-attachment';
        $storedName = $safeName . '-' . now()->format('YmdHis') . '-' . Str::random(8) . ($extension ? '.' . $extension : '');

        return [
            'attachment' => $file->storeAs('lessons/attachments', $storedName, $disk),
            'attachment_disk' => $disk,
            'attachment_original_name' => $originalName,
            'attachment_mime_type' => $file->getClientMimeType(),
            'attachment_size' => $file->getSize(),
        ];
    }

    private function deleteAttachment(Lesson $lesson): void
    {
        if ($lesson->attachment) {
            Storage::disk($lesson->attachment_disk ?: 'public')->delete($lesson->attachment);
        }
    }

    private function canViewLesson(Lesson $lesson): bool
    {
        $user = auth()->user();
        $course = $lesson->module?->course;

        if (!$user || !$course) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'teacher') {
            return (int) $course->teacher_id === (int) $user->id;
        }

        if ($user->role === 'student') {
            $studentClassIds = $user->classes()->where('classes.status', 'active')->pluck('classes.id');

            return $course->isVisibleToStudents()
                && $lesson->isVisibleToStudents()
                && $course->classes
                    ->where('status', 'active')
                    ->pluck('id')
                    ->intersect($studentClassIds)
                    ->isNotEmpty();
        }

        return false;
    }
}
