<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\QuestionAiQualityService;
use App\Services\QuestionDifficultyAnalyticsService;
use App\Services\QuizExamService;
use App\Services\QuizQuestionSelectionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
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
        $this->requireIsolatedSqliteDatabase();
        $this->createSchema();
        $this->seedExam();
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            foreach ([
                'quiz_attempt_attachments', 'quiz_attempt_answers', 'quiz_attempt_questions', 'quiz_session_user', 'quiz_sessions',
                'quiz_attempts', 'options', 'questions', 'quiz_passages', 'course_question_bank', 'question_banks',
                'quizzes', 'courses', 'users',
            ] as $table) {
                Schema::dropIfExists($table);
            }
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

        $this->assertSame(QuizAttempt::STATUS_GRADED, $submitted->status);
        $this->assertSame(10.0, (float) $submitted->score);
        $this->assertFalse($submitted->resultIsReleased());
    }

    public function test_mixed_question_types_are_snapshotted_autosaved_and_graded(): void
    {
        $this->quiz->update(['easy_count' => 5]);
        $this->seedMixedQuestions();

        $service = app(QuizExamService::class);
        $attempt = $service->startOrResume($this->quiz->fresh(), $this->student, $this->session);
        $questions = $attempt->attemptQuestions->keyBy('question_type');

        $this->assertSame([
            Question::TYPE_FILL_BLANK,
            Question::TYPE_MULTIPLE_CHOICE,
            Question::TYPE_NUMERIC,
            Question::TYPE_SINGLE_CHOICE,
            Question::TYPE_TRUE_FALSE_GROUP,
        ], $questions->keys()->sort()->values()->all());

        $payloads = [
            Question::TYPE_SINGLE_CHOICE => data_get($questions[Question::TYPE_SINGLE_CHOICE]->answer_key_snapshot, 'option_ids.0'),
            Question::TYPE_MULTIPLE_CHOICE => data_get($questions[Question::TYPE_MULTIPLE_CHOICE]->answer_key_snapshot, 'option_ids'),
            Question::TYPE_TRUE_FALSE_GROUP => data_get($questions[Question::TYPE_TRUE_FALSE_GROUP]->answer_key_snapshot, 'statements'),
            Question::TYPE_FILL_BLANK => ['  Hà Nội ', 'VIỆT NAM'],
            Question::TYPE_NUMERIC => '9,8',
        ];

        foreach ($questions as $question) {
            $service->saveAnswer($attempt, $question->id, $payloads[$question->question_type], false, $question->position);
        }

        $submitted = $service->submit($attempt->fresh());

        $this->assertSame(10.0, (float) $submitted->score);
        $this->assertTrue($submitted->answers()->where('is_correct', false)->doesntExist());
        $this->assertSame('cm', data_get($questions[Question::TYPE_NUMERIC]->response_schema_snapshot, 'unit'));
        $this->assertCount(2, data_get($questions[Question::TYPE_FILL_BLANK]->answer_key_snapshot, 'blanks'));
    }

    public function test_multiple_choice_requires_exact_set_and_numeric_respects_tolerance(): void
    {
        $this->quiz->update(['easy_count' => 5]);
        $this->seedMixedQuestions();

        $service = app(QuizExamService::class);
        $attempt = $service->startOrResume($this->quiz->fresh(), $this->student, $this->session);
        $questions = $attempt->attemptQuestions->keyBy('question_type');

        $single = $questions[Question::TYPE_SINGLE_CHOICE];
        $multiple = $questions[Question::TYPE_MULTIPLE_CHOICE];
        $truthGroup = $questions[Question::TYPE_TRUE_FALSE_GROUP];
        $fill = $questions[Question::TYPE_FILL_BLANK];
        $numeric = $questions[Question::TYPE_NUMERIC];

        $service->saveAnswer($attempt, $single->id, data_get($single->answer_key_snapshot, 'option_ids.0'), false, 1);
        $service->saveAnswer($attempt, $multiple->id, [data_get($multiple->answer_key_snapshot, 'option_ids.0')], false, 2);
        $service->saveAnswer($attempt, $truthGroup->id, data_get($truthGroup->answer_key_snapshot, 'statements'), false, 3);
        $service->saveAnswer($attempt, $fill->id, ['Hanoi', 'Việt Nam'], false, 4);
        $service->saveAnswer($attempt, $numeric->id, '10.21', false, 5);

        $submitted = $service->submit($attempt->fresh());

        $this->assertSame(6.0, (float) $submitted->score);
        $this->assertFalse((bool) $multiple->answer()->first()->is_correct);
        $this->assertFalse((bool) $numeric->answer()->first()->is_correct);
        $this->assertTrue((bool) $fill->answer()->first()->is_correct);
    }

    public function test_essay_moves_from_pending_grading_to_graded_and_released(): void
    {
        $essay = Question::create([
            'course_id' => $this->quiz->course_id,
            'question_type' => Question::TYPE_ESSAY,
            'question_text' => 'Trình bày vai trò của CSS.',
            'answer_config' => [
                'grading_mode' => 'manual',
                'max_score' => 3,
                'word_limit' => 100,
                'allow_attachments' => true,
                'rubric' => [
                    ['criterion' => 'Nội dung', 'max_score' => 2],
                    ['criterion' => 'Trình bày', 'max_score' => 1],
                ],
            ],
            'difficulty' => 'easy',
            'status' => 'published',
        ]);
        $this->quiz->update(['easy_count' => 2]);

        $service = app(QuizExamService::class);
        $attempt = $service->startOrResume($this->quiz->fresh(), $this->student, $this->session);
        $questions = $attempt->attemptQuestions->keyBy('question_type');
        $service->saveAnswer($attempt, $questions[Question::TYPE_SINGLE_CHOICE]->id, data_get($questions[Question::TYPE_SINGLE_CHOICE]->answer_key_snapshot, 'option_ids.0'), false, 1);
        $service->saveAnswer($attempt, $questions[Question::TYPE_ESSAY]->id, ['text' => 'CSS kiểm soát cách trình bày và bố cục trang web.'], false, 2);

        $submitted = $service->submit($attempt->fresh());
        $this->assertSame(QuizAttempt::STATUS_PENDING_GRADING, $submitted->status);
        $this->assertNull($submitted->score);
        $essayAnswer = $submitted->answers()->where('quiz_attempt_question_id', $questions[Question::TYPE_ESSAY]->id)->firstOrFail();
        $teacher = User::findOrFail($this->quiz->course->teacher_id);

        $graded = $service->gradeManualAnswer($submitted, $essayAnswer, [2, 1], 'Đáp ứng tốt yêu cầu.', $teacher);
        $this->assertSame(QuizAttempt::STATUS_GRADED, $graded->status);
        $this->assertSame(10.0, (float) $graded->score);
        $this->assertFalse($graded->resultIsReleased());

        $this->session->update(['results_released_at' => now()]);
        $released = $service->gradeManualAnswer($graded, $essayAnswer->fresh(), [2, 1], 'Đã duyệt.', $teacher);
        $this->assertSame(QuizAttempt::STATUS_RELEASED, $released->status);
        $this->assertTrue($released->resultIsReleased());
    }

    public function test_questions_are_selected_by_exact_type_and_difficulty_distribution(): void
    {
        $this->seedMixedQuestions();
        $hardFill = Question::create([
            'course_id' => $this->quiz->course_id,
            'question_type' => Question::TYPE_FILL_BLANK,
            'question_text' => 'Hoàn thành công thức khó: [[1]].',
            'answer_config' => [
                'blanks' => [['accepted' => ['x = 2']]],
                'case_sensitive' => false,
            ],
            'difficulty' => 'hard',
            'status' => 'published',
        ]);

        $selector = app(QuizQuestionSelectionService::class);
        $distribution = $selector->emptyDistribution();
        $distribution[Question::TYPE_SINGLE_CHOICE]['easy'] = 1;
        $distribution[Question::TYPE_FILL_BLANK]['easy'] = 1;
        $distribution[Question::TYPE_FILL_BLANK]['hard'] = 1;
        $this->quiz->update([
            'easy_count' => 2,
            'medium_count' => 0,
            'hard_count' => 1,
            'question_distribution' => $distribution,
        ]);

        $selected = $selector->selectForQuiz($this->quiz->fresh());

        $this->assertCount(3, $selected);
        $this->assertSame(1, $selected->where('question_type', Question::TYPE_SINGLE_CHOICE)->where('difficulty', 'easy')->count());
        $this->assertSame(1, $selected->where('question_type', Question::TYPE_FILL_BLANK)->where('difficulty', 'easy')->count());
        $this->assertSame([$hardFill->id], $selected->where('question_type', Question::TYPE_FILL_BLANK)->where('difficulty', 'hard')->pluck('id')->all());
    }

    public function test_distribution_cannot_request_more_questions_than_available(): void
    {
        $selector = app(QuizQuestionSelectionService::class);
        $distribution = $selector->emptyDistribution();
        $distribution[Question::TYPE_SINGLE_CHOICE]['easy'] = 2;

        try {
            $selector->assertAvailable($this->quiz->course, $distribution);
            $this->fail('Cấu hình vượt quá tồn kho phải bị từ chối.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Không đủ câu Một đáp án - Dễ: cần 2, hiện có 1.',
                $exception->errors()['question_distribution.single_choice.easy'][0]
            );
        }
    }

    public function test_ai_quality_review_detects_duplicate_question(): void
    {
        $reviewed = app(QuestionAiQualityService::class)->reviewBatch($this->quiz->course_id, [[
            'question_type' => Question::TYPE_SINGLE_CHOICE,
            'question' => 'Đáp án nào đúng?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_indexes' => [0],
            'explanation' => 'Giải thích đáp án.',
        ]]);

        $this->assertSame('needs_review', $reviewed[0]['quality']['status']);
        $this->assertSame(100, $reviewed[0]['quality']['duplicate']['similarity']);
    }

    public function test_observed_difficulty_is_calculated_after_enough_submissions(): void
    {
        $question = Question::where('question_text', 'Đáp án nào đúng?')->firstOrFail();

        foreach (range(1, 5) as $index) {
            $userId = DB::table('users')->insertGetId([
                'name' => "Học viên {$index}",
                'email' => "analytics{$index}@exam.test",
                'password' => Hash::make('x'),
                'role' => 'student',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $attemptId = DB::table('quiz_attempts')->insertGetId([
                'quiz_id' => $this->quiz->id,
                'user_id' => $userId,
                'status' => 'submitted',
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $issuedId = DB::table('quiz_attempt_questions')->insertGetId([
                'quiz_attempt_id' => $attemptId,
                'question_id' => $question->id,
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'position' => 1,
                'question_text' => $question->question_text,
                'option_snapshot' => '[]',
                'correct_option_id' => $this->correctOptionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('quiz_attempt_answers')->insert([
                'quiz_attempt_id' => $attemptId,
                'quiz_attempt_question_id' => $issuedId,
                'is_correct' => $index <= 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(QuestionDifficultyAnalyticsService::class)->refreshForQuestionIds([$question->id]);

        $question->refresh();
        $this->assertSame('easy', $question->observed_difficulty);
        $this->assertSame(5, $question->difficulty_metrics['sample_size']);
        $this->assertSame(0.8, $question->difficulty_metrics['accuracy']);
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
            $table->json('question_distribution')->nullable();
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
            $table->string('question_type')->default('single_choice');
            $table->text('question_text');
            $table->json('answer_config')->nullable();
            $table->string('difficulty')->default('medium');
            $table->string('observed_difficulty')->nullable();
            $table->json('difficulty_metrics')->nullable();
            $table->timestamp('difficulty_evaluated_at')->nullable();
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
            $table->float('auto_score')->nullable();
            $table->float('manual_score')->nullable();
            $table->json('student_answers')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('current_position')->default(1);
            $table->json('flagged_question_ids')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamp('result_released_at')->nullable();
            $table->timestamps();
            $table->unique(['quiz_id', 'user_id']);
        });
        Schema::create('quiz_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_attempt_id');
            $table->unsignedBigInteger('question_id')->nullable();
            $table->string('question_type')->default('single_choice');
            $table->string('grading_mode')->default('auto');
            $table->float('max_score')->default(1);
            $table->unsignedInteger('position');
            $table->text('question_text');
            $table->string('passage_title')->nullable();
            $table->longText('passage_content')->nullable();
            $table->string('passage_source_label')->nullable();
            $table->json('option_snapshot');
            $table->json('answer_key_snapshot')->nullable();
            $table->json('response_schema_snapshot')->nullable();
            $table->unsignedBigInteger('correct_option_id');
            $table->timestamps();
        });
        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_attempt_id');
            $table->unsignedBigInteger('quiz_attempt_question_id');
            $table->unsignedBigInteger('selected_option_id')->nullable();
            $table->json('answer_payload')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->string('grading_status')->default('ungraded');
            $table->float('score')->nullable();
            $table->json('rubric_scores')->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
        });
        Schema::create('quiz_attempt_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_attempt_id');
            $table->unsignedBigInteger('quiz_attempt_question_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
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

    private function seedMixedQuestions(): void
    {
        $courseId = $this->quiz->course_id;

        $multiple = Question::create([
            'course_id' => $courseId,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => 'Chọn hai số chẵn.',
            'answer_config' => ['grading' => 'all_or_nothing'],
            'difficulty' => 'easy',
            'status' => 'published',
        ]);
        foreach ([['2', true], ['3', false], ['4', true]] as [$text, $correct]) {
            DB::table('options')->insert(['question_id' => $multiple->id, 'option_text' => $text, 'is_correct' => $correct, 'created_at' => now(), 'updated_at' => now()]);
        }

        $truthGroup = Question::create([
            'course_id' => $courseId,
            'question_type' => Question::TYPE_TRUE_FALSE_GROUP,
            'question_text' => 'Xác định tính đúng sai.',
            'answer_config' => ['grading' => 'all_or_nothing'],
            'difficulty' => 'easy',
            'status' => 'published',
        ]);
        foreach ([['Trái Đất quay quanh Mặt Trời', true], ['Mặt Trời quay quanh Trái Đất', false]] as [$text, $correct]) {
            DB::table('options')->insert(['question_id' => $truthGroup->id, 'option_text' => $text, 'is_correct' => $correct, 'created_at' => now(), 'updated_at' => now()]);
        }

        Question::create([
            'course_id' => $courseId,
            'question_type' => Question::TYPE_FILL_BLANK,
            'question_text' => 'Thủ đô là [[1]], thuộc [[2]].',
            'answer_config' => [
                'blanks' => [
                    ['accepted' => ['Hà Nội', 'Hanoi']],
                    ['accepted' => ['Việt Nam']],
                ],
                'case_sensitive' => false,
            ],
            'difficulty' => 'easy',
            'status' => 'published',
        ]);

        Question::create([
            'course_id' => $courseId,
            'question_type' => Question::TYPE_NUMERIC,
            'question_text' => 'Chiều dài đo được là bao nhiêu?',
            'answer_config' => ['target' => 10, 'tolerance' => 0.2, 'unit' => 'cm'],
            'difficulty' => 'easy',
            'status' => 'published',
        ]);
    }
}
