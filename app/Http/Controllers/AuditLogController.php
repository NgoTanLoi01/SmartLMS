<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $filters = $this->filters($request);

        $logs = $this->filteredQuery($filters)
            ->with('user')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $users = User::query()
            ->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('audit_logs.index', compact('logs', 'actions', 'users', 'filters'));
    }

    public function destroy(Request $request, AuditLog $auditLog)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $auditLog->delete();

        return back()->with('success', 'Đã xóa audit log.');
    }

    public function bulkDestroy(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $filters = $this->filters($request);
        $deletedCount = $this->filteredQuery($filters)->delete();

        return redirect()
            ->route('audit-logs.index')
            ->with('success', "Đã xóa {$deletedCount} audit log.");
    }

    private function filters(Request $request): array
    {
        return [
            'action' => $request->input('action'),
            'user_id' => $request->input('user_id'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ];
    }

    private function filteredQuery(array $filters)
    {
        return AuditLog::query()
            ->when($filters['action'], fn ($query, $action) => $query->where('action', $action))
            ->when($filters['user_id'], fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['from_date'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to_date'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
    }
}
