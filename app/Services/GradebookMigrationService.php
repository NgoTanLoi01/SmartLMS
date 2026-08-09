<?php

namespace App\Services;

use App\Application\Gradebook\RecordGrade;
use App\Domain\Gradebook\GradebookException;
use App\Models\Assignments;
use App\Models\AttendanceColumn;
use App\Models\Course;
use App\Models\Grade;
use App\Models\GradeCategory;
use App\Models\GradeItem;
use App\Models\GradingPeriod;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GradebookMigrationService
{
    public function __construct(private RecordGrade $recordGrade) {}

    /** @return array<string,mixed> */
    public function discover(Course $course): array
    {
        $legacyColumns = AttendanceColumn::query()
            ->where('course_id', $course->id)
            ->where('type', 'grade')
            ->orderBy('order')
            ->get()
            ->map(function (AttendanceColumn $column): array {
                $values = DB::table('attendance_data')
                    ->where('attendance_column_id', $column->id)
                    ->select('value')
                    ->get()
                    ->map(fn (object $row): string => trim((string) $row->value));

                return [
                    'source_type' => GradeItem::SOURCE_LEGACY_ATTENDANCE,
                    'source_id' => $column->id,
                    'name' => $column->name,
                    'suggested_item_type' => $this->suggestedLegacyType($column->name),
                    'numeric_values' => $values->filter(fn (string $value): bool => $this->parseNumber($value) !== null)->count(),
                    'blank_values' => $values->filter(fn (string $value): bool => $value === '')->count(),
                    'absence_values' => $values->filter(fn (string $value): bool => $this->isAbsence($value))->count(),
                    'invalid_values' => $values->filter(fn (string $value): bool => $value !== '' && ! $this->isAbsence($value) && $this->parseNumber($value) === null)->unique()->take(20)->values()->all(),
                    'requires_mapping' => true,
                ];
            });

        $assignments = Assignments::query()
            ->where('course_id', $course->id)
            ->notArchived()
            ->withCount(['submissions', 'submissions as graded_count' => fn ($query) => $query->whereNotNull('grade')])
            ->orderBy('id')
            ->get()
            ->map(fn (Assignments $assignment): array => [
                'source_type' => GradeItem::SOURCE_ASSIGNMENT,
                'source_id' => $assignment->id,
                'name' => $assignment->title,
                'max_points' => $assignment->grading_scale,
                'submission_count' => $assignment->submissions_count,
                'graded_count' => $assignment->graded_count,
                'requires_mapping' => true,
            ]);

        $quizzes = Quiz::query()
            ->where('course_id', $course->id)
            ->notArchived()
            ->withCount(['attempts', 'sessions'])
            ->orderBy('id')
            ->get()
            ->map(fn (Quiz $quiz): array => [
                'source_type' => GradeItem::SOURCE_QUIZ,
                'source_id' => $quiz->id,
                'name' => $quiz->title,
                'attempt_count' => $quiz->attempts_count,
                'has_session' => $quiz->sessions_count > 0,
                'suggested_attempt_policy' => 'highest_released',
                'requires_explicit_quiz_or_exam' => true,
                'requires_mapping' => true,
            ]);

        return [
            'version' => 1,
            'generated_at' => now()->toIso8601String(),
            'approved' => false,
            'approved_by' => null,
            'course_id' => $course->id,
            'course_title' => $course->title,
            'period' => null,
            'categories' => [],
            'items' => [],
            'discovery' => [
                'legacy_grade_columns' => $legacyColumns->values()->all(),
                'assignments' => $assignments->values()->all(),
                'quizzes' => $quizzes->values()->all(),
            ],
        ];
    }

    /** @param array<string,mixed> $manifest
     * @return array<string,mixed>
     */
    public function backfill(array $manifest, bool $dryRun): array
    {
        $this->validateManifest($manifest);
        $course = Course::findOrFail((int) $manifest['course_id']);
        $actor = User::findOrFail((int) $manifest['approved_by']);
        if (! $actor->isAdmin() && (! $actor->isTeacher() || (int) $course->teacher_id !== (int) $actor->id)) {
            throw new GradebookException('Người duyệt manifest không có quyền quản lý course.');
        }
        $planned = $this->sourceRows($manifest);
        $errors = $planned->whereNotNull('error')->values();
        $summary = [
            'run_id' => (string) ($manifest['run_id'] ?? Str::uuid()),
            'course_id' => $course->id,
            'dry_run' => $dryRun,
            'planned_grades' => $planned->count(),
            'graded' => $planned->where('status', Grade::STATUS_GRADED)->count(),
            'ungraded' => $planned->where('status', Grade::STATUS_UNGRADED)->count(),
            'missing' => $planned->where('status', Grade::STATUS_MISSING)->count(),
            'excused' => $planned->where('status', Grade::STATUS_EXCUSED)->count(),
            'errors' => $errors->all(),
            'written' => 0,
        ];

        if ($errors->isNotEmpty() || $dryRun) {
            return $summary;
        }

        DB::transaction(function () use ($manifest, $course, $actor, $planned, &$summary): void {
            [$period, $items] = $this->materializeStructure($manifest, $course);

            foreach ($planned as $row) {
                $item = $items->get($row['item_code']);
                if (! $item) {
                    throw new GradebookException("Không tìm thấy item {$row['item_code']} trong manifest.");
                }
                $this->recordGrade->handle(
                    $item,
                    User::findOrFail($row['user_id']),
                    $row['status'],
                    $row['raw_points'],
                    $actor,
                    'Shadow backfill từ '.$row['source_type'],
                    $row['source_version'],
                    correlationId: "gradebook-backfill:{$period->id}:{$item->id}:{$row['user_id']}:{$row['source_version']}",
                    source: 'backfill',
                );
                $summary['written']++;
            }
        });

        return $summary;
    }

    /** @param array<string,mixed> $manifest
     * @return array<string,mixed>
     */
    public function reconcile(array $manifest): array
    {
        $this->validateManifest($manifest);
        $period = GradingPeriod::query()
            ->where('course_id', $manifest['course_id'])
            ->where('code', $manifest['period']['code'])
            ->firstOrFail();
        $items = GradeItem::query()->where('grading_period_id', $period->id)->get()->keyBy('code');
        $mismatches = [];
        $expected = $this->sourceRows($manifest);

        foreach ($expected as $row) {
            if ($row['error']) {
                $mismatches[] = ['reason' => 'source_error', ...$row];

                continue;
            }
            $item = $items->get($row['item_code']);
            $grade = $item ? Grade::query()->where('grade_item_id', $item->id)->where('user_id', $row['user_id'])->first() : null;
            $pointsMatch = $row['raw_points'] === null
                ? $grade?->raw_points === null
                : ($grade?->raw_points !== null && bccomp((string) $grade->raw_points, $row['raw_points'], 4) === 0);

            if (! $grade || $grade->status !== $row['status'] || ! $pointsMatch || $grade->source_version !== $row['source_version']) {
                $mismatches[] = [
                    'item_code' => $row['item_code'],
                    'user_id' => $row['user_id'],
                    'expected_status' => $row['status'],
                    'expected_points' => $row['raw_points'],
                    'actual_status' => $grade?->status,
                    'actual_points' => $grade?->raw_points,
                    'reason' => 'grade_mismatch',
                ];
            }
        }

        return [
            'course_id' => (int) $manifest['course_id'],
            'period_id' => $period->id,
            'expected_count' => $expected->count(),
            'matched_count' => $expected->count() - count($mismatches),
            'mismatch_count' => count($mismatches),
            'mismatches' => $mismatches,
            'passed' => $mismatches === [],
            'checksum' => hash('sha256', json_encode($expected->values()->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
        ];
    }

    /** @param array<string,mixed> $manifest */
    private function validateManifest(array $manifest): void
    {
        if (($manifest['version'] ?? null) !== 1 || ($manifest['approved'] ?? false) !== true) {
            throw new GradebookException('Manifest phải có version=1 và approved=true.');
        }
        if (empty($manifest['approved_by']) || empty($manifest['course_id']) || empty($manifest['period']['code'])) {
            throw new GradebookException('Manifest thiếu approved_by, course_id hoặc period.');
        }
        if (empty($manifest['categories']) || empty($manifest['items'])) {
            throw new GradebookException('Manifest phải có category và item mapping explicit.');
        }
        $categoryCodes = collect($manifest['categories'])->pluck('code');
        if ($categoryCodes->duplicates()->isNotEmpty()) {
            throw new GradebookException('Manifest có category code trùng.');
        }
        $items = collect($manifest['items']);
        if ($items->pluck('code')->duplicates()->isNotEmpty()) {
            throw new GradebookException('Manifest có item code trùng.');
        }
        if ($items->map(fn (array $item): string => $item['source_type'].':'.$item['source_id'])->duplicates()->isNotEmpty()) {
            throw new GradebookException('Manifest map một source nhiều lần trong cùng period.');
        }
        if ($items->contains(fn (array $item): bool => ! $categoryCodes->contains($item['category_code']))) {
            throw new GradebookException('Grade Item tham chiếu category không tồn tại.');
        }
    }

    /** @param array<string,mixed> $manifest
     * @return Collection<int,array<string,mixed>>
     */
    private function sourceRows(array $manifest): Collection
    {
        $courseId = (int) $manifest['course_id'];

        return collect($manifest['items'])->flatMap(function (array $mapping) use ($courseId): Collection {
            return match ($mapping['source_type']) {
                GradeItem::SOURCE_LEGACY_ATTENDANCE => $this->legacyRows($mapping, $courseId),
                GradeItem::SOURCE_ASSIGNMENT => $this->assignmentRows($mapping, $courseId),
                GradeItem::SOURCE_QUIZ => $this->quizRows($mapping, $courseId),
                default => collect([[
                    'item_code' => $mapping['code'],
                    'user_id' => 0,
                    'status' => null,
                    'raw_points' => null,
                    'source_type' => $mapping['source_type'],
                    'source_version' => null,
                    'error' => 'Source type không hỗ trợ backfill.',
                ]]),
            };
        })->values();
    }

    /** @param array<string,mixed> $mapping */
    private function legacyRows(array $mapping, int $courseId): Collection
    {
        $column = AttendanceColumn::query()->findOrFail((int) $mapping['source_id']);
        if ((int) $column->course_id !== $courseId || $column->type !== 'grade') {
            throw new GradebookException('Attendance column không thuộc course hoặc không phải cột điểm.');
        }

        return DB::table('attendance_data')
            ->where('attendance_column_id', $mapping['source_id'])
            ->orderBy('id')
            ->get()
            ->map(function (object $row) use ($mapping): array {
                $raw = trim((string) $row->value);
                $points = $this->parseNumber($raw);
                $status = Grade::STATUS_GRADED;
                $error = null;

                if ($raw === '') {
                    $status = Grade::STATUS_UNGRADED;
                } elseif ($this->isAbsence($raw)) {
                    $absencePolicy = $mapping['absence_policy'] ?? null;
                    $status = match ($absencePolicy) {
                        'missing' => Grade::STATUS_MISSING,
                        'excused' => Grade::STATUS_EXCUSED,
                        'zero' => Grade::STATUS_GRADED,
                        default => null,
                    };
                    $points = $absencePolicy === 'zero' ? '0' : null;
                    if ($status === null) {
                        $error = 'Giá trị vắng chưa có absence_policy explicit.';
                    }
                } elseif ($points === null) {
                    $error = "Giá trị không phải số: {$raw}";
                }

                if ($points !== null && (bccomp($points, '0', 4) < 0 || bccomp($points, (string) $mapping['max_points'], 4) > 0)) {
                    $error = "Điểm {$raw} ngoài thang 0-{$mapping['max_points']}.";
                }

                return [
                    'item_code' => $mapping['code'],
                    'user_id' => (int) $row->user_id,
                    'status' => $status,
                    'raw_points' => $status === Grade::STATUS_GRADED ? $points : null,
                    'source_type' => GradeItem::SOURCE_LEGACY_ATTENDANCE,
                    'source_version' => 'attendance_data:'.$row->id.':'.hash('sha256', (string) $row->value.'|'.($row->updated_at ?? '')),
                    'error' => $error,
                    'raw_value' => $raw,
                ];
            });
    }

    /** @param array<string,mixed> $mapping */
    private function assignmentRows(array $mapping, int $courseId): Collection
    {
        $assignment = Assignments::findOrFail((int) $mapping['source_id']);
        if ((int) $assignment->course_id !== $courseId) {
            throw new GradebookException('Assignment source không thuộc course trong manifest.');
        }

        return $assignment->submissions()->orderBy('id')->get()->map(fn ($submission): array => [
            'item_code' => $mapping['code'],
            'user_id' => (int) $submission->user_id,
            'status' => $submission->grade === null ? Grade::STATUS_UNGRADED : Grade::STATUS_GRADED,
            'raw_points' => $submission->grade === null ? null : (string) $submission->grade,
            'source_type' => GradeItem::SOURCE_ASSIGNMENT,
            'source_version' => 'assignment_submission:'.$submission->id.':'.hash('sha256', (string) $submission->grade.'|'.$submission->updated_at),
            'error' => $submission->grade !== null && bccomp((string) $submission->grade, (string) $assignment->grading_scale, 4) > 0
                ? 'Assignment grade vượt grading_scale.'
                : null,
        ]);
    }

    /** @param array<string,mixed> $mapping */
    private function quizRows(array $mapping, int $courseId): Collection
    {
        $policy = $mapping['attempt_policy'] ?? 'highest_released';
        if (! in_array($policy, ['highest_released', 'latest_released', 'first_released'], true)) {
            throw new GradebookException("Attempt policy {$policy} chưa được hỗ trợ tự động.");
        }
        $quiz = Quiz::query()->findOrFail((int) $mapping['source_id']);
        if ((int) $quiz->course_id !== $courseId) {
            throw new GradebookException('Quiz source không thuộc course trong manifest.');
        }

        return QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->resultsReleased()
            ->whereNotNull('score')
            ->get()
            ->groupBy('user_id')
            ->map(function (Collection $attempts) use ($mapping, $policy): array {
                $selected = match ($policy) {
                    'highest_released' => $attempts->sort(function (QuizAttempt $left, QuizAttempt $right): int {
                        $scoreComparison = bccomp((string) $right->score, (string) $left->score, 4);

                        return $scoreComparison !== 0
                            ? $scoreComparison
                            : ($right->completed_at?->getTimestamp() ?? 0) <=> ($left->completed_at?->getTimestamp() ?? 0);
                    })->first(),
                    'latest_released' => $attempts->sortByDesc('completed_at')->first(),
                    'first_released' => $attempts->sortBy('completed_at')->first(),
                };

                return [
                    'item_code' => $mapping['code'],
                    'user_id' => (int) $selected->user_id,
                    'status' => Grade::STATUS_GRADED,
                    'raw_points' => (string) $selected->score,
                    'source_type' => GradeItem::SOURCE_QUIZ,
                    'source_version' => 'quiz_attempt:'.$selected->id.':'.hash('sha256', (string) $selected->score.'|'.$selected->updated_at),
                    'error' => null,
                ];
            })->values();
    }

    /** @param array<string,mixed> $manifest
     * @return array{GradingPeriod,Collection<string,GradeItem>}
     */
    private function materializeStructure(array $manifest, Course $course): array
    {
        $periodData = $manifest['period'];
        $period = GradingPeriod::firstOrCreate(
            ['course_id' => $course->id, 'code' => $periodData['code']],
            [
                'name' => $periodData['name'],
                'starts_at' => $periodData['starts_at'] ?? null,
                'ends_at' => $periodData['ends_at'] ?? null,
                'status' => $periodData['status'] ?? GradingPeriod::STATUS_DRAFT,
                'missing_policy' => $periodData['missing_policy'] ?? GradingPeriod::MISSING_BLOCK,
                'rounding_precision' => $periodData['rounding_precision'] ?? 1,
                'rounding_mode' => $periodData['rounding_mode'] ?? 'half_up',
            ]
        );
        $this->assertPeriodMatchesManifest($period, $periodData);

        $categories = collect($manifest['categories'])->mapWithKeys(function (array $data) use ($course, $period): array {
            $category = GradeCategory::firstOrCreate(
                ['grading_period_id' => $period->id, 'code' => $data['code']],
                [
                    'course_id' => $course->id,
                    'name' => $data['name'],
                    'weight_percent' => $data['weight_percent'],
                    'aggregation_method' => GradeCategory::AGGREGATION_WEIGHTED_MEAN,
                    'allow_over_max' => $data['allow_over_max'] ?? false,
                    'position' => $data['position'] ?? 0,
                    'is_active' => true,
                ]
            );
            $this->assertCategoryMatchesManifest($category, $data, $course->id);

            return [$data['code'] => $category];
        });

        $items = collect($manifest['items'])->mapWithKeys(function (array $data) use ($course, $period, $categories): array {
            $item = GradeItem::firstOrCreate(
                ['grading_period_id' => $period->id, 'code' => $data['code']],
                [
                    'course_id' => $course->id,
                    'grade_category_id' => $categories->get($data['category_code'])->id,
                    'name' => $data['name'],
                    'item_type' => $data['item_type'],
                    'source_type' => $data['source_type'],
                    'source_id' => $data['source_id'],
                    'max_points' => $data['max_points'],
                    'item_weight' => $data['item_weight'] ?? 1,
                    'attempt_policy' => $data['attempt_policy'] ?? null,
                    'absence_policy' => $data['absence_policy'] ?? null,
                    'due_at' => $data['due_at'] ?? null,
                    'position' => $data['position'] ?? 0,
                    'is_published' => $data['is_published'] ?? false,
                ]
            );
            $this->assertItemMatchesManifest($item, $data, $course->id, $categories->get($data['category_code'])->id);

            return [$data['code'] => $item];
        });

        return [$period, $items];
    }

    /** @param array<string,mixed> $data */
    private function assertPeriodMatchesManifest(GradingPeriod $period, array $data): void
    {
        $expected = [
            'name' => (string) $data['name'],
            'status' => (string) ($data['status'] ?? GradingPeriod::STATUS_DRAFT),
            'missing_policy' => (string) ($data['missing_policy'] ?? GradingPeriod::MISSING_BLOCK),
            'rounding_precision' => (int) ($data['rounding_precision'] ?? 1),
            'rounding_mode' => (string) ($data['rounding_mode'] ?? 'half_up'),
        ];

        foreach ($expected as $field => $value) {
            if ($period->{$field} !== $value) {
                throw new GradebookException("Grading period đã tồn tại nhưng {$field} không khớp manifest.");
            }
        }

        foreach (['starts_at', 'ends_at'] as $field) {
            $actual = $period->{$field}?->toIso8601String();
            $manifestValue = empty($data[$field]) ? null : Carbon::parse($data[$field])->toIso8601String();
            if ($actual !== $manifestValue) {
                throw new GradebookException("Grading period đã tồn tại nhưng {$field} không khớp manifest.");
            }
        }
    }

    /** @param array<string,mixed> $data */
    private function assertCategoryMatchesManifest(GradeCategory $category, array $data, int $courseId): void
    {
        if ((int) $category->course_id !== $courseId
            || $category->name !== (string) $data['name']
            || bccomp((string) $category->weight_percent, (string) $data['weight_percent'], 4) !== 0
            || (bool) $category->allow_over_max !== (bool) ($data['allow_over_max'] ?? false)
            || (int) $category->position !== (int) ($data['position'] ?? 0)
            || ! $category->is_active) {
            throw new GradebookException("Grade category {$data['code']} đã tồn tại nhưng không khớp manifest.");
        }
    }

    /** @param array<string,mixed> $data */
    private function assertItemMatchesManifest(GradeItem $item, array $data, int $courseId, int $categoryId): void
    {
        if ((int) $item->course_id !== $courseId
            || (int) $item->grade_category_id !== $categoryId
            || $item->name !== (string) $data['name']
            || $item->item_type !== (string) $data['item_type']
            || $item->source_type !== (string) $data['source_type']
            || (int) $item->source_id !== (int) $data['source_id']
            || bccomp((string) $item->max_points, (string) $data['max_points'], 4) !== 0
            || bccomp((string) $item->item_weight, (string) ($data['item_weight'] ?? 1), 4) !== 0
            || $item->attempt_policy !== ($data['attempt_policy'] ?? null)
            || $item->absence_policy !== ($data['absence_policy'] ?? null)
            || (int) $item->position !== (int) ($data['position'] ?? 0)
            || (bool) $item->is_published !== (bool) ($data['is_published'] ?? false)) {
            throw new GradebookException("Grade item {$data['code']} đã tồn tại nhưng không khớp manifest.");
        }
    }

    private function parseNumber(string $value): ?string
    {
        $normalized = str_replace(',', '.', trim($value));

        return preg_match('/^\d+(?:\.\d{1,4})?$/', $normalized) ? $normalized : null;
    }

    private function isAbsence(string $value): bool
    {
        return strtolower(Str::ascii(trim($value))) === 'vang';
    }

    private function suggestedLegacyType(string $name): ?string
    {
        return match (strtolower(Str::ascii(trim($name)))) {
            'hs1' => GradeItem::TYPE_HS1,
            'hs2' => GradeItem::TYPE_HS2,
            'thi' => GradeItem::TYPE_EXAM,
            default => null,
        };
    }
}
