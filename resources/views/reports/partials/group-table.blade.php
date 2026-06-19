@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . ' đ';
@endphp

<div class="report-card">
    <div class="report-card__head">
        <h2 class="report-card__title">{{ $title }}</h2>
    </div>
    <div class="table-responsive">
        <table class="table report-table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="px-4 py-3">{{ $label }}</th>
                    <th class="px-4 py-3">Số môn</th>
                    <th class="px-4 py-3">Đã dạy</th>
                    <th class="px-4 py-3">Số buổi</th>
                    <th class="px-4 py-3">Tổng tiền</th>
                    <th class="px-4 py-3">Đã nhận</th>
                    <th class="px-4 py-3">Chưa nhận</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-3 fw-bold">{{ $row['label'] }}</td>
                        <td class="px-4 py-3">{{ $row['subjects_count'] }}</td>
                        <td class="px-4 py-3">{{ $row['completed_subjects_count'] }}</td>
                        <td class="px-4 py-3">{{ $row['total_sessions'] }}</td>
                        <td class="px-4 py-3">{{ $money($row['total_contract_amount']) }}</td>
                        <td class="px-4 py-3 text-success fw-bold">{{ $money($row['received_amount']) }}</td>
                        <td class="px-4 py-3 text-danger fw-bold">{{ $money($row['remaining_amount']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Không có dữ liệu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
