@extends('layouts.app')

@section('title', $course->title)

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap');

        * {
            box-sizing: border-box;
        }

        body,
        .card,
        .card-body {
            font-family: 'Be Vietnam Pro', sans-serif;
        }

        /* ── LAYOUT ── */
        .page-wrapper {
            background: #f0f2f5;
            min-height: 100vh;
            padding: 24px 20px;
        }

        /* ── HEADER CARD ── */
        .header-card {
            background: #fff;
            border-radius: 20px;
            padding: 24px 28px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06), 0 4px 16px rgba(0, 0, 0, .04);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .header-course-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #111827;
            margin: 0 0 4px;
            line-height: 1.3;
        }

        .header-teacher {
            font-size: 13px;
            color: #6b7280;
            margin: 0;
        }

        /* Progress */
        .progress-wrap {
            min-width: 260px;
            margin-top: 12px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #6b7280;
        }

        .progress-label span:last-child {
            color: #2563eb;
        }

        .progress-track {
            height: 8px;
            background: #e5e7eb;
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, #60a5fa, #2563eb);
            transition: width .6s ease;
        }

        /* ── TOOLBAR ── */
        .toolbar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 7px;
            flex-wrap: wrap;
        }

        .tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all .18s;
            text-decoration: none;
            line-height: 1;
        }

        .tool-btn i {
            font-size: 12px;
        }

        .tool-btn.blue {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .tool-btn.blue:hover {
            background: #dbeafe;
        }

        .tool-btn.amber {
            background: #fffbeb;
            color: #92400e;
        }

        .tool-btn.amber:hover {
            background: #fef3c7;
        }

        .tool-btn.purple {
            background: #f5f3ff;
            color: #5b21b6;
        }

        .tool-btn.purple:hover {
            background: #ede9fe;
        }

        .tool-btn.teal {
            background: #ecfdf5;
            color: #065f46;
        }

        .tool-btn.teal:hover {
            background: #d1fae5;
        }

        /* ── SIDEBAR (unchanged logic, improved look) ── */
        .sidebar-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06), 0 4px 16px rgba(0, 0, 0, .04);
            height: 100%;
        }

        .lesson-item-wrapper,
        .assignment-item-wrapper,
        .quiz-item-wrapper {
            transition: all 0.18s ease;
            border-left: 3px solid transparent;
            cursor: pointer;
            border-radius: 0 10px 10px 0;
        }

        .lesson-item-wrapper:hover,
        .assignment-item-wrapper:hover,
        .quiz-item-wrapper:hover {
            background: #f9fafb !important;
            border-left-color: #2563eb;
        }

        .lesson-item-wrapper.active,
        .assignment-item-wrapper.active,
        .quiz-item-wrapper.active {
            background: #eff6ff !important;
            border-left-color: #2563eb;
        }

        .action-buttons {
            opacity: 0;
            transition: opacity 0.18s;
            flex-shrink: 0;
            padding-left: 6px;
        }

        .lesson-item-wrapper:hover .action-buttons,
        .module-header-wrapper:hover .action-buttons,
        .assignment-item-wrapper:hover .action-buttons,
        .quiz-item-wrapper:hover .action-buttons {
            opacity: 1;
        }

        .btn-action {
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 12px;
            transition: all .18s;
            text-decoration: none;
        }

        .btn-edit {
            color: #f59e0b;
        }

        .btn-edit:hover {
            background: #fef3c7;
        }

        .btn-delete {
            color: #ef4444;
        }

        .btn-delete:hover {
            background: #fee2e2;
        }

        .text-truncate-custom {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            min-width: 0;
        }

        .accordion-button:not(.collapsed) {
            background: #fff;
            color: #2563eb;
            box-shadow: none;
        }

        .accordion-button:focus {
            box-shadow: none;
        }

        .accordion-button {
            padding-right: 3rem;
            font-weight: 700;
            font-size: 14px;
        }

        .accordion-item {
            border: none;
            border-bottom: 1px solid #f3f4f6;
        }

        .accordion-item:last-child {
            border-bottom: none;
        }

        /* ── CONTENT CARD ── */
        .content-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06), 0 4px 16px rgba(0, 0, 0, .04);
            display: flex;
            flex-direction: column;
        }

        /* Video */
        #video-container iframe {
            border-radius: 20px 20px 0 0;
        }

        /* External link */
        #external-link-container {
            background: linear-gradient(135deg, #eff6ff, #f0fdf4);
            border-bottom: 1px solid #e5e7eb;
        }

        /* ── LESSON AREA ── */
        #lesson-content-area {
            padding: 40px 48px;
        }

        .lesson-header-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 20px;
        }

        .lesson-divider {
            border: none;
            border-top: 2px solid #f3f4f6;
            margin-bottom: 28px;
        }

        .lesson-body {
            font-size: 15px;
            line-height: 1.85;
            color: #374151;
        }

        .course-intro-card {
            background: linear-gradient(135deg, #fafbff, #f0f9ff);
            border: 1px solid #e0eaff;
            border-radius: 16px;
            padding: 36px 40px;
        }

        /* Attachment */
        .attachment-box {
            margin: 0 40px 32px;
            background: #fafafa;
            border: 1.5px dashed #d1d5db;
            border-radius: 14px;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all .2s;
        }

        .attachment-box:hover {
            border-color: #2563eb;
            background: #eff6ff;
        }

        /* ── ASSIGNMENT AREA ── */
        #assignment-content-area {
            padding: 40px 48px;
            background: #fffdf5;
        }

        .assignment-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #111827;
        }

        .due-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 99px;
            border: 1px solid #fecaca;
        }

        .instructions-box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 28px 32px;
            line-height: 1.8;
            color: #374151;
            font-size: 14.5px;
        }

        .submission-dropzone {
            border: 2px dashed #fbbf24;
            background: #fffbeb;
            border-radius: 16px;
            padding: 28px;
            transition: all .3s;
        }

        .submission-dropzone:hover {
            border-color: #f59e0b;
            background: #fef3c7;
        }

        .submitted-file-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .grading-result-box {
            border-radius: 14px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 20px 24px;
        }

        /* ── QUIZ AREA ── */
        #quiz-content-area {
            padding: 40px 48px;
            background: #faf8ff;
        }

        .quiz-display-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 32px;
        }

        .quiz-stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 22px 28px;
            text-align: center;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
            flex: 1;
            min-width: 140px;
        }

        .quiz-stat-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        .quiz-stat-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: #7c3aed;
        }

        .btn-quiz-start {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            border: none;
            padding: 14px 40px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 99px;
            box-shadow: 0 6px 24px rgba(124, 58, 237, .28);
            transition: all .3s;
            color: #fff;
        }

        .btn-quiz-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(124, 58, 237, .36);
            color: #fff;
        }

        .quiz-notice {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 14px;
            padding: 20px 24px;
            text-align: left;
            max-width: 480px;
            width: 100%;
        }

        /* ── FOOTER NAV ── */
        .footer-nav {
            background: #fff;
            border-top: 1px solid #f3f4f6;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-footer-nav {
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            padding: 9px 22px;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            color: #374151;
            transition: all .18s;
        }

        .btn-footer-nav:hover:not(:disabled) {
            border-color: #2563eb;
            color: #2563eb;
            background: #eff6ff;
        }

        .btn-footer-nav:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .btn-complete-lesson {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            padding: 9px 24px;
            color: #fff;
            box-shadow: 0 4px 14px rgba(34, 197, 94, .28);
            transition: all .3s;
        }

        .btn-complete-lesson:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(34, 197, 94, .36);
            color: #fff;
        }

        /* Modal z-index */
        .modal-backdrop {
            z-index: 1050 !important;
        }

        .modal {
            z-index: 1060 !important;
        }

        .sticky-top {
            z-index: 1000 !important;
        }

        @media (max-width: 768px) {

            #lesson-content-area,
            #assignment-content-area,
            #quiz-content-area {
                padding: 24px 20px;
            }

            .header-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .attachment-box {
                margin: 0 20px 24px;
            }
        }
    </style>

    <div class="page-wrapper">

        {{-- ── HEADER ── --}}
        <div class="header-card">
            <div>
                <h1 class="header-course-title">{{ $course->title }}</h1>
                <p class="header-teacher"><i class="fas fa-chalkboard-teacher me-1"></i> Giáo viên:
                    {{ $course->teacher->name }}</p>

                @if (auth()->user()->role === 'student')
                    <div class="progress-wrap mt-3">
                        <div class="progress-label">
                            <span>Tiến độ học tập</span>
                            <span id="progress-text">{{ $completedCount }}/{{ $totalLessons }} bài
                                &nbsp;({{ $progress }}%)</span>
                        </div>
                        <div class="progress-track">
                            <div id="progress-bar" class="progress-fill" style="width: {{ $progress }}%;"></div>
                        </div>
                    </div>
                @endif
            </div>

            @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                <div class="toolbar">
                    <button class="tool-btn blue" data-bs-toggle="modal" data-bs-target="#addModuleModal"><i
                            class="fas fa-folder-plus"></i> Thêm chương</button>
                    <button class="tool-btn blue" data-bs-toggle="modal" data-bs-target="#addLessonModal"><i
                            class="fas fa-plus"></i> Bài học</button>
                    <button class="tool-btn amber" data-bs-toggle="modal" data-bs-target="#addCourseAssignmentModal"><i
                            class="fas fa-tasks"></i> Bài tập</button>
                    <button class="tool-btn purple"data-bs-toggle="modal" data-bs-target="#addQuizModal"><i
                            class="fas fa-question-circle"></i> Trắc nghiệm</button>
                    <a href="{{ route('attendance.show', $course->id) }}" class="tool-btn teal"><i
                            class="fas fa-user-check"></i> Điểm danh</a>
                </div>
            @endif
        </div>

        {{-- ── MAIN GRID ── --}}
        <div class="row g-4 align-items-start">

            {{-- SIDEBAR --}}
            @include('courses.partials.sidebar')

            {{-- CONTENT --}}
            <div class="col-md-8 col-lg-9">
                <div class="content-card">

                    {{-- Video --}}
                    <div id="video-container" class="ratio ratio-16x9 bg-dark d-none">
                        <iframe id="lesson-video" src="" allowfullscreen></iframe>
                    </div>

                    {{-- External link banner --}}
                    <div id="external-link-container" class="p-5 text-center d-none border-bottom">
                        <div class="mb-3 d-inline-flex align-items-center justify-content-center bg-blue-100 rounded-circle p-3"
                            style="background:#dbeafe;">
                            <i class="fas fa-external-link-alt fa-2x text-primary"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Tài liệu / Video tham khảo ngoài</h5>
                        <p class="text-muted small mb-4">Bài học này chứa một liên kết ngoài hệ thống.</p>
                        <a href="#" id="external-link-btn" target="_blank"
                            class="btn btn-primary rounded-pill px-5 fw-bold">
                            <i class="fas fa-external-link-alt me-2"></i>Truy cập ngay
                        </a>
                    </div>

                    {{-- ══ LESSON AREA ══ --}}
                    <div id="lesson-content-area">
                        <h2 id="lesson-title" class="lesson-header-title">{{ $course->title }}</h2>
                        <hr class="lesson-divider">
                        <div id="lesson-body" class="lesson-body">
                            <div class="course-intro-card">
                                <div class="course-description">
                                    {!! nl2br(e($course->description)) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Attachment --}}
                    <div id="lesson-attachment-container" class="attachment-box d-none">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-file-pdf fa-xl text-primary"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block" style="font-size:14px;">Tài liệu đính kèm</span>
                                <span id="lesson-attachment-name" class="text-muted"
                                    style="font-size:12px;">filename.pdf</span>
                            </div>
                        </div>
                        <a href="#" id="lesson-attachment-btn" download
                            class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                            <i class="fas fa-download me-1"></i> Tải về
                        </a>
                    </div>

                    {{-- ══ ASSIGNMENT AREA ══ --}}
                    <div id="assignment-content-area" class="d-none flex-column">

                        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                            <h2 id="assignment-title" class="assignment-title">
                                <i class="fas fa-tasks text-amber-500 me-2" style="color:#f59e0b;"></i>Tiêu đề bài tập
                            </h2>
                            <span id="assignment-badge" class="badge rounded-pill px-3 py-2 fs-6">Trạng thái</span>
                        </div>

                        <div class="mb-4">
                            <span class="due-badge">
                                <i class="fas fa-clock"></i> Hạn nộp: <span id="assignment-due-date"></span>
                            </span>
                        </div>

                        <hr class="lesson-divider">

                        <h6 class="fw-700 mb-3" style="font-weight:700;color:#374151;">
                            <i class="fas fa-list-check me-2 text-primary"></i>Yêu cầu bài tập
                        </h6>
                        <div id="assignment-instructions" class="instructions-box mb-5"></div>

                        @if (auth()->user()->role === 'student')
                            <div id="student-submission-area">

                                {{-- Đã nộp --}}
                                <div id="submitted-info-area" class="d-none">
                                    <h6 class="fw-bold text-success mb-3"><i class="fas fa-check-circle me-2"></i>Bài làm
                                        của bạn</h6>
                                    <div class="submitted-file-card mb-3">
                                        <div>
                                            <p class="mb-1 fw-bold" style="font-size:14px;"><i
                                                    class="fas fa-file-alt me-2 text-primary"></i>Tài liệu đã tải lên</p>
                                            <p class="mb-0 text-muted" style="font-size:12px;"><i
                                                    class="fas fa-clock me-1"></i>Đã nộp lúc: <span
                                                    id="submitted-time-text" class="fw-medium"></span></p>
                                        </div>
                                        <a href="#" id="submitted-file-link" target="_blank"
                                            class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            <i class="fas fa-eye me-1"></i> Xem file
                                        </a>
                                    </div>

                                    <div id="grading-result" class="d-none mb-3 grading-result-box">
                                        <h6 class="fw-bold text-success mb-2">
                                            <i class="fas fa-star me-2"></i>Điểm số: <span id="grade-score"
                                                class="text-dark fs-5"></span>/10
                                        </h6>
                                        <p class="mb-0 text-dark" style="font-size:14px;"><strong>Nhận xét:</strong> <span
                                                id="grade-feedback"></span></p>
                                    </div>

                                    <div class="d-flex gap-2 flex-wrap" id="submission-actions">
                                        <button type="button" class="btn btn-primary rounded-pill px-4"
                                            id="btn-edit-submission">
                                            <i class="fas fa-edit me-1"></i> Chỉnh sửa bài nộp
                                        </button>
                                        <form id="delete-submission-form" method="POST" class="m-0"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn hủy bài đã nộp?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger rounded-pill px-4">
                                                <i class="fas fa-trash-alt me-1"></i> Hủy bài nộp
                                            </button>
                                        </form>
                                    </div>

                                    <p id="graded-warning" class="text-danger small mt-2 d-none fst-italic">
                                        <i class="fas fa-lock me-1"></i>Giáo viên đã chấm điểm, bạn không thể sửa hoặc xóa
                                        bài.
                                    </p>
                                </div>

                                {{-- Form nộp bài --}}
                                <div id="upload-form-area" class="d-none submission-dropzone">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h6 class="fw-bold text-primary mb-0"><i
                                                class="fas fa-cloud-upload-alt me-2"></i>Nộp bài tập</h6>
                                        <button type="button" class="btn btn-sm btn-light d-none"
                                            id="btn-cancel-edit">Hủy sửa</button>
                                    </div>
                                    <form id="course-submit-assignment-form" method="POST" enctype="multipart/form-data"
                                        action="">
                                        @csrf
                                        <div class="input-group input-group-lg">
                                            <input type="file" name="file"
                                                class="form-control bg-white border-0 shadow-sm" required>
                                            <button class="btn btn-warning text-dark px-4 fw-bold" type="submit">
                                                <i class="fas fa-paper-plane me-2"></i>Gửi bài
                                            </button>
                                        </div>
                                    </form>
                                </div>

                            </div>
                        @else
                            <div class="text-center p-5 bg-white rounded-4 border">
                                <i class="fas fa-users-cog fa-3x text-primary mb-3 d-block"></i>
                                <p class="text-muted mb-0">Bấm vào biểu tượng <i
                                        class="fas fa-users-cog text-primary mx-1"></i> ở danh sách bên trái để chấm điểm
                                    bài tập này.</p>
                            </div>
                        @endif
                    </div>

                    {{-- ══ QUIZ AREA ══ --}}
                    <div id="quiz-content-area" class="d-none flex-column align-items-center">
                        <div class="w-100" style="max-width:560px;">

                            {{-- Icon --}}
                            <div class="text-center mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                    style="width:80px;height:80px;background:#f5f3ff;">
                                    <i id="quiz-main-icon" class="fas fa-stopwatch fa-2x" style="color:#7c3aed;"></i>
                                </div>
                                <h2 id="quiz-display-title" class="quiz-display-title">Tiêu đề bài kiểm tra</h2>
                            </div>

                            {{-- Stats --}}
                            <div class="d-flex gap-3 mb-5 flex-wrap">
                                <div class="quiz-stat-card">
                                    <div class="quiz-stat-label"><i class="fas fa-clock me-1"></i> Thời gian</div>
                                    <div class="quiz-stat-value"><span id="quiz-display-duration">0</span> <small
                                            style="font-size:.9rem;font-weight:600;color:#6b7280;">phút</small></div>
                                </div>

                                @if (auth()->user()->role === 'student')
                                    <div class="quiz-stat-card">
                                        <div class="quiz-stat-label"><i class="fas fa-tasks me-1"></i> Trạng thái</div>
                                        <div>
                                            <span id="quiz-status-text" class="fw-bold text-warning"
                                                style="font-size:1rem;">Chưa làm</span>
                                        </div>
                                    </div>
                                    <div id="quiz-score-box" class="quiz-stat-card d-none"
                                        style="background:#f0fdf4;border-color:#bbf7d0;">
                                        <div class="quiz-stat-label text-success"><i class="fas fa-star me-1"></i> Điểm số
                                        </div>
                                        <div class="quiz-stat-value text-success"><span id="quiz-score-text">0</span>/10
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if (auth()->user()->role === 'student')
                                {{-- Action area --}}
                                <div id="quiz-student-action-area">
                                    <div class="quiz-notice mb-4">
                                        <h6 class="fw-bold mb-2" style="color:#92400e;font-size:13px;">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Lưu ý quan trọng
                                        </h6>
                                        <ul class="mb-0 small text-dark ps-3">
                                            <li>Đồng hồ sẽ bắt đầu đếm ngược ngay khi bạn bấm nút.</li>
                                            <li>Hệ thống sẽ tự động nộp bài khi hết thời gian.</li>
                                        </ul>
                                    </div>
                                    <a href="#" id="start-quiz-btn" class="btn btn-quiz-start w-100">
                                        BẮT ĐẦU LÀM BÀI <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>

                                {{-- Completed --}}
                                <div id="quiz-completed-msg" class="d-none">
                                    <div class="text-center p-4 rounded-4 mb-3" style="background:#dcfce7;color:#14532d;">
                                        <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                                        <h5 class="fw-bold mb-1">Hoàn thành!</h5>
                                        <p class="mb-0 small">Bạn đã nộp bài kiểm tra này thành công.</p>
                                    </div>
                                    <a href="#" id="review-quiz-btn"
                                        class="btn btn-success rounded-pill w-100 py-3 fw-bold">
                                        <i class="fas fa-search me-2"></i> Xem chi tiết bài làm
                                    </a>
                                </div>
                            @else
                                <div class="quiz-notice mb-4" style="background:#eff6ff;border-color:#bfdbfe;">
                                    <h6 class="fw-bold mb-2 text-primary" style="font-size:13px;">
                                        <i class="fas fa-info-circle me-2"></i>Khu vực Quản lý
                                    </h6>
                                    <p class="mb-0 small text-dark">Bạn có thể vào trang soạn thảo để thêm / sửa / xóa câu
                                        hỏi.</p>
                                </div>
                                <a href="#" id="manage-quiz-btn" class="btn btn-quiz-start w-100">
                                    <i class="fas fa-cog me-2"></i> VÀO TRANG SOẠN CÂU HỎI
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- ══ FOOTER NAV ══ --}}
                    <div class="footer-nav d-none" id="nav-footer">
                        <button class="btn-footer-nav" id="btn-prev" disabled>
                            <i class="fas fa-arrow-left me-2"></i>Bài trước
                        </button>
                        <button class="btn btn-complete-lesson px-4 d-none" id="btn-complete">
                            <i class="fas fa-check-circle me-1"></i> Hoàn thành bài học
                        </button>
                        <button class="btn-footer-nav" id="btn-next" disabled>
                            Bài tiếp theo <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>

                </div>{{-- /content-card --}}
            </div>

        </div>{{-- /row --}}
    </div>{{-- /page-wrapper --}}

    @include('courses.partials.modals')
    @include('courses.partials.scripts')
@endsection
