<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('DashboardMetricsTest chỉ được phép chạy trên SQLite cô lập.');
        }

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->tables()) as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_student_dashboard_prioritizes_overdue_work_and_uses_best_quiz_score(): void
    {
        $teacher = $this->user('teacher@example.com', User::ROLE_TEACHER);
        $student = $this->user('student@example.com', User::ROLE_STUDENT);
        [$courseId, $classId] = $this->courseAndClass($teacher, $student);

        DB::table('assignments')->insert([
            'course_id' => $courseId,
            'title' => 'Bài đã quá hạn',
            'grading_scale' => 10,
            'status' => 'published',
            'published_at' => now()->subDays(3),
            'due_date' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quizId = DB::table('quizzes')->insertGetId([
            'course_id' => $courseId,
            'title' => 'Quiz được làm lại',
            'time_limit' => 30,
            'max_attempts' => 3,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ([6, 8] as $index => $score) {
            DB::table('quiz_attempts')->insert([
                'quiz_id' => $quizId,
                'user_id' => $student->id,
                'attempt_number' => $index + 1,
                'status' => 'released',
                'score' => $score,
                'completed_at' => now()->subHours(2 - $index),
                'result_released_at' => now()->subHour(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $closedQuizId = DB::table('quizzes')->insertGetId([
            'course_id' => $courseId,
            'title' => 'Quiz chưa tới ca',
            'time_limit' => 30,
            'max_attempts' => 1,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('quiz_sessions')->insert([
            'quiz_id' => $closedQuizId,
            'name' => 'Ca ngày mai',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
            'result_release_policy' => 'after_session',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = $this->dashboardData($student);

        $this->assertSame(1, $data['missing_assignments_count']);
        $this->assertSame(1, $data['overdue_assignments_count']);
        $this->assertSame('Bài đã quá hạn', $data['pending_assignments']->first()->title);
        $this->assertSame(1, $data['pending_quizzes_count']);
        $this->assertSame('Làm lại', $data['pending_quizzes']->first()->dashboard_action_label);
        $this->assertSame(8.0, (float) $data['average_score']);
        $this->assertSame([8.0], $data['chart_quiz_data']);
        $this->assertSame($classId, DB::table('class_user')->where('user_id', $student->id)->value('class_id'));
    }

    public function test_teacher_dashboard_combines_grading_queue_and_normalizes_attention_scores(): void
    {
        $teacher = $this->user('teacher-dashboard@example.com', User::ROLE_TEACHER);
        $student = $this->user('attention-student@example.com', User::ROLE_STUDENT);
        [$courseId] = $this->courseAndClass($teacher, $student);

        $gradedAssignment = DB::table('assignments')->insertGetId([
            'course_id' => $courseId,
            'title' => 'Bài thang 100',
            'grading_scale' => 100,
            'status' => 'published',
            'published_at' => now()->subDays(3),
            'due_date' => now()->subDays(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('assignment_submissions')->insert([
            'assignment_id' => $gradedAssignment,
            'user_id' => $student->id,
            'grade' => 40,
            'submitted_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
            'updated_at' => now(),
        ]);

        $pendingAssignment = DB::table('assignments')->insertGetId([
            'course_id' => $courseId,
            'title' => 'Bài chờ chấm',
            'grading_scale' => 10,
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'due_date' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('assignment_submissions')->insert([
            'assignment_id' => $pendingAssignment,
            'user_id' => $student->id,
            'submitted_at' => now()->subHours(3),
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        $quizId = DB::table('quizzes')->insertGetId([
            'course_id' => $courseId,
            'title' => 'Quiz tự luận',
            'time_limit' => 30,
            'max_attempts' => 1,
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('quiz_attempts')->insert([
            'quiz_id' => $quizId,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'pending_grading',
            'completed_at' => now()->subHours(2),
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $data = $this->dashboardData($teacher);

        $this->assertSame(1, $data['pending_assignment_grades']);
        $this->assertSame(1, $data['pending_quiz_grades']);
        $this->assertSame(2, $data['pending_grades']);
        $this->assertSame(['assignment', 'quiz'], $data['grading_queue']->pluck('type')->all());
        $this->assertSame(1, $data['attention_students_count']);
        $this->assertSame(4.0, round((float) $data['attention_students']->first()->avg_grade, 1));
    }

    public function test_admin_dashboard_separates_active_accounts_and_operational_backlog(): void
    {
        $admin = $this->user('admin-dashboard@example.com', User::ROLE_ADMIN);
        $teacher = $this->user('active-teacher@example.com', User::ROLE_TEACHER);
        $student = $this->user('active-student@example.com', User::ROLE_STUDENT);
        $this->user('inactive-student@example.com', User::ROLE_STUDENT, false);
        $expired = $this->user('expired-teacher@example.com', User::ROLE_TEACHER);
        $expired->forceFill(['expires_at' => now()->subDay()])->save();
        [$courseId] = $this->courseAndClass($teacher, $student);

        $templateId = DB::table('courses')->insertGetId([
            'title' => 'Mẫu v2',
            'teacher_id' => $teacher->id,
            'course_type' => 'template',
            'template_version' => 2,
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('courses')->insert([
            'title' => 'Khóa chờ đồng bộ',
            'teacher_id' => $teacher->id,
            'course_type' => 'delivery',
            'template_version' => 1,
            'source_template_id' => $templateId,
            'synced_template_version' => 1,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignmentId = DB::table('assignments')->insertGetId([
            'course_id' => $courseId,
            'title' => 'Bài chờ admin theo dõi',
            'grading_scale' => 10,
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('assignment_submissions')->insert([
            'assignment_id' => $assignmentId,
            'user_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $quizId = DB::table('quizzes')->insertGetId([
            'course_id' => $courseId,
            'title' => 'Quiz chờ admin theo dõi',
            'time_limit' => 30,
            'max_attempts' => 1,
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('quiz_attempts')->insert([
            'quiz_id' => $quizId,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'pending_grading',
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('backup_runs')->insert([
            'status' => 'success',
            'started_at' => now()->subHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = $this->dashboardData($admin);

        $this->assertSame(1, $data['total_students']);
        $this->assertSame(1, $data['total_teachers']);
        $this->assertSame(2, $data['account_attention_count']);
        $this->assertSame(2, $data['pending_grades']);
        $this->assertSame(1, $data['template_sync_pending_count']);
        $this->assertSame('success', $data['latest_backup']->status);
    }

    private function dashboardData(User $user): array
    {
        $this->actingAs($user);

        return app(DashboardController::class)->index()->getData()['data'];
    }

    private function user(string $email, string $role, bool $active = true): User
    {
        return User::create([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    private function courseAndClass(User $teacher, User $student): array
    {
        $courseId = DB::table('courses')->insertGetId([
            'title' => 'Khóa dashboard',
            'teacher_id' => $teacher->id,
            'course_type' => 'delivery',
            'template_version' => 1,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $classId = DB::table('classes')->insertGetId([
            'name' => 'Lớp dashboard',
            'code' => 'DASH-'.$courseId,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('class_user')->insert(['class_id' => $classId, 'user_id' => $student->id]);
        DB::table('class_course')->insert(['class_id' => $classId, 'course_id' => $courseId]);

        return [$courseId, $classId];
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
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('course_type')->default('delivery');
            $table->unsignedInteger('template_version')->default(1);
            $table->unsignedBigInteger('source_template_id')->nullable();
            $table->unsignedInteger('synced_template_version')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('status')->nullable();
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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('status')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('status')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('lesson_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->unsignedInteger('grading_scale')->default(10);
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('grade', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->unsignedInteger('time_limit')->default(30);
            $table->unsignedInteger('max_attempts')->default(1);
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
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
            $table->unsignedInteger('extra_time_minutes')->default(0);
            $table->timestamps();
        });
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('quiz_session_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('status');
            $table->decimal('score', 8, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('result_released_at')->nullable();
            $table->timestamps();
        });
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('class_id');
            $table->date('schedule_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->string('note')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('attendance_columns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('type');
        });
        Schema::create('attendance_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_column_id');
            $table->unsignedBigInteger('user_id');
            $table->string('value')->nullable();
        });
        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamps();
        });
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->text('exception')->nullable();
        });
    }

    private function tables(): array
    {
        return [
            'users', 'courses', 'classes', 'class_user', 'class_course', 'modules', 'lessons', 'lesson_user',
            'assignments', 'assignment_submissions', 'quizzes', 'quiz_sessions', 'quiz_session_user',
            'quiz_attempts', 'schedules', 'attendance_columns', 'attendance_data', 'backup_runs', 'failed_jobs',
        ];
    }
}
