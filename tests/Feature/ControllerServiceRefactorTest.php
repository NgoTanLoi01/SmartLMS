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
use Illuminate\Support\Facades\Hash;
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
            $table->unsignedInteger('template_version')->default(1);
            $table->unsignedBigInteger('source_template_id')->nullable();
            $table->unsignedInteger('synced_template_version')->nullable();
            $table->json('template_section_versions')->nullable();
            $table->json('template_sync_state')->nullable();
            $table->string('status')->default('published');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('template_origin_id')->nullable();
            $table->string('title');
            $table->unsignedInteger('order')->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('template_origin_id')->nullable();
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
            $table->unsignedBigInteger('template_origin_id')->nullable();
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
            $table->unsignedBigInteger('template_origin_id')->nullable();
            $table->string('title');
            $table->unsignedInteger('time_limit')->nullable();
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->boolean('is_random')->default(false);
            $table->unsignedInteger('easy_count')->default(0);
            $table->unsignedInteger('medium_count')->default(0);
            $table->unsignedInteger('hard_count')->default(0);
            $table->json('question_distribution')->nullable();
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
            $table->unsignedBigInteger('template_origin_id')->nullable();
            $table->unsignedBigInteger('question_bank_id')->nullable();
            $table->string('question_type')->default('single_choice');
            $table->text('question_text');
            $table->json('answer_config')->nullable();
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
        if ($this->usesIsolatedSqliteDatabase()) {
            foreach (['options', 'questions', 'course_question_bank', 'question_banks', 'quizzes', 'assignments', 'lessons', 'modules', 'courses', 'users'] as $table) {
                Schema::dropIfExists($table);
            }
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

    public function test_template_sync_updates_selected_sections_without_replacing_delivery_records(): void
    {
        $teacher = User::create([
            'name' => 'Teacher',
            'email' => 'template-sync@example.test',
            'password' => Hash::make('password'),
            'role' => 'teacher',
        ]);
        $template = Course::create([
            'title' => 'Mẫu PHP',
            'teacher_id' => $teacher->id,
            'course_type' => 'template',
            'status' => 'published',
        ]);
        $module = Module::create(['course_id' => $template->id, 'title' => 'Chương cũ', 'order' => 1, 'status' => 'published']);
        $lesson = Lesson::create(['module_id' => $module->id, 'title' => 'Bài cũ', 'order' => 1, 'status' => 'published']);
        $assignment = Assignments::create([
            'course_id' => $template->id,
            'lesson_id' => $lesson->id,
            'type' => 'essay',
            'title' => 'Bài tập cũ',
            'instructions' => 'Mô tả',
            'grading_scale' => 10,
            'status' => 'published',
        ]);
        $quiz = Quiz::create([
            'course_id' => $template->id,
            'title' => 'Quiz cũ',
            'time_limit' => 20,
            'max_attempts' => 1,
            'is_random' => true,
            'easy_count' => 1,
            'medium_count' => 0,
            'hard_count' => 0,
            'status' => 'published',
        ]);
        $delivery = Course::create([
            'title' => 'Lớp PHP',
            'teacher_id' => $teacher->id,
            'course_type' => 'delivery',
            'status' => 'published',
        ]);

        $service = app(CourseCloningService::class);
        $service->cloneContent($template->fresh(), $delivery);
        $deliveryModule = Module::where('course_id', $delivery->id)->where('template_origin_id', $module->id)->firstOrFail();
        $deliveryAssignment = Assignments::where('course_id', $delivery->id)->where('template_origin_id', $assignment->id)->firstOrFail();
        $deliveryQuiz = Quiz::where('course_id', $delivery->id)->where('template_origin_id', $quiz->id)->firstOrFail();
        Module::create(['course_id' => $delivery->id, 'title' => 'Nội dung riêng', 'order' => 99, 'status' => 'published']);

        $module->update(['title' => 'Chương mới']);
        $assignment->update(['title' => 'Bài tập mới']);
        $quiz->update(['title' => 'Quiz mới', 'max_attempts' => 3]);

        $service->syncFromTemplate($template->fresh(), $delivery->fresh(), ['content']);
        $this->assertSame('Chương mới', $deliveryModule->fresh()->title);
        $this->assertSame('Bài tập cũ', $deliveryAssignment->fresh()->title);
        $this->assertSame('Quiz cũ', $deliveryQuiz->fresh()->title);
        $this->assertDatabaseHas('modules', ['course_id' => $delivery->id, 'title' => 'Nội dung riêng']);

        $service->syncFromTemplate($template->fresh(), $delivery->fresh(), ['assignments', 'quizzes']);
        $this->assertSame($deliveryAssignment->id, Assignments::where('course_id', $delivery->id)->where('template_origin_id', $assignment->id)->firstOrFail()->id);
        $this->assertSame('Bài tập mới', $deliveryAssignment->fresh()->title);
        $this->assertSame($deliveryQuiz->id, Quiz::where('course_id', $delivery->id)->where('template_origin_id', $quiz->id)->firstOrFail()->id);
        $this->assertSame('Quiz mới', $deliveryQuiz->fresh()->title);
        $this->assertSame(3, $deliveryQuiz->fresh()->max_attempts);
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
