<?php

namespace Tests\Feature;

use App\Jobs\NotifyFrequentAttendanceAbsences;
use App\Models\User;
use App\Services\NotificationCenter;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceSaveOptimizationTest extends TestCase
{
    /** @var list<string> */
    private array $attendanceWrites = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->requireIsolatedSqliteDatabase();

        $this->createSchema();
        DB::listen(function (QueryExecuted $query): void {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'attendance_data') && preg_match('/^(insert|update|delete|replace)/', ltrim($sql))) {
                $this->attendanceWrites[] = $query->sql;
            }
        });
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            foreach ([
                'smart_notifications', 'attendance_data', 'attendance_columns',
                'class_course', 'class_user', 'classes', 'courses', 'users',
            ] as $table) {
                Schema::dropIfExists($table);
            }
        }

        parent::tearDown();
    }

    public function test_save_writes_only_changed_cells_in_one_bulk_statement_and_queues_notification(): void
    {
        Queue::fake();
        [$teacher, $courseId, $studentIds, $columnIds] = $this->seedCourse(2, 2);
        $this->attendanceWrites = [];

        $this->actingAs($teacher)->post(route('attendance.save', $courseId), [
            'data' => [
                $columnIds[0] => [
                    $studentIds[0] => 'present',
                    $studentIds[1] => 'absent',
                ],
            ],
            'notes' => [
                $columnIds[0] => [
                    $studentIds[0] => null,
                    $studentIds[1] => 'Có phép',
                ],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertCount(1, $this->attendanceWrites);
        $this->assertDatabaseHas('attendance_data', [
            'attendance_column_id' => $columnIds[0],
            'user_id' => $studentIds[0],
            'value' => 'present',
            'note' => null,
        ]);
        $this->assertDatabaseHas('attendance_data', [
            'attendance_column_id' => $columnIds[0],
            'user_id' => $studentIds[1],
            'value' => 'absent',
            'note' => 'Có phép',
        ]);
        Queue::assertPushedOn('notifications', NotifyFrequentAttendanceAbsences::class);
    }

    public function test_no_op_payload_does_not_write_or_queue_notification(): void
    {
        Queue::fake();
        [$teacher, $courseId, $studentIds, $columnIds] = $this->seedCourse(1, 1);
        $this->attendanceWrites = [];

        $this->actingAs($teacher)->post(route('attendance.save', $courseId), [
            'data' => [$columnIds[0] => [$studentIds[0] => 'present']],
            'notes' => [$columnIds[0] => [$studentIds[0] => null]],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame([], $this->attendanceWrites);
        Queue::assertNothingPushed();
    }

    public function test_empty_payload_keeps_the_existing_no_change_response(): void
    {
        Queue::fake();
        [$teacher, $courseId] = $this->seedCourse(1, 1);

        $this->actingAs($teacher)
            ->from(route('attendance.show', $courseId))
            ->post(route('attendance.save', $courseId), [])
            ->assertRedirect(route('attendance.show', $courseId))
            ->assertSessionHas('success', 'Không có thay đổi cần lưu.');

        Queue::assertNothingPushed();
    }

    public function test_attendance_status_and_existing_note_are_preserved_as_before_refactor(): void
    {
        Queue::fake();
        [$teacher, $courseId, $studentIds, $columnIds] = $this->seedCourse(1, 1);
        DB::table('attendance_data')
            ->where('attendance_column_id', $columnIds[0])
            ->where('user_id', $studentIds[0])
            ->update(['note' => 'Giữ nguyên ghi chú']);

        $this->actingAs($teacher)->post(route('attendance.save', $courseId), [
            'data' => [$columnIds[0] => [$studentIds[0] => 'Vắng']],
        ])->assertSessionHas('success', 'Đã lưu bảng điểm danh thành công!');

        $this->assertDatabaseHas('attendance_data', [
            'attendance_column_id' => $columnIds[0],
            'user_id' => $studentIds[0],
            'value' => 'absent',
            'note' => 'Giữ nguyên ghi chú',
        ]);
        Queue::assertPushedOn('notifications', NotifyFrequentAttendanceAbsences::class);
    }

    public function test_legacy_grade_cells_preserve_decimal_comma_and_absence_text(): void
    {
        [$teacher, $courseId, $studentIds] = $this->seedCourse(2, 0);
        $gradeColumnId = DB::table('attendance_columns')->insertGetId([
            'course_id' => $courseId,
            'name' => 'HS1',
            'type' => 'grade',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($teacher)->post(route('attendance.save', $courseId), [
            'data' => [
                $gradeColumnId => [
                    $studentIds[0] => '6,5',
                    $studentIds[1] => 'vắng',
                ],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_data', [
            'attendance_column_id' => $gradeColumnId,
            'user_id' => $studentIds[0],
            'value' => '6,5',
        ]);
        $this->assertDatabaseHas('attendance_data', [
            'attendance_column_id' => $gradeColumnId,
            'user_id' => $studentIds[1],
            'value' => 'vắng',
        ]);
    }

    public function test_invalid_nested_payload_is_rejected_before_the_use_case_runs(): void
    {
        Queue::fake();
        [$teacher, $courseId, $studentIds, $columnIds] = $this->seedCourse(1, 1);
        $this->attendanceWrites = [];

        $this->actingAs($teacher)
            ->from(route('attendance.show', $courseId))
            ->post(route('attendance.save', $courseId), [
                'data' => [$columnIds[0] => [$studentIds[0] => str_repeat('x', 256)]],
            ])
            ->assertRedirect(route('attendance.show', $courseId))
            ->assertSessionHasErrors("data.{$columnIds[0]}.{$studentIds[0]}");

        $this->assertSame([], $this->attendanceWrites);
        Queue::assertNothingPushed();
    }

    public function test_save_rejects_cells_outside_the_course_scope(): void
    {
        Queue::fake();
        [$teacher, $courseId, $studentIds, $columnIds] = $this->seedCourse(1, 1);
        [, $otherCourseId, $otherStudentIds, $otherColumnIds] = $this->seedCourse(1, 1, 'other');
        $this->attendanceWrites = [];

        $this->actingAs($teacher)->post(route('attendance.save', $courseId), [
            'data' => [$otherColumnIds[0] => [$studentIds[0] => 'absent']],
        ])->assertStatus(422);

        $this->actingAs($teacher)->post(route('attendance.save', $courseId), [
            'data' => [$columnIds[0] => [$otherStudentIds[0] => 'absent']],
        ])->assertStatus(422);

        $this->assertNotSame($courseId, $otherCourseId);
        $this->assertSame([], $this->attendanceWrites);
        Queue::assertNothingPushed();
    }

    public function test_absence_notification_uses_aggregate_read_and_is_idempotent(): void
    {
        [, $courseId, $studentIds, $columnIds] = $this->seedCourse(1, 3);
        DB::table('attendance_data')
            ->where('user_id', $studentIds[0])
            ->whereIn('attendance_column_id', $columnIds)
            ->update(['value' => 'absent']);

        $job = new NotifyFrequentAttendanceAbsences($courseId, [$studentIds[0], $studentIds[0]]);
        $job->handle(app(NotificationCenter::class));
        $job->handle(app(NotificationCenter::class));

        $this->assertSame([$studentIds[0]], $job->userIds);
        $this->assertDatabaseCount('smart_notifications', 1);
        $this->assertDatabaseHas('smart_notifications', [
            'user_id' => $studentIds[0],
            'type' => 'attendance_warning',
            'dedupe_key' => "attendance-warning:{$courseId}:{$studentIds[0]}:3",
        ]);
    }

    /** @return array{User,int,list<int>,list<int>} */
    private function seedCourse(int $studentCount, int $columnCount, string $suffix = 'main'): array
    {
        $now = now();
        $teacherId = DB::table('users')->insertGetId([
            'name' => 'Teacher '.$suffix,
            'email' => "teacher-{$suffix}@example.test",
            'password' => 'unused',
            'role' => User::ROLE_TEACHER,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $courseId = DB::table('courses')->insertGetId([
            'title' => 'Course '.$suffix,
            'teacher_id' => $teacherId,
            'course_type' => 'delivery',
            'status' => 'published',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $classId = DB::table('classes')->insertGetId([
            'name' => 'Class '.$suffix,
            'code' => 'CLASS-'.strtoupper($suffix),
            'teacher_id' => $teacherId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('class_course')->insert([
            'class_id' => $classId,
            'course_id' => $courseId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $studentIds = [];
        foreach (range(1, $studentCount) as $number) {
            $studentIds[] = DB::table('users')->insertGetId([
                'name' => "Student {$suffix} {$number}",
                'email' => "student-{$suffix}-{$number}@example.test",
                'password' => 'unused',
                'role' => User::ROLE_STUDENT,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        DB::table('class_user')->insert(array_map(fn (int $studentId): array => [
            'class_id' => $classId,
            'user_id' => $studentId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $studentIds));

        $columnIds = [];
        foreach (range(1, $columnCount) as $number) {
            $columnIds[] = DB::table('attendance_columns')->insertGetId([
                'course_id' => $courseId,
                'name' => 'Buổi '.$number,
                'type' => 'attendance',
                'order' => $number,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('attendance_data')->insert(collect($columnIds)->flatMap(fn (int $columnId) => collect($studentIds)->map(fn (int $studentId): array => [
            'attendance_column_id' => $columnId,
            'user_id' => $studentId,
            'value' => 'present',
            'note' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]))->all());

        return [User::findOrFail($teacherId), $courseId, $studentIds, $columnIds];
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
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('teacher_id');
            $table->string('course_type');
            $table->string('status');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedBigInteger('teacher_id');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('class_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['class_id', 'user_id']);
        });
        Schema::create('class_course', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('course_id');
            $table->timestamps();
            $table->unique(['class_id', 'course_id']);
        });
        Schema::create('attendance_columns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('name');
            $table->string('type');
            $table->integer('order');
            $table->timestamps();
        });
        Schema::create('attendance_data', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attendance_column_id');
            $table->unsignedBigInteger('user_id');
            $table->string('value')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['attendance_column_id', 'user_id']);
        });
        Schema::create('smart_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->text('data')->nullable();
            $table->string('dedupe_key')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'dedupe_key']);
        });
    }
}
