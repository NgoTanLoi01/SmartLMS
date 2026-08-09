<?php

namespace Tests\Feature;

use App\Application\Gradebook\FinalizeGrades;
use App\Application\Gradebook\ProjectAssessmentGrade;
use App\Domain\Gradebook\GradebookException;
use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Grade;
use App\Models\GradeCategory;
use App\Models\GradeItem;
use App\Models\GradingPeriod;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GradebookProjectionTest extends TestCase
{
    private User $teacher;

    private User $student;

    private Course $course;

    private GradingPeriod $period;

    private GradeCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireIsolatedSqliteDatabase();
        $this->createLegacySchema();
        $this->migration()->up();

        $this->teacher = User::create(['name' => 'Teacher', 'email' => 'projection-teacher@example.test', 'password' => 'unused', 'role' => User::ROLE_TEACHER]);
        $this->student = User::create(['name' => 'Student', 'email' => 'projection-student@example.test', 'password' => 'unused', 'role' => User::ROLE_STUDENT]);
        $this->course = Course::create(['title' => 'Projection', 'teacher_id' => $this->teacher->id, 'course_type' => 'delivery', 'status' => Course::STATUS_PUBLISHED]);
        $this->period = GradingPeriod::create([
            'course_id' => $this->course->id, 'code' => 'hk1', 'name' => 'Học kỳ 1',
            'status' => GradingPeriod::STATUS_OPEN, 'missing_policy' => GradingPeriod::MISSING_BLOCK,
            'rounding_precision' => 1, 'rounding_mode' => 'half_up',
        ]);
        $this->category = GradeCategory::create([
            'course_id' => $this->course->id, 'grading_period_id' => $this->period->id,
            'code' => 'assessment', 'name' => 'Đánh giá', 'weight_percent' => 100,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            $this->migration()->down();
            foreach (['quiz_sessions', 'quiz_attempts', 'quizzes', 'assignment_submissions', 'assignments', 'courses', 'users'] as $table) {
                Schema::dropIfExists($table);
            }
        }
        parent::tearDown();
    }

    public function test_assignment_grade_is_projected_idempotently_without_changing_legacy_source(): void
    {
        $assignment = Assignments::create([
            'course_id' => $this->course->id, 'title' => 'Bài tập', 'grading_scale' => 10,
            'status' => Assignments::STATUS_PUBLISHED,
        ]);
        $item = $this->item(GradeItem::TYPE_ASSIGNMENT, GradeItem::SOURCE_ASSIGNMENT, $assignment->id, 'assignment-1');
        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id, 'user_id' => $this->student->id,
            'grade' => 8.5, 'submitted_at' => now(),
        ]);
        $service = app(ProjectAssessmentGrade::class);

        $this->assertSame(1, $service->assignment($submission, $this->teacher));
        $version = Grade::where('grade_item_id', $item->id)->firstOrFail()->version;
        $this->assertSame(1, $service->assignment($submission->fresh(), $this->teacher));

        $grade = Grade::where('grade_item_id', $item->id)->firstOrFail();
        $this->assertSame('8.5000', $grade->raw_points);
        $this->assertSame($version, $grade->version);
        $this->assertEquals(8.5, $submission->fresh()->grade);
        $this->assertDatabaseCount('grade_change_logs', 1);
    }

    public function test_quiz_projection_uses_explicit_highest_released_attempt_policy(): void
    {
        $quizId = DB::table('quizzes')->insertGetId([
            'course_id' => $this->course->id, 'title' => 'Quiz', 'status' => 'published',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $item = $this->item(GradeItem::TYPE_QUIZ, GradeItem::SOURCE_QUIZ, $quizId, 'quiz-1', 'highest_released');
        $olderHigher = $this->attempt($quizId, 1, '9', now()->subHour());
        $newerLower = $this->attempt($quizId, 2, '7', now());

        app(ProjectAssessmentGrade::class)->quiz($newerLower, $this->teacher);

        $grade = Grade::where('grade_item_id', $item->id)->firstOrFail();
        $this->assertSame('9.0000', $grade->raw_points);
        $this->assertStringContainsString('quiz_attempt:'.$olderHigher->id.':', $grade->source_version);
    }

    public function test_projection_enforces_finalization_after_explicit_read_cutover(): void
    {
        $assignment = Assignments::create([
            'course_id' => $this->course->id, 'title' => 'Bài tập cutover', 'grading_scale' => 10,
            'status' => Assignments::STATUS_PUBLISHED,
        ]);
        $this->item(GradeItem::TYPE_ASSIGNMENT, GradeItem::SOURCE_ASSIGNMENT, $assignment->id, 'assignment-cutover');
        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id, 'user_id' => $this->student->id,
            'grade' => 8, 'submitted_at' => now(),
        ]);
        $projection = app(ProjectAssessmentGrade::class);
        $projection->assignment($submission, $this->teacher);
        app(FinalizeGrades::class)->finalize($this->period, $this->student, $this->teacher, 'cutover-finalize');
        config()->set('gradebook.read_source', 'gradebook');
        $submission->update(['grade' => 9]);

        $this->expectException(GradebookException::class);
        $this->expectExceptionMessage('reopen');

        $projection->assignment($submission->fresh(), $this->teacher);
    }

    private function item(string $type, string $sourceType, int $sourceId, string $code, ?string $attemptPolicy = null): GradeItem
    {
        return GradeItem::create([
            'course_id' => $this->course->id, 'grading_period_id' => $this->period->id,
            'grade_category_id' => $this->category->id, 'code' => $code, 'name' => $code,
            'item_type' => $type, 'source_type' => $sourceType, 'source_id' => $sourceId,
            'max_points' => 10, 'item_weight' => 1, 'attempt_policy' => $attemptPolicy,
            'is_published' => true,
        ]);
    }

    private function attempt(int $quizId, int $number, string $score, $completedAt): QuizAttempt
    {
        return QuizAttempt::create([
            'quiz_id' => $quizId, 'user_id' => $this->student->id, 'attempt_number' => $number,
            'status' => QuizAttempt::STATUS_RELEASED, 'score' => $score,
            'completed_at' => $completedAt, 'result_released_at' => $completedAt,
        ]);
    }

    private function createLegacySchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('teacher_id');
            $table->string('course_type')->default('delivery');
            $table->string('status')->default('published');
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->unsignedInteger('grading_scale')->default(10);
            $table->string('status')->default('published');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('quizzes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->string('status')->default('published');
            $table->timestamps();
        });
        Schema::create('quiz_attempts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('quiz_session_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('attempt_number');
            $table->string('status');
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('result_released_at')->nullable();
            $table->timestamps();
        });
        Schema::create('quiz_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->string('result_release_policy')->default('immediate');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('results_released_at')->nullable();
            $table->timestamps();
        });
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_08_09_140000_create_gradebook_foundation.php');
    }
}
