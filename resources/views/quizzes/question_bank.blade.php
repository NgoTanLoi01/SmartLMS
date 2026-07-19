@extends('layouts.app')

@section('title', 'Ngân hàng câu hỏi')

@section('content')
    @vite('resources/css/pages/question-bank.css')

    {{-- ── Page Header ── --}}
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-database"
                    style="color:#2563eb; font-size:18px; margin-right:10px;"></i>Ngân hàng câu hỏi</h1>
            <p class="page-subtitle">Quản lý kho câu hỏi trắc nghiệm dùng để trộn đề thi ngẫu nhiên</p>
        </div>
        <div class="btn-group-actions">
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
                                @php $correctOpt = $question->options->where('is_correct', true)->first(); @endphp
                                <div class="q-answer">
                                    <i class="fa-solid fa-circle-check"></i>
                                    {{ $correctOpt ? Str::limit($correctOpt->option_text, 45) : 'Chưa có đáp án đúng' }}
                                </div>
                            </td>
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
                            </td>
                            <td>
                                <div style="display:flex; gap:6px; justify-content:flex-end;">
                                    <button type="button" class="action-btn" data-bs-toggle="modal"
                                        data-bs-target="#editQuestionModal" data-id="{{ $question->id }}"
                                        data-course="{{ $question->course_id }}"
                                        data-bank="{{ $question->question_bank_id }}"
                                        data-difficulty="{{ $question->difficulty }}"
                                        data-text="{{ htmlspecialchars($question->question_text) }}"
                                        data-options="{{ $question->options->sortBy('id')->values()->toJson() }}"
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
                            <td colspan="7">
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
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa-solid fa-pen-square"></i>Thêm câu hỏi mới</h5>
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
                            <div class="col-flex-2">
                                <label class="form-label-sm">Ngân hàng câu hỏi</label>
                                <select name="question_bank_id" class="form-ctrl">
                                    <option value="">Tự chọn/tạo theo khóa học</option>
                                    @foreach ($questionBanks as $bank)
                                        <option value="{{ $bank->id }}" {{ request('question_bank_id') == $bank->id ? 'selected' : '' }}>
                                            {{ $bank->name }}
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
                            <i class="fa-solid fa-circle-info" style="margin-top:1px; flex-shrink:0;"></i>
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
                        <h5 class="modal-title"><i class="fa-solid fa-edit"></i>Chỉnh sửa câu hỏi</h5>
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
                            <div class="col-flex-2">
                                <label class="form-label-sm">Ngân hàng câu hỏi</label>
                                <select name="question_bank_id" id="edit_question_bank_id" class="form-ctrl">
                                    <option value="">Tự chọn/tạo theo khóa học</option>
                                    @foreach ($questionBanks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
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
            var editModal = document.getElementById('editQuestionModal');
            if (!editModal) return;

            editModal.addEventListener('show.bs.modal', function(event) {
                var btn = event.relatedTarget;
                var id = btn.getAttribute('data-id');
                var courseId = btn.getAttribute('data-course');
                var bankId = btn.getAttribute('data-bank') || '';
                var difficulty = btn.getAttribute('data-difficulty');
                var text = btn.getAttribute('data-text');
                var options = JSON.parse(btn.getAttribute('data-options') || '[]');

                document.getElementById('editQuestionForm').action = '/question-bank/' + id;
                document.getElementById('edit_course_id').value = courseId;
                document.getElementById('edit_question_bank_id').value = bankId;
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
