<?php

namespace App\Domain\Gradebook;

use App\Models\Grade;
use App\Models\GradeItem;
use Illuminate\Support\Str;

class LegacyGradeValueMapper
{
    /** @return array{status:string,raw_points:?string} */
    public function map(?string $value, GradeItem $item): array
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return ['status' => Grade::STATUS_UNGRADED, 'raw_points' => null];
        }

        if (strtolower(Str::ascii($raw)) === 'vang') {
            return match ($item->absence_policy) {
                Grade::STATUS_MISSING => ['status' => Grade::STATUS_MISSING, 'raw_points' => null],
                Grade::STATUS_EXCUSED => ['status' => Grade::STATUS_EXCUSED, 'raw_points' => null],
                'zero' => ['status' => Grade::STATUS_GRADED, 'raw_points' => '0'],
                default => throw new GradebookException("Thành phần {$item->name} chưa cấu hình cách xử lý giá trị vắng."),
            };
        }

        $points = str_replace(',', '.', $raw);
        if (! preg_match('/^\d+(?:\.\d{1,4})?$/', $points)) {
            throw new GradebookException("Giá trị “{$raw}” của {$item->name} không phải điểm hợp lệ.");
        }
        if (! $item->category?->allow_over_max && bccomp($points, (string) $item->max_points, 4) > 0) {
            throw new GradebookException("Điểm {$raw} vượt thang {$item->max_points} của {$item->name}.");
        }

        return ['status' => Grade::STATUS_GRADED, 'raw_points' => $points];
    }
}
