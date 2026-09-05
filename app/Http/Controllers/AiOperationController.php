<?php

namespace App\Http\Controllers;

use App\Models\AiOperation;
use Illuminate\Http\Request;

class AiOperationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $filters = [
            'status' => in_array($request->input('status'), [
                AiOperation::STATUS_QUEUED,
                AiOperation::STATUS_PROCESSING,
                AiOperation::STATUS_COMPLETED,
                AiOperation::STATUS_FAILED,
            ], true) ? $request->input('status') : null,
            'feature' => $request->filled('feature') ? (string) $request->input('feature') : null,
        ];

        $operations = AiOperation::with('subject')
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['feature'], fn ($query, $feature) => $query->where('feature', $feature))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $features = AiOperation::query()
            ->select('feature')
            ->distinct()
            ->orderBy('feature')
            ->pluck('feature');

        $since = now()->subDays(30);
        $summary = AiOperation::where('created_at', '>=', $since)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw("SUM(CASE WHEN status IN ('queued', 'processing') THEN 1 ELSE 0 END) as active")
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as total_tokens')
            ->selectRaw('COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost_usd')
            ->selectRaw("COALESCE(AVG(CASE WHEN status = 'completed' THEN duration_ms END), 0) as average_duration_ms")
            ->first();

        return view('system.ai-operations', compact('operations', 'summary', 'features', 'filters'));
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
            'progress' => [
                'stage' => data_get($operation->metadata, 'stage'),
                'completed_lessons' => (int) data_get($operation->metadata, 'completed_lessons', 0),
                'total_lessons' => (int) data_get($operation->metadata, 'total_lessons', 0),
                'current_lesson' => data_get($operation->metadata, 'current_lesson'),
                'percent' => (int) data_get($operation->metadata, 'progress_percent', 0),
            ],
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
