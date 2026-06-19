@extends('layouts.app')

@section('title', 'Báo cáo vận hành')

@section('content')
    @php
        $money = fn ($value) => number_format((float) $value, 0, ',', '.') . ' đ';
        $query = request()->query();
        $monthOptions = [
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
    @endphp

    <style>
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .report-title {
            margin: 0 0 4px;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
        }

        .report-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 13.5px;
        }

        .report-card,
        .report-filter,
        .report-stat {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .report-stat {
            height: 100%;
            padding: 16px;
        }

        .report-stat__label {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .report-stat__value {
            margin-top: 8px;
            font-size: 24px;
            line-height: 1.15;
            font-weight: 800;
            word-break: break-word;
        }

        .report-filter {
            padding: 16px;
            margin: 18px 0;
        }

        .report-card {
            margin-bottom: 18px;
            overflow: hidden;
        }

        .report-card__head {
            padding: 16px 18px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .report-card__title {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
        }

        .report-table th {
            color: #64748b;
            font-size: 11.5px;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .report-table td {
            font-size: 13.5px;
            vertical-align: middle;
        }

        @media (max-width: 767.98px) {
            .report-header .btn,
            .report-filter .btn {
                width: 100%;
            }
        }
    </style>

    <div class="report-header">
        <div>
            <h1 class="report-title">Báo cáo vận hành</h1>
            <p class="report-subtitle">Tổng hợp giảng dạy, số buổi và thanh toán theo trung tâm, khóa, tháng, năm.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('reports.operations.export', $query) }}" class="btn btn-success rounded-pill px-4">
                <i class="fas fa-file-excel me-2"></i>Xuất Excel
            </a>
            <a href="{{ route('reports.operations.print', $query) }}" target="_blank"
                class="btn btn-outline-danger rounded-pill px-4">
                <i class="fas fa-file-pdf me-2"></i>Xuất PDF
            </a>
        </div>
    </div>

    <form action="{{ route('reports.operations') }}" method="GET" class="report-filter">
        <div class="row g-3 align-items-end">
            @if (auth()->user()->role === 'admin')
                <div class="col-12 col-lg-2">
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
            <div class="col-12 col-md-6 col-lg-2">
                <label class="form-label small fw-bold text-muted">Trung tâm</label>
                <select name="center_name" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center }}" @selected($filters['center_name'] === $center)>{{ $center }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <label class="form-label small fw-bold text-muted">Khóa</label>
                <select name="term_code" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term }}" @selected($filters['term_code'] === $term)>{{ $term }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small fw-bold text-muted">Tháng</label>
                <select name="month" class="form-select">
                    <option value="">Cả năm</option>
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) $filters['month'] === (string) $value)>{{ $label }}</option>
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
                    <i class="fas fa-filter me-1"></i>Lọc
                </button>
                <a href="{{ route('reports.operations') }}" class="btn btn-light border">
                    <i class="fas fa-rotate-left"></i>
                </a>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-2">
            <div class="report-stat">
                <div class="report-stat__label">Môn đã dạy</div>
                <div class="report-stat__value text-primary">{{ $summary['completed_subjects_count'] }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="report-stat">
                <div class="report-stat__label">Tổng môn</div>
                <div class="report-stat__value">{{ $summary['subjects_count'] }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="report-stat">
                <div class="report-stat__label">Số buổi</div>
                <div class="report-stat__value text-success">{{ $summary['total_sessions'] }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="report-stat">
                <div class="report-stat__label">Tổng tiền</div>
                <div class="report-stat__value">{{ $money($summary['total_contract_amount']) }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="report-stat">
                <div class="report-stat__label">Đã nhận</div>
                <div class="report-stat__value text-success">{{ $money($summary['received_amount']) }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="report-stat">
                <div class="report-stat__label">Chưa nhận</div>
                <div class="report-stat__value text-danger">{{ $money($summary['remaining_amount']) }}</div>
            </div>
        </div>
    </div>

    @include('reports.partials.group-table', ['title' => 'Theo trung tâm', 'label' => 'Trung tâm', 'rows' => $byCenter])
    @include('reports.partials.group-table', ['title' => 'Theo khóa', 'label' => 'Khóa', 'rows' => $byTerm])

    <div class="report-card">
        <div class="report-card__head">
            <h2 class="report-card__title">Chi tiết giảng dạy</h2>
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">{{ $teachingRecords->count() }} dòng</span>
        </div>
        <div class="table-responsive">
            <table class="table report-table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">Môn học</th>
                        <th class="px-4 py-3">Lớp</th>
                        <th class="px-4 py-3">Trung tâm</th>
                        <th class="px-4 py-3">Khóa</th>
                        <th class="px-4 py-3">Số buổi</th>
                        <th class="px-4 py-3">Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teachingRecords->take(25) as $record)
                        <tr>
                            <td class="px-4 py-3 fw-semibold">{{ $record->subject_name }}</td>
                            <td class="px-4 py-3">{{ $record->class_name ?: '--' }}</td>
                            <td class="px-4 py-3">{{ $record->center_name ?: '--' }}</td>
                            <td class="px-4 py-3">{{ $record->term_code ?: '--' }}</td>
                            <td class="px-4 py-3 fw-bold">{{ $record->planned_sessions }}</td>
                            <td class="px-4 py-3">
                                {{ $record->start_date?->format('d/m/Y') ?: '--' }} -
                                {{ $record->end_date?->format('d/m/Y') ?: '--' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Không có dữ liệu giảng dạy.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="report-card">
        <div class="report-card__head">
            <h2 class="report-card__title">Chi tiết thanh toán</h2>
            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">{{ $contracts->count() }} hợp đồng</span>
        </div>
        <div class="table-responsive">
            <table class="table report-table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">Hợp đồng</th>
                        <th class="px-4 py-3">Ngày ký</th>
                        <th class="px-4 py-3">Tổng tiền</th>
                        <th class="px-4 py-3">Đã nhận</th>
                        <th class="px-4 py-3">Chưa nhận</th>
                        <th class="px-4 py-3">Ngày nhận</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contracts->take(25) as $contract)
                        <tr>
                            <td class="px-4 py-3 fw-semibold">{{ $contract->contract_number }}</td>
                            <td class="px-4 py-3">{{ $contract->signed_date?->format('d/m/Y') ?: '--' }}</td>
                            <td class="px-4 py-3">{{ $money($contract->total_amount) }}</td>
                            <td class="px-4 py-3 text-success fw-bold">{{ $money($contract->received_amount) }}</td>
                            <td class="px-4 py-3 text-danger fw-bold">{{ $money($contract->remaining_amount) }}</td>
                            <td class="px-4 py-3">{{ $contract->received_date?->format('d/m/Y') ?: '--' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Không có dữ liệu thanh toán.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
