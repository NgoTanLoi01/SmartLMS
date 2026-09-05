<?php

namespace Tests\Feature;

use App\Models\TeachingContract;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeachingContractPaymentUpdateTest extends TestCase
{
    private bool $isolatedSchemaCreated = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requireIsolatedSqliteDatabase();
        $this->isolatedSchemaCreated = true;

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('teaching_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->string('subject_name');
            $table->timestamps();
        });

        Schema::create('teaching_contracts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->string('contract_number')->unique();
            $table->date('signed_date')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('received_amount', 15, 2)->default(0);
            $table->string('status')->default(TeachingContract::STATUS_UNPAID);
            $table->date('received_date')->nullable();
            $table->text('evidence_url')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('teaching_contract_record', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('teaching_contract_id');
            $table->unsignedBigInteger('teaching_record_id');
            $table->timestamps();
        });

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
    }

    protected function tearDown(): void
    {
        if ($this->isolatedSchemaCreated) {
            Schema::dropIfExists('audit_logs');
            Schema::dropIfExists('teaching_contract_record');
            Schema::dropIfExists('teaching_contracts');
            Schema::dropIfExists('teaching_records');
            Schema::dropIfExists('users');
        }

        parent::tearDown();
    }

    public function test_admin_can_change_a_received_contract_to_a_partial_payment(): void
    {
        [$admin, $contract] = $this->paymentFixture();

        $this->actingAs($admin)
            ->put(route('payments.update', $contract), $this->payload([
                'status' => TeachingContract::STATUS_RECEIVED,
                'received_amount' => 4_250_000,
                'received_date' => '2026-09-05',
            ]))
            ->assertRedirect()
            ->assertSessionHas('success', 'Đã cập nhật hợp đồng thanh toán.');

        $contract->refresh();

        $this->assertSame('4250000.00', $contract->received_amount);
        $this->assertSame(TeachingContract::STATUS_PARTIAL, $contract->status);
        $this->assertSame('2026-09-05', $contract->received_date?->format('Y-m-d'));
    }

    public function test_payment_status_is_derived_from_the_submitted_amount(): void
    {
        [$admin, $contract] = $this->paymentFixture();

        $this->actingAs($admin)
            ->put(route('payments.update', $contract), $this->payload([
                'status' => TeachingContract::STATUS_PARTIAL,
                'received_amount' => 10_000_000,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('teaching_contracts', [
            'id' => $contract->id,
            'received_amount' => 10_000_000,
            'status' => TeachingContract::STATUS_RECEIVED,
        ]);

        $this->actingAs($admin)
            ->put(route('payments.update', $contract), $this->payload([
                'status' => TeachingContract::STATUS_RECEIVED,
                'received_amount' => 0,
                'received_date' => '2026-09-05',
            ]))
            ->assertRedirect();

        $contract->refresh();

        $this->assertSame('0.00', $contract->received_amount);
        $this->assertSame(TeachingContract::STATUS_UNPAID, $contract->status);
        $this->assertNull($contract->received_date);
    }

    public function test_received_amount_cannot_exceed_contract_total_via_direct_http_request(): void
    {
        [$admin, $contract] = $this->paymentFixture();

        $this->actingAs($admin)
            ->from(route('payments.index'))
            ->put(route('payments.update', $contract), $this->payload([
                'received_amount' => 10_000_001,
            ]))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHasErrors([
                'received_amount' => 'Số tiền đã nhận không được lớn hơn tổng tiền hợp đồng.',
            ]);

        $contract->refresh();

        $this->assertSame('10000000.00', $contract->received_amount);
        $this->assertSame(TeachingContract::STATUS_RECEIVED, $contract->status);
    }

    public function test_student_cannot_update_a_payment_contract_via_direct_http_request(): void
    {
        [, $contract] = $this->paymentFixture();
        $student = $this->createUser(User::ROLE_STUDENT, 'student@example.com');

        $this->actingAs($student)
            ->put(route('payments.update', $contract), $this->payload([
                'received_amount' => 3_000_000,
            ]))
            ->assertForbidden();

        $this->assertDatabaseHas('teaching_contracts', [
            'id' => $contract->id,
            'received_amount' => 10_000_000,
            'status' => TeachingContract::STATUS_RECEIVED,
        ]);
    }

    /**
     * @return array{0: User, 1: TeachingContract}
     */
    private function paymentFixture(): array
    {
        $admin = $this->createUser(User::ROLE_ADMIN, 'admin@example.com');
        $teacher = $this->createUser(User::ROLE_TEACHER, 'teacher@example.com');

        $contract = TeachingContract::create([
            'teacher_id' => $teacher->id,
            'contract_number' => 'HD-2026-001',
            'signed_date' => '2026-09-01',
            'total_amount' => 10_000_000,
            'received_amount' => 10_000_000,
            'status' => TeachingContract::STATUS_RECEIVED,
            'received_date' => '2026-09-02',
        ]);

        return [$admin, $contract];
    }

    private function createUser(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'teacher_id' => User::query()->where('role', User::ROLE_TEACHER)->value('id'),
            'contract_number' => 'HD-2026-001',
            'signed_date' => '2026-09-01',
            'total_amount' => 10_000_000,
            'received_amount' => 10_000_000,
            'status' => TeachingContract::STATUS_RECEIVED,
            'received_date' => '2026-09-02',
            'evidence_url' => null,
            'note' => null,
        ], $overrides);
    }
}
