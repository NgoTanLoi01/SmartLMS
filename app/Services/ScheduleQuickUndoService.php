<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\ScheduleChangeBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ScheduleQuickUndoService
{
    public const UNDO_SECONDS = 30;

    public function __construct(
        private ScheduleConflictService $conflicts,
        private ScheduleWriteLockService $writeLocks,
    ) {}

    /** @param array<int, array<string, mixed>> $before @param array<int, array<string, mixed>> $after */
    public function record(User $user, string $scope, array $before, array $after): ScheduleChangeBatch
    {
        return ScheduleChangeBatch::create([
            'public_id' => (string) Str::uuid(),
            'created_by' => $user->id,
            'scope' => $scope,
            'schedule_count' => count($before),
            'before_values' => array_values($before),
            'after_values' => array_values($after),
            'status' => ScheduleChangeBatch::STATUS_APPLIED,
            'expires_at' => now()->addSeconds(self::UNDO_SECONDS),
        ]);
    }

    public function undo(User $user, ScheduleChangeBatch $change): ScheduleChangeBatch
    {
        $batch = DB::transaction(function () use ($user, $change): ScheduleChangeBatch {
            $batch = ScheduleChangeBatch::query()->lockForUpdate()->findOrFail($change->id);

            if (! $user->isAdmin() && (int) $batch->created_by !== (int) $user->id) {
                abort(403);
            }
            if ($batch->status !== ScheduleChangeBatch::STATUS_APPLIED) {
                throw ValidationException::withMessages(['change' => 'Thay đổi lịch này đã được hoàn tác.']);
            }
            if ($batch->expires_at->isPast()) {
                throw ValidationException::withMessages(['change' => 'Thời gian hoàn tác nhanh đã hết.']);
            }

            $beforeById = collect($batch->before_values)->keyBy('schedule_id');
            $afterById = collect($batch->after_values)->keyBy('schedule_id');
            $scheduleIds = $beforeById->keys()->map(fn ($id) => (int) $id)->all();
            $schedules = Schedule::query()
                ->with(['classroom', 'course'])
                ->whereKey($scheduleIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($schedules->count() !== count($scheduleIds)) {
                throw ValidationException::withMessages(['change' => 'Không thể hoàn tác vì một số buổi học không còn tồn tại.']);
            }

            foreach ($schedules as $schedule) {
                Gate::forUser($user)->authorize('update', $schedule);
                if ($this->snapshot($schedule) !== $afterById->get($schedule->id)) {
                    throw ValidationException::withMessages([
                        'change' => 'Không thể hoàn tác vì lịch #'.$schedule->id.' đã được chỉnh sửa tiếp.',
                    ]);
                }
            }

            $restoreCandidates = $beforeById->values()->all();
            $this->writeLocks->acquire(array_merge(
                $schedules->map(fn (Schedule $schedule) => $this->snapshot($schedule))->all(),
                $restoreCandidates
            ));
            $conflicts = $this->conflicts->conflictsForCandidates($restoreCandidates, $scheduleIds, true);
            $conflictCount = collect($conflicts)->filter()->count();

            if ($conflictCount > 0) {
                throw ValidationException::withMessages([
                    'change' => "Không thể hoàn tác vì {$conflictCount} buổi sẽ bị trùng lịch ở vị trí cũ.",
                ]);
            }

            foreach ($schedules as $schedule) {
                $schedule->update($this->attributesForRestore($beforeById[$schedule->id]));
            }

            $batch->update([
                'status' => ScheduleChangeBatch::STATUS_UNDONE,
                'undone_by' => $user->id,
                'undone_at' => now(),
            ]);

            return $batch->fresh();
        });

        AuditLogger::log(
            AuditLogger::SCHEDULE_QUICK_UNDONE,
            $batch,
            ['schedules' => $batch->after_values],
            ['schedules' => $batch->before_values],
            ['scope' => $batch->scope, 'schedule_count' => $batch->schedule_count],
            'Hoàn tác nhanh thao tác kéo thả lịch học.'
        );
        $this->notifyAffectedClasses($batch);

        return $batch;
    }

    /** @param iterable<int, Schedule> $schedules @return array<int, array<string, mixed>> */
    public function snapshots(iterable $schedules): array
    {
        return collect($schedules)->map(fn (Schedule $schedule) => $this->snapshot($schedule))->values()->all();
    }

    /** @return array<string, mixed> */
    public function snapshot(Schedule $schedule): array
    {
        return [
            'schedule_id' => (int) $schedule->id,
            'series_id' => $schedule->series_id,
            'series_position' => $schedule->series_position === null ? null : (int) $schedule->series_position,
            'class_id' => (int) $schedule->class_id,
            'course_id' => (int) $schedule->course_id,
            'schedule_date' => $schedule->schedule_date->format('Y-m-d'),
            'start_time' => Carbon::parse($schedule->start_time)->format('H:i:s'),
            'end_time' => Carbon::parse($schedule->end_time)->format('H:i:s'),
            'room' => $schedule->room,
            'note' => $schedule->note,
            'status' => (string) $schedule->status,
        ];
    }

    /** @param array<string, mixed> $snapshot */
    private function attributesForRestore(array $snapshot): array
    {
        return collect($snapshot)->except('schedule_id')->all();
    }

    private function notifyAffectedClasses(ScheduleChangeBatch $batch): void
    {
        collect(array_merge($batch->before_values, $batch->after_values))
            ->groupBy('class_id')
            ->each(function (Collection $items, int|string $classId) use ($batch): void {
                $scheduleCount = $items->unique('schedule_id')->count();
                app(NotificationCenter::class)->notifyClassStudents(
                    (int) $classId,
                    'schedule',
                    'Đã hoàn tác thay đổi lịch học',
                    'Đã khôi phục '.$scheduleCount.' buổi học về thời gian trước đó.',
                    route('students.schedule'),
                    ['schedule_change_id' => $batch->public_id],
                    'schedule-change:'.$batch->public_id.':undone:'.$classId
                );
            });
    }
}
