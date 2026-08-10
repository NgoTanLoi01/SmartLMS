@php
    $grade = $item->grades->firstWhere('user_id', $student->id);
    $isFinalized = $row['finalization']?->state === 'finalized';
    $isLegacy = $item->source_type === 'legacy_attendance';
    $canEdit = !$isFinalized && !$item->is_locked && $period->status === 'open' && !$isLegacy;
    $status = $grade?->status ?? 'ungraded';
    $statusLabels = [
        'ungraded' => 'Chưa chấm',
        'missing' => 'Thiếu điểm',
        'excused' => 'Được miễn',
        'graded' => 'Có điểm',
        'excluded' => 'Không tính',
    ];
    $reversedIds = $grade?->adjustments?->where('type', 'reversal')->pluck('reverses_adjustment_id') ?? collect();
    $activeAdjustments = $grade?->adjustments?->where('type', '!=', 'reversal') ?? collect();
    $adjustmentIcons = [
        'bonus' => 'fa-arrow-up text-success',
        'penalty' => 'fa-arrow-down text-danger',
        'override' => 'fa-pen text-primary',
    ];
    $adjustmentLabels = ['bonus' => 'Điểm cộng', 'penalty' => 'Điểm trừ', 'override' => 'Ghi đè'];
@endphp

<div class="gradebook-cell border-start border-3 ps-2" data-grade-cell data-status="{{ $status }}"
    style="border-color: var(--status-{{ $status }}-bd) !important; background: var(--status-{{ $status }}-bg);">

    @if ($isLegacy)
        <div class="d-flex align-items-center justify-content-between">
            <div><strong>{{ $grade?->effective_points ?? '—' }}</strong><span
                    class="text-muted">/{{ $item->max_points }}</span></div>
            <span class="badge text-bg-light border small">Điểm danh</span>
        </div>
        <a class="small d-inline-block mt-1" href="{{ route('attendance.show', $period->course_id) }}">Sửa tại bảng Điểm
            danh →</a>
    @else
        <form method="POST" action="{{ route('gradebook.grades.record', [$period, $item, $student]) }}"
            class="d-flex gap-1 align-items-center">
            @csrf @method('PUT')
            <input type="hidden" name="expected_version" value="{{ $grade?->version }}">
            <select class="form-select form-select-sm" name="status" data-grade-status
                aria-label="Trạng thái {{ $item->name }} của {{ $student->name }}" style="max-width:8.5rem"
                @disabled(!$canEdit)>
                @foreach ($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input class="form-control form-control-sm" name="raw_points" data-grade-points type="number"
                min="0" @unless ($category->allow_over_max) max="{{ $item->max_points }}" @endunless
                step="0.0001" value="{{ $grade?->raw_points }}" placeholder="/{{ $item->max_points }}"
                aria-label="Điểm {{ $item->name }} của {{ $student->name }}" style="max-width:5.5rem"
                @disabled(!$canEdit || $status !== 'graded')>
            <button class="btn btn-sm btn-outline-primary px-2" type="submit" title="Lưu điểm" aria-label="Lưu điểm"
                @disabled(!$canEdit)>
                <i class="fa-solid fa-check" aria-hidden="true"></i>
            </button>
        </form>
    @endif

    @if ($grade && $grade->effective_points !== $grade->raw_points)
        <small class="text-muted d-block mt-1"
            title="Điểm gốc {{ $grade->raw_points }}, sau điều chỉnh {{ $grade->effective_points }}">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Sau điều chỉnh:
            <strong>{{ $grade->effective_points }}</strong>
        </small>
    @endif

    @if ($grade && !$isFinalized && !$item->is_locked && $period->status === 'open')
        <details class="mt-1">
            <summary class="small text-primary">
                Điểm cộng/điều chỉnh
                @if ($activeAdjustments->count())
                    <span class="badge text-bg-light border rounded-pill">{{ $activeAdjustments->count() }}</span>
                @endif
            </summary>

            <div class="border rounded p-2 mt-1 bg-white">
                @foreach ($activeAdjustments as $adjustment)
                    <div
                        class="d-flex justify-content-between align-items-start small py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <i class="fa-solid {{ $adjustmentIcons[$adjustment->type] ?? 'fa-circle' }}"
                                aria-hidden="true"></i>
                            <strong>{{ $adjustmentLabels[$adjustment->type] ?? $adjustment->type }}
                                {{ $adjustment->amount }}</strong>
                            <div class="text-muted">{{ $adjustment->reason }}</div>
                        </div>
                        @unless ($reversedIds->contains($adjustment->id))
                            <details class="ms-2">
                                <summary class="text-danger" style="cursor:pointer">Hoàn tác</summary>
                                <form method="POST"
                                    action="{{ route('gradebook.adjustments.reverse', [$period, $adjustment]) }}"
                                    class="mt-1" style="min-width:180px">
                                    @csrf
                                    <input type="hidden" name="idempotency_key"
                                        value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                                    <div class="input-group input-group-sm">
                                        <input class="form-control" name="reason" maxlength="2000"
                                            placeholder="Lý do hoàn tác" required>
                                        <button class="btn btn-outline-danger" type="submit">OK</button>
                                    </div>
                                </form>
                            </details>
                        @else
                            <span class="badge text-bg-secondary">Đã hoàn tác</span>
                        @endunless
                    </div>
                @endforeach

                <form method="POST" action="{{ route('gradebook.adjustments.store', [$period, $grade]) }}"
                    class="d-grid gap-1 {{ $activeAdjustments->count() ? 'border-top pt-2 mt-2' : '' }}">
                    @csrf
                    <input type="hidden" name="idempotency_key"
                        value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <select class="form-select form-select-sm" name="type" required>
                        <option value="bonus">Điểm cộng</option>
                        <option value="penalty">Điểm trừ</option>
                        <option value="override">Ghi đè điểm</option>
                    </select>
                    <input class="form-control form-control-sm" type="number" min="0" step="0.0001"
                        name="amount" placeholder="Giá trị" required>
                    <input class="form-control form-control-sm" name="reason" maxlength="2000"
                        placeholder="Lý do bắt buộc" required>
                    <button class="btn btn-sm btn-outline-primary" type="submit">Ghi điều chỉnh</button>
                </form>
            </div>
        </details>
    @endif
</div>
