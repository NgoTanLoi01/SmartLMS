<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Schedule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleConflictService
{
    /**
     * @return array<int, string>
     */
    public function conflicts(array $attributes, ?int $exceptScheduleId = null, ?Classroom $classroom = null): array
    {
        if (($attributes['status'] ?? Schedule::STATUS_ACTIVE) !== Schedule::STATUS_ACTIVE) {
            return [];
        }

        $classroom ??= Classroom::query()->findOrFail($attributes['class_id']);
        $baseQuery = $this->overlapQuery($attributes, $exceptScheduleId);
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

    public function ensureNoConflicts(array $attributes, ?int $exceptScheduleId = null, ?Classroom $classroom = null): void
    {
        $conflicts = $this->conflicts($attributes, $exceptScheduleId, $classroom);

        if ($conflicts !== []) {
            throw ValidationException::withMessages([
                'schedule' => implode(' ', $conflicts),
            ]);
        }
    }

    private function overlapQuery(array $attributes, ?int $exceptScheduleId): Builder
    {
        return DB::table('schedules')
            ->where('schedules.status', Schedule::STATUS_ACTIVE)
            ->where('schedules.schedule_date', $attributes['schedule_date'])
            ->where('schedules.start_time', '<', $attributes['end_time'])
            ->where('schedules.end_time', '>', $attributes['start_time'])
            ->when($exceptScheduleId, fn (Builder $query) => $query->where('schedules.id', '!=', $exceptScheduleId));
    }
}
