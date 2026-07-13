<?php

namespace Tests\Feature;

use App\Models\Assignments;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LegacyLearningMaterialService;
use App\Services\StoredAssetReferenceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyLearningMaterialServiceTest extends TestCase
{
    private bool $isolatedSchemaCreated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('LegacyLearningMaterialServiceTest chỉ được phép chạy trên SQLite cô lập.');
        }

        $this->isolatedSchemaCreated = true;

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
            $table->timestamps();
        });
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('attachment')->nullable();
            $table->string('attachment_disk')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_mime_type')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->timestamps();
        });
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->longText('instructions')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type');
            $table->string('source_type');
            $table->string('disk')->nullable();
            $table->string('file_path')->nullable();
            $table->string('url')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('status');
            $table->string('storage_status')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
            $table->unique(['disk', 'file_path']);
        });
        Schema::create('learning_material_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_material_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->timestamps();
            $table->unique(['learning_material_id', 'source_type', 'source_id']);
        });
        Schema::create('learning_material_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_material_id');
            $table->unsignedBigInteger('course_id');
            $table->timestamps();
        });

        Storage::fake('r2');
        config(['filesystems.disks.r2.url' => 'https://assets.smartlms.test']);
    }

    protected function tearDown(): void
    {
        if ($this->isolatedSchemaCreated) {
            foreach (['learning_material_assignments', 'learning_material_sources', 'learning_materials', 'assignments', 'lessons', 'modules', 'courses', 'users'] as $table) {
                Schema::dropIfExists($table);
            }
        }
        parent::tearDown();
    }

    public function test_scan_and_sync_are_idempotent_and_only_include_teachers_courses(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $courseId = DB::table('courses')->insertGetId(['title' => 'Khóa của tôi', 'teacher_id' => $teacher->id]);
        $otherCourseId = DB::table('courses')->insertGetId(['title' => 'Khóa khác', 'teacher_id' => $otherTeacher->id]);
        $moduleId = DB::table('modules')->insertGetId(['course_id' => $courseId, 'title' => 'Chương 1']);
        $otherModuleId = DB::table('modules')->insertGetId(['course_id' => $otherCourseId, 'title' => 'Chương khác']);

        Storage::disk('r2')->put('lessons/legacy.pdf', 'pdf');
        Storage::disk('r2')->put('images/diagram.png', 'image');
        Storage::disk('r2')->put('lessons/other.pdf', 'other');

        Lesson::create([
            'module_id' => $moduleId,
            'title' => 'Bài cũ',
            'content' => '<p><img src="https://assets.smartlms.test/images/diagram.png?version=1"></p>',
            'attachment' => 'lessons/legacy.pdf',
            'attachment_disk' => 'r2',
            'attachment_original_name' => 'Giao trinh.pdf',
            'attachment_mime_type' => 'application/pdf',
            'attachment_size' => 3,
        ]);
        Lesson::create([
            'module_id' => $otherModuleId,
            'title' => 'Không được quét',
            'attachment' => 'lessons/other.pdf',
            'attachment_disk' => 'r2',
        ]);
        Assignments::create([
            'course_id' => $courseId,
            'title' => 'Bài tập cũ',
            'instructions' => '<a href="https://assets.smartlms.test/images/diagram.png">Ảnh dùng lại</a>',
        ]);

        $service = app(LegacyLearningMaterialService::class);
        $preview = $service->run($teacher, true);

        $this->assertSame(3, $preview['references']);
        $this->assertSame(2, $preview['unique_files']);
        $this->assertSame(2, $preview['available_files']);
        $this->assertDatabaseCount('learning_materials', 0);

        $firstSync = $service->run($teacher);
        $secondSync = $service->run($teacher);

        $this->assertSame(2, $firstSync['imported']);
        $this->assertSame(0, $secondSync['imported']);
        $this->assertSame(2, $secondSync['already_indexed']);
        $this->assertDatabaseCount('learning_materials', 2);
        $this->assertDatabaseCount('learning_material_sources', 3);
        $this->assertDatabaseHas('learning_materials', [
            'disk' => 'r2',
            'file_path' => 'lessons/legacy.pdf',
            'uploaded_by' => $teacher->id,
            'type' => 'pdf',
        ]);
    }

    public function test_indexed_asset_is_not_deleted_from_storage(): void
    {
        Storage::disk('r2')->put('lessons/shared.pdf', 'shared');
        LearningMaterial::create([
            'title' => 'Shared',
            'type' => 'pdf',
            'source_type' => 'file',
            'disk' => 'r2',
            'file_path' => 'lessons/shared.pdf',
            'status' => 'published',
        ]);

        $deleted = app(StoredAssetReferenceService::class)->deleteIfUnindexed('r2', 'lessons/shared.pdf');

        $this->assertFalse($deleted);
        Storage::disk('r2')->assertExists('lessons/shared.pdf');
    }
}
