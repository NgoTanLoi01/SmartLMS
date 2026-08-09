<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RepairAttendanceEncodingCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireIsolatedSqliteDatabase();

        Schema::create('attendance_columns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('name');
            $table->string('type')->default('attendance');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('attendance_data', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attendance_column_id');
            $table->unsignedBigInteger('user_id');
            $table->string('value')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            Schema::dropIfExists('attendance_data');
            Schema::dropIfExists('attendance_columns');
        }

        parent::tearDown();
    }

    public function test_command_is_dry_run_by_default(): void
    {
        $id = $this->insertColumn('Ghi chÃº');

        $this->artisan('smartlms:repair-attendance-encoding')
            ->expectsOutputToContain('Dry-run')
            ->assertSuccessful();

        $this->assertSame('Ghi chÃº', DB::table('attendance_columns')->find($id)->name);
    }

    public function test_apply_repairs_only_double_encoded_values_and_is_idempotent(): void
    {
        $brokenNoteId = $this->insertColumn('Ghi chÃº');
        $brokenSessionId = $this->insertColumn('Chiá»u 11/7');
        $validId = $this->insertColumn('Sáng 12/7');
        $validWithMarkerId = $this->insertColumn('Âm nhạc');

        $this->artisan('smartlms:repair-attendance-encoding', ['--apply' => true])
            ->expectsOutputToContain('Đã sửa 2 bản ghi; bỏ qua 0 bản ghi')
            ->assertSuccessful();

        $this->assertSame('Ghi chú', DB::table('attendance_columns')->find($brokenNoteId)->name);
        $this->assertSame('Chiều 11/7', DB::table('attendance_columns')->find($brokenSessionId)->name);
        $this->assertSame('Sáng 12/7', DB::table('attendance_columns')->find($validId)->name);
        $this->assertSame('Âm nhạc', DB::table('attendance_columns')->find($validWithMarkerId)->name);

        $this->artisan('smartlms:repair-attendance-encoding', ['--apply' => true])
            ->expectsOutput('Không phát hiện dữ liệu điểm danh bị lỗi encoding.')
            ->assertSuccessful();
    }

    public function test_apply_repairs_double_encoded_values_in_note_grade_and_attendance_cells(): void
    {
        $noteColumnId = $this->insertColumn('Ghi chú', 'note');
        $gradeColumnId = $this->insertColumn('Nhận xét', 'grade');
        $attendanceColumnId = $this->insertColumn('Buổi 1');
        $brokenNoteId = $this->insertValue($noteColumnId, 'Nghá»‰ luÃ´n');
        $gradeValueId = $this->insertValue($gradeColumnId, 'Nghá»‰ luÃ´n');
        $attendanceValueId = $this->insertValue($attendanceColumnId, 'váº¯ng');

        $this->artisan('smartlms:repair-attendance-encoding')
            ->expectsOutputToContain('Dry-run')
            ->assertSuccessful();
        $this->assertSame('Nghá»‰ luÃ´n', DB::table('attendance_data')->find($brokenNoteId)->value);

        $this->artisan('smartlms:repair-attendance-encoding', ['--apply' => true])
            ->expectsOutputToContain('Đã sửa 3 bản ghi; bỏ qua 0 bản ghi')
            ->assertSuccessful();

        $this->assertSame('Nghỉ luôn', DB::table('attendance_data')->find($brokenNoteId)->value);
        $this->assertSame('Nghỉ luôn', DB::table('attendance_data')->find($gradeValueId)->value);
        $this->assertSame('vắng', DB::table('attendance_data')->find($attendanceValueId)->value);
    }

    private function insertColumn(string $name, string $type = 'attendance'): int
    {
        return DB::table('attendance_columns')->insertGetId([
            'course_id' => 1,
            'name' => $name,
            'type' => $type,
            'order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertValue(int $columnId, string $value): int
    {
        return DB::table('attendance_data')->insertGetId([
            'attendance_column_id' => $columnId,
            'user_id' => 1,
            'value' => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
