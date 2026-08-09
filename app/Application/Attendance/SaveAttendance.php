<?php

namespace App\Application\Attendance;

use App\Jobs\NotifyFrequentAttendanceAbsences;
use App\Models\AttendanceColumn;
use App\Models\AttendanceData;
use App\Models\Course;
use App\Support\AttendanceStatus;
use Illuminate\Support\Facades\DB;

class SaveAttendance
{
    /**
     * @param  array{data?: array<int|string, array<int|string, mixed>>, notes?: array<int|string, array<int|string, mixed>>}  $input
     */
    public function handle(Course $course, array $input): bool
    {
        $submittedData = collect($input['data'] ?? []);

        if ($submittedData->isEmpty()) {
            return false;
        }

        $columnIds = $submittedData->keys()->map(fn ($id) => (int) $id)->unique()->values();
        $columns = AttendanceColumn::query()
            ->where('course_id', $course->id)
            ->whereIn('id', $columnIds)
            ->get(['id', 'type'])
            ->keyBy('id');
        abort_unless($columns->count() === $columnIds->count(), 422, 'Dữ liệu chứa cột không thuộc khóa học.');

        $submittedUserIds = $submittedData
            ->flatMap(fn (array $users) => array_keys($users))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $allowedUserIds = DB::table('class_user')
            ->join('class_course', 'class_user.class_id', '=', 'class_course.class_id')
            ->where('class_course.course_id', $course->id)
            ->whereIn('class_user.user_id', $submittedUserIds)
            ->distinct()
            ->pluck('class_user.user_id')
            ->map(fn ($id) => (int) $id);
        abort_unless($allowedUserIds->count() === $submittedUserIds->count(), 422, 'Dữ liệu chứa học viên không thuộc khóa học.');

        $existing = AttendanceData::query()
            ->whereIn('attendance_column_id', $columnIds)
            ->whereIn('user_id', $submittedUserIds)
            ->get(['attendance_column_id', 'user_id', 'value', 'note'])
            ->keyBy(fn (AttendanceData $row): string => $row->attendance_column_id.':'.$row->user_id);
        $notes = $input['notes'] ?? [];
        $rows = [];
        $attendanceUserIds = [];

        foreach ($submittedData as $columnId => $users) {
            $column = $columns->get((int) $columnId);
            foreach ($users as $userId => $value) {
                $userId = (int) $userId;
                $savedValue = $column->type === 'attendance' ? AttendanceStatus::normalize($value) : $value;
                $current = $existing->get($column->id.':'.$userId);
                $noteWasSubmitted = array_key_exists((string) $columnId, $notes)
                    && array_key_exists((string) $userId, $notes[(string) $columnId]);
                $savedNote = $noteWasSubmitted ? $notes[(string) $columnId][(string) $userId] : $current?->note;

                if ($current && (string) $current->value === (string) $savedValue && (string) $current->note === (string) $savedNote) {
                    continue;
                }

                $rows[] = [
                    'attendance_column_id' => $column->id,
                    'user_id' => $userId,
                    'value' => $savedValue,
                    'note' => $savedNote,
                ];
                if ($column->type === 'attendance') {
                    $attendanceUserIds[] = $userId;
                }
            }
        }

        if ($rows !== []) {
            DB::transaction(fn () => AttendanceData::query()->upsert(
                $rows,
                ['attendance_column_id', 'user_id'],
                ['value', 'note'],
            ));
        }

        $attendanceUserIds = array_values(array_unique($attendanceUserIds));
        if ($attendanceUserIds !== []) {
            NotifyFrequentAttendanceAbsences::dispatch((int) $course->id, $attendanceUserIds)->afterCommit();
        }

        return $rows !== [];
    }
}
