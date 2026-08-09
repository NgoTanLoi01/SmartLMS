<?php

namespace Tests\Feature;

use App\Application\Gradebook\AdjustGrade;
use App\Application\Gradebook\FinalizeGrades;
use App\Application\Gradebook\RecordGrade;
use App\Domain\Gradebook\GradebookException;
use App\Domain\Gradebook\GradeCalculationService;
use App\Models\Course;
use App\Models\Grade;
use App\Models\GradeAdjustment;
use App\Models\GradeCategory;
use App\Models\GradeChangeLog;
use App\Models\GradeFinalization;
use App\Models\GradeItem;
use App\Models\GradingPeriod;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class GradebookFoundationTest extends TestCase
{
    private User $teacher;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireIsolatedSqliteDatabase();

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
            $table->string('status')->default(Course::STATUS_PUBLISHED);
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('class_course', function (Blueprint $table): void {
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('course_id');
        });
        Schema::create('class_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('smart_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type')->nullable();
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        $this->migration()->up();
        $this->teacher = $this->user('gradebook-teacher@example.test', User::ROLE_TEACHER);
        $this->student = $this->user('gradebook-student@example.test', User::ROLE_STUDENT);
        $this->course = Course::create([
            'title' => 'Khóa Gradebook',
            'teacher_id' => $this->teacher->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_PUBLISHED,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            $this->migration()->down();
            Schema::dropIfExists('smart_notifications');
            Schema::dropIfExists('class_user');
            Schema::dropIfExists('class_course');
            Schema::dropIfExists('classes');
            Schema::dropIfExists('courses');
            Schema::dropIfExists('users');
        }

        parent::tearDown();
    }

    public function test_hs1_hs2_exam_weights_and_final_rounding_are_consistent(): void
    {
        [$period, $items] = $this->vocationalPeriod();
        $recorder = app(RecordGrade::class);
        $recorder->handle($items['hs1'], $this->student, Grade::STATUS_GRADED, '8', $this->teacher);
        $recorder->handle($items['hs2'], $this->student, Grade::STATUS_GRADED, '7', $this->teacher);
        $recorder->handle($items['exam'], $this->student, Grade::STATUS_GRADED, '9', $this->teacher);

        $result = app(GradeCalculationService::class)->calculate($period, $this->student->id);

        $this->assertSame('8.333333333333', $result['unrounded_score']);
        $this->assertSame('8.3', $result['final_score']);
        $this->assertSame('7.333333333333', $result['categories'][0]['score']);
        $this->assertSame('9.000000000000', $result['categories'][1]['score']);
    }

    public function test_assignment_scale_quiz_weight_missing_and_graded_zero_are_distinct(): void
    {
        $period = $this->period(['missing_policy' => GradingPeriod::MISSING_BLOCK, 'rounding_precision' => 2]);
        $category = $this->category($period, 'assessment', 'Đánh giá', '100');
        $assignment = $this->item($period, $category, 'assignment', GradeItem::TYPE_ASSIGNMENT, '100', '1');
        $quiz = $this->item($period, $category, 'quiz', GradeItem::TYPE_QUIZ, '10', '1');
        $recorder = app(RecordGrade::class);
        $recorder->handle($assignment, $this->student, Grade::STATUS_GRADED, '80', $this->teacher);

        $this->expectException(GradebookException::class);
        app(GradeCalculationService::class)->calculate($period, $this->student->id);
    }

    public function test_missing_policy_exclude_and_zero_produce_different_results(): void
    {
        $period = $this->period(['missing_policy' => GradingPeriod::MISSING_EXCLUDE, 'rounding_precision' => 2]);
        $category = $this->category($period, 'assessment', 'Đánh giá', '100');
        $assignment = $this->item($period, $category, 'assignment', GradeItem::TYPE_ASSIGNMENT, '100', '1');
        $quiz = $this->item($period, $category, 'quiz', GradeItem::TYPE_QUIZ, '10', '1');
        $recorder = app(RecordGrade::class);
        $recorder->handle($assignment, $this->student, Grade::STATUS_GRADED, '80', $this->teacher);
        $recorder->handle($quiz, $this->student, Grade::STATUS_MISSING, null, $this->teacher);

        $excluded = app(GradeCalculationService::class)->calculate($period, $this->student->id);
        $this->assertSame('8.00', $excluded['final_score']);

        $period->update(['missing_policy' => GradingPeriod::MISSING_ZERO]);
        $zeroed = app(GradeCalculationService::class)->calculate($period->fresh(), $this->student->id);
        $this->assertSame('4.00', $zeroed['final_score']);

        $recorder->handle($quiz, $this->student, Grade::STATUS_GRADED, '0', $this->teacher);
        $gradedZero = app(GradeCalculationService::class)->calculate($period->fresh(), $this->student->id);
        $this->assertSame('4.00', $gradedZero['final_score']);
    }

    public function test_adjustments_are_idempotent_immutable_and_reversible(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'course', 'Tổng kết', '100');
        $item = $this->item($period, $category, 'manual', GradeItem::TYPE_MANUAL, '10', '1');
        $grade = app(RecordGrade::class)->handle(
            $item,
            $this->student,
            Grade::STATUS_GRADED,
            '8',
            $this->teacher,
        );
        $service = app(AdjustGrade::class);

        $bonus = $service->handle($grade, GradeAdjustment::TYPE_BONUS, '3', 'Thưởng tiến bộ', $this->teacher, 'bonus-1');
        $sameBonus = $service->handle($grade, GradeAdjustment::TYPE_BONUS, '3', 'Thưởng tiến bộ', $this->teacher, 'bonus-1');
        $this->assertSame($bonus->id, $sameBonus->id);
        $this->assertSame('10.0000', $grade->fresh()->effective_points);

        $service->reverse($bonus, 'Nhập nhầm', $this->teacher, 'reverse-bonus-1');
        $this->assertSame('8.0000', $grade->fresh()->effective_points);

        $override = $service->handle($grade, GradeAdjustment::TYPE_OVERRIDE, '6', 'Điều chỉnh có phê duyệt', $this->teacher, 'override-1');
        $this->assertSame('6.0000', $grade->fresh()->effective_points);
        $this->assertDatabaseCount('grade_adjustments', 3);

        $this->expectException(LogicException::class);
        $override->update(['reason' => 'Không được sửa ledger']);
    }

    public function test_finalize_locks_grade_until_reopen_and_keeps_audit_history(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'course', 'Tổng kết', '100');
        $item = $this->item($period, $category, 'manual', GradeItem::TYPE_MANUAL, '10', '1');
        $recorder = app(RecordGrade::class);
        $grade = $recorder->handle($item, $this->student, Grade::STATUS_GRADED, '7.55', $this->teacher);
        $service = app(FinalizeGrades::class);
        $finalization = $service->finalize($period, $this->student, $this->teacher, 'finalize-1');
        $retry = $service->finalize($period, $this->student, $this->teacher, 'finalize-1');

        $this->assertSame(GradeFinalization::STATE_FINALIZED, $finalization->state);
        $this->assertSame($finalization->id, $retry->id);
        $this->assertSame('7.6000', $finalization->final_score);
        $originalHash = $finalization->calculation_hash;

        try {
            $recorder->handle($item, $this->student, Grade::STATUS_GRADED, '8', $this->teacher, expectedVersion: $grade->version);
            $this->fail('Grade finalized không được phép sửa.');
        } catch (GradebookException $exception) {
            $this->assertStringContainsString('reopen', $exception->getMessage());
        }

        $service->reopen($period, $this->student, $this->teacher, 'Phúc khảo');
        $recorder->handle($item, $this->student, Grade::STATUS_GRADED, '8', $this->teacher, expectedVersion: $grade->fresh()->version);
        $refinalized = $service->finalize($period, $this->student, $this->teacher, 'finalize-2');

        $this->assertSame(3, $refinalized->version);
        $this->assertSame('8.0000', $refinalized->final_score);
        $this->assertNotSame($originalHash, $refinalized->calculation_hash);
        $this->assertSame(['finalize', 'reopen', 'finalize'], GradeChangeLog::whereIn('action', ['finalize', 'reopen'])->orderBy('id')->pluck('action')->all());
    }

    public function test_unique_constraints_and_policies_protect_grade_scope(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'course', 'Tổng kết', '100');
        $item = $this->item($period, $category, 'manual', GradeItem::TYPE_MANUAL, '10', '1');
        $grade = app(RecordGrade::class)->handle($item, $this->student, Grade::STATUS_GRADED, '8', $this->teacher);
        $otherTeacher = $this->user('other-gradebook-teacher@example.test', User::ROLE_TEACHER);
        $otherStudent = $this->user('other-gradebook-student@example.test', User::ROLE_STUDENT);
        $admin = $this->user('gradebook-admin@example.test', User::ROLE_ADMIN);

        $this->assertTrue(Gate::forUser($this->student)->allows('view', $grade));
        $this->assertFalse(Gate::forUser($otherStudent)->allows('view', $grade));
        $this->assertTrue(Gate::forUser($this->teacher)->allows('update', $grade));
        $this->assertFalse(Gate::forUser($otherTeacher)->allows('update', $grade));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $grade));

        $this->expectException(QueryException::class);
        Grade::create([
            'grade_item_id' => $item->id,
            'user_id' => $this->student->id,
            'status' => Grade::STATUS_GRADED,
            'raw_points' => '9',
            'effective_points' => '9',
        ]);
    }

    public function test_teacher_gradebook_ui_records_audits_finalizes_and_reopens_grade(): void
    {
        $classId = DB::table('classes')->insertGetId([
            'name' => 'Lớp Gradebook', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('class_course')->insert(['class_id' => $classId, 'course_id' => $this->course->id]);
        DB::table('class_user')->insert(['class_id' => $classId, 'user_id' => $this->student->id]);
        $period = $this->period();
        $category = $this->category($period, 'course', 'Tổng kết', '100');
        $item = $this->item($period, $category, 'manual', GradeItem::TYPE_MANUAL, '10', '1');

        $this->actingAs($this->teacher)->get(route('gradebook.index', [$this->course, 'period_id' => $period->id]))
            ->assertOk()->assertSee('Sổ điểm chính quy')->assertSee($this->student->name);

        $this->actingAs($this->teacher)->put(route('gradebook.grades.record', [$period, $item, $this->student]), [
            'status' => Grade::STATUS_GRADED, 'raw_points' => '8.25',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $grade = Grade::where('grade_item_id', $item->id)->where('user_id', $this->student->id)->firstOrFail();
        $this->assertSame('8.2500', $grade->raw_points);
        $this->assertDatabaseHas('grade_change_logs', ['grade_id' => $grade->id, 'source' => 'teacher_ui']);

        $this->actingAs($this->teacher)->post(route('gradebook.finalize', [$period, $this->student]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('grade_finalizations', ['grading_period_id' => $period->id, 'user_id' => $this->student->id, 'state' => 'finalized']);

        $this->actingAs($this->teacher)->put(route('gradebook.grades.record', [$period, $item, $this->student]), [
            'status' => Grade::STATUS_GRADED, 'raw_points' => '9', 'expected_version' => $grade->version,
        ])->assertRedirect()->assertSessionHasErrors('gradebook');
        $this->assertSame('8.2500', $grade->fresh()->raw_points);

        $this->actingAs($this->teacher)->post(route('gradebook.reopen', [$period, $this->student]), ['reason' => 'Phúc khảo'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('grade_finalizations', ['grading_period_id' => $period->id, 'user_id' => $this->student->id, 'state' => 'reopened']);
    }

    public function test_gradebook_without_period_keeps_legacy_scores_accessible_and_hides_rollout_jargon(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('gradebook.index', $this->course))
            ->assertOk()
            ->assertSee('Chưa thiết lập kỳ điểm cho khóa học này.')
            ->assertSee('Dữ liệu điểm hiện tại vẫn được giữ nguyên trong bảng Điểm danh.')
            ->assertSee(route('attendance.show', $this->course), false)
            ->assertDontSee('shadow backfill');
    }

    /** @return array{GradingPeriod,array{hs1:GradeItem,hs2:GradeItem,exam:GradeItem}} */
    private function vocationalPeriod(): array
    {
        $period = $this->period();
        $process = $this->category($period, 'process', 'Quá trình', '40', 1);
        $exam = $this->category($period, 'exam', 'Thi', '60', 2);

        return [$period, [
            'hs1' => $this->item($period, $process, 'hs1', GradeItem::TYPE_HS1, '10', '1', 1),
            'hs2' => $this->item($period, $process, 'hs2', GradeItem::TYPE_HS2, '10', '2', 2),
            'exam' => $this->item($period, $exam, 'exam', GradeItem::TYPE_EXAM, '10', '1', 1),
        ]];
    }

    /** @param array<string,mixed> $overrides */
    private function period(array $overrides = []): GradingPeriod
    {
        return GradingPeriod::create([
            'course_id' => $this->course->id,
            'code' => 'period-'.uniqid(),
            'name' => 'Học kỳ kiểm thử',
            'status' => GradingPeriod::STATUS_OPEN,
            'missing_policy' => GradingPeriod::MISSING_BLOCK,
            'rounding_precision' => 1,
            'rounding_mode' => 'half_up',
            ...$overrides,
        ]);
    }

    private function category(GradingPeriod $period, string $code, string $name, string $weight, int $position = 1): GradeCategory
    {
        return GradeCategory::create([
            'course_id' => $this->course->id,
            'grading_period_id' => $period->id,
            'code' => $code,
            'name' => $name,
            'weight_percent' => $weight,
            'position' => $position,
            'is_active' => true,
        ]);
    }

    private function item(
        GradingPeriod $period,
        GradeCategory $category,
        string $code,
        string $type,
        string $maxPoints,
        string $weight,
        int $position = 1,
    ): GradeItem {
        return GradeItem::create([
            'course_id' => $this->course->id,
            'grading_period_id' => $period->id,
            'grade_category_id' => $category->id,
            'code' => $code,
            'name' => strtoupper($code),
            'item_type' => $type,
            'source_type' => GradeItem::SOURCE_MANUAL,
            'max_points' => $maxPoints,
            'item_weight' => $weight,
            'position' => $position,
            'is_published' => true,
        ]);
    }

    private function user(string $email, string $role): User
    {
        return User::create([
            'name' => $email,
            'email' => $email,
            'password' => 'unused',
            'role' => $role,
        ]);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_08_09_140000_create_gradebook_foundation.php');
    }
}
