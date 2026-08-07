<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateCoursePlan;
use App\Models\AiOperation;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CoursePlannerController extends Controller
{
    public function generate(Request $request, Course $course)
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

        $payload = [
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
        ];

        $operation = AiOperation::create([
            'user_id' => $request->user()->id,
            'feature' => 'course_plan',
            'provider' => 'deepseek',
            'model' => config('services.deepseek.model', 'deepseek-v4-flash'),
            'status' => AiOperation::STATUS_QUEUED,
            'subject_type' => Course::class,
            'subject_id' => $course->id,
            'metadata' => [
                'session_count' => $data['session_count'],
                'minutes_per_session' => $data['minutes_per_session'],
            ],
        ]);

        GenerateCoursePlan::dispatch($operation->id, $payload)->afterCommit();

        return response()->json([
            'success' => true,
            'queued' => true,
            'operation_id' => $operation->uuid,
            'status_url' => route('ai-operations.show', $operation->uuid),
            'poll_interval_ms' => max(1000, (int) config('ai.course_plan.poll_interval_milliseconds', 2000)),
            'poll_timeout_seconds' => max(60, (int) config('ai.course_plan.poll_timeout_seconds', 420)),
        ], 202);
    }

    public function apply(Request $request, Course $course, HtmlSanitizer $htmlSanitizer)
    {
        $this->authorizeManageCourse($course);

        $data = $request->validate([
            'modules' => 'required|array|min:1|max:20',
            'modules.*.title' => 'required|string|max:255',
            'modules.*.lessons' => 'required|array|min:1|max:30',
            'modules.*.lessons.*.title' => 'required|string|max:255',
            'modules.*.lessons.*.content' => 'nullable|string|max:30000',
        ]);

        $created = DB::transaction(function () use ($course, $data, $htmlSanitizer) {
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
                        'content' => $htmlSanitizer->sanitize($lessonData['content'] ?? ''),
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
}
