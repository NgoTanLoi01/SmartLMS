<?php

namespace App\Http\Controllers;

use App\Models\LearningProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LearningProgramController extends Controller
{
    public function index()
    {
        Gate::authorize('create', LearningProgram::class);

        $query = LearningProgram::with('teacher')
            ->withCount('courses')
            ->latest();

        if (auth()->user()->role === 'teacher') {
            $query->where('teacher_id', auth()->id());
        }

        $programs = $query->get();

        return view('programs.index', compact('programs'));
    }

    public function show(LearningProgram $program)
    {
        Gate::authorize('view', $program);

        $program->load([
            'teacher',
            'courses' => function ($query) {
                $query->with(['teacher', 'classes'])
                    ->withCount('modules')
                    ->withCount([
                        'modules as lessons_count' => function ($query) {
                            $query->leftJoin('lessons', 'modules.id', '=', 'lessons.module_id')
                                ->select(DB::raw('count(lessons.id)'));
                        },
                    ])
                    ->latest();
            },
        ]);

        $templateCourses = $program->courses
            ->where('course_type', 'template')
            ->values();

        $deliveryCourses = $program->courses
            ->where('course_type', 'delivery')
            ->values();

        foreach ($deliveryCourses as $course) {
            $course->students_count = DB::table('class_user')
                ->whereIn('class_id', $course->classes->pluck('id'))
                ->distinct('user_id')
                ->count();
        }

        $classIds = $deliveryCourses
            ->flatMap(fn ($course) => $course->classes->pluck('id'))
            ->unique()
            ->values();

        $stats = [
            'template_courses' => $templateCourses->count(),
            'delivery_courses' => $deliveryCourses->count(),
            'classes' => $classIds->count(),
            'students' => $classIds->isEmpty()
                ? 0
                : DB::table('class_user')->whereIn('class_id', $classIds)->distinct('user_id')->count(),
        ];

        return view('programs.show', compact('program', 'templateCourses', 'deliveryCourses', 'stats'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', LearningProgram::class);

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
        Gate::authorize('update', $program);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:learning_programs,code,'.$program->id,
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,hidden',
        ]);

        $program->update($data);

        return back()->with('success', 'Đã cập nhật chương trình học.');
    }

    public function destroy(LearningProgram $program)
    {
        Gate::authorize('delete', $program);

        $program->delete();

        return back()->with('success', 'Đã xóa chương trình học. Các khóa học liên quan vẫn được giữ lại.');
    }
}
