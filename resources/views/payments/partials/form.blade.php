@php
    $contract = $contract ?? null;
    $statuses = \App\Models\TeachingContract::statuses();
    $selectedRecordIds = collect(old('teaching_record_ids', $contract?->teachingRecords?->pluck('id')->all() ?? []))
        ->map(fn($id) => (int) $id)
        ->all();
@endphp

<style>
    /* ── Form tokens ── */
    .pf .form-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #6b7a8d;
        margin-bottom: 5px;
    }

    .pf .form-control,
    .pf .form-select {
        font-size: 13.5px;
        border-color: #e8edf3;
        border-radius: 8px;
        background: #f8fafc;
        transition: border-color .15s, box-shadow .15s;
    }

    .pf .form-control:focus,
    .pf .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        background: #fff;
    }

    .pf .form-text {
        font-size: 11.5px;
        color: #94a3b8;
        margin-top: 4px;
    }

    /* ── Section divider ── */
    .pf-section {
        border-top: 1px solid #f1f5f9;
        padding-top: 18px;
        margin-top: 4px;
    }

    .pf-section-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #94a3b8;
        margin-bottom: 14px;
    }

    /* ── Teaching record picker ── */
    .record-picker-wrap {
        border: 1px solid #e8edf3;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
    }

    .record-picker-search {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }

    .record-picker-search i {
        color: #94a3b8;
        font-size: 12px;
    }

    .record-picker-search input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 13px;
        color: #0f1c2e;
        width: 100%;
    }

    .record-picker-search input::placeholder {
        color: #b0bac6;
    }

    .record-picker-stats {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 7px 12px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }

    .record-picker-stats span {
        font-size: 11.5px;
        color: #94a3b8;
    }

    .record-picker-stats button {
        font-size: 11.5px;
        font-weight: 600;
        color: #2563eb;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
    }

    .record-picker-stats button:hover {
        text-decoration: underline;
    }

    .record-picker-list {
        max-height: 240px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #e2e8f0 transparent;
    }

    .record-picker-list::-webkit-scrollbar {
        width: 4px;
    }

    .record-picker-list::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 4px;
    }

    .record-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        cursor: pointer;
        border-bottom: 1px solid #f8fafc;
        transition: background .1s;
        user-select: none;
    }

    .record-item:last-child {
        border-bottom: none;
    }

    .record-item:hover {
        background: #f8fafc;
    }

    .record-item.is-checked {
        background: #eff6ff;
    }

    .record-item.is-hidden {
        display: none;
    }

    .record-item input[type="checkbox"] {
        flex-shrink: 0;
        width: 15px;
        height: 15px;
        accent-color: #2563eb;
        cursor: pointer;
    }

    .record-item__body {
        flex: 1;
        min-width: 0;
    }

    .record-item__name {
        font-size: 13px;
        font-weight: 600;
        color: #0f1c2e;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .record-item__meta {
        font-size: 11.5px;
        color: #94a3b8;
        margin-top: 1px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .record-item__badge {
        flex-shrink: 0;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .record-picker-empty {
        padding: 28px;
        text-align: center;
        font-size: 13px;
        color: #94a3b8;
        display: none;
    }

    .record-picker-empty.show {
        display: block;
    }

    /* ── Selected chips preview ── */
    .selected-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 10px;
        min-height: 0;
    }

    .selected-preview:empty::before {
        content: 'Chưa chọn dòng nào';
        font-size: 12px;
        color: #b0bac6;
    }

    .sel-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #eff6ff;
        color: #1d4ed8;
        border-radius: 999px;
        padding: 3px 10px;
        font-size: 12px;
        font-weight: 600;
    }

    .sel-chip__remove {
        cursor: pointer;
        color: #93c5fd;
        font-size: 11px;
        line-height: 1;
        background: none;
        border: none;
        padding: 0;
    }

    .sel-chip__remove:hover {
        color: #1d4ed8;
    }
</style>

<div class="row g-3 pf">

    {{-- ── Section: Hợp đồng ── --}}
    @if (auth()->user()->role === 'admin')
        <div class="col-12 col-md-6">
            <label class="form-label">Giáo viên</label>
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
        <label class="form-label">Số hợp đồng <span style="color:#e53e3e">*</span></label>
        <input type="text" name="contract_number" class="form-control"
            value="{{ old('contract_number', $contract?->contract_number) }}" required placeholder="VD: 597/PMG-AV">
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Ngày ký</label>
        <input type="date" name="signed_date" class="form-control"
            value="{{ old('signed_date', $contract?->signed_date?->format('Y-m-d')) }}">
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Tổng tiền <span style="color:#e53e3e">*</span></label>
        <input type="number" name="total_amount" min="0" step="1000" class="form-control"
            value="{{ old('total_amount', $contract?->total_amount ?? 0) }}" required>
    </div>

    {{-- ── Section: Thanh toán ── --}}
    <div class="col-12 pf-section">
        <div class="pf-section-label">Thanh toán</div>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label">Trạng thái <span style="color:#e53e3e">*</span></label>
                <select name="status" class="form-select" required>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $contract?->status ?? 'unpaid') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Số tiền đã nhận</label>
                <input type="number" name="received_amount" min="0" step="1000" class="form-control"
                    value="{{ old('received_amount', $contract?->received_amount ?? 0) }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Ngày nhận</label>
                <input type="date" name="received_date" class="form-control"
                    value="{{ old('received_date', $contract?->received_date?->format('Y-m-d')) }}">
            </div>
        </div>
    </div>

    {{-- ── Section: Gắn giảng dạy ── --}}
    <div class="col-12 pf-section">
        <div class="pf-section-label">Gắn dòng giảng dạy</div>

        <div class="record-picker-wrap" id="recordPickerWrap_{{ $contract?->id ?? 'new' }}">
            <div class="record-picker-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Tìm theo tên môn, lớp, trung tâm..." class="rp-search-input">
            </div>
            <div class="record-picker-stats">
                <span class="rp-count-label">{{ count($selectedRecordIds) }} đã chọn · {{ $teachingRecords->count() }}
                    môn</span>
                <button type="button" class="rp-clear-btn"
                    style="{{ count($selectedRecordIds) === 0 ? 'display:none' : '' }}">Bỏ chọn tất cả</button>
            </div>
            <div class="record-picker-list">
                @forelse ($teachingRecords as $record)
                    @php
                        $isChecked = in_array($record->id, $selectedRecordIds, true);
                        $meta = collect([$record->class_name, $record->center_name, $record->term_code])
                            ->filter()
                            ->join(' · ');
                    @endphp
                    <label class="record-item {{ $isChecked ? 'is-checked' : '' }}"
                        data-search="{{ strtolower($record->subject_name . ' ' . $meta) }}">
                        <input type="checkbox" name="teaching_record_ids[]" value="{{ $record->id }}"
                            @checked($isChecked)>
                        <div class="record-item__body">
                            <div class="record-item__name">{{ $record->subject_name }}</div>
                            @if ($meta)
                                <div class="record-item__meta">{{ $meta }}</div>
                            @endif
                        </div>
                        @if ($record->term_code)
                            <span class="record-item__badge">{{ $record->term_code }}</span>
                        @endif
                    </label>
                @empty
                    <div class="record-picker-empty show">Chưa có dòng giảng dạy để gắn hợp đồng.</div>
                @endforelse
                <div class="record-picker-empty">Không tìm thấy kết quả.</div>
            </div>
        </div>

        {{-- Selected preview chips --}}
        <div class="selected-preview" id="selectedPreview_{{ $contract?->id ?? 'new' }}">
            @foreach ($teachingRecords->whereIn('id', $selectedRecordIds) as $record)
                <span class="sel-chip" data-id="{{ $record->id }}">
                    {{ $record->subject_name }}
                    <button type="button" class="sel-chip__remove" title="Bỏ chọn" aria-label="Bỏ chọn"><x-ui.icon name="close" /></button>
                </span>
            @endforeach
        </div>
    </div>

    {{-- ── Section: Khác ── --}}
    <div class="col-12 pf-section">
        <div class="pf-section-label">Khác</div>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Link minh chứng</label>
                <input type="url" name="evidence_url" class="form-control"
                    value="{{ old('evidence_url', $contract?->evidence_url) }}"
                    placeholder="https://drive.google.com/file/d/.../view">
                <div class="form-text">Link Google Drive hoặc link minh chứng hợp đồng / thanh toán.</div>
            </div>
            <div class="col-12">
                <label class="form-label">Ghi chú</label>
                <textarea name="note" rows="2" class="form-control" placeholder="Ghi chú thêm nếu có">{{ old('note', $contract?->note) }}</textarea>
            </div>
        </div>
    </div>

</div>

<script>
    (function() {
        // Support multiple form instances (create + edit modals)
        const uid = '{{ $contract?->id ?? 'new' }}';
        const wrap = document.getElementById('recordPickerWrap_' + uid);
        const preview = document.getElementById('selectedPreview_' + uid);
        if (!wrap) return;

        const searchInput = wrap.querySelector('.rp-search-input');
        const countLabel = wrap.querySelector('.rp-count-label');
        const clearBtn = wrap.querySelector('.rp-clear-btn');
        const emptyMsg = wrap.querySelectorAll('.record-picker-empty');
        const items = wrap.querySelectorAll('.record-item');
        const totalCount = {{ $teachingRecords->count() }};

        // ── Sync chip preview with checkbox state ──
        function getCheckedItems() {
            return [...items].filter(el => el.querySelector('input[type="checkbox"]').checked);
        }

        function renderPreview() {
            const checked = getCheckedItems();
            preview.innerHTML = '';
            checked.forEach(item => {
                const id = item.querySelector('input').value;
                const name = item.querySelector('.record-item__name').textContent.trim();
                const chip = document.createElement('span');
                chip.className = 'sel-chip';
                chip.dataset.id = id;
                chip.innerHTML =
                    `${name}<button type="button" class="sel-chip__remove" title="Bỏ chọn" aria-label="Bỏ chọn"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>`;
                chip.querySelector('button').addEventListener('click', () => {
                    const cb = item.querySelector('input');
                    cb.checked = false;
                    item.classList.remove('is-checked');
                    renderPreview();
                    updateStats();
                });
                preview.appendChild(chip);
            });
        }

        function updateStats() {
            const n = getCheckedItems().length;
            countLabel.textContent = n + ' đã chọn · ' + totalCount + ' môn';
            clearBtn.style.display = n > 0 ? '' : 'none';
        }

        // ── Checkbox toggle ──
        items.forEach(item => {
            item.addEventListener('click', (e) => {
                if (e.target.tagName === 'INPUT') {
                    item.classList.toggle('is-checked', item.querySelector('input').checked);
                    renderPreview();
                    updateStats();
                }
            });
        });

        // ── Search filter ──
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.toLowerCase().trim();
            let visible = 0;
            items.forEach(item => {
                const match = !q || item.dataset.search.includes(q);
                item.classList.toggle('is-hidden', !match);
                if (match) visible++;
            });
            // Show "no results" only in the unfound empty state (last .record-picker-empty)
            emptyMsg.forEach((el, i) => {
                if (i === emptyMsg.length - 1) {
                    el.classList.toggle('show', visible === 0 && totalCount > 0);
                }
            });
        });

        // ── Clear all ──
        clearBtn.addEventListener('click', () => {
            items.forEach(item => {
                item.querySelector('input').checked = false;
                item.classList.remove('is-checked');
            });
            renderPreview();
            updateStats();
        });

        // ── Init ──
        renderPreview();
        updateStats();
    })();
</script>
