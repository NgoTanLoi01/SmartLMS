<?php

namespace App\Application\Gradebook;

use App\Domain\Gradebook\GradebookException;
use App\Domain\Gradebook\LegacyGradeValueMapper;
use App\Models\AttendanceData;
use App\Models\GradeItem;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProjectLegacyAttendanceGrades
{
    public function __construct(
        private RecordGrade $recordGrade,
        private LegacyGradeValueMapper $mapper,
    ) {}

    /** @param list<array{attendance_column_id:int,user_id:int}> $cells */
    public function handle(int $courseId, array $cells, int $actorId): int
    {
        if ($cells === [] || ! config('gradebook.projection_enabled', true)
            || ! Schema::hasTable('grade_items') || ! Schema::hasTable('grades')) {
            return 0;
        }

        $actor = User::query()->find($actorId);
        if (! $actor) {
            Log::warning('Gradebook legacy projection skipped: actor not found.', ['actor_id' => $actorId]);

            return 0;
        }

        $columnIds = collect($cells)->pluck('attendance_column_id')->unique();
        $userIds = collect($cells)->pluck('user_id')->unique();
        $keys = collect($cells)->mapWithKeys(fn (array $cell): array => [
            $cell['attendance_column_id'].':'.$cell['user_id'] => true,
        ]);
        $items = GradeItem::query()
            ->with(['category', 'period'])
            ->where('course_id', $courseId)
            ->where('source_type', GradeItem::SOURCE_LEGACY_ATTENDANCE)
            ->whereIn('source_id', $columnIds)
            ->get()
            ->groupBy('source_id');
        $data = AttendanceData::query()
            ->whereIn('attendance_column_id', $columnIds)
            ->whereIn('user_id', $userIds)
            ->get()
            ->filter(fn (AttendanceData $row): bool => $keys->has($row->attendance_column_id.':'.$row->user_id));
        $students = User::query()->whereIn('id', $userIds)->get()->keyBy('id');
        $written = 0;

        foreach ($data as $row) {
            $student = $students->get($row->user_id);
            if (! $student) {
                continue;
            }
            foreach ($items->get($row->attendance_column_id, collect()) as $item) {
                try {
                    $mapped = $this->mapper->map($row->value, $item);
                    $sourceVersion = 'attendance_data:'.$row->id.':'.hash('sha256', (string) $row->value.'|'.$row->updated_at);
                    $this->recordGrade->handle(
                        $item,
                        $student,
                        $mapped['status'],
                        $mapped['raw_points'],
                        $actor,
                        'Đồng bộ từ bảng Điểm danh',
                        $sourceVersion,
                        correlationId: "legacy-attendance-projection:{$item->id}:{$student->id}:{$sourceVersion}",
                        source: 'legacy_attendance_projection',
                    );
                    $written++;
                } catch (GradebookException $exception) {
                    if (config('gradebook.read_source') === 'gradebook') {
                        throw $exception;
                    }
                    Log::warning('Gradebook legacy attendance projection skipped.', [
                        'grade_item_id' => $item->id,
                        'student_id' => $student->id,
                        'attendance_data_id' => $row->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return $written;
    }
}
