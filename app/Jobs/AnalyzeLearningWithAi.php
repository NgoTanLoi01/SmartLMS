<?php

namespace App\Jobs;

use App\Models\AiOperation;
use App\Services\DeepSeekService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AnalyzeLearningWithAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 150;

    public array $backoff = [10, 30, 90];

    public function __construct(public int $operationId, public array $payload)
    {
        $this->onQueue('ai');
    }

    public function handle(DeepSeekService $deepSeek): void
    {
        $operation = AiOperation::findOrFail($this->operationId);
        $started = hrtime(true);
        $operation->update([
            'status' => AiOperation::STATUS_PROCESSING,
            'attempts' => $this->attempts(),
            'started_at' => now(),
            'error_message' => null,
        ]);

        $result = $deepSeek->analyzeLearning($this->payload);
        if (! ($result['success'] ?? false)) {
            throw new \RuntimeException($result['message'] ?? 'AI learning analysis failed.');
        }

        $usage = $result['_usage'] ?? [];
        unset($result['_usage']);
        $operation->update([
            'status' => AiOperation::STATUS_COMPLETED,
            'result' => $result,
            'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
            'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
            'estimated_cost_usd' => $operation->estimatedCost($usage),
            'duration_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
            'completed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        AiOperation::whereKey($this->operationId)->update([
            'status' => AiOperation::STATUS_FAILED,
            'error_message' => mb_substr($exception?->getMessage() ?? 'Unknown queue failure', 0, 4000),
            'failed_at' => now(),
        ]);
    }
}
