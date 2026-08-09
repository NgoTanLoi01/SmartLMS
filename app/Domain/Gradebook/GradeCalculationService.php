<?php

namespace App\Domain\Gradebook;

use App\Models\Grade;
use App\Models\GradeAdjustment;
use App\Models\GradeCategory;
use App\Models\GradingPeriod;
use Illuminate\Support\Collection;

class GradeCalculationService
{
    private const SCALE = 12;

    /**
     * @return array{unrounded_score:string,final_score:string,categories:list<array<string,mixed>>,formula:array<string,mixed>,grades:list<array<string,mixed>>}
     */
    public function calculate(GradingPeriod $period, int $studentId): array
    {
        $period->loadMissing([
            'categories' => fn ($query) => $query->where('is_active', true)->orderBy('position'),
            'categories.items' => fn ($query) => $query->where('is_published', true)->orderBy('position'),
            'categories.items.grades' => fn ($query) => $query->where('user_id', $studentId),
        ]);

        $adjustments = GradeAdjustment::query()
            ->where('grading_period_id', $period->id)
            ->where('user_id', $studentId)
            ->orderBy('id')
            ->get();

        return $this->calculateLoaded($period, $studentId, $adjustments);
    }

    /**
     * @param  Collection<int, GradeAdjustment>  $adjustments
     * @return array{unrounded_score:string,final_score:string,categories:list<array<string,mixed>>,formula:array<string,mixed>,grades:list<array<string,mixed>>}
     */
    public function calculateLoaded(GradingPeriod $period, int $studentId, Collection $adjustments): array
    {
        $categories = $period->categories->where('is_active', true)->values();
        $weightTotal = $categories->reduce(
            fn (string $carry, GradeCategory $category): string => bcadd($carry, (string) $category->weight_percent, 4),
            '0'
        );

        if (bccomp($weightTotal, '100', 4) !== 0) {
            throw new GradebookException("Tổng trọng số category phải bằng 100%, hiện là {$weightTotal}%.");
        }

        $activeAdjustments = $this->activeAdjustments($adjustments);
        $categoryResults = [];
        $gradeSnapshot = [];
        $weightedFinal = '0';

        foreach ($categories as $category) {
            $weightedItems = '0';
            $itemWeightTotal = '0';
            $itemSnapshots = [];

            foreach ($category->items->where('is_published', true) as $item) {
                if ((int) $item->course_id !== (int) $period->course_id
                    || (int) $item->grading_period_id !== (int) $period->id
                    || (int) $item->grade_category_id !== (int) $category->id) {
                    throw new GradebookException('Grade Item không cùng scope với period/category.');
                }

                /** @var Grade|null $grade */
                $grade = $item->grades->firstWhere('user_id', $studentId);
                $status = $grade?->status ?? Grade::STATUS_UNGRADED;
                $points = $grade?->raw_points;
                $included = true;

                if (in_array($status, [Grade::STATUS_EXCUSED, Grade::STATUS_EXCLUDED], true)) {
                    $included = false;
                } elseif (in_array($status, [Grade::STATUS_UNGRADED, Grade::STATUS_MISSING], true)) {
                    if ($period->missing_policy === GradingPeriod::MISSING_BLOCK) {
                        throw new GradebookException("Grade Item {$item->name} còn thiếu/chưa chấm.");
                    }
                    if ($period->missing_policy === GradingPeriod::MISSING_EXCLUDE) {
                        $included = false;
                    } else {
                        $points = '0';
                    }
                } elseif ($status !== Grade::STATUS_GRADED || $points === null) {
                    throw new GradebookException("Trạng thái điểm của {$item->name} không hợp lệ.");
                }

                $effectivePoints = $points;
                if ($included) {
                    $effectivePoints = $this->applyAdjustments(
                        (string) $points,
                        $activeAdjustments->where('scope', GradeAdjustment::SCOPE_ITEM)
                            ->where('grade_id', $grade?->id),
                        '0',
                        $category->allow_over_max ? null : (string) $item->max_points,
                    );
                    $normalized = bcdiv(
                        bcmul($effectivePoints, '10', self::SCALE),
                        (string) $item->max_points,
                        self::SCALE
                    );
                    $weightedItems = bcadd(
                        $weightedItems,
                        bcmul($normalized, (string) $item->item_weight, self::SCALE),
                        self::SCALE
                    );
                    $itemWeightTotal = bcadd($itemWeightTotal, (string) $item->item_weight, self::SCALE);
                }

                $itemSnapshot = [
                    'grade_id' => $grade?->id,
                    'grade_item_id' => $item->id,
                    'grade_item_version' => $item->version,
                    'grade_version' => $grade?->version,
                    'status' => $status,
                    'raw_points' => $points,
                    'effective_points' => $effectivePoints,
                    'max_points' => (string) $item->max_points,
                    'item_weight' => (string) $item->item_weight,
                    'included' => $included,
                    'source_version' => $grade?->source_version,
                ];
                $itemSnapshots[] = $itemSnapshot;
                $gradeSnapshot[] = $itemSnapshot;
            }

            if (bccomp($itemWeightTotal, '0', self::SCALE) === 0) {
                throw new GradebookException("Category {$category->name} không có điểm đủ điều kiện.");
            }

            $categoryScore = bcdiv($weightedItems, $itemWeightTotal, self::SCALE);
            $categoryScore = $this->applyAdjustments(
                $categoryScore,
                $activeAdjustments->where('scope', GradeAdjustment::SCOPE_CATEGORY)
                    ->where('grade_category_id', $category->id),
                '0',
                $category->allow_over_max ? null : '10',
            );
            $weightedFinal = bcadd(
                $weightedFinal,
                bcdiv(
                    bcmul($categoryScore, (string) $category->weight_percent, self::SCALE),
                    '100',
                    self::SCALE
                ),
                self::SCALE
            );
            $categoryResults[] = [
                'grade_category_id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'weight_percent' => (string) $category->weight_percent,
                'score' => $categoryScore,
                'items' => $itemSnapshots,
            ];
        }

        $unrounded = $this->applyAdjustments(
            $weightedFinal,
            $activeAdjustments->where('scope', GradeAdjustment::SCOPE_FINAL),
            '0',
            '10',
        );

        return [
            'unrounded_score' => $unrounded,
            'final_score' => $this->round($unrounded, $period->rounding_precision, $period->rounding_mode),
            'categories' => $categoryResults,
            'formula' => [
                'missing_policy' => $period->missing_policy,
                'rounding_precision' => $period->rounding_precision,
                'rounding_mode' => $period->rounding_mode,
                'calculation_version' => $period->calculation_version,
            ],
            'grades' => $gradeSnapshot,
        ];
    }

    /** @param Collection<int, GradeAdjustment> $adjustments */
    public function effectiveItemPoints(Grade $grade, Collection $adjustments, bool $allowOverMax = false): string
    {
        $grade->loadMissing('item');

        return $this->applyAdjustments(
            (string) $grade->raw_points,
            $this->activeAdjustments($adjustments)->where('scope', GradeAdjustment::SCOPE_ITEM)->where('grade_id', $grade->id),
            '0',
            $allowOverMax ? null : (string) $grade->item->max_points,
        );
    }

    /** @param Collection<int, GradeAdjustment> $adjustments */
    private function activeAdjustments(Collection $adjustments): Collection
    {
        $reversedIds = $adjustments
            ->where('type', GradeAdjustment::TYPE_REVERSAL)
            ->pluck('reverses_adjustment_id')
            ->filter()
            ->map(fn ($id): int => (int) $id);

        return $adjustments->reject(
            fn (GradeAdjustment $adjustment): bool => $adjustment->type === GradeAdjustment::TYPE_REVERSAL
                || $reversedIds->contains((int) $adjustment->id)
        );
    }

    /** @param Collection<int, GradeAdjustment> $adjustments */
    private function applyAdjustments(string $base, Collection $adjustments, string $minimum, ?string $maximum): string
    {
        $value = $base;

        foreach ($adjustments as $adjustment) {
            $value = match ($adjustment->type) {
                GradeAdjustment::TYPE_BONUS => bcadd($value, (string) $adjustment->amount, self::SCALE),
                GradeAdjustment::TYPE_PENALTY => bcsub($value, (string) $adjustment->amount, self::SCALE),
                GradeAdjustment::TYPE_OVERRIDE => (string) $adjustment->amount,
                default => $value,
            };
        }

        if (bccomp($value, $minimum, self::SCALE) < 0) {
            $value = $minimum;
        }
        if ($maximum !== null && bccomp($value, $maximum, self::SCALE) > 0) {
            $value = $maximum;
        }

        return $value;
    }

    private function round(string $value, int $precision, string $mode): string
    {
        if ($mode !== 'half_up') {
            throw new GradebookException("Rounding mode {$mode} chưa được hỗ trợ.");
        }

        $precision = max(0, min(4, $precision));
        $factor = '1'.str_repeat('0', $precision);
        $scaled = bcmul($value, $factor, self::SCALE);
        $integer = bcadd($scaled, '0.5', 0);

        return bcdiv($integer, $factor, $precision);
    }
}
