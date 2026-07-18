<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AiTeachingContentController extends Controller
{
    public function generate(Request $request, DeepSeekService $deepSeekService)
    {
        $data = $request->validate([
            'type' => 'required|in:assignment,rubric,quiz,lesson_summary',
            'course_id' => 'nullable|exists:courses,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'module_id' => 'nullable|exists:modules,id',
            'source_text' => 'nullable|string|max:20000',
            'teacher_request' => 'nullable|string|max:1000',
            'current_title' => 'nullable|string|max:255',
            'current_instructions' => 'nullable|string|max:10000',
        ]);

        if (! empty($data['lesson_id'])) {
            $lesson = Lesson::with('module.course')->findOrFail($data['lesson_id']);
            $this->authorizeManageCourse($lesson->module->course);
        } elseif (! empty($data['module_id'])) {
            $module = Module::with('course')->findOrFail($data['module_id']);
            $this->authorizeManageCourse($module->course);
        } elseif (! empty($data['course_id'])) {
            $this->authorizeManageCourse(Course::findOrFail($data['course_id']));
        } else {
            abort(422, 'Cần chọn bài học hoặc khóa học nguồn để AI soạn nội dung.');
        }

        $result = $deepSeekService->generateTeachingContent($data['type'], $data, $request->user());

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    private function authorizeManageCourse(Course $course): void
    {
        Gate::authorize('manageContent', $course);
    }
}
