<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Assignments;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Support\Str;
use App\Services\NotificationCenter;

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
        $module = Module::with('course')->findOrFail($data['module_id']);
        $this->authorizeManageCourse($module->course);
        $data['status'] = $data['status'] ?? 'published';
        $data['published_at'] = $data['status'] === 'published' ? now() : null;
        $data['order'] = Lesson::where('module_id', $module->id)->notArchived()->count() + 1;

        if ($request->hasFile('attachment')) {
            $data = array_merge($data, $this->storeAttachment($request));
        }

        $lesson = Lesson::create($data);
        if ($lesson->status === Lesson::STATUS_PUBLISHED) {
            app(NotificationCenter::class)->notifyCourseStudents(
                $module->course,
                'lesson',
                'Có bài học mới',
                "Bài học \"{$lesson->title}\" vừa được đăng.",
                route('courses.show', $module->course_id),
                ['lesson_id' => $lesson->id],
                "lesson:{$lesson->id}:published"
            );
        }
        return back()->with('success', 'Đã thêm bài học thành công.');
    }

    // 2. Cập nhật hàm update (Sửa)
    public function update(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
        $wasPublished = $lesson->status === Lesson::STATUS_PUBLISHED;
        $this->authorizeManageCourse($lesson->module->course);

        $data = $request->validate([
            'title' => 'required|string',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'module_id' => 'required|exists:modules,id',
            'attachment' => 'nullable|file|max:20480',
            'status' => 'nullable|in:draft,published,hidden,archived',
            'available_from' => 'nullable|date',
        ]);
        $targetModule = Module::with('course')->findOrFail($data['module_id']);
        $this->authorizeManageCourse($targetModule->course);
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
        if (!$wasPublished && $lesson->status === Lesson::STATUS_PUBLISHED) {
            app(NotificationCenter::class)->notifyCourseStudents(
                $targetModule->course, 'lesson', 'Có bài học mới',
                "Bài học \"{$lesson->title}\" vừa được đăng.",
                route('courses.show', $targetModule->course_id), ['lesson_id' => $lesson->id],
                "lesson:{$lesson->id}:published"
            );
        }
        return back()->with('success', 'Đã cập nhật bài học.');
    }

    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        $this->authorizeManageCourse($lesson->module->course);
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

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:modules,id',
            'lesson_ids' => 'required|array',
            'lesson_ids.*' => 'integer|exists:lessons,id',
        ]);

        $module = Module::with('course')->findOrFail($validated['module_id']);
        $this->authorizeManageCourse($module->course);

        $lessonIds = array_values(array_unique($validated['lesson_ids']));
        $allowedCount = Lesson::where('module_id', $module->id)
            ->whereIn('id', $lessonIds)
            ->count();

        if ($allowedCount !== count($lessonIds)) {
            abort(422, 'Danh sách bài học không hợp lệ.');
        }

        foreach ($lessonIds as $index => $lessonId) {
            Lesson::where('module_id', $module->id)
                ->where('id', $lessonId)
                ->update(['order' => $index + 1]);
        }

        return response()->json(['message' => 'Đã cập nhật thứ tự bài học.']);
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

    private function authorizeManageCourse($course): void
    {
        $user = auth()->user();

        abort_unless(
            $course && $user && ($user->role === 'admin' || ($user->role === 'teacher' && (int) $course->teacher_id === (int) $user->id)),
            403
        );
    }
}
