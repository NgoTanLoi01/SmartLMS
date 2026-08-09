<?php

namespace App\Application\Gradebook;

use App\Domain\Gradebook\GradebookException;
use App\Models\GradeItem;
use App\Models\GradingPeriod;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class SetGradeItemLock
{
    public function handle(GradeItem $item, bool $locked, User $actor, ?int $expectedVersion = null): GradeItem
    {
        $updated = DB::transaction(function () use ($item, $locked, $expectedVersion): GradeItem {
            $current = GradeItem::query()->with('period')->lockForUpdate()->findOrFail($item->id);
            if (! $current->period || $current->period->status === GradingPeriod::STATUS_CLOSED) {
                throw new GradebookException('Kỳ điểm đã đóng. Hãy mở lại kỳ trước khi thay đổi khóa thành phần.');
            }
            if ($expectedVersion !== null && $current->version !== $expectedVersion) {
                throw new GradebookException('Thành phần điểm đã thay đổi ở phiên khác. Hãy tải lại trang.');
            }
            if ($current->is_locked === $locked) {
                return $current;
            }

            $current->forceFill(['is_locked' => $locked, 'version' => $current->version + 1])->save();

            return $current->fresh();
        });

        AuditLogger::log(
            $locked ? 'gradebook_item_locked' : 'gradebook_item_unlocked',
            $updated,
            ['is_locked' => ! $locked],
            ['is_locked' => $locked, 'version' => $updated->version],
            ['actor_id' => $actor->id, 'grading_period_id' => $updated->grading_period_id],
            $locked ? 'Đã khóa thành phần điểm.' : 'Đã mở khóa thành phần điểm.'
        );

        return $updated;
    }
}
