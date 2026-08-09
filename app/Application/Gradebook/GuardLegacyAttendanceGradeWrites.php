<?php

namespace App\Application\Gradebook;

use App\Domain\Gradebook\GradebookException;
use App\Domain\Gradebook\LegacyGradeValueMapper;
use App\Models\Course;
use App\Models\GradeFinalization;
use App\Models\GradeItem;
use App\Models\GradingPeriod;
use Illuminate\Support\Facades\Schema;

class GuardLegacyAttendanceGradeWrites
{
    public function __construct(private LegacyGradeValueMapper $mapper) {}

    /**
     * @param  list<array{attendance_column_id:int,user_id:int,value:mixed,note:mixed}>  $rows
     * @return list<array{attendance_column_id:int,user_id:int}>
     */
    public function handle(Course $course, array $rows): array
    {
        if (! config('gradebook.projection_enabled', true)
            || ! Schema::hasTable('grade_items')
            || ! Schema::hasTable('grade_finalizations')) {
            return [];
        }

        $columnIds = collect($rows)->pluck('attendance_column_id')->unique()->values();
        $items = GradeItem::query()
            ->with(['category', 'period'])
            ->where('course_id', $course->id)
            ->where('source_type', GradeItem::SOURCE_LEGACY_ATTENDANCE)
            ->whereIn('source_id', $columnIds)
            ->get();
        if ($items->isEmpty()) {
            return [];
        }

        $userIds = collect($rows)->pluck('user_id')->unique()->values();
        $finalized = GradeFinalization::query()
            ->whereIn('grading_period_id', $items->pluck('grading_period_id'))
            ->whereIn('user_id', $userIds)
            ->where('state', GradeFinalization::STATE_FINALIZED)
            ->get(['grading_period_id', 'user_id'])
            ->keyBy(fn (GradeFinalization $row): string => $row->grading_period_id.':'.$row->user_id);
        $itemsByColumn = $items->groupBy('source_id');
        $mapped = [];

        foreach ($rows as $row) {
            foreach ($itemsByColumn->get($row['attendance_column_id'], collect()) as $item) {
                if ($item->period?->status !== GradingPeriod::STATUS_OPEN) {
                    throw new GradebookException("Kỳ điểm của {$item->name} chưa mở hoặc đã đóng.");
                }
                if ($item->is_locked) {
                    throw new GradebookException("Thành phần {$item->name} đã bị khóa.");
                }
                if ($finalized->has($item->grading_period_id.':'.$row['user_id'])) {
                    throw new GradebookException("Điểm của học viên đã được chốt. Hãy mở lại điểm trước khi sửa {$item->name} trong Điểm danh.");
                }
                $this->mapper->map($row['value'], $item);
                $mapped[$row['attendance_column_id'].':'.$row['user_id']] = [
                    'attendance_column_id' => (int) $row['attendance_column_id'],
                    'user_id' => (int) $row['user_id'],
                ];
            }
        }

        return array_values($mapped);
    }
}
