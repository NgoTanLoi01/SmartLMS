<?php

namespace App\Http\Controllers;

use App\Models\Assignments;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Services\AuditLogger;
use App\Services\CourseCloningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentCloneController extends Controller
{
    public function __construct(private CourseCloningService $cloner) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'source_type' => ['required', Rule::in(['module', 'lesson', 'assignment', 'quiz'])],
            'source_id' => ['required', 'integer', 'min:1'],
            'target_course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'target_module_id' => ['nullable', 'integer', 'exists:modules,id'],
            'target_lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'copy_assignments' => ['nullable', 'boolean'],
            'copy_materials' => ['nullable', 'boolean'],
            'copy_rubrics' => ['nullable', 'boolean'],
            'copy_questions' => ['nullable', 'boolean'],
        ]);

        $options = [
            'copy_assignments' => $request->boolean('copy_assignments'),
            'copy_materials' => $request->boolean('copy_materials'),
            'copy_rubrics' => $request->boolean('copy_rubrics'),
            'copy_questions' => $request->boolean('copy_questions'),
        ];

        [$result, $targetCourse] = match ($data['source_type']) {
            'module' => $this->cloneModule((int) $data['source_id'], $options),
            'lesson' => $this->cloneLesson($data, $options),
            'assignment' => $this->cloneAssignment($data, $options),
            'quiz' => $this->cloneQuiz($data, $options),
        };

        AuditLogger::log(
            AuditLogger::CONTENT_CLONED,
            $result['model'],
            null,
            ['status' => $result['model']->status],
            [
                'source_type' => $data['source_type'],
                'source_id' => (int) $data['source_id'],
                'target_course_id' => $targetCourse->id,
                'options' => $options,
                'counts' => collect($result)->except(['type', 'model'])->all(),
            ],
            'Sao chép nội dung riêng lẻ sang trạng thái bản nháp.'
        );

        return redirect()->route('courses.show', $targetCourse)
            ->with('success', $this->successMessage($result));
    }

    private function cloneModule(int $sourceId, array $options): array
    {
        $source = Module::query()->notArchived()->with('course')->findOrFail($sourceId);
        Gate::authorize('update', $source);

        return [$this->cloner->cloneModule($source, $options), $source->course];
    }

    private function cloneLesson(array $data, array $options): array
    {
        $source = Lesson::query()->notArchived()->with('module.course')->findOrFail((int) $data['source_id']);
        Gate::authorize('update', $source);
        [$targetCourse, $targetModule] = $this->targetModule($data);
        Gate::authorize('create', [Lesson::class, $targetModule]);

        return [$this->cloner->cloneLesson($source, $targetModule, $options), $targetCourse];
    }

    private function cloneAssignment(array $data, array $options): array
    {
        $source = Assignments::query()->notArchived()->with('course')->findOrFail((int) $data['source_id']);
        Gate::authorize('update', $source);
        [$targetCourse, $targetLesson] = $this->targetLesson($data);
        Gate::authorize('create', [Assignments::class, $targetCourse]);

        return [
            $this->cloner->cloneAssignment($source, $targetLesson, $options['copy_rubrics']),
            $targetCourse,
        ];
    }

    private function cloneQuiz(array $data, array $options): array
    {
        $source = Quiz::query()->notArchived()->with('course')->findOrFail((int) $data['source_id']);
        Gate::authorize('update', $source);
        $targetCourse = $this->targetCourse($data);
        Gate::authorize('create', [Quiz::class, $targetCourse]);

        return [
            $this->cloner->cloneQuiz($source, $targetCourse, $options['copy_questions']),
            $targetCourse,
        ];
    }

    private function targetModule(array $data): array
    {
        $targetCourse = $this->targetCourse($data);
        $targetModule = Module::query()->notArchived()->findOrFail((int) ($data['target_module_id'] ?? 0));

        if ((int) $targetModule->course_id !== (int) $targetCourse->id) {
            throw ValidationException::withMessages([
                'target_module_id' => 'Chương đích không thuộc khóa học đã chọn.',
            ]);
        }

        return [$targetCourse, $targetModule];
    }

    private function targetLesson(array $data): array
    {
        $targetCourse = $this->targetCourse($data);
        $targetLesson = Lesson::query()
            ->notArchived()
            ->with('module')
            ->findOrFail((int) ($data['target_lesson_id'] ?? 0));

        if ((int) $targetLesson->module?->course_id !== (int) $targetCourse->id) {
            throw ValidationException::withMessages([
                'target_lesson_id' => 'Bài học đích không thuộc khóa học đã chọn.',
            ]);
        }

        return [$targetCourse, $targetLesson];
    }

    private function targetCourse(array $data): Course
    {
        if (empty($data['target_course_id'])) {
            throw ValidationException::withMessages([
                'target_course_id' => 'Vui lòng chọn khóa học đích.',
            ]);
        }

        return Course::query()->notArchived()->findOrFail((int) $data['target_course_id']);
    }

    private function successMessage(array $result): string
    {
        $details = collect([
            ($result['lessons'] ?? 0) > 0 ? $result['lessons'].' bài học' : null,
            ($result['assignments'] ?? 0) > 0 ? $result['assignments'].' bài tập' : null,
            ($result['materials'] ?? 0) > 0 ? $result['materials'].' học liệu' : null,
            ($result['questions'] ?? 0) > 0 ? $result['questions'].' câu hỏi riêng' : null,
        ])->filter()->implode(', ');

        return 'Đã tạo bản sao ở trạng thái nháp'.($details ? " ({$details})" : '').'.';
    }
}
