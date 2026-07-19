@extends('layouts.app')

@section('title', 'Quản lý bài tập')
<style>
    .modal.fade .modal-dialog {
        transition: transform .3s ease-out;
        transform: translate(0, 190px) !important;
    }
</style>
@section('content')
    <div class="container-fluid py-4 assignments-page">
        <div class="assignments-header mb-4 px-2">
            <div>
                <h3 class="fw-bold mb-0 text-dark">Danh sách bài tập</h3>
                <p class="text-muted mb-0 small">Quản lý các yêu cầu thực hành và nộp bài</p>
            </div>

            @if (auth()->user()->role === 'admin' || auth()->user()->role === 'teacher')
                <button class="btn btn-primary rounded-pill px-4 shadow-sm assignments-create-btn" data-bs-toggle="modal"
                    data-bs-target="#addAssignmentModal">
                    <i class="fa-solid fa-plus me-1"></i> Tạo bài tập mới
                </button>
            @endif
        </div>

        <div class="row g-4">
            @forelse($assignments as $assignment)
                @php
                    $assignmentTypeLabel = match ($assignment->type ?? 'file') {
                        'essay' => 'Tự luận',
                        'mixed' => 'File + tự luận',
                        default => 'Nộp file',
                    };
                    $submission = auth()->user()->role === 'student' ? $assignment->submissions->first() : null;
                @endphp
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100 rounded-3 hover-shadow transition-all assignment-card">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="assignment-card-top mb-3">
                                <div class="assignment-badges">
                                    <div class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2 fw-bold small assignment-badge">
                                        <i class="fa-solid fa-book me-1"></i> {{ $assignment->course->title }}
                                    </div>
                                    <div class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 fw-bold small assignment-badge">
                                        <i class="fa-solid fa-pen me-1"></i> {{ $assignmentTypeLabel }}
                                    </div>
                                </div>

                                @if (auth()->user()->role === 'student')
                                    @if ($submission)
                                        <div class="badge bg-success rounded-pill px-2 py-1 small">
                                            <i class="fa-solid fa-check"></i> Đã nộp
                                        </div>
                                    @else
                                        <div class="badge bg-warning text-dark rounded-pill px-2 py-1 small">
                                            <i class="fa-solid fa-clock"></i> Chưa nộp
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <h5 class="fw-bold text-dark mb-2 assignment-title">{{ $assignment->title }}</h5>

                            {{-- Sử dụng strip_tags để loại bỏ thẻ HTML trước khi cắt chuỗi, tránh lỗi hiển thị trên Card --}}
                            <p class="text-muted small mb-3 flex-grow-1">
                                {{ Str::limit(strip_tags($assignment->instructions), 100) }}
                            </p>

                            <div class="bg-light rounded p-3 mb-3">
                                <div class="d-flex align-items-center mb-2 small">
                                    <i class="fa-solid fa-calendar-days text-danger me-2"></i>
                                    <span class="fw-bold">Hạn nộp:</span>
                                    <span class="ms-1">{{ $assignment->due_date->format('d/m/Y H:i') }}</span>
                                </div>
                                @if (($assignment->type ?? 'file') !== 'essay')
                                    <div class="d-flex align-items-start small">
                                        <i class="fa-solid fa-file-arrow-up text-muted me-2"></i>
                                        <span class="text-muted assignment-extensions">Định dạng: {{ $assignment->allowed_extensions }}</span>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center small">
                                        <i class="fa-solid fa-align-left text-muted me-2"></i>
                                        <span class="text-muted">Nhập câu trả lời trực tiếp</span>
                                    </div>
                                @endif
                            </div>

                            <div class="d-grid gap-2">
                                @if (auth()->user()->role === 'student')
                                    {{-- Truyền instructions đã loại bỏ tag vào data-attribute để Modal JS xử lý an toàn --}}
                                    <button class="btn btn-outline-primary rounded-pill fw-bold" data-bs-toggle="modal"
                                        data-bs-target="#submitAssignmentModal" data-id="{{ $assignment->id }}"
                                        data-title="{{ $assignment->title }}"
                                        data-instructions="{{ strip_tags($assignment->instructions) }}"
                                        data-extensions="{{ $assignment->allowed_extensions }}"
                                        data-type="{{ $assignment->type ?? 'file' }}"
                                        data-has-file="{{ $submission && $submission->file_path ? '1' : '0' }}"
                                        data-text-answer='@json($submission?->text_answer ?? "")'>
                                        {{ $submission ? 'Nộp lại bài làm' : 'Bắt đầu làm bài' }}
                                    </button>

                                    @if ($submission && $submission->grade)
                                        <div
                                            class="mt-2 p-2 bg-success bg-opacity-10 rounded border border-success border-opacity-25 text-center">
                                            <span class="small fw-bold text-success">Điểm:
                                                {{ $submission->grade }}/10</span>
                                        </div>
                                    @endif
                                @else
                                    <button type="button"
                                        class="btn btn-light rounded-pill fw-bold border view-assignment-submissions-btn"
                                        data-assignment-id="{{ $assignment->id }}"
                                        data-assignment-title="{{ $assignment->title }}"
                                        data-url="{{ route('assignments.submissions.list', $assignment->id) }}">
                                        <i class="fa-solid fa-eye me-1"></i> Xem bài nộp
                                        ({{ $assignment->submissions->count() }})
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="fa-solid fa-list-check fa-3x text-muted opacity-50 mb-3"></i>
                    <h5 class="text-muted fw-bold">Chưa có bài tập nào được giao</h5>
                </div>
            @endforelse
        </div>
    </div>

    <div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('assignments.store') }}" method="POST" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">Giao bài tập mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold small text-muted">Tiêu đề bài tập</label>
                            <input type="text" name="title" class="form-control bg-light border-0 py-2"
                                placeholder="VD: Thực hành Migration" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Chọn khóa học</label>
                            <select name="course_id" id="createAssignmentCourseSelect"
                                class="form-select bg-light border-0 py-2" required>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Loại bài tập</label>
                            <select name="type" class="form-select bg-light border-0 py-2" required>
                                <option value="file">Nộp file</option>
                                <option value="essay">Tự luận</option>
                                <option value="mixed">File + tự luận</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Thuộc bài học</label>
                            <select name="lesson_id" id="createAssignmentLessonSelect"
                                class="form-select bg-light border-0 py-2" required>
                                @foreach ($courses as $course)
                                    @foreach ($course->modules as $module)
                                        @foreach ($module->lessons as $lesson)
                                            <option value="{{ $lesson->id }}" data-course="{{ $course->id }}">
                                                {{ $course->title }} - {{ $module->title }} - {{ $lesson->title }}
                                            </option>
                                        @endforeach
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">Yêu cầu bài tập</label>
                            <textarea name="instructions" class="form-control bg-light border-0" rows="4"
                                placeholder="Viết mô tả chi tiết yêu cầu nộp bài..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Thang điểm</label>
                            <input type="number" name="grading_scale" class="form-control bg-light border-0 py-2"
                                value="10" min="1" max="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">AI hỗ trợ chấm</label>
                            <select name="ai_grading_enabled" class="form-select bg-light border-0 py-2">
                                <option value="1">Bật AI hỗ trợ chấm</option>
                                <option value="0">Tắt AI hỗ trợ chấm</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">Tiêu chí chấm điểm</label>
                            <textarea name="grading_rubric" class="form-control bg-light border-0" rows="4"
                                placeholder="VD: Đúng yêu cầu: 4 điểm&#10;Đầy đủ ý: 3 điểm&#10;Ví dụ minh họa: 2 điểm&#10;Trình bày rõ ràng: 1 điểm"></textarea>
                            <div class="form-text">AI sẽ ưu tiên chấm theo tiêu chí này để nhận xét bám sát hơn.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Hạn chót (Deadline)</label>
                            <input type="datetime-local" name="due_date" class="form-control bg-light border-0 py-2"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Trạng thái</label>
                            <select name="status" class="form-select bg-light border-0 py-2">
                                <option value="published">Xuất bản ngay</option>
                                <option value="draft">Lưu nháp</option>
                                <option value="hidden">Ẩn khỏi học sinh</option>
                                <option value="archived">Lưu trữ</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Định dạng cho phép (Cách nhau dấu
                                phẩy)</label>
                            <input type="text" name="allowed_extensions" class="form-control bg-light border-0 py-2"
                                value="pdf,docx,txt,md,html,htm,css,js,php,png,jpg,jpeg">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Dung lượng tối đa (KB)</label>
                            <input type="number" name="max_file_size" class="form-control bg-light border-0 py-2"
                                value="5120">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Tạo bài tập</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="submitAssignmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="submitForm" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="submitModalTitle">Nộp bài tập</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="fw-bold small mb-1">Hướng dẫn nộp bài:</h6>
                        <p class="small text-muted mb-0" id="submitInstructions"></p>
                    </div>

                    <div class="mb-3 d-none" id="submitEssayField">
                        <label class="form-label fw-bold small text-muted">Bài làm tự luận</label>
                        <textarea name="text_answer" id="submitTextAnswer" class="form-control bg-light border-0" rows="8"
                            placeholder="Nhập bài làm tự luận của bạn..."></textarea>
                    </div>

                    <div class="mb-3" id="submitFileField">
                        <label class="form-label fw-bold small text-muted">Chọn file từ máy tính</label>
                        <input type="file" name="file" id="submitFileInput" class="form-control bg-light border-0 py-2">
                        <div class="form-text small">Chỉ chấp nhận file định dạng yêu cầu, tối đa 5MB.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">Gửi bài làm</button>
                </div>
            </form>
        </div>
    </div>

    @if (auth()->user()->role === 'admin' || auth()->user()->role === 'teacher')
        <div class="modal fade" id="assignmentSubmissionsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title fw-bold text-dark" id="assignmentSubmissionsTitle">Bài nộp</h5>
                            <p class="text-muted small mb-0" id="assignmentSubmissionsMeta">Đang tải dữ liệu...</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body py-4">
                        <div id="assignmentSubmissionsContent">
                            <div class="text-center py-5 text-muted">
                                <div class="spinner-border text-primary mb-3"></div>
                                <div>Đang tải danh sách bài nộp...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        .assignments-header {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
        }

        .assignment-card {
            min-width: 0;
        }

        .assignment-card-top {
            align-items: flex-start;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            min-width: 0;
        }

        .assignment-badges {
            align-items: flex-start;
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .assignment-badge {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
            text-align: left;
            line-height: 1.35;
        }

        .assignment-title,
        .assignment-extensions {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .hover-shadow:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
        }

        .transition-all {
            transition: all 0.3s ease;
        }

        .submission-table {
            min-width: 860px;
        }

        .submission-mobile-list {
            display: none;
        }

        .submission-mobile-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 14px;
        }

        .submission-status {
            border-radius: 999px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 9px;
        }

        .submission-status.done {
            background: #dcfce7;
            color: #166534;
        }

        .submission-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .bulk-download-toolbar {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: space-between;
            margin-bottom: 14px;
            padding: 11px 13px;
        }

        .bulk-download-toolbar__hint { color: #64748b; font-size: 12px; }
        .bulk-download-toolbar__actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .bulk-download-toolbar select { border: 1px solid #dbe2ea; border-radius: 999px; font-size: 12px; font-weight: 700; padding: 7px 32px 7px 12px; }
        .submission-select { height: 17px; width: 17px; }

        @media (max-width: 767.98px) {
            .assignments-page {
                padding-left: 2px;
                padding-right: 2px;
            }

            .assignments-header {
                align-items: stretch;
                flex-direction: column;
            }

            .assignments-create-btn,
            .assignment-card .btn {
                width: 100%;
            }

            .assignment-card .card-body {
                padding: 18px !important;
            }

            .assignment-card-top {
                flex-direction: column;
            }

            .assignment-badges {
                width: 100%;
            }

            .assignment-badge {
                width: 100%;
            }

            #addAssignmentModal .modal-dialog,
            #submitAssignmentModal .modal-dialog,
            #assignmentSubmissionsModal .modal-dialog {
                margin: 0;
                max-width: none;
                width: 100%;
            }

            #addAssignmentModal .modal-content,
            #submitAssignmentModal .modal-content,
            #assignmentSubmissionsModal .modal-content {
                border-radius: 0;
                min-height: 100dvh;
            }

            #assignmentSubmissionsModal .modal-body {
                padding: 16px !important;
            }

            .submission-desktop-table {
                display: none;
            }

            .submission-mobile-list {
                display: grid;
                gap: 10px;
            }
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const submitModal = document.getElementById('submitAssignmentModal');
            const courseSelect = document.getElementById('createAssignmentCourseSelect');
            const lessonSelect = document.getElementById('createAssignmentLessonSelect');
            let allowedExtensions = [];
            let currentAssignmentType = 'file';

            function syncLessonOptions() {
                if (!courseSelect || !lessonSelect) return;

                const selectedCourseId = courseSelect.value;
                let firstVisibleOption = null;

                Array.from(lessonSelect.options).forEach(option => {
                    const visible = option.dataset.course === selectedCourseId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                    if (visible && !firstVisibleOption) firstVisibleOption = option;
                });

                if (firstVisibleOption && lessonSelect.selectedOptions[0]?.disabled) {
                    lessonSelect.value = firstVisibleOption.value;
                }
            }

            if (courseSelect) {
                courseSelect.addEventListener('change', syncLessonOptions);
                syncLessonOptions();
            }

            if (submitModal) {
                submitModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const title = button.getAttribute('data-title');
                    const instructions = button.getAttribute('data-instructions');
                    const extensions = button.getAttribute('data-extensions') ||
                        'pdf,docx,txt,md,html,htm,css,js,php,png,jpg,jpeg';
                    const textAnswer = JSON.parse(button.getAttribute('data-text-answer') || '""');
                    currentAssignmentType = button.getAttribute('data-type') || 'file';
                    const needsFile = ['file', 'mixed'].includes(currentAssignmentType);
                    const needsEssay = ['essay', 'mixed'].includes(currentAssignmentType);
                    const hasExistingFile = button.getAttribute('data-has-file') === '1';
                    const fileField = document.getElementById('submitFileField');
                    const fileInput = document.getElementById('submitFileInput');
                    const essayField = document.getElementById('submitEssayField');
                    const essayInput = document.getElementById('submitTextAnswer');

                    allowedExtensions = extensions.split(',').map(e => e.trim().toLowerCase());

                    document.getElementById('submitModalTitle').innerText = 'Nộp bài: ' + title;
                    document.getElementById('submitInstructions').innerText = instructions;
                    document.getElementById('submitForm').action = `/assignments/${id}/submit`;
                    if (fileField) fileField.classList.toggle('d-none', !needsFile);
                    if (fileInput) {
                        fileInput.required = needsFile && !hasExistingFile;
                        fileInput.value = '';
                    }
                    if (essayField) essayField.classList.toggle('d-none', !needsEssay);
                    if (essayInput) {
                        essayInput.required = needsEssay;
                        essayInput.value = textAnswer || '';
                    }

                    // Reset lỗi cũ mỗi lần mở modal
                    clearError();
                });
            }

            const form = document.getElementById('submitForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    clearError();

                    const fileInput = document.getElementById('submitFileInput');
                    const essayInput = document.getElementById('submitTextAnswer');
                    const file = fileInput.files[0];
                    const needsFile = ['file', 'mixed'].includes(currentAssignmentType);
                    const needsEssay = ['essay', 'mixed'].includes(currentAssignmentType);

                    if (needsEssay && essayInput.value.trim().length < 10) {
                        e.preventDefault();
                        showError('Bài tự luận cần có ít nhất 10 ký tự.');
                        return;
                    }

                    if (needsFile && !file) return; // để Laravel validate required
                    if (!file) return;

                    const maxSize = 5 * 1024 * 1024; // 5MB
                    const ext = file.name.split('.').pop().toLowerCase();

                    if (!allowedExtensions.includes(ext)) {
                        e.preventDefault();
                        showError(
                            `Định dạng file <strong>.${ext}</strong> không được chấp nhận. Vui lòng chọn file có định dạng: <strong>${allowedExtensions.join(', ')}</strong>.`
                        );
                        return;
                    }

                    if (file.size > maxSize) {
                        e.preventDefault();
                        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                        showError(
                            `File của bạn nặng <strong>${sizeMB} MB</strong>, vượt quá giới hạn cho phép <strong>5 MB</strong>. Vui lòng nén file hoặc chọn file nhỏ hơn.`
                        );
                        return;
                    }
                });
            }

            function showError(message) {
                let alert = document.getElementById('submitFileError');
                if (!alert) {
                    alert = document.createElement('div');
                    alert.id = 'submitFileError';
                    alert.className =
                        'alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 py-2 px-3 small';
                    alert.setAttribute('role', 'alert');
                    const anchor = document.getElementById('submitEssayField') || document.getElementById('submitFileField');
                    anchor.parentNode.insertBefore(alert, anchor);
                }
                alert.innerHTML = `
            <i class="fa-solid fa-circle-exclamation mt-1 flex-shrink-0"></i>
            <div>${message}</div>
            <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="alert"></button>
        `;
            }

            function clearError() {
                const alert = document.getElementById('submitFileError');
                if (alert) alert.remove();
            }

            const submissionsModal = document.getElementById('assignmentSubmissionsModal');
            const submissionsTitle = document.getElementById('assignmentSubmissionsTitle');
            const submissionsMeta = document.getElementById('assignmentSubmissionsMeta');
            const submissionsContent = document.getElementById('assignmentSubmissionsContent');

            const esc = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            document.querySelectorAll('.view-assignment-submissions-btn').forEach(button => {
                button.addEventListener('click', async function() {
                    if (!submissionsModal || !submissionsContent) return;

                    submissionsTitle.textContent = this.dataset.assignmentTitle || 'Bài nộp';
                    submissionsMeta.textContent = 'Đang tải danh sách bài nộp...';
                    submissionsContent.innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <div class="spinner-border text-primary mb-3"></div>
                            <div>Đang tải danh sách bài nộp...</div>
                        </div>`;

                    const modal = new bootstrap.Modal(submissionsModal);
                    modal.show();

                    try {
                        const response = await fetch(this.dataset.url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Không tải được danh sách bài nộp.');
                        }

                        renderSubmissions(data);
                    } catch (error) {
                        submissionsContent.innerHTML = `
                            <div class="alert alert-danger mb-0">
                                <i class="fa-solid fa-circle-exclamation me-1"></i>${esc(error.message)}
                            </div>`;
                    }
                });
            });

            function renderSubmissions(data) {
                const rows = Array.isArray(data.submissions) ? data.submissions : [];
                submissionsTitle.textContent = data.assignment_title ? `Bài tập: ${data.assignment_title}` : 'Bài nộp';
                submissionsMeta.textContent =
                    `${data.course_title || 'Khóa học'} · ${data.submitted_count || 0}/${data.total_students || rows.length} học sinh đã nộp`;

                if (!rows.length) {
                    submissionsContent.innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-users fa-2x mb-3 opacity-50"></i>
                            <div>Chưa có học sinh nào trong lớp của khóa học này.</div>
                        </div>`;
                    return;
                }

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                submissionsContent.innerHTML = `
                    <form method="POST" action="${esc(data.download_url || '')}" class="bulk-download-form">
                    <input type="hidden" name="_token" value="${esc(csrf)}">
                    <div class="bulk-download-toolbar">
                        <div>
                            <div class="fw-bold text-dark small"><i class="fa-solid fa-file-zipper text-primary me-1"></i>Tải bài nộp hàng loạt</div>
                            <div class="bulk-download-toolbar__hint">File ZIP kèm danh sách CSV tổng hợp.</div>
                        </div>
                        <div class="bulk-download-toolbar__actions">
                            <select name="mode" class="bulk-download-mode" aria-label="Phạm vi tải">
                                <option value="all">Tất cả bài đã nộp</option>
                                <option value="ungraded">Chỉ bài chưa chấm</option>
                                <option value="selected">Các học sinh đã chọn</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">
                                <i class="fa-solid fa-download me-1"></i>Tải ZIP
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive submission-desktop-table">
                        <table class="table align-middle submission-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:42px"><input type="checkbox" class="submission-select select-all-submissions" aria-label="Chọn tất cả"></th>
                                    <th>Học sinh</th>
                                    <th>Trạng thái</th>
                                    <th>Thời gian nộp</th>
                                    <th>Điểm</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rows.map(row => `
                                    <tr>
                                        <td>${row.submission_id ? `<input type="checkbox" class="submission-select submission-checkbox" name="submission_ids[]" value="${esc(row.submission_id)}">` : ''}</td>
                                        <td>
                                            <div class="fw-bold">${esc(row.student_name || 'Học sinh')}</div>
                                            ${row.student_code ? `<div class="text-muted small">${esc(row.student_code)}</div>` : ''}
                                            <div class="text-muted small">${esc(row.student_email || '')}</div>
                                        </td>
                                        <td>
                                            ${row.submission_id
                                                ? '<span class="submission-status done"><i class="fa-solid fa-check me-1"></i>Đã nộp</span>'
                                                : '<span class="submission-status pending"><i class="fa-solid fa-clock me-1"></i>Chưa nộp</span>'}
                                        </td>
                                        <td>${esc(row.submitted_at || '-')}</td>
                                        <td>${row.grade !== null && row.grade !== undefined ? esc(row.grade) : '-'}</td>
                                        <td class="text-end">
                                            ${row.submission_id ? `
                                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                                    ${row.file_url ? `<a href="${esc(row.file_url)}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fa-solid fa-file me-1"></i>File</a>` : ''}
                                                    <a href="${esc(row.review_url)}" class="btn btn-sm btn-primary rounded-pill">
                                                        <i class="fa-solid fa-pen-to-square me-1"></i>Chấm bài
                                                    </a>
                                                </div>
                                            ` : '<span class="text-muted small">Chưa có bài làm</span>'}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <div class="submission-mobile-list">
                        ${rows.map(row => `
                            <div class="submission-mobile-card">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="min-w-0">
                                        ${row.submission_id ? `<input type="checkbox" class="submission-select submission-checkbox float-start me-2 mt-1" name="submission_ids[]" value="${esc(row.submission_id)}">` : ''}
                                        <div class="fw-bold text-dark">${esc(row.student_name || 'Học sinh')}</div>
                                        <div class="text-muted small text-break">${esc(row.student_email || '')}</div>
                                    </div>
                                    ${row.submission_id
                                        ? '<span class="submission-status done flex-shrink-0"><i class="fa-solid fa-check me-1"></i>Đã nộp</span>'
                                        : '<span class="submission-status pending flex-shrink-0"><i class="fa-solid fa-clock me-1"></i>Chưa nộp</span>'}
                                </div>
                                <div class="small text-muted mb-1">
                                    <i class="fa-solid fa-clock me-1"></i>${esc(row.submitted_at || 'Chưa có thời gian nộp')}
                                </div>
                                <div class="small text-muted mb-3">
                                    <i class="fa-solid fa-star me-1"></i>Điểm: ${row.grade !== null && row.grade !== undefined ? esc(row.grade) : '-'}
                                </div>
                                ${row.submission_id ? `
                                    <div class="d-grid gap-2">
                                        <a href="${esc(row.review_url)}" class="btn btn-sm btn-primary rounded-pill">
                                            <i class="fa-solid fa-pen-to-square me-1"></i>Chấm bài
                                        </a>
                                        ${row.file_url ? `<a href="${esc(row.file_url)}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fa-solid fa-file me-1"></i>Xem file</a>` : ''}
                                    </div>
                                ` : '<div class="text-muted small">Chưa có bài làm</div>'}
                            </div>
                        `).join('')}
                    </div>
                    </form>`;
            }

            submissionsContent?.addEventListener('change', function(event) {
                if (!event.target.matches('.select-all-submissions')) return;
                this.querySelectorAll('.submission-checkbox').forEach(checkbox => {
                    if (checkbox.offsetParent !== null) checkbox.checked = event.target.checked;
                });
            });

            submissionsContent?.addEventListener('submit', function(event) {
                const form = event.target.closest('.bulk-download-form');
                if (!form) return;
                const mode = form.querySelector('.bulk-download-mode')?.value;
                if (mode === 'selected' && !form.querySelector('.submission-checkbox:checked')) {
                    event.preventDefault();
                    alert('Vui lòng chọn ít nhất một học sinh đã nộp bài.');
                }
            });
        });
    </script>
@endpush
