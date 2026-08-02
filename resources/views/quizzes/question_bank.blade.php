@extends('layouts.app')

@section('title', 'Ngân hàng câu hỏi')

@section('content')
    @push('styles')
        @vite('resources/css/pages/question-bank.css')
    @endpush

    @php
        $hasActiveFilters =
            request()->hasAny(['course_id', 'question_type', 'question_bank_id']) ||
            (request('status') && request('status') !== 'active');
    @endphp

    <div class="lms-page question-bank-page">
        <section class="question-overview" aria-label="Tổng quan ngân hàng câu hỏi">
            <x-ui.page-header title="Ngân hàng câu hỏi">
                <x-slot:meta>
                    <span><i class="fa-solid fa-circle-question"></i> Soạn, phân loại và tái sử dụng câu hỏi kiểm tra</span>
                </x-slot:meta>

                <x-slot:actions>
                    <x-ui.button :href="route('quizzes.ai_generate')" tone="outline" icon="fa-wand-magic-sparkles">
                        Tạo bằng AI
                    </x-ui.button>
                    <x-ui.button icon="fa-plus" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                        Thêm câu hỏi
                    </x-ui.button>
                    <div class="dropdown question-tools-dropdown">
                        <button type="button" class="lms-btn lms-btn-outline" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="fa-solid fa-ellipsis" aria-hidden="true"></i> Công cụ
                        </button>
                        <div class="dropdown-menu dropdown-menu-end question-tools-menu">
                            <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                data-bs-target="#passageModal">
                                <i class="fa-solid fa-file-lines" aria-hidden="true"></i> Quản lý ngữ liệu
                            </button>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                data-bs-target="#addQuestionBankModal">
                                <i class="fa-solid fa-layer-group" aria-hidden="true"></i> Tạo ngân hàng
                            </button>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                data-bs-target="#attachQuestionBankModal">
                                <i class="fa-solid fa-link" aria-hidden="true"></i> Gắn ngân hàng với khóa học
                            </button>
                            <div class="dropdown-divider"></div>
                            <button type="button" class="dropdown-item text-success" data-bs-toggle="modal"
                                data-bs-target="#importQuestionModal">
                                <i class="fa-solid fa-file-excel" aria-hidden="true"></i> Nhập câu hỏi từ Excel
                            </button>
                        </div>
                    </div>
                </x-slot:actions>
            </x-ui.page-header>

            <div class="question-stats" aria-label="Thống kê câu hỏi theo bộ lọc">
                <article class="question-stat question-stat--total">
                    <span class="question-stat__icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span>
                    <div><strong>{{ (int) $questionStats->total }}</strong><span>Tổng câu hỏi</span></div>
                </article>
                <article class="question-stat question-stat--easy">
                    <span class="question-stat__icon"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
                    <div><strong>{{ (int) $questionStats->easy }}</strong><span>Mức dễ</span></div>
                </article>
                <article class="question-stat question-stat--medium">
                    <span class="question-stat__icon"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i></span>
                    <div><strong>{{ (int) $questionStats->medium }}</strong><span>Mức trung bình</span></div>
                </article>
                <article class="question-stat question-stat--hard">
                    <span class="question-stat__icon"><i class="fa-solid fa-fire" aria-hidden="true"></i></span>
                    <div><strong>{{ (int) $questionStats->hard }}</strong><span>Mức khó</span></div>
                </article>
            </div>
        </section>

        <form action="{{ route('questions.index') }}" method="GET" class="question-filter-panel"
            id="question-filter-form">
            <div class="question-filter-field">
                <label for="filter-course">Khóa học</label>
                <select name="course_id" id="filter-course" class="form-select">
                    <option value="">Tất cả khóa học</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="question-filter-field">
                <label for="filter-bank">Ngân hàng</label>
                <select name="question_bank_id" id="filter-bank" class="form-select">
                    <option value="">Tất cả ngân hàng</option>
                    @foreach ($questionBanks as $bank)
                        <option value="{{ $bank->id }}" @selected(request('question_bank_id') == $bank->id)>{{ $bank->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="question-filter-field">
                <label for="filter-type">Hình thức</label>
                <select name="question_type" id="filter-type" class="form-select">
                    <option value="">Tất cả hình thức</option>
                    @foreach ($questionTypeLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('question_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="question-filter-field question-filter-field--status">
                <label for="filter-status">Trạng thái</label>
                <select name="status" id="filter-status" class="form-select">
                    <option value="active" @selected(request('status', 'active') === 'active')>Đang sử dụng</option>
                    <option value="archived" @selected(request('status') === 'archived')>Đã lưu trữ</option>
                    <option value="all" @selected(request('status') === 'all')>Tất cả trạng thái</option>
                </select>
            </div>
            <div class="question-filter-actions">
                <x-ui.button type="submit" icon="fa-filter">Áp dụng</x-ui.button>
                @if ($hasActiveFilters)
                    <x-ui.button :href="route('questions.index')" tone="outline" icon="fa-rotate-left">Đặt lại</x-ui.button>
                @endif
            </div>
            <div class="question-filter-summary">
                Hiển thị {{ $questions->firstItem() ?? 0 }}–{{ $questions->lastItem() ?? 0 }} trong
                {{ $questions->total() }} câu hỏi phù hợp
            </div>
        </form>

        <section class="table-card question-list-card" aria-label="Danh sách câu hỏi">
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

        <div class="question-table-wrap">
            <table class="question-table">
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
                        <th class="question-table__content" scope="col">Nội dung câu hỏi</th>
                        <th class="question-table__classification" scope="col">Phân loại</th>
                        <th class="question-table__scope" scope="col">Phạm vi sử dụng</th>
                        <th class="question-table__actions" scope="col">Thao tác</th>
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
                            <td class="selection-column" data-label="Chọn">
                                @if (!$isArchived)
                                    <input type="checkbox" class="question-checkbox question-row-checkbox"
                                        name="question_ids[]" value="{{ $question->id }}" form="bulk-question-form"
                                        aria-label="Chọn câu hỏi #{{ $question->id }}">
                                @else
                                    <i class="fa-solid fa-box-archive archived-row-icon" title="Câu hỏi đã lưu trữ"
                                        aria-hidden="true"></i>
                                @endif
                            </td>
                            <td data-label="Nội dung câu hỏi">
                                <div class="question-heading-line">
                                    <span class="q-id">#{{ $question->id }}</span>
                                    @if ($isArchived)
                                        <span class="question-status-badge"><i class="fa-solid fa-box-archive"
                                                aria-hidden="true"></i> Đã lưu trữ</span>
                                    @endif
                                </div>
                                <div class="q-text">{{ Str::limit($question->question_text, 125) }}</div>
                                @if ($question->passage)
                                    <div class="question-passage"><i class="fa-solid fa-file-lines"
                                            aria-hidden="true"></i> {{ Str::limit($question->passage->title, 65) }}</div>
                                @endif
                                <div class="q-answer">
                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                    {{ Str::limit($question->answerSummary(), 95) }}
                                </div>
                            </td>
                            <td data-label="Phân loại">
                                <div class="question-classification">
                                    <span class="type-badge type-{{ $question->question_type }}">{{ $question->typeLabel() }}</span>
                                    @if ($question->difficulty === 'easy')
                                        <span class="diff-badge diff-easy">Dễ</span>
                                    @elseif ($question->difficulty === 'medium')
                                        <span class="diff-badge diff-medium">Trung bình</span>
                                    @else
                                        <span class="diff-badge diff-hard">Khó</span>
                                    @endif
                                </div>
                                @if ($question->observedDifficultyLabel())
                                    <div class="question-observed" title="Độ khó thực tế được tính từ kết quả làm bài">
                                        Thực tế: {{ $question->observedDifficultyLabel() }} ·
                                        {{ round(((float) ($difficultyMetrics['accuracy'] ?? 0)) * 100) }}% đúng /
                                        {{ $sampleSize }} lượt
                                    </div>
                                @elseif ($sampleSize > 0)
                                    <div class="question-observed">Cần {{ max(0, 5 - $sampleSize) }} lượt nữa để đánh giá</div>
                                @endif
                            </td>
                            <td data-label="Phạm vi sử dụng">
                                <div class="question-scope">
                                    <div class="question-scope__bank" title="Ngân hàng câu hỏi">
                                        <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                                        {{ $question->questionBank->name ?? ($question->course->title ?? 'Chưa có ngân hàng') }}
                                    </div>
                                    <div class="question-scope__courses" title="Khóa học sử dụng">
                                        <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                                        {{ $question->questionBank?->courses?->pluck('title')->take(2)->implode(', ') ?: $question->course->title ?? 'Chưa gắn khóa học' }}
                                        @if (($question->questionBank?->courses?->count() ?? 0) > 2)
                                            và {{ $question->questionBank->courses->count() - 2 }} khóa khác
                                        @endif
                                    </div>
                                    <div class="question-scope__teacher" title="Giáo viên phụ trách">
                                        <i class="fa-solid fa-user-tie" aria-hidden="true"></i>
                                        {{ $question->questionBank->teacher->name ?? ($question->course->teacher->name ?? 'Chưa xác định') }}
                                    </div>
                                </div>
                            </td>
                            <td data-label="Thao tác">
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
                                <div class="question-row-actions">
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
                                            <i class="fa-solid fa-pen" aria-hidden="true"></i><span>Sửa</span>
                                        </button>
                                        <form action="{{ route('questions.destroyBank', $question->id) }}" method="POST"
                                            class="question-action-form">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="action-btn danger"
                                                data-confirm="Lưu trữ câu hỏi này? Đáp án và dữ liệu liên quan vẫn được giữ lại."
                                                title="Lưu trữ" aria-label="Lưu trữ câu hỏi #{{ $question->id }}">
                                                <i class="fa-solid fa-box-archive" aria-hidden="true"></i><span>Lưu trữ</span>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('questions.restoreBank', $question->id) }}" method="POST"
                                            class="question-action-form">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="action-btn restore"
                                                data-confirm="Khôi phục câu hỏi này vào danh sách đang sử dụng?"
                                                title="Khôi phục" aria-label="Khôi phục câu hỏi #{{ $question->id }}">
                                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span>Khôi phục</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="5">
                                <x-ui.empty-state
                                    :title="request('status') === 'archived' ? 'Chưa có câu hỏi lưu trữ' : 'Không tìm thấy câu hỏi'"
                                    :description="$hasActiveFilters ? 'Hãy thay đổi điều kiện hoặc đặt lại bộ lọc.' : 'Hãy thêm câu hỏi đầu tiên vào ngân hàng.'"
                                    icon="fa-box-open">
                                    @if ($hasActiveFilters)
                                        <x-ui.button :href="route('questions.index')" tone="outline" size="sm"
                                            icon="fa-rotate-left">Đặt lại bộ lọc</x-ui.button>
                                    @endif
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($questions->hasPages())
            <div class="pagination-wrap">{{ $questions->appends(request()->query())->links() }}</div>
        @endif
        </section>
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
    {{-- Interaction states shared by the table and modal forms. --}}
    <style>
        .action-btn:focus-visible,
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
