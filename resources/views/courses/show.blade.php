@extends('layouts.app')

@section('title', $course->title)

@section('content')
    @push('styles')
        @vite('resources/css/pages/course-show.css')
    @endpush

    {{-- Mobile overlay + drawer --}}
    <div id="mobile-sidebar-overlay" aria-hidden="true"></div>
    <div id="mobile-sidebar-drawer" role="dialog" aria-modal="true" aria-hidden="true"
        aria-labelledby="mobile-sidebar-title" tabindex="-1">
        <div class="mobile-drawer-header">
            <h2 id="mobile-sidebar-title" class="mobile-drawer-title">
                <i class="fa-solid fa-list-ul me-2 text-primary"></i>Nội dung khóa học
            </h2>
            <button id="btn-close-sidebar" class="btn btn-sm btn-light border" type="button"
                aria-label="Đóng danh sách nội dung">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <div id="mobile-sidebar-content"></div>
    </div>

    <div class="page-wrapper" id="course-page-wrapper">

        @php
            $courseLessonCount = $course->modules->sum(fn($module) => $module->lessons->count());
            $courseAssignmentCount = $course->modules->sum(
                fn($module) => $module->lessons->sum(fn($lesson) => $lesson->assignments->count()),
            );
            $courseQuizCount = $course->quizzes
                ->filter(fn($quiz) => $quiz->sessions->isEmpty())
                ->count();
            $courseExamCount = $course->quizzes
                ->filter(fn($quiz) => $quiz->sessions->isNotEmpty())
                ->count();
            $isCourseManager = auth()->id() === $course->teacher_id || auth()->user()->role === 'admin';
            $allLessons = $course->modules->flatMap(fn($module) => $module->lessons);
            $allAssignments = $allLessons->flatMap(fn($lesson) => $lesson->assignments);
            $nextLesson = $allLessons->first();
            $nextAssignment =
                auth()->user()->role === 'student'
                    ? $allAssignments->first(fn($assignment) => !isset($userSubmissions[$assignment->id]))
                    : $allAssignments->first();
            $finalExam = $course->quizzes->first(fn($quiz) => $quiz->sessions->isNotEmpty());
            $regularQuizzes = $course->quizzes->filter(fn($quiz) => $quiz->sessions->isEmpty());
            $nextQuiz =
                auth()->user()->role === 'student'
                    ? $regularQuizzes->first(fn($quiz) => !isset($userQuizAttempts[$quiz->id]))
                    : $regularQuizzes->first();
            $courseStatusLabel = match ($course->status ?? 'published') {
                'draft' => 'Bản nháp',
                'hidden' => 'Đang ẩn',
                'archived' => 'Đã lưu trữ',
                default => 'Đã xuất bản',
            };
        @endphp

        {{-- ── HEADER ── --}}
        <div class="header-card course-ref-header">
            <div class="min-w-0">
                <div class="course-ref-badges">
                    <span class="course-ref-badge">
                        {{ auth()->user()->role === 'student' ? 'Đang học' : 'Khóa học' }}
                    </span>
                    <span class="course-ref-badge light">{{ $courseStatusLabel }}</span>
                </div>
                <h1 class="header-course-title">{{ $course->title }}</h1>
                <p class="header-teacher">
                    <i class="fa-solid fa-chalkboard-teacher"></i> {{ $course->teacher->name }}
                </p>
            </div>

            <div class="course-ref-side">
                @if ($isCourseManager)
                    <div class="course-ref-stats">
                        <div class="course-ref-stat"><strong>{{ $course->modules->count() }}</strong><span>Chương</span></div>
                        <div class="course-ref-stat"><strong>{{ $courseLessonCount }}</strong><span>Bài học</span></div>
                        <div class="course-ref-stat"><strong>{{ $courseAssignmentCount }}</strong><span>Bài tập</span></div>
                        <div class="course-ref-stat"><strong>{{ $course->quizzes->count() }}</strong><span>Kiểm tra</span></div>
                    </div>
                @else
                    <div class="course-review-panel">
                        <div class="course-review-panel__copy">
                            <span class="course-review-panel__eyebrow">Kho nội dung xem lại</span>
                            <strong>{{ $course->modules->count() }} chương · {{ $courseLessonCount }} bài học</strong>
                        </div>
                        @if ($nextLesson)

                        @endif
                        <div class="course-review-links">
                            <a href="{{ route('attendance.show', $course->id) }}">
                                <i class="fa-solid fa-chart-column"></i> Điểm số
                            </a>
                            <a href="{{ route('courses.materials.index', $course->id) }}">
                                <i class="fa-solid fa-folder-open"></i> Kho học liệu
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if ($isCourseManager)
            <div class="teacher-mode-panel" id="teacher-mode-panel">
                <div class="teacher-mode-row">
                    <div>
                        <h6 class="teacher-mode-title"><i class="fa-solid fa-layer-group me-2"></i>Chế độ giáo viên</h6>
                        <div class="teacher-mode-subtitle">Quản lý nội dung, theo dõi tiến độ và thao tác nhanh.</div>
                    </div>
                    <div class="teacher-mode-toggle" role="group" aria-label="Chế độ hiển thị khóa học">
                        <button type="button" class="teacher-mode-btn active" data-course-mode="manage" aria-pressed="true">
                            <i class="fa-solid fa-pen-to-square me-1"></i>Quản lý
                        </button>
                        <button type="button" class="teacher-mode-btn" data-course-mode="preview" aria-pressed="false">
                            <i class="fa-solid fa-eye me-1"></i>Xem như học viên
                        </button>
                    </div>
                </div>
                <div class="teacher-preview-banner">
                    <i class="fa-solid fa-eye"></i>
                    <span>Đang xem ở chế độ học viên. Nút sửa/xóa đang ẩn.</span>
                </div>
                <div class="teacher-quick-actions">
                    <div class="dropdown">
                        <button type="button" class="tool-btn blue dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="fa-solid fa-plus"></i> Thêm nội dung
                        </button>
                        <ul class="dropdown-menu course-tool-menu">
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal"
                                    data-bs-target="#addModuleModal"><i class="fa-solid fa-folder-plus"></i>Thêm chương</button></li>
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal"
                                    data-bs-target="#addLessonModal"><i class="fa-solid fa-file-circle-plus"></i>Thêm bài học</button></li>
                        </ul>
                    </div>
                    <button type="button" class="tool-btn amber" data-bs-toggle="modal"
                        data-bs-target="#addCourseAssignmentModal">
                        <i class="fa-solid fa-file-signature"></i> Giao bài tập
                    </button>
                    <button type="button" class="tool-btn purple" data-bs-toggle="modal" data-bs-target="#addQuizModal">
                        <i class="fa-solid fa-stopwatch"></i> Tạo bài kiểm tra
                    </button>
                    <a href="{{ route('attendance.show', $course->id) }}" class="tool-btn teal">
                        <i class="fa-solid fa-user-check"></i> Điểm danh
                    </a>
                    <a href="{{ route('courses.materials.index', $course->id) }}" class="tool-btn blue">
                        <i class="fa-solid fa-folder-open"></i> Kho học liệu
                    </a>
                    <div class="dropdown teacher-more-tools">
                        <button type="button" class="tool-btn neutral dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="fa-solid fa-ellipsis"></i> Công cụ khác
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end course-tool-menu">
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal"
                                    data-bs-target="#aiCoursePlanModal"><i class="fa-solid fa-wand-magic-sparkles"></i>AI thiết kế khóa học</button></li>
                            <li><a href="{{ route('quizzes.ai_generate') }}" class="dropdown-item"><i
                                        class="fa-solid fa-robot"></i>Tạo câu hỏi bằng AI</a></li>
                            <li><button type="button" class="dropdown-item" id="course-quality-check-btn"
                                    data-url="{{ route('courses.quality-check', $course->id) }}"><i
                                        class="fa-solid fa-shield-halved"></i>Kiểm tra chất lượng</button></li>
                            <li><button type="button" class="dropdown-item" id="start-presentation-btn"><i
                                        class="fa-solid fa-display"></i>Trình chiếu bài học</button></li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @if (auth()->user()->role !== 'student')
            <div class="course-dashboard-grid">
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label"><i class="fa-solid fa-users me-1 text-blue-500"></i> Học viên</div>
                    <div class="course-dashboard-value">{{ $courseDashboard['students_count'] }}</div>
                    <div class="course-dashboard-sub">{{ $courseDashboard['modules_count'] }} chương ·
                        {{ $courseDashboard['lessons_count'] }} bài</div>
                </div>
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label"><i class="fa-solid fa-layer-group me-1"></i> Nội dung</div>
                    <div class="course-dashboard-value">{{ $courseDashboard['lessons_count'] }}</div>
                    <div class="course-dashboard-sub">Trong {{ $courseDashboard['modules_count'] }} chương</div>
                </div>
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label"><i class="fa-solid fa-file-signature me-1 text-warning"></i> Nộp bài
                        tập
                    </div>
                    <div class="course-dashboard-value">{{ $courseDashboard['assignment_submission_rate'] }}%</div>
                    <div class="course-dashboard-sub">{{ $courseDashboard['pending_grades'] }} bài chờ chấm</div>
                </div>
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label"><i class="fa-solid fa-stopwatch me-1 text-purple"></i> Điểm kiểm tra</div>
                    <div class="course-dashboard-value">
                        {{ $courseDashboard['average_score'] !== null ? round($courseDashboard['average_score'], 1) : '—' }}
                    </div>
                    <div class="course-dashboard-sub">Điểm trung bình hiện tại</div>
                </div>
            </div>
        @endif

        {{-- ── MAIN GRID ── --}}
        <div class="course-ref-grid row align-items-start">

            {{-- DESKTOP SIDEBAR --}}
            <div class="col-md-4 col-xl-3 d-none d-md-block order-md-2 course-sidebar-column">
                <div class="desktop-sidebar-wrap course-sidebar-stack">
                    <div class="sidebar-inner-card course-outline-card">
                        <div class="sidebar-head">
                            <div class="sidebar-head-row">
                                <h6 class="sidebar-head-title">
                                    <i class="fa-solid fa-list-ul text-primary"></i>  Nội dung khóa học
                                </h6>
                                <span class="sidebar-head-count">{{ $course->modules->count() }} chương</span>
                            </div>
                            <div class="course-sidebar-summary">{{ $courseLessonCount }} bài học · {{ $courseAssignmentCount }} bài tập</div>
                            <div class="course-outline-search-wrap">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                <input type="search" class="course-outline-search" data-outline-target="#courseAccordion"
                                    placeholder="Tìm chương hoặc bài học" aria-label="Tìm trong nội dung khóa học">
                            </div>
                        </div>
                        @if ($isCourseManager)
                            <div id="reorder-toast" class="reorder-toast mx-3 mt-3">
                                <i class="fa-solid fa-check me-1"></i>Đã lưu thứ tự nội dung
                            </div>
                        @endif
                        <div class="sidebar-scroll">
                            @include('courses.partials.sidebar')
                        </div>
                    </div>

                    <div class="course-side-card">
                        <h6 class="course-side-card__title">
                            <i class="fa-solid fa-book-open-reader"></i>
                            {{ auth()->user()->role === 'student' ? 'Xem lại nhanh' : 'Lối tắt nội dung' }}
                        </h6>
                        <div class="course-todo-list">
                            @if ($nextLesson)
                                <button type="button" class="course-todo-item course-jump-btn"
                                    data-target-type="lesson" data-target-id="{{ $nextLesson->id }}">
                                    <span class="course-todo-icon lesson"><i class="fa-solid fa-play"></i></span>
                                    <span>
                                        <span class="course-todo-title">
                                            {{ auth()->user()->role === 'student' ? 'Mở bài đầu tiên' : 'Xem bài học đầu tiên' }}
                                        </span>
                                        <span class="course-todo-meta">{{ $nextLesson->title }}</span>
                                    </span>
                                </button>
                            @endif

                            @if ($nextAssignment)
                                <button type="button" class="course-todo-item course-jump-btn"
                                    data-target-type="assignment" data-target-id="{{ $nextAssignment->id }}">
                                    <span class="course-todo-icon assignment"><i class="fa-solid fa-file-signature"></i></span>
                                    <span>
                                        <span
                                            class="course-todo-title">{{ auth()->user()->role === 'student' ? 'Nộp bài tập' : 'Bài tập trong khóa' }}</span>
                                        <span class="course-todo-meta">
                                            {{ $nextAssignment->title }}
                                            @if ($nextAssignment->due_date)
                                                · Hạn {{ $nextAssignment->due_date->format('d/m/Y') }}
                                            @endif
                                        </span>
                                    </span>
                                </button>
                            @endif

                            @if ($nextQuiz)
                                <button type="button" class="course-todo-item course-jump-btn"
                                    data-target-type="quiz" data-target-id="{{ $nextQuiz->id }}">
                                    <span class="course-todo-icon quiz"><i class="fa-solid fa-list-check"></i></span>
                                    <span>
                                        <span
                                            class="course-todo-title">{{ auth()->user()->role === 'student' ? 'Làm kiểm tra' : 'Kiểm tra trong khóa' }}</span>
                                        <span class="course-todo-meta">{{ $nextQuiz->title }} ·
                                            {{ $nextQuiz->time_limit }} phút</span>
                                    </span>
                                </button>
                            @endif

                            @if ($finalExam)
                                <button type="button" class="course-todo-item course-jump-btn"
                                    data-target-type="quiz" data-target-id="{{ $finalExam->id }}">
                                    <span class="course-todo-icon exam"><i class="fa-solid fa-award"></i></span>
                                    <span>
                                        <span class="course-todo-title">Thi kết thúc học phần</span>
                                        <span class="course-todo-meta">{{ $finalExam->title }}</span>
                                    </span>
                                </button>
                            @endif

                            @if (!$nextLesson && !$nextAssignment && !$nextQuiz && !$finalExam)
                                <div class="course-todo-item">
                                    <span class="course-todo-icon lesson"><i class="fa-solid fa-folder-open"></i></span>
                                    <span>
                                        <span class="course-todo-title">Chưa có nội dung để xem</span>
                                        <span class="course-todo-meta">Giáo viên chưa đăng bài học cho khóa này.</span>
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="course-side-card">
                        <h6 class="course-side-card__title">
                            <i class="fa-solid fa-circle-info"></i> Thông tin khóa học
                        </h6>
                        <div class="course-info-list">
                            <div class="course-info-row">
                                <span class="course-info-label">Số bài học</span>
                                <span class="course-info-value">{{ $courseLessonCount }} bài</span>
                            </div>
                            <div class="course-info-row">
                                <span class="course-info-label">Bài tập</span>
                                <span class="course-info-value">{{ $courseAssignmentCount }} bài</span>
                            </div>
                            <div class="course-info-row">
                                <span class="course-info-label">Bài kiểm tra</span>
                                <span class="course-info-value">{{ $courseQuizCount }} bài</span>
                            </div>
                            <div class="course-info-row">
                                <span class="course-info-label">Bài thi</span>
                                <span class="course-info-value">{{ $courseExamCount }} bài</span>
                            </div>
                            <div class="course-info-row">
                                <span class="course-info-label">Giáo viên</span>
                                <span class="course-info-value">{{ $course->teacher->name }}</span>
                            </div>
                            <div class="course-info-row">
                                <span class="course-info-label">Trạng thái</span>
                                <span class="course-info-value">{{ $courseStatusLabel }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CONTENT --}}
            <div class="col-12 col-md-8 col-xl-9 order-md-1" id="course-content-column">
                <div class="content-card">

                    {{-- Video --}}
                    <div id="video-container" class="ratio ratio-16x9 bg-dark d-none">
                        <iframe id="lesson-video" src="" title="Video bài học đang xem" loading="lazy"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                    </div>

                    {{-- External link banner --}}
                    <div id="external-link-container" class="p-4 p-md-5 text-center d-none border-bottom">
                        <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle p-3"
                            style="background:var(--blue-100);">
                            <i class="fa-solid fa-arrow-up-right-from-square fa-2x text-primary"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Tài liệu / Video tham khảo ngoài</h5>
                        <p class="text-muted small mb-4">Bài học này chứa một liên kết ngoài hệ thống.</p>
                        <a href="#" id="external-link-btn" target="_blank" rel="noopener noreferrer"
                            class="btn btn-primary rounded-pill px-4 fw-bold">
                            <i class="fa-solid fa-arrow-up-right-from-square me-2"></i>Truy cập ngay
                        </a>
                    </div>

                    {{-- ══ LESSON AREA ══ --}}
                    <div id="lesson-content-area" tabindex="-1">
                        <div class="lesson-current-head">
                            <div>
                                <span class="lesson-current-badge">Bài học hiện tại</span>
                                <h2 id="lesson-title" class="lesson-header-title">{{ $course->title }}</h2>
                                <div id="lesson-module-title" class="lesson-current-meta">
                                    {{ $course->modules->first()?->title ? 'Chương 1: ' . $course->modules->first()?->title : 'Chọn bài học trong nội dung khóa học' }}
                                </div>
                            </div>
                            <div id="lesson-duration-box" class="lesson-duration-box d-none">
                                <span>Thời lượng dự kiến</span>
                                <strong><i class="fa-regular fa-clock"></i> <span id="lesson-duration-text"></span></strong>
                            </div>
                        </div>

                        {{-- Attachment --}}
                        <div id="lesson-attachment-container" class="attachment-box d-none">
                            <div class="attachment-box__head">
                                <h6>
                                    <i class="fa-solid fa-paperclip"></i>
                                    Tài liệu đính kèm bài học
                                </h6>
                                <span class="badge bg-light text-dark border">1 file</span>
                            </div>
                            <div class="attachment-item">
                                <div class="attachment-icon file-doc">
                                    <i class="fa-solid fa-file-lines"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <strong id="lesson-attachment-name">filename.pdf</strong>
                                    <div class="small-text">Tài liệu bài học · Có thể tải về</div>
                                </div>
                                <div class="d-flex gap-2 attachment-actions">
                                    <a href="#" id="lesson-attachment-view-btn" target="_blank"
                                        class="btn btn-sm btn-secondary-action">
                                        <i class="fa-solid fa-eye me-1"></i>Xem
                                    </a>
                                    <a href="#" id="lesson-attachment-btn" download
                                        class="btn btn-sm btn-primary-action">
                                        <i class="fa-solid fa-download me-1"></i>Tải
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div id="lesson-material-container" class="lesson-material-box d-none">
                            <div class="lesson-material-head">
                                <h6>
                                    <i class="fa-solid fa-folder-open"></i>
                                    Học liệu liên quan
                                </h6>
                                <span id="lesson-material-count" class="badge bg-light text-dark border">0 mục</span>
                            </div>
                            <div id="lesson-material-list" class="lesson-material-list"></div>
                        </div>

                        <hr class="lesson-divider">
                        <div id="lesson-body" class="lesson-body">
                            <div class="course-intro-card">
                                <div class="course-description">{!! nl2br(e($course->description)) !!}</div>
                            </div>
                            <div id="welcome-placeholder" class="welcome-guide mt-4">
                                @if (auth()->user()->role === 'student')
                                    <h6 class="fw-bold mb-1" style="font-size:14px;"><i
                                            class="fa-solid fa-compass me-2 text-primary"></i>Bắt đầu từ đâu?</h6>
                                    <p class="text-muted small mb-0">Chọn một chương trong danh sách nội dung để mở lại bài cần xem.</p>
                                    <div class="welcome-guide-grid">
                                        <div class="welcome-guide-item">
                                            <div class="welcome-guide-icon" style="background:var(--blue-50);">
                                                <i class="fa-solid fa-play-circle" style="color:var(--blue-600);"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold" style="font-size:13px;">Bài học</div>
                                                <div class="text-muted" style="font-size:12px;line-height:1.5;">Đọc lại nội dung, xem video và tải tài liệu của từng bài.</div>
                                            </div>
                                        </div>
                                        <div class="welcome-guide-item">
                                            <div class="welcome-guide-icon" style="background:var(--amber-50);">
                                                <i class="fa-solid fa-file-signature" style="color:var(--amber-500);"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold" style="font-size:13px;">Bài tập</div>
                                                <div class="text-muted" style="font-size:12px;line-height:1.5;">Xem yêu
                                                    cầu, nộp file hoặc viết bài tự luận.</div>
                                            </div>
                                        </div>
                                        <div class="welcome-guide-item">
                                            <div class="welcome-guide-icon" style="background:var(--purple-50);">
                                                <i class="fa-solid fa-stopwatch" style="color:var(--purple-600);"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold" style="font-size:13px;">Bài kiểm tra</div>
                                                <div class="text-muted" style="font-size:12px;line-height:1.5;">Bấm bắt
                                                    đầu khi sẵn sàng vì hệ thống sẽ tính giờ.</div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <h6 class="fw-bold mb-2" style="font-size:14px;"><i
                                            class="fa-solid fa-pen-to-square me-2 text-primary"></i>Quản lý nội dung</h6>
                                    <div class="text-muted small">Chọn bài học, bài tập hoặc bài kiểm tra trong danh sách nội dung để
                                        xem nhanh. Dùng các nút thêm nội dung ở phần trên.</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div id="lesson-ai-toolbar" class="lesson-ai-toolbar">
                        <div class="lesson-ai-toolbar__intro">
                            <span class="lesson-ai-icon">
                                <i class="fa-solid fa-robot"></i>
                            </span>
                            <div>
                                <h6 class="lesson-ai-title">AI trợ giảng</h6>
                                <div class="lesson-ai-subtitle">Hỗ trợ theo bài đang mở.</div>
                            </div>
                        </div>
                        <div class="lesson-ai-actions">
                            <button type="button" class="lesson-ai-btn" data-ai-assist-mode="summary"
                                data-ai-prompt="Tóm tắt bài học này thành các ý chính dễ nhớ.">
                                <i class="fa-solid fa-align-left"></i>Tóm tắt
                            </button>
                            <button type="button" class="lesson-ai-btn" data-ai-assist-mode="explain"
                                data-ai-prompt="Giải thích lại bài học này theo cách dễ hiểu hơn, từng bước ngắn gọn.">
                                <i class="fa-solid fa-lightbulb"></i>Dễ hiểu hơn
                            </button>
                            <button type="button" class="lesson-ai-btn" data-ai-assist-mode="examples"
                                data-ai-prompt="Tạo ví dụ minh họa ngắn cho nội dung chính của bài học này.">
                                <i class="fa-solid fa-shapes"></i>Ví dụ
                            </button>
                            <button type="button" class="lesson-ai-btn" data-ai-assist-mode="review"
                                data-ai-prompt="Gợi ý cách ôn tập sau bài học này và vài câu hỏi tự kiểm tra.">
                                <i class="fa-solid fa-list-check"></i>Ôn tập
                            </button>
                        </div>
                    </div>

                    {{-- ══ ASSIGNMENT AREA ══ --}}
                    <div id="assignment-content-area" class="d-none flex-column" tabindex="-1">
                        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                            <h2 id="assignment-title" class="assignment-title">
                                <i class="fa-solid fa-list-check me-2" style="color:var(--amber-500);"></i>Tiêu đề bài tập
                            </h2>
                            <span id="assignment-badge" class="badge rounded-pill px-3 py-2 fs-6">Trạng thái</span>
                        </div>
                        <div class="mb-4">
                            <span class="due-badge">
                                <i class="fa-solid fa-clock"></i> Hạn nộp: <span id="assignment-due-date"></span>
                            </span>
                        </div>
                        <hr class="lesson-divider">
                        <h6 class="fw-bold mb-3" style="color:var(--gray-700);">
                            <i class="fa-solid fa-list-check me-2 text-primary"></i>Yêu cầu bài tập
                        </h6>
                        <div id="assignment-instructions" class="instructions-box mb-4"></div>

                        @if (auth()->user()->role === 'student')
                            <div id="student-submission-area">
                                <div id="submitted-info-area" class="d-none">
                                    <h6 class="fw-bold text-success mb-3">
                                        <i class="fa-solid fa-circle-check me-2"></i>Bài làm của bạn
                                    </h6>
                                    <div class="submitted-file-card mb-3">
                                        <div>
                                            <p class="mb-1 fw-bold" style="font-size:14px;">
                                                <i class="fa-solid fa-clock me-2 text-success"></i>Thông tin nộp bài
                                            </p>
                                            <p class="mb-0 text-muted" style="font-size:12px;">
                                                Đã nộp lúc: <span id="submitted-time-text" class="fw-medium"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <div id="submitted-file-card" class="submitted-file-card mb-3">
                                        <div>
                                            <p class="mb-1 fw-bold" style="font-size:14px;">
                                                <i class="fa-solid fa-file-lines me-2 text-primary"></i>Tài liệu đã tải lên
                                            </p>
                                            <p class="mb-0 text-muted" style="font-size:12px;">Mở file để xem chi tiết bài
                                                làm.</p>
                                        </div>
                                        <a href="#" id="submitted-file-link" target="_blank"
                                            class="btn btn-outline-primary btn-sm rounded-pill px-3 flex-shrink-0">
                                            <i class="fa-solid fa-eye me-1"></i> Xem file
                                        </a>
                                    </div>
                                    <div id="submitted-text-answer-card"
                                        class="submitted-file-card d-none mb-3 align-items-start">
                                        <div class="w-100">
                                            <p class="mb-2 fw-bold" style="font-size:14px;">
                                                <i class="fa-solid fa-align-left me-2 text-primary"></i>Bài tự luận đã nộp
                                            </p>
                                            <div id="submitted-text-answer-text" class="bg-light rounded-3 p-3 text-dark"
                                                style="font-size:14px;line-height:1.7;white-space:pre-wrap;"></div>
                                        </div>
                                    </div>
                                    <div id="grading-result" class="d-none mb-3 grading-result-box">
                                        <h6 class="fw-bold text-success mb-2">
                                            <i class="fa-solid fa-star me-2"></i>Điểm số:
                                            <span id="grade-score" class="text-dark fs-5"></span>/10
                                        </h6>
                                        <p class="mb-0 text-dark" style="font-size:14px;">
                                            <strong>Nhận xét:</strong> <span id="grade-feedback"></span>
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap" id="submission-actions">
                                        <button type="button" class="btn btn-primary rounded-pill px-4"
                                            id="btn-edit-submission">
                                            <i class="fa-solid fa-edit me-1"></i> Chỉnh sửa bài nộp
                                        </button>
                                        <form id="delete-submission-form" method="POST" class="m-0"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn hủy bài đã nộp?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger rounded-pill px-4">
                                                <i class="fa-solid fa-trash-can me-1"></i> Hủy bài nộp
                                            </button>
                                        </form>
                                    </div>
                                    <p id="graded-warning" class="text-danger small mt-2 d-none fst-italic">
                                        <i class="fa-solid fa-lock me-1"></i>Giáo viên đã chấm điểm, bạn không thể sửa hoặc xóa
                                        bài.
                                    </p>
                                </div>
                                <div id="upload-form-area" class="d-none submission-dropzone">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold text-primary mb-0">
                                            <i class="fa-solid fa-cloud-arrow-up me-2"></i>Nộp bài tập
                                        </h6>
                                        <button type="button" class="btn btn-sm btn-light d-none"
                                            id="btn-cancel-edit">Hủy sửa</button>
                                    </div>
                                    <form id="course-submit-assignment-form" method="POST" enctype="multipart/form-data"
                                        action="">
                                        @csrf
                                        <div id="essay-answer-field" class="mb-3 d-none">
                                            <label for="essay-answer-input" class="form-label small fw-bold text-muted">
                                                Nội dung bài tự luận
                                            </label>
                                            <textarea name="text_answer" id="essay-answer-input" class="form-control bg-white border-0 shadow-sm" rows="8"
                                                placeholder="Nhập bài làm tự luận của bạn..."></textarea>
                                        </div>
                                        <div class="d-flex flex-column flex-sm-row gap-2">
                                            <div id="file-upload-field" class="flex-grow-1">
                                                <input type="file" name="file" id="assignment-file-input"
                                                    class="form-control bg-white border-0 shadow-sm">
                                                <div class="form-text small">Chỉ cần chọn file với bài dạng nộp file.</div>
                                            </div>
                                            <button class="btn btn-warning text-dark px-4 fw-bold flex-shrink-0"
                                                type="submit">
                                                <i class="fa-solid fa-paper-plane me-1"></i>Gửi bài
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="text-center p-4 p-md-5 bg-white rounded-4 border">
                                <i class="fa-solid fa-users-gear fa-3x text-primary mb-3 d-block"></i>
                                <p class="text-muted mb-0">Bấm vào biểu tượng
                                    <i class="fa-solid fa-users-gear text-primary mx-1"></i>
                                    trong danh sách nội dung để chấm điểm bài tập này.
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- ══ QUIZ AREA ══ --}}
                    <div id="quiz-content-area" class="d-none flex-column align-items-center" tabindex="-1">
                        <div class="w-100" style="max-width:520px;">
                            <div class="text-center mb-4">
                                <div id="quiz-main-icon-wrap" class="quiz-main-icon-wrap mb-3">
                                    <i id="quiz-main-icon" class="fa-solid fa-stopwatch fa-2x"
                                        style="color:var(--purple-600);"></i>
                                </div>
                                <h2 id="quiz-display-title" class="quiz-display-title">Tiêu đề bài kiểm tra</h2>
                            </div>
                            <div class="d-flex gap-2 mb-4 flex-wrap">
                                <div class="quiz-stat-card">
                                    <div class="quiz-stat-label"><i class="fa-solid fa-clock me-1"></i> Thời gian</div>
                                    <div class="quiz-stat-value">
                                        <span id="quiz-display-duration">0</span>
                                        <small style="font-size:.8rem;font-weight:600;color:var(--gray-500);">phút</small>
                                    </div>
                                </div>
                                @if (auth()->user()->role === 'student')
                                    <div class="quiz-stat-card">
                                        <div class="quiz-stat-label"><i class="fa-solid fa-list-check me-1"></i> Trạng thái</div>
                                        <div><span id="quiz-status-text" class="quiz-status-value is-not-started">Chưa làm</span></div>
                                    </div>
                                    <div id="quiz-score-box" class="quiz-stat-card quiz-score-card d-none">
                                        <div class="quiz-stat-label text-success"><i class="fa-solid fa-star me-1"></i> Điểm số
                                        </div>
                                        <div class="quiz-stat-value text-success"><span id="quiz-score-text">0</span>/10
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if (auth()->user()->role === 'student')
                                <div id="quiz-student-action-area">
                                    <div class="quiz-notice mb-4">
                                        <h6 class="fw-bold mb-2" style="color:var(--amber-800);font-size:13px;">
                                            <i class="fa-solid fa-triangle-exclamation me-2"></i>Lưu ý quan trọng
                                        </h6>
                                        <ul class="mb-0 small text-dark ps-3">
                                            <li>Đồng hồ bắt đầu ngay khi bạn bấm nút.</li>
                                            <li>Hệ thống tự nộp bài khi hết thời gian.</li>
                                        </ul>
                                    </div>
                                    <a href="#" id="start-quiz-btn" class="btn btn-quiz-start w-100">
                                        BẮT ĐẦU LÀM BÀI <i class="fa-solid fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                                <div id="quiz-completed-msg" class="d-none">
                                    <div id="quiz-result-panel" class="quiz-result-panel is-submitted" role="status" aria-live="polite">
                                        <div class="quiz-result-icon"><i id="quiz-result-icon" class="fa-solid fa-circle-check"></i></div>
                                        <div class="quiz-result-copy">
                                            <span id="quiz-result-eyebrow" class="quiz-result-eyebrow">Đã ghi nhận bài làm</span>
                                            <h5 id="quiz-result-title" class="quiz-result-title">Đã nộp bài</h5>
                                            <p id="quiz-result-description" class="quiz-result-description">Bài kiểm tra đã được nộp thành công.</p>
                                        </div>
                                    </div>
                                    <a href="#" id="review-quiz-btn"
                                        class="btn quiz-review-button w-100 d-none">
                                        <i class="fa-solid fa-magnifying-glass-chart me-2"></i> Xem kết quả và bài làm
                                    </a>
                                </div>
                            @else
                                <div class="quiz-notice mb-4"
                                    style="background:var(--blue-50);border-color:var(--blue-100);">
                                    <h6 class="fw-bold mb-2 text-primary" style="font-size:13px;">
                                        <i class="fa-solid fa-circle-info me-2"></i>Khu vực Quản lý
                                    </h6>
                                    <p class="mb-0 small text-dark">Vào trang soạn thảo để thêm / sửa / xóa câu hỏi.</p>
                                </div>
                                <a href="#" id="manage-quiz-btn" class="btn btn-quiz-start w-100">
                                    <i class="fa-solid fa-cog me-2"></i> VÀO TRANG SOẠN CÂU HỎI
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- ══ FOOTER NAV ══ --}}
                    <div class="footer-nav d-none" id="nav-footer">
                        <button type="button" class="btn-footer-nav" id="btn-prev" disabled>
                            <i class="fa-solid fa-arrow-left"></i>Bài trước
                        </button>
                        <span class="footer-nav__hint"><i class="fa-regular fa-eye"></i> Nội dung dùng để xem lại sau buổi học</span>
                        <button type="button" class="btn-footer-nav" id="btn-next" disabled>
                            Bài tiếp <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>

                </div>{{-- /content-card --}}
            </div>

        </div>{{-- /row --}}
    </div>{{-- /page-wrapper --}}

    {{-- Mobile FAB --}}
    <button type="button" id="btn-open-sidebar" aria-label="Mở danh sách nội dung" aria-controls="mobile-sidebar-drawer"
        aria-expanded="false" title="Danh sách nội dung">
        <i class="fa-solid fa-list"></i>
    </button>

    @if ($isCourseManager)
        <div class="presentation-controls" id="presentation-controls" aria-label="Điều khiển trình chiếu">
            <button type="button" id="presentation-font-down" title="Giảm cỡ chữ" aria-label="Giảm cỡ chữ">
                <i class="fa-solid fa-minus"></i><span>A</span>
            </button>
            <button type="button" id="presentation-font-up" title="Tăng cỡ chữ" aria-label="Tăng cỡ chữ">
                <span>A</span><i class="fa-solid fa-plus"></i>
            </button>
            <button type="button" id="exit-presentation-btn" class="presentation-exit-btn">
                <i class="fa-solid fa-compress"></i> Thoát trình chiếu
            </button>
        </div>
    @endif

    @if ($isCourseManager)
        <style>
            .ai-plan-dialog { max-width: 1080px; }
            .ai-plan-intro { background: linear-gradient(135deg,#eff6ff,#f5f3ff); border:1px solid #dbeafe; border-radius:14px; color:#334155; padding:14px 16px; }
            .ai-plan-form-grid { display:grid; gap:14px; grid-template-columns:repeat(2,minmax(0,1fr)); }
            .ai-plan-span-2 { grid-column:1/-1; }
            .ai-plan-result { max-height:58vh; overflow:auto; padding-right:4px; }
            .ai-plan-module { background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; margin-bottom:14px; padding:14px; }
            .ai-plan-module-head { align-items:center; display:flex; gap:10px; margin-bottom:10px; }
            .ai-plan-module-title { font-weight:800; }
            .ai-plan-lesson { background:#fff; border:1px solid #e2e8f0; border-radius:12px; margin-top:10px; padding:12px; }
            .ai-plan-lesson-head { align-items:center; display:flex; gap:8px; margin-bottom:8px; }
            .ai-plan-lesson-content { border:1px solid #dbe2ea; border-radius:10px; color:#334155; line-height:1.55; min-height:120px; padding:12px; }
            .ai-plan-lesson-content:focus { border-color:#8b5cf6; box-shadow:0 0 0 3px rgba(139,92,246,.12); outline:0; }
            .ai-plan-remove { background:#fff1f2; border:0; border-radius:9px; color:#be123c; height:34px; width:34px; }
            @media(max-width:767px){ .ai-plan-form-grid{grid-template-columns:1fr}.ai-plan-span-2{grid-column:auto} }
        </style>
        <div class="modal fade" id="aiCoursePlanModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ai-plan-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title fw-bold"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i>AI thiết kế khóa học</h5>
                            <div class="small text-muted mt-1">AI tạo bản nháp, giáo viên duyệt trước khi đưa vào khóa học.</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div id="ai-plan-form-step">
                            <div class="ai-plan-intro mb-3"><strong>{{ $course->title }}</strong><br>Hãy cung cấp bối cảnh ngắn để kế hoạch sát với lớp học thực tế.</div>
                            <form id="ai-course-plan-form" class="ai-plan-form-grid">
                                <div><label class="form-label fw-semibold">Đối tượng học viên</label><input class="form-control" name="audience" placeholder="VD: Học viên trung cấp năm 1" required></div>
                                <div><label class="form-label fw-semibold">Trình độ hiện tại</label><input class="form-control" name="current_level" placeholder="VD: Đã biết HTML/CSS cơ bản" required></div>
                                <div><label class="form-label fw-semibold">Số buổi</label><input class="form-control" name="session_count" type="number" min="1" max="60" value="10" required></div>
                                <div><label class="form-label fw-semibold">Phút mỗi buổi</label><input class="form-control" name="minutes_per_session" type="number" min="30" max="480" value="135" required></div>
                                <div class="ai-plan-span-2"><label class="form-label fw-semibold">Mục tiêu đầu ra</label><textarea class="form-control" name="learning_outcomes" rows="3" placeholder="Sau khóa học, học viên có thể..." required></textarea></div>
                                <div class="ai-plan-span-2"><label class="form-label fw-semibold">Yêu cầu hoặc lưu ý thêm</label><textarea class="form-control" name="notes" rows="2" placeholder="Chủ đề bắt buộc, cách tổ chức lớp, loại bài tập mong muốn..."></textarea></div>
                            </form>
                        </div>
                        <div id="ai-plan-loading" class="text-center py-5 d-none"><div class="spinner-border text-primary"></div><h6 class="mt-3 mb-1">AI đang thiết kế chương trình...</h6><div class="small text-muted">Quá trình có thể mất một vài phút.</div></div>
                        <div id="ai-plan-review-step" class="d-none">
                            <div class="alert alert-info" id="ai-plan-summary"></div>
                            <div class="small text-muted mb-3"><i class="fa-solid fa-pen me-1"></i>Có thể sửa trực tiếp tên chương, tên bài và nội dung trước khi áp dụng.</div>
                            <div id="ai-plan-result" class="ai-plan-result"></div>
                        </div>
                        <div id="ai-plan-error" class="alert alert-danger d-none mt-3"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-outline-primary d-none" id="ai-plan-back-btn"><i class="fa-solid fa-arrow-left me-1"></i>Điều chỉnh yêu cầu</button>
                        <button type="button" class="btn btn-primary" id="ai-plan-generate-btn"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Tạo bản nháp</button>
                        <button type="button" class="btn btn-success d-none" id="ai-plan-apply-btn"><i class="fa-solid fa-check me-1"></i>Áp dụng vào khóa học</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @include('courses.partials.modals')
    @include('courses.partials.scripts')

    @include('courses.partials.show-page-scripts')
@endsection
