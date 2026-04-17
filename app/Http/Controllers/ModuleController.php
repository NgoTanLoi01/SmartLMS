<?php

namespace App\Http\Controllers;

use App\Models\Module;
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
            'order' => Module::where('course_id', $request->course_id)->count() + 1,
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
        Module::findOrFail($id)->delete();
        return back()->with('success', 'Đã xóa chương và các bài học liên quan!');
    }
}
