@extends('layouts.app')

@section('title', $course->title)

@section('content')
    <style>
        .modal-backdrop {
            z-index: 1050 !important;
        }

        .modal {
            z-index: 1060 !important;
        }

        .sticky-top {
            z-index: 1000 !important;
        }

        /* Tối ưu Sidebar & Hover */
        .lesson-item-wrapper,
        .assignment-item-wrapper {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            cursor: pointer;
        }

        .lesson-item-wrapper:hover,
        .assignment-item-wrapper:hover {
            background-color: #f8f9fa !important;
            border-left-color: #0d6efd;
        }

        .lesson-item-wrapper.active,
        .assignment-item-wrapper.active {
            background-color: #e7f1ff !important;
            border-left-color: #0d6efd;
        }

        /* Sửa lỗi UI Icon đè text */
        .action-buttons {
            opacity: 0;
            transition: opacity 0.2s ease;
            flex-shrink: 0;
            background: transparent;
            padding-left: 8px;
        }

        .lesson-item-wrapper:hover .action-buttons,
        .module-header-wrapper:hover .action-buttons,
        .assignment-item-wrapper:hover .action-buttons {
            opacity: 1;
        }

        .btn-action {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-edit {
            color: #f59f00;
        }

        .btn-edit:hover {
            background: #fff4d5;
        }

        .btn-delete {
            color: #fa5252;
        }

        .btn-delete:hover {
            background: #ffe8e8;
        }

        .text-truncate-custom {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            min-width: 0;
        }

        .accordion-button:not(.collapsed) {
            background-color: #ffffff;
            color: #0d6efd;
        }

        .accordion-button {
            padding-right: 3rem;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 px-3">
            <div>
                <h3 class="fw-bold mb-0 text-dark">{{ $course->title }}</h3>
                <p class="text-muted mb-0 small">Giảng viên: {{ $course->teacher->name }}</p>
                @if (auth()->user()->role === 'student')
                    <div class="mt-3" style="max-width: 400px;">
                        <div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-muted fw-medium">Tiến độ học tập</span>
                            <span class="text-primary fw-bold" id="progress-text">{{ $completedCount }}/{{ $totalLessons }}
                                bài ({{ $progress }}%)</span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 10px;">
                            <div id="progress-bar" class="progress-bar bg-primary" role="progressbar"
                                style="width: {{ $progress }}%; transition: width 0.5s ease;"></div>
                        </div>
                    </div>
                @endif
            </div>

            @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary rounded-pill px-3 shadow-sm btn-sm" data-bs-toggle="modal"
                        data-bs-target="#addModuleModal">
                        <i class="fas fa-folder-plus me-1"></i> Chương
                    </button>
                    <button class="btn btn-primary rounded-pill px-3 shadow-sm btn-sm" data-bs-toggle="modal"
                        data-bs-target="#addLessonModal" {{ $course->modules->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-plus me-1"></i> Bài học
                    </button>
                    <button class="btn btn-warning text-dark fw-bold rounded-pill px-3 shadow-sm btn-sm"
                        data-bs-toggle="modal" data-bs-target="#addCourseAssignmentModal"
                        {{ $course->modules->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-tasks me-1"></i> Bài tập
                    </button>
                    <a href="{{ route('attendance.show', $course->id) }}"
                        class="btn btn-info text-white rounded-pill px-3 shadow-sm btn-sm">
                        <i class="fas fa-user-check me-1"></i> Điểm danh
                    </a>

                </div>
            @endif

        </div>

        <div class="row g-4">
            <!-- Sidebar Danh sách bài học -->
            <div class="col-md-4 col-lg-3">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold small text-uppercase text-muted"><i class="fas fa-list-ol me-2"></i>Nội dung
                            học tập</h6>
                    </div>
                    <div class="card-body p-0" style="max-height: 75vh; overflow-y: auto;">
                        <div class="accordion accordion-flush" id="courseAccordion">
                            @forelse ($course->modules as $index => $module)
                                <div class="accordion-item border-bottom">
                                    <div class="position-relative module-header-wrapper d-flex align-items-center">
                                        <button
                                            class="accordion-button {{ $index == 0 ? '' : 'collapsed' }} py-3 fw-bold flex-grow-1 shadow-none"
                                            type="button" data-bs-toggle="collapse"
                                            data-bs-target="#module-{{ $module->id }}">
                                            <span class="text-truncate-custom me-4">{{ $module->title }}</span>
                                        </button>

                                        @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                            <div
                                                class="action-buttons position-absolute end-0 me-5 d-flex align-items-center">
                                                <a href="javascript:void(0)" class="btn-action btn-edit edit-module-btn"
                                                    data-id="{{ $module->id }}" data-title="{{ $module->title }}"
                                                    data-bs-toggle="modal" data-bs-target="#editModuleModal">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('modules.destroy', $module->id) }}" method="POST"
                                                    class="d-inline mb-0">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="btn-action btn-delete border-0 bg-transparent"
                                                        onclick="return confirm('Xóa chương này?')"><i
                                                            class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>

                                    <div id="module-{{ $module->id }}"
                                        class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}"
                                        data-bs-parent="#courseAccordion">
                                        <div class="accordion-body p-0">
                                            <div class="list-group list-group-flush">
                                                @forelse ($module->lessons as $lesson)
                                                    @php $isCompleted = in_array($lesson->id, $completedLessonIds ?? []); @endphp
                                                    <div
                                                        class="list-group-item border-0 px-3 py-2 lesson-item-wrapper d-flex align-items-center justify-content-between shadow-none">
                                                        <a href="javascript:void(0)"
                                                            class="lesson-item text-decoration-none text-dark flex-grow-1 d-flex align-items-center"
                                                            style="min-width: 0;" data-id="{{ $lesson->id }}"
                                                            data-content="{{ $lesson->content }}"
                                                            data-title="{{ $lesson->title }}"
                                                            data-video="{{ $lesson->video_url }}"
                                                            data-module="{{ $module->id }}">
                                                            <i class="{{ $isCompleted ? 'fas fa-check-circle text-success' : 'far fa-play-circle text-primary' }} me-2 flex-shrink-0 lesson-icon"
                                                                id="icon-lesson-{{ $lesson->id }}"></i>
                                                            <span
                                                                class="small text-truncate-custom">{{ $lesson->title }}</span>
                                                        </a>

                                                        @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                                            <div class="action-buttons d-flex ms-2">
                                                                <a href="javascript:void(0)"
                                                                    class="btn-action btn-edit edit-lesson-btn"
                                                                    data-id="{{ $lesson->id }}"
                                                                    data-title="{{ $lesson->title }}"
                                                                    data-content="{{ $lesson->content }}"
                                                                    data-video="{{ $lesson->video_url }}"
                                                                    data-module="{{ $module->id }}"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editLessonModal">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form action="{{ route('lessons.destroy', $lesson->id) }}"
                                                                    method="POST" class="d-inline mb-0">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit"
                                                                        class="btn-action btn-delete border-0 bg-transparent"
                                                                        onclick="return confirm('Xóa bài này?')"><i
                                                                            class="fas fa-times"></i></button>
                                                                </form>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    {{-- ✅ BÀI TẬP --}}
                                                    @foreach ($lesson->assignments as $assignment)
                                                        @php
                                                            $submission =
                                                                auth()->user()->role === 'student' &&
                                                                isset($userSubmissions[$assignment->id])
                                                                    ? $userSubmissions[$assignment->id]
                                                                    : null;
                                                        @endphp
                                                        <div
                                                            class="list-group-item border-0 py-2 assignment-item-wrapper d-flex align-items-center justify-content-between shadow-none bg-light border-bottom">
                                                            <div class="ms-4 flex-grow-1 d-flex align-items-center"
                                                                style="min-width: 0;">
                                                                <a href="javascript:void(0)"
                                                                    class="assignment-item text-decoration-none text-dark flex-grow-1 d-flex align-items-center"
                                                                    data-id="{{ $assignment->id }}"
                                                                    data-title="{{ $assignment->title }}"
                                                                    data-instructions="{{ $assignment->instructions }}"
                                                                    data-due="{{ $assignment->due_date ? $assignment->due_date->format('d/m/Y H:i') : '' }}"
                                                                    data-raw-due="{{ $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : '' }}"
                                                                    data-status="{{ $submission ? 'submitted' : 'pending' }}"
                                                                    data-grade="{{ $submission->grade ?? '' }}"
                                                                    data-feedback="{{ $submission->feedback ?? '' }}"
                                                                    data-sub-id="{{ $submission ? $submission->id : '' }}"
                                                                    data-sub-time="{{ $submission ? $submission->submitted_at->format('H:i - d/m/Y') : '' }}"
                                                                    data-sub-file="{{ $submission ? asset('storage/' . $submission->file_path) : '' }}">

                                                                    <i
                                                                        class="{{ $submission ? 'fas fa-check-circle text-success' : 'fas fa-file-signature text-warning' }} me-2 flex-shrink-0"></i>
                                                                    <span
                                                                        class="small text-truncate-custom fw-medium">{{ $assignment->title }}</span>
                                                                </a>
                                                            </div>

                                                            @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                                                <div class="action-buttons d-flex ms-2 gap-1">
                                                                    <!-- Nút sửa bài tập -->
                                                                    <a href="javascript:void(0)"
                                                                        class="btn-action btn-edit edit-assignment-btn"
                                                                        data-id="{{ $assignment->id }}"
                                                                        data-title="{{ $assignment->title }}"
                                                                        data-instructions="{{ $assignment->instructions }}"
                                                                        data-due="{{ $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : '' }}"
                                                                        data-lesson="{{ $lesson->id }}"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editAssignmentModal"
                                                                        title="Sửa bài tập">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>

                                                                    <!-- Nút xóa bài tập -->
                                                                    <form
                                                                        action="{{ route('assignments.destroy', $assignment->id) }}"
                                                                        method="POST" class="d-inline mb-0">
                                                                        @csrf @method('DELETE')
                                                                        <button type="submit"
                                                                            class="btn-action btn-delete border-0 bg-transparent"
                                                                            onclick="return confirm('Xóa bài tập này?')"
                                                                            title="Xóa bài tập">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>

                                                                    <!-- Nút xem danh sách nộp bài -->
                                                                    <a href="javascript:void(0)"
                                                                        class="btn-action text-primary view-submissions-btn border bg-white shadow-sm"
                                                                        data-id="{{ $assignment->id }}"
                                                                        title="Chấm điểm / Xem danh sách">
                                                                        <i class="fas fa-users-cog"></i>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach

                                                @empty
                                                    <div class="py-2 px-4 text-muted small fst-italic">Trống</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-muted small">Chưa có nội dung.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Khu vực hiển thị Phải -->
            <div class="col-md-8 col-lg-9">
                <div class="card border-0 shadow-sm overflow-hidden h-100 d-flex flex-column">

                    <!-- GIAO DIỆN BÀI HỌC -->
                    <div id="video-container" class="ratio ratio-16x9 bg-dark d-none">
                        <iframe id="lesson-video" src="" allowfullscreen></iframe>
                    </div>

                    <div id="external-link-container"
                        class="bg-primary bg-opacity-10 p-4 text-center d-none border-bottom">
                        <i class="fas fa-external-link-alt fa-3x text-primary mb-3"></i>
                        <h5 class="fw-bold text-dark">Tài liệu / Video tham khảo ngoài</h5>
                        <p class="text-muted small mb-3">Bài học này chứa một liên kết ngoài hệ thống. Vui lòng bấm nút bên
                            dưới để truy cập.</p>
                        <a href="#" id="external-link-btn" target="_blank"
                            class="btn btn-primary rounded-pill px-4 shadow-sm">Truy cập liên kết ngay</a>
                    </div>

                    <div class="card-body p-4 flex-grow-1" id="lesson-content-area">
                        <h2 id="lesson-title" class="fw-bold mb-3 text-dark">{{ $course->title }}</h2>
                        <hr>
                        <div id="lesson-body" class="lh-lg text-secondary">
                            <p>{{ $course->description }}</p>
                            <div class="text-center py-5" id="welcome-placeholder">
                                <i class="fas fa-book-reader fa-3x text-light mb-3 d-block"></i>
                                <h5 class="text-muted">Hãy chọn bài học để bắt đầu</h5>
                            </div>
                        </div>
                    </div>

                    <!-- GIAO DIỆN BÀI TẬP -->
                    <div class="card-body p-4 flex-grow-1 d-none" id="assignment-content-area"
                        style="background-color: #fcfdfd;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 id="assignment-title" class="fw-bold text-dark mb-0">Tiêu đề bài tập</h2>
                            <span id="assignment-badge" class="badge rounded-pill px-3 py-2 fs-6">Trạng thái</span>
                        </div>
                        <div
                            class="d-flex align-items-center text-danger fw-bold small mb-4 bg-danger bg-opacity-10 d-inline-block px-3 py-2 rounded">
                            <i class="fas fa-clock me-2"></i> Hạn nộp: <span id="assignment-due-date"
                                class="ms-1"></span>
                        </div>
                        <hr>

                        <h5 class="fw-bold mt-4 mb-3"><i class="fas fa-tasks me-2 text-primary"></i>Yêu cầu bài tập:</h5>
                        <div id="assignment-instructions"
                            class="lh-lg text-secondary bg-white p-4 rounded border shadow-sm mb-4"></div>

                        @if (auth()->user()->role === 'student')
                            @if (auth()->user()->role === 'student')
                                <div id="student-submission-area"
                                    class="mt-4 p-4 border border-primary border-opacity-25 rounded bg-primary bg-opacity-10">

                                    <!-- PHẦN 1: KHI ĐÃ NỘP BÀI -->
                                    <div id="submitted-info-area" class="d-none">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="fw-bold text-success mb-0"><i
                                                    class="fas fa-check-circle me-2"></i>Bài làm của bạn</h5>
                                        </div>

                                        <div
                                            class="bg-white p-3 rounded border shadow-sm mb-3 d-flex align-items-center justify-content-between">
                                            <div>
                                                <p class="mb-1 fw-bold text-dark"><i
                                                        class="fas fa-file-alt me-2 text-primary"></i>Tài liệu đã tải lên
                                                </p>
                                                <p class="mb-0 small text-muted"><i class="fas fa-clock me-1"></i> Đã nộp
                                                    lúc: <span id="submitted-time-text" class="fw-medium"></span></p>
                                            </div>
                                            <a href="#" id="submitted-file-link" target="_blank"
                                                class="btn btn-outline-primary btn-sm rounded-pill px-3">Xem file</a>
                                        </div>

                                        <div id="grading-result"
                                            class="d-none mb-3 p-3 bg-success bg-opacity-10 border border-success rounded">
                                            <h6 class="fw-bold text-success mb-1">Điểm số: <span id="grade-score"
                                                    class="fs-5"></span>/10</h6>
                                            <p class="mb-0 small text-dark"><strong>Nhận xét:</strong> <span
                                                    id="grade-feedback"></span></p>
                                        </div>

                                        <div class="d-flex gap-2" id="submission-actions">
                                            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm"
                                                id="btn-edit-submission">
                                                <i class="fas fa-edit me-1"></i> Chỉnh sửa bài nộp
                                            </button>
                                            <form id="delete-submission-form" method="POST" class="m-0"
                                                onsubmit="return confirm('Bạn có chắc chắn muốn hủy bài đã nộp?');">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-outline-danger rounded-pill px-4 shadow-sm">
                                                    <i class="fas fa-trash-alt me-1"></i> Hủy bài nộp
                                                </button>
                                            </form>
                                        </div>
                                        <p id="graded-warning" class="text-danger small mt-2 d-none fst-italic"><i
                                                class="fas fa-lock me-1"></i>Giáo viên đã chấm điểm, bạn không thể sửa hoặc
                                            xóa bài.</p>
                                    </div>

                                    <!-- PHẦN 2: FORM NỘP BÀI MỚI / CHỈNH SỬA -->
                                    <div id="upload-form-area" class="d-none">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="fw-bold text-primary mb-0"><i
                                                    class="fas fa-cloud-upload-alt me-2"></i>Nộp bài tập</h5>
                                            <button type="button" class="btn btn-sm btn-light d-none"
                                                id="btn-cancel-edit">Hủy sửa</button>
                                        </div>

                                        <!-- Chú ý thuộc tính enctype="multipart/form-data" -->
                                        <form id="course-submit-assignment-form" method="POST"
                                            enctype="multipart/form-data" action="">
                                            @csrf
                                            <div class="input-group mb-3">
                                                <input type="file" name="file" class="form-control bg-white"
                                                    required>
                                                <button class="btn btn-primary px-4 fw-bold shadow-sm" type="submit">Gửi
                                                    bài</button>
                                            </div>
                                            <p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i>Vui
                                                lòng upload file theo đúng định dạng giáo viên yêu cầu.</p>
                                        </form>

                                    </div>

                                </div>
                            @else
                                <div class="text-center mt-5">
                                    <p class="text-muted"><i class="fas fa-info-circle me-1"></i>Bấm vào biểu tượng ⚙️ ở
                                        danh sách bên trái để chấm điểm bài tập này.</p>
                                </div>
                            @endif
                        @else
                            <div class="text-center mt-5">
                                <p class="text-muted"><i class="fas fa-info-circle me-1"></i>Bấm vào biểu tượng ⚙️ ở danh
                                    sách bên trái để chấm điểm bài tập này.</p>
                            </div>
                        @endif
                    </div>

                    <!-- FOOTER BÀI HỌC -->
                    <div class="card-footer bg-light border-top p-3 d-flex justify-content-between align-items-center"
                        id="nav-footer">
                        <button class="btn btn-outline-secondary rounded-pill px-4 btn-sm fw-medium" id="btn-prev"
                            disabled>
                            <i class="fas fa-arrow-left me-1"></i> Bài trước
                        </button>
                        <button class="btn btn-success rounded-pill px-4 shadow-sm fw-bold d-none" id="btn-complete">
                            <i class="fas fa-check-circle me-1"></i> Hoàn thành bài học
                        </button>
                        <button class="btn btn-outline-secondary rounded-pill px-4 btn-sm fw-medium" id="btn-next"
                            disabled>
                            Bài tiếp theo <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= MODALS ================= -->
    <!-- Modal Thêm Chương -->
    <div class="modal fade" id="addModuleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('modules.store') }}" method="POST" class="modal-content border-0">@csrf<input
                    type="hidden" name="course_id" value="{{ $course->id }}">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Thêm chương mới</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4"><input type="text" name="title" class="form-control bg-light border-0"
                        placeholder="Tên chương học..." required></div>
                <div class="modal-footer border-0 pt-0"><button type="submit"
                        class="btn btn-primary rounded-pill px-4 w-100">Lưu lại</button></div>
            </form>
        </div>
    </div>

    <!-- Modal Sửa Chương -->
    <div class="modal fade" id="editModuleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editModuleForm" method="POST" class="modal-content border-0">@csrf @method('PUT')<div
                    class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-warning">Sửa chương</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4"><input type="text" name="title" id="editModuleTitle"
                        class="form-control bg-light border-0" required></div>
                <div class="modal-footer border-0 pt-0"><button type="submit"
                        class="btn btn-warning text-dark fw-bold rounded-pill px-4 w-100">Cập nhật</button></div>
            </form>
        </div>
    </div>

    <!-- Modal Thêm Bài Học -->
    <div class="modal fade" id="addLessonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('lessons.store') }}" method="POST" class="modal-content border-0">@csrf<div
                    class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Thêm bài học mới</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="small fw-bold">Chọn chương</label><select name="module_id"
                            class="form-select bg-light border-0" required>
                            @foreach ($course->modules as $module)
                                <option value="{{ $module->id }}">{{ $module->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="small fw-bold">Tiêu đề bài học</label><input type="text"
                            name="title" class="form-control bg-light border-0" placeholder="Tiêu đề bài học..."
                            required></div>
                    <div class="mb-3"><label class="small fw-bold">Link (Youtube, Google Drive, Zoom...)</label><input
                            type="url" name="video_url" class="form-control bg-light border-0"
                            placeholder="https://..."></div><label class="small fw-bold">Nội dung chi tiết</label>
                    <textarea name="content" class="form-control bg-light border-0" rows="4"></textarea>
                </div>
                <div class="modal-footer border-0"><button type="submit"
                        class="btn btn-primary rounded-pill px-4 w-100">Lưu bài học</button></div>
            </form>
        </div>
    </div>

    <!-- Modal Sửa Bài Học -->
    <div class="modal fade" id="editLessonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editLessonForm" method="POST" class="modal-content border-0">@csrf @method('PUT')<div
                    class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-warning">Sửa bài học</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="small fw-bold">Chương</label><select name="module_id"
                            id="editLessonModule" class="form-select bg-light border-0" required>
                            @foreach ($course->modules as $module)
                                <option value="{{ $module->id }}">{{ $module->title }}</option>
                            @endforeach
                        </select></div>
                    <div class="mb-3"><label class="small fw-bold">Tiêu đề bài học</label><input type="text"
                            name="title" id="editLessonTitle" class="form-control bg-light border-0" required></div>
                    <div class="mb-3"><label class="small fw-bold">Link (Youtube, Google Drive, Zoom...)</label><input
                            type="url" name="video_url" id="editLessonVideo" class="form-control bg-light border-0">
                    </div><label class="small fw-bold">Nội dung chi tiết</label>
                    <textarea name="content" id="editLessonContent" class="form-control bg-light border-0" rows="4"></textarea>
                </div>
                <div class="modal-footer border-0"><button type="submit"
                        class="btn btn-warning text-dark fw-bold rounded-pill px-4 w-100">Cập nhật</button></div>
            </form>
        </div>
    </div>

    <!-- Modal Thêm Bài Tập -->
    <div class="modal fade" id="addCourseAssignmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('assignments.store') }}" method="POST" class="modal-content border-0">
                @csrf
                <input type="hidden" name="course_id" value="{{ $course->id }}">
                <input type="hidden" name="status" value="published">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Thêm bài tập thực hành</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Bài tập thuộc bài học nào?</label>
                            <select name="lesson_id" class="form-select bg-light border-0" required>
                                <option value="" disabled selected>-- Chọn bài học --</option>
                                @foreach ($course->modules as $module)
                                    <optgroup label="{{ $module->title }}">
                                        @foreach ($module->lessons as $lesson)
                                            <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Hạn nộp (Deadline)</label>
                            <input type="datetime-local" name="due_date" class="form-control bg-light border-0" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Tiêu đề bài tập</label>
                            <input type="text" name="title" class="form-control bg-light border-0"
                                placeholder="VD: Bài tập thực hành HTML cơ bản" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Nội dung yêu cầu</label>
                            <textarea name="instructions" class="form-control bg-light border-0" rows="4"
                                placeholder="Nhập yêu cầu chi tiết..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-warning fw-bold text-dark rounded-pill px-4 w-100">Lưu bài
                        tập</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Sửa Bài Tập -->
    <div class="modal fade" id="editAssignmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="editAssignmentForm" method="POST" class="modal-content border-0">
                @csrf @method('PUT')
                <input type="hidden" name="course_id" value="{{ $course->id }}">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-warning">Sửa bài tập</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Bài tập thuộc bài học nào?</label>
                            <select name="lesson_id" id="editAssignmentLesson" class="form-select bg-light border-0"
                                required>
                                @foreach ($course->modules as $module)
                                    <optgroup label="{{ $module->title }}">
                                        @foreach ($module->lessons as $lesson)
                                            <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Hạn nộp (Deadline)</label>
                            <input type="datetime-local" name="due_date" id="editAssignmentDue"
                                class="form-control bg-light border-0" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Tiêu đề bài tập</label>
                            <input type="text" name="title" id="editAssignmentTitle"
                                class="form-control bg-light border-0" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Nội dung yêu cầu</label>
                            <textarea name="instructions" id="editAssignmentInstructions" class="form-control bg-light border-0" rows="4"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-warning fw-bold text-dark rounded-pill px-4 w-100">Cập nhật bài
                        tập</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Danh Sách Chấm Điểm -->
    <div class="modal fade" id="viewSubmissionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold" id="modal-assignment-name">Danh sách nộp bài</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="px-4 py-3">Học sinh</th>
                                    <th class="px-4 py-3">Trạng thái</th>
                                    <th class="px-4 py-3">Thời gian nộp</th>
                                    <th class="px-4 py-3">File bài làm</th>
                                    <th class="px-4 py-3">Chấm điểm</th>
                                </tr>
                            </thead>
                            <tbody id="submissions-table-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let totalLessonsCount = {{ $totalLessons ?? 0 }};
            let currentCompletedCount = {{ $completedCount ?? 0 }};

            function getYoutubeId(url) {
                const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
                const match = url.match(regExp);
                return (match && match[2].length === 11) ? match[2] : null;
            }

            let currentLessonIndex = -1;
            const lessons = Array.from(document.querySelectorAll('.lesson-item'));
            let currentLessonId = null;

            function updateNavButtons() {
                document.getElementById('btn-prev').disabled = (currentLessonIndex <= 0);
                document.getElementById('btn-next').disabled = (currentLessonIndex === -1 || currentLessonIndex >= lessons
                    .length - 1);

                const btnComplete = document.getElementById('btn-complete');
                if (currentLessonIndex !== -1) {
                    btnComplete.classList.remove('d-none');
                    btnComplete.classList.replace('btn-secondary', 'btn-success');
                    btnComplete.innerHTML = '<i class="fas fa-check-circle me-1"></i> Hoàn thành bài học';
                    btnComplete.disabled = false;
                }
            }

            // GIAO DIỆN COMPONENTS
            const lessonArea = document.getElementById('lesson-content-area');
            const assignmentArea = document.getElementById('assignment-content-area');
            const videoContainer = document.getElementById('video-container');
            const externalContainer = document.getElementById('external-link-container');
            const navFooter = document.getElementById('nav-footer');
            const iframe = document.getElementById('lesson-video');
            const externalBtn = document.getElementById('external-link-btn');

            // ==========================================
            // 1. CLICK VÀO BÀI HỌC
            // ==========================================
            lessons.forEach((item, index) => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.lesson-item-wrapper, .assignment-item-wrapper').forEach(li => li
                        .classList.remove('active'));
                    this.closest('.lesson-item-wrapper').classList.add('active');

                    // Hiện giao diện bài học, ẩn bài tập
                    assignmentArea.classList.add('d-none');
                    assignmentArea.classList.remove('d-flex');
                    lessonArea.classList.remove('d-none');
                    navFooter.classList.remove('d-none');

                    currentLessonId = this.getAttribute('data-id');
                    currentLessonIndex = index;
                    updateNavButtons();

                    document.getElementById('lesson-title').innerText = this.getAttribute('data-title');
                    document.getElementById('lesson-body').innerHTML = this.getAttribute('data-content') ||
                        '<p class="text-muted fst-italic">Không có nội dung văn bản.</p>';
                    const placeholder = document.getElementById('welcome-placeholder');
                    if (placeholder) placeholder.style.display = 'none';

                    const videoUrl = this.getAttribute('data-video');
                    const ytId = videoUrl ? getYoutubeId(videoUrl) : null;

                    if (ytId) {
                        iframe.src = `https://www.youtube.com/embed/${ytId}?autoplay=1`;
                        videoContainer.classList.remove('d-none');
                        externalContainer.classList.add('d-none');
                    } else if (videoUrl && videoUrl.trim() !== '') {
                        iframe.src = '';
                        videoContainer.classList.add('d-none');
                        externalBtn.href = videoUrl;
                        externalContainer.classList.remove('d-none');
                    } else {
                        iframe.src = '';
                        videoContainer.classList.add('d-none');
                        externalContainer.classList.add('d-none');
                    }
                    lessonArea.scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });

            // ==========================================
            // 2. CLICK VÀO BÀI TẬP (CÓ CHECK DEADLINE)
            // ==========================================
            const assignments = Array.from(document.querySelectorAll('.assignment-item'));
            assignments.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.lesson-item-wrapper, .assignment-item-wrapper').forEach(li => li
                        .classList.remove('active'));
                    this.closest('.assignment-item-wrapper').classList.add('active');

                    // Ẩn giao diện Bài học, Hiện Bài tập
                    lessonArea.classList.add('d-none');
                    videoContainer.classList.add('d-none');
                    externalContainer.classList.add('d-none');
                    iframe.src = '';
                    navFooter.classList.add('d-none');

                    assignmentArea.classList.remove('d-none');
                    assignmentArea.classList.add('d-flex', 'flex-column');

                    const id = this.getAttribute('data-id');
                    document.getElementById('assignment-title').innerText = this.getAttribute('data-title');
                    document.getElementById('assignment-instructions').innerText = this.getAttribute(
                        'data-instructions');
                    document.getElementById('assignment-due-date').innerText = this.getAttribute('data-due');

                    // LẤY VÀ KIỂM TRA HẠN NỘP (Cần HTML có data-raw-due="YYYY-MM-DDTHH:mm")
                    const rawDue = this.getAttribute('data-raw-due');
                    const isOverdue = rawDue ? new Date(rawDue) < new Date() : false;

                    const status = this.getAttribute('data-status');
                    const badge = document.getElementById('assignment-badge');
                    const grade = this.getAttribute('data-grade');
                    const feedback = this.getAttribute('data-feedback');
                    const subId = this.getAttribute('data-sub-id');
                    const subTime = this.getAttribute('data-sub-time');
                    const subFile = this.getAttribute('data-sub-file');

                    const submittedArea = document.getElementById('submitted-info-area');
                    const uploadArea = document.getElementById('upload-form-area');
                    const gradingResult = document.getElementById('grading-result');
                    const submissionActions = document.getElementById('submission-actions');
                    let gradedWarning = document.getElementById('graded-warning');

                    const btnCancelEdit = document.getElementById('btn-cancel-edit');
                    const btnEditSub = document.getElementById('btn-edit-submission');
                    const deleteForm = document.getElementById('delete-submission-form');
                    const submitForm = document.getElementById('course-submit-assignment-form');

                    // TẠO THÔNG BÁO KHÓA QUÁ HẠN (NẾU CHƯA TỒN TẠI)
                    let lockedAlert = document.getElementById('overdue-locked-alert');
                    if (!lockedAlert && uploadArea) {
                        lockedAlert = document.createElement('div');
                        lockedAlert.id = 'overdue-locked-alert';
                        lockedAlert.className = 'alert alert-danger mb-0 border-0 shadow-sm text-center mt-3';
                        lockedAlert.innerHTML =
                            '<i class="fas fa-lock fa-2x mb-2 text-danger"></i><h6 class="fw-bold text-danger">Đã hết thời gian nộp bài</h6><p class="small mb-0 text-danger">Rất tiếc, bạn đã bỏ lỡ bài tập này hoặc không thể sửa đổi do đã quá hạn.</p>';
                        uploadArea.appendChild(lockedAlert);
                    }

                    // XỬ LÝ TRẠNG THÁI ĐÃ NỘP BÀI
                    if (status === 'submitted') {
                        badge.className = 'badge rounded-pill px-3 py-2 fs-6 bg-success';
                        badge.innerHTML = '<i class="fas fa-check me-1"></i> Đã nộp';

                        if (submittedArea && uploadArea) {
                            submittedArea.classList.remove('d-none');
                            uploadArea.classList.add('d-none');
                            document.getElementById('submitted-time-text').innerText = subTime;
                            document.getElementById('submitted-file-link').href = subFile;
                            if (deleteForm) deleteForm.action = `/submissions/${subId}/delete`;
                            if (btnCancelEdit) btnCancelEdit.classList.remove('d-none');
                        }

                        // Nếu quá hạn và chưa có điểm -> Khóa nút sửa/xóa bài nộp
                        if (isOverdue && (!grade || grade === '')) {
                            if (btnEditSub) btnEditSub.classList.add('d-none');
                            if (deleteForm) deleteForm.classList.add('d-none');

                            if (gradedWarning) {
                                gradedWarning.innerHTML =
                                    '<i class="fas fa-lock me-1"></i>Đã hết hạn nộp. Bạn không thể sửa hoặc hủy bài nộp nữa.';
                                gradedWarning.classList.remove('d-none', 'text-success');
                                gradedWarning.classList.add('text-danger');
                            }
                        } else if (!grade || grade === '') {
                            // Chưa quá hạn, chưa có điểm -> Mở nút sửa
                            if (btnEditSub) btnEditSub.classList.remove('d-none');
                            if (deleteForm) deleteForm.classList.remove('d-none');
                            if (gradedWarning) gradedWarning.classList.add('d-none');
                        }

                    } else {
                        // XỬ LÝ TRẠNG THÁI CHƯA NỘP BÀI
                        if (submittedArea && uploadArea) {
                            submittedArea.classList.add('d-none');
                            uploadArea.classList.remove('d-none');
                            if (btnCancelEdit) btnCancelEdit.classList.add('d-none');
                        }

                        if (isOverdue) {
                            badge.className = 'badge rounded-pill px-3 py-2 fs-6 bg-danger';
                            badge.innerHTML = '<i class="fas fa-times-circle me-1"></i> Quá hạn';

                            // Khóa form nộp bài mới
                            if (submitForm) submitForm.classList.add('d-none');
                            if (lockedAlert) lockedAlert.classList.remove('d-none');
                        } else {
                            badge.className = 'badge rounded-pill px-3 py-2 fs-6 bg-warning text-dark';
                            badge.innerHTML = '<i class="fas fa-clock me-1"></i> Chưa nộp';

                            // Mở lại form nộp bài
                            if (submitForm) submitForm.classList.remove('d-none');
                            if (lockedAlert) lockedAlert.classList.add('d-none');
                        }
                    }

                    // XỬ LÝ KHI GIÁO VIÊN ĐÃ CHẤM ĐIỂM (Ưu tiên khóa cao nhất)
                    if (grade && grade !== '') {
                        if (gradingResult) gradingResult.classList.remove('d-none');
                        document.getElementById('grade-score').innerText = grade;
                        document.getElementById('grade-feedback').innerText = feedback || 'Không có nhận xét';

                        if (submissionActions) submissionActions.classList.add('d-none');
                        if (gradedWarning) {
                            gradedWarning.innerHTML =
                                '<i class="fas fa-lock me-1"></i>Giáo viên đã chấm điểm, bạn không thể sửa hoặc xóa bài.';
                            gradedWarning.classList.remove('d-none', 'text-danger');
                            gradedWarning.classList.add('text-success'); // Màu xanh cho thông báo đã chấm điểm
                        }
                    } else {
                        if (gradingResult) gradingResult.classList.add('d-none');
                        if (!isOverdue && status === 'submitted' && submissionActions) {
                            submissionActions.classList.remove('d-none');
                        }
                    }

                    if (submitForm) submitForm.action = `/assignments/${id}/submit`;

                    assignmentArea.scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });

            // ==========================================
            // SỰ KIỆN NÚT SỬA BÀI & HỦY SỬA (STUDENT)
            // ==========================================
            const btnEditSub = document.getElementById('btn-edit-submission');
            const btnCancelEdit = document.getElementById('btn-cancel-edit');

            if (btnEditSub) {
                btnEditSub.addEventListener('click', () => {
                    document.getElementById('submitted-info-area').classList.add('d-none');
                    document.getElementById('upload-form-area').classList.remove('d-none');
                });
            }
            if (btnCancelEdit) {
                btnCancelEdit.addEventListener('click', () => {
                    document.getElementById('submitted-info-area').classList.remove('d-none');
                    document.getElementById('upload-form-area').classList.add('d-none');
                });
            }

            // ==========================================
            // 3. ĐIỀU HƯỚNG BÀI TRƯỚC / SAU
            // ==========================================
            document.getElementById('btn-prev').addEventListener('click', () => {
                if (currentLessonIndex > 0) lessons[currentLessonIndex - 1].click();
            });

            document.getElementById('btn-next').addEventListener('click', () => {
                if (currentLessonIndex < lessons.length - 1) lessons[currentLessonIndex + 1].click();
            });

            // ==========================================
            // 4. HOÀN THÀNH BÀI HỌC
            // ==========================================
            document.getElementById('btn-complete').addEventListener('click', function() {
                if (!currentLessonId) return;
                axios.post(`/lessons/${currentLessonId}/complete`)
                    .then(response => {
                        this.classList.replace('btn-success', 'btn-secondary');
                        this.innerHTML = '<i class="fas fa-check me-1"></i> Đã hoàn thành';
                        this.disabled = true;

                        const icon = document.getElementById('icon-lesson-' + currentLessonId);
                        if (icon && !icon.classList.contains('fa-check-circle')) {
                            icon.className = 'fas fa-check-circle text-success me-2 flex-shrink-0 lesson-icon';
                            currentCompletedCount++;
                            let newProgress = Math.round((currentCompletedCount / totalLessonsCount) * 100);
                            const progressText = document.getElementById('progress-text');
                            const progressBar = document.getElementById('progress-bar');
                            if (progressText) progressText.innerText =
                                `${currentCompletedCount}/${totalLessonsCount} bài (${newProgress}%)`;
                            if (progressBar) progressBar.style.width = newProgress + '%';
                        }
                        setTimeout(() => document.getElementById('btn-next').click(), 1000);
                    });
            });

            // ==========================================
            // 5. MODAL CHẤM ĐIỂM (AJAX)
            // ==========================================
            document.querySelectorAll('.view-submissions-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const id = this.getAttribute('data-id');
                    const tableBody = document.getElementById('submissions-table-body');
                    tableBody.innerHTML =
                        '<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';

                    const myModal = new bootstrap.Modal(document.getElementById('viewSubmissionsModal'));
                    myModal.show();

                    axios.get(`/assignments/${id}/submissions-list`)
                        .then(response => {
                            document.getElementById('modal-assignment-name').innerText = 'Bài tập: ' +
                                response.data.assignment_title;
                            tableBody.innerHTML = '';
                            response.data.submissions.forEach(sub => {
                                let statusBadge = sub.submitted_at ?
                                    '<span class="badge bg-success">Đã nộp</span>' :
                                    '<span class="badge bg-light text-muted border">Chưa nộp</span>';
                                let fileLink = sub.file_url ?
                                    `<a href="${sub.file_url}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download me-1"></i>Tải file</a>` :
                                    '<span class="text-muted small">---</span>';
                                let gradeForm = sub.submission_id ?
                                    `<form action="/submissions/${sub.submission_id}/grade" method="POST" class="d-flex gap-2">
                                        @csrf
                                        <input type="number" name="grade" step="0.1" class="form-control form-control-sm" style="width:70px" value="${sub.grade || ''}" placeholder="0-10">
                                        <input type="text" name="feedback" class="form-control form-control-sm" value="${sub.feedback || ''}" placeholder="Nhận xét...">
                                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-save"></i></button>
                                       </form>` :
                                    '<span class="text-muted small">N/A</span>';

                                tableBody.innerHTML += `
                                    <tr>
                                        <td class="px-4">
                                            <div class="fw-bold">${sub.student_name}</div>
                                            <div class="small text-muted">${sub.student_email}</div>
                                        </td>
                                        <td class="px-4">${statusBadge}</td>
                                        <td class="px-4 small text-muted">${sub.submitted_at || '---'}</td>
                                        <td class="px-4">${fileLink}</td>
                                        <td class="px-4">${gradeForm}</td>
                                    </tr>
                                `;
                            });
                        });
                });
            });

            // ==========================================
            // 6. GÁN VALUE CHO CÁC MODAL SỬA (GIÁO VIÊN)
            // ==========================================

            // Sửa Chương
            document.querySelectorAll('.edit-module-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    document.getElementById('editModuleForm').action =
                        `/modules/${this.getAttribute('data-id')}`;
                    document.getElementById('editModuleTitle').value = this.getAttribute('data-title');
                });
            });

            // Sửa Bài học
            document.querySelectorAll('.edit-lesson-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    document.getElementById('editLessonForm').action =
                        `/lessons/${this.getAttribute('data-id')}`;
                    document.getElementById('editLessonTitle').value = this.getAttribute('data-title');
                    document.getElementById('editLessonContent').value = this.getAttribute('data-content');
                    document.getElementById('editLessonVideo').value = this.getAttribute('data-video');
                    document.getElementById('editLessonModule').value = this.getAttribute('data-module');
                });
            });

            // Sửa Bài tập (MỚI)
            document.querySelectorAll('.edit-assignment-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Tránh kích hoạt click mở giao diện làm bài tập
                    document.getElementById('editAssignmentForm').action =
                        `/assignments/${this.getAttribute('data-id')}`;
                    document.getElementById('editAssignmentLesson').value = this.getAttribute('data-lesson');
                    document.getElementById('editAssignmentDue').value = this.getAttribute('data-due');
                    document.getElementById('editAssignmentTitle').value = this.getAttribute('data-title');
                    document.getElementById('editAssignmentInstructions').value = this.getAttribute(
                        'data-instructions');
                });
            });
        </script>
    @endpush

@endsection
