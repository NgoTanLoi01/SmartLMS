<?php

namespace App\Jobs;

use App\Models\AiOperation;
use App\Models\User;
use App\Services\LegacyLearningMaterialService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class SyncLegacyLearningMaterials implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public int $operationId,
        public int $userId,
        public bool $dryRun = false,
    ) {
        $this->onQueue('documents');
    }

    public function handle(LegacyLearningMaterialService $service): void
    {
        $operation = AiOperation::findOrFail($this->operationId);
        $user = User::find($this->userId);
        if (! $user) {
            throw new RuntimeException('Không tìm thấy người yêu cầu đồng bộ học liệu.');
        }

        $started = hrtime(true);
        $operation->update([
            'status' => AiOperation::STATUS_PROCESSING,
            'attempts' => $this->attempts(),
            'started_at' => now(),
            'error_message' => null,
        ]);

        $result = Cache::lock('learning-materials:legacy-sync', 660)
            ->block(10, fn () => $service->run($user, $this->dryRun));

        $operation->update([
            'status' => AiOperation::STATUS_COMPLETED,
            'result' => $result,
            'duration_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
            'completed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        AiOperation::whereKey($this->operationId)->update([
            'status' => AiOperation::STATUS_FAILED,
            'error_message' => mb_substr($exception?->getMessage() ?? 'Đồng bộ học liệu cũ thất bại.', 0, 4000),
            'failed_at' => now(),
        ]);
    }
}
