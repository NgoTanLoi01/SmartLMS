<?php

namespace App\Http\Controllers;

use App\Models\TeachingContract;
use App\Models\TeachingRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OperationalDashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $user = auth()->user();
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $month = min(12, max(1, $month));
        $year = min(2100, max(2020, $year));

        $filters = [
            'month' => $month,
            'year' => $year,
            'teacher_id' => $request->input('teacher_id'),
        ];

        $teacherId = $user->role === 'admin' && $filters['teacher_id']
            ? (int) $filters['teacher_id']
            : ($user->role === 'teacher' ? $user->id : null);

        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = (clone $periodStart)->endOfMonth()->endOfDay();

        $teachingBaseQuery = TeachingRecord::query()
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->notArchived();

        $teachingMonthQuery = (clone $teachingBaseQuery)
            ->whereBetween('start_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);

        $contractBaseQuery = TeachingContract::query()
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->notArchived();

        $receivedMonthQuery = (clone $contractBaseQuery)
            ->whereIn('status', [TeachingContract::STATUS_RECEIVED, TeachingContract::STATUS_PARTIAL])
            ->whereBetween('received_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);

        $pendingContracts = (clone $contractBaseQuery)
            ->whereColumn('received_amount', '<', 'total_amount')
            ->orderByRaw('COALESCE(signed_date, created_at) DESC')
            ->get();

        $centerRows = (clone $teachingMonthQuery)
            ->selectRaw("COALESCE(NULLIF(center_name, ''), 'Chưa cập nhật') as label")
            ->selectRaw('COUNT(*) as subjects_count')
            ->selectRaw("COUNT(DISTINCT COALESCE(NULLIF(class_name, ''), CONCAT('record-', id))) as classes_count")
            ->selectRaw('COALESCE(SUM(planned_sessions), 0) as sessions_count')
            ->groupBy('label')
            ->orderByDesc('classes_count')
            ->orderByDesc('sessions_count')
            ->limit(6)
            ->get();

        $recentTeachingRecords = (clone $teachingMonthQuery)
            ->with('teacher')
            ->orderByRaw('COALESCE(start_date, created_at) DESC')
            ->limit(8)
            ->get();

        $recentReceivedContracts = (clone $receivedMonthQuery)
            ->with('teacher')
            ->orderByRaw('COALESCE(received_date, signed_date, created_at) DESC')
            ->limit(8)
            ->get();

        $topCenter = $centerRows->first();
        $totalAmount = (float) (clone $contractBaseQuery)->sum('total_amount');
        $receivedAmount = (float) (clone $contractBaseQuery)->sum('received_amount');

        $stats = [
            'month_sessions' => (int) (clone $teachingMonthQuery)->sum('planned_sessions'),
            'month_subjects' => (clone $teachingMonthQuery)->count(),
            'active_subjects' => (clone $teachingBaseQuery)->where('status', TeachingRecord::STATUS_TEACHING)->count(),
            'received_contracts' => (clone $receivedMonthQuery)->where('status', TeachingContract::STATUS_RECEIVED)->count(),
            'received_amount' => (float) (clone $receivedMonthQuery)->sum('received_amount'),
            'pending_contracts' => $pendingContracts->count(),
            'pending_amount' => max(0, $totalAmount - $receivedAmount),
            'top_center_name' => $topCenter?->label,
            'top_center_classes' => (int) ($topCenter?->classes_count ?? 0),
        ];

        $teachers = User::where('role', 'teacher')->orderBy('name')->get();
        $monthOptions = $this->monthOptions();

        return view('operations.dashboard', compact(
            'filters',
            'periodStart',
            'periodEnd',
            'stats',
            'centerRows',
            'pendingContracts',
            'recentTeachingRecords',
            'recentReceivedContracts',
            'teachers',
            'monthOptions'
        ));
    }

    private function monthOptions(): array
    {
        return [
            1 => 'Tháng 1',
            2 => 'Tháng 2',
            3 => 'Tháng 3',
            4 => 'Tháng 4',
            5 => 'Tháng 5',
            6 => 'Tháng 6',
            7 => 'Tháng 7',
            8 => 'Tháng 8',
            9 => 'Tháng 9',
            10 => 'Tháng 10',
            11 => 'Tháng 11',
            12 => 'Tháng 12',
        ];
    }

    private function authorizeAccess(): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'teacher'], true), 403);
    }
}
