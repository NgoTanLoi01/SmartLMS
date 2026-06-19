<?php

namespace App\Http\Controllers;

use App\Imports\TeachingContractsImport;
use App\Models\TeachingContract;
use App\Models\TeachingRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class TeachingContractController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $user = auth()->user();
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => $request->input('status'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ];

        $query = TeachingContract::with(['teacher', 'teachingRecords'])
            ->withCount('teachingRecords')
            ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
            ->when($filters['search'], function ($q, $keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('contract_number', 'like', "%{$keyword}%")
                        ->orWhere('evidence_url', 'like', "%{$keyword}%")
                        ->orWhere('note', 'like', "%{$keyword}%")
                        ->orWhereHas('teachingRecords', function ($recordQuery) use ($keyword) {
                            $recordQuery->where('subject_name', 'like', "%{$keyword}%")
                                ->orWhere('class_name', 'like', "%{$keyword}%")
                                ->orWhere('center_name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when(
                $filters['status'],
                fn ($q, $status) => $q->where('status', $status),
                fn ($q) => $q->notArchived()
            )
            ->when($filters['from_date'], fn ($q, $date) => $q->whereDate('signed_date', '>=', $date))
            ->when($filters['to_date'], fn ($q, $date) => $q->whereDate('signed_date', '<=', $date));

        $contracts = $query
            ->orderByRaw('COALESCE(signed_date, created_at) DESC')
            ->paginate(15)
            ->withQueryString();

        $scopeQuery = TeachingContract::query()
            ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
            ->notArchived();

        $totalAmount = (float) (clone $scopeQuery)->sum('total_amount');
        $receivedAmount = (float) (clone $scopeQuery)->sum('received_amount');

        $stats = [
            'total_contracts' => (clone $scopeQuery)->count(),
            'total_amount' => $totalAmount,
            'received_amount' => $receivedAmount,
            'remaining_amount' => max(0, $totalAmount - $receivedAmount),
        ];

        $teachers = User::where('role', 'teacher')->orderBy('name')->get();
        $teachingRecords = TeachingRecord::query()
            ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
            ->orderByRaw('COALESCE(start_date, created_at) DESC')
            ->get();

        return view('payments.index', compact(
            'contracts',
            'stats',
            'filters',
            'teachers',
            'teachingRecords'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $data = $this->validatedData($request);
        $data['teacher_id'] = auth()->user()->role === 'admin'
            ? (int) $data['teacher_id']
            : auth()->id();

        $recordIds = $this->allowedRecordIds($request, $data['teacher_id']);

        DB::transaction(function () use ($data, $recordIds) {
            $contract = TeachingContract::create($this->normalizeAmounts($data));
            $contract->teachingRecords()->sync($recordIds);
        });

        return back()->with('success', 'Đã thêm hợp đồng thanh toán.');
    }

    public function update(Request $request, TeachingContract $payment)
    {
        $this->authorizeContract($payment);

        $data = $this->validatedData($request, $payment->id);
        $data['teacher_id'] = auth()->user()->role === 'admin'
            ? (int) $data['teacher_id']
            : $payment->teacher_id;

        $recordIds = $this->allowedRecordIds($request, $data['teacher_id']);

        DB::transaction(function () use ($payment, $data, $recordIds) {
            $payment->update($this->normalizeAmounts($data));
            $payment->teachingRecords()->sync($recordIds);
        });

        return back()->with('success', 'Đã cập nhật hợp đồng thanh toán.');
    }

    public function destroy(TeachingContract $payment)
    {
        $this->authorizeContract($payment);
        $payment->update(['status' => TeachingContract::STATUS_ARCHIVED]);

        return back()->with('success', 'Đã lưu trữ hợp đồng thanh toán. Dữ liệu liên kết vẫn được giữ lại.');
    }

    public function import(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'teacher_id' => auth()->user()->role === 'admin' ? 'required|exists:users,id' : 'nullable',
        ]);

        $teacherId = auth()->user()->role === 'admin'
            ? (int) $validated['teacher_id']
            : auth()->id();

        $import = new TeachingContractsImport($teacherId);
        Excel::import($import, $request->file('file'));

        if (!empty($import->missingHeaders)) {
            return back()->with('error', 'File thiếu cột bắt buộc: ' . implode(', ', $import->missingHeaders) . '.');
        }

        $message = "Đã nhập {$import->importedCount} hợp đồng.";
        if ($import->updatedCount > 0) {
            $message .= " Cập nhật {$import->updatedCount} hợp đồng đã có.";
        }
        if ($import->invalidCount > 0) {
            $message .= " Bỏ qua {$import->invalidCount} dòng không hợp lệ.";
        }

        return back()->with($import->importedCount > 0 || $import->updatedCount > 0 ? 'success' : 'error', $message);
    }

    private function validatedData(Request $request, ?int $contractId = null): array
    {
        $teacherRule = auth()->user()->role === 'admin' ? 'required|exists:users,id' : 'nullable';

        return $request->validate([
            'teacher_id' => $teacherRule,
            'contract_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('teaching_contracts', 'contract_number')->ignore($contractId),
            ],
            'signed_date' => 'nullable|date',
            'total_amount' => 'required|numeric|min:0|max:999999999999',
            'received_amount' => 'nullable|numeric|min:0|max:999999999999|lte:total_amount',
            'status' => 'required|in:' . implode(',', array_keys(TeachingContract::statuses())),
            'received_date' => 'nullable|date',
            'evidence_url' => 'nullable|url|max:2000',
            'teaching_record_ids' => 'nullable|array',
            'teaching_record_ids.*' => 'exists:teaching_records,id',
            'note' => 'nullable|string|max:2000',
        ]);
    }

    private function normalizeAmounts(array $data): array
    {
        $data['total_amount'] = (float) $data['total_amount'];
        $data['received_amount'] = (float) ($data['received_amount'] ?? 0);

        if ($data['status'] === TeachingContract::STATUS_RECEIVED) {
            $data['received_amount'] = $data['total_amount'];
        }

        if ($data['status'] === TeachingContract::STATUS_UNPAID) {
            $data['received_amount'] = 0;
            $data['received_date'] = null;
        }

        if ($data['status'] === TeachingContract::STATUS_CANCELLED) {
            $data['received_amount'] = 0;
        }

        return $data;
    }

    private function allowedRecordIds(Request $request, int $teacherId): array
    {
        $ids = collect($request->input('teaching_record_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return TeachingRecord::query()
            ->whereIn('id', $ids)
            ->where('teacher_id', $teacherId)
            ->pluck('id')
            ->all();
    }

    private function authorizeAccess(): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'teacher'], true), 403);
    }

    private function authorizeContract(TeachingContract $contract): void
    {
        $this->authorizeAccess();
        abort_if(auth()->user()->role === 'teacher' && $contract->teacher_id !== auth()->id(), 403);
    }
}
