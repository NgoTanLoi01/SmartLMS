@extends('layouts.app')

@section('title', 'Ngân hàng câu hỏi')

@section('content')
    @vite('resources/css/pages/question-bank.css')

    {{-- ── Page Header ── --}}
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-database" style="color:#2563eb; font-size:18px; margin-right:10px;"
                    aria-hidden="true"></i>Ngân hàng câu hỏi</h1>
            <p class="page-subtitle">Soạn câu hỏi hỗn hợp, quản lý ngữ liệu và tự động chấm điểm</p>
        </div>
        <div class="btn-group-actions">
            <button type="button" class="btn-act btn-act-ghost" data-bs-toggle="modal" data-bs-target="#passageModal">
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i> Ngữ liệu
            </button>
            <button type="button" class="btn-act btn-act-ghost" data-bs-toggle="modal"
                data-bs-target="#addQuestionBankModal">
                <i class="fa-solid fa-layer-group" aria-hidden="true"></i> Tạo bank
            </button>
            <button type="button" class="btn-act btn-act-ghost" data-bs-toggle="modal"
                data-bs-target="#attachQuestionBankModal">
                <i class="fa-solid fa-link" aria-hidden="true"></i> Gắn bank
            </button>
            <button type="button" class="btn-act btn-act-ghost-green" data-bs-toggle="modal"
                data-bs-target="#importQuestionModal">
                <i class="fa-solid fa-file-excel" aria-hidden="true"></i> Nhập từ Excel
            </button>
            <a href="{{ route('quizzes.ai_generate') }}" class="btn-act btn-act-ghost">
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Tạo bằng AI
            </a>
            <button type="button" class="btn-act btn-act-primary" data-bs-toggle="modal"
                data-bs-target="#addQuestionModal">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Thêm câu hỏi
            </button>
        </div>
    </div>

    {{--
        ── Filter bar ──
        Optimization: was 4 separate <form> tags, each re-declaring the other
        3 filters as hidden inputs to preserve state (12 hidden inputs total,
        4 HTTP round-trips worth of markup). Consolidated into a single form
        so every control shares state natively — no hidden-input duplication,
        one code path to maintain.
    --}}
    @php
        $hasActiveFilters =
            request()->hasAny(['course_id', 'question_type', 'question_bank_id']) ||
            (request('status') && request('status') !== 'active');
        // Single pass over the current page's questions instead of 3 separate
// ->where()->count() calls against the same collection.
$difficultyCounts = $questions->getCollection()->groupBy('difficulty')->map->count();
    @endphp
    <form action="{{ route('questions.index') }}" method="GET" class="filter-bar" id="question-filter-form">
        <div class="filter-group">
            <label class="filter-label" for="filter-course">Lọc theo khóa học</label>
            <select name="course_id" id="filter-course" onchange="this.form.submit()">
                <option value="">Tất cả khóa học</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>
                        {{ $course->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label" for="filter-type">Loại câu hỏi</label>
            <select name="question_type" id="filter-type" onchange="this.form.submit()">
                <option value="">Tất cả hình thức</option>
                @foreach ($questionTypeLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('question_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label" for="filter-bank">Lọc theo ngân hàng</label>
            <select name="question_bank_id" id="filter-bank" onchange="this.form.submit()">
                <option value="">Tất cả ngân hàng</option>
                @foreach ($questionBanks as $bank)
                    <option value="{{ $bank->id }}" @selected(request('question_bank_id') == $bank->id)>
                        {{ $bank->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group filter-group-status">
            <label class="filter-label" for="filter-status">Trạng thái</label>
            <select name="status" id="filter-status" onchange="this.form.submit()">
                <option value="active" @selected(request('status', 'active') === 'active')>Đang sử dụng</option>
                <option value="archived" @selected(request('status') === 'archived')>Đã lưu trữ</option>
                <option value="all" @selected(request('status') === 'all')>Tất cả trạng thái</option>
            </select>
        </div>

        <div>
            <div class="filter-label" style="margin-bottom:8px;">Thống kê trang này</div>
            <div class="stat-chips">
                <span class="stat-chip chip-easy">
                    <i class="fa-solid fa-circle" style="font-size:7px;" aria-hidden="true"></i>
                    Dễ: {{ $difficultyCounts['easy'] ?? 0 }}
                </span>
                <span class="stat-chip chip-medium">
                    <i class="fa-solid fa-circle" style="font-size:7px;" aria-hidden="true"></i>
                    Trung bình: {{ $difficultyCounts['medium'] ?? 0 }}
                </span>
                <span class="stat-chip chip-hard">
                    <i class="fa-solid fa-circle" style="font-size:7px;" aria-hidden="true"></i>
                    Khó: {{ $difficultyCounts['hard'] ?? 0 }}
                </span>
            </div>
        </div>

        @if ($hasActiveFilters)
            <a href="{{ route('questions.index') }}" class="filter-clear-link">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Xóa bộ lọc
            </a>
        @endif
    </form>

    {{-- ── Table ── --}}
    <div class="table-card">
        @if ($questions->contains(fn($question) => $question->status !== \App\Models\Question::STATUS_ARCHIVED))
            <div class="bulk-toolbar" id="question-bulk-toolbar">
                <div class="bulk-selection-info">
                    <span class="bulk-selection-icon" aria-hidden="true"><i class="fa-solid fa-check-double"></i></span>
                    <div>
                        <strong><span id="selected-question-count" aria-live="polite">0</span> câu hỏi đã chọn</strong>
                        <small>Chọn từng câu hoặc chọn tất cả câu trên trang hiện tại.</small>
                    </div>
                </div>
                <form id="bulk-question-form" action="{{ route('questions.bulkDestroyBank') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bulk-delete-button" id="bulk-delete-button" disabled>
                        <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Lưu trữ đã chọn
                    </button>
                </form>
            </div>
        @endif

        <p class="table-result-count text-muted small px-3 pt-2 mb-0">
            Hiển thị {{ $questions->count() }} / {{ $questions->total() }} câu hỏi
            @if ($questions->total() > $questions->count())
                (trang {{ $questions->currentPage() }}/{{ $questions->lastPage() }})
            @endif
        </p>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <caption class="visually-hidden">Danh sách câu hỏi trong ngân hàng</caption>
                <thead>
                    <tr>
                        <th class="selection-column" scope="col">
                            @if ($questions->contains(fn($question) => $question->status !== \App\Models\Question::STATUS_ARCHIVED))
                                <input type="checkbox" class="question-checkbox" id="select-all-questions"
                                    aria-label="Chọn tất cả câu hỏi trên trang">
                            @else
                                <i class="fa-solid fa-box-archive" title="Danh sách câu hỏi đã lưu trữ"
                                    aria-hidden="true"></i>
                            @endif
                        </th>
                        <th style="width:52px;" scope="col">ID</th>
                        <th style="width:36%;" scope="col">Nội dung câu hỏi</th>
                        <th scope="col">Hình thức</th>
                        <th scope="col">Ngân hàng</th>
                        <th scope="col">Dùng cho</th>
                        <th scope="col">Giáo viên</th>
                        <th style="width:150px;" scope="col">Độ khó</th>
                        <th style="width:90px; text-align:right;" scope="col">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($questions as $question)
                        @php
                            $isArchived = $question->status === \App\Models\Question::STATUS_ARCHIVED;
                            $difficultyMetrics = $question->difficulty_metrics ?? [];
                            $sampleSize = (int) ($difficultyMetrics['sample_size'] ?? 0);
                        @endphp
                        <tr data-question-row="{{ $question->id }}" @class(['is-archived' => $isArchived])>
                            <td class="selection-column">
                                @if (!$isArchived)
                                    <input type="checkbox" class="question-checkbox question-row-checkbox"
                                        name="question_ids[]" value="{{ $question->id }}" form="bulk-question-form"
                                        aria-label="Chọn câu hỏi #{{ $question->id }}">
                                @else
                                    <i class="fa-solid fa-box-archive archived-row-icon" title="Câu hỏi đã lưu trữ"
                                        aria-hidden="true"></i>
                                @endif
                            </td>
                            <td><span class="q-id">#{{ $question->id }}</span></td>
                            <td>
                                <div class="q-text">{{ Str::limit($question->question_text, 80) }}</div>
                                @if ($isArchived)
                                    <span class="question-status-badge"><i class="fa-solid fa-box-archive"
                                            aria-hidden="true"></i> Đã lưu trữ</span>
                                @endif
                                @if ($question->passage)
                                    <div class="small text-primary mt-1"><i class="fa-solid fa-file-lines"
                                            aria-hidden="true"></i> {{ Str::limit($question->passage->title, 55) }}</div>
                                @endif
                                <div class="q-answer">
                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                    {{ Str::limit($question->answerSummary(), 62) }}
                                </div>
                            </td>
                            <td><span
                                    class="type-badge type-{{ $question->question_type }}">{{ $question->typeLabel() }}</span>
                            </td>
                            <td><span
                                    class="q-course">{{ $question->questionBank->name ?? ($question->course->title ?? 'Chưa có') }}</span>
                            </td>
                            <td>
                                <span class="q-course">
                                    {{ $question->questionBank?->courses?->pluck('title')->take(2)->implode(', ') ?: $question->course->title ?? 'Chưa có' }}
                                    @if (($question->questionBank?->courses?->count() ?? 0) > 2)
                                        ...
                                    @endif
                                </span>
                            </td>
                            <td>
                                <div class="teacher-chip">
                                    <div class="teacher-avatar-sm"><i class="fa-solid fa-user-tie"
                                            style="font-size:10px;" aria-hidden="true"></i>
                                    </div>
                                    {{ $question->questionBank->teacher->name ?? ($question->course->teacher->name ?? 'Chưa có') }}
                                </div>
                            </td>
                            <td>
                                @if ($question->difficulty === 'easy')
                                    <span class="diff-badge diff-easy">Dễ</span>
                                @elseif ($question->difficulty === 'medium')
                                    <span class="diff-badge diff-medium">Trung bình</span>
                                @else
                                    <span class="diff-badge diff-hard">Khó</span>
                                @endif
                                @if ($question->observedDifficultyLabel())
                                    <div class="small text-muted mt-1"
                                        title="Độ khó thực tế được tính từ kết quả làm bài">
                                        Thực tế: <strong>{{ $question->observedDifficultyLabel() }}</strong>
                                        · {{ round(((float) ($difficultyMetrics['accuracy'] ?? 0)) * 100) }}% đúng
                                        / {{ $sampleSize }} lượt
                                    </div>
                                @elseif ($sampleSize > 0)
                                    <div class="small text-muted mt-1">
                                        Cần {{ max(0, 5 - $sampleSize) }} lượt nữa để đánh giá
                                    </div>
                                @endif
                            </td>
                            <td>
                                @php
                                    $editPayload = json_encode(
                                        [
                                            'id' => $question->id,
                                            'course_id' => $question->course_id,
                                            'question_bank_id' => $question->question_bank_id,
                                            'quiz_passage_id' => $question->quiz_passage_id,
                                            'difficulty' => $question->difficulty,
                                            'question_type' => $question->question_type,
                                            'question_text' => $question->question_text,
                                            'answer_config' => $question->answer_config,
                                            'options' => $question->options->sortBy('id')->values(),
                                        ],
                                        JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT,
                                    );
                                @endphp
                                <div style="display:flex; gap:6px; justify-content:flex-end;">
                                    @if (!$isArchived)
                                        <button type="button" class="action-btn" data-bs-toggle="modal"
                                            data-bs-target="#editQuestionModal" data-id="{{ $question->id }}"
                                            data-update-url="{{ route('questions.updateBank', $question->id) }}"
                                            data-course="{{ $question->course_id }}"
                                            data-bank="{{ $question->question_bank_id }}"
                                            data-passage="{{ $question->quiz_passage_id }}"
                                            data-difficulty="{{ $question->difficulty }}"
                                            data-payload="{{ $editPayload }}" title="Sửa"
                                            aria-label="Sửa câu hỏi #{{ $question->id }}">
                                            <i class="fa-solid fa-edit" aria-hidden="true"></i>
                                        </button>
                                        <form action="{{ route('questions.destroyBank', $question->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="action-btn danger"
                                                data-confirm="Lưu trữ câu hỏi này? Đáp án và dữ liệu liên quan vẫn được giữ lại."
                                                title="Lưu trữ" aria-label="Lưu trữ câu hỏi #{{ $question->id }}">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('questions.restoreBank', $question->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="action-btn restore"
                                                data-confirm="Khôi phục câu hỏi này vào danh sách đang sử dụng?"
                                                title="Khôi phục" aria-label="Khôi phục câu hỏi #{{ $question->id }}">
                                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="9">
                                <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                                <p>
                                    @if (request('status') === 'archived')
                                        Chưa có câu hỏi nào được lưu trữ.
                                    @elseif ($hasActiveFilters)
                                        Không tìm thấy câu hỏi phù hợp với bộ lọc hiện tại.
                                        <a href="{{ route('questions.index') }}">Xóa bộ lọc</a>.
                                    @else
                                        Kho câu hỏi trống. Hãy thêm câu hỏi mới!
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($questions->hasPages())
            <div class="pagination-wrap">{{ $questions->appends(request()->query())->links() }}</div>
        @endif
    </div>

    <div class="modal fade" id="passageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('questions.passages.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Ngữ liệu dùng
                            chung</h5><button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row-g">
                            <div class="col-flex-2"><label class="form-label-sm">Khóa học</label><select name="course_id"
                                    class="form-ctrl" required>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-flex-2"><label class="form-label-sm">Tên ngữ liệu</label><input
                                    name="title" class="form-ctrl" required
                                    placeholder="Ví dụ: Đọc đoạn văn và trả lời câu 1–5"></div>
                            <div class="col-flex-full"><label class="form-label-sm">Nguồn tham khảo</label><input
                                    name="source_label" class="form-ctrl" placeholder="Không bắt buộc"></div>
                            <div class="col-flex-full"><label class="form-label-sm">Nội dung đoạn văn/tài liệu</label>
                                <textarea name="content" class="form-ctrl" rows="8" maxlength="50000" required></textarea>
                            </div>
                        </div>
                        @if ($passages->isNotEmpty())
                            <div class="section-divider">Ngữ liệu hiện có</div>
                            @foreach ($passages as $passage)
                                <div class="d-flex align-items-center gap-2 border rounded-3 p-2 mb-2">
                                    <div class="flex-grow-1"><strong>{{ $passage->title }}</strong><small
                                            class="text-muted d-block">{{ $passage->course->title ?? '' }} ·
                                            {{ $passage->questions_count }} câu hỏi</small></div>
                                    <span class="badge bg-light text-secondary">Đang sử dụng</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-modal-submit">Lưu ngữ liệu</button></div>
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
                        <h5 class="modal-title"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Tạo ngân hàng
                            câu hỏi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div style="margin-bottom:14px;">
                            <label class="form-label-sm">Tên ngân hàng</label>
                            <input type="text" name="name" class="form-ctrl" placeholder="VD: Web Frontend"
                                required>
                        </div>
                        <div style="margin-bottom:14px;">
                            <label class="form-label-sm">Mô tả</label>
                            <textarea name="description" class="form-ctrl" rows="3" placeholder="Mô tả ngắn về bộ câu hỏi..."></textarea>
                        </div>
                        <div>
                            <label class="form-label-sm">Gắn với khóa học</label>
                            <select name="course_ids[]" class="form-ctrl" multiple size="5">
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>
                                        {{ $course->title }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="info-note mt-2">
                                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
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
                        <h5 class="modal-title"><i class="fa-solid fa-link" aria-hidden="true"></i> Gắn bank với khóa học
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
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
        <div class="modal-dialog modal-lg modal-dialog-centered question-editor-dialog">
            <div class="modal-content question-editor-modal">
                <form action="{{ route('questions.storeBank') }}" method="POST" class="question-editor-form">
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
        <div class="modal-dialog modal-lg modal-dialog-centered question-editor-dialog">
            <div class="modal-content question-editor-modal">
                <form action="" method="POST" id="editQuestionForm" class="question-editor-form">
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
                            <i class="fa-solid fa-file-arrow-up" style="color:#16a34a;" aria-hidden="true"></i> Nhập câu
                            hỏi từ file
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
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
                            <label class="form-label-sm">3. Tải lên file .xlsx, .xls hoặc .csv (tối đa 5 MB)</label>
                            <input type="file" name="file" class="form-ctrl" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <div class="warn-note">
                            <strong>Định dạng bắt buộc (đúng 7 cột A → G, không để trống):</strong>
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

@push('styles')
    {{-- Light-touch polish that doesn't depend on the external stylesheet:
         focus visibility, button loading state, and the new "clear filters" link. --}}
    <style>
        .filter-clear-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #64748b;
            text-decoration: none;
            align-self: end;
            padding: 8px 0;
        }

        .filter-clear-link:hover {
            color: #dc2626;
            text-decoration: underline;
        }

        .table-result-count {
            color: #64748b;
        }

        .action-btn:focus-visible,
        .btn-act:focus-visible,
        .btn-modal-submit:focus-visible,
        .btn-modal-cancel:focus-visible,
        select:focus-visible,
        input:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }

        button[disabled].is-loading {
            opacity: .65;
            cursor: progress;
        }

        .visually-hidden {
            position: absolute !important;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.001ms !important;
                transition-duration: 0.001ms !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /* ─────────────────────────────────────────────
             * Question editor (add / edit) — dynamic option
             * & fill-blank builder shared by both modals.
             * ───────────────────────────────────────────── */
            const editors = new Map();

            document.querySelectorAll('[data-question-editor]').forEach(root => {
                const state = {
                    root,
                    options: Array.from({
                        length: 4
                    }, (_, index) => ({
                        text: '',
                        correct: index === 0
                    })),
                    blanks: [''],
                };
                editors.set(root.dataset.questionEditor, state);

                const type = () => root.querySelector('[name="question_type"]:checked').value;
                const optionRows = root.querySelector('[data-option-rows]');
                const textInput = root.querySelector('[data-field="question_text"]');

                const captureOptions = () => {
                    optionRows.querySelectorAll('[data-option-row]').forEach((row, index) => {
                        if (!state.options[index]) return;
                        state.options[index].text = row.querySelector('[data-option-text]')
                            .value;
                        const correctness = row.querySelector('[data-option-correct]');
                        if (correctness) {
                            state.options[index].correct = correctness.type === 'select' ?
                                correctness.value === '1' :
                                correctness.checked;
                        }
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
                        const marker = isGroup ?
                            `<span class="statement-number">${number}</span>` :
                            `<label class="correct-control" title="Đánh dấu đáp án đúng"><input data-option-correct type="${isMultiple ? 'checkbox' : 'radio'}" name="${isMultiple ? 'correct_options[]' : 'correct_option'}" value="${number}" ${option.correct ? 'checked' : ''}><span><i class="fa-solid fa-check"></i></span></label>`;
                        const truth = isGroup ?
                            `<select data-option-correct name="truth_values[${number}]" class="truth-select"><option value="1" ${option.correct ? 'selected' : ''}>Đúng</option><option value="0" ${!option.correct ? 'selected' : ''}>Sai</option></select>` :
                            '';
                        row.innerHTML =
                            `${marker}<input data-option-text type="text" name="options[${number}]" class="answer-input" maxlength="2000" placeholder="${isGroup ? 'Nhập nhận định' : 'Nhập phương án'} ${number}" required>${truth}<button type="button" class="remove-option" aria-label="Xóa phương án ${number}" ${state.options.length <= 2 ? 'disabled' : ''}><i class="fa-solid fa-xmark"></i></button>`;
                        row.querySelector('[data-option-text]').value = option.text || '';
                        row.querySelector('.remove-option').addEventListener('click', () => {
                            captureOptions();
                            state.options.splice(index, 1);
                            if (!isGroup && !state.options.some(item => item.correct))
                                state.options[0].correct = true;
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
                    root.querySelector('[data-blank-preview]').textContent = count ?
                        `Đã nhận diện ${count} ô trống. Mỗi ô có thể có nhiều cách viết đúng.` :
                        'Thêm [[1]] vào nội dung để tạo ô trống đầu tiên.';
                    state.blanks.slice(0, count).forEach((answer, index) => {
                        const row = document.createElement('label');
                        row.className = 'blank-answer-row';
                        row.innerHTML =
                            `<span>Ô ${index + 1}</span><input type="text" name="blank_answers[${index + 1}]" maxlength="2000" placeholder="Đáp án đúng | cách viết khác" required>`;
                        const input = row.querySelector('input');
                        input.value = answer;
                        input.addEventListener('input', () => {
                            state.blanks[index] = input.value;
                        });
                        container.appendChild(row);
                    });
                };

                const answerSectionIsActive = (sectionType, currentType) => ({
                    options: ['single_choice', 'multiple_choice', 'true_false_group'].includes(
                        currentType),
                    blanks: currentType === 'fill_blank',
                    numeric: currentType === 'numeric',
                    essay: currentType === 'essay',
                    code_debug: currentType === 'code_debug',
                } [sectionType] ?? false);

                const guidanceCopy = {
                    fill_blank: 'Có thể khai báo nhiều cách viết đúng bằng dấu |.',
                    numeric: 'Ví dụ 10 ± 0.5 sẽ chấp nhận kết quả từ 9.5 đến 10.5.',
                    true_false_group: 'Học viên phải trả lời đầy đủ từng nhận định để được tính đúng.',
                    essay: 'Điểm chỉ được công bố sau khi giáo viên chấm xong tất cả câu tự luận.',
                    code_debug: 'Bản xem trước được cô lập và không cho phép thực thi JavaScript.',
                    default: 'Thứ tự phương án sẽ được xáo trộn khi phát đề.',
                };

                const applyType = () => {
                    captureOptions();
                    const currentType = type();
                    renderOptions();
                    renderBlanks();

                    root.querySelectorAll('[data-answer-section]').forEach(section => {
                        const active = answerSectionIsActive(section.dataset.answerSection,
                            currentType);
                        section.hidden = !active;
                        section.querySelectorAll('input,select,textarea').forEach(input => {
                            input.disabled = !active;
                        });
                    });

                    root.querySelector('[data-placeholder-helper]').hidden = currentType !==
                        'fill_blank';
                    root.querySelector('[data-add-option]').hidden = currentType ===
                        'true_false_group' && state.options.length >= 10;
                    root.querySelector('[data-options-title]').textContent = currentType ===
                        'true_false_group' ? 'Các nhận định' : 'Các phương án';
                    root.querySelector('[data-options-hint]').textContent = currentType ===
                        'single_choice' ?
                        'Chọn một đáp án đúng.' :
                        currentType === 'multiple_choice' ?
                        'Chọn từ hai đáp án đúng; chấm theo nguyên tắc chọn đủ.' :
                        'Xác định Đúng hoặc Sai cho từng nhận định.';
                    root.querySelector('[data-editor-guidance] span').textContent = guidanceCopy[
                        currentType] ?? guidanceCopy.default;
                };

                root.querySelectorAll('[name="question_type"]').forEach(input => input.addEventListener(
                    'change', applyType));
                root.querySelector('[data-add-option]').addEventListener('click', () => {
                    captureOptions();
                    if (state.options.length >= (type() === 'true_false_group' ? 10 : 8)) return;
                    state.options.push({
                        text: '',
                        correct: false
                    });
                    renderOptions();
                });
                root.querySelector('[data-field="explanation_mode"]')?.addEventListener('change', event => {
                    const limit = root.querySelector('[data-field="explanation_word_limit"]');
                    limit.disabled = event.target.value === 'disabled' || type() !== 'code_debug';
                });
                textInput.addEventListener('input', () => {
                    if (type() === 'fill_blank') renderBlanks();
                });

                state.applyType = applyType;
                state.renderBlanks = renderBlanks;
                applyType();
            });

            document.getElementById('editQuestionModal')?.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                const payload = JSON.parse(button.getAttribute('data-payload') || '{}');
                const state = editors.get('edit');
                const root = state.root;

                document.getElementById('editQuestionForm').action = button.dataset.updateUrl;
                ['course_id', 'question_bank_id', 'quiz_passage_id', 'difficulty', 'question_text'].forEach(
                    field => {
                        root.querySelector(`[data-field="${field}"]`).value = payload[field] ?? '';
                    });

                const typeInput = root.querySelector(
                    `[name="question_type"][value="${payload.question_type || 'single_choice'}"]`);
                if (typeInput) typeInput.checked = true;

                state.options = (payload.options || []).map(option => ({
                    text: option.option_text,
                    correct: Boolean(option.is_correct)
                }));
                if (state.options.length < 2 && ['single_choice', 'multiple_choice', 'true_false_group']
                    .includes(payload.question_type)) {
                    state.options = [{
                        text: '',
                        correct: true
                    }, {
                        text: '',
                        correct: false
                    }];
                }

                const config = payload.answer_config || {};
                state.blanks = (config.blanks || []).map(blank => (blank.accepted || []).join(' | '));
                root.querySelector('[name="case_sensitive"]').checked = Boolean(config.case_sensitive);
                root.querySelector('[data-field="numeric_answer"]').value = config.target ?? '';
                root.querySelector('[data-field="numeric_tolerance"]').value = config.tolerance ?? 0;
                root.querySelector('[data-field="numeric_unit"]').value = config.unit ?? '';
                root.querySelector('[data-field="essay_max_score"]').value = config.max_score ?? 1;
                root.querySelector('[data-field="word_limit"]').value = config.word_limit ?? 500;
                root.querySelector('[data-field="allow_attachments"]').checked = Boolean(config
                    .allow_attachments);
                root.querySelector('[data-field="essay_rubric_text"]').value = (config.rubric || []).map(
                        item => `${item.criterion} | ${item.max_score}`).join('\n') ||
                    'Mức độ đáp ứng yêu cầu | 1';
                root.querySelector('[data-field="code_max_score"]').value = config.max_score ?? 1;
                root.querySelector('[data-field="starter_code"]').value = config.starter_code ?? '';
                root.querySelector('[data-field="explanation_mode"]').value = config.explanation_mode ??
                    'optional';
                root.querySelector('[data-field="explanation_word_limit"]').value = config
                    .explanation_word_limit ?? 150;
                root.querySelector('[data-field="code_rubric_text"]').value = (config.rubric || []).map(
                        item => `${item.criterion} | ${item.max_score}`).join('\n') ||
                    'Mã sửa đúng và hiển thị đúng | 1';

                root.querySelector('[data-option-rows]').innerHTML = '';
                state.applyType();
            });

            /* ─────────────────────────────────────────────
             * Bulk selection toolbar
             * ───────────────────────────────────────────── */
            const selectAll = document.getElementById('select-all-questions');
            const rowCheckboxes = Array.from(document.querySelectorAll('.question-row-checkbox'));
            const selectedCount = document.getElementById('selected-question-count');
            const bulkToolbar = document.getElementById('question-bulk-toolbar');
            const bulkDeleteButton = document.getElementById('bulk-delete-button');

            const updateBulkSelection = () => {
                const checked = rowCheckboxes.filter(checkbox => checkbox.checked);
                if (selectedCount) selectedCount.textContent = checked.length;
                if (bulkDeleteButton) bulkDeleteButton.disabled = checked.length === 0;
                if (bulkToolbar) bulkToolbar.classList.toggle('has-selection', checked.length > 0);
                if (selectAll) {
                    selectAll.checked = rowCheckboxes.length > 0 && checked.length === rowCheckboxes.length;
                    selectAll.indeterminate = checked.length > 0 && checked.length < rowCheckboxes.length;
                }
                rowCheckboxes.forEach(checkbox => {
                    checkbox.closest('[data-question-row]')?.classList.toggle('is-selected', checkbox
                        .checked);
                });
            };

            selectAll?.addEventListener('change', () => {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                updateBulkSelection();
            });
            rowCheckboxes.forEach(checkbox => checkbox.addEventListener('change', updateBulkSelection));
            updateBulkSelection();
            document.addEventListener('submit', event => {
                const form = event.target;
                const submitter = event.submitter;

                if (form.id === 'bulk-question-form') {
                    const count = rowCheckboxes.filter(checkbox => checkbox.checked).length;
                    if (count === 0 || !confirm(
                            `Lưu trữ ${count} câu hỏi đã chọn? Các đề đã phát và dữ liệu bài làm vẫn được giữ nguyên.`
                        )) {
                        event.preventDefault();
                        return;
                    }
                } else if (submitter?.dataset.confirm && !confirm(submitter.dataset.confirm)) {
                    event.preventDefault();
                    return;
                }

                if (submitter && submitter.type === 'submit') {
                    submitter.disabled = true;
                    submitter.classList.add('is-loading');
                }
            });
        });
    </script>
@endpush
