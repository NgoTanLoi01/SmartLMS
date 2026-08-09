<?php

namespace Tests\Feature;

use App\Http\Controllers\CourseController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoursePaginationQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('CoursePaginationQueryTest chỉ được phép chạy trên SQLite cô lập.');
        }

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        foreach (['lessons', 'modules', 'class_user', 'class_course', 'classes', 'courses', 'learning_programs', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_course_index_paginates_and_counts_students_without_hydrating_rosters(): void
    {
        $teacher = User::create([
            'name' => 'Teacher',
            'email' => 'course-pagination@example.test',
            'password' => 'unused',
            'role' => User::ROLE_TEACHER,
        ]);
        $student = User::create([
            'name' => 'Student',
            'email' => 'course-pagination-student@example.test',
            'password' => 'unused',
            'role' => User::ROLE_STUDENT,
        ]);
        $classId = DB::table('classes')->insertGetId([
            'name' => 'Class',
            'code' => 'PAGINATION',
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('class_user')->insert(['class_id' => $classId, 'user_id' => $student->id]);

        foreach (range(1, 20) as $number) {
            $courseId = DB::table('courses')->insertGetId([
                'title' => 'Course '.$number,
                'teacher_id' => $teacher->id,
                'course_type' => 'delivery',
                'status' => 'published',
                'created_at' => now()->subMinutes($number),
                'updated_at' => now(),
            ]);
            DB::table('class_course')->insert(['class_id' => $classId, 'course_id' => $courseId]);
            $moduleId = DB::table('modules')->insertGetId([
                'course_id' => $courseId,
                'status' => 'published',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('lessons')->insert([
                'module_id' => $moduleId,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($teacher);
        $view = app(CourseController::class)->index();
        $data = $view->getData();

        $this->assertSame(20, $data['courses']->total());
        $this->assertCount(18, $data['courses']->items());
        $this->assertSame(20, $data['courseStats']['total']);
        $this->assertSame(20, $data['courseStats']['lessons']);
        $this->assertTrue($data['courses']->getCollection()->every(fn ($course) => (int) $course->students_count === 1));
    }

    private function createSchema(): void
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
        Schema::create('learning_programs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->timestamps();
        });
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('learning_program_id')->nullable();
            $table->string('course_type');
            $table->string('status');
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('class_course', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('course_id');
        });
        Schema::create('class_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('status')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('status')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
    }
}
