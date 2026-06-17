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
        $money = fn($value) => number_format((float) $value, 0, ',', '.') . ' đ';
    @endphp

    <style>
        /* ── Design tokens ── */
        :root {
            --p-surface: #ffffff;
            --p-border: #e8edf3;
            --p-ink: #0f1c2e;
            --p-muted: #6b7a8d;
            --p-accent: #2563eb;
            --p-radius: 12px;
            --p-shadow: 0 1px 3px rgba(15, 28, 46, .06), 0 4px 12px rgba(15, 28, 46, .05);
        }

        .pp {
            padding-bottom: 40px;
        }

        /* ── Header ── */
        .pp-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .pp-eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--p-accent);
            margin-bottom: 4px;
        }

        .pp-title {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: var(--p-ink);
            letter-spacing: -.3px;
        }

        .pp-sub {
            margin: 4px 0 0;
            font-size: 13px;
            color: var(--p-muted);
        }

        /* ── Stat strip ── */
        .pp-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        @media (max-width: 767px) {
            .pp-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .pp-stat {
            background: var(--p-surface);
            border: 1px solid var(--p-border);
            border-radius: var(--p-radius);
            padding: 16px 18px;
            box-shadow: var(--p-shadow);
            position: relative;
            overflow: hidden;
        }

        .pp-stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: var(--p-radius) var(--p-radius) 0 0;
        }

        .pp-stat--blue::before {
            background: #2563eb;
        }

        .pp-stat--slate::before {
            background: #475569;
        }

        .pp-stat--green::before {
            background: #16a34a;
        }

        .pp-stat--red::before {
            background: #dc2626;
        }

        .pp-stat__lbl {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--p-muted);
        }

        .pp-stat__val {
            font-size: 22px;
            font-weight: 800;
            line-height: 1.15;
            margin-top: 6px;
            color: var(--p-ink);
            word-break: break-word;
        }

        .pp-stat--blue .pp-stat__val {
            color: #2563eb;
        }

        .pp-stat--slate .pp-stat__val {
            color: #334155;
        }

        .pp-stat--green .pp-stat__val {
            color: #16a34a;
        }

        .pp-stat--red .pp-stat__val {
            color: #dc2626;
        }

        /* ── Filter bar ── */
        .pp-filter {
            background: var(--p-surface);
            border: 1px solid var(--p-border);
            border-radius: var(--p-radius);
            padding: 16px 20px;
            margin-bottom: 16px;
            box-shadow: var(--p-shadow);
        }

        .pp-filter .form-control,
        .pp-filter .form-select {
            font-size: 13px;
            border-color: var(--p-border);
            border-radius: 8px;
            background: #f8fafc;
            transition: border-color .15s, box-shadow .15s;
        }

        .pp-filter .form-control:focus,
        .pp-filter .form-select:focus {
            border-color: var(--p-accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
            background: #fff;
        }

        .pp-filter .form-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--p-muted);
            margin-bottom: 5px;
        }

        /* ── Table card ── */
        .pp-card {
            background: var(--p-surface);
            border: 1px solid var(--p-border);
            border-radius: var(--p-radius);
            box-shadow: var(--p-shadow);
            overflow: hidden;
        }

        .pp-table {
            margin: 0;
        }

        .pp-table thead th {
            background: #f8fafc;
            border-bottom: 1px solid var(--p-border);
            color: var(--p-muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
            padding: 12px 20px;
        }

        .pp-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background .12s;
        }

        .pp-table tbody tr:last-child {
            border-bottom: none;
        }

        .pp-table tbody tr:hover {
            background: #f8fafc;
        }

        .pp-table td {
            padding: 14px 20px;
            vertical-align: middle;
            font-size: 13.5px;
            color: var(--p-ink);
        }

        /* ── Contract number ── */
        .contract-id {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 800;
            font-size: 13.5px;
            color: var(--p-accent);
        }

        .pp-note {
            font-size: 12px;
            color: var(--p-muted);
            margin-top: 2px;
        }

        /* ── Amount display ── */
        .amount-main {
            font-weight: 700;
            font-size: 13.5px;
        }

        .amount-remain {
            font-size: 11.5px;
            color: #dc2626;
            margin-top: 1px;
        }

        /* ── Badges ── */
        .pp-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 999px;
            padding: 3px 10px;
            font-size: 11.5px;
            font-weight: 700;
            line-height: 1.6;
        }

        .pp-badge--warning {
            background: #fffbeb;
            color: #b45309;
        }

        .pp-badge--info {
            background: #f0f9ff;
            color: #0369a1;
        }

        .pp-badge--success {
            background: #f0fdf4;
            color: #15803d;
        }

        .pp-badge--secondary {
            background: #f1f5f9;
            color: #475569;
        }

        /* ── Teaching record chips ── */
        .record-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 11.5px;
            font-weight: 600;
            margin: 2px;
        }

        .record-chip--more {
            background: #f1f5f9;
            color: #475569;
        }

        /* ── Action buttons ── */
        .pp-actions {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        .pp-btn-edit,
        .pp-btn-del {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 8px;
            padding: 5px 11px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid;
            cursor: pointer;
            transition: background .12s, color .12s;
            background: transparent;
        }

        .pp-btn-edit {
            border-color: #bfdbfe;
            color: #2563eb;
        }

        .pp-btn-edit:hover {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .pp-btn-del {
            border-color: #fecaca;
            color: #dc2626;
        }

        .pp-btn-del:hover {
            background: #fef2f2;
            color: #b91c1c;
        }

        /* ── Evidence link ── */
        .evidence-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12.5px;
            font-weight: 600;
            color: #475569;
            border: 1px solid var(--p-border);
            border-radius: 7px;
            padding: 4px 10px;
            text-decoration: none;
            transition: border-color .12s, color .12s;
        }

        .evidence-link:hover {
            border-color: #93c5fd;
            color: var(--p-accent);
        }

        /* ── Empty state ── */
        .pp-empty {
            text-align: center;
            padding: 56px 24px;
            color: var(--p-muted);
        }

        .pp-empty-icon {
            width: 56px;
            height: 56px;
            background: #f1f5f9;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #94a3b8;
            margin-bottom: 16px;
        }

        .pp-empty p {
            margin: 0;
            font-size: 14px;
        }

        /* ── Header CTA buttons ── */
        .btn-pp-import {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #fff;
            border: 1px solid var(--p-border);
            border-radius: 9px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            color: var(--p-ink);
            transition: border-color .15s, box-shadow .15s;
            cursor: pointer;
        }

        .btn-pp-import:hover {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .07);
        }

        .btn-pp-add {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--p-accent);
            border: none;
            border-radius: 9px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            transition: background .15s, box-shadow .15s;
            cursor: pointer;
        }

        .btn-pp-add:hover {
            background: #1d4ed8;
            box-shadow: 0 4px 12px rgba(37, 99, 235, .3);
        }

        .pp-pagination {
            padding: 14px 20px;
            border-top: 1px solid var(--p-border);
        }

        @media (max-width: 575px) {
            .pp-actions {
                justify-content: flex-start;
            }

            .pp-header-actions {
                width: 100%;
            }

            .btn-pp-import,
            .btn-pp-add {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="pp">

        {{-- Header --}}
        <div class="pp-header">
            <div>
                <div class="pp-eyebrow">Quản lý</div>
                <h1 class="pp-title">Thanh toán</h1>
                <p class="pp-sub">Quản lý hợp đồng, trạng thái nhận tiền và các dòng giảng dạy liên quan.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap pp-header-actions">
                <button type="button" class="btn-pp-import" data-bs-toggle="modal" data-bs-target="#importPaymentModal">
                    <i class="fas fa-file-import"></i>Import Excel/CSV
                </button>
                <button type="button" class="btn-pp-add" data-bs-toggle="modal" data-bs-target="#createPaymentModal">
                    <i class="fas fa-plus"></i>Thêm hợp đồng
                </button>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3 mb-4 d-flex align-items-center gap-2">
                <i class="fas fa-check-circle"></i><span>{{ session('success') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3 mb-4 d-flex align-items-center gap-2">
                <i class="fas fa-exclamation-circle"></i><span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- Stats --}}
        <div class="pp-stats">
            <div class="pp-stat pp-stat--blue">
                <div class="pp-stat__lbl">Tổng hợp đồng</div>
                <div class="pp-stat__val">{{ $stats['total_contracts'] }}</div>
            </div>
            <div class="pp-stat pp-stat--slate">
                <div class="pp-stat__lbl">Tổng tiền</div>
                <div class="pp-stat__val">{{ $money($stats['total_amount']) }}</div>
            </div>
            <div class="pp-stat pp-stat--green">
                <div class="pp-stat__lbl">Đã nhận</div>
                <div class="pp-stat__val">{{ $money($stats['received_amount']) }}</div>
            </div>
            <div class="pp-stat pp-stat--red">
                <div class="pp-stat__lbl">Chưa nhận</div>
                <div class="pp-stat__val">{{ $money($stats['remaining_amount']) }}</div>
            </div>
        </div>

        {{-- Filters --}}
        <form action="{{ route('payments.index') }}" method="GET" class="pp-filter">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="search" name="search" value="{{ $filters['search'] }}" class="form-control"
                        placeholder="Số hợp đồng, môn học, lớp, ghi chú...">
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label">Ngày ký từ</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control">
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill rounded-3"
                        style="font-size:13px;font-weight:600">
                        <i class="fas fa-filter me-1"></i>Lọc
                    </button>
                    <a href="{{ route('payments.index') }}" class="btn btn-light border rounded-3" style="font-size:13px"
                        title="Đặt lại">
                        <i class="fas fa-rotate-left"></i>
                    </a>
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="pp-card">
            <div class="table-responsive">
                <table class="table pp-table">
                    <thead>
                        <tr>
                            <th>Số hợp đồng</th>
                            <th>Ngày ký</th>
                            <th>Tổng tiền</th>
                            <th>Đã nhận</th>
                            <th>Trạng thái</th>
                            <th>Ngày nhận</th>
                            <th>Minh chứng</th>
                            <th>Giảng dạy</th>
                            <th style="text-align:right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contracts as $contract)
                            <tr>
                                <td>
                                    <div class="contract-id">
                                        <i class="fas fa-file-contract"
                                            style="font-size:11px"></i>{{ $contract->contract_number }}
                                    </div>
                                    @if (auth()->user()->role === 'admin')
                                        <div class="pp-note">
                                            <i class="fas fa-user-tie"
                                                style="font-size:10px;margin-right:3px"></i>{{ $contract->teacher?->name ?? 'N/A' }}
                                        </div>
                                    @endif
                                    @if ($contract->note)
                                        <div class="pp-note">{{ Str::limit($contract->note, 60) }}</div>
                                    @endif
                                </td>
                                <td style="font-size:13px">{{ $contract->signed_date?->format('d/m/Y') ?: '—' }}</td>
                                <td>
                                    <span class="amount-main">{{ $money($contract->total_amount) }}</span>
                                </td>
                                <td>
                                    <div class="amount-main" style="color:#16a34a">{{ $money($contract->received_amount) }}
                                    </div>
                                    @if ($contract->remaining_amount > 0)
                                        <div class="amount-remain">Còn {{ $money($contract->remaining_amount) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php $sc = $statusClasses[$contract->status] ?? 'secondary'; @endphp
                                    <span class="pp-badge pp-badge--{{ $sc }}">
                                        {{ $statuses[$contract->status] ?? $contract->status }}
                                    </span>
                                </td>
                                <td style="font-size:13px">{{ $contract->received_date?->format('d/m/Y') ?: '—' }}</td>
                                <td>
                                    @if ($contract->evidence_url)
                                        <a href="{{ $contract->evidence_url }}" target="_blank" rel="noopener"
                                            class="evidence-link">
                                            <i class="fas fa-arrow-up-right-from-square" style="font-size:10px"></i>Xem
                                        </a>
                                    @else
                                        <span style="font-size:12px;color:#94a3b8">Chưa có</span>
                                    @endif
                                </td>
                                <td style="min-width:220px">
                                    @forelse ($contract->teachingRecords->take(3) as $record)
                                        <span class="record-chip">
                                            <i class="fas fa-chalkboard-teacher"
                                                style="font-size:9px"></i>{{ $record->subject_name }}
                                        </span>
                                    @empty
                                        <span style="font-size:12px;color:#94a3b8">Chưa gắn</span>
                                    @endforelse
                                    @if ($contract->teachingRecords->count() > 3)
                                        <span
                                            class="record-chip record-chip--more">+{{ $contract->teachingRecords->count() - 3 }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="pp-actions">
                                        <button type="button" class="pp-btn-edit" data-bs-toggle="modal"
                                            data-bs-target="#editPaymentModal{{ $contract->id }}">
                                            <i class="fas fa-pen-to-square"></i>Sửa
                                        </button>
                                        <form action="{{ route('payments.destroy', $contract->id) }}" method="POST"
                                            onsubmit="return confirm('Xóa hợp đồng thanh toán này?');" style="margin:0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="pp-btn-del">
                                                <i class="fas fa-trash-alt"></i>Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="pp-empty">
                                        <div class="pp-empty-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                        <p>Chưa có hợp đồng thanh toán.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($contracts->hasPages())
                <div class="pp-pagination">{{ $contracts->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Create modal --}}
    <div class="modal fade" id="createPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{ route('payments.store') }}" method="POST"
                class="modal-content border-0 shadow-lg payment-form">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Thêm hợp đồng thanh toán</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    @include('payments.partials.form', ['contract' => null])
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Import modal --}}
    <div class="modal fade" id="importPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('payments.import') }}" method="POST" enctype="multipart/form-data"
                class="modal-content border-0 shadow-lg">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Import thanh toán từ Excel / CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    @if (auth()->user()->role === 'admin')
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Giáo viên</label>
                            <select name="teacher_id" class="form-select" required>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">File Excel / CSV</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt"
                            required>
                    </div>
                    <div class="alert border-0 rounded-3 small mb-0" style="background:#eff6ff;color:#1e40af">
                        <i class="fas fa-circle-info me-2"></i>File cần có các cột:
                        <strong>Số hợp đồng, Ngày ký, Tổng tiền VND, Trạng thái, Ngày nhận, Hợp đồng, Ghi chú</strong>.
                        Cột <strong>Hợp đồng</strong> sẽ lưu vào link minh chứng nếu là URL, nếu không sẽ lưu vào ghi chú.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Import</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit modals --}}
    @foreach ($contracts as $contract)
        <div class="modal fade" id="editPaymentModal{{ $contract->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form action="{{ route('payments.update', $contract->id) }}" method="POST"
                    class="modal-content border-0 shadow-lg payment-form">
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
                        <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4">Lưu thay đổi</button>
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
