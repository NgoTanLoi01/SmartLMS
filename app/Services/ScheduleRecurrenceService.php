<?php

namespace App\Services;

use App\Models\Classroom;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class ScheduleRecurrenceService
{
    public const MAX_OCCURRENCES = 104;

    public function __construct(private ScheduleConflictService $scheduleConflicts) {}

    /**
     * @return array<int, CarbonImmutable>
     */
    public function dates(array $attributes): array
    {
        $startDate = CarbonImmutable::parse($attributes['schedule_date'])->startOfDay();
        $intervalWeeks = (int) $attributes['repeat_interval'];
        $dates = [];

        if ($attributes['end_mode'] === 'count') {
            $occurrenceCount = (int) $attributes['occurrence_count'];

            for ($position = 0; $position < $occurrenceCount; $position++) {
                $dates[] = $startDate->addWeeks($position * $intervalWeeks);
            }

            return $dates;
        }

        $repeatUntil = CarbonImmutable::parse($attributes['repeat_until'])->startOfDay();

        for ($date = $startDate; $date->lte($repeatUntil); $date = $date->addWeeks($intervalWeeks)) {
            $dates[] = $date;

            if (count($dates) > self::MAX_OCCURRENCES) {
                throw ValidationException::withMessages([
                    'repeat_until' => 'Một chuỗi lịch không được vượt quá '.self::MAX_OCCURRENCES.' buổi.',
                ]);
            }
        }

        if (count($dates) < 2) {
            throw ValidationException::withMessages([
                'repeat_until' => 'Chuỗi lịch phải có ít nhất 2 buổi.',
            ]);
        }

        return $dates;
    }

    /**
     * @param  array<int, int>  $exceptScheduleIds
     * @return array<int, array{position: int, date: string, date_label: string, conflicts: array<int, string>, has_conflict: bool}>
     */
    public function preview(
        array $scheduleData,
        array $recurrenceData,
        Classroom $classroom,
        array $exceptScheduleIds = []
    ): array {
        $dates = $this->dates(array_merge($scheduleData, $recurrenceData));
        $conflictsByDate = $this->scheduleConflicts->conflictsForDates(
            $scheduleData,
            $dates,
            $exceptScheduleIds,
            $classroom
        );

        return collect($dates)
            ->values()
            ->map(function (CarbonImmutable $date, int $position) use ($conflictsByDate): array {
                $conflicts = $conflictsByDate[$date->toDateString()] ?? [];

                return [
                    'position' => $position + 1,
                    'date' => $date->toDateString(),
                    'date_label' => $date->locale('vi')->translatedFormat('l, d/m/Y'),
                    'conflicts' => $conflicts,
                    'has_conflict' => $conflicts !== [],
                ];
            })
            ->all();
    }
}
