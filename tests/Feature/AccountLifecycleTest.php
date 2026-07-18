<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable()->unique();
            $table->string('student_code')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default(User::ROLE_STUDENT);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->text('deactivation_reason')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
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

    protected function tearDown(): void
    {
        Schema::dropIfExists('smart_notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_active_account_can_login_and_last_login_is_recorded(): void
    {
        $user = $this->createUser();

        $this->post(route('login.post'), [
            'login' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_admin_can_view_account_lifecycle_management_interface(): void
    {
        $admin = $this->createUser([
            'email' => 'lifecycle-ui-admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Quản lý vòng đời tài khoản')
            ->assertSee('Đang hoạt động')
            ->assertSee('Không giới hạn thời gian');
    }

    public function test_inactive_and_expired_accounts_cannot_login(): void
    {
        $inactive = $this->createUser([
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);
        $expired = $this->createUser([
            'email' => 'expired@example.com',
            'expires_at' => now()->subMinute(),
        ]);

        foreach ([$inactive, $expired] as $user) {
            $this->post(route('login.post'), [
                'login' => $user->email,
                'password' => 'correct-password',
            ])->assertSessionHasErrors('login');

            $this->assertGuest();
        }
    }

    public function test_admin_can_deactivate_account_and_revoke_existing_sessions(): void
    {
        $admin = $this->createUser([
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
        $target = $this->createUser(['email' => 'teacher@example.com']);

        DB::table('sessions')->insert([
            'id' => 'target-session',
            'user_id' => $target->id,
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)
            ->patch(route('users.lifecycle.update', $target), [
                'is_active' => '0',
                'expires_at' => '',
                'deactivation_reason' => 'Kết thúc hợp đồng',
            ])
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertFalse($target->is_active);
        $this->assertNotNull($target->deactivated_at);
        $this->assertSame('Kết thúc hợp đồng', $target->deactivation_reason);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogger::ACCOUNT_LIFECYCLE_UPDATED,
            'auditable_id' => $target->id,
        ]);
    }

    public function test_disabled_account_is_logged_out_on_the_next_authenticated_request(): void
    {
        $user = $this->createUser([
            'email' => 'disabled-session@example.com',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_non_admin_cannot_manage_account_lifecycle(): void
    {
        $teacher = $this->createUser(['email' => 'teacher-admin-check@example.com']);
        $target = $this->createUser(['email' => 'target@example.com']);

        $this->actingAs($teacher)
            ->patch(route('users.lifecycle.update', $target), [
                'is_active' => '0',
                'deactivation_reason' => 'Không đủ quyền',
            ])
            ->assertForbidden();

        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_self_or_expire_the_last_active_admin(): void
    {
        $admin = $this->createUser([
            'email' => 'only-admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->patch(route('users.lifecycle.update', $admin), [
                'is_active' => '0',
                'deactivation_reason' => 'Tự khóa',
            ])
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->patch(route('users.lifecycle.update', $admin), [
                'is_active' => '1',
                'expires_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHas('error');

        $admin->refresh();
        $this->assertTrue($admin->is_active);
        $this->assertNull($admin->expires_at);
    }

    private function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Tài khoản kiểm thử',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('correct-password'),
            'role' => User::ROLE_TEACHER,
            'is_active' => true,
        ], $attributes));
    }
}
