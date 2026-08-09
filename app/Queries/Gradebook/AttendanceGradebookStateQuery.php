<?php

namespace App\Queries\Gradebook;

use App\Models\Course;
use App\Models\GradeFinalization;
use App\Models\GradeItem;
use App\Models\GradingPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AttendanceGradebookStateQuery
{
    /**
     * @param  Collection<int,mixed>  $columns
     * @param  Collection<int,mixed>  $students
     * @return array{mapped_columns:array<int,string>,locked_cells:array<int,array<int,string>>}
     */
    public function get(Course $course, Collection $columns, Collection $students): array
    {
        $empty = ['mapped_columns' => [], 'locked_cells' => []];
        if (! Schema::hasTable('grade_items') || ! Schema::hasTable('grade_finalizations')) {
            return $empty;
        }

        $items = GradeItem::query()
            ->with('period')
            ->where('course_id', $course->id)
            ->where('source_type', GradeItem::SOURCE_LEGACY_ATTENDANCE)
            ->whereIn('source_id', $columns->where('type', 'grade')->pluck('id'))
            ->get();
        if ($items->isEmpty()) {
            return $empty;
        }

        $finalized = GradeFinalization::query()
            ->whereIn('grading_period_id', $items->pluck('grading_period_id'))
            ->whereIn('user_id', $students->pluck('id'))
            ->where('state', GradeFinalization::STATE_FINALIZED)
            ->get()
            ->groupBy('grading_period_id');
        $mapped = [];
        $locks = [];

        foreach ($items as $item) {
            $mapped[(int) $item->source_id] = $item->name;
            if ($item->is_locked || $item->period?->status !== GradingPeriod::STATUS_OPEN) {
                foreach ($students as $student) {
                    $locks[(int) $item->source_id][(int) $student->id] = $item->period?->status === GradingPeriod::STATUS_CLOSED
                        ? 'Kỳ điểm đã đóng'
                        : 'Thành phần điểm đã khóa';
                }

                continue;
            }

            foreach ($finalized->get($item->grading_period_id, collect()) as $row) {
                $locks[(int) $item->source_id][(int) $row->user_id] = 'Điểm học viên đã chốt';
            }
        }

        return ['mapped_columns' => $mapped, 'locked_cells' => $locks];
    }
}
