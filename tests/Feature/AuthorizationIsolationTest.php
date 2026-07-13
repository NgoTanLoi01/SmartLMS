<?php

namespace Tests\Feature;

use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceColumn;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthorizationIsolationTest extends TestCase
{
    private User $owner;

    private User $otherTeacher;

    private User $admin;

    private Course $course;

    private Classroom $classroom;

    private Module $module;

    private Lesson $lesson;

    private Assignments $assignment;

    private Quiz $quiz;

    private AttendanceColumn $attendanceColumn;

    private Schedule $schedule;

    private QuestionBank $questionBank;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('AuthorizationIsolationTest chỉ được phép chạy trên SQLite cô lập.');
        }

        $this->createSchema();
        $this->seedAuthorizationGraph();
    }

    protected function tearDown(): void
    {
        foreach ([
            'assignment_submissions', 'questions', 'course_question_bank', 'question_banks', 'schedules', 'attendance_columns',
            'quizzes', 'assignments', 'lessons', 'modules', 'class_course', 'class_user', 'classes', 'courses', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_teacher_cannot_mutate_another_teachers_resources(): void
    {
        $this->actingAs($this->otherTeacher)
            ->post(route('assignments.store'), [
                'course_id' => $this->course->id,
                'lesson_id' => $this->lesson->id,
                'type' => 'essay',
                'title' => 'Bài tập chiếm quyền',
                'instructions' => 'Không được tạo',
                'due_date' => now()->addDay()->toDateTimeString(),
                'status' => Assignments::STATUS_PUBLISHED,
            ])
            ->assertForbidden();

        $this->actingAs($this->otherTeacher)
            ->post(route('attendance.addColumn', $this->course), [
                'type' => 'attendance',
                'name' => 'Cột chiếm quyền',
            ])
            ->assertForbidden();

        $this->actingAs($this->otherTeacher)
            ->post(route('schedules.store'), [
                'class_id' => $this->classroom->id,
                'course_id' => $this->course->id,
                'schedule_date' => now()->addDay()->toDateString(),
                'start_time' => '08:00',
                'end_time' => '10:00',
            ])
            ->assertForbidden();

        $this->actingAs($this->otherTeacher)
            ->put(route('assignments.update', $this->assignment), [])
            ->assertForbidden();

        $this->actingAs($this->otherTeacher)
            ->delete(route('quizzes.destroy', $this->quiz))
            ->assertForbidden();

        $this->actingAs($this->otherTeacher)
            ->delete(route('attendance.deleteColumn', $this->attendanceColumn))
            ->assertForbidden();

        $this->actingAs($this->otherTeacher)
            ->delete(route('schedules.destroy', $this->schedule))
            ->assertForbidden();

        $this->actingAs($this->otherTeacher)
            ->put(route('modules.update', $this->module), ['title' => 'Chiếm quyền'])
            ->assertForbidden();

        $this->actingAs($this->otherTeacher)
            ->put(route('lessons.update', $this->lesson), [])
            ->assertForbidden();

        $this->assertDatabaseHas('assignments', ['id' => $this->assignment->id, 'title' => 'Bài tập của A']);
        $this->assertDatabaseHas('quizzes', ['id' => $this->quiz->id, 'status' => Quiz::STATUS_PUBLISHED]);
        $this->assertDatabaseHas('attendance_columns', ['id' => $this->attendanceColumn->id]);
        $this->assertDatabaseHas('schedules', ['id' => $this->schedule->id, 'status' => Schedule::STATUS_ACTIVE]);
        $this->assertDatabaseHas('modules', ['id' => $this->module->id, 'title' => 'Chương của A']);
        $this->assertDatabaseMissing('assignments', ['title' => 'Bài tập chiếm quyền']);
        $this->assertDatabaseMissing('attendance_columns', ['name' => 'Cột chiếm quyền']);
        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_owner_and_admin_have_expected_resource_permissions(): void
    {
        $ownedResources = [
            $this->course,
            $this->classroom,
            $this->module,
            $this->lesson,
            $this->assignment,
            $this->quiz,
            $this->attendanceColumn,
            $this->schedule,
            $this->questionBank,
            $this->question,
        ];

        foreach ($ownedResources as $resource) {
            $this->assertTrue(Gate::forUser($this->owner)->allows('update', $resource), $resource::class);
            $this->assertFalse(Gate::forUser($this->otherTeacher)->allows('update', $resource), $resource::class);
            $this->assertTrue(Gate::forUser($this->admin)->allows('update', $resource), $resource::class);
        }
    }

    public function test_submission_is_visible_only_to_owner_course_teacher_and_admin(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $otherStudent = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $submission = AssignmentSubmission::create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
            'text_answer' => 'Bài làm thuộc riêng học sinh.',
            'submitted_at' => now(),
        ]);

        $this->assertTrue(Gate::forUser($student)->allows('view', $submission));
        $this->assertTrue(Gate::forUser($this->owner)->allows('view', $submission));
        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $submission));
        $this->assertFalse(Gate::forUser($otherStudent)->allows('view', $submission));
        $this->assertFalse(Gate::forUser($this->otherTeacher)->allows('view', $submission));
    }

    private function createSchema(): void
    {
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
            $table->string('course_type')->default('delivery');
            $table->string('status')->default(Course::STATUS_PUBLISHED);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedBigInteger('teacher_id');
            $table->string('status')->default(Classroom::STATUS_ACTIVE);
            $table->timestamps();
        });
        Schema::create('class_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('class_course', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('course_id');
            $table->timestamps();
        });
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->integer('order')->default(0);
            $table->string('status')->default(Module::STATUS_PUBLISHED);
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('title');
            $table->text('content')->nullable();
            $table->integer('order')->default(0);
            $table->string('status')->default(Lesson::STATUS_PUBLISHED);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->string('type')->default('essay');
            $table->string('title');
            $table->text('instructions');
            $table->dateTime('due_date');
            $table->string('status')->default(Assignments::STATUS_PUBLISHED);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('user_id');
            $table->string('file_path')->nullable();
            $table->text('text_answer')->nullable();
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->integer('time_limit')->default(30);
            $table->string('status')->default(Quiz::STATUS_PUBLISHED);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('attendance_columns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('name');
            $table->string('type');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('course_id');
            $table->date('schedule_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default(Schedule::STATUS_ACTIVE);
            $table->timestamps();
        });
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->timestamps();
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('question_bank_id')->nullable();
            $table->text('question_text');
            $table->string('difficulty')->default('easy');
            $table->string('status')->default(Question::STATUS_PUBLISHED);
            $table->timestamps();
        });
        Schema::create('course_question_bank', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('question_bank_id');
            $table->timestamps();
        });
    }

    private function seedAuthorizationGraph(): void
    {
        $this->owner = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->course = Course::create([
            'title' => 'Khóa của A',
            'description' => 'Test',
            'teacher_id' => $this->owner->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $this->classroom = Classroom::create([
            'name' => 'Lớp của A',
            'code' => 'A-01',
            'teacher_id' => $this->owner->id,
            'status' => Classroom::STATUS_ACTIVE,
        ]);
        $this->classroom->courses()->attach($this->course);
        $this->module = Module::create(['course_id' => $this->course->id, 'title' => 'Chương của A']);
        $this->lesson = Lesson::create([
            'module_id' => $this->module->id,
            'title' => 'Bài của A',
            'status' => Lesson::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $this->assignment = Assignments::create([
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'type' => 'essay',
            'title' => 'Bài tập của A',
            'instructions' => 'Nội dung',
            'due_date' => now()->addDay(),
            'status' => Assignments::STATUS_PUBLISHED,
        ]);
        $this->quiz = Quiz::create([
            'course_id' => $this->course->id,
            'title' => 'Quiz của A',
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
        $this->attendanceColumn = AttendanceColumn::create([
            'course_id' => $this->course->id,
            'name' => 'Buổi 1',
            'type' => 'attendance',
        ]);
        $this->schedule = Schedule::create([
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);
        $this->questionBank = QuestionBank::create(['name' => 'Ngân hàng của A', 'teacher_id' => $this->owner->id]);
        $this->question = Question::create([
            'course_id' => $this->course->id,
            'question_bank_id' => $this->questionBank->id,
            'question_text' => 'Câu hỏi của A',
            'difficulty' => 'easy',
        ]);
    }
}
