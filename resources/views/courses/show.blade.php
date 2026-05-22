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

        .lesson-item-wrapper,
        .assignment-item-wrapper,
        .quiz-item-wrapper {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            cursor: pointer;
        }

        .lesson-item-wrapper:hover,
        .assignment-item-wrapper:hover,
        .quiz-item-wrapper:hover {
            background-color: #f8f9fa !important;
            border-left-color: #0d6efd;
        }

        .lesson-item-wrapper.active,
        .assignment-item-wrapper.active,
        .quiz-item-wrapper.active {
            background-color: #e7f1ff !important;
            border-left-color: #0d6efd;
        }

        .action-buttons {
            opacity: 0;
            transition: opacity 0.2s ease;
            flex-shrink: 0;
            background: transparent;
            padding-left: 8px;
        }

        .lesson-item-wrapper:hover .action-buttons,
        .module-header-wrapper:hover .action-buttons,
        .assignment-item-wrapper:hover .action-buttons,
        .quiz-item-wrapper:hover .action-buttons {
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

        .course-intro-card {
            background: linear-gradient(135deg, #ffffff, #f8fbff);
            border: 1px solid #eef2f7;
        }

        .course-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            background: linear-gradient(135deg, #0d6efd, #4dabf7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            box-shadow: 0 10px 25px rgba(13, 110, 253, 0.2);
        }

        .course-description {
            font-size: 15px;
            line-height: 1.9;
            color: #495057;
        }

        .info-box {
            background: white;
            border-radius: 18px;
            padding: 20px;
            text-align: center;
            border: 1px solid #f1f3f5;
            transition: all 0.25s ease;
            height: 100%;
        }

        .info-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
        }

        .info-box i {
            font-size: 24px;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 px-3">
            <div>
                <h3 class="fw-bold mb-0 text-dark">{{ $course->title }}</h3>
                <p class="text-muted mb-0 small">Giáo viên: {{ $course->teacher->name }}</p>
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
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                        <button class="btn btn-white border-end btn-sm fw-bold text-primary" data-bs-toggle="modal"
                            data-bs-target="#addModuleModal">
                            <i class="fas fa-folder-plus"></i> <span class="d-none d-lg-inline">Chương</span>
                        </button>
                        <button class="btn btn-white border-end btn-sm fw-bold text-primary" data-bs-toggle="modal"
                            data-bs-target="#addLessonModal">
                            <i class="fas fa-plus"></i> <span class="d-none d-lg-inline">Bài học</span>
                        </button>
                        <button class="btn btn-white btn-sm fw-bold text-warning" data-bs-toggle="modal"
                            data-bs-target="#addCourseAssignmentModal">
                            <i class="fas fa-tasks"></i> <span class="d-none d-lg-inline">Bài tập</span>
                        </button>
                    </div>

                    <a href="{{ route('attendance.show', $course->id) }}"
                        class="btn btn-info text-white rounded-pill px-3 shadow-sm btn-sm fw-bold">
                        <i class="fas fa-user-check"></i> Điểm danh
                    </a>

                    <button class="btn btn-purple text-white rounded-pill px-3 shadow-sm btn-sm fw-bold"
                        style="background-color: #6f42c1;" data-bs-toggle="modal" data-bs-target="#addQuizModal">
                        <i class="fas fa-question-circle"></i> Trắc nghiệm
                    </button>
                </div>
            @endif
        </div>

        <div class="row g-4">
            @include('courses.partials.sidebar')

            <div class="col-md-8 col-lg-9">
                <div class="card border-0 shadow-sm overflow-hidden h-100 d-flex flex-column">

                    <div id="video-container" class="ratio ratio-16x9 bg-dark d-none">
                        <iframe id="lesson-video" src="" allowfullscreen></iframe>
                    </div>

                    <div id="external-link-container" class="bg-primary bg-opacity-10 p-4 text-center d-none border-bottom">
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

                            {{-- COURSE INTRO --}}
                            <div class="course-intro-card p-4 p-md-5 rounded-4 shadow-sm mb-4">

                                <div class="course-description mb-4">
                                    {!! nl2br(e($course->description)) !!}
                                </div>

                            </div>

                            {{-- PLACEHOLDER --}}
                            <div class="text-center py-5" id="welcome-placeholder">
                                <i class="fas fa-book-reader fa-3x text-light mb-3 d-block"></i>
                                <h5 class="text-muted">Hãy chọn bài học để bắt đầu</h5>
                            </div>

                        </div>
                    </div>

                    <div id="lesson-attachment-container"
                        class="mt-4 p-3 bg-light rounded border d-none d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-file-download fa-2x text-primary me-2 align-middle"></i>
                            <span class="fw-bold text-dark">Tài liệu đính kèm:</span>
                            <span id="lesson-attachment-name" class="text-muted ms-2 small">filename.pdf</span>
                        </div>
                        <a href="#" id="lesson-attachment-btn" download
                            class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                            <i class="fas fa-download me-1"></i> Tải về
                        </a>
                    </div>

                    <div class="card-body p-4 flex-grow-1 d-none" id="assignment-content-area"
                        style="background-color: #fffdf5;">
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
                        <div id="assignment-instructions" class="lh-lg bg-white p-4 rounded border shadow-sm mb-4"></div>

                        @if (auth()->user()->role === 'student')
                            <div id="student-submission-area"
                                class="mt-4 p-4 border border-primary border-opacity-25 rounded bg-primary bg-opacity-10">
                                <div id="submitted-info-area" class="d-none">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold text-success mb-0"><i class="fas fa-check-circle me-2"></i>Bài
                                            làm của bạn</h5>
                                    </div>
                                    <div
                                        class="bg-white p-3 rounded border shadow-sm mb-3 d-flex align-items-center justify-content-between">
                                        <div>
                                            <p class="mb-1 fw-bold text-dark"><i
                                                    class="fas fa-file-alt me-2 text-primary"></i>Tài liệu đã tải lên</p>
                                            <p class="mb-0 small text-muted"><i class="fas fa-clock me-1"></i> Đã nộp lúc:
                                                <span id="submitted-time-text" class="fw-medium"></span>
                                            </p>
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
                                            id="btn-edit-submission"><i class="fas fa-edit me-1"></i> Chỉnh sửa bài
                                            nộp</button>
                                        <form id="delete-submission-form" method="POST" class="m-0"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn hủy bài đã nộp?');">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-outline-danger rounded-pill px-4 shadow-sm"><i
                                                    class="fas fa-trash-alt me-1"></i> Hủy bài nộp</button>
                                        </form>
                                    </div>
                                    <p id="graded-warning" class="text-danger small mt-2 d-none fst-italic"><i
                                            class="fas fa-lock me-1"></i>Giáo viên đã chấm điểm, bạn không thể sửa hoặc xóa
                                        bài.</p>
                                </div>

                                <div id="upload-form-area" class="d-none">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold text-primary mb-0"><i
                                                class="fas fa-cloud-upload-alt me-2"></i>Nộp bài tập</h5>
                                        <button type="button" class="btn btn-sm btn-light d-none"
                                            id="btn-cancel-edit">Hủy sửa</button>
                                    </div>
                                    <form id="course-submit-assignment-form" method="POST" enctype="multipart/form-data"
                                        action="">
                                        @csrf
                                        <div class="input-group mb-3">
                                            <input type="file" name="file" class="form-control bg-white" required>
                                            <button class="btn btn-primary px-4 fw-bold shadow-sm" type="submit">Gửi
                                                bài</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="text-center mt-5">
                                <p class="text-muted"><i class="fas fa-info-circle me-1"></i>Bấm vào biểu tượng <i
                                        class="fas fa-users-cog text-primary"></i> ở danh
                                    sách bên trái để chấm điểm bài tập này.</p>
                            </div>
                        @endif
                    </div>

                    {{-- ✅ GIAO DIỆN BÀI KIỂM TRA — CHỈ 1 BLOCK DUY NHẤT --}}
                    <div class="card-body p-4 flex-grow-1 d-none" id="quiz-content-area"
                        style="background-color: #fdfcff;">
                        <div class="d-flex flex-column justify-content-center align-items-center h-100 text-center py-5">
                            <i class="fas fa-stopwatch fa-5x mb-4" style="color: #6f42c1;" id="quiz-main-icon"></i>
                            <h2 id="quiz-display-title" class="fw-bold text-dark mb-4">Tiêu đề bài kiểm tra</h2>

                            <div class="d-flex justify-content-center gap-4 mb-4">
                                <div class="p-3 bg-white rounded-4 shadow-sm border border-light"
                                    style="min-width: 150px;">
                                    <span class="d-block text-muted small text-uppercase fw-bold mb-1"><i
                                            class="fas fa-clock me-1"></i> Thời gian</span>
                                    <h3 class="mb-0 fw-bold" style="color: #6f42c1;"><span
                                            id="quiz-display-duration">0</span> <small class="fs-6">Phút</small></h3>
                                </div>

                                @if (auth()->user()->role === 'student')
                                    <div class="p-3 bg-white rounded-4 shadow-sm border border-light"
                                        style="min-width: 150px;">
                                        <span class="d-block text-muted small text-uppercase fw-bold mb-1"><i
                                                class="fas fa-tasks me-1"></i> Trạng thái</span>
                                        <h3 id="quiz-status-text" class="mb-0 fw-bold text-warning fs-5 mt-2">Chưa làm
                                        </h3>
                                    </div>
                                    <div id="quiz-score-box"
                                        class="p-3 bg-success bg-opacity-10 rounded-4 shadow-sm border border-success d-none"
                                        style="min-width: 150px;">
                                        <span class="d-block text-success small text-uppercase fw-bold mb-1"><i
                                                class="fas fa-star me-1"></i> Điểm số</span>
                                        <h3 class="mb-0 fw-bold text-success mt-2"><span id="quiz-score-text">0</span>/10
                                        </h3>
                                    </div>
                                @endif
                            </div>

                            <hr class="w-50 mx-auto mb-4 text-muted opacity-25">

                            @if (auth()->user()->role === 'student')
                                <div id="quiz-student-action-area">
                                    <div
                                        class="alert alert-warning border-0 shadow-sm bg-warning bg-opacity-10 mb-4 text-start d-inline-block">
                                        <h6 class="fw-bold text-warning mb-2"><i
                                                class="fas fa-exclamation-triangle me-2"></i>Lưu ý quan trọng:</h6>
                                        <ul class="mb-0 small text-dark">
                                            <li>Đồng hồ sẽ bắt đầu đếm ngược ngay khi bạn bấm nút.</li>
                                            <li>Hệ thống sẽ tự động nộp bài khi hết thời gian.</li>
                                        </ul>
                                    </div>
                                    <div><a href="#" id="start-quiz-btn"
                                            class="btn btn-lg text-white rounded-pill px-5 shadow-sm fw-bold"
                                            style="background-color: #6f42c1;">BẮT ĐẦU LÀM BÀI <i
                                                class="fas fa-arrow-right ms-2"></i></a></div>
                                </div>
                                <div id="quiz-completed-msg" class="d-none w-100 mt-4">
                                    <div class="alert alert-success border-0 shadow-sm px-5 py-3 w-100 text-center"
                                        style="background-color: #d1e7dd; color: #0f5132;">
                                        <h5 class="fw-bold mb-1"><i class="fas fa-check-circle me-2"></i>Hoàn thành!</h5>
                                        <p class="mb-0">Bạn đã nộp bài kiểm tra này thành công.</p>
                                    </div>

                                    <div class="text-center mt-3">
                                        <a href="#" id="review-quiz-btn"
                                            class="btn btn-success rounded-pill px-5 py-2 shadow-sm fw-bold">
                                            <i class="fas fa-search me-2"></i> Xem chi tiết bài làm
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info border-0 shadow-sm bg-info bg-opacity-10 mb-4 text-start d-inline-block"
                                    style="max-width: 500px;">
                                    <h6 class="fw-bold text-info mb-2"><i class="fas fa-info-circle me-2"></i>Khu vực Quản
                                        lý:</h6>
                                    <p class="mb-0 small text-dark">Bạn có thể vào trang soạn thảo để thêm/sửa/xóa câu hỏi.
                                    </p>
                                </div>
                                <a href="#" id="manage-quiz-btn"
                                    class="btn btn-lg text-white rounded-pill px-5 shadow-sm fw-bold"
                                    style="background-color: #6f42c1;"><i class="fas fa-cog me-2"></i> VÀO TRANG SOẠN CÂU
                                    HỎI</a>
                            @endif
                        </div>
                    </div>

                    <div class="card-footer bg-light border-top p-3 d-flex justify-content-between align-items-center"
                        id="nav-footer">
                        <button class="btn btn-outline-secondary rounded-pill px-4 btn-sm fw-medium" id="btn-prev"
                            disabled><i class="fas fa-arrow-left me-1"></i> Bài trước</button>
                        <button class="btn btn-success rounded-pill px-4 shadow-sm fw-bold d-none" id="btn-complete"><i
                                class="fas fa-check-circle me-1"></i> Hoàn thành bài học</button>
                        <button class="btn btn-outline-secondary rounded-pill px-4 btn-sm fw-medium" id="btn-next"
                            disabled>Bài tiếp theo <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('courses.partials.modals')

    @include('courses.partials.scripts')

@endsection
