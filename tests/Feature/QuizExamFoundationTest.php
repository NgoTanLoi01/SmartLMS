<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\QuizExamService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuizExamFoundationTest extends TestCase
{
    private User $student;

    private Quiz $quiz;

    private QuizSession $session;

    private int $correctOptionId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        $this->seedExam();
    }

    protected function tearDown(): void
    {
        foreach ([
            'quiz_attempt_answers', 'quiz_attempt_questions', 'quiz_session_user', 'quiz_sessions',
            'quiz_attempts', 'options', 'questions', 'quiz_passages', 'course_question_bank', 'question_banks',
            'quizzes', 'courses', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_attempt_is_snapshotted_and_resumed_from_database(): void
    {
        $service = app(QuizExamService::class);
        $attempt = $service->startOrResume($this->quiz, $this->student, $this->session);

        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $attempt->id,
            'status' => 'in_progress',
            'quiz_session_id' => $this->session->id,
        ]);
        $this->assertDatabaseCount('quiz_attempt_questions', 1);

        $snapshot = $attempt->attemptQuestions->first();
        Question::whereKey($snapshot->question_id)->update(['question_text' => 'Nội dung đã sửa sau khi phát đề']);

        $resumed = $service->startOrResume($this->quiz, $this->student, $this->session);

        $this->assertSame($attempt->id, $resumed->id);
        $this->assertSame('Đáp án nào đúng?', $resumed->attemptQuestions->first()->question_text);
        $this->assertSame('Đọc đoạn văn mẫu.', $resumed->attemptQuestions->first()->passage_content);
    }

    public function test_autosave_flag_and_submit_are_persisted_with_delayed_result(): void
    {
        $service = app(QuizExamService::class);
        $attempt = $service->startOrResume($this->quiz, $this->student, $this->session);
        $attemptQuestion = $attempt->attemptQuestions->first();

        $service->saveAnswer($attempt, $attemptQuestion->id, $this->correctOptionId, true, 1);

        $this->assertDatabaseHas('quiz_attempt_answers', [
            'quiz_attempt_id' => $attempt->id,
            'quiz_attempt_question_id' => $attemptQuestion->id,
            'selected_option_id' => $this->correctOptionId,
        ]);
        $this->assertContains($attemptQuestion->id, $attempt->fresh()->flagged_question_ids);

        $submitted = $service->submit($attempt);

        $this->assertSame('submitted', $submitted->status);
        $this->assertSame(10.0, (float) $submitted->score);
        $this->assertFalse($submitted->resultIsReleased());
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
            $table->integer('time_limit');
            $table->boolean('is_random')->default(true);
            $table->integer('easy_count')->default(0);
            $table->integer('medium_count')->default(0);
            $table->integer('hard_count')->default(0);
            $table->string('status')->default('published');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->timestamps();
        });
        Schema::create('course_question_bank', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('question_bank_id');
            $table->timestamps();
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('question_bank_id')->nullable();
            $table->unsignedBigInteger('quiz_passage_id')->nullable();
            $table->text('question_text');
            $table->string('difficulty')->default('medium');
            $table->string('status')->default('published');
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
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->string('name');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status');
            $table->string('result_release_policy');
            $table->timestamp('results_released_at')->nullable();
            $table->timestamps();
        });
        Schema::create('quiz_session_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_session_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedSmallInteger('extra_time_minutes')->default(0);
            $table->timestamps();
        });
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('quiz_session_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->float('score')->nullable();
            $table->json('student_answers')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('current_position')->default(1);
            $table->json('flagged_question_ids')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('result_released_at')->nullable();
            $table->timestamps();
            $table->unique(['quiz_id', 'user_id']);
        });
        Schema::create('quiz_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_attempt_id');
            $table->unsignedBigInteger('question_id')->nullable();
            $table->unsignedInteger('position');
            $table->text('question_text');
            $table->string('passage_title')->nullable();
            $table->longText('passage_content')->nullable();
            $table->string('passage_source_label')->nullable();
            $table->json('option_snapshot');
            $table->unsignedBigInteger('correct_option_id');
            $table->timestamps();
        });
        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_attempt_id');
            $table->unsignedBigInteger('quiz_attempt_question_id');
            $table->unsignedBigInteger('selected_option_id')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
        });
    }

    private function seedExam(): void
    {
        $teacher = User::create(['name' => 'Giáo viên', 'email' => 'teacher@exam.test', 'password' => Hash::make('x'), 'role' => 'teacher']);
        $this->student = User::create(['name' => 'Học viên', 'email' => 'student@exam.test', 'password' => Hash::make('x'), 'role' => 'student']);
        $course = Course::create(['title' => 'Khóa thi', 'teacher_id' => $teacher->id, 'course_type' => 'delivery', 'status' => 'published', 'published_at' => now()]);
        $this->quiz = Quiz::create(['course_id' => $course->id, 'title' => 'Thi thử', 'time_limit' => 30, 'easy_count' => 1, 'medium_count' => 0, 'hard_count' => 0, 'status' => 'published', 'published_at' => now()]);
        $passageId = DB::table('quiz_passages')->insertGetId([
            'course_id' => $course->id,
            'title' => 'Ngữ liệu mẫu',
            'content' => 'Đọc đoạn văn mẫu.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $question = Question::create(['course_id' => $course->id, 'quiz_passage_id' => $passageId, 'question_text' => 'Đáp án nào đúng?', 'difficulty' => 'easy', 'status' => 'published']);
        $this->correctOptionId = DB::table('options')->insertGetId(['question_id' => $question->id, 'option_text' => 'Đúng', 'is_correct' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('options')->insert(['question_id' => $question->id, 'option_text' => 'Sai', 'is_correct' => false, 'created_at' => now(), 'updated_at' => now()]);
        $this->session = QuizSession::create(['quiz_id' => $this->quiz->id, 'name' => 'Ca 1', 'starts_at' => now()->subMinute(), 'ends_at' => now()->addMinutes(40), 'status' => 'open', 'result_release_policy' => 'after_session']);
        DB::table('quiz_session_user')->insert(['quiz_session_id' => $this->session->id, 'user_id' => $this->student->id, 'extra_time_minutes' => 0, 'created_at' => now(), 'updated_at' => now()]);
    }
}
