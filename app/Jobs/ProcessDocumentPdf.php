<?php

namespace App\Jobs;

use App\Models\AiOperation;
use App\Services\DocumentProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessDocumentPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;
    public array $backoff = [30, 120, 300];

    public function __construct(public int $operationId, public string $path, public string $documentName, public int $courseId)
    {
        $this->onQueue('documents');
    }

    public function handle(DocumentProcessingService $service): void
    {
        $operation = AiOperation::findOrFail($this->operationId);
        $started = hrtime(true);
        $operation->update([
            'status' => AiOperation::STATUS_PROCESSING,
            'attempts' => $this->attempts(),
            'started_at' => now(),
            'error_message' => null,
        ]);

        $result = $service->processAndStorePdf(
            Storage::disk('local')->path($this->path),
            $this->documentName,
            $this->courseId
        );

        $operation->update([
            'status' => AiOperation::STATUS_COMPLETED,
            'result' => $result,
            'prompt_tokens' => (int) ($result['estimated_tokens'] ?? 0),
            'total_tokens' => (int) ($result['estimated_tokens'] ?? 0),
            'duration_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
            'completed_at' => now(),
        ]);

        Storage::disk('local')->delete($this->path);
    }

    public function failed(?Throwable $exception): void
    {
        AiOperation::whereKey($this->operationId)->update([
            'status' => AiOperation::STATUS_FAILED,
            'error_message' => mb_substr($exception?->getMessage() ?? 'Unknown document processing failure', 0, 4000),
            'failed_at' => now(),
        ]);
    }
}
