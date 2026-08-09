<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateLearningFileMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireIsolatedSqliteDatabase();

        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('file_path')->nullable();
            $table->string('file_disk')->nullable();
            $table->char('checksum_sha256', 64)->nullable();
        });
        Schema::create('lessons', function (Blueprint $table): void {
            $table->id();
            $table->string('attachment')->nullable();
            $table->string('attachment_disk')->nullable();
        });
        Schema::create('learning_materials', function (Blueprint $table): void {
            $table->id();
            $table->string('file_path')->nullable();
            $table->string('disk')->nullable();
        });
        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('allowed_extensions')->default('pdf,php');
        });

        Storage::fake('public');
        Storage::fake('local');
        config([
            'filesystems.submission_disk' => 'local',
            'filesystems.lesson_attachment_disk' => 'local',
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            Schema::dropIfExists('assignments');
            Schema::dropIfExists('learning_materials');
            Schema::dropIfExists('lessons');
            Schema::dropIfExists('assignment_submissions');
        }

        parent::tearDown();
    }

    public function test_command_moves_public_files_and_updates_every_reference(): void
    {
        Storage::disk('public')->put('assignments/student.pdf', 'submission');
        Storage::disk('public')->put('lessons/shared.pdf', 'lesson');

        DB::table('assignment_submissions')->insert([
            'file_path' => 'assignments/student.pdf',
            'file_disk' => 'public',
        ]);
        DB::table('lessons')->insert([
            'attachment' => 'lessons/shared.pdf',
            'attachment_disk' => 'public',
        ]);
        DB::table('learning_materials')->insert([
            'file_path' => 'lessons/shared.pdf',
            'disk' => 'public',
        ]);

        $this->artisan('smartlms:migrate-private-learning-files --delete-source')->assertSuccessful();

        $this->assertDatabaseHas('assignment_submissions', ['file_disk' => 'local']);
        $this->assertDatabaseHas('lessons', ['attachment_disk' => 'local']);
        $this->assertDatabaseHas('learning_materials', ['disk' => 'local']);
        Storage::disk('local')->assertExists('assignments/student.pdf');
        Storage::disk('local')->assertExists('lessons/shared.pdf');
        Storage::disk('public')->assertMissing('assignments/student.pdf');
        Storage::disk('public')->assertMissing('lessons/shared.pdf');
    }

    public function test_submission_group_moves_local_file_to_r2_with_checksum_and_keeps_rollback_copy(): void
    {
        Storage::fake('r2');
        config(['filesystems.submission_disk' => 'r2']);
        Storage::disk('local')->put('assignments/local.pdf', 'submission-local');
        DB::table('assignment_submissions')->insert([
            'file_path' => 'assignments/local.pdf',
            'file_disk' => 'local',
        ]);

        $this->artisan('smartlms:migrate-private-learning-files --group=submissions')->assertSuccessful();

        $this->assertDatabaseHas('assignment_submissions', [
            'file_disk' => 'r2',
            'checksum_sha256' => hash('sha256', 'submission-local'),
        ]);
        Storage::disk('r2')->assertExists('assignments/local.pdf');
        Storage::disk('local')->assertExists('assignments/local.pdf');
    }

    public function test_dry_run_does_not_change_files_or_database(): void
    {
        Storage::disk('public')->put('assignments/dry-run.pdf', 'submission');
        DB::table('assignment_submissions')->insert([
            'file_path' => 'assignments/dry-run.pdf',
            'file_disk' => 'public',
        ]);

        $this->artisan('smartlms:migrate-private-learning-files --dry-run')->assertSuccessful();

        $this->assertDatabaseHas('assignment_submissions', ['file_disk' => 'public']);
        Storage::disk('public')->assertExists('assignments/dry-run.pdf');
        Storage::disk('local')->assertMissing('assignments/dry-run.pdf');
    }

    public function test_security_migration_removes_php_from_existing_assignment_configuration(): void
    {
        DB::table('assignments')->insert([
            ['allowed_extensions' => 'pdf,php,png'],
            ['allowed_extensions' => 'PHP'],
        ]);

        $migration = require database_path('migrations/2026_08_01_000001_remove_php_from_assignment_uploads.php');
        $migration->up();

        $this->assertSame('pdf,png', DB::table('assignments')->where('id', 1)->value('allowed_extensions'));
        $this->assertSame(
            'pdf,docx,txt,md,html,htm,css,js,png,jpg,jpeg',
            DB::table('assignments')->where('id', 2)->value('allowed_extensions')
        );
    }
}
