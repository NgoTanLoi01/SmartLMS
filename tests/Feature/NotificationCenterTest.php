<?php

namespace Tests\Feature;

use App\Models\SmartNotification;
use App\Models\User;
use App\Services\NotificationCenter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default(User::ROLE_STUDENT);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('smart_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
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

    protected function tearDown(): void
    {
        Schema::dropIfExists('smart_notifications');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_dedupe_key_prevents_duplicate_notifications(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $center = app(NotificationCenter::class);

        $center->notifyUser($user, 'lesson', 'Bài học mới', 'Nội dung', '/courses/1', [], 'lesson:1:published');
        $center->notifyUser($user, 'lesson', 'Bài học mới', 'Nội dung', '/courses/1', [], 'lesson:1:published');

        $this->assertDatabaseCount('smart_notifications', 1);
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $other = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $notification = SmartNotification::create([
            'user_id' => $owner->id,
            'type' => 'grade',
            'title' => 'Có điểm mới',
            'message' => 'Bài làm đã được chấm.',
        ]);

        $this->actingAs($other)
            ->get(route('notifications.open', $notification))
            ->assertForbidden();
    }

    public function test_read_all_only_marks_current_users_notifications(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $other = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $mine = SmartNotification::create(['user_id' => $user->id, 'type' => 'quiz', 'title' => 'Quiz', 'message' => 'Mới']);
        $theirs = SmartNotification::create(['user_id' => $other->id, 'type' => 'quiz', 'title' => 'Quiz', 'message' => 'Mới']);

        $this->actingAs($user)->patch(route('notifications.read-all'))->assertRedirect();

        $this->assertNotNull($mine->fresh()->read_at);
        $this->assertNull($theirs->fresh()->read_at);
    }
}
