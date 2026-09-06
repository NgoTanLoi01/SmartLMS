<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditIntegrityService;
use App\Services\AuditLogger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class AuditLogIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->requireIsolatedSqliteDatabase();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->string('role')->default(User::ROLE_STUDENT);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('action', 100)->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedBigInteger('chain_position')->nullable()->unique();
            $table->char('previous_hash', 64)->nullable();
            $table->char('entry_hash', 64)->nullable()->unique();
            $table->unsignedTinyInteger('integrity_version')->default(1);
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('audit_log_chain_states', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('last_position')->default(0);
            $table->unsignedBigInteger('last_audit_log_id')->nullable();
            $table->char('last_hash', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('smart_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->json('data')->nullable();
            $table->string('dedupe_key')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'dedupe_key']);
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('smart_notifications');
        Schema::dropIfExists('audit_log_chain_states');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_logger_redacts_nested_secrets_and_builds_a_verifiable_chain(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        AuditLogger::log('security_test', $admin, [
            'profile' => [
                'name' => 'Quản trị viên',
                'password' => 'must-not-be-stored',
                'headers' => ['Authorization' => 'Bearer secret-token'],
            ],
        ], null, [
            'connection' => [
                'api_key' => 'private-key',
                'visible' => 'kept',
            ],
        ]);
        AuditLogger::log('second_event', null, null, ['status' => 'ok']);

        $first = AuditLog::query()->orderBy('chain_position')->firstOrFail();
        $second = AuditLog::query()->orderByDesc('chain_position')->firstOrFail();

        $this->assertSame('[REDACTED]', data_get($first->old_values, 'profile.password'));
        $this->assertSame('[REDACTED]', data_get($first->old_values, 'profile.headers.Authorization'));
        $this->assertSame('[REDACTED]', data_get($first->metadata, 'connection.api_key'));
        $this->assertSame('kept', data_get($first->metadata, 'connection.visible'));
        $this->assertSame($admin->id, $first->actor_id);
        $this->assertSame($admin->name, $first->actor_name);
        $this->assertSame(1, $first->chain_position);
        $this->assertSame(2, $second->chain_position);
        $this->assertSame($first->entry_hash, $second->previous_hash);
        $this->assertSame(64, strlen($first->entry_hash));
        $this->assertTrue(app(AuditIntegrityService::class)->verify()['valid']);
    }

    public function test_model_rejects_updates_and_deletes(): void
    {
        $this->actingAs($this->admin());
        AuditLogger::log('immutable_event', null, null, ['status' => 'created']);
        $log = AuditLog::query()->firstOrFail();

        try {
            $log->update(['description' => 'changed']);
            $this->fail('Audit log update should have been rejected.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('append-only', $exception->getMessage());
        }

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_admin_can_verify_but_no_http_delete_endpoint_exists(): void
    {
        $admin = $this->admin();
        $student = $this->user(['email' => 'audit-student@example.com']);
        $this->actingAs($admin);
        AuditLogger::log('http_integrity_event');
        $log = AuditLog::query()->firstOrFail();

        $this->post(route('audit-logs.verify'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Kiểm tra toàn vẹn')
            ->assertSee('append-only')
            ->assertDontSee('Dọn nhật ký');

        $this->delete('/audit-logs')->assertStatus(405);
        $this->delete('/audit-logs/'.$log->id)->assertNotFound();
        $this->actingAs($student)->post(route('audit-logs.verify'))->assertForbidden();
    }

    public function test_verification_detects_direct_database_tampering(): void
    {
        $this->actingAs($this->admin());
        AuditLogger::log('tamper_target', null, null, null, [], 'Nội dung ban đầu');
        $log = AuditLog::query()->firstOrFail();

        DB::table('audit_logs')->where('id', $log->id)->update(['description' => 'Nội dung đã bị sửa']);
        $result = app(AuditIntegrityService::class)->verify();

        $this->assertFalse($result['valid']);
        $this->assertSame($log->id, $result['failed_id']);
        $this->assertStringContainsString('đã thay đổi', $result['message']);
    }

    public function test_retention_archives_without_deleting_or_breaking_the_chain(): void
    {
        $this->actingAs($this->admin());
        Carbon::setTestNow('2025-01-01 08:00:00');
        AuditLogger::log('old_event');
        $oldLogId = AuditLog::query()->value('id');

        Carbon::setTestNow('2026-09-06 08:00:00');
        AuditLogger::log('current_event');

        $this->artisan('smartlms:audit-archive', ['--days' => 365])
            ->expectsOutputToContain('không có bản ghi nào bị xóa')
            ->assertSuccessful();

        $this->assertNotNull(AuditLog::query()->findOrFail($oldLogId)->archived_at);
        $this->assertSame(3, AuditLog::query()->count());
        $this->assertTrue(app(AuditIntegrityService::class)->verify()['valid']);
    }

    private function admin(): User
    {
        return $this->user([
            'name' => 'Audit Admin',
            'email' => 'audit-admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function user(array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'name' => 'Audit User',
            'email' => 'audit-user-'.uniqid().'@example.com',
            'password' => Hash::make('correct-password'),
            'role' => User::ROLE_STUDENT,
            'is_active' => true,
        ], $attributes));
    }
}
