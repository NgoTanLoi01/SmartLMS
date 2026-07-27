@extends('layouts.app')

@section('title', 'Ngân hàng câu hỏi')

@section('content')
    @vite('resources/css/pages/question-bank.css')

    {{-- ── Page Header ── --}}
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-database"
                    style="color:#2563eb; font-size:18px; margin-right:10px;"></i>Ngân hàng câu hỏi</h1>
            <p class="page-subtitle">Soạn câu hỏi hỗn hợp, quản lý ngữ liệu và tự động chấm điểm</p>
        </div>
        <div class="btn-group-actions">
            <button class="btn-act btn-act-ghost" data-bs-toggle="modal" data-bs-target="#passageModal">
                <i class="fa-solid fa-file-lines"></i> Ngữ liệu
            </button>
            <button class="btn-act btn-act-ghost" data-bs-toggle="modal" data-bs-target="#addQuestionBankModal">
                <i class="fa-solid fa-layer-group"></i> Tạo bank
            </button>
            <button class="btn-act btn-act-ghost" data-bs-toggle="modal" data-bs-target="#attachQuestionBankModal">
                <i class="fa-solid fa-link"></i> Gắn bank
            </button>
            <button class="btn-act btn-act-ghost-green" data-bs-toggle="modal" data-bs-target="#importQuestionModal">
                <i class="fa-solid fa-file-excel"></i> Nhập từ Excel
            </button>
            <a href="{{ route('quizzes.ai_generate') }}" class="btn-act btn-act-ghost">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Tạo bằng AI
            </a>
            <button class="btn-act btn-act-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                <i class="fa-solid fa-plus"></i> Thêm câu hỏi
            </button>
        </div>
    </div>

    {{-- ── Filter bar ── --}}
    <div class="filter-bar">
        <form action="{{ route('questions.index') }}" method="GET" class="filter-group">
            <label class="filter-label">Lọc theo khóa học</label>
            <select name="course_id" onchange="this.form.submit()">
                <option value="">Tất cả khóa học</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                        {{ $course->title }}
                    </option>
                @endforeach
            </select>
            @if (request('question_bank_id'))
                <input type="hidden" name="question_bank_id" value="{{ request('question_bank_id') }}">
            @endif
            @if (request('question_type'))
                <input type="hidden" name="question_type" value="{{ request('question_type') }}">
            @endif
        </form>

        <form action="{{ route('questions.index') }}" method="GET" class="filter-group">
            <label class="filter-label">Loại câu hỏi</label>
            <select name="question_type" onchange="this.form.submit()">
                <option value="">Tất cả hình thức</option>
                @foreach($questionTypeLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('question_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if(request('course_id'))<input type="hidden" name="course_id" value="{{ request('course_id') }}">@endif
            @if(request('question_bank_id'))<input type="hidden" name="question_bank_id" value="{{ request('question_bank_id') }}">@endif
        </form>

        <form action="{{ route('questions.index') }}" method="GET" class="filter-group">
            <label class="filter-label">Lọc theo ngân hàng</label>
            <select name="question_bank_id" onchange="this.form.submit()">
                <option value="">Tất cả ngân hàng</option>
                @foreach ($questionBanks as $bank)
                    <option value="{{ $bank->id }}" {{ request('question_bank_id') == $bank->id ? 'selected' : '' }}>
                        {{ $bank->name }}
                    </option>
                @endforeach
            </select>
            @if (request('course_id'))
                <input type="hidden" name="course_id" value="{{ request('course_id') }}">
            @endif
            @if (request('question_type'))
                <input type="hidden" name="question_type" value="{{ request('question_type') }}">
            @endif
        </form>

        <div>
            <div class="filter-label" style="margin-bottom:8px;">Thống kê</div>
            <div class="stat-chips">
                <span class="stat-chip chip-easy">
                    <i class="fa-solid fa-circle" style="font-size:7px;"></i>
                    Dễ: {{ $questions->where('difficulty', 'easy')->count() }}
                </span>
                <span class="stat-chip chip-medium">
                    <i class="fa-solid fa-circle" style="font-size:7px;"></i>
                    Trung bình: {{ $questions->where('difficulty', 'medium')->count() }}
                </span>
                <span class="stat-chip chip-hard">
                    <i class="fa-solid fa-circle" style="font-size:7px;"></i>
                    Khó: {{ $questions->where('difficulty', 'hard')->count() }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── Table ── --}}
    <div class="table-card">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:52px;">ID</th>
                        <th style="width:36%;">Nội dung câu hỏi</th>
                        <th>Hình thức</th>
                        <th>Ngân hàng</th>
                        <th>Dùng cho</th>
                        <th>Giáo viên</th>
                        <th style="width:150px;">Độ khó</th>
                        <th style="width:90px; text-align:right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($questions as $question)
                        <tr>
                            <td><span class="q-id">#{{ $question->id }}</span></td>
                            <td>
                                <div class="q-text">{{ Str::limit($question->question_text, 80) }}</div>
                                @if($question->passage)
                                    <div class="small text-primary mt-1"><i class="fa-solid fa-file-lines"></i> {{ Str::limit($question->passage->title, 55) }}</div>
                                @endif
                                <div class="q-answer">
                                    <i class="fa-solid fa-circle-check"></i>
                                    {{ Str::limit($question->answerSummary(), 62) }}
                                </div>
                            </td>
                            <td><span class="type-badge type-{{ $question->question_type }}">{{ $question->typeLabel() }}</span></td>
                            <td><span class="q-course">{{ $question->questionBank->name ?? $question->course->title ?? 'N/A' }}</span></td>
                            <td>
                                <span class="q-course">
                                    {{ $question->questionBank?->courses?->pluck('title')->take(2)->implode(', ') ?: ($question->course->title ?? 'N/A') }}
                                    @if (($question->questionBank?->courses?->count() ?? 0) > 2)
                                        ...
                                    @endif
                                </span>
                            </td>
                            <td>
                                <div class="teacher-chip">
                                    <div class="teacher-avatar-sm"><i class="fa-solid fa-user-tie" style="font-size:10px;"></i>
                                    </div>
                                    {{ $question->questionBank->teacher->name ?? $question->course->teacher->name ?? 'N/A' }}
                                </div>
                            </td>
                            <td>
                                @if ($question->difficulty == 'easy')
                                    <span class="diff-badge diff-easy">Dễ</span>
                                @elseif($question->difficulty == 'medium')
                                    <span class="diff-badge diff-medium">Trung bình</span>
                                @else
                                    <span class="diff-badge diff-hard">Khó</span>
                                @endif
                                @php($difficultyMetrics = $question->difficulty_metrics ?? [])
                                @if($question->observedDifficultyLabel())
                                    <div class="small text-muted mt-1" title="Độ khó thực tế được tính từ kết quả làm bài">
                                        Thực tế: <strong>{{ $question->observedDifficultyLabel() }}</strong>
                                        · {{ round(((float) ($difficultyMetrics['accuracy'] ?? 0)) * 100) }}% đúng
                                        / {{ (int) ($difficultyMetrics['sample_size'] ?? 0) }} lượt
                                    </div>
                                @elseif((int) ($difficultyMetrics['sample_size'] ?? 0) > 0)
                                    <div class="small text-muted mt-1">
                                        Cần {{ max(0, 5 - (int) $difficultyMetrics['sample_size']) }} lượt nữa để đánh giá
                                    </div>
                                @endif
                            </td>
                            <td>
                                @php
                                    $editPayload = json_encode([
                                        'id' => $question->id,
                                        'course_id' => $question->course_id,
                                        'question_bank_id' => $question->question_bank_id,
                                        'quiz_passage_id' => $question->quiz_passage_id,
                                        'difficulty' => $question->difficulty,
                                        'question_type' => $question->question_type,
                                        'question_text' => $question->question_text,
                                        'answer_config' => $question->answer_config,
                                        'options' => $question->options->sortBy('id')->values(),
                                    ], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT);
                                @endphp
                                <div style="display:flex; gap:6px; justify-content:flex-end;">
                                    <button type="button" class="action-btn" data-bs-toggle="modal"
                                        data-bs-target="#editQuestionModal" data-id="{{ $question->id }}"
                                        data-update-url="{{ route('questions.updateBank', $question->id) }}"
                                        data-course="{{ $question->course_id }}"
                                        data-bank="{{ $question->question_bank_id }}"
                                        data-passage="{{ $question->quiz_passage_id }}"
                                        data-difficulty="{{ $question->difficulty }}"
                                        data-payload="{{ $editPayload }}"
                                        title="Sửa">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                    <form action="{{ route('questions.destroyBank', $question->id) }}" method="POST"
                                        style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-btn danger"
                                            onclick="return confirm('Lưu trữ câu hỏi này? Đáp án và dữ liệu liên quan vẫn được giữ lại.')" title="Lưu trữ">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="8">
                                <i class="fa-solid fa-box-open"></i>
                                <p>Kho câu hỏi trống. Hãy thêm câu hỏi mới!</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($questions->hasPages())
            <div class="pagination-wrap">{{ $questions->links() }}</div>
        @endif
    </div>

    <div class="modal fade" id="passageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('questions.passages.store') }}" method="POST">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-file-lines"></i> Ngữ liệu dùng chung</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row-g">
                            <div class="col-flex-2"><label class="form-label-sm">Khóa học</label><select name="course_id" class="form-ctrl" required>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
                            <div class="col-flex-2"><label class="form-label-sm">Tên ngữ liệu</label><input name="title" class="form-ctrl" required placeholder="Ví dụ: Đọc đoạn văn và trả lời câu 1–5"></div>
                            <div class="col-flex-full"><label class="form-label-sm">Nguồn tham khảo</label><input name="source_label" class="form-ctrl" placeholder="Không bắt buộc"></div>
                            <div class="col-flex-full"><label class="form-label-sm">Nội dung đoạn văn/tài liệu</label><textarea name="content" class="form-ctrl" rows="8" maxlength="50000" required></textarea></div>
                        </div>
                        @if($passages->isNotEmpty())
                            <div class="section-divider">Ngữ liệu hiện có</div>
                            @foreach($passages as $passage)
                                <div class="d-flex align-items-center gap-2 border rounded-3 p-2 mb-2">
                                    <div class="flex-grow-1"><strong>{{ $passage->title }}</strong><small class="text-muted d-block">{{ $passage->course->title ?? '' }} · {{ $passage->questions_count }} câu hỏi</small></div>
                                    <span class="badge bg-light text-secondary">Đang sử dụng</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <div class="modal-footer"><button class="btn-modal-submit">Lưu ngữ liệu</button></div>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════
     MODAL: Tạo ngân hàng câu hỏi
══════════════════════════════ --}}
    <div class="modal fade" id="addQuestionBankModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('questions.banks.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa-solid fa-layer-group"></i>Tạo ngân hàng câu hỏi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div style="margin-bottom:14px;">
                            <label class="form-label-sm">Tên ngân hàng</label>
                            <input type="text" name="name" class="form-ctrl" placeholder="VD: Web Frontend" required>
                        </div>
                        <div style="margin-bottom:14px;">
                            <label class="form-label-sm">Mô tả</label>
                            <textarea name="description" class="form-ctrl" rows="3" placeholder="Mô tả ngắn về bộ câu hỏi..."></textarea>
                        </div>
                        <div>
                            <label class="form-label-sm">Gắn với khóa học</label>
                            <select name="course_ids[]" class="form-ctrl" multiple size="5">
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                        {{ $course->title }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="info-note mt-2">
                                <i class="fa-solid fa-circle-info"></i>
                                Một ngân hàng có thể dùng chung cho nhiều khóa học/lớp.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn-modal-submit">Tạo bank</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════
     MODAL: Gắn ngân hàng với khóa học
══════════════════════════════ --}}
    <div class="modal fade" id="attachQuestionBankModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('questions.banks.attach') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa-solid fa-link"></i>Gắn bank với khóa học</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div style="margin-bottom:14px;">
                            <label class="form-label-sm">Ngân hàng câu hỏi</label>
                            <select name="question_bank_id" class="form-ctrl" required>
                                @foreach ($questionBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label-sm">Khóa học được dùng bank này</label>
                            <select name="course_ids[]" class="form-ctrl" multiple size="6" required>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn-modal-submit">Gắn bank</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════
     MODAL: Thêm câu hỏi
══════════════════════════════ --}}
    <div class="modal fade" id="addQuestionModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('questions.storeBank') }}" method="POST">
                    @csrf
                    @include('quizzes.partials.question_editor', ['mode' => 'add', 'editorId' => 'add'])
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════
     MODAL: Sửa câu hỏi
══════════════════════════════ --}}
    <div class="modal fade" id="editQuestionModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="" method="POST" id="editQuestionForm">
                    @csrf @method('PUT')
                    @include('quizzes.partials.question_editor', ['mode' => 'edit', 'editorId' => 'edit'])
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════
     MODAL: Nhập từ Excel
══════════════════════════════ --}}
    <div class="modal fade" id="importQuestionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('questions.importBank') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" style="color:#16a34a;">
                            <i class="fa-solid fa-file-arrow-up" style="color:#16a34a;"></i>Nhập câu hỏi từ file
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div style="margin-bottom:14px;">
                            <label class="form-label-sm">1. Chọn khóa học</label>
                            <select name="course_id" class="form-ctrl" required>
                                <option value="">-- Vui lòng chọn khóa học --</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="margin-bottom:14px;">
                            <label class="form-label-sm">2. Chọn ngân hàng câu hỏi</label>
                            <select name="question_bank_id" class="form-ctrl">
                                <option value="">Tự chọn/tạo theo khóa học</option>
                                @foreach ($questionBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label class="form-label-sm">3. Tải lên file .xlsx</label>
                            <input type="file" name="file" class="form-ctrl" accept=".xlsx" required>
                        </div>
                        <div class="warn-note">
                            <strong>Định dạng file Excel (7 cột A → G):</strong>
                            <ol>
                                <li>Nội dung câu hỏi</li>
                                <li>Độ khó (<em>easy, medium, hard</em>)</li>
                                <li>Đáp án A</li>
                                <li>Đáp án B</li>
                                <li>Đáp án C</li>
                                <li>Đáp án D</li>
                                <li>Đáp án đúng (<em>A, B, C hoặc D</em>)</li>
                            </ol>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn-modal-submit green">Bắt đầu nhập</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editors = new Map();

            document.querySelectorAll('[data-question-editor]').forEach(root => {
                const state = {
                    root,
                    options: Array.from({length: 4}, (_, index) => ({text: '', correct: index === 0})),
                    blanks: [''],
                };
                editors.set(root.dataset.questionEditor, state);

                const type = () => root.querySelector('[name="question_type"]:checked').value;
                const optionRows = root.querySelector('[data-option-rows]');
                const textInput = root.querySelector('[data-field="question_text"]');

                const captureOptions = () => {
                    optionRows.querySelectorAll('[data-option-row]').forEach((row, index) => {
                        if (!state.options[index]) return;
                        state.options[index].text = row.querySelector('[data-option-text]').value;
                        const correctness = row.querySelector('[data-option-correct]');
                        if (correctness) state.options[index].correct = correctness.type === 'select' ? correctness.value === '1' : correctness.checked;
                    });
                };

                const renderOptions = () => {
                    const currentType = type();
                    const isGroup = currentType === 'true_false_group';
                    const isMultiple = currentType === 'multiple_choice';
                    optionRows.innerHTML = '';
                    state.options.forEach((option, index) => {
                        const number = index + 1;
                        const row = document.createElement('div');
                        row.className = 'answer-row answer-row-modern';
                        row.dataset.optionRow = '';
                        const marker = isGroup
                            ? `<span class="statement-number">${number}</span>`
                            : `<label class="correct-control" title="Đánh dấu đáp án đúng"><input data-option-correct type="${isMultiple ? 'checkbox' : 'radio'}" name="${isMultiple ? 'correct_options[]' : 'correct_option'}" value="${number}" ${option.correct ? 'checked' : ''}><span><i class="fa-solid fa-check"></i></span></label>`;
                        const truth = isGroup
                            ? `<select data-option-correct name="truth_values[${number}]" class="truth-select"><option value="1" ${option.correct ? 'selected' : ''}>Đúng</option><option value="0" ${!option.correct ? 'selected' : ''}>Sai</option></select>`
                            : '';
                        row.innerHTML = `${marker}<input data-option-text type="text" name="options[${number}]" class="answer-input" maxlength="2000" placeholder="${isGroup ? 'Nhập nhận định' : 'Nhập phương án'} ${number}" required>${truth}<button type="button" class="remove-option" aria-label="Xóa phương án" ${state.options.length <= 2 ? 'disabled' : ''}><i class="fa-solid fa-xmark"></i></button>`;
                        row.querySelector('[data-option-text]').value = option.text || '';
                        row.querySelector('.remove-option').addEventListener('click', () => {
                            captureOptions();
                            state.options.splice(index, 1);
                            if (!state.options.some(item => item.correct) && !isGroup) state.options[0].correct = true;
                            renderOptions();
                        });
                        optionRows.appendChild(row);
                    });
                };

                const renderBlanks = () => {
                    const placeholders = textInput.value.match(/\[\[\s*\d+\s*\]\]/g) || [];
                    const count = placeholders.length;
                    while (state.blanks.length < count) state.blanks.push('');
                    state.blanks = state.blanks.slice(0, Math.max(count, 1));
                    const container = root.querySelector('[data-blank-rows]');
                    container.innerHTML = '';
                    root.querySelector('[data-blank-preview]').textContent = count
                        ? `Đã nhận diện ${count} ô trống. Mỗi ô có thể có nhiều cách viết đúng.`
                        : 'Thêm [[1]] vào nội dung để tạo ô trống đầu tiên.';
                    state.blanks.slice(0, count).forEach((answer, index) => {
                        const row = document.createElement('label');
                        row.className = 'blank-answer-row';
                        row.innerHTML = `<span>Ô ${index + 1}</span><input type="text" name="blank_answers[${index + 1}]" maxlength="2000" placeholder="Đáp án đúng | cách viết khác" required>`;
                        const input = row.querySelector('input');
                        input.value = answer;
                        input.addEventListener('input', () => state.blanks[index] = input.value);
                        container.appendChild(row);
                    });
                };

                const applyType = () => {
                    captureOptions();
                    const currentType = type();
                    renderOptions();
                    renderBlanks();
                    root.querySelectorAll('[data-answer-section]').forEach(section => {
                        const active = (section.dataset.answerSection === 'options' && ['single_choice', 'multiple_choice', 'true_false_group'].includes(currentType))
                            || (section.dataset.answerSection === 'blanks' && currentType === 'fill_blank')
                            || (section.dataset.answerSection === 'numeric' && currentType === 'numeric');
                        section.hidden = !active;
                        section.querySelectorAll('input,select,textarea').forEach(input => input.disabled = !active);
                    });
                    root.querySelector('[data-placeholder-helper]').hidden = currentType !== 'fill_blank';
                    root.querySelector('[data-add-option]').hidden = currentType === 'true_false_group' && state.options.length >= 10;
                    root.querySelector('[data-options-title]').textContent = currentType === 'true_false_group' ? 'Các nhận định' : 'Các phương án';
                    root.querySelector('[data-options-hint]').textContent = currentType === 'single_choice' ? 'Chọn một đáp án đúng.' : currentType === 'multiple_choice' ? 'Chọn từ hai đáp án đúng; chấm theo nguyên tắc chọn đủ.' : 'Xác định Đúng hoặc Sai cho từng nhận định.';
                    root.querySelector('[data-editor-guidance] span').textContent = currentType === 'fill_blank' ? 'Có thể khai báo nhiều cách viết đúng bằng dấu |.' : currentType === 'numeric' ? 'Ví dụ 10 ± 0.5 sẽ chấp nhận kết quả từ 9.5 đến 10.5.' : currentType === 'true_false_group' ? 'Học sinh phải trả lời đầy đủ từng nhận định để được tính đúng.' : 'Thứ tự phương án sẽ được xáo trộn khi phát đề.';
                };

                root.querySelectorAll('[name="question_type"]').forEach(input => input.addEventListener('change', applyType));
                root.querySelector('[data-add-option]').addEventListener('click', () => {
                    captureOptions();
                    if (state.options.length >= (type() === 'true_false_group' ? 10 : 8)) return;
                    state.options.push({text: '', correct: false});
                    renderOptions();
                });
                textInput.addEventListener('input', () => {
                    if (type() === 'fill_blank') renderBlanks();
                });

                state.applyType = applyType;
                state.renderBlanks = renderBlanks;
                applyType();
            });

            const editModal = document.getElementById('editQuestionModal');
            editModal?.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                const payload = JSON.parse(button.getAttribute('data-payload') || '{}');
                const state = editors.get('edit');
                const root = state.root;
                document.getElementById('editQuestionForm').action = button.dataset.updateUrl;
                ['course_id', 'question_bank_id', 'quiz_passage_id', 'difficulty', 'question_text'].forEach(field => {
                    root.querySelector(`[data-field="${field}"]`).value = payload[field] ?? '';
                });
                const typeInput = root.querySelector(`[name="question_type"][value="${payload.question_type || 'single_choice'}"]`);
                if (typeInput) typeInput.checked = true;
                state.options = (payload.options || []).map(option => ({text: option.option_text, correct: Boolean(option.is_correct)}));
                if (state.options.length < 2 && ['single_choice', 'multiple_choice', 'true_false_group'].includes(payload.question_type)) {
                    state.options = [{text: '', correct: true}, {text: '', correct: false}];
                }
                const config = payload.answer_config || {};
                state.blanks = (config.blanks || []).map(blank => (blank.accepted || []).join(' | '));
                root.querySelector('[name="case_sensitive"]').checked = Boolean(config.case_sensitive);
                root.querySelector('[data-field="numeric_answer"]').value = config.target ?? '';
                root.querySelector('[data-field="numeric_tolerance"]').value = config.tolerance ?? 0;
                root.querySelector('[data-field="numeric_unit"]').value = config.unit ?? '';
                root.querySelector('[data-option-rows]').innerHTML = '';
                state.applyType();
            });
        });
    </script>
@endpush
