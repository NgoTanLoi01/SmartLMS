<?php

namespace Tests\Feature;

use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialAssignment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\QuizPassage;
use App\Models\User;
use App\Services\CourseCloningService;
use App\Services\SubmissionFileService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator;
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
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('user_id');
            $table->string('file_path')->nullable();
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type')->default('document');
            $table->string('source_type')->default('file');
            $table->string('disk')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('status')->default('published');
            $table->timestamps();
        });
        Schema::create('learning_material_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_material_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->unsignedBigInteger('unlock_when_lesson_id')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->string('status')->default('published');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
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
        Schema::create('quiz_passages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->longText('content');
            $table->string('source_label')->nullable();
            $table->timestamps();
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('template_origin_id')->nullable();
            $table->unsignedBigInteger('question_bank_id')->nullable();
            $table->unsignedBigInteger('quiz_passage_id')->nullable();
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
            foreach (['options', 'questions', 'quiz_passages', 'course_question_bank', 'question_banks', 'quizzes', 'learning_material_assignments', 'learning_materials', 'assignment_submissions', 'assignments', 'lessons', 'modules', 'courses', 'users'] as $table) {
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

    public function test_individual_module_clone_is_draft_and_never_copies_submissions_or_grades(): void
    {
        Storage::fake('public');
        config(['filesystems.lesson_attachment_disk' => 'public']);
        Storage::disk('public')->put('lessons/source.pdf', 'lesson-content');

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $course = Course::create([
            'title' => 'Khóa PHP',
            'teacher_id' => $teacher->id,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'PHP cơ bản',
            'order' => 1,
            'status' => Module::STATUS_PUBLISHED,
        ]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Biến và kiểu dữ liệu',
            'attachment' => 'lessons/source.pdf',
            'attachment_disk' => 'public',
            'attachment_original_name' => 'source.pdf',
            'order' => 1,
            'status' => Lesson::STATUS_PUBLISHED,
        ]);
        $assignment = Assignments::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'type' => 'file',
            'title' => 'Bài tập biến',
            'instructions' => 'Nộp bài thực hành',
            'grading_rubric' => 'Đúng cú pháp: 5 điểm',
            'grading_scale' => 10,
            'due_date' => now()->addWeek(),
            'status' => Assignments::STATUS_PUBLISHED,
        ]);
        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'grade' => 9,
            'submitted_at' => now(),
        ]);
        $material = LearningMaterial::create([
            'title' => 'Slide PHP',
            'type' => 'slide',
            'source_type' => LearningMaterial::SOURCE_FILE,
            'disk' => 'public',
            'file_path' => 'materials/php.pptx',
            'uploaded_by' => $teacher->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);
        LearningMaterialAssignment::create([
            'learning_material_id' => $material->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'status' => LearningMaterialAssignment::STATUS_PUBLISHED,
        ]);

        $result = app(CourseCloningService::class)->cloneModule($module, [
            'copy_assignments' => true,
            'copy_materials' => true,
            'copy_rubrics' => true,
        ]);

        $copy = $result['model']->fresh(['lessons.assignments']);
        $copiedLesson = $copy->lessons->firstOrFail();
        $copiedAssignment = $copiedLesson->assignments->firstOrFail();

        $this->assertSame(Module::STATUS_DRAFT, $copy->status);
        $this->assertSame(Lesson::STATUS_DRAFT, $copiedLesson->status);
        $this->assertSame(Assignments::STATUS_DRAFT, $copiedAssignment->status);
        $this->assertSame($assignment->grading_rubric, $copiedAssignment->grading_rubric);
        $this->assertNotSame($lesson->attachment, $copiedLesson->attachment);
        $this->assertSame(1, AssignmentSubmission::count());
        $this->assertFalse($copiedAssignment->submissions()->exists());
        $this->assertDatabaseHas('learning_material_assignments', [
            'learning_material_id' => $material->id,
            'course_id' => $course->id,
            'lesson_id' => $copiedLesson->id,
            'status' => LearningMaterialAssignment::STATUS_HIDDEN,
        ]);
    }

    public function test_individual_lesson_assignment_and_quiz_can_be_copied_to_another_course(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $sourceCourse = Course::create(['title' => 'Khóa nguồn', 'teacher_id' => $teacher->id, 'status' => Course::STATUS_PUBLISHED]);
        $targetCourse = Course::create(['title' => 'Khóa đích', 'teacher_id' => $teacher->id, 'status' => Course::STATUS_PUBLISHED]);
        $sourceModule = Module::create(['course_id' => $sourceCourse->id, 'title' => 'Nguồn', 'status' => Module::STATUS_PUBLISHED]);
        $targetModule = Module::create(['course_id' => $targetCourse->id, 'title' => 'Đích', 'status' => Module::STATUS_PUBLISHED]);
        $sourceLesson = Lesson::create(['module_id' => $sourceModule->id, 'title' => 'Bài nguồn', 'status' => Lesson::STATUS_PUBLISHED]);
        $targetLesson = Lesson::create(['module_id' => $targetModule->id, 'title' => 'Bài đích', 'status' => Lesson::STATUS_PUBLISHED]);
        $sourceAssignment = Assignments::create([
            'course_id' => $sourceCourse->id,
            'lesson_id' => $sourceLesson->id,
            'title' => 'Bài tập nguồn',
            'instructions' => 'Yêu cầu',
            'grading_rubric' => 'Rubric nguồn',
            'due_date' => now()->addDay(),
            'status' => Assignments::STATUS_PUBLISHED,
        ]);
        $sourceQuiz = Quiz::create([
            'course_id' => $sourceCourse->id,
            'title' => 'Quiz nguồn',
            'time_limit' => 20,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
        $bank = QuestionBank::create(['name' => 'Ngân hàng dùng chung', 'teacher_id' => $teacher->id]);
        $sourceCourse->questionBanks()->attach($bank);
        $passage = QuizPassage::create([
            'course_id' => $sourceCourse->id,
            'title' => 'Đoạn văn PHP',
            'content' => 'Nội dung dùng chung cho nhóm câu hỏi.',
            'source_label' => 'Tài liệu nội bộ',
        ]);
        $sourceQuestion = Question::create([
            'course_id' => $sourceCourse->id,
            'question_bank_id' => null,
            'quiz_passage_id' => $passage->id,
            'question_text' => 'PHP là viết tắt của gì?',
            'difficulty' => 'easy',
            'status' => Question::STATUS_PUBLISHED,
        ]);
        $sourceQuestion->options()->create(['option_text' => 'PHP Hypertext Preprocessor', 'is_correct' => true]);

        $lessonResult = app(CourseCloningService::class)->cloneLesson($sourceLesson, $targetModule);
        $assignmentResult = app(CourseCloningService::class)->cloneAssignment($sourceAssignment, $targetLesson, false);
        $quizResult = app(CourseCloningService::class)->cloneQuiz($sourceQuiz, $targetCourse, true);

        $this->assertSame($targetModule->id, $lessonResult['model']->module_id);
        $this->assertSame(Lesson::STATUS_DRAFT, $lessonResult['model']->status);
        $this->assertSame($targetCourse->id, $assignmentResult['model']->course_id);
        $this->assertNull($assignmentResult['model']->grading_rubric);
        $this->assertSame(Assignments::STATUS_DRAFT, $assignmentResult['model']->status);
        $this->assertSame($targetCourse->id, $quizResult['model']->course_id);
        $this->assertSame(Quiz::STATUS_DRAFT, $quizResult['model']->status);
        $this->assertTrue($targetCourse->questionBanks()->whereKey($bank->id)->exists());
        $copiedQuestion = Question::query()->where('course_id', $targetCourse->id)->firstOrFail();
        $this->assertSame($sourceQuestion->question_text, $copiedQuestion->question_text);
        $this->assertSame(1, $copiedQuestion->options()->count());
        $this->assertSame('Đoạn văn PHP', $copiedQuestion->passage?->title);
        $this->assertSame($targetCourse->id, $copiedQuestion->passage?->course_id);
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
<x-ui.role-badge role="admin" />
<x-ui.status-badge status="published" />
<x-ui.empty-state title="Chưa có khóa học" description="Hãy tạo khóa học đầu tiên." />
BLADE);

        $this->assertStringContainsString('<header', $html);
        $this->assertStringContainsString('aria-label="breadcrumb"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('lms-stat danger', $html);
        $this->assertStringContainsString('Quản trị viên', $html);
        $this->assertStringContainsString('Đang sử dụng', $html);
        $this->assertStringContainsString('Chưa có khóa học', $html);
    }

    public function test_shared_pagination_is_compact_and_fully_localized(): void
    {
        $paginator = new LengthAwarePaginator(
            range(1, 20),
            194,
            20,
            1,
            ['path' => '/users']
        );

        $html = Blade::render(
            '<x-ui.pagination :paginator="$paginator" item-label="tài khoản" />',
            compact('paginator')
        );

        $this->assertStringContainsString('Hiển thị', $html);
        $this->assertStringContainsString('194', $html);
        $this->assertStringContainsString('tài khoản', $html);
        $this->assertStringContainsString('aria-label="Trang sau"', $html);
        $this->assertStringNotContainsString('Showing', $html);
    }
}
