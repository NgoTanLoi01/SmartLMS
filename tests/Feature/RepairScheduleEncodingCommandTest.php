<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RepairScheduleEncodingCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->requireIsolatedSqliteDatabase();

        Schema::create('schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('room')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            Schema::dropIfExists('schedules');
        }

        parent::tearDown();
    }

    public function test_command_is_dry_run_by_default(): void
    {
        $id = $this->insertSchedule('PhÃ²ng mÃ¡y tÃ­nh 01', 'Thi káº¿t thÃºc mÃ´n');

        $this->artisan('smartlms:repair-schedule-encoding')
            ->expectsOutputToContain('Dry-run')
            ->assertSuccessful();

        $schedule = DB::table('schedules')->find($id);
        $this->assertSame('PhÃ²ng mÃ¡y tÃ­nh 01', $schedule->room);
        $this->assertSame('Thi káº¿t thÃºc mÃ´n', $schedule->note);
    }

    public function test_apply_repairs_room_and_note_preserves_valid_rows_and_is_idempotent(): void
    {
        $brokenId = $this->insertSchedule('PhÃ²ng mÃ¡y tÃ­nh 02', 'Thi káº¿t thÃºc mÃ´n');
        $validId = $this->insertSchedule('Phòng thực hành 03', 'Học bù');

        $this->artisan('smartlms:repair-schedule-encoding', ['--apply' => true])
            ->expectsOutputToContain('Đã sửa 1 lịch; bỏ qua 0 lịch')
            ->assertSuccessful();

        $this->assertSame('Phòng máy tính 02', DB::table('schedules')->find($brokenId)->room);
        $this->assertSame('Thi kết thúc môn', DB::table('schedules')->find($brokenId)->note);
        $this->assertSame('Phòng thực hành 03', DB::table('schedules')->find($validId)->room);

        $this->artisan('smartlms:repair-schedule-encoding', ['--apply' => true])
            ->expectsOutput('Không phát hiện dữ liệu lịch bị lỗi encoding.')
            ->assertSuccessful();
    }

    public function test_apply_repairs_only_the_broken_field_and_preserves_null_values(): void
    {
        $brokenRoomId = $this->insertSchedule('MÃ¡y tÃ­nh', null);
        $brokenNoteId = $this->insertSchedule(null, 'Thi káº¿t thÃºc mÃ´n');

        $this->artisan('smartlms:repair-schedule-encoding', ['--apply' => true])
            ->expectsOutputToContain('Đã sửa 2 lịch; bỏ qua 0 lịch')
            ->assertSuccessful();

        $this->assertSame('Máy tính', DB::table('schedules')->find($brokenRoomId)->room);
        $this->assertNull(DB::table('schedules')->find($brokenRoomId)->note);
        $this->assertNull(DB::table('schedules')->find($brokenNoteId)->room);
        $this->assertSame('Thi kết thúc môn', DB::table('schedules')->find($brokenNoteId)->note);
    }

    private function insertSchedule(?string $room, ?string $note): int
    {
        return DB::table('schedules')->insertGetId([
            'room' => $room,
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
