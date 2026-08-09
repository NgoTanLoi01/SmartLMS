<?php

namespace App\Application\Gradebook;

use App\Domain\Gradebook\GradebookException;
use App\Models\Assignments;
use App\Models\AttendanceColumn;
use App\Models\Course;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\GradingPeriod;
use App\Models\Quiz;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\GradebookMigrationService;
use Illuminate\Support\Str;

class ConfigureGradebook
{
    public function __construct(private GradebookMigrationService $migration) {}

    /**
     * @param  array<string,mixed>  $input
     * @return array{result:array<string,mixed>,manifest:array<string,mixed>,checksum:string,period:?GradingPeriod}
     */
    public function handle(Course $course, User $actor, array $input, bool $dryRun): array
    {
        $manifest = $this->manifest($course, $actor, $input);
        $result = $this->migration->backfill($manifest, $dryRun);

        if ($result['errors'] !== []) {
            throw new GradebookException((string) ($result['errors'][0]['error'] ?? 'Dữ liệu nguồn chưa sẵn sàng để tạo Sổ điểm.'));
        }

        $period = $dryRun
            ? null
            : GradingPeriod::query()
                ->where('course_id', $course->id)
                ->where('code', $manifest['period']['code'])
                ->firstOrFail();

        if ($period) {
            AuditLogger::log(
                'gradebook_period_created',
                $period,
                null,
                $period->only(['course_id', 'code', 'name', 'status', 'missing_policy', 'rounding_precision', 'rounding_mode']),
                ['category_count' => count($manifest['categories']), 'item_count' => count($manifest['items'])],
                'Đã tạo kỳ điểm và mapping nguồn cho Sổ điểm.'
            );
        }

        return [
            'result' => $result,
            'manifest' => $manifest,
            'checksum' => $this->checksum($manifest),
            'period' => $period,
        ];
    }

    /** @param array<string,mixed> $manifest */
    public function checksum(array $manifest): string
    {
        unset($manifest['generated_at'], $manifest['run_id']);

        return hash('sha256', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string,mixed>  $input
     * @return array<string,mixed>
     */
    private function manifest(Course $course, User $actor, array $input): array
    {
        $categories = collect($input['categories'])
            ->map(fn (array $category, int $index): array => [
                'code' => strtolower(trim($category['code'])),
                'name' => trim($category['name']),
                'weight_percent' => (string) $category['weight_percent'],
                'allow_over_max' => (bool) ($category['allow_over_max'] ?? false),
                'position' => $index + 1,
            ])
            ->values();
        $categoryCodes = $categories->pluck('code');
        $items = collect($input['items'])
            ->filter(fn (array $item): bool => (bool) ($item['enabled'] ?? false))
            ->map(function (array $item, int $index) use ($course, $categoryCodes): array {
                $categoryCode = strtolower(trim($item['category_code']));
                if (! $categoryCodes->contains($categoryCode)) {
                    throw new GradebookException("Nhóm điểm {$categoryCode} không tồn tại trong cấu hình.");
                }

                return $this->mappedItem($course, $item, $index + 1, $categoryCode);
            })
            ->values();

        if ($items->isEmpty()) {
            throw new GradebookException('Hãy chọn ít nhất một thành phần điểm để tạo Sổ điểm.');
        }
        $emptyCategory = $categoryCodes->first(fn (string $code): bool => ! $items->contains('category_code', $code));
        if ($emptyCategory !== null) {
            throw new GradebookException("Nhóm điểm {$emptyCategory} chưa có thành phần nào được chọn.");
        }

        $weightTotal = $categories->reduce(
            fn (string $total, array $category): string => bcadd($total, $category['weight_percent'], 4),
            '0'
        );
        if (bccomp($weightTotal, '100', 4) !== 0) {
            throw new GradebookException("Tổng trọng số nhóm điểm phải bằng 100%, hiện là {$weightTotal}%.");
        }

        $period = $input['period'];

        return [
            'version' => 1,
            'run_id' => (string) Str::uuid(),
            'generated_at' => now()->toIso8601String(),
            'approved' => true,
            'approved_by' => $actor->id,
            'course_id' => $course->id,
            'period' => [
                'code' => strtolower(trim($period['code'])),
                'name' => trim($period['name']),
                'starts_at' => $period['starts_at'] ?: null,
                'ends_at' => $period['ends_at'] ?: null,
                'status' => GradingPeriod::STATUS_OPEN,
                'missing_policy' => $period['missing_policy'],
                'rounding_precision' => (int) $period['rounding_precision'],
                'rounding_mode' => 'half_up',
            ],
            'categories' => $categories->all(),
            'items' => $items->all(),
        ];
    }

    /**
     * @param  array<string,mixed>  $input
     * @return array<string,mixed>
     */
    private function mappedItem(Course $course, array $input, int $position, string $categoryCode): array
    {
        $sourceType = (string) $input['source_type'];
        $sourceId = (int) $input['source_id'];
        $itemType = (string) $input['item_type'];
        $base = [
            'code' => strtolower(trim($input['code'])),
            'name' => trim($input['name']),
            'category_code' => $categoryCode,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'item_weight' => (string) $input['item_weight'],
            'position' => $position,
            'is_published' => true,
        ];

        return match ($sourceType) {
            GradeItem::SOURCE_LEGACY_ATTENDANCE => $this->legacyItem($course, $sourceId, $itemType, $input, $base),
            GradeItem::SOURCE_ASSIGNMENT => $this->assignmentItem($course, $sourceId, $itemType, $base),
            GradeItem::SOURCE_QUIZ => $this->quizItem($course, $sourceId, $itemType, $input, $base),
            default => throw new GradebookException('Nguồn thành phần điểm không được hỗ trợ.'),
        };
    }

    /** @param array<string,mixed> $input @param array<string,mixed> $base @return array<string,mixed> */
    private function legacyItem(Course $course, int $sourceId, string $itemType, array $input, array $base): array
    {
        $column = AttendanceColumn::query()->where('course_id', $course->id)->where('type', 'grade')->find($sourceId);
        if (! $column) {
            throw new GradebookException('Cột điểm Điểm danh không thuộc khóa học hoặc không còn tồn tại.');
        }
        if (! in_array($itemType, [GradeItem::TYPE_HS1, GradeItem::TYPE_HS2, GradeItem::TYPE_EXAM, GradeItem::TYPE_MANUAL], true)) {
            throw new GradebookException('Loại mapping cho cột điểm Điểm danh không hợp lệ.');
        }
        $absencePolicy = $input['absence_policy'] ?? null;
        if (! in_array($absencePolicy, [Grade::STATUS_MISSING, Grade::STATUS_EXCUSED, 'zero'], true)) {
            throw new GradebookException('Hãy chọn cách xử lý giá trị vắng cho cột điểm Điểm danh.');
        }

        return [...$base, 'item_type' => $itemType, 'max_points' => '10', 'absence_policy' => $absencePolicy];
    }

    /** @param array<string,mixed> $base @return array<string,mixed> */
    private function assignmentItem(Course $course, int $sourceId, string $itemType, array $base): array
    {
        $assignment = Assignments::query()->where('course_id', $course->id)->find($sourceId);
        if (! $assignment || $itemType !== GradeItem::TYPE_ASSIGNMENT) {
            throw new GradebookException('Mapping Assignment không hợp lệ hoặc không thuộc khóa học.');
        }

        return [...$base, 'item_type' => GradeItem::TYPE_ASSIGNMENT, 'max_points' => (string) $assignment->grading_scale];
    }

    /** @param array<string,mixed> $input @param array<string,mixed> $base @return array<string,mixed> */
    private function quizItem(Course $course, int $sourceId, string $itemType, array $input, array $base): array
    {
        $quiz = Quiz::query()->where('course_id', $course->id)->find($sourceId);
        if (! $quiz || ! in_array($itemType, [GradeItem::TYPE_QUIZ, GradeItem::TYPE_EXAM], true)) {
            throw new GradebookException('Mapping Quiz/Thi không hợp lệ hoặc không thuộc khóa học.');
        }
        $attemptPolicy = $input['attempt_policy'] ?? 'highest_released';
        if (! in_array($attemptPolicy, ['highest_released', 'latest_released', 'first_released'], true)) {
            throw new GradebookException('Chính sách chọn lượt làm bài không hợp lệ.');
        }

        return [...$base, 'item_type' => $itemType, 'max_points' => '10', 'attempt_policy' => $attemptPolicy];
    }
}
