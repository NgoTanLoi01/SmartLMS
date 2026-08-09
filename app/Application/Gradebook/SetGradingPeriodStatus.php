<?php

namespace App\Application\Gradebook;

use App\Domain\Gradebook\GradebookException;
use App\Models\GradeFinalization;
use App\Models\GradeItem;
use App\Models\GradingPeriod;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class SetGradingPeriodStatus
{
    public function close(GradingPeriod $period, User $actor): GradingPeriod
    {
        $updated = DB::transaction(function () use ($period): GradingPeriod {
            $current = GradingPeriod::query()->lockForUpdate()->findOrFail($period->id);
            if ($current->status === GradingPeriod::STATUS_CLOSED) {
                return $current;
            }
            if ($current->status !== GradingPeriod::STATUS_OPEN) {
                throw new GradebookException('Chỉ kỳ điểm đang mở mới có thể đóng.');
            }

            $studentIds = DB::table('class_user')
                ->join('class_course', 'class_course.class_id', '=', 'class_user.class_id')
                ->join('users', 'users.id', '=', 'class_user.user_id')
                ->where('class_course.course_id', $current->course_id)
                ->where('users.role', User::ROLE_STUDENT)
                ->distinct()
                ->pluck('class_user.user_id');
            if ($studentIds->isEmpty()) {
                throw new GradebookException('Khóa học chưa có học viên để chốt kỳ điểm.');
            }

            $finalizedCount = GradeFinalization::query()
                ->where('grading_period_id', $current->id)
                ->whereIn('user_id', $studentIds)
                ->where('state', GradeFinalization::STATE_FINALIZED)
                ->distinct('user_id')
                ->count('user_id');
            if ($finalizedCount !== $studentIds->count()) {
                $remaining = $studentIds->count() - $finalizedCount;
                throw new GradebookException("Còn {$remaining} học viên chưa chốt điểm. Không thể đóng kỳ.");
            }

            GradeItem::query()->where('grading_period_id', $current->id)->update([
                'is_locked' => true,
                'version' => DB::raw('version + 1'),
            ]);
            $current->forceFill(['status' => GradingPeriod::STATUS_CLOSED])->save();

            return $current->fresh();
        });

        AuditLogger::log('gradebook_period_closed', $updated, ['status' => GradingPeriod::STATUS_OPEN], ['status' => $updated->status], ['actor_id' => $actor->id], 'Đã đóng toàn bộ kỳ điểm.');

        return $updated;
    }

    public function reopen(GradingPeriod $period, User $actor, string $reason): GradingPeriod
    {
        if (trim($reason) === '') {
            throw new GradebookException('Lý do mở lại kỳ điểm là bắt buộc.');
        }

        $updated = DB::transaction(function () use ($period): GradingPeriod {
            $current = GradingPeriod::query()->lockForUpdate()->findOrFail($period->id);
            if ($current->status !== GradingPeriod::STATUS_CLOSED) {
                throw new GradebookException('Chỉ kỳ điểm đã đóng mới có thể mở lại.');
            }
            $current->forceFill(['status' => GradingPeriod::STATUS_OPEN])->save();

            return $current->fresh();
        });

        AuditLogger::log('gradebook_period_reopened', $updated, ['status' => GradingPeriod::STATUS_CLOSED], ['status' => $updated->status], ['actor_id' => $actor->id, 'reason' => trim($reason)], 'Đã mở lại kỳ điểm.');

        return $updated;
    }
}
