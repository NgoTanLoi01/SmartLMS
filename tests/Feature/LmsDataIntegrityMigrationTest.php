<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LmsDataIntegrityMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('LmsDataIntegrityMigrationTest chỉ được phép chạy trên SQLite cô lập.');
        }

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            foreach ([
                'lms_integrity_backups', 'submissions', 'assignment_submissions',
                'attendance_data', 'class_course', 'class_user',
            ] as $table) {
                Schema::dropIfExists($table);
            }
        }

        parent::tearDown();
    }

    public function test_migration_checkpoints_deduplicates_promotes_legacy_and_adds_unique_constraints(): void
    {
        DB::table('class_user')->insert([
            ['id' => 1, 'class_id' => 10, 'user_id' => 20, 'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
            ['id' => 2, 'class_id' => 10, 'user_id' => 20, 'created_at' => '2026-08-01 08:01:00', 'updated_at' => '2026-08-01 08:01:00'],
        ]);
        DB::table('class_course')->insert([
            ['id' => 1, 'class_id' => 10, 'course_id' => 30, 'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
            ['id' => 2, 'class_id' => 10, 'course_id' => 30, 'created_at' => '2026-08-01 08:01:00', 'updated_at' => '2026-08-01 08:01:00'],
        ]);
        DB::table('attendance_data')->insert([
            ['id' => 1, 'attendance_column_id' => 40, 'user_id' => 20, 'value' => 'absent', 'note' => null, 'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00'],
            ['id' => 2, 'attendance_column_id' => 40, 'user_id' => 20, 'value' => 'present', 'note' => 'latest', 'created_at' => '2026-08-01 08:01:00', 'updated_at' => '2026-08-01 08:01:00'],
        ]);
        DB::table('assignment_submissions')->insert([
            [
                'id' => 1, 'assignment_id' => 50, 'user_id' => 20, 'file_path' => 'old.pdf',
                'file_disk' => 'public', 'text_answer' => 'Nội dung cần giữ', 'grade' => 7,
                'feedback' => 'old', 'submitted_at' => '2026-08-01 08:00:00',
                'created_at' => '2026-08-01 08:00:00', 'updated_at' => '2026-08-01 08:00:00',
            ],
            [
                'id' => 2, 'assignment_id' => 50, 'user_id' => 20, 'file_path' => 'latest.pdf',
                'file_disk' => 'public', 'text_answer' => null, 'grade' => 7,
                'feedback' => 'latest', 'submitted_at' => '2026-08-01 08:01:00',
                'created_at' => '2026-08-01 08:01:00', 'updated_at' => '2026-08-01 08:01:00',
            ],
        ]);
        DB::table('submissions')->insert([
            [
                'id' => 1, 'assignment_id' => 60, 'student_id' => 21, 'file_path' => 'legacy-final.pdf',
                'grade' => 8, 'feedback' => 'legacy', 'submitted_at' => '2026-07-01 08:00:00',
                'is_final' => true, 'deleted_at' => null,
                'created_at' => '2026-07-01 08:00:00', 'updated_at' => '2026-07-01 08:00:00',
            ],
            [
                'id' => 2, 'assignment_id' => 61, 'student_id' => 21, 'file_path' => 'legacy-draft.pdf',
                'grade' => null, 'feedback' => null, 'submitted_at' => '2026-07-01 09:00:00',
                'is_final' => false, 'deleted_at' => null,
                'created_at' => '2026-07-01 09:00:00', 'updated_at' => '2026-07-01 09:00:00',
            ],
        ]);

        $migration = require database_path('migrations/2026_08_09_120000_consolidate_lms_data_integrity.php');
        $migration->up();

        $this->assertDatabaseCount('class_user', 1);
        $this->assertDatabaseHas('class_user', ['id' => 1]);
        $this->assertDatabaseCount('class_course', 1);
        $this->assertDatabaseHas('class_course', ['id' => 1]);
        $this->assertDatabaseCount('attendance_data', 1);
        $this->assertDatabaseHas('attendance_data', ['id' => 2, 'value' => 'present', 'note' => 'latest']);
        $this->assertDatabaseHas('assignment_submissions', [
            'id' => 2,
            'file_path' => 'latest.pdf',
            'text_answer' => 'Nội dung cần giữ',
            'feedback' => 'latest',
        ]);
        $this->assertDatabaseHas('assignment_submissions', [
            'assignment_id' => 60,
            'user_id' => 21,
            'file_path' => 'legacy-final.pdf',
            'grade' => 8,
        ]);
        $this->assertDatabaseMissing('assignment_submissions', ['assignment_id' => 61, 'user_id' => 21]);
        $this->assertDatabaseCount('submissions', 0);
        $this->assertDatabaseCount('lms_integrity_backups', 7);

        $this->assertUniqueConstraint('class_user', ['class_id' => 10, 'user_id' => 20]);
        $this->assertUniqueConstraint('class_course', ['class_id' => 10, 'course_id' => 30]);
        $this->assertUniqueConstraint('attendance_data', [
            'attendance_column_id' => 40, 'user_id' => 20, 'value' => 'late',
        ]);
        $this->assertUniqueConstraint('assignment_submissions', [
            'assignment_id' => 50, 'user_id' => 20, 'file_path' => 'duplicate.pdf', 'file_disk' => 'public',
        ]);

        $migration->down();

        $this->assertFalse(Schema::hasTable('lms_integrity_backups'));
        $this->assertDatabaseCount('class_user', 2);
        $this->assertDatabaseCount('class_course', 2);
        $this->assertDatabaseCount('attendance_data', 2);
        $this->assertDatabaseHas('assignment_submissions', ['id' => 1, 'file_path' => 'old.pdf']);
        $this->assertDatabaseHas('assignment_submissions', ['id' => 2, 'text_answer' => null]);
        $this->assertDatabaseCount('submissions', 2);

        DB::table('class_user')->insert(['class_id' => 10, 'user_id' => 20]);
        $this->assertDatabaseCount('class_user', 3);
    }

    private function assertUniqueConstraint(string $table, array $row): void
    {
        try {
            DB::table($table)->insert($row);
            $this->fail("Unique constraint của {$table} phải từ chối dữ liệu trùng.");
        } catch (QueryException) {
            $this->assertTrue(true);
        }
    }

    private function createSchema(): void
    {
        Schema::create('class_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('class_course', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('course_id');
            $table->timestamps();
        });
        Schema::create('attendance_data', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attendance_column_id');
            $table->unsignedBigInteger('user_id');
            $table->string('value')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('user_id');
            $table->string('file_path')->nullable();
            $table->string('file_disk')->default('public');
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->longText('text_answer')->nullable();
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->decimal('ai_suggested_score', 5, 2)->nullable();
            $table->longText('ai_feedback')->nullable();
            $table->json('ai_rubric_breakdown')->nullable();
            $table->json('ai_review_flags')->nullable();
            $table->longText('ai_grading_notes')->nullable();
            $table->timestamp('ai_analyzed_at')->nullable();
            $table->json('ai_analysis_history')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('student_id');
            $table->string('file_path');
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('is_final')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
