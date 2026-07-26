@php
    $isEdit = $formSession !== null;
    $selectedCandidates = $isEdit ? $formSession->candidates->pluck('id')->all() : $candidatePool->pluck('id')->all();
@endphp

<form class="session-form" id="session-form-{{ $formId }}" method="POST"
    action="{{ $isEdit ? route('quiz-sessions.update', $formSession) : route('quizzes.sessions.store', $quiz) }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <section class="session-form-section">
        <div class="session-form-title"><i class="fa-solid fa-calendar-check"></i> Thông tin và lịch thi</div>
        <div class="row g-3">
            <div class="col-lg-6">
                <label class="form-label" for="{{ $formId }}-name">Tên ca thi</label>
                <input class="form-control" id="{{ $formId }}-name" name="name" required
                    value="{{ old('name', $formSession?->name) }}" placeholder="Ví dụ: Ca 1 · Phòng A">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label" for="{{ $formId }}-starts">Thời điểm bắt đầu</label>
                <input type="datetime-local" class="form-control" id="{{ $formId }}-starts" name="starts_at" required
                    value="{{ old('starts_at', $formSession?->starts_at?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label" for="{{ $formId }}-ends">Thời điểm kết thúc</label>
                <input type="datetime-local" class="form-control" id="{{ $formId }}-ends" name="ends_at" required
                    value="{{ old('ends_at', $formSession?->ends_at?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}-status">Trạng thái vận hành</label>
                <select class="form-select" id="{{ $formId }}-status" name="status">
                    <option value="scheduled" @selected(($formSession?->status ?? 'scheduled') === 'scheduled')>Đã lên lịch</option>
                    <option value="open" @selected($formSession?->status === 'open')>Cho phép vào thi</option>
                    <option value="closed" @selected($formSession?->status === 'closed')>Đóng ca thi</option>
                    <option value="cancelled" @selected($formSession?->status === 'cancelled')>Hủy ca thi</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}-release">Chính sách công bố kết quả</label>
                <select class="form-select" id="{{ $formId }}-release" name="result_release_policy">
                    <option value="after_session" @selected(($formSession?->result_release_policy ?? 'after_session') === 'after_session')>Tự động sau khi ca thi kết thúc</option>
                    <option value="immediate" @selected($formSession?->result_release_policy === 'immediate')>Ngay khi từng thí sinh nộp bài</option>
                    <option value="manual" @selected($formSession?->result_release_policy === 'manual')>Giáo viên công bố thủ công</option>
                </select>
            </div>
        </div>
    </section>

    <section class="session-form-section mb-3">
        <div class="session-form-title"><i class="fa-solid fa-users"></i> Phân công thí sinh</div>
        <div class="candidate-tools">
            <div class="candidate-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" data-candidate-search placeholder="Tìm theo tên, mã học viên hoặc email..." aria-label="Tìm thí sinh">
            </div>
            <button class="candidate-toggle" type="button" data-candidate-toggle>Chọn/bỏ tất cả</button>
            <span class="candidate-counter"><strong data-selected-count>0</strong>/{{ $candidatePool->count() }} đã chọn</span>
        </div>

        <div class="candidate-list" data-candidate-list>
            @forelse($candidatePool as $candidate)
                @php
                    $nameParts = preg_split('/\s+/', trim($candidate->name));
                    $initials = mb_strtoupper(mb_substr($nameParts[0] ?? '', 0, 1).mb_substr(end($nameParts) ?: '', 0, 1));
                    $isSelected = in_array($candidate->id, $selectedCandidates);
                @endphp
                <div class="candidate-item" data-candidate-item
                    data-search-value="{{ mb_strtolower($candidate->name.' '.$candidate->student_code.' '.$candidate->email) }}">
                    <input class="form-check-input m-0" type="checkbox" name="candidate_ids[]"
                        value="{{ $candidate->id }}" id="{{ $formId }}-candidate-{{ $candidate->id }}"
                        data-candidate-checkbox @checked($isSelected)>
                    <div class="candidate-avatar">{{ $initials ?: 'HV' }}</div>
                    <label for="{{ $formId }}-candidate-{{ $candidate->id }}" style="min-width:0;cursor:pointer">
                        <div class="candidate-name">{{ $candidate->name }}</div>
                        <div class="candidate-code">{{ $candidate->student_code ?: $candidate->email }}</div>
                    </label>
                    <div class="candidate-extra">
                        <label for="{{ $formId }}-extra-{{ $candidate->id }}">Thời gian cộng thêm</label>
                        <div class="input-group input-group-sm">
                            <input type="number" min="0" max="180" class="form-control"
                                id="{{ $formId }}-extra-{{ $candidate->id }}"
                                name="extra_time_minutes[{{ $candidate->id }}]"
                                value="{{ $isEdit ? ($formSession->candidates->firstWhere('id', $candidate->id)?->pivot?->extra_time_minutes ?? 0) : 0 }}"
                                data-extra-time @disabled(!$isSelected)>
                            <span class="input-group-text">phút</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="exam-empty py-4 border-0"><p>Chưa có học viên phù hợp để xếp ca.</p></div>
            @endforelse
        </div>
    </section>

    <div class="session-form-footer">
        <div class="session-form-hint"><i class="fa-solid fa-circle-info"></i> Khuyến nghị 30–50 thí sinh mỗi ca để theo dõi thuận tiện.</div>
        <button class="exam-action exam-action-primary" type="submit" style="background:var(--sl-primary);color:#fff;border-color:var(--sl-primary)"
            @disabled($candidatePool->isEmpty())>
            <i class="fa-solid fa-floppy-disk"></i> {{ $isEdit ? 'Lưu thay đổi' : 'Tạo ca thi' }}
        </button>
    </div>
</form>

<script>
    (() => {
        const form = document.getElementById(@json('session-form-'.$formId));
        if (!form || form.dataset.candidateReady === '1') return;
        form.dataset.candidateReady = '1';
        const search = form.querySelector('[data-candidate-search]');
        const toggle = form.querySelector('[data-candidate-toggle]');
        const count = form.querySelector('[data-selected-count]');
        const items = Array.from(form.querySelectorAll('[data-candidate-item]'));

        const refresh = () => {
            const selected = form.querySelectorAll('[data-candidate-checkbox]:checked').length;
            if (count) count.textContent = selected;
            items.forEach(item => {
                const checkbox = item.querySelector('[data-candidate-checkbox]');
                const extra = item.querySelector('[data-extra-time]');
                if (extra) extra.disabled = !checkbox.checked;
            });
        };

        items.forEach(item => item.querySelector('[data-candidate-checkbox]')?.addEventListener('change', refresh));
        search?.addEventListener('input', () => {
            const query = search.value.trim().toLocaleLowerCase('vi');
            items.forEach(item => item.hidden = query !== '' && !item.dataset.searchValue.includes(query));
        });
        toggle?.addEventListener('click', () => {
            const visible = items.filter(item => !item.hidden);
            const shouldSelect = visible.some(item => !item.querySelector('[data-candidate-checkbox]').checked);
            visible.forEach(item => item.querySelector('[data-candidate-checkbox]').checked = shouldSelect);
            refresh();
        });
        refresh();
    })();
</script>
