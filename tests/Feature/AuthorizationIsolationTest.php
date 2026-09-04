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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        if ($this->usesIsolatedSqliteDatabase()) {
            foreach ([
                'smart_notifications',
                'assignment_submissions', 'questions', 'course_question_bank', 'question_banks', 'schedules', 'attendance_columns',
                'quizzes', 'assignments', 'lessons', 'modules', 'class_course', 'class_user', 'classes', 'courses', 'users',
            ] as $table) {
                Schema::dropIfExists($table);
            }
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

    public function test_teacher_without_classes_cannot_bypass_schedule_import_scope(): void
    {
        $targetDate = now()->addDays(3);
        $csv = implode("\n", [
            'Ngày,Giờ học,Tên môn học,Lớp,Phòng học',
            $targetDate->format('d/m/Y').',12:00-13:00,Khóa của A,Lớp của A,P101',
        ]);

        $this->actingAs($this->otherTeacher)
            ->post(route('schedules.import'), [
                'file' => UploadedFile::fake()->createWithContent('schedule.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('schedules', [
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => $targetDate->toDateString(),
            'start_time' => '12:00:00',
        ]);
    }

    public function test_schedule_import_rejects_a_renamed_executable(): void
    {
        $this->actingAs($this->admin)
            ->post(route('schedules.import'), [
                'file' => UploadedFile::fake()->createWithContent(
                    'schedule.xlsx',
                    "#!/bin/sh\necho unsafe\n"
                ),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_admin_can_import_schedule_without_an_explicit_class_scope(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->classroom->students()->attach($student);
        $targetDate = now()->addDays(4);
        $csv = implode("\n", [
            'Ngày,Giờ học,Tên môn học,Lớp,Phòng học',
            $targetDate->format('d/m/Y').',12:00-13:00,Khóa của A,Lớp của A,P102',
        ]);

        $this->actingAs($this->admin)
            ->post(route('schedules.import'), [
                'file' => UploadedFile::fake()->createWithContent('schedule.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('schedules', [
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => $targetDate->toDateString(),
            'start_time' => '12:00:00',
            'room' => 'P102',
        ]);
        $this->assertDatabaseHas('smart_notifications', [
            'user_id' => $student->id,
            'type' => 'schedule',
            'title' => 'Lịch học mới đã được nhập',
        ]);
    }

    public function test_import_skips_conflicts_without_mutating_existing_exam_note(): void
    {
        $this->schedule->update(['note' => 'Thi kết thúc môn']);
        $csv = implode("\n", [
            'Ngày,Giờ học,Tên môn học,Lớp,Phòng học',
            now()->format('d/m/Y').',09:00-11:00,Khóa của A - Thi kết thúc môn,Lớp của A,P103',
        ]);

        $this->actingAs($this->owner)
            ->post(route('schedules.import'), [
                'file' => UploadedFile::fake()->createWithContent('schedule.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Bỏ qua 1 lịch bị trùng giờ'));

        $this->assertDatabaseCount('schedules', 1);
        $this->assertDatabaseHas('schedules', [
            'id' => $this->schedule->id,
            'note' => 'Thi kết thúc môn',
        ]);
    }

    public function test_direct_http_request_rejects_overlapping_class_schedule(): void
    {
        $this->actingAs($this->owner)
            ->postJson(route('schedules.store'), [
                'class_id' => $this->classroom->id,
                'course_id' => $this->course->id,
                'schedule_date' => now()->toDateString(),
                'start_time' => '09:00',
                'end_time' => '11:00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('schedule')
            ->assertJsonFragment(['Lớp học đã có lịch trong khoảng thời gian này. Giáo viên đã có lịch dạy trong khoảng thời gian này.']);

        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_direct_http_request_rejects_teacher_and_room_conflicts(): void
    {
        $secondClass = Classroom::create([
            'name' => 'Lớp thứ hai của A',
            'code' => 'A-02',
            'teacher_id' => $this->owner->id,
            'status' => Classroom::STATUS_ACTIVE,
        ]);
        $secondClass->courses()->attach($this->course);

        $this->actingAs($this->owner)
            ->postJson(route('schedules.store'), [
                'class_id' => $secondClass->id,
                'course_id' => $this->course->id,
                'schedule_date' => now()->toDateString(),
                'start_time' => '09:30',
                'end_time' => '10:30',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('schedule')
            ->assertJsonFragment(['Giáo viên đã có lịch dạy trong khoảng thời gian này.']);

        $this->schedule->update(['room' => 'P101']);
        $otherCourse = Course::create([
            'title' => 'Khóa của B',
            'description' => 'Test',
            'teacher_id' => $this->otherTeacher->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $otherClass = Classroom::create([
            'name' => 'Lớp của B',
            'code' => 'B-01',
            'teacher_id' => $this->otherTeacher->id,
            'status' => Classroom::STATUS_ACTIVE,
        ]);
        $otherClass->courses()->attach($otherCourse);

        $this->actingAs($this->otherTeacher)
            ->postJson(route('schedules.store'), [
                'class_id' => $otherClass->id,
                'course_id' => $otherCourse->id,
                'schedule_date' => now()->toDateString(),
                'start_time' => '08:30',
                'end_time' => '09:30',
                'room' => ' p101 ',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('schedule')
            ->assertJsonFragment(['Phòng p101 đã được sử dụng trong khoảng thời gian này.']);

        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_direct_http_request_cannot_bypass_conflicts_when_updating_schedule(): void
    {
        $secondSchedule = Schedule::create([
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => now()->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => Schedule::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->owner)
            ->putJson(route('schedules.update', $secondSchedule), [
                'class_id' => $this->classroom->id,
                'course_id' => $this->course->id,
                'schedule_date' => now()->toDateString(),
                'start_time' => '09:00',
                'end_time' => '11:00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('schedule');

        $this->assertDatabaseHas('schedules', [
            'id' => $secondSchedule->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);
    }

    public function test_schedule_event_api_only_returns_requested_date_range(): void
    {
        $inside = Schedule::create([
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => now()->addDays(10)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => Schedule::STATUS_ACTIVE,
        ]);
        Schedule::create([
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => now()->addMonths(3)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => Schedule::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->owner)->getJson(route('schedules.index', [
            'start' => now()->addDays(9)->toIso8601String(),
            'end' => now()->addDays(12)->toIso8601String(),
        ]));

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $inside->id);
    }

    public function test_recurring_schedule_preview_lists_conflicting_occurrences(): void
    {
        $response = $this->actingAs($this->owner)->postJson(route('schedules.series.preview'), [
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'repeat_interval' => 1,
            'end_mode' => 'count',
            'occurrence_count' => 3,
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.total', 3)
            ->assertJsonPath('summary.available', 2)
            ->assertJsonPath('summary.conflicts', 1)
            ->assertJsonPath('occurrences.0.has_conflict', true)
            ->assertJsonPath('occurrences.1.has_conflict', false);
    }

    public function test_recurring_schedule_preview_supports_biweekly_repetition_until_a_date(): void
    {
        $startDate = now()->addDay()->startOfDay();

        $response = $this->actingAs($this->owner)->postJson(route('schedules.series.preview'), [
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => $startDate->toDateString(),
            'start_time' => '12:00',
            'end_time' => '13:00',
            'repeat_interval' => 2,
            'end_mode' => 'date',
            'repeat_until' => $startDate->copy()->addDays(28)->toDateString(),
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.total', 3)
            ->assertJsonPath('occurrences.0.date', $startDate->toDateString())
            ->assertJsonPath('occurrences.1.date', $startDate->copy()->addDays(14)->toDateString())
            ->assertJsonPath('occurrences.2.date', $startDate->copy()->addDays(28)->toDateString());
    }

    public function test_recurring_schedule_can_skip_conflicts_and_preserve_series_positions(): void
    {
        $response = $this->actingAs($this->owner)->postJson(route('schedules.series.store'), [
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'room' => 'P202',
            'repeat_interval' => 1,
            'end_mode' => 'count',
            'occurrence_count' => 3,
            'skip_conflicts' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('created_count', 2)
            ->assertJsonPath('skipped_count', 1);

        $seriesId = $response->json('series_id');
        $this->assertNotEmpty($seriesId);
        $this->assertDatabaseCount('schedules', 3);
        $this->assertSame(
            [2, 3],
            Schedule::query()->where('series_id', $seriesId)->orderBy('series_position')->pluck('series_position')->all()
        );
    }

    public function test_recurring_schedule_without_skip_is_atomic_when_a_conflict_exists(): void
    {
        $this->actingAs($this->owner)->postJson(route('schedules.series.store'), [
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'repeat_interval' => 1,
            'end_mode' => 'count',
            'occurrence_count' => 3,
            'skip_conflicts' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('schedule');

        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_teacher_cannot_preview_a_recurring_schedule_for_another_teachers_class(): void
    {
        $this->actingAs($this->otherTeacher)->postJson(route('schedules.series.preview'), [
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => now()->addDay()->toDateString(),
            'start_time' => '12:00',
            'end_time' => '13:00',
            'repeat_interval' => 1,
            'end_mode' => 'count',
            'occurrence_count' => 3,
        ])->assertForbidden();
    }

    public function test_updating_one_occurrence_does_not_modify_the_rest_of_the_series(): void
    {
        $series = $this->createScheduleSeries();
        $selected = $series[1];

        $this->actingAs($this->owner)->putJson(route('schedules.update', $selected), [
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => $selected->schedule_date->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '15:00',
            'update_scope' => 'occurrence',
        ])->assertOk();

        $this->assertDatabaseHas('schedules', ['id' => $selected->id, 'start_time' => '14:00']);
        $this->assertDatabaseHas('schedules', ['id' => $series[0]->id, 'start_time' => '12:00:00']);
        $this->assertDatabaseHas('schedules', ['id' => $series[2]->id, 'start_time' => '12:00:00']);
    }

    public function test_updating_a_series_shifts_all_dates_and_checks_the_series_as_a_unit(): void
    {
        $series = $this->createScheduleSeries();
        $selected = $series[1];
        $newSelectedDate = $selected->schedule_date->copy()->addDay();

        $this->actingAs($this->owner)->putJson(route('schedules.update', $selected), [
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => $newSelectedDate->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '15:00',
            'room' => 'P303',
            'update_scope' => 'series',
        ])->assertOk()->assertJsonPath('updated_count', 3);

        foreach ($series as $member) {
            $this->assertDatabaseHas('schedules', [
                'id' => $member->id,
                'schedule_date' => $member->schedule_date->copy()->addDay()->format('Y-m-d'),
                'start_time' => '14:00',
                'end_time' => '15:00',
                'room' => 'P303',
            ]);
        }
    }

    public function test_conflict_in_one_occurrence_rolls_back_the_entire_series_update(): void
    {
        $series = $this->createScheduleSeries();
        $selected = $series[0];
        Schedule::create([
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => now()->addDays(15)->toDateString(),
            'start_time' => '12:30:00',
            'end_time' => '13:30:00',
            'status' => Schedule::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->owner)->putJson(route('schedules.update', $selected), [
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => $selected->schedule_date->copy()->addDay()->toDateString(),
            'start_time' => '12:00',
            'end_time' => '13:00',
            'update_scope' => 'series',
        ])->assertUnprocessable()->assertJsonValidationErrors('schedule');

        foreach ($series as $member) {
            $this->assertDatabaseHas('schedules', [
                'id' => $member->id,
                'schedule_date' => $member->schedule_date->toDateString(),
                'start_time' => '12:00:00',
            ]);
        }
    }

    public function test_archiving_a_series_does_not_archive_unrelated_schedules(): void
    {
        $series = $this->createScheduleSeries();

        $this->actingAs($this->owner)
            ->deleteJson(route('schedules.destroy', $series[1]), ['delete_scope' => 'series'])
            ->assertOk()
            ->assertJsonPath('archived_count', 3);

        $this->assertSame(3, Schedule::query()->where('series_id', $series[0]->series_id)->where('status', Schedule::STATUS_ARCHIVED)->count());
        $this->assertDatabaseHas('schedules', ['id' => $this->schedule->id, 'status' => Schedule::STATUS_ACTIVE]);
    }

    public function test_recurring_schedule_http_request_cannot_exceed_the_occurrence_limit(): void
    {
        $this->actingAs($this->owner)->postJson(route('schedules.series.store'), [
            'class_id' => $this->classroom->id,
            'course_id' => $this->course->id,
            'schedule_date' => now()->addDay()->toDateString(),
            'start_time' => '12:00',
            'end_time' => '13:00',
            'repeat_interval' => 1,
            'end_mode' => 'count',
            'occurrence_count' => 105,
        ])->assertUnprocessable()->assertJsonValidationErrors('occurrence_count');

        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_student_schedule_requires_the_exact_class_course_pair(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $secondCourse = Course::create([
            'title' => 'Khóa thứ hai',
            'description' => 'Test',
            'teacher_id' => $this->owner->id,
            'course_type' => 'delivery',
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $secondClass = Classroom::create([
            'name' => 'Lớp thứ hai',
            'code' => 'A-03',
            'teacher_id' => $this->owner->id,
            'status' => Classroom::STATUS_ACTIVE,
        ]);
        $this->classroom->students()->attach($student);
        $secondClass->students()->attach($student);
        $secondClass->courses()->attach($secondCourse);

        $invalidPairSchedule = Schedule::create([
            'class_id' => $this->classroom->id,
            'course_id' => $secondCourse->id,
            'schedule_date' => now()->addDay()->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => Schedule::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($student)->getJson(route('students.schedule', [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->addDays(3)->toIso8601String(),
        ]));

        $response->assertOk();
        $this->assertNotContains($invalidPairSchedule->id, $response->collect()->pluck('id')->all());
        $this->assertContains($this->schedule->id, $response->collect()->pluck('id')->all());
    }

    public function test_lesson_content_is_lazy_loaded_only_for_authorized_users(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->classroom->students()->attach($student);

        $this->actingAs($this->owner)
            ->getJson(route('lessons.content', $this->lesson))
            ->assertOk()
            ->assertJsonPath('id', $this->lesson->id)
            ->assertJsonPath('content', '<p>Nội dung xem lại.</p>');

        $this->actingAs($student)
            ->getJson(route('lessons.content', $this->lesson))
            ->assertOk()
            ->assertJsonPath('content', '<p>Nội dung xem lại.</p>');

        $this->actingAs($this->otherTeacher)
            ->getJson(route('lessons.content', $this->lesson))
            ->assertForbidden();
    }

    public function test_owner_can_create_and_edit_rich_text_lesson_content(): void
    {
        $this->actingAs($this->owner)
            ->post(route('lessons.store'), [
                'module_id' => $this->module->id,
                'title' => 'Bài soạn bằng editor',
                'content' => '<h2>Bài mới</h2><p><strong>Nội dung</strong></p><script>alert(1)</script>',
                'status' => Lesson::STATUS_DRAFT,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $lesson = Lesson::where('title', 'Bài soạn bằng editor')->firstOrFail();
        $this->assertSame('<h2>Bài mới</h2><p><strong>Nội dung</strong></p>', $lesson->content);

        $this->actingAs($this->owner)
            ->put(route('lessons.update', $lesson), [
                'module_id' => $this->module->id,
                'title' => 'Bài soạn bằng editor',
                'content' => '<h3>Nội dung đã sửa</h3><ul><li>Mục một</li></ul><img src="javascript:alert(2)">',
                'status' => Lesson::STATUS_DRAFT,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $lesson->refresh();
        $this->assertStringContainsString('<h3>Nội dung đã sửa</h3>', $lesson->content);
        $this->assertStringContainsString('<ul><li>Mục một</li></ul>', $lesson->content);
        $this->assertStringNotContainsString('javascript:', $lesson->content);
    }

    public function test_owner_can_create_and_edit_rich_text_assignment_instructions(): void
    {
        $this->actingAs($this->owner)
            ->post(route('assignments.store'), [
                'course_id' => $this->course->id,
                'lesson_id' => $this->lesson->id,
                'type' => 'essay',
                'title' => 'Bài tập soạn bằng editor',
                'instructions' => '<h3>Yêu cầu</h3><ol><li>Hoàn thành phần một</li></ol><script>alert(1)</script>',
                'due_date' => now()->addWeek()->toDateTimeString(),
                'status' => Assignments::STATUS_DRAFT,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $assignment = Assignments::where('title', 'Bài tập soạn bằng editor')->firstOrFail();
        $this->assertStringContainsString('<h3>Yêu cầu</h3>', $assignment->instructions);
        $this->assertStringNotContainsString('<script', $assignment->instructions);

        $this->actingAs($this->owner)
            ->put(route('assignments.update', $assignment), [
                'lesson_id' => $this->lesson->id,
                'type' => 'essay',
                'title' => 'Bài tập soạn bằng editor',
                'instructions' => '<p><strong>Yêu cầu đã sửa</strong></p><a href="javascript:alert(2)">x</a>',
                'due_date' => now()->addWeek()->toDateTimeString(),
                'status' => Assignments::STATUS_DRAFT,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $assignment->refresh();
        $this->assertStringContainsString('<strong>Yêu cầu đã sửa</strong>', $assignment->instructions);
        $this->assertStringNotContainsString('javascript:', $assignment->instructions);
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

    public function test_teacher_can_select_submission_formats_and_student_sees_the_requirement(): void
    {
        $this->actingAs($this->owner)
            ->post(route('assignments.store'), [
                'course_id' => $this->course->id,
                'lesson_id' => $this->lesson->id,
                'type' => 'file',
                'title' => 'Bài thuyết trình cuối khóa',
                'instructions' => 'Nộp bài thuyết trình theo đúng định dạng.',
                'due_date' => now()->addWeek()->toDateTimeString(),
                'allowed_extensions' => ['ppt', 'pptx', 'pdf'],
                'max_file_size' => 10240,
                'status' => Assignments::STATUS_PUBLISHED,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $assignment = Assignments::where('title', 'Bài thuyết trình cuối khóa')->sole();
        $this->assertSame('ppt,pptx,pdf', $assignment->allowed_extensions);
        $this->assertSame(10240, $assignment->max_file_size);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->classroom->students()->attach($student);

        $this->actingAs($student)
            ->get(route('assignments.index'))
            ->assertOk()
            ->assertSee('Bài thuyết trình cuối khóa')
            ->assertSee('.PPT, .PPTX, .PDF')
            ->assertSee('tối đa 10 MB');
    }

    public function test_submission_rejects_a_file_format_not_selected_by_the_teacher(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->classroom->students()->attach($student);
        $this->assignment->update([
            'type' => 'file',
            'allowed_extensions' => 'ppt,pptx',
            'max_file_size' => 10240,
        ]);

        $this->actingAs($student)
            ->post(route('assignments.submit', $this->assignment), [
                'file' => UploadedFile::fake()->createWithContent('answer.txt', 'not a presentation'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('assignment_submissions', [
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_assignment_submission_workflow_keeps_one_current_submission_and_enforces_authorization(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $otherStudent = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->classroom->students()->attach($student);

        $this->actingAs($student)
            ->post(route('assignments.submit', $this->assignment), [
                'text_answer' => 'Nội dung bài nộp lần đầu.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $submission = AssignmentSubmission::where('assignment_id', $this->assignment->id)
            ->where('user_id', $student->id)
            ->firstOrFail();

        $this->actingAs($student)
            ->post(route('assignments.submit', $this->assignment), [
                'text_answer' => 'Nội dung bài nộp đã cập nhật.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('assignment_submissions', 1);
        $this->assertDatabaseHas('assignment_submissions', [
            'id' => $submission->id,
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
            'text_answer' => 'Nội dung bài nộp đã cập nhật.',
        ]);
        $this->assertSame($submission->id, $student->fresh()->submissions()->sole()->id);

        $this->actingAs($this->owner)
            ->get(route('assignments.submissions.review', $submission))
            ->assertOk()
            ->assertSee('Nội dung bài nộp đã cập nhật.');

        $this->actingAs($this->otherTeacher)
            ->get(route('assignments.submissions.review', $submission))
            ->assertForbidden();

        $this->actingAs($otherStudent)
            ->get(route('assignments.submissions.review', $submission))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->post(route('assignments.grade', $submission), [
                'grade' => 8.5,
                'feedback' => 'Đạt yêu cầu.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assignment_submissions', [
            'id' => $submission->id,
            'grade' => 8.5,
            'feedback' => 'Đạt yêu cầu.',
        ]);

        $this->actingAs($student)
            ->post(route('assignments.grade', $submission), ['grade' => 10])
            ->assertForbidden();
    }

    public function test_direct_http_request_cannot_create_or_update_submission_after_deadline(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->classroom->students()->attach($student);
        $this->assignment->update(['due_date' => now()->subMinute()]);

        $response = $this->actingAs($student)
            ->post(route('assignments.submit', $this->assignment), [
                'text_answer' => 'Nội dung cố nộp sau khi đã quá hạn.',
            ]);
        $response->assertRedirect()->assertSessionHasErrors('submission');
        $this->assertStringContainsString('đã quá hạn', session('errors')->first('submission'));

        $this->assertDatabaseMissing('assignment_submissions', [
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
        ]);

        $submission = AssignmentSubmission::create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
            'text_answer' => 'Nội dung trước deadline.',
            'submitted_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($student)
            ->post(route('assignments.submit', $this->assignment), [
                'text_answer' => 'Nội dung cố cập nhật sau deadline.',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('submission');

        $this->assertSame('Nội dung trước deadline.', $submission->fresh()->text_answer);
    }

    public function test_direct_http_request_cannot_update_or_delete_a_graded_submission(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->classroom->students()->attach($student);
        $submission = AssignmentSubmission::create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
            'text_answer' => 'Bài đã được giáo viên chấm.',
            'grade' => 8.5,
            'feedback' => 'Đạt yêu cầu.',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->post(route('assignments.submit', $this->assignment), [
                'text_answer' => 'Cố sửa nội dung đã chấm.',
            ]);
        $response->assertRedirect()->assertSessionHasErrors('submission');
        $this->assertStringContainsString('đã được chấm điểm', session('errors')->first('submission'));

        $this->actingAs($student)
            ->delete(route('assignments.submissions.delete', $submission))
            ->assertRedirect()
            ->assertSessionHasErrors('submission');

        $this->assertDatabaseHas('assignment_submissions', [
            'id' => $submission->id,
            'text_answer' => 'Bài đã được giáo viên chấm.',
            'grade' => 8.5,
        ]);
    }

    public function test_direct_http_request_cannot_delete_submission_after_deadline(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->classroom->students()->attach($student);
        $this->assignment->update(['due_date' => now()->subMinute()]);
        $submission = AssignmentSubmission::create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
            'text_answer' => 'Bài nộp trước khi hết hạn.',
            'submitted_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($student)
            ->delete(route('assignments.submissions.delete', $submission));
        $response->assertRedirect()->assertSessionHasErrors('submission');
        $this->assertStringContainsString('đã quá hạn', session('errors')->first('submission'));

        $this->assertDatabaseHas('assignment_submissions', ['id' => $submission->id]);
    }

    public function test_student_review_only_contains_their_own_submission_data(): void
    {
        $student = User::factory()->create([
            'name' => 'Học viên hiện tại',
            'email' => 'current-student@example.test',
            'role' => User::ROLE_STUDENT,
        ]);
        $classmate = User::factory()->create([
            'name' => 'Bạn cùng lớp bí mật',
            'email' => 'classmate-secret@example.test',
            'role' => User::ROLE_STUDENT,
        ]);
        $this->classroom->students()->attach([$student->id, $classmate->id]);
        $ownSubmission = AssignmentSubmission::create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
            'text_answer' => 'Nội dung bài làm của chính tôi.',
            'grade' => 7.5,
            'feedback' => 'Nhận xét dành riêng cho tôi.',
            'submitted_at' => now(),
        ]);
        $classmateSubmission = AssignmentSubmission::create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $classmate->id,
            'text_answer' => 'Nội dung bí mật của bạn cùng lớp.',
            'grade' => 9.5,
            'feedback' => 'Nhận xét bí mật của bạn cùng lớp.',
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('assignments.submissions.review', $classmateSubmission))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('assignments.submissions.review', $ownSubmission))
            ->assertOk()
            ->assertSee('Bài làm của bạn')
            ->assertSee('Nội dung bài làm của chính tôi.')
            ->assertSee('Nhận xét dành riêng cho tôi.')
            ->assertDontSee('Danh sách học viên')
            ->assertDontSee('Bạn cùng lớp bí mật')
            ->assertDontSee('classmate-secret@example.test')
            ->assertDontSee('Nội dung bí mật của bạn cùng lớp.')
            ->assertDontSee('Nhận xét bí mật của bạn cùng lớp.')
            ->assertDontSee('9.5')
            ->assertDontSee('Tải bài nộp (.zip)')
            ->assertDontSee('AI phân tích bài làm');

        $this->actingAs($this->owner)
            ->get(route('assignments.submissions.review', $ownSubmission))
            ->assertOk()
            ->assertSee('Danh sách học viên')
            ->assertSee('Bạn cùng lớp bí mật')
            ->assertSee('Đã chấm: 9.5');
    }

    public function test_file_submission_is_stored_on_configured_private_disk_with_checksum(): void
    {
        Storage::fake('r2');
        config(['filesystems.submission_disk' => 'r2']);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->classroom->students()->attach($student);
        $this->assignment->update([
            'type' => 'file',
            'allowed_extensions' => 'txt',
            'max_file_size' => 1024,
        ]);

        $this->actingAs($student)
            ->post(route('assignments.submit', $this->assignment), [
                'file' => UploadedFile::fake()->createWithContent('answer.txt', 'private answer'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $submission = AssignmentSubmission::where('assignment_id', $this->assignment->id)
            ->where('user_id', $student->id)
            ->sole();
        $this->assertSame('r2', $submission->file_disk);
        $this->assertSame(hash('sha256', 'private answer'), $submission->checksum_sha256);
        Storage::disk('r2')->assertExists($submission->file_path);
    }

    public function test_submission_roster_endpoint_is_paginated_server_side(): void
    {
        $students = User::factory()->count(30)->create(['role' => User::ROLE_STUDENT]);
        $this->classroom->students()->attach($students->pluck('id'));

        $this->actingAs($this->owner)
            ->getJson(route('assignments.submissions.list', $this->assignment))
            ->assertOk()
            ->assertJsonCount(25, 'submissions')
            ->assertJsonPath('total_students', 30)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 2);

        $this->actingAs($this->owner)
            ->getJson(route('assignments.submissions.list', ['id' => $this->assignment->id, 'page' => 2]))
            ->assertOk()
            ->assertJsonCount(5, 'submissions')
            ->assertJsonPath('pagination.current_page', 2);
    }

    public function test_owner_can_bulk_archive_questions(): void
    {
        $secondQuestion = Question::create([
            'course_id' => $this->course->id,
            'question_bank_id' => $this->questionBank->id,
            'question_text' => 'Câu hỏi thứ hai của A',
            'difficulty' => 'medium',
        ]);

        $this->actingAs($this->owner)
            ->delete(route('questions.bulkDestroyBank'), [
                'question_ids' => [$this->question->id, $secondQuestion->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('questions', [
            'id' => $this->question->id,
            'status' => Question::STATUS_ARCHIVED,
        ]);
        $this->assertDatabaseHas('questions', [
            'id' => $secondQuestion->id,
            'status' => Question::STATUS_ARCHIVED,
        ]);
    }

    public function test_teacher_cannot_bulk_archive_another_teachers_questions(): void
    {
        $this->actingAs($this->otherTeacher)
            ->delete(route('questions.bulkDestroyBank'), [
                'question_ids' => [$this->question->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('questions', [
            'id' => $this->question->id,
            'status' => Question::STATUS_PUBLISHED,
        ]);
    }

    public function test_owner_can_restore_an_archived_question(): void
    {
        $this->question->update(['status' => Question::STATUS_ARCHIVED]);

        $this->actingAs($this->owner)
            ->patch(route('questions.restoreBank', $this->question))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('questions', [
            'id' => $this->question->id,
            'status' => Question::STATUS_PUBLISHED,
        ]);
    }

    public function test_teacher_cannot_restore_another_teachers_archived_question(): void
    {
        $this->question->update(['status' => Question::STATUS_ARCHIVED]);

        $this->actingAs($this->otherTeacher)
            ->patch(route('questions.restoreBank', $this->question))
            ->assertForbidden();

        $this->assertDatabaseHas('questions', [
            'id' => $this->question->id,
            'status' => Question::STATUS_ARCHIVED,
        ]);
    }

    public function test_assignment_index_is_filterable_and_limited_to_accessible_courses(): void
    {
        $this->actingAs($this->owner)
            ->get(route('assignments.index', [
                'q' => 'Bài tập của A',
                'course_id' => $this->course->id,
                'status' => Assignments::STATUS_PUBLISHED,
            ]))
            ->assertOk()
            ->assertSee('Tổng bài tập')
            ->assertSee('Bài tập của A')
            ->assertSee('Xem bài nộp');

        $this->actingAs($this->otherTeacher)
            ->get(route('assignments.index'))
            ->assertOk()
            ->assertDontSee('Bài tập của A');
    }

    public function test_content_clone_http_request_cannot_bypass_source_or_target_course_permissions(): void
    {
        $otherCourse = Course::create([
            'title' => 'Khóa đích của B',
            'teacher_id' => $this->otherTeacher->id,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $otherModule = Module::create([
            'course_id' => $otherCourse->id,
            'title' => 'Chương đích của B',
            'status' => Module::STATUS_PUBLISHED,
        ]);

        $payload = [
            'source_type' => 'lesson',
            'source_id' => $this->lesson->id,
            'target_course_id' => $otherCourse->id,
            'target_module_id' => $otherModule->id,
            'copy_assignments' => 1,
        ];

        $this->actingAs($this->owner)
            ->post(route('content-clones.store'), $payload)
            ->assertForbidden();

        $this->actingAs($this->otherTeacher)
            ->post(route('content-clones.store'), $payload)
            ->assertForbidden();

        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->actingAs($student)
            ->post(route('content-clones.store'), $payload)
            ->assertForbidden();
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
        Schema::create('smart_notifications', function (Blueprint $table) {
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
            $table->text('grading_rubric')->nullable();
            $table->unsignedInteger('grading_scale')->default(10);
            $table->boolean('ai_grading_enabled')->default(false);
            $table->dateTime('due_date');
            $table->string('allowed_extensions')->nullable();
            $table->unsignedInteger('max_file_size')->nullable();
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
            $table->string('file_disk')->default('public');
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->char('checksum_sha256', 64)->nullable();
            $table->text('text_answer')->nullable();
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'user_id']);
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
            $table->uuid('series_id')->nullable();
            $table->unsignedSmallInteger('series_position')->nullable();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('course_id');
            $table->date('schedule_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->string('note')->nullable();
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
            'content' => '<p>Nội dung xem lại.</p>',
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

    /**
     * @return array<int, Schedule>
     */
    private function createScheduleSeries(): array
    {
        $seriesId = (string) Str::uuid();

        return collect([7, 14, 21])->map(function (int $days, int $index) use ($seriesId): Schedule {
            return Schedule::create([
                'series_id' => $seriesId,
                'series_position' => $index + 1,
                'class_id' => $this->classroom->id,
                'course_id' => $this->course->id,
                'schedule_date' => now()->addDays($days)->toDateString(),
                'start_time' => '12:00:00',
                'end_time' => '13:00:00',
                'status' => Schedule::STATUS_ACTIVE,
            ]);
        })->all();
    }
}
