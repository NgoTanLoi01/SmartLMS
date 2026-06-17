@extends('layouts.app')

@section('title', 'Thanh toán')

@section('content')
    @php
        $statuses = \App\Models\TeachingContract::statuses();
        $statusClasses = [
            'unpaid' => 'warning',
            'partial' => 'info',
            'received' => 'success',
            'cancelled' => 'secondary',
        ];
        $money = fn ($value) => number_format((float) $value, 0, ',', '.') . ' đ';
    @endphp

    <style>
        .payment-page {
            color: #0f172a;
        }

        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .payment-title {
            margin: 0 0 4px;
            font-size: 22px;
            font-weight: 800;
        }

        .payment-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 13.5px;
        }

        .payment-stat,
        .payment-filter,
        .payment-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .payment-stat {
            padding: 16px;
            height: 100%;
        }

        .payment-stat__label {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .payment-stat__value {
            margin-top: 8px;
            font-size: 24px;
            font-weight: 800;
            line-height: 1.15;
            word-break: break-word;
        }

        .payment-filter {
            padding: 16px;
            margin: 18px 0;
        }

        .payment-table th {
            color: #64748b;
            font-size: 11.5px;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .payment-table td {
            vertical-align: middle;
            font-size: 13.5px;
        }

        .payment-sub {
            color: #64748b;
            font-size: 12px;
        }

        .contract-number {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 800;
            color: #2563eb;
            text-decoration: none;
        }

        .record-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 9px;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 11.5px;
            font-weight: 700;
            margin: 2px;
        }

        .payment-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .record-picker {
            max-height: 220px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px;
            background: #f8fafc;
        }

        @media (max-width: 767.98px) {
            .payment-header .btn,
            .payment-filter .btn {
                width: 100%;
            }

            .payment-actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="payment-page">
        <div class="payment-header">
            <div>
                <h1 class="payment-title">Thanh toán</h1>
                <p class="payment-subtitle">Quản lý hợp đồng, trạng thái nhận tiền và các dòng giảng dạy liên quan.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-toggle="modal"
                    data-bs-target="#importPaymentModal">
                    <i class="fas fa-file-import me-2"></i>Import Excel/CSV
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
                    data-bs-target="#createPaymentModal">
                    <i class="fas fa-plus me-2"></i>Thêm hợp đồng
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3">
                <i class="fas fa-exclamation-circle me-2"></i>{{ $errors->first() }}
            </div>
        @endif

        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="payment-stat">
                    <div class="payment-stat__label">Tổng hợp đồng</div>
                    <div class="payment-stat__value text-primary">{{ $stats['total_contracts'] }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="payment-stat">
                    <div class="payment-stat__label">Tổng tiền hợp đồng</div>
                    <div class="payment-stat__value">{{ $money($stats['total_amount']) }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="payment-stat">
                    <div class="payment-stat__label">Đã nhận</div>
                    <div class="payment-stat__value text-success">{{ $money($stats['received_amount']) }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="payment-stat">
                    <div class="payment-stat__label">Chưa nhận</div>
                    <div class="payment-stat__value text-danger">{{ $money($stats['remaining_amount']) }}</div>
                </div>
            </div>
        </div>

        <form action="{{ route('payments.index') }}" method="GET" class="payment-filter">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label small fw-bold text-muted">Tìm kiếm</label>
                    <input type="search" name="search" value="{{ $filters['search'] }}" class="form-control"
                        placeholder="Số hợp đồng, môn học, lớp, ghi chú...">
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label class="form-label small fw-bold text-muted">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small fw-bold text-muted">Ngày ký từ</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small fw-bold text-muted">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control">
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-fill">
                        <i class="fas fa-filter me-1"></i>Lọc
                    </button>
                    <a href="{{ route('payments.index') }}" class="btn btn-light border">
                        <i class="fas fa-rotate-left"></i>
                    </a>
                </div>
            </div>
        </form>

        <div class="payment-card">
            <div class="table-responsive">
                <table class="table payment-table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">Số hợp đồng</th>
                            <th class="px-4 py-3">Ngày ký</th>
                            <th class="px-4 py-3">Tổng tiền</th>
                            <th class="px-4 py-3">Đã nhận</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3">Ngày nhận</th>
                            <th class="px-4 py-3">Giảng dạy</th>
                            <th class="px-4 py-3 text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contracts as $contract)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="contract-number">
                                        <i class="fas fa-file-contract"></i>{{ $contract->contract_number }}
                                    </div>
                                    @if (auth()->user()->role === 'admin')
                                        <div class="payment-sub">
                                            <i class="fas fa-user-tie me-1"></i>{{ $contract->teacher?->name ?? 'N/A' }}
                                        </div>
                                    @endif
                                    @if ($contract->note)
                                        <div class="payment-sub">{{ Str::limit($contract->note, 70) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $contract->signed_date?->format('d/m/Y') ?: '--' }}</td>
                                <td class="px-4 py-3 fw-bold">{{ $money($contract->total_amount) }}</td>
                                <td class="px-4 py-3">
                                    <div class="fw-bold text-success">{{ $money($contract->received_amount) }}</div>
                                    @if ($contract->remaining_amount > 0)
                                        <div class="payment-sub text-danger">Còn {{ $money($contract->remaining_amount) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="badge bg-{{ $statusClasses[$contract->status] ?? 'secondary' }} bg-opacity-10 text-{{ $statusClasses[$contract->status] ?? 'secondary' }} rounded-pill px-3">
                                        {{ $statuses[$contract->status] ?? $contract->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $contract->received_date?->format('d/m/Y') ?: '--' }}</td>
                                <td class="px-4 py-3" style="min-width:240px">
                                    @forelse ($contract->teachingRecords->take(3) as $record)
                                        <span class="record-chip">
                                            <i class="fas fa-chalkboard-teacher"></i>{{ $record->subject_name }}
                                        </span>
                                    @empty
                                        <span class="payment-sub">Chưa gắn dòng giảng dạy</span>
                                    @endforelse
                                    @if ($contract->teachingRecords->count() > 3)
                                        <span class="record-chip">+{{ $contract->teachingRecords->count() - 3 }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="payment-actions">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editPaymentModal{{ $contract->id }}">
                                            <i class="fas fa-edit me-1"></i>Sửa
                                        </button>
                                        <form action="{{ route('payments.destroy', $contract->id) }}" method="POST"
                                            onsubmit="return confirm('Xóa hợp đồng thanh toán này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                                                <i class="fas fa-trash-alt me-1"></i>Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-file-invoice-dollar fa-3x mb-3 opacity-25"></i>
                                    <p class="mb-0">Chưa có hợp đồng thanh toán.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($contracts->hasPages())
                <div class="p-3 border-top">
                    {{ $contracts->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="createPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{ route('payments.store') }}" method="POST" class="modal-content border-0 shadow payment-form">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Thêm hợp đồng thanh toán</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    @include('payments.partials.form', ['contract' => null])
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="importPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('payments.import') }}" method="POST" enctype="multipart/form-data"
                class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Import thanh toán từ Excel/CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    @if (auth()->user()->role === 'admin')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Giáo viên</label>
                            <select name="teacher_id" class="form-select" required>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-semibold">File Excel/CSV</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required>
                    </div>
                    <div class="alert alert-info small mb-0">
                        File cần có các cột:
                        <strong>Số hợp đồng, Ngày ký, Tổng tiền VND, Trạng thái, Ngày nhận, Hợp đồng, Ghi chú</strong>.
                        Cột <strong>Hợp đồng</strong> sẽ được lưu vào ghi chú nếu là tên/link hợp đồng.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Import</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($contracts as $contract)
        <div class="modal fade" id="editPaymentModal{{ $contract->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form action="{{ route('payments.update', $contract->id) }}" method="POST"
                    class="modal-content border-0 shadow payment-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Sửa hợp đồng thanh toán</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body py-4">
                        @include('payments.partials.form', ['contract' => $contract])
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.payment-form').forEach((form) => {
                const statusSelect = form.querySelector('[name="status"]');
                const totalInput = form.querySelector('[name="total_amount"]');
                const receivedInput = form.querySelector('[name="received_amount"]');
                const receivedDateInput = form.querySelector('[name="received_date"]');

                const syncStatus = () => {
                    if (!statusSelect || !receivedInput || !totalInput) return;

                    if (statusSelect.value === 'received') {
                        receivedInput.value = totalInput.value || 0;
                    }

                    if (statusSelect.value === 'unpaid') {
                        receivedInput.value = 0;
                        if (receivedDateInput) receivedDateInput.value = '';
                    }
                };

                statusSelect?.addEventListener('change', syncStatus);
                totalInput?.addEventListener('input', () => {
                    if (statusSelect?.value === 'received') {
                        receivedInput.value = totalInput.value || 0;
                    }
                });
            });
        });
    </script>
@endsection
