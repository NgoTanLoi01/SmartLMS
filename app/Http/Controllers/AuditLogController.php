<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditFailureReporter;
use App\Services\AuditIntegrityService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $integrityEnabled = app(AuditIntegrityService::class)->supportsIntegrityChain();
        $filters = $this->filters($request);
        $filteredLogs = $this->filteredQuery($filters, $integrityEnabled);
        $actorColumn = $integrityEnabled ? 'actor_id' : 'user_id';

        $stats = (clone $filteredLogs)
            ->selectRaw(
                "COUNT(*) as total, COUNT(DISTINCT action) as action_count, COUNT(DISTINCT {$actorColumn}) as actor_count, SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as today_count",
                [now()->startOfDay()]
            )
            ->first();

        $logs = (clone $filteredLogs)
            ->with('user')
            ->latest($integrityEnabled ? 'chain_position' : 'created_at')
            ->paginate(20)
            ->withQueryString();

        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $users = User::query()
            ->whereIn('id', AuditLog::query()
                ->select($integrityEnabled ? 'actor_id' : 'user_id')
                ->whereNotNull($integrityEnabled ? 'actor_id' : 'user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $archiveStats = [
            'active' => $integrityEnabled ? AuditLog::query()->active()->count() : AuditLog::query()->count(),
            'archived' => $integrityEnabled ? AuditLog::query()->archived()->count() : 0,
            'retention_days' => max(1, (int) config('audit.retention_days', 365)),
        ];

        return view('audit_logs.index', compact('logs', 'actions', 'users', 'filters', 'stats', 'archiveStats'));
    }

    public function verify(
        Request $request,
        AuditIntegrityService $integrity,
        AuditFailureReporter $reporter
    ) {
        abort_unless($request->user()?->isAdmin(), 403);

        $result = $integrity->verify();
        AuditLogger::log(
            $result['valid'] ? AuditLogger::AUDIT_INTEGRITY_VERIFIED : AuditLogger::AUDIT_INTEGRITY_FAILED,
            null,
            null,
            null,
            [
                'checked_count' => $result['checked'],
                'failed_id' => $result['failed_id'],
                'last_hash' => $result['last_hash'],
            ],
            $result['message']
        );

        if (! $result['valid']) {
            $reporter->report('audit_integrity_verification', $result['message']);
        }

        return back()->with($result['valid'] ? 'success' : 'error', $result['message']);
    }

    private function filters(Request $request): array
    {
        return [
            'action' => $request->input('action'),
            'user_id' => $request->input('user_id'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'storage' => in_array($request->input('storage'), ['active', 'archived', 'all'], true)
                ? $request->input('storage')
                : 'active',
        ];
    }

    private function filteredQuery(array $filters, bool $integrityEnabled = true)
    {
        return AuditLog::query()
            ->when($filters['action'], fn ($query, $action) => $query->where('action', $action))
            ->when($filters['user_id'], fn ($query, $userId) => $query->where($integrityEnabled ? 'actor_id' : 'user_id', $userId))
            ->when($filters['from_date'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to_date'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($integrityEnabled && $filters['storage'] === 'active', fn ($query) => $query->active())
            ->when($integrityEnabled && $filters['storage'] === 'archived', fn ($query) => $query->archived());
    }
}
