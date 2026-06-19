<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Assignments;
use App\Models\Lesson;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|max:255',
        ]);

        Module::create([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'order' => Module::where('course_id', $request->course_id)->notArchived()->count() + 1,
            'status' => Module::STATUS_PUBLISHED,
        ]);

        return back()->with('success', 'Đã thêm chương mới thành công!');
    }
    public function update(Request $request, $id)
    {
        $module = Module::findOrFail($id);
        $module->update($request->validate(['title' => 'required|max:255']));
        return back()->with('success', 'Đã cập nhật chương!');
    }

    public function destroy($id)
    {
        $module = Module::with('lessons')->findOrFail($id);
        $lessonIds = Lesson::where('module_id', $module->id)->pluck('id');

        $module->update(['status' => Module::STATUS_ARCHIVED]);
        Lesson::whereIn('id', $lessonIds)->update([
            'status' => Lesson::STATUS_ARCHIVED,
            'published_at' => null,
        ]);
        Assignments::whereIn('lesson_id', $lessonIds)->update([
            'status' => Assignments::STATUS_ARCHIVED,
            'published_at' => null,
        ]);

        return back()->with('success', 'Đã lưu trữ chương. Bài học và bài tập liên quan vẫn được giữ lại.');
    }
}
