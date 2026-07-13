<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class AiOperation extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uuid', 'user_id', 'feature', 'provider', 'model', 'status',
        'subject_type', 'subject_id', 'metadata', 'result', 'error_message',
        'prompt_tokens', 'completion_tokens', 'total_tokens', 'estimated_cost_usd',
        'duration_ms', 'attempts', 'started_at', 'completed_at', 'failed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'result' => 'array',
        'estimated_cost_usd' => 'decimal:8',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $operation) {
            $operation->uuid ??= (string) Str::uuid();
        });
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function estimatedCost(array $usage): float
    {
        $input = (float) config('services.deepseek.input_cost_per_million', 0.14);
        $output = (float) config('services.deepseek.output_cost_per_million', 0.28);

        return round(
            (($usage['prompt_tokens'] ?? 0) * $input + ($usage['completion_tokens'] ?? 0) * $output) / 1_000_000,
            8
        );
    }
}
