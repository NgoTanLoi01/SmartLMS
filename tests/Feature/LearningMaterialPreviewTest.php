<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialAssignment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearningMaterialPreviewTest extends TestCase
{
    private User $teacher;

    private User $student;

    private User $outsideStudent;

    private Course $course;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireIsolatedSqliteDatabase();
        $this->createSchema();
        Storage::fake('public');

        $this->teacher = $this->user('preview-teacher@example.com', User::ROLE_TEACHER);
        $this->student = $this->user('preview-student@example.com', User::ROLE_STUDENT);
        $this->outsideStudent = $this->user('preview-outside@example.com', User::ROLE_STUDENT);
        $this->course = Course::create([
            'title' => 'Khóa học có học liệu',
            'teacher_id' => $this->teacher->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $this->classroom = Classroom::create([
            'name' => 'Lớp xem học liệu',
            'teacher_id' => $this->teacher->id,
            'status' => Classroom::STATUS_ACTIVE,
        ]);
        DB::table('class_course')->insert([
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
        ]);
        DB::table('class_user')->insert([
            'class_id' => $this->classroom->id,
            'user_id' => $this->student->id,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            foreach ([
                'smart_notifications', 'learning_material_sources', 'learning_material_assignments', 'learning_materials',
                'lessons', 'modules', 'class_course', 'class_user', 'classes', 'courses', 'users',
            ] as $table) {
                Schema::dropIfExists($table);
            }
        }

        parent::tearDown();
    }

    public function test_supported_material_formats_are_detected_from_a_safe_extension_allowlist(): void
    {
        $expectations = [
            'lesson.pdf' => ['pdf', 'application/pdf'],
            'diagram.webp' => ['image', 'image/webp'],
            'recording.mp4' => ['video', 'video/mp4'],
            'notes.md' => ['text', 'text/plain; charset=UTF-8'],
            'sample.html' => ['text', 'text/plain; charset=UTF-8'],
            'slides.pptx' => [null, 'application/octet-stream'],
            'vector.svg' => [null, 'application/octet-stream'],
        ];

        foreach ($expectations as $name => [$type, $contentType]) {
            $material = new LearningMaterial([
                'source_type' => LearningMaterial::SOURCE_FILE,
                'original_name' => $name,
            ]);

            $this->assertSame($type, $material->previewType(), $name);
            $this->assertSame($contentType, $material->previewContentType(), $name);
        }
    }

    public function test_teacher_can_preview_library_files_inline_with_security_headers(): void
    {
        $material = $this->material('tai-lieu.pdf', 'application/pdf');

        $response = $this->actingAs($this->teacher)
            ->get(route('materials.library.preview', $material))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        $this->assertStringStartsWith('inline;', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_course_material_page_previews_supported_files_and_downloads_only_unsupported_files(): void
    {
        $image = $this->material('so-do.png', 'image/png');
        $slides = $this->material(
            'bai-giang.pptx',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        );
        $imageAssignment = $this->assign($image);
        $slidesAssignment = $this->assign($slides);

        $response = $this->actingAs($this->teacher)
            ->get(route('courses.materials.index', $this->course))
            ->assertOk()
            ->assertSee(route('materials.preview', $imageAssignment))
            ->assertSee(route('materials.download', $slidesAssignment))
            ->assertSee('data-material-preview', false);

        $this->assertSame(1, substr_count($response->getContent(), 'id="materialPreviewModal"'));
        $this->assertStringNotContainsString(route('materials.download', $imageAssignment), $response->getContent());
    }

    public function test_text_and_video_materials_use_browser_safe_content_types(): void
    {
        $html = $this->material('vi-du.html', 'text/html', '<script>alert(1)</script>');
        $video = $this->material('bai-giang.webm', 'application/octet-stream');

        $this->actingAs($this->teacher)
            ->get(route('materials.library.preview', $html))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($this->teacher)
            ->get(route('materials.library.preview', $video))
            ->assertOk()
            ->assertHeader('Content-Type', 'video/webm');
    }

    public function test_unsupported_format_is_not_previewed_and_remains_downloadable(): void
    {
        $material = $this->material(
            'bai-trinh-chieu.pptx',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        );

        $this->actingAs($this->teacher)
            ->get(route('materials.library.preview', $material))
            ->assertNotFound();

        $response = $this->actingAs($this->teacher)
            ->get(route('materials.library.download', $material))
            ->assertOk();

        $this->assertStringStartsWith('attachment;', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_student_can_preview_visible_course_material_but_outside_student_cannot_bypass_course_scope(): void
    {
        $material = $this->material('minh-hoa.png', 'image/png');
        $assignment = LearningMaterialAssignment::create([
            'learning_material_id' => $material->id,
            'course_id' => $this->course->id,
            'status' => LearningMaterialAssignment::STATUS_PUBLISHED,
        ]);

        $this->actingAs($this->student)
            ->get(route('materials.preview', $assignment))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->actingAs($this->outsideStudent)
            ->get(route('materials.preview', $assignment))
            ->assertForbidden();

        $assignment->update(['status' => LearningMaterialAssignment::STATUS_HIDDEN]);

        $this->actingAs($this->student)
            ->get(route('materials.preview', $assignment))
            ->assertForbidden();
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

    private function material(string $name, string $mimeType, string $contents = 'preview-content'): LearningMaterial
    {
        $path = 'course-materials/'.$name;
        Storage::disk('public')->put($path, $contents);

        return LearningMaterial::create([
            'title' => pathinfo($name, PATHINFO_FILENAME),
            'type' => 'document',
            'source_type' => LearningMaterial::SOURCE_FILE,
            'disk' => 'public',
            'file_path' => $path,
            'original_name' => $name,
            'mime_type' => $mimeType,
            'file_size' => strlen($contents),
            'uploaded_by' => $this->teacher->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);
    }

    private function assign(LearningMaterial $material): LearningMaterialAssignment
    {
        return LearningMaterialAssignment::create([
            'learning_material_id' => $material->id,
            'course_id' => $this->course->id,
            'status' => LearningMaterialAssignment::STATUS_PUBLISHED,
        ]);
    }

    private function createSchema(): void
    {
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
        Schema::create('smart_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->json('data')->nullable();
            $table->string('dedupe_key')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('teacher_id');
            $table->string('course_type')->default('delivery');
            $table->string('status');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('teacher_id');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('class_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('class_course', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('course_id');
        });
        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title')->nullable();
            $table->integer('order')->default(0);
            $table->string('status')->default('published');
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('title')->nullable();
            $table->string('status')->default('published');
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('learning_materials', function (Blueprint $table): void {
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
            $table->timestamps();
        });
        Schema::create('learning_material_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('learning_material_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->unsignedBigInteger('unlock_when_lesson_id')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->string('status');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('learning_material_sources', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('learning_material_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->timestamps();
        });
    }
}
