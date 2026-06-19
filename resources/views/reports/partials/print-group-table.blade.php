@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . ' đ';
@endphp

<h2>{{ $title }}</h2>
<table>
    <thead>
        <tr>
            <th>{{ $label }}</th>
            <th>Số môn</th>
            <th>Đã dạy</th>
            <th>Số buổi</th>
            <th>Tổng tiền</th>
            <th>Đã nhận</th>
            <th>Chưa nhận</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['subjects_count'] }}</td>
                <td>{{ $row['completed_subjects_count'] }}</td>
                <td>{{ $row['total_sessions'] }}</td>
                <td>{{ $money($row['total_contract_amount']) }}</td>
                <td>{{ $money($row['received_amount']) }}</td>
                <td>{{ $money($row['remaining_amount']) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7">Không có dữ liệu.</td>
            </tr>
        @endforelse
    </tbody>
</table>
