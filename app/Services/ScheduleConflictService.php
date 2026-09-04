<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleConflictService
{
    /**
     * @return array<int, string>
     */
    public function conflicts(array $attributes, int|array|null $exceptScheduleIds = null, ?Classroom $classroom = null): array
    {
        if (($attributes['status'] ?? Schedule::STATUS_ACTIVE) !== Schedule::STATUS_ACTIVE) {
            return [];
        }

        $classroom ??= Classroom::query()->findOrFail($attributes['class_id']);
        $baseQuery = $this->overlapQuery($attributes, $exceptScheduleIds);
        $conflicts = [];

        if ((clone $baseQuery)->where('schedules.class_id', $classroom->id)->exists()) {
            $conflicts[] = 'Lớp học đã có lịch trong khoảng thời gian này.';
        }

        if ($classroom->teacher_id && (clone $baseQuery)
            ->join('classes', 'schedules.class_id', '=', 'classes.id')
            ->where('classes.teacher_id', $classroom->teacher_id)
            ->exists()) {
            $conflicts[] = 'Giáo viên đã có lịch dạy trong khoảng thời gian này.';
        }

        $room = trim((string) ($attributes['room'] ?? ''));
        if ($room !== '' && (clone $baseQuery)
            ->whereNotNull('schedules.room')
            ->whereRaw('LOWER(TRIM(schedules.room)) = ?', [mb_strtolower($room)])
            ->exists()) {
            $conflicts[] = "Phòng {$room} đã được sử dụng trong khoảng thời gian này.";
        }

        return array_values(array_unique($conflicts));
    }

    public function ensureNoConflicts(array $attributes, int|array|null $exceptScheduleIds = null, ?Classroom $classroom = null): void
    {
        $conflicts = $this->conflicts($attributes, $exceptScheduleIds, $classroom);

        if ($conflicts !== []) {
            throw ValidationException::withMessages([
                'schedule' => implode(' ', $conflicts),
            ]);
        }
    }

    /**
     * Kiểm tra nhiều ngày có cùng lớp và khung giờ bằng một truy vấn duy nhất.
     *
     * @param  array<int, string|\DateTimeInterface>  $dates
     * @return array<string, array<int, string>>
     */
    public function conflictsForDates(
        array $attributes,
        array $dates,
        int|array|null $exceptScheduleIds = null,
        ?Classroom $classroom = null
    ): array {
        $dateValues = collect($dates)
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();
        $result = $dateValues->mapWithKeys(fn (string $date) => [$date => []])->all();

        if ($dateValues->isEmpty() || ($attributes['status'] ?? Schedule::STATUS_ACTIVE) !== Schedule::STATUS_ACTIVE) {
            return $result;
        }

        $classroom ??= Classroom::query()->findOrFail($attributes['class_id']);
        $excludedIds = array_values(array_filter((array) $exceptScheduleIds));
        $rows = DB::table('schedules')
            ->leftJoin('classes', 'schedules.class_id', '=', 'classes.id')
            ->where('schedules.status', Schedule::STATUS_ACTIVE)
            ->whereIn('schedules.schedule_date', $dateValues->all())
            ->where('schedules.start_time', '<', $attributes['end_time'])
            ->where('schedules.end_time', '>', $attributes['start_time'])
            ->when($excludedIds !== [], fn (Builder $query) => $query->whereNotIn('schedules.id', $excludedIds))
            ->select([
                'schedules.schedule_date',
                'schedules.class_id',
                'schedules.room',
                'classes.teacher_id',
            ])
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->schedule_date)->toDateString());

        $room = mb_strtolower(trim((string) ($attributes['room'] ?? '')));

        foreach ($dateValues as $date) {
            $dateRows = $rows->get($date, collect());
            $conflicts = [];

            if ($dateRows->contains(fn ($row) => (int) $row->class_id === (int) $classroom->id)) {
                $conflicts[] = 'Lớp học đã có lịch trong khoảng thời gian này.';
            }

            if ($classroom->teacher_id && $dateRows->contains(
                fn ($row) => (int) $row->teacher_id === (int) $classroom->teacher_id
            )) {
                $conflicts[] = 'Giáo viên đã có lịch dạy trong khoảng thời gian này.';
            }

            if ($room !== '' && $dateRows->contains(
                fn ($row) => mb_strtolower(trim((string) $row->room)) === $room
            )) {
                $conflicts[] = "Phòng {$attributes['room']} đã được sử dụng trong khoảng thời gian này.";
            }

            $result[$date] = $conflicts;
        }

        return $result;
    }

    private function overlapQuery(array $attributes, int|array|null $exceptScheduleIds): Builder
    {
        $excludedIds = array_values(array_filter((array) $exceptScheduleIds));

        return DB::table('schedules')
            ->where('schedules.status', Schedule::STATUS_ACTIVE)
            ->where('schedules.schedule_date', $attributes['schedule_date'])
            ->where('schedules.start_time', '<', $attributes['end_time'])
            ->where('schedules.end_time', '>', $attributes['start_time'])
            ->when($excludedIds !== [], fn (Builder $query) => $query->whereNotIn('schedules.id', $excludedIds));
    }
}
