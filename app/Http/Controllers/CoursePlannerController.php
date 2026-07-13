<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CoursePlannerController extends Controller
{
    public function generate(Request $request, Course $course, DeepSeekService $deepSeek)
    {
        $this->authorizeManageCourse($course);

        $data = $request->validate([
            'audience' => 'required|string|max:500',
            'current_level' => 'required|string|max:500',
            'learning_outcomes' => 'required|string|max:2000',
            'session_count' => 'required|integer|min:1|max:60',
            'minutes_per_session' => 'required|integer|min:30|max:480',
            'notes' => 'nullable|string|max:2000',
        ]);

        $result = $deepSeek->generateCoursePlan([
            'course' => [
                'title' => $course->title,
                'description' => $course->description,
                'existing_modules' => $course->modules()->with('lessons:id,module_id,title')->get()
                    ->map(fn ($module) => [
                        'title' => $module->title,
                        'lessons' => $module->lessons->pluck('title')->values()->all(),
                    ])->values()->all(),
            ],
            'requirements' => $data,
        ]);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function apply(Request $request, Course $course)
    {
        $this->authorizeManageCourse($course);

        $data = $request->validate([
            'modules' => 'required|array|min:1|max:20',
            'modules.*.title' => 'required|string|max:255',
            'modules.*.lessons' => 'required|array|min:1|max:30',
            'modules.*.lessons.*.title' => 'required|string|max:255',
            'modules.*.lessons.*.content' => 'nullable|string|max:30000',
        ]);

        $created = DB::transaction(function () use ($course, $data) {
            $moduleOrder = (int) $course->modules()->max('order');
            $moduleCount = 0;
            $lessonCount = 0;

            foreach ($data['modules'] as $moduleData) {
                $module = Module::create([
                    'course_id' => $course->id,
                    'title' => trim($moduleData['title']),
                    'order' => ++$moduleOrder,
                    'status' => Module::STATUS_PUBLISHED,
                ]);
                $moduleCount++;

                foreach ($moduleData['lessons'] as $index => $lessonData) {
                    Lesson::create([
                        'module_id' => $module->id,
                        'title' => trim($lessonData['title']),
                        'content' => $this->sanitizeLessonContent($lessonData['content'] ?? ''),
                        'order' => $index + 1,
                        'status' => Lesson::STATUS_DRAFT,
                        'published_at' => null,
                    ]);
                    $lessonCount++;
                }
            }

            return compact('moduleCount', 'lessonCount');
        });

        return response()->json([
            'success' => true,
            'message' => "Đã thêm {$created['moduleCount']} chương và {$created['lessonCount']} bài học ở trạng thái bản nháp.",
            'redirect_url' => route('courses.show', $course),
        ]);
    }

    private function authorizeManageCourse(Course $course): void
    {
        Gate::authorize('manageContent', $course);
    }

    private function sanitizeLessonContent(string $content): string
    {
        $content = strip_tags($content, '<h2><h3><h4><p><ul><ol><li><strong><em><br>');

        return preg_replace('/<(h2|h3|h4|p|ul|ol|li|strong|em|br)\b[^>]*>/i', '<$1>', $content) ?? '';
    }
}
