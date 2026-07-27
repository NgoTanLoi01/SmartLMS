<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuizPermanentDeletionTest extends TestCase
{
    private User $teacher;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireIsolatedSqliteDatabase();
        $this->createSchema();
        $this->teacher = User::create([
            'name' => 'Giáo viên',
            'email' => 'archive-teacher@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_TEACHER,
            'is_active' => true,
        ]);
        $this->course = Course::create([
            'title' => 'Khóa học lưu trữ',
            'teacher_id' => $this->teacher->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_PUBLISHED,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            foreach (['quiz_session_user', 'quiz_sessions', 'quiz_attempts', 'quizzes', 'courses', 'users'] as $table) {
                Schema::dropIfExists($table);
            }
        }
        parent::tearDown();
    }

    public function test_teacher_can_permanently_delete_archived_quiz_without_attempts(): void
    {
        $quiz = $this->archivedQuiz('Đề tạo nhầm');
        $sessionId = DB::table('quiz_sessions')->insertGetId([
            'quiz_id' => $quiz->id,
            'name' => 'Ca tạo nhầm',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('quiz_session_user')->insert([
            'quiz_session_id' => $sessionId,
            'user_id' => $this->teacher->id,
        ]);

        $this->actingAs($this->teacher)
            ->delete(route('quizzes.force-destroy', $quiz), ['confirmation' => $quiz->title])
            ->assertRedirect(route('quizzes.archived', $this->course));

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseMissing('quiz_sessions', ['id' => $sessionId]);
        $this->assertDatabaseCount('quiz_session_user', 0);
    }

    public function test_archived_quiz_with_attempt_is_protected_from_permanent_deletion(): void
    {
        $quiz = $this->archivedQuiz('Đề đã có kết quả');
        DB::table('quiz_attempts')->insert([
            'quiz_id' => $quiz->id,
            'user_id' => $this->teacher->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->teacher)
            ->from(route('quizzes.archived', $this->course))
            ->delete(route('quizzes.force-destroy', $quiz), ['confirmation' => $quiz->title])
            ->assertRedirect(route('quizzes.archived', $this->course))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('quizzes', ['id' => $quiz->id, 'status' => Quiz::STATUS_ARCHIVED]);
    }

    private function archivedQuiz(string $title): Quiz
    {
        return Quiz::create([
            'course_id' => $this->course->id,
            'title' => $title,
            'time_limit' => 30,
            'status' => Quiz::STATUS_ARCHIVED,
        ]);
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
            $table->string('status')->default('published');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->integer('time_limit')->default(30);
            $table->string('status')->default('published');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('quiz_session_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
        });
    }
}
