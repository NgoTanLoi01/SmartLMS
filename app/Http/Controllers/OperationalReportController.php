<?php

namespace App\Http\Controllers;

use App\Exports\OperationalReportExport;
use App\Models\TeachingContract;
use App\Models\TeachingRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class OperationalReportController extends Controller
{
    private const REPORT_TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function index(Request $request)
    {
        $this->authorizeAccess();

        $report = $this->buildReport($request);

        return view('reports.operations', $report);
    }

    public function exportExcel(Request $request)
    {
        $this->authorizeAccess();

        $report = $this->buildReport($request);
        $filename = 'Bao_cao_giang_day_thanh_toan_' . now(self::REPORT_TIMEZONE)->format('Ymd_His') . '.xlsx';

        return Excel::download(new OperationalReportExport($report), $filename);
    }

    public function print(Request $request)
    {
        $this->authorizeAccess();

        $report = $this->buildReport($request);

        return view('reports.operations-print', $report);
    }

    private function buildReport(Request $request): array
    {
        $user = auth()->user();
        $filters = [
            'center_name' => $request->input('center_name'),
            'term_code' => $request->input('term_code'),
            'month' => $request->input('month'),
            'year' => $request->input('year', now(self::REPORT_TIMEZONE)->year),
            'teacher_id' => $request->input('teacher_id'),
        ];
        $filters['month'] = $filters['month'] ? (int) $filters['month'] : null;
        $filters['year'] = $filters['year'] ? (int) $filters['year'] : null;

        $teacherId = $user->role === 'admin' && $filters['teacher_id']
            ? (int) $filters['teacher_id']
            : ($user->role === 'teacher' ? $user->id : null);

        $teachingQuery = TeachingRecord::with('teacher')
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($filters['center_name'], fn ($q, $center) => $q->where('center_name', $center))
            ->when($filters['term_code'], fn ($q, $term) => $q->where('term_code', $term));

        $this->applyDateFilters($teachingQuery, 'start_date', $filters);

        $teachingRecords = $teachingQuery
            ->orderByRaw('COALESCE(start_date, created_at) DESC')
            ->get();

        $contractQuery = TeachingContract::with(['teacher', 'teachingRecords'])
            ->notArchived()
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($filters['center_name'], function ($q, $center) {
                $q->whereHas('teachingRecords', fn ($recordQuery) => $recordQuery->where('center_name', $center));
            })
            ->when($filters['term_code'], function ($q, $term) {
                $q->whereHas('teachingRecords', fn ($recordQuery) => $recordQuery->where('term_code', $term));
            });

        $this->applyContractPeriodFilters($contractQuery, $filters);

        $contracts = $contractQuery
            ->orderByRaw('COALESCE(received_date, signed_date, created_at) DESC')
            ->get();

        $summary = [
            'subjects_count' => $teachingRecords->count(),
            'completed_subjects_count' => $teachingRecords->where('status', TeachingRecord::STATUS_COMPLETED)->count(),
            'total_sessions' => $teachingRecords->sum('planned_sessions'),
            'total_contract_amount' => (float) $contracts->sum('total_amount'),
            'received_amount' => (float) $contracts->sum('received_amount'),
            'remaining_amount' => max(0, (float) $contracts->sum('total_amount') - (float) $contracts->sum('received_amount')),
        ];

        $byCenter = $teachingRecords
            ->groupBy(fn ($record) => $record->center_name ?: 'Chưa cập nhật')
            ->map(fn ($items, $center) => $this->groupRow($center, $items, $contracts, 'center_name'))
            ->values();

        $byTerm = $teachingRecords
            ->groupBy(fn ($record) => $record->term_code ?: 'Chưa cập nhật')
            ->map(fn ($items, $term) => $this->groupRow($term, $items, $contracts, 'term_code'))
            ->values();

        $centers = TeachingRecord::query()
            ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
            ->whereNotNull('center_name')
            ->where('center_name', '!=', '')
            ->distinct()
            ->orderBy('center_name')
            ->pluck('center_name');

        $terms = TeachingRecord::query()
            ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
            ->whereNotNull('term_code')
            ->where('term_code', '!=', '')
            ->distinct()
            ->orderBy('term_code')
            ->pluck('term_code');

        $teachers = User::where('role', 'teacher')->orderBy('name')->get();
        $periodLabel = $this->periodLabel($filters);
        $generatedAt = now(self::REPORT_TIMEZONE);
        $logoDataUri = $this->logoDataUri();

        return compact(
            'filters',
            'summary',
            'byCenter',
            'byTerm',
            'teachingRecords',
            'contracts',
            'centers',
            'terms',
            'teachers',
            'periodLabel',
            'generatedAt',
            'logoDataUri'
        );
    }

    private function applyDateFilters(Builder $query, string $column, array $filters): void
    {
        if ($filters['year']) {
            $query->whereYear($column, (int) $filters['year']);
        }

        if ($filters['month']) {
            $query->whereMonth($column, (int) $filters['month']);
        }
    }

    private function applyContractPeriodFilters(Builder $query, array $filters): void
    {
        if (!$filters['year'] && !$filters['month']) {
            return;
        }

        $query->where(function ($periodQuery) use ($filters) {
            $periodQuery
                ->where(function ($receivedQuery) use ($filters) {
                    $receivedQuery->whereNotNull('received_date');
                    $this->applyDateFilters($receivedQuery, 'received_date', $filters);
                })
                ->orWhere(function ($signedQuery) use ($filters) {
                    $signedQuery->whereNull('received_date');
                    $this->applyDateFilters($signedQuery, 'signed_date', $filters);
                });
        });
    }

    private function periodLabel(array $filters): string
    {
        if ($filters['month'] && $filters['year']) {
            return 'Tháng ' . $filters['month'] . '/' . $filters['year'];
        }

        if ($filters['year']) {
            return 'Năm ' . $filters['year'];
        }

        return 'Tất cả thời gian';
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('smartlms-logo-sharpened.png');

        if (!file_exists($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
    }

    private function groupRow(string $label, $teachingRecords, $contracts, string $field): array
    {
        $matchedContracts = $contracts->filter(function ($contract) use ($field, $label) {
            return $contract->teachingRecords->contains(function ($record) use ($field, $label) {
                $value = $record->{$field} ?: 'Chưa cập nhật';

                return $value === $label;
            });
        });

        return [
            'label' => $label,
            'subjects_count' => $teachingRecords->count(),
            'completed_subjects_count' => $teachingRecords->where('status', TeachingRecord::STATUS_COMPLETED)->count(),
            'total_sessions' => $teachingRecords->sum('planned_sessions'),
            'total_contract_amount' => (float) $matchedContracts->sum('total_amount'),
            'received_amount' => (float) $matchedContracts->sum('received_amount'),
            'remaining_amount' => max(0, (float) $matchedContracts->sum('total_amount') - (float) $matchedContracts->sum('received_amount')),
        ];
    }

    private function authorizeAccess(): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'teacher'], true), 403);
    }
}
