<?php

namespace App\Queries\Gradebook;

use App\Models\GradeChangeLog;
use App\Models\GradingPeriod;

class GradebookHistoryQuery
{
    public function get(GradingPeriod $period, ?int $studentId = null, ?int $itemId = null, int $perPage = 50)
    {
        return GradeChangeLog::query()
            ->with(['student:id,name,email', 'actor:id,name', 'item:id,name'])
            ->where('grading_period_id', $period->id)
            ->when($studentId, fn ($query) => $query->where('user_id', $studentId))
            ->when($itemId, fn ($query) => $query->where('grade_item_id', $itemId))
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
