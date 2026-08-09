@extends('layouts.app')

@section('title', 'Sổ điểm · '.$course->title)

@section('content')
    <style>
        .gradebook-table th:first-child,.gradebook-table td:first-child{position:sticky;left:0;z-index:2;background:#fff;min-width:190px}.gradebook-table thead th:first-child{z-index:3}.gradebook-mobile{display:none}.gradebook-cell{min-width:190px}@media(max-width:767.98px){.gradebook-desktop{display:none}.gradebook-mobile{display:block}}
    </style>
    <div class="lms-page">
        <x-ui.page-header title="Sổ điểm chính quy">
            <x-slot:meta><span><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>{{ $course->title }}</span></x-slot:meta>
        </x-ui.page-header>

        @if ($periods->isEmpty())
            <div class="alert alert-info" role="status">
                <strong>Chưa thiết lập kỳ điểm cho khóa học này.</strong>
                Sổ điểm chính quy chỉ mở nhập và chốt điểm sau khi thành phần, trọng số và công thức tính điểm được duyệt.
                Dữ liệu điểm hiện tại vẫn được giữ nguyên trong bảng Điểm danh.
                <div class="mt-2 d-flex flex-wrap gap-2"><a class="btn btn-sm btn-primary" href="{{ route('gradebook.setup.create', $course) }}">Thiết lập Sổ điểm</a><a class="btn btn-sm btn-outline-primary" href="{{ route('attendance.show', $course) }}">Mở bảng điểm hiện tại</a></div>
            </div>
        @else
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                <div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-primary" href="{{ route('gradebook.setup.create', $course) }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Tạo kỳ điểm mới</a><a class="btn btn-sm btn-outline-secondary" href="{{ route('gradebook.history', $period) }}"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Lịch sử thay đổi</a></div>
                @if($period->status === 'open')
                    <form method="POST" action="{{ route('gradebook.periods.close', $period) }}" onsubmit="return confirm('Chỉ đóng kỳ khi mọi học viên đã chốt điểm. Tiếp tục?')">@csrf<button class="btn btn-sm btn-outline-danger" type="submit">Đóng toàn bộ kỳ</button></form>
                @elseif($period->status === 'closed')
                    <form method="POST" action="{{ route('gradebook.periods.reopen', $period) }}" class="d-flex gap-1">@csrf<input class="form-control form-control-sm" name="reason" required maxlength="2000" placeholder="Lý do mở lại kỳ"><button class="btn btn-sm btn-outline-warning text-nowrap" type="submit">Mở lại kỳ</button></form>
                @endif
            </div>

            <form method="GET" class="card border-0 shadow-sm mb-3"><div class="card-body d-flex flex-column flex-md-row align-items-md-end gap-3">
                <div class="flex-grow-1"><label for="gradebook-period" class="form-label">Kỳ điểm</label><select id="gradebook-period" name="period_id" class="form-select">@foreach ($periods as $availablePeriod)<option value="{{ $availablePeriod->id }}" @selected($period->id === $availablePeriod->id)>{{ $availablePeriod->name }}</option>@endforeach</select></div>
                <button type="submit" class="btn btn-primary">Xem kỳ điểm</button><span class="badge {{ $period->status === 'open' ? 'text-bg-success' : ($period->status === 'closed' ? 'text-bg-secondary' : 'text-bg-warning') }} mb-2">{{ ['open' => 'Đang mở', 'closed' => 'Đã đóng', 'draft' => 'Bản nháp'][$period->status] ?? $period->status }}</span>
            </div></form>

            <div class="alert alert-light border" role="note"><strong>Công thức:</strong> @foreach ($period->categories as $category){{ $category->name }} {{ rtrim(rtrim($category->weight_percent, '0'), '.') }}%@if(! $loop->last) + @endif @endforeach · Điểm thiếu: {{ ['block' => 'không cho chốt', 'exclude' => 'không tính', 'zero' => 'tính 0'][$period->missing_policy] ?? $period->missing_policy }} · Làm tròn {{ $period->rounding_precision }} chữ số.</div>

            <div class="gradebook-desktop card border-0 shadow-sm mb-3"><div class="table-responsive"><table class="table align-middle mb-0 gradebook-table">
                <thead><tr><th>Học viên</th>@foreach($period->categories as $category)@foreach($category->items as $item)<th><span class="d-block">{{ $item->name }}</span><small class="text-muted d-block">{{ $category->name }} · {{ $item->item_weight }}× · /{{ $item->max_points }}</small><small class="text-muted d-block">{{ ['legacy_attendance' => 'Điểm danh', 'assignment' => 'Bài tập', 'quiz' => 'Quiz', 'manual' => 'Nhập tay'][$item->source_type] ?? $item->source_type }}</small>@if($period->status !== 'closed')<form method="POST" action="{{ $item->is_locked ? route('gradebook.items.unlock', [$period, $item]) : route('gradebook.items.lock', [$period, $item]) }}" class="mt-1">@csrf<input type="hidden" name="expected_version" value="{{ $item->version }}"><button class="btn btn-sm {{ $item->is_locked ? 'btn-outline-warning' : 'btn-outline-secondary' }}" type="submit">{{ $item->is_locked ? 'Mở khóa' : 'Khóa thành phần' }}</button></form>@else<span class="badge text-bg-secondary">Đã khóa</span>@endif</th>@endforeach @endforeach<th>Trung bình</th><th>Chốt điểm</th></tr></thead>
                <tbody>@forelse($rows as $row)@php($student = $row['student'])<tr><td><strong>{{ $student->name }}</strong><div class="small text-muted">{{ $student->email }}</div></td>@foreach($period->categories as $category)@foreach($category->items as $item)<td>@include('gradebook.partials.grade-cell', compact('period','row','student','category','item'))</td>@endforeach @endforeach<td>@if($row['calculation'])<strong>{{ $row['calculation']['final_score'] }}</strong><div class="small text-muted">Chưa làm tròn: {{ $row['calculation']['unrounded_score'] }}</div>@else<span class="text-warning" title="{{ $row['calculation_error'] }}">Chưa đủ điểm</span>@endif</td><td style="min-width:170px">@if($row['finalization']?->state === 'finalized')<span class="badge text-bg-success mb-1">Đã chốt · {{ $row['finalization']->final_score }}</span>@if($period->status === 'open')<form method="POST" action="{{ route('gradebook.reopen', [$period, $student]) }}">@csrf<input class="form-control form-control-sm mb-1" name="reason" required maxlength="2000" placeholder="Lý do mở lại"><button class="btn btn-sm btn-outline-danger" type="submit">Mở lại điểm</button></form>@endif @else<form method="POST" action="{{ route('gradebook.finalize', [$period, $student]) }}">@csrf<button class="btn btn-sm btn-success" type="submit" @disabled(!$row['calculation'] || $period->status !== 'open')>Chốt điểm</button></form>@endif</td></tr>@empty<tr><td colspan="99"><x-ui.empty-state icon="fa-users" title="Chưa có học viên" description="Khóa học chưa có học viên trong lớp được gán." /></td></tr>@endforelse</tbody>
            </table></div></div>

            <div class="gradebook-mobile">@foreach($rows as $row)<article class="card border-0 shadow-sm mb-3"><div class="card-body"><h2 class="h6">{{ $row['student']->name }}</h2><div class="small text-muted mb-3">{{ $row['student']->email }}</div>@foreach($period->categories as $category)<div class="fw-semibold mb-1">{{ $category->name }} · {{ $category->weight_percent }}%</div>@foreach($category->items as $item)<div class="border-bottom py-2"><div class="mb-1">{{ $item->name }}</div>@include('gradebook.partials.grade-cell', ['period'=>$period,'row'=>$row,'student'=>$row['student'],'category'=>$category,'item'=>$item])</div>@endforeach @endforeach<div class="d-flex justify-content-between pt-3"><span>Trung bình</span><strong>{{ $row['calculation']['final_score'] ?? 'Chưa đủ điểm' }}</strong></div></div></article>@endforeach</div>
            @if($students)<div class="mt-3">{{ $students->links() }}</div>@endif
        @endif
    </div>

    @push('scripts')@vite('resources/js/pages/gradebook.js')@endpush
@endsection
