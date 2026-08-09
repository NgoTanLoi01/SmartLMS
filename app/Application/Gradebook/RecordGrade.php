<?php

namespace App\Application\Gradebook;

use App\Domain\Gradebook\GradebookException;
use App\Models\Grade;
use App\Models\GradeChangeLog;
use App\Models\GradeFinalization;
use App\Models\GradeItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordGrade
{
    public function handle(
        GradeItem $item,
        User $student,
        string $status,
        ?string $rawPoints,
        User $actor,
        ?string $reason = null,
        ?string $sourceVersion = null,
        ?int $expectedVersion = null,
        ?string $correlationId = null,
        string $source = 'manual',
    ): Grade {
        return DB::transaction(function () use (
            $item, $student, $status, $rawPoints, $actor, $reason, $sourceVersion,
            $expectedVersion, $correlationId, $source
        ): Grade {
            $lockedItem = GradeItem::query()->with(['category', 'period'])->lockForUpdate()->findOrFail($item->id);
            $this->assertWritable($lockedItem, $student);
            $this->validateGrade($lockedItem, $status, $rawPoints);

            $grade = Grade::query()
                ->where('grade_item_id', $lockedItem->id)
                ->where('user_id', $student->id)
                ->lockForUpdate()
                ->first();
            $before = $grade ? $this->snapshot($grade) : null;

            if ($grade
                && $grade->status === $status
                && $this->samePoints($grade->raw_points, $status === Grade::STATUS_GRADED ? $rawPoints : null)
                && $grade->source_version === $sourceVersion) {
                return $grade;
            }

            if ($grade && $expectedVersion !== null && $grade->version !== $expectedVersion) {
                throw new GradebookException('Điểm đã được thay đổi ở phiên khác. Hãy tải lại trước khi lưu.');
            }

            $attributes = [
                'status' => $status,
                'raw_points' => $status === Grade::STATUS_GRADED ? $rawPoints : null,
                'effective_points' => $status === Grade::STATUS_GRADED ? $rawPoints : null,
                'source_version' => $sourceVersion,
                'graded_by' => $actor->id,
                'graded_at' => $status === Grade::STATUS_GRADED ? now() : null,
                'version' => ($grade?->version ?? 0) + 1,
            ];

            if ($grade) {
                $grade->fill($attributes)->save();
            } else {
                $grade = Grade::create([
                    'grade_item_id' => $lockedItem->id,
                    'user_id' => $student->id,
                    ...$attributes,
                ]);
            }

            GradeChangeLog::firstOrCreate(
                [
                    'correlation_id' => $correlationId ?? (string) Str::uuid(),
                    'action' => $before ? 'update' : 'create',
                    'grade_id' => $grade->id,
                ],
                [
                    'grade_item_id' => $lockedItem->id,
                    'grading_period_id' => $lockedItem->grading_period_id,
                    'user_id' => $student->id,
                    'actor_id' => $actor->id,
                    'before' => $before,
                    'after' => $this->snapshot($grade),
                    'reason' => $reason,
                    'source' => $source,
                    'request_id' => request()?->header('X-Request-ID'),
                ]
            );

            return $grade->fresh();
        });
    }

    private function assertWritable(GradeItem $item, User $student): void
    {
        if (! $student->isStudent()) {
            throw new GradebookException('Chỉ tài khoản học viên mới có thể nhận điểm.');
        }
        if (! $item->category || ! $item->period
            || (int) $item->category->course_id !== (int) $item->course_id
            || (int) $item->category->grading_period_id !== (int) $item->grading_period_id
            || (int) $item->period->course_id !== (int) $item->course_id) {
            throw new GradebookException('Grade Item, category và period không cùng scope course.');
        }
        if ($item->is_locked) {
            throw new GradebookException('Grade Item đang bị khóa.');
        }

        if (GradeFinalization::query()
            ->where('grading_period_id', $item->grading_period_id)
            ->where('user_id', $student->id)
            ->where('state', GradeFinalization::STATE_FINALIZED)
            ->exists()) {
            throw new GradebookException('Điểm đã được chốt. Hãy reopen trước khi sửa.');
        }
    }

    private function validateGrade(GradeItem $item, string $status, ?string $rawPoints): void
    {
        $statuses = [
            Grade::STATUS_UNGRADED,
            Grade::STATUS_MISSING,
            Grade::STATUS_EXCUSED,
            Grade::STATUS_GRADED,
            Grade::STATUS_EXCLUDED,
        ];
        if (! in_array($status, $statuses, true)) {
            throw new GradebookException('Trạng thái điểm không hợp lệ.');
        }

        if ($status !== Grade::STATUS_GRADED) {
            if ($rawPoints !== null) {
                throw new GradebookException('Chỉ trạng thái graded mới được có điểm.');
            }

            return;
        }

        if ($rawPoints === null || ! preg_match('/^\d+(?:\.\d{1,4})?$/', $rawPoints)) {
            throw new GradebookException('Điểm phải là số không âm với tối đa 4 chữ số thập phân.');
        }
        if (bccomp($rawPoints, '0', 4) < 0) {
            throw new GradebookException('Điểm không được âm.');
        }
        if (! $item->category->allow_over_max && bccomp($rawPoints, (string) $item->max_points, 4) > 0) {
            throw new GradebookException("Điểm không được vượt quá {$item->max_points}.");
        }
    }

    private function samePoints(?string $left, ?string $right): bool
    {
        if ($left === null || $right === null) {
            return $left === $right;
        }

        return bccomp($left, $right, 4) === 0;
    }

    /** @return array<string,mixed> */
    private function snapshot(Grade $grade): array
    {
        return $grade->only([
            'id', 'grade_item_id', 'user_id', 'status', 'raw_points', 'effective_points',
            'source_version', 'graded_by', 'graded_at', 'version',
        ]);
    }
}
