<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QueueHealthCommand extends Command
{
    protected $signature = 'smartlms:queue-health
        {--connection= : Queue connection cần kiểm tra}
        {--json : Xuất JSON cho monitoring collector}';

    protected $description = 'Đo queue depth và độ trễ của job cũ nhất theo từng worker pool';

    public function handle(): int
    {
        $connectionName = (string) ($this->option('connection') ?: config('queue.default'));
        $driver = (string) config("queue.connections.{$connectionName}.driver", $connectionName);

        try {
            $connection = Queue::connection($connectionName);
            $metrics = collect(['default', 'notifications', 'ai', 'documents'])
                ->map(function (string $queue) use ($connection, $connectionName, $driver): array {
                    $oldestAt = $this->oldestPendingAt($connection, $connectionName, $driver, $queue);

                    return [
                        'queue' => $queue,
                        'depth' => $connection->size($queue),
                        'oldest_pending_at' => $oldestAt,
                        'lag_seconds' => $oldestAt ? max(now()->timestamp - $oldestAt, 0) : 0,
                    ];
                })
                ->all();
        } catch (Throwable $exception) {
            $this->error("Queue {$connectionName} không khả dụng: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $payload = [
            'ok' => true,
            'connection' => $connectionName,
            'driver' => $driver,
            'checked_at' => now()->toIso8601String(),
            'queues' => $metrics,
            'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null,
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info("Queue connection: {$connectionName} ({$driver})");
        $this->table(
            ['Queue', 'Depth', 'Lag (giây)', 'Job cũ nhất'],
            array_map(fn (array $metric): array => [
                $metric['queue'],
                $metric['depth'],
                $metric['lag_seconds'],
                $metric['oldest_pending_at'] ? date(DATE_ATOM, $metric['oldest_pending_at']) : '—',
            ], $metrics),
        );
        $this->line('Failed jobs: '.($payload['failed_jobs'] ?? 'không có bảng'));

        return self::SUCCESS;
    }

    private function oldestPendingAt(object $connection, string $connectionName, string $driver, string $queue): ?int
    {
        if ($driver === 'redis' && method_exists($connection, 'creationTimeOfOldestPendingJob')) {
            return $connection->creationTimeOfOldestPendingJob($queue);
        }

        if ($driver === 'database') {
            $table = (string) config("queue.connections.{$connectionName}.table", 'jobs');
            if (! Schema::hasTable($table)) {
                return null;
            }

            $createdAt = DB::connection(config("queue.connections.{$connectionName}.connection"))
                ->table($table)
                ->where('queue', $queue)
                ->min('created_at');

            return $createdAt ? (int) $createdAt : null;
        }

        return null;
    }
}
