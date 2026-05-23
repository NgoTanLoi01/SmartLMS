@extends('layouts.app')

@section('title', $course->title)

@section('content')
    <style>
        /* --- GLOBAL & OVERRIDES --- */
        .modal-backdrop {
            z-index: 1050 !important;
        }

        .modal {
            z-index: 1060 !important;
        }

        .sticky-top {
            z-index: 1000 !important;
        }

        /* --- SIDEBAR LIST ITEMS --- */
        .lesson-item-wrapper,
        .assignment-item-wrapper,
        .quiz-item-wrapper {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            cursor: pointer;
            border-radius: 0 8px 8px 0;
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

        /* --- HEADER & TOP BAR --- */
        .course-header {
            background: #fff;
            border-bottom: 1px solid #f1f3f5;
        }

        .main-topbar {
            padding: 8px;
            border: 1px solid #e9ecef;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8f9fa;
            border-radius: 12px;
            flex-wrap: wrap;
        }

        .top-btn {
            font-size: 13px;
            padding: 6px 14px;
            border-radius: 8px;
            border: none;
            color: #495057;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all .2s;
            text-decoration: none;
            font-weight: 500;
        }

        .top-btn:hover {
            background: #e9ecef;
            color: #212529;
        }

        .top-btn.btn-add {
            background: #e7f5ff;
            color: #1c7ed6;
        }

        .top-btn.btn-add:hover {
            background: #d0ebff;
        }

        .top-btn.btn-purple {
            background: #EEEDFE;
            color: #3C3489;
        }

        .top-btn.btn-purple:hover {
            background: #CECBF6;
        }

        .top-btn.btn-amber {
            background: #FAEEDA;
            color: #633806;
        }

        .top-btn.btn-amber:hover {
            background: #FAC775;
        }

        .top-btn.btn-teal {
            background: #E1F5EE;
            color: #085041;
        }

        .top-btn.btn-teal:hover {
            background: #9FE1CB;
        }

        .progress-course {
            height: 10px;
            border-radius: 20px;
            background: #e9ecef;
        }

        .progress-course .progress-bar {
            background: linear-gradient(90deg, #4dabf7, #0d6efd);
            border-radius: 20px;
            transition: width 0.6s ease;
        }

        /* --- MAIN CONTENT AREA --- */
        .content-card {
            border-radius: 16px;
            border: none;
            overflow: hidden;
        }

        /* Lesson Intro */
        .course-intro-card {
            background: linear-gradient(135deg, #ffffff, #f8fbff);
            border: 1px solid #eef2f7;
            border-radius: 16px;
        }

        .course-description {
            font-size: 15px;
            line-height: 1.8;
            color: #495057;
        }

        .attachment-box {
            background: #f8f9fa;
            border: 1px dashed #ced4da;
            border-radius: 12px;
            transition: all 0.2s;
        }

        .attachment-box:hover {
            border-color: #0d6efd;
            background: #f0f7ff;
        }

        /* Assignment Area */
        .assignment-area {
            background-color: #fffdf5;
        }

        .submission-dropzone {
            border: 2px dashed #ffc107;
            background: #fffdf5;
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s;
        }

        .submission-dropzone:hover {
            border-color: #f59f00;
            background: #fff8e0;
        }

        .grading-result-box {
            border-radius: 12px;
            background: #e6fcf0;
            border: 1px solid #b2f2d0;
        }

        /* Quiz Area */
        .quiz-area {
            background-color: #f8f5ff;
        }

        .quiz-stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            border: 1px solid #eef2f7;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            flex: 1;
            min-width: 140px;
        }

        .btn-quiz-start {
            background: linear-gradient(135deg, #6f42c1, #9b59b6);
            border: none;
            padding: 12px 32px;
            font-size: 18px;
            border-radius: 50px;
            box-shadow: 0 6px 20px rgba(111, 66, 193, 0.3);
            transition: all 0.3s;
        }

        .btn-quiz-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(111, 66, 193, 0.4);
        }

        /* Footer Nav */
        .footer-nav {
            background: #fff;
            border-top: 1px solid #f1f3f5;
        }

        .btn-footer-nav {
            border-radius: 8px;
            font-weight: 500;
            padding: 8px 20px;
        }

        .btn-complete-lesson {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
            transition: all 0.3s;
        }

        .btn-complete-lesson:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(46, 204, 113, 0.4);
        }
    </style>

    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div
            class="course-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 p-3 rounded-4 shadow-sm bg-white">
            <div class="mb-3 mb-md-0">
                <h3 class="fw-bold mb-1 text-dark">{{ $course->title }}</h3>
                <p class="text-muted mb-0 small"><i class="fas fa-chalkboard-teacher me-1"></i> Giáo viên:
                    {{ $course->teacher->name }}</p>
                @if (auth()->user()->role === 'student')
                    <div class="mt-3" style="min-width: 300px;">
                        <div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-muted fw-medium">Tiến độ học tập</span>
                            <span class="text-primary fw-bold" id="progress-text">{{ $completedCount }}/{{ $totalLessons }}
                                bài ({{ $progress }}%)</span>
                        </div>
                        <div class="progress progress-course">
                            <div id="progress-bar" class="progress-bar" role="progressbar"
                                style="width: {{ $progress }}%;"></div>
                        </div>
                    </div>
                @endif
            </div>

            @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                <div class="main-topbar">
                    <button class="top-btn btn-add" data-bs-toggle="modal" data-bs-target="#addModuleModal"><i
                            class="fas fa-folder-plus"></i> Thêm chương</button>
                    <button class="top-btn btn-add" data-bs-toggle="modal" data-bs-target="#addLessonModal"><i
                            class="fas fa-plus"></i> Bài học</button>
                    <button class="top-btn btn-amber" data-bs-toggle="modal" data-bs-target="#addCourseAssignmentModal"><i
                            class="fas fa-tasks"></i> Bài tập</button>
                    <button class="top-btn btn-purple" data-bs-toggle="modal" data-bs-target="#addQuizModal"><i
                            class="fas fa-question-circle"></i> Trắc nghiệm</button>
                    <a href="{{ route('attendance.show', $course->id) }}" class="top-btn btn-teal"><i
                            class="fas fa-user-check"></i> Điểm danh</a>
                </div>
            @endif
        </div>

        <!-- Main Content -->
        <div class="row g-4">
            @include('courses.partials.sidebar')

            <div class="col-md-8 col-lg-9">
                <div class="card content-card shadow-sm h-100 d-flex flex-column">

                    <!-- Video & External Link -->
                    <div id="video-container" class="ratio ratio-16x9 bg-dark d-none">
                        <iframe id="lesson-video" src="" allowfullscreen
                            style="border-radius: 16px 16px 0 0;"></iframe>
                    </div>

                    <div id="external-link-container" class="bg-primary bg-opacity-10 p-5 text-center d-none border-bottom">
                        <i class="fas fa-external-link-alt fa-3x text-primary mb-3"></i>
                        <h5 class="fw-bold text-dark">Tài liệu / Video tham khảo ngoài</h5>
                        <p class="text-muted small mb-4">Bài học này chứa một liên kết ngoài hệ thống. Vui lòng bấm nút bên
                            dưới để truy cập.</p>
                        <a href="#" id="external-link-btn" target="_blank"
                            class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold"><i
                                class="fas fa-external-link-alt me-2"></i>Truy cập ngay</a>
                    </div>

                    <!-- LESSON AREA -->
                    <div class="card-body p-5 flex-grow-1" id="lesson-content-area">
                        <h2 id="lesson-title" class="fw-bold mb-4 text-dark">{{ $course->title }}</h2>
                        <hr class="mt-1 mb-4 opacity-25">
                        <div id="lesson-body" class="lh-lg text-secondary">
                            <div class="course-intro-card p-5 shadow-sm">
                                <div class="course-description mb-4">
                                    {!! nl2br(e($course->description)) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="lesson-attachment-container"
                        class="mx-5 mb-4 p-4 attachment-box d-none align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                                <i class="fas fa-file-pdf fa-2x text-primary"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block">Tài liệu đính kèm</span>
                                <span id="lesson-attachment-name" class="text-muted small">filename.pdf</span>
                            </div>
                        </div>
                        <a href="#" id="lesson-attachment-btn" download
                            class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                            <i class="fas fa-download me-1"></i> Tải về
                        </a>
                    </div>

                    <!-- ASSIGNMENT AREA -->
                    <div class="card-body p-5 flex-grow-1 d-none assignment-area" id="assignment-content-area">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 id="assignment-title" class="fw-bold text-dark mb-0"><i
                                    class="fas fa-tasks text-warning me-2"></i>Tiêu đề bài tập</h2>
                            <span id="assignment-badge" class="badge rounded-pill px-3 py-2 fs-6">Trạng thái</span>
                        </div>
                        <div
                            class="d-flex align-items-center text-danger fw-bold small mb-4 bg-danger bg-opacity-10 d-inline-block px-3 py-2 rounded-3">
                            <i class="fas fa-clock me-2"></i> Hạn nộp: <span id="assignment-due-date" class="ms-1"></span>
                        </div>
                        <hr class="opacity-25">

                        <h5 class="fw-bold mt-4 mb-3"><i class="fas fa-list-check me-2 text-primary"></i>Yêu cầu bài tập:
                        </h5>
                        <div id="assignment-instructions" class="lh-lg bg-white p-4 rounded-4 shadow-sm mb-4 border"></div>

                        @if (auth()->user()->role === 'student')
                            <div id="student-submission-area" class="mt-4">
                                <div id="submitted-info-area" class="d-none">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold text-success mb-0"><i class="fas fa-check-circle me-2"></i>Bài
                                            làm của bạn</h5>
                                    </div>
                                    <div
                                        class="bg-white p-4 rounded-4 shadow-sm mb-3 d-flex align-items-center justify-content-between">
                                        <div>
                                            <p class="mb-1 fw-bold text-dark"><i
                                                    class="fas fa-file-alt me-2 text-primary"></i>Tài liệu đã tải lên</p>
                                            <p class="mb-0 small text-muted"><i class="fas fa-clock me-1"></i> Đã nộp lúc:
                                                <span id="submitted-time-text" class="fw-medium"></span></p>
                                        </div>
                                        <a href="#" id="submitted-file-link" target="_blank"
                                            class="btn btn-outline-primary btn-sm rounded-pill px-3"><i
                                                class="fas fa-eye me-1"></i> Xem file</a>
                                    </div>
                                    <div id="grading-result" class="d-none mb-3 p-4 grading-result-box">
                                        <h6 class="fw-bold text-success mb-2"><i class="fas fa-star me-2"></i>Điểm số:
                                            <span id="grade-score" class="fs-4 text-dark"></span>/10</h6>
                                        <p class="mb-0 text-dark"><strong>Nhận xét:</strong> <span
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

                                <div id="upload-form-area" class="d-none submission-dropzone">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="fw-bold text-primary mb-0"><i
                                                class="fas fa-cloud-upload-alt me-2"></i>Nộp bài tập</h5>
                                        <button type="button" class="btn btn-sm btn-light d-none"
                                            id="btn-cancel-edit">Hủy sửa</button>
                                    </div>
                                    <form id="course-submit-assignment-form" method="POST" enctype="multipart/form-data"
                                        action="">
                                        @csrf
                                        <div class="input-group input-group-lg">
                                            <input type="file" name="file"
                                                class="form-control bg-white border-0 shadow-sm" required>
                                            <button class="btn btn-warning text-dark px-4 fw-bold shadow-sm"
                                                type="submit"><i class="fas fa-paper-plane me-2"></i>Gửi bài</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="text-center mt-5 p-4 bg-white rounded-4 shadow-sm border">
                                <i class="fas fa-users-cog fa-3x text-primary mb-3"></i>
                                <p class="text-muted mb-0">Bấm vào biểu tượng <i
                                        class="fas fa-users-cog text-primary mx-1"></i> ở danh sách bên trái để chấm điểm
                                    bài tập này.</p>
                            </div>
                        @endif
                    </div>

                    <!-- QUIZ AREA -->
                    <div class="card-body p-5 flex-grow-1 d-none quiz-area" id="quiz-content-area">
                        <div class="d-flex flex-column justify-content-center align-items-center h-100 text-center py-5">
                            <div class="p-4 bg-white rounded-circle shadow-sm mb-4 d-inline-block">
                                <i class="fas fa-stopwatch fa-3x" style="color: #6f42c1;"></i>
                            </div>
                            <h2 id="quiz-display-title" class="fw-bold text-dark mb-5">Tiêu đề bài kiểm tra</h2>

                            <div class="d-flex justify-content-center gap-4 mb-5 flex-wrap w-100"
                                style="max-width: 600px;">
                                <div class="quiz-stat-card">
                                    <span class="d-block text-muted small text-uppercase fw-bold mb-2"><i
                                            class="fas fa-clock me-1"></i> Thời gian</span>
                                    <h3 class="mb-0 fw-bold" style="color: #6f42c1;"><span
                                            id="quiz-display-duration">0</span> <small class="fs-6">Phút</small></h3>
                                </div>

                                @if (auth()->user()->role === 'student')
                                    <div class="quiz-stat-card">
                                        <span class="d-block text-muted small text-uppercase fw-bold mb-2"><i
                                                class="fas fa-tasks me-1"></i> Trạng thái</span>
                                        <h3 id="quiz-status-text" class="mb-0 fw-bold text-warning fs-5 mt-1">Chưa làm
                                        </h3>
                                    </div>
                                    <div id="quiz-score-box" class="quiz-stat-card d-none border-success"
                                        style="background: #e6fcf0;">
                                        <span class="d-block text-success small text-uppercase fw-bold mb-2"><i
                                                class="fas fa-star me-1"></i> Điểm số</span>
                                        <h3 class="mb-0 fw-bold text-success mt-1"><span id="quiz-score-text">0</span>/10
                                        </h3>
                                    </div>
                                @endif
                            </div>

                            @if (auth()->user()->role === 'student')
                                <div id="quiz-student-action-area" class="w-100" style="max-width: 500px;">
                                    <div
                                        class="alert border-0 shadow-sm bg-warning bg-opacity-10 mb-4 text-start rounded-4 p-4">
                                        <h6 class="fw-bold text-warning mb-2"><i
                                                class="fas fa-exclamation-triangle me-2"></i>Lưu ý quan trọng:</h6>
                                        <ul class="mb-0 small text-dark">
                                            <li>Đồng hồ sẽ bắt đầu đếm ngược ngay khi bạn bấm nút.</li>
                                            <li>Hệ thống sẽ tự động nộp bài khi hết thời gian.</li>
                                        </ul>
                                    </div>
                                    <div><a href="#" id="start-quiz-btn"
                                            class="btn btn-quiz-start text-white w-100">BẮT ĐẦU LÀM BÀI <i
                                                class="fas fa-arrow-right ms-2"></i></a></div>
                                </div>
                                <div id="quiz-completed-msg" class="d-none w-100 mt-4" style="max-width: 500px;">
                                    <div class="alert border-0 shadow-sm px-5 py-4 w-100 text-center rounded-4"
                                        style="background-color: #d1e7dd; color: #0f5132;">
                                        <h4 class="fw-bold mb-2"><i class="fas fa-check-circle me-2"></i>Hoàn thành!</h4>
                                        <p class="mb-0">Bạn đã nộp bài kiểm tra này thành công.</p>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="#" id="review-quiz-btn"
                                            class="btn btn-success rounded-pill px-5 py-3 shadow-sm fw-bold">
                                            <i class="fas fa-search me-2"></i> Xem chi tiết bài làm
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info border-0 shadow-sm bg-info bg-opacity-10 mb-4 text-start d-inline-block rounded-4 p-4"
                                    style="max-width: 500px;">
                                    <h6 class="fw-bold text-info mb-2"><i class="fas fa-info-circle me-2"></i>Khu vực Quản
                                        lý:</h6>
                                    <p class="mb-0 small text-dark">Bạn có thể vào trang soạn thảo để thêm/sửa/xóa câu hỏi.
                                    </p>
                                </div>
                                <a href="#" id="manage-quiz-btn" class="btn btn-quiz-start text-white"><i
                                        class="fas fa-cog me-2"></i> VÀO TRANG SOẠN CÂU HỎI</a>
                            @endif
                        </div>
                    </div>

                    <!-- FOOTER NAVIGATION -->
                    <div class="card-footer footer-nav p-4 d-flex justify-content-between align-items-center"
                        id="nav-footer">
                        <button class="btn btn-outline-secondary btn-footer-nav px-4" id="btn-prev" disabled><i
                                class="fas fa-arrow-left me-2"></i> Bài trước</button>
                        <button class="btn btn-success btn-complete-lesson px-4 d-none" id="btn-complete"><i
                                class="fas fa-check-circle me-2"></i> Hoàn thành bài học</button>
                        <button class="btn btn-outline-secondary btn-footer-nav px-4" id="btn-next" disabled>Bài tiếp
                            theo <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('courses.partials.modals')
    @include('courses.partials.scripts')
@endsection
