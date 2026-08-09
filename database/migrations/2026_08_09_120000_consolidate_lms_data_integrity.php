<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BACKUP_TABLE = 'lms_integrity_backups';

    private const MIGRATION_KEY = '2026_08_09_lms_integrity';

    private const UNIQUE_INDEXES = [
        'class_user' => ['class_user_class_user_unique', ['class_id', 'user_id']],
        'class_course' => ['class_course_class_course_unique', ['class_id', 'course_id']],
        'attendance_data' => ['attendance_data_column_user_unique', ['attendance_column_id', 'user_id']],
        'assignment_submissions' => ['assignment_submissions_assignment_user_unique', ['assignment_id', 'user_id']],
    ];

    public function up(): void
    {
        $this->createBackupTable();

        DB::transaction(function (): void {
            $this->promoteLegacySubmissions();
            $this->deduplicateSimpleTable(
                'class_user',
                ['class_id', 'user_id'],
                'deduplicated_class_membership',
                false,
            );
            $this->deduplicateSimpleTable(
                'class_course',
                ['class_id', 'course_id'],
                'deduplicated_class_course',
                false,
            );
            $this->deduplicateSimpleTable(
                'attendance_data',
                ['attendance_column_id', 'user_id'],
                'deduplicated_attendance_value',
                true,
            );
            $this->deduplicateAssignmentSubmissions();
        });

        foreach (self::UNIQUE_INDEXES as $table => [$index, $columns]) {
            $this->assertNoDuplicates($table, $columns);

            if (! $this->hasIndex($table, $index)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($columns, $index));
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::UNIQUE_INDEXES, true) as $table => [$index]) {
            if ($this->hasIndex($table, $index)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique($index));
            }
        }

        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            return;
        }

        DB::transaction(function (): void {
            $this->restoreCanonicalSnapshots();
            $this->restoreRemovedRows();
            $this->restoreLegacyRows();
        });

        Schema::dropIfExists(self::BACKUP_TABLE);
    }

    private function createBackupTable(): void
    {
        if (Schema::hasTable(self::BACKUP_TABLE)) {
            return;
        }

        Schema::create(self::BACKUP_TABLE, function (Blueprint $table): void {
            $table->id();
            $table->string('migration_key', 80);
            $table->string('source_table', 80);
            $table->unsignedBigInteger('source_id');
            $table->string('action', 100);
            $table->string('canonical_table', 80)->nullable();
            $table->unsignedBigInteger('canonical_id')->nullable();
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(
                ['migration_key', 'source_table', 'source_id', 'action'],
                'lms_integrity_backup_source_action_unique',
            );
        });
    }

    private function promoteLegacySubmissions(): void
    {
        if (! Schema::hasTable('submissions')) {
            return;
        }

        DB::table('submissions')->lazyById(500)->each(function (object $seed): void {
            if (! DB::table('submissions')->where('id', $seed->id)->exists()) {
                return;
            }

            $legacyRows = DB::table('submissions')
                ->where('assignment_id', $seed->assignment_id)
                ->where('student_id', $seed->student_id)
                ->orderByDesc('updated_at')
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->get();
            $first = $legacyRows->first();
            $active = DB::table('assignment_submissions')
                ->where('assignment_id', $first->assignment_id)
                ->where('user_id', $first->student_id)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();

            $eligible = $legacyRows
                ->filter(fn (object $row): bool => ($row->deleted_at ?? null) === null && (bool) ($row->is_final ?? true))
                ->values();
            $promotedLegacyId = null;

            if (! $active && $eligible->isNotEmpty()) {
                $candidate = $eligible->first();
                $canonicalId = DB::table('assignment_submissions')->insertGetId([
                    'assignment_id' => $candidate->assignment_id,
                    'user_id' => $candidate->student_id,
                    'file_path' => $candidate->file_path,
                    'file_disk' => 'public',
                    'original_filename' => $candidate->file_path ? basename($candidate->file_path) : null,
                    'grade' => $candidate->grade,
                    'feedback' => $candidate->feedback,
                    'submitted_at' => $candidate->submitted_at ?? $candidate->updated_at ?? $candidate->created_at,
                    'created_at' => $candidate->created_at,
                    'updated_at' => $candidate->updated_at,
                ]);
                $active = DB::table('assignment_submissions')->where('id', $canonicalId)->first();
                $promotedLegacyId = (int) $candidate->id;
            }

            foreach ($legacyRows as $legacyRow) {
                $action = match (true) {
                    ($legacyRow->deleted_at ?? null) !== null => 'archived_legacy_deleted',
                    ! (bool) ($legacyRow->is_final ?? true) => 'archived_legacy_draft',
                    $promotedLegacyId === (int) $legacyRow->id => 'promoted_legacy_submission',
                    default => 'superseded_legacy_submission',
                };

                $this->backup('submissions', $legacyRow, $action, 'assignment_submissions', $active?->id);
                DB::table('submissions')->where('id', $legacyRow->id)->delete();
            }
        });
    }

    private function deduplicateSimpleTable(
        string $table,
        array $keys,
        string $action,
        bool $preferLatest,
    ): void {
        foreach ($this->duplicateGroups($table, $keys) as $group) {
            $query = $this->applyGroup(DB::table($table), $keys, $group);
            $query = $preferLatest
                ? $query->orderByDesc('updated_at')->orderByDesc('id')
                : $query->orderBy('id');
            $rows = $query->get();
            $canonical = $rows->shift();

            foreach ($rows as $duplicate) {
                $this->backup($table, $duplicate, $action, $table, $canonical->id);
                DB::table($table)->where('id', $duplicate->id)->delete();
            }
        }
    }

    private function deduplicateAssignmentSubmissions(): void
    {
        $keys = ['assignment_id', 'user_id'];

        foreach ($this->duplicateGroups('assignment_submissions', $keys) as $group) {
            $rows = $this->applyGroup(DB::table('assignment_submissions'), $keys, $group)
                ->orderByDesc('updated_at')
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->get();
            $canonical = $rows->shift();
            $updates = $this->submissionFallbackValues($canonical, $rows);

            if ($updates !== []) {
                $this->backup(
                    'assignment_submissions',
                    $canonical,
                    'canonical_before_submission_merge',
                    'assignment_submissions',
                    $canonical->id,
                );
                DB::table('assignment_submissions')->where('id', $canonical->id)->update($updates);
            }

            foreach ($rows as $duplicate) {
                $this->backup(
                    'assignment_submissions',
                    $duplicate,
                    'deduplicated_assignment_submission',
                    'assignment_submissions',
                    $canonical->id,
                );
                DB::table('assignment_submissions')->where('id', $duplicate->id)->delete();
            }
        }
    }

    private function submissionFallbackValues(object $canonical, Collection $duplicates): array
    {
        $updates = [];

        if ($this->isBlank($canonical->file_path ?? null)) {
            $fileSource = $duplicates->first(fn (object $row): bool => ! $this->isBlank($row->file_path ?? null));
            if ($fileSource) {
                foreach (['file_path', 'file_disk', 'original_filename', 'mime_type', 'file_size'] as $field) {
                    if (property_exists($fileSource, $field)) {
                        $updates[$field] = $fileSource->{$field};
                    }
                }
            }
        }

        foreach ([
            'text_answer', 'grade', 'feedback', 'submitted_at', 'ai_suggested_score', 'ai_feedback',
            'ai_rubric_breakdown', 'ai_review_flags', 'ai_grading_notes', 'ai_analyzed_at', 'ai_analysis_history',
        ] as $field) {
            if (! property_exists($canonical, $field) || ! $this->isBlank($canonical->{$field})) {
                continue;
            }

            $source = $duplicates->first(
                fn (object $row): bool => property_exists($row, $field) && ! $this->isBlank($row->{$field}),
            );
            if ($source) {
                $updates[$field] = $source->{$field};
            }
        }

        return $updates;
    }

    private function duplicateGroups(string $table, array $keys): Collection
    {
        return DB::table($table)
            ->select($keys)
            ->groupBy($keys)
            ->havingRaw('COUNT(*) > 1')
            ->get();
    }

    private function applyGroup(Builder $query, array $keys, object $group): Builder
    {
        foreach ($keys as $key) {
            $query->where($key, $group->{$key});
        }

        return $query;
    }

    private function assertNoDuplicates(string $table, array $keys): void
    {
        if ($this->duplicateGroups($table, $keys)->isNotEmpty()) {
            throw new RuntimeException("Không thể tạo unique constraint vì {$table} vẫn còn dữ liệu trùng.");
        }
    }

    private function backup(
        string $sourceTable,
        object $row,
        string $action,
        ?string $canonicalTable,
        ?int $canonicalId,
    ): void {
        DB::table(self::BACKUP_TABLE)->insertOrIgnore([
            'migration_key' => self::MIGRATION_KEY,
            'source_table' => $sourceTable,
            'source_id' => $row->id,
            'action' => $action,
            'canonical_table' => $canonicalTable,
            'canonical_id' => $canonicalId,
            'snapshot' => json_encode((array) $row, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function restoreCanonicalSnapshots(): void
    {
        DB::table(self::BACKUP_TABLE)
            ->where('migration_key', self::MIGRATION_KEY)
            ->where('action', 'canonical_before_submission_merge')
            ->orderBy('id')
            ->get()
            ->each(function (object $backup): void {
                $snapshot = $this->filteredSnapshot($backup->source_table, $backup->snapshot);
                unset($snapshot['id']);
                DB::table($backup->source_table)->where('id', $backup->source_id)->update($snapshot);
            });
    }

    private function restoreRemovedRows(): void
    {
        $actions = [
            'deduplicated_class_membership',
            'deduplicated_class_course',
            'deduplicated_attendance_value',
            'deduplicated_assignment_submission',
        ];

        DB::table(self::BACKUP_TABLE)
            ->where('migration_key', self::MIGRATION_KEY)
            ->whereIn('action', $actions)
            ->orderBy('id')
            ->get()
            ->each(function (object $backup): void {
                DB::table($backup->source_table)->insertOrIgnore(
                    $this->filteredSnapshot($backup->source_table, $backup->snapshot),
                );
            });
    }

    private function restoreLegacyRows(): void
    {
        if (! Schema::hasTable('submissions')) {
            return;
        }

        DB::table(self::BACKUP_TABLE)
            ->where('migration_key', self::MIGRATION_KEY)
            ->where('source_table', 'submissions')
            ->orderBy('id')
            ->get()
            ->each(function (object $backup): void {
                DB::table('submissions')->insertOrIgnore(
                    $this->filteredSnapshot('submissions', $backup->snapshot),
                );
            });
    }

    private function filteredSnapshot(string $table, string $snapshot): array
    {
        $row = json_decode($snapshot, true, 512, JSON_THROW_ON_ERROR);
        $columns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($row, $columns);
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(
            fn (array $definition): bool => strcasecmp((string) $definition['name'], $index) === 0,
        );
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '';
    }
};
