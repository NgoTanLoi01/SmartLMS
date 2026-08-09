@php
    $grade = $item->grades->firstWhere('user_id', $student->id);
    $isFinalized = $row['finalization']?->state === 'finalized';
    $isLegacy = $item->source_type === 'legacy_attendance';
    $canEdit = !$isFinalized && !$item->is_locked && $period->status === 'open' && !$isLegacy;
    $status = $grade?->status ?? 'ungraded';
    $statusLabels = ['ungraded' => 'Chưa chấm', 'missing' => 'Thiếu điểm', 'excused' => 'Được miễn', 'graded' => 'Có điểm', 'excluded' => 'Không tính'];
    $reversedIds = $grade?->adjustments?->where('type', 'reversal')->pluck('reverses_adjustment_id') ?? collect();
@endphp

<div class="gradebook-cell" data-grade-cell>
    @if($isLegacy)
        <div><strong>{{ $grade?->effective_points ?? '—' }}</strong>/{{ $item->max_points }}</div>
        <span class="badge text-bg-light border">Đồng bộ từ Điểm danh</span>
        <div class="mt-1"><a class="small" href="{{ route('attendance.show', $period->course_id) }}">Sửa tại bảng Điểm danh</a></div>
    @else
        <form method="POST" action="{{ route('gradebook.grades.record', [$period, $item, $student]) }}" class="d-grid gap-1">
            @csrf @method('PUT')
            <input type="hidden" name="expected_version" value="{{ $grade?->version }}">
            <select class="form-select form-select-sm" name="status" data-grade-status aria-label="Trạng thái {{ $item->name }} của {{ $student->name }}" @disabled(!$canEdit)>
                @foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach
            </select>
            <div class="d-flex gap-1">
                <input class="form-control form-control-sm" name="raw_points" data-grade-points type="number" min="0" @unless($category->allow_over_max) max="{{ $item->max_points }}" @endunless step="0.0001" value="{{ $grade?->raw_points }}" placeholder="Điểm /{{ $item->max_points }}" aria-label="Điểm {{ $item->name }} của {{ $student->name }}" @disabled(!$canEdit || $status !== 'graded')>
                <button class="btn btn-sm btn-outline-primary" type="submit" title="Lưu điểm" @disabled(!$canEdit)><i class="fa-solid fa-check" aria-hidden="true"></i></button>
            </div>
        </form>
    @endif

    @if($grade && $grade->effective_points !== $grade->raw_points)
        <small class="text-muted d-block mt-1">Sau điều chỉnh: {{ $grade->effective_points }}</small>
    @endif

    @if($grade && !$isFinalized && !$item->is_locked && $period->status === 'open')
        <details class="mt-2">
            <summary class="small text-primary">Điểm cộng/điều chỉnh</summary>
            <form method="POST" action="{{ route('gradebook.adjustments.store', [$period, $grade]) }}" class="border rounded p-2 mt-1 d-grid gap-1">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <select class="form-select form-select-sm" name="type" required><option value="bonus">Điểm cộng</option><option value="penalty">Điểm trừ</option><option value="override">Ghi đè điểm</option></select>
                <input class="form-control form-control-sm" type="number" min="0" step="0.0001" name="amount" placeholder="Giá trị" required>
                <input class="form-control form-control-sm" name="reason" maxlength="2000" placeholder="Lý do bắt buộc" required>
                <button class="btn btn-sm btn-outline-primary" type="submit">Ghi điều chỉnh</button>
            </form>
            @foreach($grade->adjustments->where('type', '!=', 'reversal') as $adjustment)
                <div class="small border-top mt-2 pt-2">
                    <strong>{{ $adjustment->type }} {{ $adjustment->amount }}</strong> · {{ $adjustment->reason }}
                    @unless($reversedIds->contains($adjustment->id))
                        <form method="POST" action="{{ route('gradebook.adjustments.reverse', [$period, $adjustment]) }}" class="mt-1">
                            @csrf<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <div class="input-group input-group-sm"><input class="form-control" name="reason" maxlength="2000" placeholder="Lý do hoàn tác" required><button class="btn btn-outline-danger" type="submit">Hoàn tác</button></div>
                        </form>
                    @else<span class="badge text-bg-secondary">Đã hoàn tác</span>@endunless
                </div>
            @endforeach
        </details>
    @endif
</div>
