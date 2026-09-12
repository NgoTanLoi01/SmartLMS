<?php

namespace Tests\Feature;

use App\Services\AuditIntegrityService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationRollbackIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('MigrationRollbackIntegrityTest chỉ được phép chạy trên SQLite cô lập.');
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
        });
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            foreach (['audit_log_chain_states', 'audit_logs', 'schedule_change_batches', 'schedule_resource_locks', 'schedule_adjustment_batches', 'grading_feedback_templates', 'assignment_submissions', 'question_versions', 'quiz_attempt_attachments', 'quiz_attempt_answers', 'quiz_attempt_questions', 'quiz_session_user', 'quiz_sessions', 'quiz_attempts', 'options', 'questions', 'quiz_passages', 'quizzes', 'attendance_data', 'attendance_columns', 'schedules', 'class_user', 'classes', 'courses', 'users'] as $table) {
                Schema::dropIfExists($table);
            }
        }

        parent::tearDown();
    }

    public function test_quiz_bundle_migration_rolls_back_every_created_table(): void
    {
        $migration = require database_path('migrations/2026_04_16_113843_create_quizzes_and_questions_tables.php');

        $migration->up();
        $this->assertTrue(Schema::hasTable('quizzes'));
        $this->assertTrue(Schema::hasTable('questions'));
        $this->assertTrue(Schema::hasTable('options'));
        $this->assertTrue(Schema::hasTable('quiz_attempts'));

        $migration->down();
        $this->assertFalse(Schema::hasTable('quiz_attempts'));
        $this->assertFalse(Schema::hasTable('options'));
        $this->assertFalse(Schema::hasTable('questions'));
        $this->assertFalse(Schema::hasTable('quizzes'));
    }

    public function test_class_bundle_migration_rolls_back_pivot_before_class(): void
    {
        $migration = require database_path('migrations/2026_04_18_025716_create_classes_and_class_user_tables.php');

        $migration->up();
        $this->assertTrue(Schema::hasTable('classes'));
        $this->assertTrue(Schema::hasTable('class_user'));

        $migration->down();
        $this->assertFalse(Schema::hasTable('class_user'));
        $this->assertFalse(Schema::hasTable('classes'));
    }

    public function test_quiz_exam_foundation_migration_is_reversible(): void
    {
        $quizBundle = require database_path('migrations/2026_04_16_113843_create_quizzes_and_questions_tables.php');
        $quizBundle->up();

        $foundation = require database_path('migrations/2026_07_26_000001_create_quiz_exam_foundation.php');
        $foundation->up();

        $this->assertTrue(Schema::hasTable('quiz_sessions'));
        $this->assertTrue(Schema::hasTable('quiz_passages'));
        $this->assertTrue(Schema::hasTable('quiz_session_user'));
        $this->assertTrue(Schema::hasTable('quiz_attempt_questions'));
        $this->assertTrue(Schema::hasTable('quiz_attempt_answers'));
        $this->assertTrue(Schema::hasColumn('quiz_attempts', 'expires_at'));

        $foundation->down();

        $this->assertFalse(Schema::hasTable('quiz_attempt_answers'));
        $this->assertFalse(Schema::hasTable('quiz_attempt_questions'));
        $this->assertFalse(Schema::hasTable('quiz_session_user'));
        $this->assertFalse(Schema::hasTable('quiz_sessions'));
        $this->assertFalse(Schema::hasTable('quiz_passages'));
        $this->assertFalse(Schema::hasColumn('quiz_attempts', 'expires_at'));

        $quizBundle->down();
    }

    public function test_attendance_bundle_migration_rolls_back_data_before_columns(): void
    {
        $migration = require database_path('migrations/2026_04_19_024524_create_attendance_tables.php');

        $migration->up();
        $this->assertTrue(Schema::hasTable('attendance_columns'));
        $this->assertTrue(Schema::hasTable('attendance_data'));

        $migration->down();
        $this->assertFalse(Schema::hasTable('attendance_data'));
        $this->assertFalse(Schema::hasTable('attendance_columns'));
    }

    public function test_mixed_question_type_migration_is_reversible(): void
    {
        $quizBundle = require database_path('migrations/2026_04_16_113843_create_quizzes_and_questions_tables.php');
        $quizBundle->up();
        $foundation = require database_path('migrations/2026_07_26_000001_create_quiz_exam_foundation.php');
        $foundation->up();
        Schema::table('questions', function (Blueprint $table) {
            $table->string('difficulty')->default('medium');
        });

        $mixedTypes = require database_path('migrations/2026_07_27_000002_add_mixed_question_types.php');
        $mixedTypes->up();

        $this->assertTrue(Schema::hasColumn('questions', 'question_type'));
        $this->assertTrue(Schema::hasColumn('questions', 'answer_config'));
        $this->assertTrue(Schema::hasColumn('quiz_attempt_questions', 'answer_key_snapshot'));
        $this->assertTrue(Schema::hasColumn('quiz_attempt_answers', 'answer_payload'));
        $this->assertTrue(Schema::hasColumn('quiz_attempt_answers', 'is_correct'));

        $mixedTypes->down();

        $this->assertFalse(Schema::hasColumn('questions', 'question_type'));
        $this->assertFalse(Schema::hasColumn('quiz_attempt_questions', 'answer_key_snapshot'));
        $this->assertFalse(Schema::hasColumn('quiz_attempt_answers', 'answer_payload'));

        $foundation->down();
        $quizBundle->down();
    }

    public function test_quiz_question_distribution_migration_is_reversible(): void
    {
        $quizBundle = require database_path('migrations/2026_04_16_113843_create_quizzes_and_questions_tables.php');
        $quizBundle->up();
        Schema::table('quizzes', function (Blueprint $table) {
            $table->unsignedInteger('hard_count')->default(0);
        });

        $distribution = require database_path('migrations/2026_07_27_000003_add_question_distribution_to_quizzes.php');
        $distribution->up();

        $this->assertTrue(Schema::hasColumn('quizzes', 'question_distribution'));

        $distribution->down();

        $this->assertFalse(Schema::hasColumn('quizzes', 'question_distribution'));

        $quizBundle->down();
    }

    public function test_manual_quiz_grading_migration_is_reversible(): void
    {
        $quizBundle = require database_path('migrations/2026_04_16_113843_create_quizzes_and_questions_tables.php');
        $quizBundle->up();
        $foundation = require database_path('migrations/2026_07_26_000001_create_quiz_exam_foundation.php');
        $foundation->up();
        Schema::table('questions', fn (Blueprint $table) => $table->string('difficulty')->default('medium'));
        $mixedTypes = require database_path('migrations/2026_07_27_000002_add_mixed_question_types.php');
        $mixedTypes->up();

        $manualGrading = require database_path('migrations/2026_07_27_000005_add_manual_quiz_grading.php');
        $manualGrading->up();

        $this->assertTrue(Schema::hasColumn('quiz_attempts', 'manual_score'));
        $this->assertTrue(Schema::hasColumn('quiz_attempt_questions', 'grading_mode'));
        $this->assertTrue(Schema::hasColumn('quiz_attempt_answers', 'teacher_feedback'));
        $this->assertTrue(Schema::hasTable('quiz_attempt_attachments'));

        $manualGrading->down();
        $this->assertFalse(Schema::hasColumn('quiz_attempts', 'manual_score'));
        $this->assertFalse(Schema::hasColumn('quiz_attempt_questions', 'grading_mode'));
        $this->assertFalse(Schema::hasColumn('quiz_attempt_answers', 'teacher_feedback'));
        $this->assertFalse(Schema::hasTable('quiz_attempt_attachments'));

        $mixedTypes->down();
        $foundation->down();
        $quizBundle->down();
    }

    public function test_observed_question_difficulty_migration_is_reversible(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('difficulty')->default('medium');
        });

        $migration = require database_path('migrations/2026_07_27_000004_add_observed_difficulty_to_questions.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('questions', 'observed_difficulty'));
        $this->assertTrue(Schema::hasColumn('questions', 'difficulty_metrics'));

        $migration->down();

        $this->assertFalse(Schema::hasColumn('questions', 'observed_difficulty'));
        $this->assertFalse(Schema::hasColumn('questions', 'difficulty_metrics'));
    }

    public function test_quiz_attempt_constraint_keeps_first_completed_attempt_and_is_reversible(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('completed_at')->nullable();
        });

        DB::table('quiz_attempts')->insert([
            ['quiz_id' => 10, 'user_id' => 20, 'completed_at' => '2026-07-23 10:00:00'],
            ['quiz_id' => 10, 'user_id' => 20, 'completed_at' => '2026-07-23 10:00:01'],
        ]);

        $migration = require database_path('migrations/2026_07_23_200000_enforce_single_quiz_attempt_per_student.php');
        $migration->up();

        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->assertDatabaseHas('quiz_attempts', ['id' => 1]);

        try {
            DB::table('quiz_attempts')->insert([
                'quiz_id' => 10,
                'user_id' => 20,
                'completed_at' => now(),
            ]);
            $this->fail('Unique constraint phải từ chối kết quả quiz trùng lặp.');
        } catch (QueryException) {
            $this->assertDatabaseCount('quiz_attempts', 1);
        }

        $migration->down();
        DB::table('quiz_attempts')->insert([
            'quiz_id' => 10,
            'user_id' => 20,
            'completed_at' => now(),
        ]);
        $this->assertDatabaseCount('quiz_attempts', 2);
    }

    public function test_submission_checksum_migration_is_nullable_and_reversible(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('file_size')->nullable();
        });
        DB::table('assignment_submissions')->insert(['file_size' => 123]);

        $migration = require database_path('migrations/2026_08_09_130000_add_checksum_to_assignment_submissions.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('assignment_submissions', 'checksum_sha256'));
        $this->assertDatabaseHas('assignment_submissions', ['id' => 1, 'checksum_sha256' => null]);

        $migration->down();
        $this->assertFalse(Schema::hasColumn('assignment_submissions', 'checksum_sha256'));
        $this->assertDatabaseHas('assignment_submissions', ['id' => 1, 'file_size' => 123]);
    }

    public function test_assignment_grading_workflow_migrations_backfill_and_roll_back(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
        });
        DB::table('assignment_submissions')->insert([
            'grade' => 8.5,
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        $workflowMigration = require database_path('migrations/2026_09_04_000001_add_grading_workflow_to_assignment_submissions.php');
        $workflowMigration->up();
        $this->assertDatabaseHas('assignment_submissions', [
            'id' => 1,
            'grading_status' => 'published',
        ]);
        $this->assertTrue(Schema::hasColumn('assignment_submissions', 'rubric_scores'));
        $this->assertTrue(Schema::hasColumn('assignment_submissions', 'grade_published_at'));

        $templateMigration = require database_path('migrations/2026_09_04_000002_create_grading_feedback_templates_table.php');
        $templateMigration->up();
        $this->assertTrue(Schema::hasTable('grading_feedback_templates'));
        $templateMigration->down();
        $this->assertFalse(Schema::hasTable('grading_feedback_templates'));

        $workflowMigration->down();
        $this->assertFalse(Schema::hasColumn('assignment_submissions', 'grading_status'));
        $this->assertFalse(Schema::hasColumn('assignment_submissions', 'rubric_scores'));
        $this->assertFalse(Schema::hasColumn('assignment_submissions', 'grade_published_at'));
    }

    public function test_schedule_series_migration_is_reversible(): void
    {
        Schema::create('schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('active');
        });

        $migration = require database_path('migrations/2026_09_03_000002_add_series_columns_to_schedules_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('schedules', 'series_id'));
        $this->assertTrue(Schema::hasColumn('schedules', 'series_position'));

        $migration->down();

        $this->assertFalse(Schema::hasColumn('schedules', 'series_id'));
        $this->assertFalse(Schema::hasColumn('schedules', 'series_position'));
    }

    public function test_schedule_adjustment_batch_migration_is_reversible(): void
    {
        $migration = require database_path('migrations/2026_09_11_000001_create_schedule_adjustment_batches_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('schedule_adjustment_batches'));
        $this->assertTrue(Schema::hasColumns('schedule_adjustment_batches', [
            'public_id',
            'created_by',
            'before_values',
            'after_values',
            'status',
            'undone_by',
            'undone_at',
        ]));

        $migration->down();
        $this->assertFalse(Schema::hasTable('schedule_adjustment_batches'));
    }

    public function test_schedule_write_guard_and_quick_undo_migration_is_reversible(): void
    {
        $migration = require database_path('migrations/2026_09_12_000001_create_schedule_write_guards_and_change_batches.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('schedule_resource_locks'));
        $this->assertTrue(Schema::hasColumns('schedule_change_batches', [
            'public_id',
            'created_by',
            'scope',
            'before_values',
            'after_values',
            'expires_at',
            'undone_by',
            'undone_at',
        ]));

        $migration->down();
        $this->assertFalse(Schema::hasTable('schedule_change_batches'));
        $this->assertFalse(Schema::hasTable('schedule_resource_locks'));
    }

    public function test_question_version_migration_backfills_and_is_reversible(): void
    {
        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('question_bank_id')->nullable();
            $table->unsignedBigInteger('quiz_passage_id')->nullable();
            $table->string('question_type')->default('single_choice');
            $table->text('question_text');
            $table->json('answer_config')->nullable();
            $table->string('difficulty')->default('medium');
            $table->string('status')->default('published');
            $table->timestamps();
        });
        Schema::create('options', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
        $questionId = DB::table('questions')->insertGetId([
            'course_id' => 1,
            'question_type' => 'single_choice',
            'question_text' => 'Câu hỏi cần lưu phiên bản',
            'difficulty' => 'medium',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('options')->insert([
            'question_id' => $questionId,
            'option_text' => 'Đáp án đúng',
            'is_correct' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_09_04_000003_add_question_bank_management_fields.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('questions', 'tags'));
        $this->assertTrue(Schema::hasColumn('questions', 'current_version'));
        $this->assertDatabaseHas('question_versions', [
            'question_id' => $questionId,
            'version_number' => 1,
            'change_type' => 'baseline',
        ]);
        $snapshot = json_decode(DB::table('question_versions')->value('snapshot'), true);
        $this->assertSame('Câu hỏi cần lưu phiên bản', $snapshot['question_text']);
        $this->assertTrue($snapshot['options'][0]['is_correct']);

        $migration->down();

        $this->assertFalse(Schema::hasTable('question_versions'));
        $this->assertFalse(Schema::hasColumn('questions', 'tags'));
        $this->assertFalse(Schema::hasColumn('questions', 'current_version'));
    }

    public function test_audit_integrity_migration_backfills_chain_and_is_reversible(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name')->nullable();
            $table->string('email')->nullable();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Admin cũ', 'email' => 'old-admin@example.com']);
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 100);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
        DB::table('audit_logs')->insert([
            'user_id' => 1,
            'action' => 'legacy_event',
            'description' => 'Bản ghi trước migration',
            'metadata' => json_encode(['source' => 'legacy']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_09_06_000001_harden_audit_log_integrity.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('audit_log_chain_states'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'entry_hash'));
        $this->assertDatabaseHas('audit_logs', [
            'id' => 1,
            'actor_id' => 1,
            'actor_name' => 'Admin cũ',
            'chain_position' => 1,
        ]);
        $this->assertSame(64, strlen((string) DB::table('audit_logs')->value('entry_hash')));
        $this->assertTrue(app(AuditIntegrityService::class)->verify()['valid']);

        $migration->down();

        $this->assertFalse(Schema::hasTable('audit_log_chain_states'));
        $this->assertFalse(Schema::hasColumn('audit_logs', 'entry_hash'));
        $this->assertDatabaseHas('audit_logs', ['id' => 1, 'action' => 'legacy_event']);
    }
}
