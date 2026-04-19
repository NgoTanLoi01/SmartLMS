<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Lesson;
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
        ]);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('lessons/attachments', 'public');
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
        ]);

        if ($request->hasFile('attachment')) {
            // Xóa file cũ nếu có
            if ($lesson->attachment) {
                Storage::disk('public')->delete($lesson->attachment);
            }
            $data['attachment'] = $request->file('attachment')->store('lessons/attachments', 'public');
        } else {
            // ✅ Quan trọng: Không có file mới thì giữ nguyên file cũ
            unset($data['attachment']);
        }

        $lesson->update($data);
        return back()->with('success', 'Đã cập nhật bài học.');
    }

    // 3. (Tùy chọn) Cập nhật hàm destroy để xóa file khi xóa bài học
    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        if ($lesson->attachment) {
            Storage::disk('public')->delete($lesson->attachment);
        }
        $lesson->delete();
        return back()->with('success', 'Đã xóa bài học.');
    }

    public function toggleComplete($id)
    {
        $user = auth()->user();

        // Cập nhật cột completed_at bằng thời gian hiện tại
        $user->lessons()->syncWithoutDetaching([
            $id => ['completed_at' => now()],
        ]);

        return response()->json(['message' => 'Đã đánh dấu hoàn thành bài học!']);
    }
}
