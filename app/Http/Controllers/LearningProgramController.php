<?php

namespace App\Http\Controllers;

use App\Models\LearningProgram;
use Illuminate\Http\Request;

class LearningProgramController extends Controller
{
    public function index()
    {
        $this->authorizeProgramManagement();

        $query = LearningProgram::with('teacher')
            ->withCount('courses')
            ->latest();

        if (auth()->user()->role === 'teacher') {
            $query->where('teacher_id', auth()->id());
        }

        $programs = $query->get();

        return view('programs.index', compact('programs'));
    }

    public function store(Request $request)
    {
        $this->authorizeProgramManagement();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:learning_programs,code',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,hidden',
        ]);

        LearningProgram::create(array_merge($data, [
            'teacher_id' => auth()->id(),
        ]));

        return back()->with('success', 'Đã tạo chương trình học thành công.');
    }

    public function update(Request $request, LearningProgram $program)
    {
        $this->authorizeProgramOwner($program);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:learning_programs,code,' . $program->id,
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,hidden',
        ]);

        $program->update($data);

        return back()->with('success', 'Đã cập nhật chương trình học.');
    }

    public function destroy(LearningProgram $program)
    {
        $this->authorizeProgramOwner($program);

        $program->delete();

        return back()->with('success', 'Đã xóa chương trình học. Các khóa học liên quan vẫn được giữ lại.');
    }

    private function authorizeProgramManagement(): void
    {
        if (!in_array(auth()->user()->role, ['admin', 'teacher'])) {
            abort(403, 'Bạn không có quyền quản lý chương trình học.');
        }
    }

    private function authorizeProgramOwner(LearningProgram $program): void
    {
        $this->authorizeProgramManagement();

        if (auth()->user()->role !== 'admin' && $program->teacher_id !== auth()->id()) {
            abort(403, 'Bạn không có quyền thao tác chương trình học này.');
        }
    }
}
