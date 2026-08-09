@extends('layouts.app')

@section('title', 'Sổ điểm · '.$course->title)

@section('content')
    <style>
        .gradebook-table th:first-child,.gradebook-table td:first-child{position:sticky;left:0;z-index:2;background:#fff;min-width:190px}
        .gradebook-table thead th:first-child{z-index:3}.gradebook-mobile{display:none}
        @media(max-width:767.98px){.gradebook-desktop{display:none}.gradebook-mobile{display:block}}
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
                <div class="mt-2">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('attendance.show', $course) }}">Mở bảng điểm hiện tại</a>
                </div>
            </div>
        @else
            <form method="GET" class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-end gap-3">
                    <div class="flex-grow-1"><label for="gradebook-period" class="form-label">Kỳ điểm</label><select id="gradebook-period" name="period_id" class="form-select">
                        @foreach ($periods as $availablePeriod)<option value="{{ $availablePeriod->id }}" @selected($period->id === $availablePeriod->id)>{{ $availablePeriod->name }}</option>@endforeach
                    </select></div>
                    <button type="submit" class="btn btn-primary">Xem kỳ điểm</button>
                </div>
            </form>

            <div class="alert alert-light border" role="note">
                <strong>Công thức:</strong>
                @foreach ($period->categories as $category)
                    {{ $category->name }} {{ rtrim(rtrim($category->weight_percent, '0'), '.') }}%@if(! $loop->last) + @endif
                @endforeach
                · Missing: {{ $period->missing_policy }} · Làm tròn {{ $period->rounding_precision }} chữ số ({{ $period->rounding_mode }}).
                <div class="small text-muted mt-1">Nguồn đọc hiện tại: {{ config('gradebook.read_source') }}. Màn hình legacy chưa bị thay thế khi reconciliation chưa đạt.</div>
            </div>

            <div class="gradebook-desktop card border-0 shadow-sm mb-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 gradebook-table">
                        <thead><tr><th>Học viên</th>@foreach($period->categories as $category)@foreach($category->items as $item)<th><span class="d-block">{{ $item->name }}</span><small class="text-muted">{{ $category->name }} · {{ $item->item_weight }}× · /{{ $item->max_points }}</small></th>@endforeach @endforeach<th>Trung bình</th><th>Chốt điểm</th></tr></thead>
                        <tbody>
                        @forelse($rows as $row)
                            @php($student = $row['student'])
                            <tr>
                                <td><strong>{{ $student->name }}</strong><div class="small text-muted">{{ $student->email }}</div></td>
                                @foreach($period->categories as $category)@foreach($category->items as $item)
                                    @php($grade = $item->grades->firstWhere('user_id', $student->id))
                                    <td style="min-width:180px">
                                        <form method="POST" action="{{ route('gradebook.grades.record', [$period, $item, $student]) }}" class="d-flex gap-1">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="graded"><input type="hidden" name="expected_version" value="{{ $grade?->version }}">
                                            <input class="form-control form-control-sm" aria-label="Điểm {{ $item->name }} của {{ $student->name }}" name="raw_points" type="number" min="0" @unless($category->allow_over_max) max="{{ $item->max_points }}" @endunless step="0.0001" value="{{ $grade?->raw_points }}" required {{ $row['finalization']?->state === 'finalized' ? 'disabled' : '' }}>
                                            <button class="btn btn-sm btn-outline-primary" type="submit" title="Lưu điểm" {{ $row['finalization']?->state === 'finalized' ? 'disabled' : '' }}><i class="fa-solid fa-check" aria-hidden="true"></i></button>
                                        </form>
                                        @if($grade && $grade->effective_points !== $grade->raw_points)<small class="text-muted">Sau điều chỉnh: {{ $grade->effective_points }}</small>@endif
                                    </td>
                                @endforeach @endforeach
                                <td>@if($row['calculation'])<strong>{{ $row['calculation']['final_score'] }}</strong><div class="small text-muted">Chưa làm tròn: {{ $row['calculation']['unrounded_score'] }}</div>@else<span class="text-warning" title="{{ $row['calculation_error'] }}">Chưa đủ điểm</span>@endif</td>
                                <td style="min-width:150px">
                                    @if($row['finalization']?->state === 'finalized')
                                        <span class="badge text-bg-success mb-1">Đã chốt · {{ $row['finalization']->final_score }}</span>
                                        <form method="POST" action="{{ route('gradebook.reopen', [$period, $student]) }}">@csrf<input class="form-control form-control-sm mb-1" name="reason" required maxlength="2000" placeholder="Lý do mở lại"><button class="btn btn-sm btn-outline-danger" type="submit">Mở lại</button></form>
                                    @else
                                        <form method="POST" action="{{ route('gradebook.finalize', [$period, $student]) }}">@csrf<button class="btn btn-sm btn-success" type="submit" {{ $row['calculation'] ? '' : 'disabled' }}>Chốt điểm</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty<tr><td colspan="99"><x-ui.empty-state icon="fa-users" title="Chưa có học viên" description="Khóa học chưa có học viên trong lớp được gán." /></td></tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="gradebook-mobile">
                @foreach($rows as $row)
                    <article class="card border-0 shadow-sm mb-3"><div class="card-body"><h2 class="h6">{{ $row['student']->name }}</h2><div class="small text-muted mb-3">{{ $row['student']->email }}</div>
                        @foreach($period->categories as $category)<div class="fw-semibold mb-1">{{ $category->name }} · {{ $category->weight_percent }}%</div>@foreach($category->items as $item)@php($grade = $item->grades->firstWhere('user_id', $row['student']->id))<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $item->name }}</span><strong>{{ $grade?->effective_points ?? '—' }}/{{ $item->max_points }}</strong></div>@endforeach @endforeach
                        <div class="d-flex justify-content-between pt-3"><span>Trung bình</span><strong>{{ $row['calculation']['final_score'] ?? 'Chưa đủ điểm' }}</strong></div>
                    </div></article>
                @endforeach
            </div>
            @if($students)<div class="mt-3">{{ $students->links() }}</div>@endif
        @endif
    </div>
@endsection
