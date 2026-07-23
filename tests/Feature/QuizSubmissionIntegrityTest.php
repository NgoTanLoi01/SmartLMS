<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuizSubmissionIntegrityTest extends TestCase
{
    private User $student;

    private Course $course;

    private Quiz $quiz;

    private int $questionId;

    private int $correctOptionId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('QuizSubmissionIntegrityTest chỉ được phép chạy trên SQLite cô lập.');
        }

        $this->createSchema();
        $this->seedQuiz();
    }

    protected function tearDown(): void
    {
        foreach (['quiz_attempts', 'options', 'questions', 'quizzes', 'course_question_bank', 'class_course', 'class_user', 'classes', 'courses', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_submission_after_time_limit_and_grace_is_rejected(): void
    {
        $this->cacheQuizSession(now()->timestamp - 100, 60);

        $this->actingAs($this->student)
            ->post(route('quizzes.submit', $this->quiz), [
                'answers' => [$this->questionId => $this->correctOptionId],
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('quiz_attempts', 0);
        $this->assertFalse(Cache::has($this->sessionKey()));
    }

    public function test_duplicate_submission_creates_only_one_attempt(): void
    {
        $this->cacheQuizSession(now()->timestamp - 10, 60);

        $this->actingAs($this->student)
            ->post(route('quizzes.submit', $this->quiz), [
                'answers' => [$this->questionId => $this->correctOptionId],
            ])
            ->assertSessionHas('success');

        $this->cacheQuizSession(now()->timestamp - 5, 60);

        $this->actingAs($this->student)
            ->post(route('quizzes.submit', $this->quiz), [
                'answers' => [$this->questionId => $this->correctOptionId],
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->student->id,
            'score' => 10,
        ]);
    }

    private function cacheQuizSession(int $startedAt, int $timeLimit): void
    {
        Cache::put($this->sessionKey(), [
            'question_ids' => [$this->questionId],
            'option_ids' => [$this->questionId => [$this->correctOptionId]],
            'started_at' => $startedAt,
            'time_limit' => $timeLimit,
        ], now()->addMinutes(5));
    }

    private function sessionKey(): string
    {
        return "quiz_session_{$this->quiz->id}_{$this->student->id}";
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
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
        });
        Schema::create('class_course', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('course_id');
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
        Schema::create('course_question_bank', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('question_bank_id');
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->text('question_text');
            $table->timestamps();
        });
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('user_id');
            $table->float('score')->nullable();
            $table->json('student_answers')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['quiz_id', 'user_id']);
        });
    }

    private function seedQuiz(): void
    {
        $teacher = User::create([
            'name' => 'Giáo viên',
            'email' => 'quiz-teacher@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_TEACHER,
            'is_active' => true,
        ]);
        $this->student = User::create([
            'name' => 'Học viên',
            'email' => 'quiz-student@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STUDENT,
            'is_active' => true,
        ]);
        $this->course = Course::create([
            'title' => 'Khóa học quiz',
            'teacher_id' => $teacher->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $classroomId = DB::table('classes')->insertGetId([
            'name' => 'Lớp quiz',
            'code' => 'QUIZ-01',
            'teacher_id' => $teacher->id,
            'status' => Classroom::STATUS_ACTIVE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('class_user')->insert(['class_id' => $classroomId, 'user_id' => $this->student->id]);
        DB::table('class_course')->insert(['class_id' => $classroomId, 'course_id' => $this->course->id]);

        $this->quiz = Quiz::create([
            'course_id' => $this->course->id,
            'title' => 'Quiz giới hạn lượt',
            'time_limit' => 1,
            'status' => Quiz::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $this->questionId = DB::table('questions')->insertGetId([
            'question_text' => 'Đáp án đúng?',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->correctOptionId = DB::table('options')->insertGetId([
            'question_id' => $this->questionId,
            'option_text' => 'Đúng',
            'is_correct' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
