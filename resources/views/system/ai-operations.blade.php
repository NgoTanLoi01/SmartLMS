@extends('layouts.app')

@section('title', 'Theo dõi AI & Queue')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h3 class="fw-bold mb-1">Theo dõi AI & Queue</h3>
            <p class="text-muted mb-0">Trạng thái xử lý, lỗi, token và chi phí ước tính trong 30 ngày.</p>
        </div>
        <span class="badge bg-secondary">Tự làm mới khi tải lại trang</span>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['Tổng tác vụ', (int) $summary->total, 'primary'],
            ['Đang chờ/chạy', (int) $summary->active, 'warning'],
            ['Thất bại', (int) $summary->failed, 'danger'],
            ['Tổng token', number_format((int) $summary->total_tokens), 'info'],
            ['Chi phí ước tính', '$' . number_format((float) $summary->estimated_cost_usd, 6), 'success'],
        ] as [$label, $value, $color])
            <div class="col-6 col-lg">
                <div class="card border-0 shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fs-4 fw-bold text-{{ $color }}">{{ $value }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Thời gian</th><th>Chức năng</th><th>Trạng thái</th><th>Token</th><th>Chi phí</th><th>Thời gian chạy</th><th>Lỗi</th></tr></thead>
                <tbody>
                @forelse ($operations as $operation)
                    @php $badge = ['completed' => 'success', 'failed' => 'danger', 'processing' => 'primary', 'queued' => 'warning'][$operation->status] ?? 'secondary'; @endphp
                    <tr>
                        <td class="text-nowrap">{{ $operation->created_at->format('d/m/Y H:i:s') }}</td>
                        <td><div class="fw-semibold">{{ $operation->feature }}</div><small class="text-muted">{{ $operation->provider }} · {{ $operation->model }}</small></td>
                        <td><span class="badge bg-{{ $badge }}">{{ $operation->status }}</span><div class="small text-muted mt-1">Lần chạy: {{ $operation->attempts }}</div></td>
                        <td>{{ number_format($operation->total_tokens) }}</td>
                        <td>${{ number_format((float) $operation->estimated_cost_usd, 8) }}</td>
                        <td>{{ $operation->duration_ms ? number_format($operation->duration_ms) . ' ms' : '—' }}</td>
                        <td style="max-width:360px"><span class="text-danger small">{{ $operation->error_message ? Str::limit($operation->error_message, 180) : '—' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">Chưa có tác vụ nào được ghi nhận.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($operations->hasPages())<div class="card-footer">{{ $operations->links() }}</div>@endif
    </div>
</div>
@endsection
