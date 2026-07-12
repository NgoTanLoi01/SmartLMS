<?php

namespace App\Http\Controllers;

use App\Models\AiOperation;

class AiOperationController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        $operations = AiOperation::with('subject')
            ->latest()
            ->paginate(30);
        $since = now()->subDays(30);
        $summary = AiOperation::where('created_at', '>=', $since)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw("SUM(CASE WHEN status IN ('queued', 'processing') THEN 1 ELSE 0 END) as active")
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as total_tokens')
            ->selectRaw('COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost_usd')
            ->first();

        return view('system.ai-operations', compact('operations', 'summary'));
    }

    public function show(string $uuid)
    {
        $operation = AiOperation::where('uuid', $uuid)->firstOrFail();
        abort_unless(auth()->user()->role === 'admin' || (int) $operation->user_id === (int) auth()->id(), 403);

        return response()->json([
            'id' => $operation->uuid,
            'feature' => $operation->feature,
            'status' => $operation->status,
            'result' => $operation->status === AiOperation::STATUS_COMPLETED ? $operation->result : null,
            'message' => $operation->status === AiOperation::STATUS_FAILED
                ? ($operation->error_message ?: 'Tác vụ xử lý thất bại.')
                : null,
            'usage' => [
                'prompt_tokens' => $operation->prompt_tokens,
                'completion_tokens' => $operation->completion_tokens,
                'total_tokens' => $operation->total_tokens,
                'estimated_cost_usd' => $operation->estimated_cost_usd,
                'duration_ms' => $operation->duration_ms,
            ],
        ]);
    }
}
