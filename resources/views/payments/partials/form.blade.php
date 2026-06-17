@php
    $contract = $contract ?? null;
    $statuses = \App\Models\TeachingContract::statuses();
    $selectedRecordIds = collect(old('teaching_record_ids', $contract?->teachingRecords?->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp

<div class="row g-3">
    @if (auth()->user()->role === 'admin')
        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Giáo viên</label>
            <select name="teacher_id" class="form-select" required>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(old('teacher_id', $contract?->teacher_id ?? auth()->id()) == $teacher->id)>
                        {{ $teacher->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Số hợp đồng</label>
        <input type="text" name="contract_number" class="form-control"
            value="{{ old('contract_number', $contract?->contract_number) }}" required placeholder="VD: 597/PMG-AV">
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Ngày ký</label>
        <input type="date" name="signed_date" class="form-control"
            value="{{ old('signed_date', $contract?->signed_date?->format('Y-m-d')) }}">
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Tổng tiền</label>
        <input type="number" name="total_amount" min="0" step="1000" class="form-control"
            value="{{ old('total_amount', $contract?->total_amount ?? 0) }}" required>
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Trạng thái</label>
        <select name="status" class="form-select" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $contract?->status ?? 'unpaid') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Số tiền đã nhận</label>
        <input type="number" name="received_amount" min="0" step="1000" class="form-control"
            value="{{ old('received_amount', $contract?->received_amount ?? 0) }}">
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Ngày nhận</label>
        <input type="date" name="received_date" class="form-control"
            value="{{ old('received_date', $contract?->received_date?->format('Y-m-d')) }}">
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Gắn dòng giảng dạy</label>
        <div class="record-picker">
            @forelse ($teachingRecords as $record)
                <label class="d-flex gap-2 align-items-start py-2 border-bottom">
                    <input type="checkbox" name="teaching_record_ids[]" value="{{ $record->id }}"
                        class="form-check-input mt-1" @checked(in_array($record->id, $selectedRecordIds, true))>
                    <span>
                        <span class="fw-semibold">{{ $record->subject_name }}</span>
                        <span class="text-muted small d-block">
                            {{ $record->class_name ?: 'Chưa có lớp' }}
                            @if ($record->center_name)
                                · {{ $record->center_name }}
                            @endif
                            @if ($record->term_code)
                                · {{ $record->term_code }}
                            @endif
                        </span>
                    </span>
                </label>
            @empty
                <div class="text-muted small">Chưa có dòng giảng dạy để gắn hợp đồng.</div>
            @endforelse
        </div>
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Ghi chú</label>
        <textarea name="note" rows="3" class="form-control" placeholder="Ghi chú thêm nếu có">{{ old('note', $contract?->note) }}</textarea>
    </div>
</div>
