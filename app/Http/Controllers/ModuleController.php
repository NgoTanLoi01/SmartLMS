<?php

namespace App\Http\Controllers;

use App\Models\Assignments;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ModuleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|max:255',
        ]);
        $course = Course::findOrFail($request->course_id);
        Gate::authorize('create', [Module::class, $course]);

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
        Gate::authorize('update', $module);
        $module->update($request->validate(['title' => 'required|max:255']));

        return back()->with('success', 'Đã cập nhật chương!');
    }

    public function destroy($id)
    {
        $module = Module::with('lessons')->findOrFail($id);
        Gate::authorize('delete', $module);
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

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'module_ids' => 'required|array|min:1',
            'module_ids.*' => 'integer|exists:modules,id',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        Gate::authorize('create', [Module::class, $course]);

        $allowedIds = Module::where('course_id', $course->id)
            ->whereIn('id', $validated['module_ids'])
            ->pluck('id')
            ->all();

        if (count($allowedIds) !== count(array_unique($validated['module_ids']))) {
            abort(422, 'Danh sách chương không hợp lệ.');
        }

        foreach (array_values($validated['module_ids']) as $index => $moduleId) {
            Module::where('course_id', $course->id)
                ->where('id', $moduleId)
                ->update(['order' => $index + 1]);
        }

        return response()->json(['message' => 'Đã cập nhật thứ tự chương.']);
    }
}
