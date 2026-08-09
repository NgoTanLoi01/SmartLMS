<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QueueHealthCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('QueueHealthCommandTest chỉ được phép chạy trên SQLite cô lập.');
        }

        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('jobs');
        parent::tearDown();
    }

    public function test_queue_health_reports_depth_and_lag_for_each_worker_pool(): void
    {
        DB::table('jobs')->insert([
            'queue' => 'notifications',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->subSeconds(30)->timestamp,
        ]);
        $this->assertSame(1, Queue::connection('database')->size('notifications'));

        $this->assertSame(0, Artisan::call('smartlms:queue-health', [
            '--connection' => 'database',
            '--json' => true,
        ]));
        $output = Artisan::output();
        $this->assertStringContainsString('"connection":"database"', $output);
        $this->assertStringContainsString('"queue":"notifications"', $output);
    }
}
