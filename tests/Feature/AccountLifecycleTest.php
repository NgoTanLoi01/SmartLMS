<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AccountLifecycleTest extends TestCase
{
    private bool $isolatedSchemaCreated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('AccountLifecycleTest chỉ được phép chạy trên SQLite cô lập.');
        }

        $this->isolatedSchemaCreated = true;

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

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->string('title');
            $table->timestamps();
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
        if ($this->isolatedSchemaCreated) {
            Schema::dropIfExists('smart_notifications');
            Schema::dropIfExists('audit_logs');
            Schema::dropIfExists('courses');
            Schema::dropIfExists('sessions');
            Schema::dropIfExists('users');
        }

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
            ->assertSee('Quản trị hệ thống')
            ->assertSee('Tài khoản người dùng')
            ->assertSee('Nhật ký hệ thống')
            ->assertDontSee('Audit log')
            ->assertSee('Tổng tài khoản')
            ->assertSee('Cần xử lý')
            ->assertSee('Cấp tài khoản mới')
            ->assertSee('Sửa thông tin')
            ->assertSee('Cấp lại mật khẩu')
            ->assertSee('Quản lý vòng đời tài khoản')
            ->assertSee('Đang hoạt động')
            ->assertSee('Không giới hạn thời gian');
    }

    public function test_student_sidebar_shows_flat_learning_navigation(): void
    {
        $student = $this->createUser([
            'email' => 'student-navigation@example.com',
            'role' => User::ROLE_STUDENT,
        ]);

        $this->actingAs($student);

        $sidebar = view('layouts.partials.sidebar')->render();

        $this->assertStringContainsString('Menu học viên', $sidebar);
        $this->assertStringContainsString('Khóa học của tôi', $sidebar);
        $this->assertStringContainsString('Kết quả học tập', $sidebar);
        $this->assertStringContainsString('Học tập của bạn', $sidebar);
        $this->assertStringNotContainsString('data-testid="nav-group-learning"', $sidebar);
        $this->assertStringNotContainsString('Quản trị hệ thống', $sidebar);
    }

    public function test_create_user_validation_uses_the_account_modal_error_bag(): void
    {
        $admin = $this->createUser([
            'email' => 'create-user-validation@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'role' => User::ROLE_TEACHER,
            ])
            ->assertSessionHasErrors(['name', 'password'], null, 'createUser');
    }

    public function test_admin_can_update_user_profile_without_changing_role_password_or_lifecycle(): void
    {
        $admin = $this->createUser([
            'email' => 'profile-admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
        $target = $this->createUser([
            'name' => 'Giáo viên cũ',
            'email' => 'old-teacher@example.com',
            'role' => User::ROLE_TEACHER,
            'is_active' => false,
            'deactivation_reason' => 'Đang tạm nghỉ',
        ]);
        $originalPassword = $target->password;

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->patch(route('users.update', $target), [
                'editing_user_id' => $target->id,
                'name' => 'Giáo viên mới',
                'email' => 'new-teacher@example.com',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertSame('Giáo viên mới', $target->name);
        $this->assertSame('new-teacher@example.com', $target->email);
        $this->assertSame(User::ROLE_TEACHER, $target->role);
        $this->assertSame($originalPassword, $target->password);
        $this->assertFalse($target->is_active);
        $this->assertSame('Đang tạm nghỉ', $target->deactivation_reason);

        $auditLog = AuditLog::query()
            ->where('action', AuditLogger::ACCOUNT_PROFILE_UPDATED)
            ->where('auditable_id', $target->id)
            ->firstOrFail();
        $this->assertSame('Giáo viên cũ', $auditLog->old_values['name']);
        $this->assertSame('Giáo viên mới', $auditLog->new_values['name']);
    }

    public function test_admin_can_update_student_login_identifiers_and_internal_email(): void
    {
        $admin = $this->createUser([
            'email' => 'student-profile-admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
        $student = $this->createUser([
            'name' => 'Học viên cũ',
            'username' => 'hocviencu',
            'student_code' => 'hv001',
            'email' => 'hocviencu@student.smartlms',
            'role' => User::ROLE_STUDENT,
        ]);

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->patch(route('users.update', $student), [
                'editing_user_id' => $student->id,
                'name' => 'Học viên mới',
                'username' => '  Hoc-Vien.Moi  ',
                'student_code' => ' HV- 002 ',
                'email' => '',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $student->refresh();
        $this->assertSame('Học viên mới', $student->name);
        $this->assertSame('hoc-vien.moi', $student->username);
        $this->assertSame('hv002', $student->student_code);
        $this->assertSame('hoc.vien.moi@student.smartlms', $student->email);
    }

    public function test_update_user_profile_rejects_duplicate_student_identifiers_in_edit_error_bag(): void
    {
        $admin = $this->createUser([
            'email' => 'duplicate-profile-admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
        $existing = $this->createUser([
            'username' => 'existing-student',
            'student_code' => 'hv009',
            'email' => 'existing-student@example.com',
            'role' => User::ROLE_STUDENT,
        ]);
        $target = $this->createUser([
            'name' => 'Học viên mục tiêu',
            'username' => 'target-student',
            'student_code' => 'hv010',
            'email' => 'target-student@example.com',
            'role' => User::ROLE_STUDENT,
        ]);

        $response = $this->followingRedirects()
            ->actingAs($admin)
            ->from(route('users.index'))
            ->patch(route('users.update', $target), [
                'editing_user_id' => $target->id,
                'name' => 'Tên không được lưu',
                'username' => $existing->username,
                'student_code' => 'HV-009',
                'email' => $target->email,
            ]);

        $response
            ->assertOk()
            ->assertSee('Vui lòng kiểm tra lại các thông tin được đánh dấu bên dưới.')
            ->assertSee('Tên đăng nhập này đã được sử dụng.')
            ->assertSee('Mã học viên này đã được sử dụng.')
            ->assertSee('data-reopen="1"', false);

        $this->assertSame('Học viên mục tiêu', $target->fresh()->name);
    }

    public function test_non_admin_cannot_update_user_profile(): void
    {
        $teacher = $this->createUser(['email' => 'profile-teacher@example.com']);
        $target = $this->createUser(['email' => 'profile-target@example.com']);

        $this->actingAs($teacher)
            ->patch(route('users.update', $target), [
                'editing_user_id' => $target->id,
                'name' => 'Không được phép',
                'email' => 'forbidden@example.com',
            ])
            ->assertForbidden();

        $this->assertNotSame('Không được phép', $target->fresh()->name);
    }

    public function test_student_internal_email_collision_is_rejected_before_update(): void
    {
        $admin = $this->createUser([
            'email' => 'internal-email-admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
        $this->createUser([
            'username' => 'alpha.beta',
            'email' => 'alpha.beta@student.smartlms',
            'role' => User::ROLE_STUDENT,
        ]);
        $target = $this->createUser([
            'name' => 'Học viên chưa đổi',
            'username' => 'target-alpha',
            'email' => 'target-alpha@student.smartlms',
            'role' => User::ROLE_STUDENT,
        ]);

        $response = $this->followingRedirects()
            ->actingAs($admin)
            ->from(route('users.index'))
            ->patch(route('users.update', $target), [
                'editing_user_id' => $target->id,
                'name' => 'Tên không được lưu',
                'username' => 'alpha-beta',
                'student_code' => '',
                'email' => '',
            ]);

        $response
            ->assertOk()
            ->assertSee('Email nội bộ tạo từ tên đăng nhập đã được sử dụng.');
        $this->assertSame('Học viên chưa đổi', $target->fresh()->name);
        $this->assertSame('target-alpha', $target->fresh()->username);
    }

    public function test_attendance_page_has_link_back_to_current_course(): void
    {
        $teacher = $this->createUser(['email' => 'attendance-navigation@example.com']);
        $this->actingAs($teacher);
        view()->share('errors', new ViewErrorBag);

        $course = (object) ['id' => 321, 'title' => 'Khóa học kiểm thử'];
        $html = view('attendance.show', [
            'course' => $course,
            'students' => collect(),
            'columns' => collect(),
            'attendanceData' => [],
            'attendanceNotes' => [],
            'schedules' => collect(),
            'isStudentView' => false,
        ])->render();

        $this->assertStringContainsString('Quay lại khóa học', $html);
        $this->assertStringContainsString(route('courses.show', 321), $html);
        $this->assertStringContainsString('data-testid="attendance-back-to-course"', $html);
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

    public function test_teacher_with_owned_training_data_cannot_be_physically_deleted(): void
    {
        $admin = $this->createUser([
            'email' => 'deletion-admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
        $teacher = $this->createUser(['email' => 'teacher-with-course@example.com']);

        DB::table('courses')->insert([
            'teacher_id' => $teacher->id,
            'title' => 'Khóa học cần được bảo toàn',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $teacher))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $teacher->id]);
        $this->assertDatabaseHas('courses', ['teacher_id' => $teacher->id]);
    }

    public function test_teacher_without_owned_training_data_can_be_deleted(): void
    {
        $admin = $this->createUser([
            'email' => 'plain-deletion-admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
        $teacher = $this->createUser(['email' => 'teacher-without-data@example.com']);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $teacher))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
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
