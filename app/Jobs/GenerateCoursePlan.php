<?php

namespace App\Jobs;

use App\Models\AiOperation;
use App\Services\DeepSeekService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class GenerateCoursePlan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout;

    public array $backoff = [10, 30];

    public function __construct(public int $operationId, public array $payload)
    {
        $this->timeout = max(240, min(590, (int) config('ai.course_plan.job_timeout_seconds', 570)));
        $this->onQueue('ai');
    }

    public function handle(DeepSeekService $deepSeek): void
    {
        $operation = AiOperation::findOrFail($this->operationId);
        $startedAt = hrtime(true);
        $operation->update([
            'status' => AiOperation::STATUS_PROCESSING,
            'attempts' => $this->attempts(),
            'started_at' => $operation->started_at ?? now(),
            'error_message' => null,
        ]);

        $checkpoint = data_get($operation->result, '_checkpoint', []);
        $result = $deepSeek->generateCoursePlan(
            $this->payload,
            is_array($checkpoint) ? $checkpoint : [],
            function (array $savedCheckpoint, array $progress) use ($operation): void {
                $completed = max(0, (int) ($progress['completed_lessons'] ?? 0));
                $total = max(1, (int) ($progress['total_lessons'] ?? 1));
                $usage = $savedCheckpoint['usage'] ?? [];
                $metadata = array_merge($operation->metadata ?? [], $progress, [
                    'progress_percent' => min(99, (int) floor($completed / $total * 100)),
                    'progress_updated_at' => now()->toIso8601String(),
                ]);

                $operation->update([
                    'result' => ['_checkpoint' => $savedCheckpoint],
                    'metadata' => $metadata,
                    'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
                    'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
                    'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
                    'estimated_cost_usd' => $operation->estimatedCost($usage),
                ]);
            },
        );
        if (! ($result['success'] ?? false)) {
            $exception = new RuntimeException($result['message'] ?? 'AI chưa tạo được kế hoạch khóa học.');

            if (! ($result['retryable'] ?? true)) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }

        $usage = $result['_usage'] ?? [];
        $operation->update([
            'status' => AiOperation::STATUS_COMPLETED,
            'result' => ['success' => true, 'plan' => $result['plan']],
            'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
            'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
            'estimated_cost_usd' => $operation->estimatedCost($usage),
            'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            'completed_at' => now(),
            'failed_at' => null,
            'metadata' => array_merge($operation->metadata ?? [], [
                'stage' => 'completed',
                'completed_lessons' => (int) data_get($this->payload, 'requirements.session_count', 0),
                'progress_percent' => 100,
                'progress_updated_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        AiOperation::whereKey($this->operationId)->update([
            'status' => AiOperation::STATUS_FAILED,
            'attempts' => max(1, $this->attempts()),
            'error_message' => mb_substr(
                $exception?->getMessage() ?: 'AI thiết kế khóa học gặp lỗi. Vui lòng thử lại.',
                0,
                4000,
            ),
            'failed_at' => now(),
        ]);
    }
}
