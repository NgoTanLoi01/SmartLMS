<?php

namespace App\Application\Gradebook;

use App\Domain\Gradebook\GradebookException;
use App\Domain\Gradebook\GradeCalculationService;
use App\Models\Grade;
use App\Models\GradeChangeLog;
use App\Models\GradeFinalization;
use App\Models\GradeItem;
use App\Models\GradingPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinalizeGrades
{
    public function __construct(private GradeCalculationService $calculator) {}

    public function finalize(GradingPeriod $period, User $student, User $actor, ?string $correlationId = null): GradeFinalization
    {
        if (! $student->isStudent()) {
            throw new GradebookException('Chỉ tài khoản học viên mới có thể chốt điểm.');
        }

        return DB::transaction(function () use ($period, $student, $actor, $correlationId): GradeFinalization {
            $lockedPeriod = GradingPeriod::query()->lockForUpdate()->findOrFail($period->id);
            if ((int) $lockedPeriod->course_id !== (int) $period->course_id) {
                throw new GradebookException('Period không thuộc course yêu cầu.');
            }

            GradeItem::query()->where('grading_period_id', $lockedPeriod->id)->lockForUpdate()->get();
            Grade::query()
                ->where('user_id', $student->id)
                ->whereIn('grade_item_id', GradeItem::query()->where('grading_period_id', $lockedPeriod->id)->select('id'))
                ->lockForUpdate()
                ->get();

            $finalization = GradeFinalization::query()
                ->where('grading_period_id', $lockedPeriod->id)
                ->where('user_id', $student->id)
                ->lockForUpdate()
                ->first();
            if ($finalization?->state === GradeFinalization::STATE_FINALIZED) {
                if ($correlationId && GradeChangeLog::query()
                    ->where('grading_period_id', $lockedPeriod->id)
                    ->where('user_id', $student->id)
                    ->where('action', 'finalize')
                    ->where('correlation_id', $correlationId)
                    ->exists()) {
                    return $finalization;
                }
                throw new GradebookException('Điểm đã được chốt.');
            }

            $lockedPeriod->unsetRelations();
            $calculation = $this->calculator->calculate($lockedPeriod, $student->id);
            $snapshot = [
                'formula' => $calculation['formula'],
                'categories' => $calculation['categories'],
            ];
            $hashPayload = [
                'period_id' => $lockedPeriod->id,
                'student_id' => $student->id,
                'formula_snapshot' => $snapshot,
                'grade_snapshot' => $calculation['grades'],
                'unrounded_score' => $calculation['unrounded_score'],
            ];
            $hash = hash('sha256', json_encode($hashPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $before = $finalization?->toArray();
            $attributes = [
                'course_id' => $lockedPeriod->course_id,
                'state' => GradeFinalization::STATE_FINALIZED,
                'final_score' => $calculation['final_score'],
                'unrounded_score' => $calculation['unrounded_score'],
                'formula_snapshot' => $snapshot,
                'grade_snapshot' => $calculation['grades'],
                'calculation_hash' => $hash,
                'version' => ($finalization?->version ?? 0) + 1,
                'finalized_by' => $actor->id,
                'finalized_at' => now(),
                'reopened_by' => null,
                'reopened_at' => null,
                'reopen_reason' => null,
            ];

            if ($finalization) {
                $finalization->fill($attributes)->save();
            } else {
                $finalization = GradeFinalization::create([
                    'grading_period_id' => $lockedPeriod->id,
                    'user_id' => $student->id,
                    ...$attributes,
                ]);
            }

            GradeChangeLog::create([
                'grading_period_id' => $lockedPeriod->id,
                'user_id' => $student->id,
                'actor_id' => $actor->id,
                'action' => 'finalize',
                'before' => $before,
                'after' => $finalization->fresh()->toArray(),
                'source' => 'application',
                'correlation_id' => $correlationId ?? (string) Str::uuid(),
                'request_id' => request()?->header('X-Request-ID'),
            ]);

            return $finalization->fresh();
        });
    }

    public function reopen(GradingPeriod $period, User $student, User $actor, string $reason): GradeFinalization
    {
        if (trim($reason) === '') {
            throw new GradebookException('Lý do reopen là bắt buộc.');
        }

        return DB::transaction(function () use ($period, $student, $actor, $reason): GradeFinalization {
            $finalization = GradeFinalization::query()
                ->where('grading_period_id', $period->id)
                ->where('user_id', $student->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($finalization->state !== GradeFinalization::STATE_FINALIZED) {
                throw new GradebookException('Chỉ điểm đã finalized mới có thể reopen.');
            }

            $before = $finalization->toArray();
            $finalization->forceFill([
                'state' => GradeFinalization::STATE_REOPENED,
                'version' => $finalization->version + 1,
                'reopened_by' => $actor->id,
                'reopened_at' => now(),
                'reopen_reason' => trim($reason),
            ])->save();

            GradeChangeLog::create([
                'grading_period_id' => $period->id,
                'user_id' => $student->id,
                'actor_id' => $actor->id,
                'action' => 'reopen',
                'before' => $before,
                'after' => $finalization->fresh()->toArray(),
                'reason' => trim($reason),
                'source' => 'application',
                'correlation_id' => (string) Str::uuid(),
                'request_id' => request()?->header('X-Request-ID'),
            ]);

            return $finalization->fresh();
        });
    }
}
