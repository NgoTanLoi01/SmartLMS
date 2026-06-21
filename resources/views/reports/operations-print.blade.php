<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Báo cáo vận hành SmartLMS</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111827;
            margin: 0;
            font-size: 11px;
            background: #f3f6fb;
        }

        .page {
            background: #ffffff;
            min-height: 100vh;
            padding: 18px;
        }

        .print-btn {
            position: fixed;
            right: 18px;
            top: 18px;
            padding: 9px 16px;
            border-radius: 999px;
            border: none;
            background: #2563eb;
            color: #fff;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(37, 99, 235, .25);
            z-index: 10;
        }

        .report-header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .brand,
        .report-meta {
            display: table-cell;
            vertical-align: middle;
        }

        .brand {
            width: 58%;
        }

        .report-meta {
            width: 42%;
            text-align: right;
            color: #64748b;
            line-height: 1.7;
        }

        .brand-row {
            display: table;
        }

        .brand-logo,
        .brand-text {
            display: table-cell;
            vertical-align: middle;
        }

        .brand-logo {
            width: 86px;
        }

        .brand-logo img {
            max-width: 74px;
            max-height: 58px;
        }

        .eyebrow {
            color: #2563eb;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 4px;
        }

        h1 {
            font-size: 22px;
            margin: 0;
            letter-spacing: -0.02em;
            color: #0f172a;
        }

        .subtitle {
            margin-top: 5px;
            color: #64748b;
            font-size: 11px;
        }

        .filter-bar {
            display: table;
            width: 100%;
            margin: 12px 0 14px;
            border: 1px solid #dbeafe;
            background: #eff6ff;
            border-radius: 10px;
        }

        .filter-item {
            display: table-cell;
            padding: 10px 12px;
            border-right: 1px solid #dbeafe;
        }

        .filter-item:last-child {
            border-right: none;
        }

        .filter-label {
            color: #64748b;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 3px;
        }

        .filter-value {
            color: #0f172a;
            font-weight: 800;
        }

        .note {
            margin: 0 0 14px;
            padding: 9px 12px;
            border: 1px solid #fde68a;
            border-radius: 10px;
            color: #92400e;
            background: #fffbeb;
            font-size: 10.5px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            margin-bottom: 14px;
        }

        .stat {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
            background: #f8fafc;
            min-height: 62px;
        }

        .stat .label {
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: .04em;
        }

        .stat .value {
            font-size: 15px;
            font-weight: 900;
            margin-top: 5px;
            line-height: 1.2;
            word-break: break-word;
        }

        .positive {
            color: #15803d;
        }

        .negative {
            color: #b91c1c;
        }

        .section {
            page-break-inside: avoid;
            margin-top: 14px;
        }

        h2 {
            font-size: 13px;
            margin: 0 0 8px;
            color: #0f172a;
            display: inline-block;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            overflow: hidden;
        }

        th,
        td {
            border-right: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            padding: 7px;
            text-align: left;
            vertical-align: top;
        }

        th:last-child,
        td:last-child {
            border-right: none;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        th {
            background: #eaf2ff;
            color: #334155;
            font-weight: 900;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .035em;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .nowrap {
            white-space: nowrap;
        }

        .muted {
            color: #64748b;
        }

        .footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            color: #64748b;
            font-size: 9.5px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                padding: 0;
            }

            .print-btn {
                display: none;
            }
        }
    </style>
</head>

<body>
    @php
        $money = fn($value) => number_format((float) $value, 0, ',', '.') . ' đ';
        $selectedTeacher = $filters['teacher_id'] ? $teachers->firstWhere('id', (int) $filters['teacher_id'])?->name : null;
    @endphp

    <button class="print-btn" onclick="window.print()">In / Lưu PDF</button>

    <div class="page">
        <header class="report-header">
            <div class="brand">
                <div class="brand-row">
                    <div class="brand-logo">
                        @if ($logoDataUri)
                            <img src="{{ $logoDataUri }}" alt="SmartLMS">
                        @endif
                    </div>
                    <div class="brand-text">
                        <div class="eyebrow">SmartLMS Operations</div>
                        <h1>Báo cáo giảng dạy & thanh toán</h1>
                        <div class="subtitle">Tổng hợp số môn, số buổi, hợp đồng và dòng tiền vận hành.</div>
                    </div>
                </div>
            </div>
            <div class="report-meta">
                <div><strong>Kỳ báo cáo:</strong> {{ $periodLabel }}</div>
                <div><strong>Ngày xuất:</strong> {{ $generatedAt->format('d/m/Y H:i') }}</div>
                <div><strong>Người xuất:</strong> {{ auth()->user()->name }}</div>
            </div>
        </header>

        <div class="filter-bar">
            <div class="filter-item">
                <div class="filter-label">Giáo viên</div>
                <div class="filter-value">{{ $selectedTeacher ?: (auth()->user()->role === 'teacher' ? auth()->user()->name : 'Tất cả') }}</div>
            </div>
            <div class="filter-item">
                <div class="filter-label">Trung tâm</div>
                <div class="filter-value">{{ $filters['center_name'] ?: 'Tất cả' }}</div>
            </div>
            <div class="filter-item">
                <div class="filter-label">Khóa</div>
                <div class="filter-value">{{ $filters['term_code'] ?: 'Tất cả' }}</div>
            </div>
            <div class="filter-item">
                <div class="filter-label">Thời gian</div>
                <div class="filter-value">{{ $periodLabel }}</div>
            </div>
        </div>

        <p class="note">
            Tiêu chí thời gian: dữ liệu giảng dạy lọc theo ngày bắt đầu môn học. Thanh toán đã nhận lọc theo ngày nhận tiền;
            hợp đồng chưa nhận lọc theo ngày ký để vẫn theo dõi được khoản chờ.
        </p>

        <section class="stats">
            <div class="stat">
                <div class="label">Môn đã dạy</div>
                <div class="value">{{ $summary['completed_subjects_count'] }}</div>
            </div>
            <div class="stat">
                <div class="label">Tổng môn</div>
                <div class="value">{{ $summary['subjects_count'] }}</div>
            </div>
            <div class="stat">
                <div class="label">Tổng số buổi</div>
                <div class="value positive">{{ $summary['total_sessions'] }}</div>
            </div>
            <div class="stat">
                <div class="label">Tổng tiền</div>
                <div class="value">{{ $money($summary['total_contract_amount']) }}</div>
            </div>
            <div class="stat">
                <div class="label">Đã nhận</div>
                <div class="value positive">{{ $money($summary['received_amount']) }}</div>
            </div>
            <div class="stat">
                <div class="label">Chưa nhận</div>
                <div class="value negative">{{ $money($summary['remaining_amount']) }}</div>
            </div>
        </section>

        <section class="section">
            @include('reports.partials.print-group-table', [
                'title' => 'Theo trung tâm',
                'label' => 'Trung tâm',
                'rows' => $byCenter,
            ])
        </section>

        <section class="section">
            @include('reports.partials.print-group-table', [
                'title' => 'Theo khóa',
                'label' => 'Khóa',
                'rows' => $byTerm,
            ])
        </section>

        <section class="section">
            <h2>Chi tiết giảng dạy</h2>
            <table>
                <thead>
                    <tr>
                        <th>Môn học</th>
                        <th>Lớp</th>
                        <th>Trung tâm</th>
                        <th>Khóa</th>
                        <th>Số buổi</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teachingRecords as $record)
                        <tr>
                            <td><strong>{{ $record->subject_name }}</strong></td>
                            <td>{{ $record->class_name ?: '--' }}</td>
                            <td>{{ $record->center_name ?: '--' }}</td>
                            <td class="nowrap">{{ $record->term_code ?: '--' }}</td>
                            <td class="nowrap"><strong>{{ $record->planned_sessions }}</strong></td>
                            <td class="nowrap">
                                {{ $record->start_date?->format('d/m/Y') ?: '--' }}
                                -
                                {{ $record->end_date?->format('d/m/Y') ?: '--' }}
                            </td>
                            <td class="nowrap">{{ \App\Models\TeachingRecord::statuses()[$record->status] ?? $record->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="muted">Không có dữ liệu giảng dạy.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Chi tiết thanh toán</h2>
            <table>
                <thead>
                    <tr>
                        <th>Hợp đồng</th>
                        <th>Ngày ký</th>
                        <th>Ngày nhận</th>
                        <th>Tổng tiền</th>
                        <th>Đã nhận</th>
                        <th>Chưa nhận</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contracts as $contract)
                        <tr>
                            <td><strong>{{ $contract->contract_number }}</strong></td>
                            <td class="nowrap">{{ $contract->signed_date?->format('d/m/Y') ?: '--' }}</td>
                            <td class="nowrap">{{ $contract->received_date?->format('d/m/Y') ?: '--' }}</td>
                            <td class="nowrap">{{ $money($contract->total_amount) }}</td>
                            <td class="nowrap positive"><strong>{{ $money($contract->received_amount) }}</strong></td>
                            <td class="nowrap negative"><strong>{{ $money($contract->remaining_amount) }}</strong></td>
                            <td class="nowrap">{{ \App\Models\TeachingContract::statuses()[$contract->status] ?? $contract->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="muted">Không có dữ liệu thanh toán.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <footer class="footer">
            <span>SmartLMS · Báo cáo vận hành</span>
            <span>Tiêu chí thanh toán: ngày nhận tiền / ngày ký nếu chưa nhận</span>
        </footer>
    </div>

    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 250));
    </script>
</body>

</html>
