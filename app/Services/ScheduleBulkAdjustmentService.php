<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\ScheduleAdjustmentBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ScheduleBulkAdjustmentService
{
    public const MAX_SCHEDULES = 500;

    public function __construct(
        private ScheduleConflictService $conflicts,
        private ScheduleWriteLockService $writeLocks,
    ) {}

    public function preview(User $user, array $filters): array
    {
        $this->authorizeClassScope($user, $filters['class_ids']);
        $schedules = $this->selectedSchedules($user, $filters)->get();
        $this->ensureSelectionIsValid($user, $schedules);

        return $this->buildPreview($schedules, $this->shiftDays($filters));
    }

    public function apply(User $user, array $filters): ScheduleAdjustmentBatch
    {
        $this->authorizeClassScope($user, $filters['class_ids']);

        $batch = DB::transaction(function () use ($user, $filters): ScheduleAdjustmentBatch {
            $schedules = $this->selectedSchedules($user, $filters, true)->get();
            $this->ensureSelectionIsValid($user, $schedules);
            $this->writeLocks->acquire($schedules->map(fn (Schedule $schedule) => $this->candidate(
                $schedule,
                $schedule->schedule_date->copy()->addDays($this->shiftDays($filters))->format('Y-m-d')
            ))->all());
            $preview = $this->buildPreview($schedules, $this->shiftDays($filters), true);

            if ($preview['summary']['conflicts'] > 0) {
                throw ValidationException::withMessages([
                    'schedules' => 'Có '.$preview['summary']['conflicts'].' buổi bị trùng. Hãy xem trước và điều chỉnh phạm vi trước khi áp dụng.',
                ]);
            }

            $before = $schedules->map(fn (Schedule $schedule) => $this->snapshot($schedule))->values()->all();
            $afterById = collect($preview['items'])->keyBy('schedule_id');

            foreach ($schedules as $schedule) {
                $schedule->update(['schedule_date' => $afterById[$schedule->id]['new_date']]);
            }

            $after = $schedules->map(fn (Schedule $schedule) => $this->snapshot($schedule->fresh()))->values()->all();

            return ScheduleAdjustmentBatch::create([
                'public_id' => (string) Str::uuid(),
                'created_by' => $user->id,
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
                'class_ids' => array_values($filters['class_ids']),
                'course_ids' => array_values($filters['course_ids'] ?? []),
                'shift_unit' => $filters['shift_unit'],
                'shift_amount' => (int) $filters['shift_amount'],
                'shift_days' => $this->shiftDays($filters),
                'schedule_count' => $schedules->count(),
                'before_values' => $before,
                'after_values' => $after,
                'status' => ScheduleAdjustmentBatch::STATUS_APPLIED,
            ]);
        });

        AuditLogger::log(
            AuditLogger::SCHEDULE_BULK_ADJUSTED,
            $batch,
            ['schedules' => $batch->before_values],
            ['schedules' => $batch->after_values],
            $this->auditMetadata($batch),
            'Điều chỉnh lịch học hàng loạt.'
        );
        $this->notifyClasses($batch, false);

        return $batch;
    }

    public function undo(User $user, ScheduleAdjustmentBatch $adjustment): ScheduleAdjustmentBatch
    {
        $batch = DB::transaction(function () use ($user, $adjustment): ScheduleAdjustmentBatch {
            $batch = ScheduleAdjustmentBatch::query()->lockForUpdate()->findOrFail($adjustment->id);

            if (! $user->isAdmin() && (int) $batch->created_by !== (int) $user->id) {
                abort(403);
            }

            if ($batch->status !== ScheduleAdjustmentBatch::STATUS_APPLIED) {
                throw ValidationException::withMessages([
                    'adjustment' => 'Đợt điều chỉnh này đã được hoàn tác trước đó.',
                ]);
            }

            $beforeById = collect($batch->before_values)->keyBy('schedule_id');
            $afterById = collect($batch->after_values)->keyBy('schedule_id');
            $scheduleIds = $beforeById->keys()->map(fn ($id) => (int) $id)->all();
            $schedules = Schedule::query()
                ->with(['classroom', 'course'])
                ->whereKey($scheduleIds)
                ->lockForUpdate()
                ->get();

            if ($schedules->count() !== count($scheduleIds)) {
                throw ValidationException::withMessages([
                    'adjustment' => 'Không thể hoàn tác vì một số buổi học không còn tồn tại.',
                ]);
            }

            foreach ($schedules as $schedule) {
                Gate::forUser($user)->authorize('update', $schedule);
                $expectedDate = $afterById[$schedule->id]['schedule_date'] ?? null;
                if ($schedule->schedule_date->format('Y-m-d') !== $expectedDate) {
                    throw ValidationException::withMessages([
                        'adjustment' => 'Không thể hoàn tác vì lịch #'.$schedule->id.' đã được đổi ngày sau đợt điều chỉnh này.',
                    ]);
                }
            }

            $undoCandidates = $schedules->map(function (Schedule $schedule) use ($beforeById): array {
                return $this->candidate($schedule, $beforeById[$schedule->id]['schedule_date']);
            })->values()->all();
            $this->writeLocks->acquire($undoCandidates);
            $conflicts = $this->conflicts->conflictsForCandidates($undoCandidates, $scheduleIds, true);
            $conflictCount = collect($conflicts)->filter()->count();

            if ($conflictCount > 0) {
                throw ValidationException::withMessages([
                    'adjustment' => "Không thể hoàn tác vì {$conflictCount} buổi sẽ bị trùng lịch ở ngày cũ.",
                ]);
            }

            foreach ($schedules as $schedule) {
                $schedule->update(['schedule_date' => $beforeById[$schedule->id]['schedule_date']]);
            }

            $batch->update([
                'status' => ScheduleAdjustmentBatch::STATUS_UNDONE,
                'undone_by' => $user->id,
                'undone_at' => now(),
            ]);

            return $batch->fresh();
        });

        AuditLogger::log(
            AuditLogger::SCHEDULE_BULK_ADJUSTMENT_UNDONE,
            $batch,
            ['schedules' => $batch->after_values],
            ['schedules' => $batch->before_values],
            $this->auditMetadata($batch),
            'Hoàn tác điều chỉnh lịch học hàng loạt.'
        );
        $this->notifyClasses($batch, true);

        return $batch;
    }

    private function selectedSchedules(User $user, array $filters, bool $lock = false): Builder
    {
        return Schedule::query()
            ->with(['classroom:id,name,teacher_id,status', 'course:id,title,teacher_id,status'])
            ->where('status', Schedule::STATUS_ACTIVE)
            ->whereBetween('schedule_date', [$filters['date_from'], $filters['date_to']])
            ->whereIn('class_id', $filters['class_ids'])
            ->when($filters['course_ids'] ?? [], fn (Builder $query, array $ids) => $query->whereIn('course_id', $ids))
            ->whereHas('classroom', fn (Builder $query) => $query->where('status', '!=', Classroom::STATUS_ARCHIVED))
            ->whereHas('course', fn (Builder $query) => $query->where('status', '!=', 'archived'))
            ->when($user->isTeacher(), fn (Builder $query) => $query->whereHas(
                'classroom',
                fn (Builder $classQuery) => $classQuery->where('teacher_id', $user->id)
            ))
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->limit(self::MAX_SCHEDULES + 1)
            ->when($lock, fn (Builder $query) => $query->lockForUpdate());
    }

    private function ensureSelectionIsValid(User $user, Collection $schedules): void
    {
        if ($schedules->isEmpty()) {
            throw ValidationException::withMessages([
                'schedules' => 'Không tìm thấy buổi học phù hợp với lớp, khóa học và khoảng ngày đã chọn.',
            ]);
        }

        if ($schedules->count() > self::MAX_SCHEDULES) {
            throw ValidationException::withMessages([
                'schedules' => 'Mỗi đợt chỉ được điều chỉnh tối đa '.self::MAX_SCHEDULES.' buổi học.',
            ]);
        }

        $schedules->each(fn (Schedule $schedule) => Gate::forUser($user)->authorize('update', $schedule));
    }

    private function authorizeClassScope(User $user, array $classIds): void
    {
        Classroom::query()
            ->whereKey($classIds)
            ->get()
            ->each(fn (Classroom $classroom) => Gate::forUser($user)->authorize('update', $classroom));
    }

    private function buildPreview(Collection $schedules, int $shiftDays, bool $lockConflicts = false): array
    {
        $scheduleIds = $schedules->modelKeys();
        $candidates = $schedules->map(fn (Schedule $schedule) => $this->candidate(
            $schedule,
            $schedule->schedule_date->copy()->addDays($shiftDays)->format('Y-m-d')
        ))->values()->all();
        $conflicts = $this->conflicts->conflictsForCandidates($candidates, $scheduleIds, $lockConflicts);
        $candidatesById = collect($candidates)->keyBy('schedule_id');
        $items = $schedules->map(function (Schedule $schedule) use ($candidatesById, $conflicts): array {
            $candidate = $candidatesById[$schedule->id];
            $messages = $conflicts[$schedule->id] ?? [];

            return [
                'schedule_id' => $schedule->id,
                'class_name' => $schedule->classroom?->name,
                'course_title' => $schedule->course?->title,
                'start_time' => Carbon::parse($schedule->start_time)->format('H:i'),
                'end_time' => Carbon::parse($schedule->end_time)->format('H:i'),
                'old_date' => $schedule->schedule_date->format('Y-m-d'),
                'old_date_label' => $schedule->schedule_date->format('d/m/Y'),
                'new_date' => $candidate['schedule_date'],
                'new_date_label' => Carbon::parse($candidate['schedule_date'])->format('d/m/Y'),
                'has_conflict' => $messages !== [],
                'conflicts' => $messages,
            ];
        })->values();

        return [
            'items' => $items->all(),
            'summary' => [
                'total' => $items->count(),
                'available' => $items->where('has_conflict', false)->count(),
                'conflicts' => $items->where('has_conflict', true)->count(),
                'shift_days' => $shiftDays,
            ],
        ];
    }

    private function candidate(Schedule $schedule, string $date): array
    {
        return [
            'schedule_id' => (int) $schedule->id,
            'class_id' => (int) $schedule->class_id,
            'schedule_date' => $date,
            'start_time' => Carbon::parse($schedule->start_time)->format('H:i:s'),
            'end_time' => Carbon::parse($schedule->end_time)->format('H:i:s'),
            'room' => $schedule->room,
            'status' => (string) $schedule->status,
        ];
    }

    private function snapshot(Schedule $schedule): array
    {
        return [
            'schedule_id' => (int) $schedule->id,
            'class_id' => (int) $schedule->class_id,
            'course_id' => (int) $schedule->course_id,
            'schedule_date' => $schedule->schedule_date->format('Y-m-d'),
            'start_time' => Carbon::parse($schedule->start_time)->format('H:i:s'),
            'end_time' => Carbon::parse($schedule->end_time)->format('H:i:s'),
            'room' => $schedule->room,
        ];
    }

    private function shiftDays(array $filters): int
    {
        $days = (int) $filters['shift_amount'] * ($filters['shift_unit'] === 'week' ? 7 : 1);

        return $filters['direction'] === 'backward' ? -$days : $days;
    }

    private function auditMetadata(ScheduleAdjustmentBatch $batch): array
    {
        return [
            'batch_id' => $batch->public_id,
            'class_ids' => $batch->class_ids,
            'course_ids' => $batch->course_ids,
            'date_from' => $batch->date_from->format('Y-m-d'),
            'date_to' => $batch->date_to->format('Y-m-d'),
            'shift_days' => $batch->shift_days,
            'schedule_count' => $batch->schedule_count,
        ];
    }

    private function notifyClasses(ScheduleAdjustmentBatch $batch, bool $undone): void
    {
        $snapshots = collect($undone ? $batch->before_values : $batch->after_values);
        $snapshots->groupBy('class_id')->each(function (Collection $items, int|string $classId) use ($batch, $undone): void {
            app(NotificationCenter::class)->notifyClassStudents(
                (int) $classId,
                'schedule',
                $undone ? 'Đã hoàn tác điều chỉnh lịch học' : 'Lịch học đã được điều chỉnh',
                $undone
                    ? 'Đã khôi phục '.$items->count().' buổi học về ngày trước khi điều chỉnh.'
                    : 'Đã dời '.$items->count().' buổi học '.abs($batch->shift_days).' ngày. Vui lòng kiểm tra lại lịch.',
                route('students.schedule'),
                ['adjustment_batch_id' => $batch->public_id, 'schedule_count' => $items->count()],
                'schedule-adjustment:'.$batch->public_id.':'.($undone ? 'undone' : 'applied').':'.$classId
            );
        });
    }
}
