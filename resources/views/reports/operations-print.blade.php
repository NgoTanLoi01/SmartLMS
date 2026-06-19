<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Báo cáo giảng dạy & thanh toán</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111827;
            margin: 28px;
            font-size: 12px;
        }

        h1 {
            text-align: center;
            font-size: 22px;
            margin: 0 0 6px;
        }

        .meta {
            text-align: center;
            color: #6b7280;
            margin-bottom: 22px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .stat {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
        }

        .label {
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .value {
            font-size: 16px;
            font-weight: 800;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 7px;
            text-align: left;
        }

        th {
            background: #eff6ff;
            font-weight: 800;
        }

        h2 {
            font-size: 15px;
            margin: 18px 0 8px;
        }

        @media print {
            button {
                display: none;
            }
        }
    </style>
</head>

<body>
    @php
        $money = fn ($value) => number_format((float) $value, 0, ',', '.') . ' đ';
    @endphp

    <button onclick="window.print()" style="position:fixed;right:24px;top:18px;padding:8px 14px">In / Lưu PDF</button>

    <h1>Báo cáo giảng dạy & thanh toán</h1>
    <div class="meta">Ngày xuất: {{ now()->format('d/m/Y H:i') }}</div>

    <div class="stats">
        <div class="stat">
            <div class="label">Môn đã dạy</div>
            <div class="value">{{ $summary['completed_subjects_count'] }}</div>
        </div>
        <div class="stat">
            <div class="label">Tổng số buổi</div>
            <div class="value">{{ $summary['total_sessions'] }}</div>
        </div>
        <div class="stat">
            <div class="label">Tổng tiền</div>
            <div class="value">{{ $money($summary['total_contract_amount']) }}</div>
        </div>
        <div class="stat">
            <div class="label">Đã nhận</div>
            <div class="value">{{ $money($summary['received_amount']) }}</div>
        </div>
        <div class="stat">
            <div class="label">Chưa nhận</div>
            <div class="value">{{ $money($summary['remaining_amount']) }}</div>
        </div>
        <div class="stat">
            <div class="label">Tổng môn</div>
            <div class="value">{{ $summary['subjects_count'] }}</div>
        </div>
    </div>

    @include('reports.partials.print-group-table', ['title' => 'Theo trung tâm', 'label' => 'Trung tâm', 'rows' => $byCenter])
    @include('reports.partials.print-group-table', ['title' => 'Theo khóa', 'label' => 'Khóa', 'rows' => $byTerm])

    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 250));
    </script>
</body>

</html>
