<?php

namespace Tests\Feature;

use App\Domain\Gradebook\GradebookException;
use App\Models\Assignments;
use App\Models\Course;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\User;
use App\Services\GradebookMigrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GradebookBackfillTest extends TestCase
{
    private User $teacher;

    private User $studentOne;

    private User $studentTwo;

    private Course $course;

    private int $columnId;

    private int $assignmentId;

    private int $quizId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireIsolatedSqliteDatabase();
        $this->createSourceSchema();
        $this->migration()->up();
        $this->seedSources();
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            $this->migration()->down();
            foreach (['quiz_attempts', 'quiz_sessions', 'quizzes', 'assignment_submissions', 'assignments', 'attendance_data', 'attendance_columns', 'courses', 'users'] as $table) {
                Schema::dropIfExists($table);
            }
        }
        parent::tearDown();
    }

    public function test_discovery_never_approves_or_guesses_legacy_mapping(): void
    {
        $manifest = app(GradebookMigrationService::class)->discover($this->course);

        $this->assertFalse($manifest['approved']);
        $this->assertNull($manifest['approved_by']);
        $this->assertSame([], $manifest['items']);
        $this->assertTrue($manifest['discovery']['legacy_grade_columns'][0]['requires_mapping']);
        $this->assertSame(GradeItem::TYPE_HS1, $manifest['discovery']['legacy_grade_columns'][0]['suggested_item_type']);
        $this->assertSame(1, $manifest['discovery']['legacy_grade_columns'][0]['absence_values']);
    }

    public function test_dry_run_writes_nothing_then_backfill_is_idempotent_and_reconciles(): void
    {
        $service = app(GradebookMigrationService::class);
        $manifest = $this->approvedManifest();

        $dryRun = $service->backfill($manifest, true);
        $this->assertSame(4, $dryRun['planned_grades']);
        $this->assertSame(0, $dryRun['written']);
        $this->assertDatabaseCount('grading_periods', 0);
        $this->assertDatabaseCount('grades', 0);

        $result = $service->backfill($manifest, false);
        $this->assertSame([], $result['errors']);
        $this->assertSame(4, $result['written']);
        $this->assertDatabaseCount('grades', 4);
        $this->assertDatabaseHas('grades', [
            'user_id' => $this->studentOne->id,
            'status' => Grade::STATUS_GRADED,
            'raw_points' => 6.5,
        ]);
        $this->assertDatabaseHas('grades', [
            'user_id' => $this->studentTwo->id,
            'status' => Grade::STATUS_MISSING,
            'raw_points' => null,
        ]);
        $quizItem = GradeItem::where('code', 'quiz-1')->firstOrFail();
        $this->assertDatabaseHas('grades', [
            'grade_item_id' => $quizItem->id,
            'user_id' => $this->studentOne->id,
            'raw_points' => 9,
        ]);

        $logCount = DB::table('grade_change_logs')->count();
        $versions = Grade::orderBy('id')->pluck('version')->all();
        $service->backfill($manifest, false);
        $this->assertSame($logCount, DB::table('grade_change_logs')->count());
        $this->assertSame($versions, Grade::orderBy('id')->pluck('version')->all());

        $reconciliation = $service->reconcile($manifest);
        $this->assertTrue($reconciliation['passed']);
        $this->assertSame(4, $reconciliation['matched_count']);
        $this->assertSame(0, $reconciliation['mismatch_count']);

        Grade::where('grade_item_id', $quizItem->id)->update(['raw_points' => 5, 'effective_points' => 5]);
        $failed = $service->reconcile($manifest);
        $this->assertFalse($failed['passed']);
        $this->assertSame(1, $failed['mismatch_count']);
    }

    public function test_unresolved_absence_blocks_backfill_before_structure_is_created(): void
    {
        $manifest = $this->approvedManifest();
        unset($manifest['items'][0]['absence_policy']);

        $result = app(GradebookMigrationService::class)->backfill($manifest, false);

        $this->assertCount(1, $result['errors']);
        $this->assertDatabaseCount('grading_periods', 0);
        $this->assertDatabaseCount('grades', 0);
    }

    public function test_backfill_rejects_source_from_another_course(): void
    {
        $otherCourse = Course::create([
            'title' => 'Khóa khác',
            'teacher_id' => $this->teacher->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $foreignAssignmentId = DB::table('assignments')->insertGetId([
            'course_id' => $otherCourse->id,
            'title' => 'Bài tập ngoài course',
            'grading_scale' => 10,
            'status' => Assignments::STATUS_PUBLISHED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $manifest = $this->approvedManifest();
        $manifest['items'][1]['source_id'] = $foreignAssignmentId;

        $this->expectException(GradebookException::class);
        $this->expectExceptionMessage('Assignment source không thuộc course');

        app(GradebookMigrationService::class)->backfill($manifest, true);
    }

    public function test_backfill_rejects_existing_structure_that_drifted_from_manifest(): void
    {
        $service = app(GradebookMigrationService::class);
        $manifest = $this->approvedManifest();
        $service->backfill($manifest, false);
        DB::table('grade_categories')->where('code', 'assessment')->update(['weight_percent' => 50]);

        $this->expectException(GradebookException::class);
        $this->expectExceptionMessage('không khớp manifest');

        $service->backfill($manifest, false);
    }

    /** @return array<string,mixed> */
    private function approvedManifest(): array
    {
        return [
            'version' => 1,
            'run_id' => 'gradebook-backfill-test',
            'approved' => true,
            'approved_by' => $this->teacher->id,
            'course_id' => $this->course->id,
            'period' => [
                'code' => 'hk1-2026',
                'name' => 'Học kỳ 1',
                'status' => 'draft',
                'missing_policy' => 'block',
                'rounding_precision' => 1,
                'rounding_mode' => 'half_up',
            ],
            'categories' => [[
                'code' => 'assessment',
                'name' => 'Đánh giá',
                'weight_percent' => '100',
                'position' => 1,
            ]],
            'items' => [
                [
                    'code' => 'hs1-legacy', 'name' => 'HS1', 'category_code' => 'assessment',
                    'item_type' => 'hs1', 'source_type' => 'legacy_attendance', 'source_id' => $this->columnId,
                    'max_points' => '10', 'item_weight' => '1', 'absence_policy' => 'missing', 'is_published' => true,
                ],
                [
                    'code' => 'assignment-1', 'name' => 'Bài tập', 'category_code' => 'assessment',
                    'item_type' => 'assignment', 'source_type' => 'assignment', 'source_id' => $this->assignmentId,
                    'max_points' => '10', 'item_weight' => '1', 'is_published' => true,
                ],
                [
                    'code' => 'quiz-1', 'name' => 'Quiz', 'category_code' => 'assessment',
                    'item_type' => 'quiz', 'source_type' => 'quiz', 'source_id' => $this->quizId,
                    'max_points' => '10', 'item_weight' => '1', 'attempt_policy' => 'highest_released', 'is_published' => true,
                ],
            ],
        ];
    }

    private function seedSources(): void
    {
        $this->teacher = $this->user('backfill-teacher@example.test', User::ROLE_TEACHER);
        $this->studentOne = $this->user('backfill-student-1@example.test', User::ROLE_STUDENT);
        $this->studentTwo = $this->user('backfill-student-2@example.test', User::ROLE_STUDENT);
        $this->course = Course::create([
            'title' => 'Khóa backfill',
            'teacher_id' => $this->teacher->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $this->columnId = DB::table('attendance_columns')->insertGetId([
            'course_id' => $this->course->id,
            'name' => 'HS1',
            'type' => 'grade',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('attendance_data')->insert([
            [
                'attendance_column_id' => $this->columnId, 'user_id' => $this->studentOne->id,
                'value' => '6,5', 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'attendance_column_id' => $this->columnId, 'user_id' => $this->studentTwo->id,
                'value' => 'vắng', 'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
        $this->assignmentId = DB::table('assignments')->insertGetId([
            'course_id' => $this->course->id,
            'title' => 'Bài tập nguồn',
            'grading_scale' => 10,
            'status' => Assignments::STATUS_PUBLISHED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('assignment_submissions')->insert([
            'assignment_id' => $this->assignmentId,
            'user_id' => $this->studentOne->id,
            'grade' => 8,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->quizId = DB::table('quizzes')->insertGetId([
            'course_id' => $this->course->id,
            'title' => 'Quiz nguồn',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ([7, 9] as $attempt => $score) {
            DB::table('quiz_attempts')->insert([
                'quiz_id' => $this->quizId,
                'user_id' => $this->studentOne->id,
                'attempt_number' => $attempt + 1,
                'status' => 'released',
                'score' => $score,
                'completed_at' => now()->addMinutes($attempt),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function createSourceSchema(): void
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
        Schema::create('attendance_columns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('name');
            $table->string('type');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        Schema::create('attendance_data', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attendance_column_id');
            $table->unsignedBigInteger('user_id');
            $table->string('value')->nullable();
            $table->text('note')->nullable();
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
        Schema::create('quiz_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->string('result_release_policy')->default('immediate');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('results_released_at')->nullable();
            $table->timestamps();
        });
        Schema::create('quiz_attempts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('quiz_session_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('status');
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('result_released_at')->nullable();
            $table->timestamps();
        });
    }

    private function user(string $email, string $role): User
    {
        return User::create(['name' => $email, 'email' => $email, 'password' => 'unused', 'role' => $role]);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_08_09_140000_create_gradebook_foundation.php');
    }
}
