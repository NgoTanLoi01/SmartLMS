@extends('layouts.app')

@section('title', 'Lịch sử Sổ điểm · '.$period->name)

@section('content')
    <div class="lms-page">
        <x-ui.page-header title="Lịch sử thay đổi điểm">
            <x-slot:meta><span><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>{{ $period->course->title }} · {{ $period->name }}</span></x-slot:meta>
        </x-ui.page-header>

        <form method="GET" class="card border-0 shadow-sm mb-3"><div class="card-body row g-3 align-items-end">
            <div class="col-md-4"><label class="form-label" for="history-item">Thành phần</label><select class="form-select" id="history-item" name="item_id"><option value="">Tất cả thành phần</option>@foreach($period->items as $item)<option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label" for="history-student">ID học viên</label><input class="form-control" id="history-student" type="number" min="1" name="student_id" value="{{ request('student_id') }}" placeholder="Để trống để xem tất cả"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Lọc</button></div>
            <div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="{{ route('gradebook.index', [$period->course, 'period_id' => $period->id]) }}">Quay lại</a></div>
        </div></form>

        <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Thời gian</th><th>Học viên</th><th>Thành phần</th><th>Thao tác</th><th>Người thực hiện</th><th>Lý do/Nguồn</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="text-nowrap">{{ $log->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</td>
                    <td><strong>{{ $log->student?->name ?? 'Học viên #'.$log->user_id }}</strong><div class="small text-muted">#{{ $log->user_id }}</div></td>
                    <td>{{ $log->item?->name ?? 'Điểm cuối kỳ' }}</td>
                    <td><span class="badge text-bg-light border">{{ $log->action }}</span></td>
                    <td>{{ $log->actor?->name ?? 'Hệ thống' }}</td>
                    <td><div>{{ $log->reason ?: '—' }}</div><small class="text-muted">{{ $log->source }}</small></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-ui.empty-state icon="fa-clock-rotate-left" title="Chưa có lịch sử" description="Các lần nhập, điều chỉnh, chốt và mở lại điểm sẽ xuất hiện tại đây." /></td></tr>
            @endforelse
            </tbody>
        </table></div></div>
        <div class="mt-3">{{ $logs->links() }}</div>
    </div>
@endsection
