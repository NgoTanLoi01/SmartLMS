<?php

namespace Tests\Feature;

use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use App\Services\CourseCloningService;
use App\Services\SubmissionFileService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ControllerServiceRefactorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('ControllerServiceRefactorTest chỉ được phép chạy trên SQLite cô lập.');
        }

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
            $table->text('description')->nullable();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('learning_program_id')->nullable();
            $table->string('course_type')->default('delivery');
            $table->string('status')->default('published');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->unsignedInteger('order')->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('video_url')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment')->nullable();
            $table->string('attachment_disk')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_mime_type')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->string('type')->default('file');
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->text('grading_rubric')->nullable();
            $table->unsignedInteger('grading_scale')->default(10);
            $table->boolean('ai_grading_enabled')->default(false);
            $table->timestamp('due_date')->nullable();
            $table->string('allowed_extensions')->nullable();
            $table->unsignedInteger('max_file_size')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->unsignedInteger('time_limit')->nullable();
            $table->boolean('is_random')->default(false);
            $table->unsignedInteger('easy_count')->default(0);
            $table->unsignedInteger('medium_count')->default(0);
            $table->unsignedInteger('hard_count')->default(0);
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('teacher_id');
            $table->timestamps();
        });
        Schema::create('course_question_bank', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('question_bank_id');
            $table->timestamps();
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('question_bank_id')->nullable();
            $table->text('question_text');
            $table->string('difficulty')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach (['options', 'questions', 'course_question_bank', 'question_banks', 'quizzes', 'assignments', 'lessons', 'modules', 'courses', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_course_cloning_service_copies_learning_structure_and_attachment(): void
    {
        Storage::fake('public');
        config(['filesystems.lesson_attachment_disk' => 'public']);
        Storage::disk('public')->put('lessons/source.pdf', 'lesson-content');

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $source = Course::create([
            'title' => 'Khóa mẫu',
            'description' => 'Nội dung mẫu',
            'teacher_id' => $teacher->id,
            'course_type' => 'template',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $target = Course::create([
            'title' => 'Khóa triển khai',
            'description' => 'Nội dung triển khai',
            'teacher_id' => $teacher->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $module = Module::create([
            'course_id' => $source->id,
            'title' => 'Chương 1',
            'order' => 1,
            'status' => Module::STATUS_PUBLISHED,
        ]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Bài 1',
            'content' => 'Nội dung bài học',
            'attachment' => 'lessons/source.pdf',
            'attachment_disk' => 'public',
            'order' => 1,
            'status' => Lesson::STATUS_PUBLISHED,
        ]);
        Assignments::create([
            'course_id' => $source->id,
            'lesson_id' => $lesson->id,
            'type' => 'essay',
            'title' => 'Bài tập 1',
            'instructions' => 'Trả lời câu hỏi',
            'grading_scale' => 10,
            'status' => Assignments::STATUS_PUBLISHED,
        ]);
        Quiz::create([
            'course_id' => $source->id,
            'title' => 'Quiz 1',
            'time_limit' => 15,
            'is_random' => false,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        app(CourseCloningService::class)->cloneContent(
            $source->load(['modules.lessons', 'assignments', 'quizzes', 'questionBanks']),
            $target
        );

        $targetModule = $target->modules()->with('lessons')->firstOrFail();
        $targetLesson = $targetModule->lessons->first();
        $targetAssignment = $target->assignments()->firstOrFail();

        $this->assertSame('Chương 1', $targetModule->title);
        $this->assertSame('Bài 1', $targetLesson->title);
        $this->assertNotSame($lesson->attachment, $targetLesson->attachment);
        Storage::disk('public')->assertExists($targetLesson->attachment);
        $this->assertSame($targetLesson->id, $targetAssignment->lesson_id);
        $this->assertSame('Quiz 1', $target->quizzes()->firstOrFail()->title);
    }

    public function test_submission_file_service_detects_preview_types_and_deletes_stored_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('assignments/submission.pdf', 'pdf-content');

        $submission = new AssignmentSubmission([
            'file_path' => 'assignments/submission.pdf',
            'file_disk' => 'public',
            'original_filename' => 'Bai nop.pdf',
            'mime_type' => 'application/pdf',
        ]);
        $submission->id = 99;
        $files = app(SubmissionFileService::class);

        $this->assertSame('pdf', $files->previewType($submission));
        $this->assertStringContainsString('/submissions/99/file', $files->url($submission));

        $files->delete($submission);

        Storage::disk('public')->assertMissing('assignments/submission.pdf');
    }

    public function test_shared_ui_components_render_semantic_header_and_stat_card(): void
    {
        $html = Blade::render(<<<'BLADE'
<x-ui.page-header title="Tiến độ lớp" :breadcrumbs="[['label' => 'Lớp học', 'url' => '/classes'], ['label' => 'L01']]">
    <x-slot:meta><span>20 học sinh</span></x-slot:meta>
</x-ui.page-header>
<x-ui.stat-grid><x-ui.stat-card label="Cần chú ý" value="3" tone="danger" /></x-ui.stat-grid>
BLADE);

        $this->assertStringContainsString('<header', $html);
        $this->assertStringContainsString('aria-label="breadcrumb"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('lms-stat danger', $html);
    }
}
