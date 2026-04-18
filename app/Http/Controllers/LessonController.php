<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'module_id' => 'required|exists:modules,id',
            'title' => 'required',
            'content' => 'required',
        ]);

        \App\Models\Lesson::create([
            'module_id' => $request->module_id,
            'title' => $request->title,
            'content' => $request->content,
            'video_url' => $request->video_url,
            'order' => \App\Models\Lesson::where('module_id', $request->module_id)->count() + 1,
        ]);

        return back()->with('success', 'Đã thêm bài học mới!');
    }
    public function update(Request $request, $id)
    {
        $lesson = \App\Models\Lesson::findOrFail($id);
        $data = $request->validate([
            'title' => 'required|max:255',
            'content' => 'nullable',
            'video_url' => 'nullable|url',
        ]);
        $lesson->update($data);
        return back()->with('success', 'Đã cập nhật bài học!');
    }

    public function destroy($id)
    {
        \App\Models\Lesson::findOrFail($id)->delete();
        return back()->with('success', 'Đã xóa bài học!');
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
