<?php

namespace Tests\Feature;

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
        foreach (['quiz_attempts', 'options', 'questions', 'quizzes', 'attendance_data', 'attendance_columns', 'class_user', 'classes', 'courses', 'users'] as $table) {
            Schema::dropIfExists($table);
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
}
