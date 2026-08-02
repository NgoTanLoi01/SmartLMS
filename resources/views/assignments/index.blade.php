@extends('layouts.app')

@section('title', 'Quản lý bài tập')

@section('content')
    @push('styles')
        @vite('resources/css/pages/assignments-index.css')
    @endpush

    @php
        $isStudent = auth()->user()->role === 'student';
        $hasAssignmentFilters = request()->filled('q') || request()->filled('course_id') || request()->filled('status');
    @endphp

    <div class="lms-page assignments-page">
        <x-ui.page-header title="Bài tập">
            <x-slot:meta>
                <span><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                    {{ $isStudent ? 'Theo dõi hạn nộp và bài làm của bạn' : 'Quản lý yêu cầu, hạn nộp và tiến độ chấm bài' }}
                </span>
            </x-slot:meta>

            <x-slot:actions>
                @if (auth()->user()->role === 'admin' || auth()->user()->role === 'teacher')
                    <x-ui.button class="assignments-create-btn" icon="fa-plus" data-bs-toggle="modal"
                        data-bs-target="#addAssignmentModal">
                        Tạo bài tập
                    </x-ui.button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        <section class="assignment-stats" aria-label="Tổng quan bài tập">
            <article class="assignment-stat">
                <span class="assignment-stat__icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span>
                <div><strong>{{ $assignmentStats['total'] }}</strong><span>Tổng bài tập</span></div>
            </article>
            @if ($isStudent)
                <article class="assignment-stat assignment-stat--success">
                    <span class="assignment-stat__icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
                    <div><strong>{{ $assignmentStats['submitted'] }}</strong><span>Đã nộp</span></div>
                </article>
                <article class="assignment-stat assignment-stat--warning">
                    <span class="assignment-stat__icon"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></span>
                    <div><strong>{{ $assignmentStats['pending'] }}</strong><span>Chờ hoàn thành</span></div>
                </article>
            @else
                <article class="assignment-stat assignment-stat--success">
                    <span class="assignment-stat__icon"><i class="fa-solid fa-eye" aria-hidden="true"></i></span>
                    <div><strong>{{ $assignmentStats['published'] }}</strong><span>Đã xuất bản</span></div>
                </article>
                <article class="assignment-stat assignment-stat--warning">
                    <span class="assignment-stat__icon"><i class="fa-solid fa-calendar-week" aria-hidden="true"></i></span>
                    <div><strong>{{ $assignmentStats['due_soon'] }}</strong><span>Hạn trong 7 ngày</span></div>
                </article>
            @endif
            <article class="assignment-stat assignment-stat--danger">
                <span class="assignment-stat__icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>
                <div><strong>{{ $assignmentStats['overdue'] }}</strong><span>{{ $isStudent ? 'Đã quá hạn' : 'Qua hạn nộp' }}</span></div>
            </article>
        </section>

        <form action="{{ route('assignments.index') }}" method="GET" class="assignment-filter-panel" role="search">
            <div class="assignment-filter-field assignment-filter-field--search">
                <label for="assignment-search">Tìm bài tập</label>
                <div class="assignment-filter-control">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="assignment-search" class="form-control" type="search" name="q"
                        value="{{ request('q') }}" placeholder="Tên hoặc nội dung yêu cầu">
                </div>
            </div>
            <div class="assignment-filter-field">
                <label for="assignment-course">Khóa học</label>
                <select id="assignment-course" class="form-select" name="course_id">
                    <option value="">Tất cả khóa học</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="assignment-filter-field">
                <label for="assignment-status">Trạng thái</label>
                <select id="assignment-status" class="form-select" name="status">
                    <option value="">Tất cả trạng thái</option>
                    @if ($isStudent)
                        <option value="pending" @selected(request('status') === 'pending')>Chờ hoàn thành</option>
                        <option value="submitted" @selected(request('status') === 'submitted')>Đã nộp</option>
                        <option value="overdue" @selected(request('status') === 'overdue')>Đã quá hạn</option>
                    @else
                        <option value="published" @selected(request('status') === 'published')>Đã xuất bản</option>
                        <option value="draft" @selected(request('status') === 'draft')>Bản nháp</option>
                        <option value="hidden" @selected(request('status') === 'hidden')>Đang ẩn</option>
                        <option value="upcoming" @selected(request('status') === 'upcoming')>Còn hạn nộp</option>
                        <option value="overdue" @selected(request('status') === 'overdue')>Đã qua hạn</option>
                    @endif
                </select>
            </div>
            <div class="assignment-filter-actions">
                <x-ui.button type="submit" icon="fa-filter">Áp dụng</x-ui.button>
                @if ($hasAssignmentFilters)
                    <x-ui.button :href="route('assignments.index')" tone="outline" icon="fa-rotate-left">Đặt lại</x-ui.button>
                @endif
            </div>
            <div class="assignment-filter-summary">
                Hiển thị {{ $assignments->firstItem() ?? 0 }}–{{ $assignments->lastItem() ?? 0 }} trong
                {{ $assignments->total() }} bài tập phù hợp
            </div>
        </form>

        <section class="assignment-grid" aria-label="Danh sách bài tập">
            @forelse($assignments as $assignment)
                @php
                    $assignmentTypeLabel = match ($assignment->type ?? 'file') {
                        'essay' => 'Tự luận',
                        'mixed' => 'File + tự luận',
                        default => 'Nộp file',
                    };
                    $submission = $isStudent ? $assignment->submissions->first() : null;
                    $isOverdue = $assignment->due_date->isPast();
                    $status = $isStudent
                        ? ($submission ? 'submitted' : ($isOverdue ? 'overdue' : 'pending'))
                        : ($assignment->status ?? 'draft');
                    $statusLabel = match ($status) {
                        'submitted' => $submission?->grade !== null ? 'Đã chấm' : 'Đã nộp',
                        'overdue' => 'Đã quá hạn',
                        'pending' => 'Chờ hoàn thành',
                        'published' => 'Đã xuất bản',
                        'hidden' => 'Đang ẩn',
                        default => 'Bản nháp',
                    };
                @endphp
                <article class="assignment-card {{ $isOverdue ? 'is-overdue' : '' }}">
                    <div class="assignment-card__head">
                        <span class="assignment-card__type"><i class="fa-solid fa-file-pen" aria-hidden="true"></i>
                            {{ $assignmentTypeLabel }}</span>
                        <x-ui.status-badge :status="$status" :label="$statusLabel" />
                    </div>

                    <div class="assignment-card__course" title="{{ $assignment->course->title }}">
                        <i class="fa-solid fa-book-open" aria-hidden="true"></i> {{ $assignment->course->title }}
                    </div>
                    <h2 class="assignment-card__title">{{ $assignment->title }}</h2>
                    <p class="assignment-card__description">
                        {{ Str::limit(strip_tags($assignment->instructions), 125) }}
                    </p>

                    <div class="assignment-card__details">
                        <div class="assignment-detail {{ $isOverdue ? 'is-danger' : '' }}">
                            <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                            <span><small>Hạn nộp</small><strong>{{ $assignment->due_date->format('d/m/Y · H:i') }}</strong></span>
                        </div>
                        <div class="assignment-detail">
                            <i class="fa-solid fa-paperclip" aria-hidden="true"></i>
                            <span><small>Hình thức</small><strong>
                                {{ ($assignment->type ?? 'file') === 'essay' ? 'Trả lời trực tiếp' : $assignmentTypeLabel }}
                            </strong></span>
                        </div>
                    </div>

                    <div class="assignment-card__footer">
                        @if ($isStudent)
                            <button class="lms-btn lms-btn-outline assignment-card__primary" data-bs-toggle="modal"
                                data-bs-target="#submitAssignmentModal" data-id="{{ $assignment->id }}"
                                data-title="{{ $assignment->title }}"
                                data-instructions="{{ strip_tags($assignment->instructions) }}"
                                data-extensions="{{ $assignment->allowed_extensions }}"
                                data-type="{{ $assignment->type ?? 'file' }}"
                                data-has-file="{{ $submission && $submission->file_path ? '1' : '0' }}"
                                data-text-answer='@json($submission?->text_answer ?? "")'>
                                <i class="fa-solid {{ $submission ? 'fa-rotate' : 'fa-arrow-up-from-bracket' }}"
                                    aria-hidden="true"></i>
                                {{ $submission ? 'Cập nhật bài làm' : 'Làm bài' }}
                            </button>

                            @if ($submission?->grade !== null)
                                <span class="assignment-grade">{{ $submission->grade }}/{{ $assignment->grading_scale ?? 10 }} điểm</span>
                            @endif
                        @else
                            <button type="button"
                                class="lms-btn lms-btn-outline assignment-card__primary view-assignment-submissions-btn"
                                data-assignment-id="{{ $assignment->id }}"
                                data-assignment-title="{{ $assignment->title }}"
                                data-url="{{ route('assignments.submissions.list', $assignment->id) }}">
                                <i class="fa-solid fa-inbox" aria-hidden="true"></i> Xem bài nộp
                                <span class="assignment-submission-count">{{ $assignment->submissions_count }}</span>
                            </button>
                            @if (($assignment->pending_grading_count ?? 0) > 0)
                                <span class="assignment-pending-grade">{{ $assignment->pending_grading_count }} chờ chấm</span>
                            @endif
                        @endif
                    </div>
                </article>
            @empty
                <x-ui.empty-state title="Không tìm thấy bài tập"
                    :description="$hasAssignmentFilters ? 'Hãy thay đổi điều kiện hoặc đặt lại bộ lọc.' : ($isStudent ? 'Hiện chưa có bài tập nào cần hoàn thành.' : 'Hãy tạo bài tập đầu tiên cho khóa học.')"
                    icon="fa-list-check">
                    @if ($hasAssignmentFilters)
                        <x-ui.button :href="route('assignments.index')" tone="outline" size="sm"
                            icon="fa-rotate-left">Đặt lại bộ lọc</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @endforelse
        </section>

        <x-ui.pagination :paginator="$assignments" item-label="bài tập" class="assignment-pagination" />
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
                                <option value="hidden">Ẩn khỏi học viên</option>
                                <option value="archived">Lưu trữ</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Định dạng cho phép (Cách nhau dấu
                                phẩy)</label>
                            <input type="text" name="allowed_extensions" class="form-control bg-light border-0 py-2"
                                value="pdf,docx,txt,md,html,htm,css,js,png,jpg,jpeg">
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
                        'pdf,docx,txt,md,html,htm,css,js,png,jpg,jpeg';
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
                    `${data.course_title || 'Khóa học'} · ${data.submitted_count || 0}/${data.total_students || rows.length} học viên đã nộp`;

                if (!rows.length) {
                    submissionsContent.innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-users fa-2x mb-3 opacity-50"></i>
                            <div>Chưa có học viên nào trong lớp của khóa học này.</div>
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
                                <option value="selected">Các học viên đã chọn</option>
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
                                    <th>Học viên</th>
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
                                            <div class="fw-bold">${esc(row.student_name || 'Học viên')}</div>
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
                                        <div class="fw-bold text-dark">${esc(row.student_name || 'Học viên')}</div>
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
                    alert('Vui lòng chọn ít nhất một học viên đã nộp bài.');
                }
            });
        });
    </script>
@endpush
