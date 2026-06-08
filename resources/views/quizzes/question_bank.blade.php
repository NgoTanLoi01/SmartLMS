@extends('layouts.app')

@section('title', 'Ngân hàng câu hỏi')

@section('content')
    <style>
        /* ── Page header ── */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 22px;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 4px;
        }

        .page-subtitle {
            font-size: 13.5px;
            color: #64748b;
            margin: 0;
        }

        .btn-group-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-act {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 13.5px;
            font-weight: 500;
            font-family: 'Be Vietnam Pro', sans-serif;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
            white-space: nowrap;
        }

        .btn-act i {
            font-size: 12px;
        }

        .btn-act-primary {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }

        .btn-act-primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #fff;
        }

        .btn-act-ghost {
            background: #fff;
            color: #0f172a;
            border-color: #e2e8f0;
        }

        .btn-act-ghost:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .btn-act-ghost-green {
            background: #fff;
            color: #16a34a;
            border-color: #bbf7d0;
        }

        .btn-act-ghost-green:hover {
            background: #f0fdf4;
            color: #15803d;
        }

        /* ── Filter bar ── */
        .filter-bar {
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .filter-bar .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 220px;
            flex: 1;
        }

        .filter-label {
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
        }

        .filter-bar select {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 14px;
            font-family: 'Be Vietnam Pro', sans-serif;
            color: #0f172a;
            background: #f8fafc;
            transition: border-color 0.15s;
        }

        .filter-bar select:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        }

        .stat-chips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .stat-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 500;
            border: 1px solid;
        }

        .chip-easy {
            background: #f0fdf4;
            color: #16a34a;
            border-color: #bbf7d0;
        }

        .chip-medium {
            background: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }

        .chip-hard {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        /* ── Table card ── */
        .table-card {
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 14px;
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead tr {
            background: #f8fafc;
            border-bottom: 1px solid #e8edf3;
        }

        .data-table thead th {
            padding: 11px 16px;
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
            white-space: nowrap;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.12s;
        }

        .data-table tbody tr:last-child {
            border-bottom: none;
        }

        .data-table tbody tr:hover {
            background: #f8fafc;
        }

        .data-table td {
            padding: 13px 16px;
            vertical-align: middle;
        }

        .q-id {
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
        }

        .q-text {
            font-size: 14px;
            font-weight: 500;
            color: #0f172a;
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .q-answer {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: #16a34a;
            font-weight: 500;
        }

        .q-answer i {
            font-size: 11px;
        }

        .q-course {
            font-size: 13px;
            color: #334155;
            font-weight: 500;
        }

        .teacher-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: #334155;
            font-weight: 500;
        }

        .teacher-avatar-sm {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            flex-shrink: 0;
        }

        .diff-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .diff-easy {
            background: #f0fdf4;
            color: #16a34a;
        }

        .diff-medium {
            background: #fffbeb;
            color: #b45309;
        }

        .diff-hard {
            background: #fef2f2;
            color: #dc2626;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
            color: #64748b;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }

        .action-btn:hover {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #2563eb;
        }

        .action-btn.danger:hover {
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
        }

        /* ── Empty state ── */
        .empty-row td {
            padding: 56px 20px;
            text-align: center;
            color: #94a3b8;
        }

        .empty-row i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 12px;
            opacity: .35;
        }

        .empty-row p {
            font-size: 14px;
            margin: 0;
        }

        /* ── Pagination ── */
        .pagination-wrap {
            padding: 14px 20px;
            border-top: 1px solid #f1f5f9;
            background: #fff;
        }

        /* ── Modals ── */
        .modal {
            z-index: 1060 !important;
        }

        .modal-content {
            border: 1px solid #e8edf3;
            border-radius: 14px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 20px 24px 0;
            border: none;
            align-items: flex-start;
        }

        .modal-title {
            font-size: 17px;
            font-weight: 600;
            color: #0f172a;
        }

        .modal-title i {
            color: #2563eb;
            margin-right: 8px;
        }

        .modal-body {
            padding: 20px 24px;
        }

        .modal-footer {
            padding: 0 24px 20px;
            border: none;
            gap: 8px;
        }

        .form-label-sm {
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
            display: block;
            margin-bottom: 5px;
        }

        .form-ctrl {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 14px;
            font-family: 'Be Vietnam Pro', sans-serif;
            color: #0f172a;
            transition: border-color 0.15s, box-shadow 0.15s;
            background: #fff;
        }

        .form-ctrl:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
            outline: none;
        }

        textarea.form-ctrl {
            resize: vertical;
        }

        .section-divider {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 14px;
        }

        .answer-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            background: #f8fafc;
            border: 1px solid #e8edf3;
            border-radius: 10px;
            padding: 10px 14px;
            transition: border-color 0.15s, background 0.15s;
        }

        .answer-row:has(input[type=radio]:checked) {
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .answer-label {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .answer-row:has(input[type=radio]:checked) .answer-label {
            background: #2563eb;
            color: #fff;
        }

        .answer-input {
            flex: 1;
            border: none;
            background: transparent;
            font-size: 14px;
            font-family: 'Be Vietnam Pro', sans-serif;
            color: #0f172a;
        }

        .answer-input:focus {
            outline: none;
        }

        .info-note {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 12.5px;
            color: #1e40af;
            display: flex;
            gap: 8px;
            align-items: flex-start;
            margin-top: 14px;
        }

        .warn-note {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: #92400e;
        }

        .warn-note ol {
            margin: 8px 0 0;
            padding-left: 18px;
        }

        .warn-note li {
            margin-bottom: 2px;
        }

        .btn-modal-cancel {
            background: #f1f5f9;
            color: #334155;
            border: none;
            border-radius: 10px;
            padding: 9px 20px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Be Vietnam Pro', sans-serif;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-modal-cancel:hover {
            background: #e2e8f0;
        }

        .btn-modal-submit {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 9px 24px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Be Vietnam Pro', sans-serif;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-modal-submit:hover {
            background: #1d4ed8;
        }

        .btn-modal-submit.green {
            background: #16a34a;
        }

        .btn-modal-submit.green:hover {
            background: #15803d;
        }

        .row-g {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .col-flex-2 {
            flex: 2;
            min-width: 160px;
        }

        .col-flex-1 {
            flex: 1;
            min-width: 120px;
        }

        .col-flex-full {
            flex: 1 1 100%;
        }
    </style>

    {{-- ── Page Header ── --}}
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-database"
                    style="color:#2563eb; font-size:18px; margin-right:10px;"></i>Ngân hàng câu hỏi</h1>
            <p class="page-subtitle">Quản lý kho câu hỏi trắc nghiệm dùng để trộn đề thi ngẫu nhiên</p>
        </div>
        <div class="btn-group-actions">
            <button class="btn-act btn-act-ghost-green" data-bs-toggle="modal" data-bs-target="#importQuestionModal">
                <i class="fas fa-file-excel"></i> Nhập từ Excel
            </button>
            <a href="{{ route('quizzes.ai_generate') }}" class="btn-act btn-act-ghost">
                <i class="fas fa-magic"></i> Tạo bằng AI
            </a>
            <button class="btn-act btn-act-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                <i class="fas fa-plus"></i> Thêm câu hỏi
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
        </form>

        <div>
            <div class="filter-label" style="margin-bottom:8px;">Thống kê</div>
            <div class="stat-chips">
                <span class="stat-chip chip-easy">
                    <i class="fas fa-circle" style="font-size:7px;"></i>
                    Dễ: {{ $questions->where('difficulty', 'easy')->count() }}
                </span>
                <span class="stat-chip chip-medium">
                    <i class="fas fa-circle" style="font-size:7px;"></i>
                    Trung bình: {{ $questions->where('difficulty', 'medium')->count() }}
                </span>
                <span class="stat-chip chip-hard">
                    <i class="fas fa-circle" style="font-size:7px;"></i>
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
                        <th>Khóa học</th>
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
                                @php $correctOpt = $question->options->where('is_correct', true)->first(); @endphp
                                <div class="q-answer">
                                    <i class="fas fa-check-circle"></i>
                                    {{ $correctOpt ? Str::limit($correctOpt->option_text, 45) : 'Chưa có đáp án đúng' }}
                                </div>
                            </td>
                            <td><span class="q-course">{{ $question->course->title ?? 'N/A' }}</span></td>
                            <td>
                                <div class="teacher-chip">
                                    <div class="teacher-avatar-sm"><i class="fas fa-user-tie" style="font-size:10px;"></i>
                                    </div>
                                    {{ $question->course->teacher->name ?? 'N/A' }}
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
                            </td>
                            <td>
                                <div style="display:flex; gap:6px; justify-content:flex-end;">
                                    <button type="button" class="action-btn" data-bs-toggle="modal"
                                        data-bs-target="#editQuestionModal" data-id="{{ $question->id }}"
                                        data-course="{{ $question->course_id }}"
                                        data-difficulty="{{ $question->difficulty }}"
                                        data-text="{{ htmlspecialchars($question->question_text) }}"
                                        data-options="{{ $question->options->sortBy('id')->values()->toJson() }}"
                                        title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('questions.destroyBank', $question->id) }}" method="POST"
                                        style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-btn danger"
                                            onclick="return confirm('Xóa câu hỏi này ra khỏi ngân hàng?')" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="6">
                                <i class="fas fa-box-open"></i>
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

    {{-- ══════════════════════════════
     MODAL: Thêm câu hỏi
══════════════════════════════ --}}
    <div class="modal fade" id="addQuestionModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('questions.storeBank') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-pen-square"></i>Thêm câu hỏi mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row-g">
                            <div class="col-flex-2">
                                <label class="form-label-sm">Khóa học</label>
                                <select name="course_id" class="form-ctrl" required>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}"
                                            {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                            {{ $course->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-flex-1">
                                <label class="form-label-sm">Độ khó</label>
                                <select name="difficulty" class="form-ctrl" required>
                                    <option value="easy">Dễ</option>
                                    <option value="medium" selected>Trung bình</option>
                                    <option value="hard">Khó</option>
                                </select>
                            </div>
                            <div class="col-flex-full">
                                <label class="form-label-sm">Nội dung câu hỏi</label>
                                <textarea name="question_text" class="form-ctrl" rows="3" placeholder="Nhập câu hỏi tại đây..." required></textarea>
                            </div>
                        </div>

                        <div class="section-divider">Các đáp án — tích chọn đáp án đúng</div>

                        @for ($i = 1; $i <= 4; $i++)
                            <div class="answer-row">
                                <input type="radio" name="correct_option" value="{{ $i }}"
                                    {{ $i == 1 ? 'checked' : '' }} required style="cursor:pointer; flex-shrink:0;">
                                <div class="answer-label">{{ chr(64 + $i) }}</div>
                                <input type="text" name="options[{{ $i }}]" class="answer-input"
                                    placeholder="Nhập đáp án {{ chr(64 + $i) }}..." required>
                            </div>
                        @endfor

                        <div class="info-note">
                            <i class="fas fa-info-circle" style="margin-top:1px; flex-shrink:0;"></i>
                            Khi học sinh làm bài, thứ tự 4 đáp án sẽ được xáo trộn ngẫu nhiên.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn-modal-submit">Lưu vào kho</button>
                    </div>
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
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit"></i>Chỉnh sửa câu hỏi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row-g">
                            <div class="col-flex-2">
                                <label class="form-label-sm">Khóa học</label>
                                <select name="course_id" id="edit_course_id" class="form-ctrl" required>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-flex-1">
                                <label class="form-label-sm">Độ khó</label>
                                <select name="difficulty" id="edit_difficulty" class="form-ctrl" required>
                                    <option value="easy">Dễ</option>
                                    <option value="medium">Trung bình</option>
                                    <option value="hard">Khó</option>
                                </select>
                            </div>
                            <div class="col-flex-full">
                                <label class="form-label-sm">Nội dung câu hỏi</label>
                                <textarea name="question_text" id="edit_question_text" class="form-ctrl" rows="3" required></textarea>
                            </div>
                        </div>

                        <div class="section-divider">Các đáp án — tích chọn đáp án đúng</div>

                        @for ($i = 1; $i <= 4; $i++)
                            <div class="answer-row">
                                <input type="radio" name="correct_option" id="edit_correct_{{ $i }}"
                                    value="{{ $i }}" required style="cursor:pointer; flex-shrink:0;">
                                <div class="answer-label">{{ chr(64 + $i) }}</div>
                                <input type="text" name="options[{{ $i }}]"
                                    id="edit_option_{{ $i }}" class="answer-input" required>
                            </div>
                        @endfor
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn-modal-submit">Cập nhật</button>
                    </div>
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
                            <i class="fas fa-file-upload" style="color:#16a34a;"></i>Nhập câu hỏi từ file
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
                        <div style="margin-bottom:16px;">
                            <label class="form-label-sm">2. Tải lên file .xlsx</label>
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
            var editModal = document.getElementById('editQuestionModal');
            if (!editModal) return;

            editModal.addEventListener('show.bs.modal', function(event) {
                var btn = event.relatedTarget;
                var id = btn.getAttribute('data-id');
                var courseId = btn.getAttribute('data-course');
                var difficulty = btn.getAttribute('data-difficulty');
                var text = btn.getAttribute('data-text');
                var options = JSON.parse(btn.getAttribute('data-options') || '[]');

                document.getElementById('editQuestionForm').action = '/question-bank/' + id;
                document.getElementById('edit_course_id').value = courseId;
                document.getElementById('edit_difficulty').value = difficulty;
                document.getElementById('edit_question_text').value = text;

                for (var j = 1; j <= 4; j++) {
                    document.getElementById('edit_correct_' + j).checked = false;
                }
                for (var i = 0; i < 4; i++) {
                    var n = i + 1;
                    if (options[i]) {
                        document.getElementById('edit_option_' + n).value = options[i].option_text;
                        if (options[i].is_correct == 1 || options[i].is_correct === true) {
                            document.getElementById('edit_correct_' + n).checked = true;
                        }
                    }
                }
            });
        });
    </script>
@endpush
