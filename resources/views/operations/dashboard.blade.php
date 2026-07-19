@extends('layouts.app')

@section('title', 'Dashboard vận hành')

@section('content')
    @php
        $money = fn ($value) => number_format((float) $value, 0, ',', '.') . ' đ';
        $periodLabel = $periodStart->format('m/Y');
    @endphp

    <style>
        .ops-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .ops-title {
            margin: 0 0 4px;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
        }

        .ops-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 13.5px;
        }

        .ops-filter,
        .ops-card,
        .ops-stat {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .ops-filter {
            padding: 16px;
            margin-bottom: 18px;
        }

        .ops-stat {
            height: 100%;
            padding: 18px;
            position: relative;
            overflow: hidden;
        }

        .ops-stat::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: var(--ops-accent, #2563eb);
        }

        .ops-stat__icon {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: color-mix(in srgb, var(--ops-accent, #2563eb) 12%, white);
            color: var(--ops-accent, #2563eb);
            margin-bottom: 14px;
        }

        .ops-stat__label {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .ops-stat__value {
            margin-top: 7px;
            font-size: 26px;
            line-height: 1.15;
            font-weight: 850;
            color: #0f172a;
            word-break: break-word;
        }

        .ops-stat__note {
            margin-top: 8px;
            color: #64748b;
            font-size: 12.5px;
        }

        .ops-card {
            overflow: hidden;
            height: 100%;
        }

        .ops-card__head {
            padding: 16px 18px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .ops-card__title {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }

        .ops-table th {
            color: #64748b;
            font-size: 11.5px;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .ops-table td {
            font-size: 13.5px;
            vertical-align: middle;
        }

        .ops-rank {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 800;
            font-size: 12px;
        }

        @media (max-width: 767.98px) {
            .ops-filter .btn,
            .ops-header .btn {
                width: 100%;
            }

            .ops-stat__value {
                font-size: 22px;
            }
        }
    </style>

    <div class="ops-header">
        <div>
            <h1 class="ops-title">Dashboard vận hành</h1>
            <p class="ops-subtitle">
                Theo dõi nhanh giảng dạy và tài chính trong {{ $periodLabel }}, tách riêng khỏi dashboard chính.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('reports.operations', ['month' => $filters['month'], 'year' => $filters['year'], 'teacher_id' => $filters['teacher_id']]) }}"
                class="btn btn-outline-primary rounded-pill px-4">
                <i class="fa-solid fa-chart-column me-2"></i>Xem báo cáo
            </a>
            <a href="{{ route('payments.index') }}" class="btn btn-primary rounded-pill px-4">
                <i class="fa-solid fa-file-invoice-dollar me-2"></i>Thanh toán
            </a>
        </div>
    </div>

    <form action="{{ route('operations.dashboard') }}" method="GET" class="ops-filter">
        <div class="row g-3 align-items-end">
            @if (auth()->user()->role === 'admin')
                <div class="col-12 col-lg-3">
                    <label class="form-label small fw-bold text-muted">Giáo viên</label>
                    <select name="teacher_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected((string) $filters['teacher_id'] === (string) $teacher->id)>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-6 col-lg-2">
                <label class="form-label small fw-bold text-muted">Tháng</label>
                <select name="month" class="form-select">
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}" @selected((int) $filters['month'] === (int) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small fw-bold text-muted">Năm</label>
                <input type="number" name="year" value="{{ $filters['year'] }}" min="2020" max="2100"
                    class="form-control">
            </div>
            <div class="col-12 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="fa-solid fa-filter me-1"></i>Lọc
                </button>
                <a href="{{ route('operations.dashboard') }}" class="btn btn-light border">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="ops-stat" style="--ops-accent: #2563eb;">
                <div class="ops-stat__icon"><i class="fa-solid fa-chalkboard-teacher"></i></div>
                <div class="ops-stat__label">Buổi dạy trong tháng</div>
                <div class="ops-stat__value">{{ $stats['month_sessions'] }}</div>
                <div class="ops-stat__note">{{ $stats['month_subjects'] }} môn bắt đầu trong {{ $periodLabel }}</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="ops-stat" style="--ops-accent: #16a34a;">
                <div class="ops-stat__icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="ops-stat__label">Hợp đồng đã nhận</div>
                <div class="ops-stat__value">{{ $stats['received_contracts'] }}</div>
                <div class="ops-stat__note">{{ $money($stats['received_amount']) }} đã nhận trong tháng</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="ops-stat" style="--ops-accent: #dc2626;">
                <div class="ops-stat__icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="ops-stat__label">Tiền còn chờ</div>
                <div class="ops-stat__value">{{ $money($stats['pending_amount']) }}</div>
                <div class="ops-stat__note">{{ $stats['pending_contracts'] }} hợp đồng còn chưa nhận đủ</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="ops-stat" style="--ops-accent: #f59e0b;">
                <div class="ops-stat__icon"><i class="fa-solid fa-building"></i></div>
                <div class="ops-stat__label">Trung tâm nhiều lớp nhất</div>
                <div class="ops-stat__value">{{ $stats['top_center_name'] ?: '--' }}</div>
                <div class="ops-stat__note">{{ $stats['top_center_classes'] }} lớp trong {{ $periodLabel }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="ops-card">
                <div class="ops-card__head">
                    <h2 class="ops-card__title">Trung tâm nhiều lớp nhất</h2>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">{{ $centerRows->count() }} trung tâm</span>
                </div>
                <div class="table-responsive">
                    <table class="table ops-table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Trung tâm</th>
                                <th class="px-4 py-3">Lớp</th>
                                <th class="px-4 py-3">Môn</th>
                                <th class="px-4 py-3">Buổi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($centerRows as $index => $row)
                                <tr>
                                    <td class="px-4 py-3"><span class="ops-rank">{{ $index + 1 }}</span></td>
                                    <td class="px-4 py-3 fw-semibold">{{ $row->label }}</td>
                                    <td class="px-4 py-3">{{ $row->classes_count }}</td>
                                    <td class="px-4 py-3">{{ $row->subjects_count }}</td>
                                    <td class="px-4 py-3 fw-bold text-primary">{{ $row->sessions_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">Chưa có dữ liệu trung tâm trong tháng này.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="ops-card">
                <div class="ops-card__head">
                    <h2 class="ops-card__title">Hợp đồng còn chờ</h2>
                    <a href="{{ route('payments.index', ['status' => \App\Models\TeachingContract::STATUS_UNPAID]) }}"
                        class="btn btn-sm btn-outline-danger rounded-pill px-3">
                        Xem chi tiết
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table ops-table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3">Hợp đồng</th>
                                <th class="px-4 py-3">Còn chờ</th>
                                <th class="px-4 py-3">Ngày ký</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingContracts->take(6) as $contract)
                                <tr>
                                    <td class="px-4 py-3 fw-semibold">{{ $contract->contract_number }}</td>
                                    <td class="px-4 py-3 text-danger fw-bold">{{ $money($contract->remaining_amount) }}</td>
                                    <td class="px-4 py-3">{{ $contract->signed_date?->format('d/m/Y') ?: '--' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-5">Không có hợp đồng đang chờ.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="ops-card">
                <div class="ops-card__head">
                    <h2 class="ops-card__title">Giảng dạy trong tháng</h2>
                    <a href="{{ route('teaching.index', ['from_date' => $periodStart->toDateString(), 'to_date' => $periodEnd->toDateString()]) }}"
                        class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        Xem danh sách
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table ops-table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3">Môn</th>
                                <th class="px-4 py-3">Lớp</th>
                                <th class="px-4 py-3">Buổi</th>
                                <th class="px-4 py-3">Bắt đầu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentTeachingRecords as $record)
                                <tr>
                                    <td class="px-4 py-3 fw-semibold">{{ $record->subject_name }}</td>
                                    <td class="px-4 py-3">{{ $record->class_name ?: '--' }}</td>
                                    <td class="px-4 py-3 fw-bold">{{ $record->planned_sessions }}</td>
                                    <td class="px-4 py-3">{{ $record->start_date?->format('d/m/Y') ?: '--' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">Chưa có môn bắt đầu trong tháng này.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="ops-card">
                <div class="ops-card__head">
                    <h2 class="ops-card__title">Thanh toán đã nhận trong tháng</h2>
                    <a href="{{ route('payments.index', ['status' => \App\Models\TeachingContract::STATUS_RECEIVED]) }}"
                        class="btn btn-sm btn-outline-success rounded-pill px-3">
                        Xem danh sách
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table ops-table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3">Hợp đồng</th>
                                <th class="px-4 py-3">Đã nhận</th>
                                <th class="px-4 py-3">Ngày nhận</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentReceivedContracts as $contract)
                                <tr>
                                    <td class="px-4 py-3 fw-semibold">{{ $contract->contract_number }}</td>
                                    <td class="px-4 py-3 text-success fw-bold">{{ $money($contract->received_amount) }}</td>
                                    <td class="px-4 py-3">{{ $contract->received_date?->format('d/m/Y') ?: '--' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-5">Chưa có thanh toán đã nhận trong tháng này.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
