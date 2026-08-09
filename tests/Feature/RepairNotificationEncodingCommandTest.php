<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RepairNotificationEncodingCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->requireIsolatedSqliteDatabase();

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
        });
    }

    protected function tearDown(): void
    {
        if ($this->usesIsolatedSqliteDatabase()) {
            Schema::dropIfExists('smart_notifications');
        }

        parent::tearDown();
    }

    public function test_command_is_dry_run_by_default(): void
    {
        $id = $this->insertNotification(
            'BÃ i táº­p Ä‘Ã£ Ä‘Æ°á»£c cháº¥m',
            'BÃ i lÃ m cá»§a báº¡n Ä‘Ã£ Ä‘Æ°á»£c cháº¥m.'
        );

        $this->artisan('smartlms:repair-notification-encoding')
            ->expectsOutputToContain('Dry-run')
            ->assertSuccessful();

        $this->assertSame('BÃ i táº­p Ä‘Ã£ Ä‘Æ°á»£c cháº¥m', DB::table('smart_notifications')->find($id)->title);
    }

    public function test_apply_repairs_title_and_message_preserves_valid_rows_and_is_idempotent(): void
    {
        $brokenId = $this->insertNotification(
            'Cáº£nh bÃ¡o chuyÃªn cáº§n',
            'Báº¡n Ä‘Ã£ cÃ³ nhiá»u buá»•i váº¯ng.'
        );
        $validId = $this->insertNotification('Có bài học mới', 'Nội dung bài học đã sẵn sàng.');

        $this->artisan('smartlms:repair-notification-encoding', ['--apply' => true])
            ->expectsOutputToContain('Đã sửa 1 thông báo; bỏ qua 0 thông báo')
            ->assertSuccessful();

        $this->assertSame('Cảnh báo chuyên cần', DB::table('smart_notifications')->find($brokenId)->title);
        $this->assertSame('Bạn đã có nhiều buổi vắng.', DB::table('smart_notifications')->find($brokenId)->message);
        $this->assertSame('Có bài học mới', DB::table('smart_notifications')->find($validId)->title);

        $this->artisan('smartlms:repair-notification-encoding', ['--apply' => true])
            ->expectsOutput('Không phát hiện dữ liệu thông báo bị lỗi encoding.')
            ->assertSuccessful();
    }

    private function insertNotification(string $title, string $message): int
    {
        return DB::table('smart_notifications')->insertGetId([
            'user_id' => 1,
            'type' => 'test',
            'title' => $title,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
