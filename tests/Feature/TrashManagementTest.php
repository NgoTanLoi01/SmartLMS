<?php

namespace Tests\Feature;

use App\Models\Assignments;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialAssignment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrashManagementTest extends TestCase
{
    private User $admin;

    private User $teacher;

    private User $otherTeacher;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireIsolatedSqliteDatabase();
        $this->createSchema();
        $this->admin = $this->user('trash-admin@example.com', User::ROLE_ADMIN);
        $this->teacher = $this->user('trash-teacher@example.com', User::ROLE_TEACHER);
        $this->otherTeacher = $this->user('trash-other@example.com', User::ROLE_TEACHER);
        $this->course = Course::create([
            'title' => 'Khóa học đã lưu trữ',
            'teacher_id' => $this->teacher->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_ARCHIVED,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            foreach ([
                'audit_logs', 'smart_notifications', 'learning_material_assignments', 'learning_materials',
                'assignment_submissions', 'schedule_resource_locks', 'schedules', 'assignments',
                'lessons', 'modules', 'class_course', 'classes', 'courses', 'users',
            ] as $table) {
                Schema::dropIfExists($table);
            }
        }

        parent::tearDown();
    }

    public function test_teacher_only_sees_their_own_archived_content_and_has_no_permanent_delete_action(): void
    {
        Course::create([
            'title' => 'Khóa học của giáo viên khác',
            'teacher_id' => $this->otherTeacher->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_ARCHIVED,
        ]);

        $this->actingAs($this->teacher)
            ->get(route('trash.index'))
            ->assertOk()
            ->assertSee('Khóa học đã lưu trữ')
            ->assertDontSee('Khóa học của giáo viên khác')
            ->assertDontSee('Xóa vĩnh viễn');
    }

    public function test_trash_page_renders_each_flash_message_only_once(): void
    {
        $message = 'Đã xóa vĩnh viễn 1 mục và dữ liệu phụ thuộc liên quan.';

        $response = $this->actingAs($this->admin)
            ->withSession(['success' => $message])
            ->get(route('trash.index'))
            ->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), $message));
    }

    public function test_student_cannot_access_the_trash(): void
    {
        $student = $this->user('trash-student@example.com', User::ROLE_STUDENT);

        $this->actingAs($student)->get(route('trash.index'))->assertForbidden();
        $this->actingAs($student)->patchJson(route('trash.restore'), [
            'items' => ['course:'.$this->course->id],
        ])->assertForbidden();
    }

    public function test_teacher_cannot_restore_another_teachers_archived_content_by_direct_request(): void
    {
        $otherCourse = Course::create([
            'title' => 'Khóa học không thuộc quyền quản lý',
            'teacher_id' => $this->otherTeacher->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_ARCHIVED,
        ]);

        $this->actingAs($this->teacher)->patchJson(route('trash.restore'), [
            'items' => ['course:'.$otherCourse->id],
        ])->assertNotFound();

        $this->assertDatabaseHas('courses', [
            'id' => $otherCourse->id,
            'status' => Course::STATUS_ARCHIVED,
        ]);
    }

    public function test_bulk_restore_orders_parent_before_child_and_uses_safe_statuses(): void
    {
        $module = Module::create([
            'course_id' => $this->course->id,
            'title' => 'Chương đã lưu trữ',
            'order' => 1,
            'status' => Module::STATUS_ARCHIVED,
        ]);

        $this->actingAs($this->teacher)->patch(route('trash.restore'), [
            'items' => ['module:'.$module->id, 'course:'.$this->course->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('courses', ['id' => $this->course->id, 'status' => Course::STATUS_DRAFT]);
        $this->assertDatabaseHas('modules', ['id' => $module->id, 'status' => Module::STATUS_PUBLISHED]);
    }

    public function test_child_cannot_be_restored_while_its_parent_is_archived(): void
    {
        $module = Module::create([
            'course_id' => $this->course->id,
            'title' => 'Chương cha',
            'order' => 1,
            'status' => Module::STATUS_ARCHIVED,
        ]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Bài học con',
            'order' => 1,
            'status' => Lesson::STATUS_ARCHIVED,
        ]);

        $this->actingAs($this->teacher)->patchJson(route('trash.restore'), [
            'items' => ['lesson:'.$lesson->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'status' => Lesson::STATUS_ARCHIVED]);
    }

    public function test_assignment_can_be_restored_individually_as_a_draft(): void
    {
        $this->course->update(['status' => Course::STATUS_DRAFT]);
        $assignment = Assignments::create([
            'course_id' => $this->course->id,
            'title' => 'Bài tập đã lưu trữ',
            'instructions' => 'Nội dung bài tập',
            'due_date' => now()->addWeek(),
            'status' => Assignments::STATUS_ARCHIVED,
        ]);

        $this->actingAs($this->teacher)->patch(route('trash.restore'), [
            'items' => ['assignment:'.$assignment->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'status' => Assignments::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    public function test_restoring_an_archived_schedule_rechecks_conflicts(): void
    {
        $this->course->update(['status' => Course::STATUS_DRAFT]);
        $classroom = Classroom::create([
            'name' => 'Lớp lịch học',
            'teacher_id' => $this->teacher->id,
            'status' => Classroom::STATUS_ACTIVE,
        ]);
        DB::table('class_course')->insert(['class_id' => $classroom->id, 'course_id' => $this->course->id]);
        Schedule::create([
            'class_id' => $classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => '2026-09-10',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => Schedule::STATUS_ACTIVE,
        ]);
        $archived = Schedule::create([
            'class_id' => $classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => Schedule::STATUS_ARCHIVED,
        ]);

        $this->actingAs($this->teacher)->patchJson(route('trash.restore'), [
            'items' => ['schedule:'.$archived->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('schedule');

        $this->assertDatabaseHas('schedules', ['id' => $archived->id, 'status' => Schedule::STATUS_ARCHIVED]);
    }

    public function test_restoring_material_and_its_course_assignment_keeps_assignment_hidden(): void
    {
        $this->course->update(['status' => Course::STATUS_DRAFT]);
        $material = LearningMaterial::create([
            'title' => 'Slide đã lưu trữ',
            'type' => 'slide',
            'source_type' => LearningMaterial::SOURCE_LINK,
            'url' => 'https://example.com/slide',
            'uploaded_by' => $this->teacher->id,
            'status' => LearningMaterial::STATUS_ARCHIVED,
        ]);
        $assignment = LearningMaterialAssignment::create([
            'learning_material_id' => $material->id,
            'course_id' => $this->course->id,
            'status' => LearningMaterialAssignment::STATUS_ARCHIVED,
        ]);

        $this->actingAs($this->teacher)->patch(route('trash.restore'), [
            'items' => ['material_assignment:'.$assignment->id, 'material:'.$material->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('learning_materials', ['id' => $material->id, 'status' => LearningMaterial::STATUS_PUBLISHED]);
        $this->assertDatabaseHas('learning_material_assignments', ['id' => $assignment->id, 'status' => LearningMaterialAssignment::STATUS_HIDDEN]);
    }

    public function test_only_admin_can_permanently_delete_and_confirmation_is_required(): void
    {
        $payload = ['items' => ['course:'.$this->course->id], 'confirmation' => 'XOA VINH VIEN'];

        $this->actingAs($this->teacher)
            ->deleteJson(route('trash.permanent-delete'), $payload)
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->deleteJson(route('trash.permanent-delete'), [
                'items' => ['course:'.$this->course->id],
                'confirmation' => 'xoa vinh vien',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('confirmation');

        $this->assertDatabaseHas('courses', ['id' => $this->course->id]);

        $this->actingAs($this->admin)
            ->delete(route('trash.permanent-delete'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('courses', ['id' => $this->course->id]);
    }

    private function user(string $email, string $role): User
    {
        return User::create([
            'name' => $email,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
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
        Schema::create('smart_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->json('data')->nullable();
            $table->string('dedupe_key')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('teacher_id');
            $table->string('course_type')->default('delivery');
            $table->string('status');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('teacher_id');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('class_course', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('course_id');
        });
        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->integer('order')->default(0);
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('title');
            $table->text('content')->nullable();
            $table->integer('order')->default(0);
            $table->string('status');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->string('attachment')->nullable();
            $table->string('attachment_disk')->nullable();
            $table->timestamps();
        });
        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->string('title');
            $table->text('instructions');
            $table->dateTime('due_date');
            $table->string('status');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('user_id');
            $table->string('file_path')->nullable();
            $table->string('file_disk')->default('public');
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('learning_materials', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('type');
            $table->string('source_type');
            $table->string('disk')->nullable();
            $table->string('file_path')->nullable();
            $table->string('url')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('learning_material_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('learning_material_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->unsignedBigInteger('unlock_when_lesson_id')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->string('status');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('schedules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('course_id');
            $table->date('schedule_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->string('note')->nullable();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('schedule_resource_locks', function (Blueprint $table): void {
            $table->id();
            $table->string('resource_key', 191)->unique();
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }
}
