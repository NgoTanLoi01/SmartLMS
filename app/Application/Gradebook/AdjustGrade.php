<?php

namespace App\Application\Gradebook;

use App\Domain\Gradebook\GradebookException;
use App\Domain\Gradebook\GradeCalculationService;
use App\Models\Grade;
use App\Models\GradeAdjustment;
use App\Models\GradeChangeLog;
use App\Models\GradeFinalization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdjustGrade
{
    public function __construct(private GradeCalculationService $calculator) {}

    public function handle(
        Grade $grade,
        string $type,
        string $amount,
        string $reason,
        User $actor,
        string $idempotencyKey,
    ): GradeAdjustment {
        return DB::transaction(function () use ($grade, $type, $amount, $reason, $actor, $idempotencyKey): GradeAdjustment {
            $lockedGrade = Grade::query()->with('item.category')->lockForUpdate()->findOrFail($grade->id);
            $this->assertWritable($lockedGrade);
            $this->validate($type, $amount, $reason, $idempotencyKey);

            $existing = GradeAdjustment::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $before = $lockedGrade->only(['status', 'raw_points', 'effective_points', 'version']);
            $adjustment = GradeAdjustment::create([
                'grading_period_id' => $lockedGrade->item->grading_period_id,
                'user_id' => $lockedGrade->user_id,
                'grade_id' => $lockedGrade->id,
                'type' => $type,
                'scope' => GradeAdjustment::SCOPE_ITEM,
                'amount' => $amount,
                'reason' => trim($reason),
                'adjusted_by' => $actor->id,
                'adjusted_at' => now(),
                'idempotency_key' => $idempotencyKey,
            ]);

            $adjustments = GradeAdjustment::query()->where('grade_id', $lockedGrade->id)->orderBy('id')->get();
            $lockedGrade->forceFill([
                'effective_points' => $this->calculator->effectiveItemPoints(
                    $lockedGrade,
                    $adjustments,
                    (bool) $lockedGrade->item->category->allow_over_max,
                ),
                'version' => $lockedGrade->version + 1,
            ])->save();

            GradeChangeLog::create([
                'grade_id' => $lockedGrade->id,
                'grade_item_id' => $lockedGrade->grade_item_id,
                'grading_period_id' => $lockedGrade->item->grading_period_id,
                'user_id' => $lockedGrade->user_id,
                'actor_id' => $actor->id,
                'action' => 'adjust',
                'before' => $before,
                'after' => [
                    ...$lockedGrade->fresh()->only(['status', 'raw_points', 'effective_points', 'version']),
                    'adjustment_id' => $adjustment->id,
                    'adjustment_type' => $adjustment->type,
                    'adjustment_amount' => $adjustment->amount,
                ],
                'reason' => trim($reason),
                'source' => 'manual',
                'correlation_id' => (string) Str::uuid(),
                'request_id' => request()?->header('X-Request-ID'),
            ]);

            return $adjustment;
        });
    }

    public function reverse(GradeAdjustment $target, string $reason, User $actor, string $idempotencyKey): GradeAdjustment
    {
        return DB::transaction(function () use ($target, $reason, $actor, $idempotencyKey): GradeAdjustment {
            $existing = GradeAdjustment::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
            if (GradeAdjustment::query()->where('reverses_adjustment_id', $target->id)->exists()) {
                throw new GradebookException('Adjustment này đã được reversal.');
            }
            if (! $target->grade_id) {
                throw new GradebookException('Foundation hiện chỉ reversal adjustment ở item scope.');
            }

            $grade = Grade::query()->with('item.category')->lockForUpdate()->findOrFail($target->grade_id);
            $this->assertWritable($grade);
            if (trim($reason) === '') {
                throw new GradebookException('Lý do reversal là bắt buộc.');
            }

            $reversal = GradeAdjustment::create([
                'grading_period_id' => $target->grading_period_id,
                'user_id' => $target->user_id,
                'grade_id' => $target->grade_id,
                'type' => GradeAdjustment::TYPE_REVERSAL,
                'scope' => $target->scope,
                'amount' => '0',
                'reason' => trim($reason),
                'adjusted_by' => $actor->id,
                'adjusted_at' => now(),
                'reverses_adjustment_id' => $target->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            $adjustments = GradeAdjustment::query()->where('grade_id', $grade->id)->orderBy('id')->get();
            $grade->forceFill([
                'effective_points' => $this->calculator->effectiveItemPoints(
                    $grade,
                    $adjustments,
                    (bool) $grade->item->category->allow_over_max,
                ),
                'version' => $grade->version + 1,
            ])->save();

            GradeChangeLog::create([
                'grade_id' => $grade->id,
                'grade_item_id' => $grade->grade_item_id,
                'grading_period_id' => $target->grading_period_id,
                'user_id' => $grade->user_id,
                'actor_id' => $actor->id,
                'action' => 'adjust',
                'after' => ['reversal_id' => $reversal->id, 'reverses_adjustment_id' => $target->id],
                'reason' => trim($reason),
                'source' => 'manual',
                'correlation_id' => (string) Str::uuid(),
            ]);

            return $reversal;
        });
    }

    private function assertWritable(Grade $grade): void
    {
        if (GradeFinalization::query()
            ->where('grading_period_id', $grade->item->grading_period_id)
            ->where('user_id', $grade->user_id)
            ->where('state', GradeFinalization::STATE_FINALIZED)
            ->exists()) {
            throw new GradebookException('Điểm đã được chốt. Hãy reopen trước khi adjustment.');
        }
    }

    private function validate(string $type, string $amount, string $reason, string $idempotencyKey): void
    {
        if (! in_array($type, [GradeAdjustment::TYPE_BONUS, GradeAdjustment::TYPE_PENALTY, GradeAdjustment::TYPE_OVERRIDE], true)) {
            throw new GradebookException('Loại adjustment không hợp lệ.');
        }
        if (! preg_match('/^\d+(?:\.\d{1,4})?$/', $amount)) {
            throw new GradebookException('Giá trị adjustment không hợp lệ.');
        }
        if (trim($reason) === '') {
            throw new GradebookException('Lý do adjustment là bắt buộc.');
        }
        if (trim($idempotencyKey) === '') {
            throw new GradebookException('Idempotency key là bắt buộc.');
        }
    }
}
