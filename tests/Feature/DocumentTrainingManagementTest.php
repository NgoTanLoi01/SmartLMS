<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DeepSeekService;
use App\Services\RagDocumentAccessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
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

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('course_type')->default('delivery');
            $table->string('status')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('feature');
            $table->string('status')->default('queued');
            $table->timestamps();
        });

        Schema::create('smart_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type')->nullable();
            $table->string('title');
            $table->text('message');
            $table->timestamp('read_at')->nullable();
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
        if ($this->usesIsolatedSqliteDatabase()) {
            Schema::connection('pgsql')->dropIfExists('document_chunks');
            DB::purge('pgsql');
            Schema::dropIfExists('smart_notifications');
            Schema::dropIfExists('ai_operations');
            Schema::dropIfExists('courses');
            Schema::dropIfExists('users');
        }

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

    public function test_teacher_only_sees_training_documents_from_courses_they_manage(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $ownCourseId = DB::table('courses')->insertGetId([
            'title' => 'Khóa học của giáo viên hiện tại',
            'teacher_id' => $teacher->id,
            'course_type' => 'delivery',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherCourseId = DB::table('courses')->insertGetId([
            'title' => 'Khóa học giáo viên khác',
            'teacher_id' => $otherTeacher->id,
            'course_type' => 'delivery',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('pgsql')->table('document_chunks')->insert([
            $this->documentRow($ownCourseId, $teacher->id, 'tai-lieu-duoc-phep.pdf'),
            $this->documentRow($otherCourseId, $otherTeacher->id, 'tai-lieu-khoa-khac.pdf'),
            $this->documentRow(null, $otherTeacher->id, 'tai-lieu-global-cua-admin.pdf'),
        ]);

        $this->actingAs($teacher)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('tai-lieu-duoc-phep.pdf')
            ->assertDontSee('tai-lieu-khoa-khac.pdf')
            ->assertDontSee('tai-lieu-global-cua-admin.pdf');
    }

    public function test_course_scoped_rag_retrieval_includes_only_global_and_current_course_documents(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $ownCourseId = DB::table('courses')->insertGetId([
            'title' => 'Khóa học đang mở trong chatbot',
            'teacher_id' => $teacher->id,
            'course_type' => 'delivery',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherCourseId = DB::table('courses')->insertGetId([
            'title' => 'Khóa học không liên quan',
            'teacher_id' => $otherTeacher->id,
            'course_type' => 'delivery',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('pgsql')->table('document_chunks')->insert([
            $this->documentRow($ownCourseId, $teacher->id, 'nguon-khoa-hien-tai.pdf'),
            $this->documentRow($otherCourseId, $otherTeacher->id, 'nguon-khoa-khac.pdf'),
            $this->documentRow(null, $otherTeacher->id, 'nguon-toan-he-thong.pdf'),
        ]);

        $query = DB::connection('pgsql')->table('document_chunks')->where('is_active', true);
        $names = app(RagDocumentAccessService::class)
            ->scopeForRetrieval($query, $teacher, $ownCourseId)
            ->pluck('document_name')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([
            'nguon-khoa-hien-tai.pdf',
            'nguon-toan-he-thong.pdf',
        ], $names);
    }

    public function test_teacher_cannot_delete_rag_document_from_another_course_by_direct_request(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherCourseId = DB::table('courses')->insertGetId([
            'title' => 'Khóa học ngoài phạm vi',
            'teacher_id' => $otherTeacher->id,
            'course_type' => 'delivery',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('pgsql')->table('document_chunks')->insert(
            $this->documentRow($otherCourseId, $otherTeacher->id, 'khong-duoc-xoa.pdf')
        );

        $this->actingAs($teacher)
            ->delete(route('documents.destroy', 'khong-duoc-xoa.pdf'), [
                'course_id' => $otherCourseId,
                'uploaded_by' => $otherTeacher->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('document_chunks', [
            'course_id' => $otherCourseId,
            'document_name' => 'khong-duoc-xoa.pdf',
        ], 'pgsql');
    }

    public function test_chatbot_rejects_direct_course_context_outside_teacher_scope(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherCourseId = DB::table('courses')->insertGetId([
            'title' => 'Khóa học không được phép dùng làm ngữ cảnh',
            'teacher_id' => $otherTeacher->id,
            'course_type' => 'delivery',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $deepSeek = Mockery::mock(DeepSeekService::class);
        $deepSeek->shouldNotReceive('sendMessage');
        $this->app->instance(DeepSeekService::class, $deepSeek);

        $this->actingAs($teacher)
            ->postJson(route('chatbot.send'), [
                'messages' => [[
                    'role' => 'user',
                    'content' => 'Hãy trả lời từ tài liệu khóa học này.',
                ]],
                'lesson_context' => ['course_id' => $otherCourseId],
            ])
            ->assertForbidden();
    }

    private function documentRow(?int $courseId, int $uploadedBy, string $name): array
    {
        return [
            'course_id' => $courseId,
            'uploaded_by' => $uploadedBy,
            'document_name' => $name,
            'content' => 'Nội dung kiểm thử phạm vi tài liệu.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
