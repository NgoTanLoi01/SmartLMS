<?php

namespace Tests\Feature;

use App\Models\BackupRun;
use App\Models\User;
use App\Services\BackupRestoreService;
use App\Services\BackupService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;
use ZipArchive;

class SystemBackupRecoveryTest extends TestCase
{
    private User $admin;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireIsolatedSqliteDatabase();
        $this->createSchema();

        $this->admin = $this->user('backup-admin@example.com', User::ROLE_ADMIN);
        $this->teacher = $this->user('backup-teacher@example.com', User::ROLE_TEACHER);
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            Schema::dropIfExists('smart_notifications');
            Schema::dropIfExists('audit_logs');
            Schema::dropIfExists('backup_runs');
            Schema::dropIfExists('users');
        }

        parent::tearDown();
    }

    public function test_only_admin_can_verify_or_restore_backup_via_direct_http_request(): void
    {
        $backup = $this->backup();

        $this->actingAs($this->teacher)
            ->post(route('system.backups.verify', $backup))
            ->assertForbidden();

        $this->actingAs($this->teacher)
            ->post(route('system.backups.restore', $backup), [
                'confirmation' => 'KHOI PHUC',
                'current_password' => 'password',
            ])
            ->assertForbidden();
    }

    public function test_backup_management_page_exposes_operational_status_and_safe_restore_flow(): void
    {
        $backup = $this->backup([
            'metadata' => [
                'format' => BackupService::FORMAT,
                'format_version' => BackupService::FORMAT_VERSION,
                'integrity_status' => 'valid',
                'included_files' => 12,
                'vector_database_rows' => 35,
                'missing_files' => 0,
            ],
        ]);
        $this->backup([
            'status' => 'failed',
            'filename' => null,
            'error_message' => 'Không thể kết nối kho lưu trữ.',
            'metadata' => [],
        ]);

        $this->actingAs($this->admin)
            ->get(route('system.backups.index'))
            ->assertOk()
            ->assertSee('Lịch sử sao lưu')
            ->assertSee('Nội dung gói sao lưu')
            ->assertSee('12')
            ->assertSee('35')
            ->assertSee('Tạo dự phòng và phục hồi')
            ->assertSee('Không thể kết nối kho lưu trữ.');

        $this->actingAs($this->teacher)
            ->get(route('system.backups.index'))
            ->assertForbidden();
    }

    public function test_restore_requires_exact_confirmation_and_current_admin_password(): void
    {
        $backup = $this->backup();
        $restoreService = Mockery::mock(BackupRestoreService::class);
        $restoreService->shouldNotReceive('verifyBackup');
        $restoreService->shouldNotReceive('restoreBackup');
        $this->app->instance(BackupRestoreService::class, $restoreService);

        $this->actingAs($this->admin)
            ->post(route('system.backups.restore', $backup), [
                'confirmation' => 'khoi phuc',
                'current_password' => 'password',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('confirmation', null, 'restoreBackup');

        $this->actingAs($this->admin)
            ->post(route('system.backups.restore', $backup), [
                'confirmation' => 'KHOI PHUC',
                'current_password' => 'wrong-password',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('current_password', null, 'restoreBackup');
    }

    public function test_legacy_database_only_backup_cannot_be_restored(): void
    {
        $backup = $this->backup([
            'type' => 'database',
            'filename' => 'smartlms-db-old.sql.gz',
            'metadata' => [],
        ]);

        $this->actingAs($this->admin)
            ->post(route('system.backups.restore', $backup), [
                'confirmation' => 'KHOI PHUC',
                'current_password' => 'password',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Backup này là định dạng cũ hoặc không đủ dữ liệu để phục hồi an toàn.');
    }

    public function test_admin_can_verify_a_backup_and_the_action_is_audited(): void
    {
        $backup = $this->backup();
        $restoreService = Mockery::mock(BackupRestoreService::class);
        $restoreService->shouldReceive('verifyBackup')->once()->andReturn([
            'valid' => true,
            'message' => 'Gói backup toàn vẹn và sẵn sàng phục hồi.',
            'files' => 3,
            'missing_files' => 0,
        ]);
        $this->app->instance(BackupRestoreService::class, $restoreService);

        $this->actingAs($this->admin)
            ->post(route('system.backups.verify', $backup))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'backup.verified',
            'auditable_id' => $backup->id,
        ]);
    }

    public function test_restore_creates_pre_restore_backup_enters_maintenance_and_writes_audit_log(): void
    {
        $backup = $this->backup();
        $preRestore = $this->backup([
            'triggered_by' => 'pre_restore',
            'filename' => 'smartlms-full-pre-restore.zip',
        ]);

        $backupService = Mockery::mock(BackupService::class);
        $backupService->shouldReceive('runFullBackup')
            ->once()
            ->with(Mockery::on(fn (array $options) => $options['triggered_by'] === 'pre_restore'
                && $options['user_id'] === $this->admin->id
                && $options['skip_prune'] === true))
            ->andReturn($preRestore);
        $this->app->instance(BackupService::class, $backupService);

        $restoreService = Mockery::mock(BackupRestoreService::class);
        $restoreService->shouldReceive('verifyBackup')->once()->with(Mockery::type(BackupRun::class))->andReturn([
            'valid' => true,
            'message' => 'Hợp lệ',
            'files' => 2,
            'missing_files' => 0,
        ]);
        $restoreService->shouldReceive('restoreBackup')->once()->andReturn([
            'restored_files' => 2,
            'missing_files_at_backup' => 0,
            'manifest' => [],
        ]);
        $this->app->instance(BackupRestoreService::class, $restoreService);

        Artisan::shouldReceive('call')->once()->with('down', Mockery::on(fn (array $arguments) => isset($arguments['--secret']) && $arguments['--retry'] === 60))->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('up')->andReturn(0);

        $this->actingAs($this->admin)
            ->post(route('system.backups.restore', $backup), [
                'confirmation' => 'KHOI PHUC',
                'current_password' => 'password',
            ])
            ->assertRedirect(route('system.backups.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'backup.restored',
            'auditable_id' => $backup->id,
        ]);
    }

    public function test_failed_restore_automatically_rolls_back_from_pre_restore_backup(): void
    {
        $backup = $this->backup();
        $preRestore = $this->backup([
            'triggered_by' => 'pre_restore',
            'filename' => 'smartlms-full-rollback.zip',
        ]);

        $backupService = Mockery::mock(BackupService::class);
        $backupService->shouldReceive('runFullBackup')->once()->andReturn($preRestore);
        $this->app->instance(BackupService::class, $backupService);

        $restoreService = Mockery::mock(BackupRestoreService::class);
        $restoreService->shouldReceive('verifyBackup')->once()->andReturn([
            'valid' => true,
            'message' => 'Hợp lệ',
            'files' => 1,
            'missing_files' => 0,
        ]);
        $restoreService->shouldReceive('restoreBackup')
            ->once()
            ->with(Mockery::on(fn (BackupRun $candidate) => $candidate->is($backup)))
            ->andThrow(new \RuntimeException('Lỗi giữa quá trình phục hồi'));
        $restoreService->shouldReceive('restoreBackup')
            ->once()
            ->with(Mockery::on(fn (BackupRun $candidate) => $candidate->is($preRestore)))
            ->andReturn(['restored_files' => 1, 'missing_files_at_backup' => 0, 'manifest' => []]);
        $this->app->instance(BackupRestoreService::class, $restoreService);

        Artisan::shouldReceive('call')->once()->with('down', Mockery::type('array'))->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('up')->andReturn(0);

        $this->actingAs($this->admin)
            ->post(route('system.backups.restore', $backup), [
                'confirmation' => 'KHOI PHUC',
                'current_password' => 'password',
            ])
            ->assertRedirect(route('system.backups.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'đã tự động quay lại'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'backup.restore_failed',
            'auditable_id' => $backup->id,
        ]);
    }

    public function test_real_integrity_check_validates_package_and_rejects_checksum_mismatch(): void
    {
        $path = sys_get_temp_dir().'/smartlms-test-'.uniqid().'.zip';
        $database = gzencode("-- SmartLMS database backup\nSET FOREIGN_KEY_CHECKS=0;\n", 9);
        $vectorDatabase = gzencode(json_encode([
            'format' => 'smartlms-vector-database',
            'version' => 1,
            'connection' => 'pgsql',
            'table' => 'document_chunks',
        ], JSON_THROW_ON_ERROR)."\n", 9);
        $file = 'important-file';
        $manifest = [
            'format' => BackupService::FORMAT,
            'version' => BackupService::FORMAT_VERSION,
            'database' => [
                'driver' => 'mysql',
                'archive_path' => 'database.sql.gz',
                'size_bytes' => strlen($database),
                'checksum_sha256' => hash('sha256', $database),
            ],
            'vector_database' => [
                'driver' => 'pgsql',
                'connection' => 'pgsql',
                'table' => 'document_chunks',
                'archive_path' => 'vector-database.jsonl.gz',
                'size_bytes' => strlen($vectorDatabase),
                'checksum_sha256' => hash('sha256', $vectorDatabase),
                'rows' => 0,
            ],
            'files' => [[
                'disk' => 'local',
                'path' => 'documents/example.pdf',
                'archive_path' => 'files/local/documents/example.pdf',
                'size_bytes' => strlen($file),
                'checksum_sha256' => hash('sha256', $file),
            ]],
            'missing_files' => [],
        ];
        $archive = new ZipArchive;
        $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $archive->addFromString('database.sql.gz', $database);
        $archive->addFromString('vector-database.jsonl.gz', $vectorDatabase);
        $archive->addFromString('files/local/documents/example.pdf', $file);
        $archive->addFromString('manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        $archive->close();

        try {
            $backup = $this->backup([
                'local_path' => $path,
                'metadata' => [
                    'format' => BackupService::FORMAT,
                    'format_version' => BackupService::FORMAT_VERSION,
                    'package_checksum_sha256' => hash_file('sha256', $path),
                ],
            ]);
            config([
                'database.default' => 'backup_test_mysql',
                'database.connections.backup_test_mysql' => ['driver' => 'mysql'],
            ]);
            $service = app(BackupRestoreService::class);

            $this->assertTrue($service->verifyBackup($backup, false)['valid']);

            $backup->metadata = array_merge($backup->metadata, ['package_checksum_sha256' => str_repeat('0', 64)]);
            $this->assertFalse($service->verifyBackup($backup, false)['valid']);
        } finally {
            config(['database.default' => 'sqlite']);
            @unlink($path);
        }
    }

    private function backup(array $overrides = []): BackupRun
    {
        return BackupRun::create(array_merge([
            'user_id' => $this->admin->id,
            'type' => 'full',
            'status' => 'success',
            'triggered_by' => 'manual',
            'filename' => 'smartlms-full-test.zip',
            'size_bytes' => 1024,
            'started_at' => now(),
            'finished_at' => now(),
            'metadata' => [
                'format' => BackupService::FORMAT,
                'format_version' => BackupService::FORMAT_VERSION,
                'integrity_status' => 'valid',
            ],
        ], $overrides));
    }

    private function user(string $email, string $role): User
    {
        return User::create([
            'name' => $email,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default(User::ROLE_STUDENT);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('type')->default('database');
            $table->string('status')->default('running');
            $table->string('triggered_by')->default('manual');
            $table->string('filename')->nullable();
            $table->string('local_path')->nullable();
            $table->string('remote_disk')->nullable();
            $table->string('remote_path')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
        Schema::create('smart_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type')->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->json('data')->nullable();
            $table->string('dedupe_key')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
}
