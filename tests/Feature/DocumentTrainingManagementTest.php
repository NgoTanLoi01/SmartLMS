<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentTrainingManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('DocumentTrainingManagementTest chỉ được phép chạy trên SQLite cô lập.');
        }

        $vectorConnection = config('database.connections.sqlite');
        $vectorConnection['database'] = ':memory:';
        config(['database.connections.pgsql' => $vectorConnection]);
        DB::purge('pgsql');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::connection('pgsql')->create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('document_name');
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::connection('pgsql')->dropIfExists('document_chunks');
        DB::purge('pgsql');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_admin_can_delete_a_global_training_document_when_course_id_is_empty(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        DB::connection('pgsql')->table('document_chunks')->insert([
            [
                'course_id' => null,
                'uploaded_by' => 999,
                'document_name' => 'tai-lieu-toan-he-thong.pdf',
                'content' => 'Đoạn kiến thức toàn hệ thống thứ nhất.',
                'is_active' => true,
            ],
            [
                'course_id' => null,
                'uploaded_by' => 999,
                'document_name' => 'tai-lieu-toan-he-thong.pdf',
                'content' => 'Đoạn kiến thức toàn hệ thống thứ hai.',
                'is_active' => true,
            ],
            [
                'course_id' => 12,
                'uploaded_by' => 999,
                'document_name' => 'tai-lieu-toan-he-thong.pdf',
                'content' => 'Đoạn kiến thức thuộc khóa học khác.',
                'is_active' => true,
            ],
        ]);

        $this->actingAs($admin)
            ->from(route('documents.index'))
            ->delete(route('documents.destroy', 'tai-lieu-toan-he-thong.pdf'), [
                'course_id' => '',
                'uploaded_by' => 999,
            ])
            ->assertRedirect(route('documents.index'))
            ->assertSessionHas('success', 'Đã xóa 2 đoạn kiến thức của tài liệu: tai-lieu-toan-he-thong.pdf');

        $this->assertDatabaseMissing('document_chunks', [
            'course_id' => null,
            'uploaded_by' => 999,
            'document_name' => 'tai-lieu-toan-he-thong.pdf',
        ], 'pgsql');
        $this->assertDatabaseHas('document_chunks', [
            'course_id' => 12,
            'uploaded_by' => 999,
            'document_name' => 'tai-lieu-toan-he-thong.pdf',
        ], 'pgsql');
    }
}
