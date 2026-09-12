<?php

namespace App\Services;

use App\Models\Assignments;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialAssignment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TrashService
{
    public const TYPES = [
        'course' => ['label' => 'Khóa học', 'icon' => 'fa-graduation-cap'],
        'module' => ['label' => 'Chương', 'icon' => 'fa-layer-group'],
        'lesson' => ['label' => 'Bài học', 'icon' => 'fa-book-open'],
        'assignment' => ['label' => 'Bài tập', 'icon' => 'fa-clipboard-check'],
        'material' => ['label' => 'Học liệu', 'icon' => 'fa-folder-open'],
        'material_assignment' => ['label' => 'Học liệu đã gỡ', 'icon' => 'fa-link-slash'],
        'schedule' => ['label' => 'Lịch học', 'icon' => 'fa-calendar-days'],
    ];

    public function __construct(
        private ScheduleConflictService $scheduleConflicts,
        private ScheduleWriteLockService $scheduleWriteLocks,
        private PermanentDeletionService $permanentDeletion,
    ) {}

    public function counts(User $user): array
    {
        return collect(array_keys(self::TYPES))
            ->mapWithKeys(fn (string $type) => [$type => $this->query($type, $user)->count()])
            ->all();
    }

    public function paginate(string $type, User $user, string $search = ''): LengthAwarePaginator
    {
        $query = $this->query($type, $user);
        $this->applySearch($query, $type, $search);

        return $query->latest('updated_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Model $model) => $this->present($type, $model));
    }

    /** @param array<int, array{type: string, id: int}> $items */
    public function restoreMany(array $items, User $user): int
    {
        $models = $this->resolveMany($items, $user)
            ->sortBy(fn (array $item) => $this->restoreOrder($item['type']))
            ->values();

        DB::transaction(function () use ($models, $user): void {
            foreach ($models as $item) {
                $this->restore($item['type'], $item['model'], $user);
            }
        });

        return $models->count();
    }

    /** @param array<int, array{type: string, id: int}> $items */
    public function permanentlyDeleteMany(array $items, User $user): int
    {
        abort_unless($user->isAdmin(), 403);
        $models = $this->resolveMany($items, $user)
            ->sortBy(fn (array $item) => $this->deleteOrder($item['type']))
            ->values();

        foreach ($models as $item) {
            if (! $item['model']->exists) {
                continue;
            }
            $this->permanentDeletion->purge($item['type'], $item['model']);
        }

        return $models->count();
    }

    public function query(string $type, User $user): Builder
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $query = match ($type) {
            'course' => Course::query()->with('teacher')->where('status', Course::STATUS_ARCHIVED),
            'module' => Module::query()->with('course.teacher')->where('status', Module::STATUS_ARCHIVED),
            'lesson' => Lesson::query()->with('module.course.teacher')->where('status', Lesson::STATUS_ARCHIVED),
            'assignment' => Assignments::query()->with(['course.teacher', 'lesson'])->where('status', Assignments::STATUS_ARCHIVED),
            'material' => LearningMaterial::query()->with('uploader')->where('status', LearningMaterial::STATUS_ARCHIVED),
            'material_assignment' => LearningMaterialAssignment::query()->with(['material', 'course.teacher'])->where('status', LearningMaterialAssignment::STATUS_ARCHIVED),
            'schedule' => Schedule::query()->with(['classroom.teacher', 'course.teacher'])->where('status', Schedule::STATUS_ARCHIVED),
        };

        if ($user->isAdmin()) {
            return $query;
        }

        return match ($type) {
            'course' => $query->where('teacher_id', $user->id),
            'module' => $query->whereHas('course', fn (Builder $course) => $course->where('teacher_id', $user->id)),
            'lesson' => $query->whereHas('module.course', fn (Builder $course) => $course->where('teacher_id', $user->id)),
            'assignment' => $query->whereHas('course', fn (Builder $course) => $course->where('teacher_id', $user->id)),
            'material' => $query->where('uploaded_by', $user->id),
            'material_assignment' => $query->whereHas('course', fn (Builder $course) => $course->where('teacher_id', $user->id)),
            'schedule' => $query
                ->whereHas('classroom', fn (Builder $classroom) => $classroom->where('teacher_id', $user->id))
                ->whereHas('course', fn (Builder $course) => $course->where('teacher_id', $user->id)),
        };
    }

    private function restore(string $type, Model $model, User $user): void
    {
        $model->refresh();

        match ($type) {
            'course' => $this->restoreCourse($model, $user),
            'module' => $this->restoreModule($model, $user),
            'lesson' => $this->restoreLesson($model, $user),
            'assignment' => $this->restoreAssignment($model, $user),
            'material' => $this->restoreMaterial($model, $user),
            'material_assignment' => $this->restoreMaterialAssignment($model, $user),
            'schedule' => $this->restoreSchedule($model, $user),
        };
    }

    private function restoreCourse(Model $model, User $user): void
    {
        /** @var Course $course */
        $course = $model;
        Gate::forUser($user)->authorize('update', $course);
        $course->update(['status' => Course::STATUS_DRAFT, 'published_at' => null]);
    }

    private function restoreModule(Model $model, User $user): void
    {
        /** @var Module $module */
        $module = $model;
        $module->load('course');
        $this->requireActiveParent($module->course, 'khóa học');
        Gate::forUser($user)->authorize('update', $module);
        $module->update(['status' => Module::STATUS_PUBLISHED]);
    }

    private function restoreLesson(Model $model, User $user): void
    {
        /** @var Lesson $lesson */
        $lesson = $model;
        $lesson->load('module.course');
        $this->requireActiveParent($lesson->module?->course, 'khóa học');
        $this->requireActiveParent($lesson->module, 'chương');
        Gate::forUser($user)->authorize('update', $lesson);
        $lesson->update(['status' => Lesson::STATUS_DRAFT, 'published_at' => null]);
    }

    private function restoreAssignment(Model $model, User $user): void
    {
        /** @var Assignments $assignment */
        $assignment = $model;
        $assignment->load('course', 'lesson.module');
        $this->requireActiveParent($assignment->course, 'khóa học');
        if ($assignment->lesson) {
            $this->requireActiveParent($assignment->lesson->module, 'chương');
            $this->requireActiveParent($assignment->lesson, 'bài học');
        }
        Gate::forUser($user)->authorize('update', $assignment);
        $assignment->update(['status' => Assignments::STATUS_DRAFT, 'published_at' => null]);
    }

    private function restoreMaterial(Model $model, User $user): void
    {
        /** @var LearningMaterial $material */
        $material = $model;
        abort_unless($material->ownedBy($user), 403);
        $material->update(['status' => LearningMaterial::STATUS_PUBLISHED]);
    }

    private function restoreMaterialAssignment(Model $model, User $user): void
    {
        /** @var LearningMaterialAssignment $assignment */
        $assignment = $model;
        $assignment->load(['material', 'course', 'lesson.module', 'unlockLesson.module']);
        $this->requireActiveParent($assignment->course, 'khóa học');
        $this->requireActiveParent($assignment->material, 'học liệu gốc');
        foreach ([$assignment->lesson, $assignment->unlockLesson] as $lesson) {
            if ($lesson) {
                $this->requireActiveParent($lesson->module, 'chương');
                $this->requireActiveParent($lesson, 'bài học');
            }
        }
        Gate::forUser($user)->authorize('manageContent', $assignment->course);
        $assignment->update(['status' => LearningMaterialAssignment::STATUS_HIDDEN]);
    }

    private function restoreSchedule(Model $model, User $user): void
    {
        /** @var Schedule $schedule */
        $schedule = $model;
        $schedule->load(['classroom', 'course']);
        $this->requireActiveParent($schedule->classroom, 'lớp học');
        $this->requireActiveParent($schedule->course, 'khóa học');
        Gate::forUser($user)->authorize('update', $schedule);
        $attributes = array_merge($schedule->only([
            'class_id', 'course_id', 'schedule_date', 'start_time', 'end_time', 'room', 'note',
        ]), [
            'schedule_date' => $schedule->schedule_date->toDateString(),
            'status' => Schedule::STATUS_ACTIVE,
        ]);
        $this->scheduleWriteLocks->acquire([$attributes]);
        $this->scheduleConflicts->ensureNoConflicts($attributes, $schedule->id, $schedule->classroom);
        $schedule->update(['status' => Schedule::STATUS_ACTIVE]);
    }

    private function requireActiveParent(?Model $model, string $label): void
    {
        if (! $model || $model->getAttribute('status') === 'archived') {
            throw ValidationException::withMessages([
                'items' => "Cần khôi phục {$label} liên quan trước.",
            ]);
        }
    }

    /** @param array<int, array{type: string, id: int}> $items */
    private function resolveMany(array $items, User $user)
    {
        return collect($items)
            ->unique(fn (array $item) => $item['type'].':'.$item['id'])
            ->map(function (array $item) use ($user): array {
                $model = $this->query($item['type'], $user)->find($item['id']);
                abort_unless($model, 404);

                return ['type' => $item['type'], 'model' => $model];
            });
    }

    private function applySearch(Builder $query, string $type, string $search): void
    {
        if ($search === '') {
            return;
        }

        match ($type) {
            'course', 'module', 'lesson', 'assignment', 'material' => $query->where('title', 'like', "%{$search}%"),
            'material_assignment' => $query->whereHas('material', fn (Builder $material) => $material->where('title', 'like', "%{$search}%")),
            'schedule' => $query->where(function (Builder $match) use ($search): void {
                $match->where('room', 'like', "%{$search}%")
                    ->orWhereHas('course', fn (Builder $course) => $course->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('classroom', fn (Builder $classroom) => $classroom->where('name', 'like', "%{$search}%"));
            }),
        };
    }

    private function present(string $type, Model $model): array
    {
        return [
            'key' => $type.':'.$model->getKey(),
            'type' => $type,
            'id' => $model->getKey(),
            'title' => match ($type) {
                'course', 'module', 'lesson', 'assignment', 'material' => $model->getAttribute('title'),
                'material_assignment' => $model->material?->title ?? 'Học liệu không còn tồn tại',
                'schedule' => ($model->course?->title ?? 'Khóa học').' — '.($model->classroom?->name ?? 'Lớp học'),
            },
            'context' => match ($type) {
                'course' => $model->teacher?->name ?? 'Chưa có giáo viên',
                'module' => $model->course?->title ?? 'Khóa học không tồn tại',
                'lesson' => ($model->module?->course?->title ?? 'Khóa học').' / '.($model->module?->title ?? 'Chương'),
                'assignment' => ($model->course?->title ?? 'Khóa học').($model->lesson ? ' / '.$model->lesson->title : ''),
                'material' => $model->uploader?->name ?? 'Không rõ người tải',
                'material_assignment' => $model->course?->title ?? 'Khóa học không tồn tại',
                'schedule' => $model->schedule_date?->format('d/m/Y').' · '.substr((string) $model->start_time, 0, 5).'–'.substr((string) $model->end_time, 0, 5).($model->room ? ' · '.$model->room : ''),
            },
            'archived_at' => $model->updated_at?->format('d/m/Y H:i'),
        ];
    }

    private function restoreOrder(string $type): int
    {
        return array_search($type, ['course', 'module', 'lesson', 'assignment', 'material', 'material_assignment', 'schedule'], true);
    }

    private function deleteOrder(string $type): int
    {
        return array_search($type, ['material_assignment', 'schedule', 'assignment', 'lesson', 'module', 'material', 'course'], true);
    }
}
