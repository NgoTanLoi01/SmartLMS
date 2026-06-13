@extends('layouts.app')

@section('title', 'Danh sách khóa học')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        /* ─── Tokens ─────────────────────────────────────── */
        :root {
            --brand: #2563eb;
            --brand-light: #eff6ff;
            --brand-border: #bfdbfe;
            --indigo: #4f46e5;
            --indigo-light: #eef2ff;
            --indigo-border: #c7d2fe;
            --surface: #ffffff;
            --surface-2: #f8fafc;
            --border: #e2e8f0;
            --border-hover: #bfdbfe;
            --text: #0f172a;
            --text-2: #334155;
            --muted: #64748b;
            --subtle: #94a3b8;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --radius-card: 16px;
            --radius-btn: 10px;
            --shadow-card: 0 1px 3px rgba(0, 0, 0, .06), 0 1px 2px rgba(0, 0, 0, .04);
            --shadow-hover: 0 12px 32px rgba(37, 99, 235, .10), 0 2px 8px rgba(37, 99, 235, .06);
        }

        /* ─── Layout ─────────────────────────────────────── */
        .courses-page {
            padding-bottom: 48px;
        }

        /* ─── Page header ─────────────────────────────────── */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-eyebrow {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--brand);
            margin-bottom: 6px;
        }

        .page-eyebrow-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--brand);
            animation: pulse-dot 2.2s ease-in-out infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: .5;
                transform: scale(.7);
            }
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
            margin: 0 0 4px;
            letter-spacing: -.3px;
        }

        .page-subtitle {
            font-size: 13.5px;
            color: var(--muted);
            margin: 0;
        }

        /* ─── Create button ───────────────────────────────── */
        .btn-create {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--brand);
            color: #fff;
            border: none;
            border-radius: var(--radius-btn);
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            white-space: nowrap;
            transition: background .15s, box-shadow .15s, transform .12s;
            box-shadow: 0 1px 3px rgba(37, 99, 235, .25), 0 4px 12px rgba(37, 99, 235, .15);
            flex-shrink: 0;
        }

        .btn-create:hover {
            background: #1d4ed8;
            color: #fff;
            box-shadow: 0 2px 6px rgba(37, 99, 235, .3), 0 6px 16px rgba(37, 99, 235, .2);
            transform: translateY(-1px);
        }

        .btn-create:active {
            transform: translateY(0);
        }

        .btn-create i {
            font-size: 11px;
        }

        /* ─── Filters ────────────────────────────────────── */
        .course-filters {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 28px;
            box-shadow: var(--shadow-card);
        }

        .course-filter-grid {
            display: grid;
            grid-template-columns: minmax(220px, 1.5fr) repeat(4, minmax(150px, 1fr)) auto;
            gap: 10px;
            align-items: end;
        }

        .course-filter-grid-student {
            grid-template-columns: minmax(220px, 1.5fr) repeat(2, minmax(150px, 1fr)) auto;
        }

        .course-filter-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .course-filter-control {
            min-height: 38px;
            border-radius: 10px;
            border-color: var(--border);
            font-size: 13px;
        }

        .course-filter-actions {
            display: flex;
            gap: 8px;
        }

        .course-filter-actions .btn {
            min-height: 38px;
            border-radius: 10px;
            white-space: nowrap;
        }

        /* ─── Section ─────────────────────────────────────── */
        .course-section {
            margin-bottom: 40px;
        }

        .course-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .section-title-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-icon {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: var(--brand-light);
            color: var(--brand);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }

        .section-icon-indigo {
            background: var(--indigo-light);
            color: var(--indigo);
        }

        .course-section-title {
            margin: 0 0 2px;
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -.1px;
        }

        .course-section-subtitle {
            margin: 0;
            font-size: 12px;
            color: var(--muted);
        }

        .course-section-count {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 999px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            white-space: nowrap;
        }

        /* ─── Grid ────────────────────────────────────────── */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }

        /* ─── Card ────────────────────────────────────────── */
        .course-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-card);
            overflow: visible;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-card);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
            cursor: default;
        }

        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
            border-color: var(--border-hover);
        }

        .course-card-template {
            border-style: dashed;
            border-color: var(--indigo-border);
        }

        .course-card-template:hover {
            border-color: var(--indigo);
            box-shadow: 0 12px 32px rgba(79, 70, 229, .10), 0 2px 8px rgba(79, 70, 229, .06);
        }

        /* ─── Thumb ───────────────────────────────────────── */
        .card-thumb {
            height: 112px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 60%, #e0e7ff 100%);
            border-radius: var(--radius-card) var(--radius-card) 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: visible;
        }

        .card-thumb::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 70% 30%, rgba(37, 99, 235, .08) 0%, transparent 65%);
        }

        .card-thumb-template {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 60%, #ede9fe 100%);
        }

        .card-thumb-template::before {
            background: radial-gradient(ellipse at 70% 30%, rgba(79, 70, 229, .08) 0%, transparent 65%);
        }

        .thumb-icon {
            font-size: 2rem;
            color: #93c5fd;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 2px 6px rgba(37, 99, 235, .15));
        }

        .thumb-icon-indigo {
            color: #a5b4fc;
            filter: drop-shadow(0 2px 6px rgba(79, 70, 229, .15));
        }

        /* ─── Dropdown ────────────────────────────────────── */
        .dropdown {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }

        .card-menu-btn {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: rgba(255, 255, 255, .88);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(226, 232, 240, .8);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 13px;
            color: var(--muted);
            transition: background .15s, color .15s, box-shadow .15s;
            padding: 0;
        }

        .card-menu-btn:hover {
            background: #fff;
            color: var(--brand);
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
        }

        .card-dropdown {
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .1), 0 2px 8px rgba(0, 0, 0, .06);
            padding: 6px;
            min-width: 180px;
        }

        .card-dropdown .dropdown-item {
            border-radius: 8px;
            font-size: 13.5px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text);
        }

        .card-dropdown .dropdown-item i {
            width: 14px;
            text-align: center;
            color: var(--muted);
        }

        .card-dropdown .dropdown-item:hover {
            background: var(--brand-light);
            color: var(--brand);
        }

        .card-dropdown .dropdown-item:hover i {
            color: var(--brand);
        }

        .card-dropdown .dropdown-item.text-danger:hover {
            background: var(--danger-light);
            color: var(--danger);
        }

        .card-dropdown .dropdown-item.text-danger:hover i {
            color: var(--danger);
        }

        .card-dropdown .dropdown-divider {
            border-color: #f1f5f9;
            margin: 4px 0;
        }

        /* ─── Card body ───────────────────────────────────── */
        .card-body-inner {
            padding: 16px 18px 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 0;
        }

        /* Badge */
        .card-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--brand-light);
            color: var(--brand);
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            max-width: 100%;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            letter-spacing: .02em;
        }

        .card-badge-template {
            background: var(--indigo-light);
            color: var(--indigo);
        }

        /* Title */
        .card-title {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.45;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 42px;
            letter-spacing: -.1px;
        }

        /* Desc */
        .card-desc {
            font-size: 12.5px;
            color: var(--muted);
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 14px;
        }

        /* Teacher */
        .teacher-row {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 10px;
        }

        .teacher-row img {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 1.5px solid var(--border);
        }

        .teacher-row span {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-2);
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        /* Stats */
        .stats-row {
            display: flex;
            gap: 14px;
            font-size: 11.5px;
            color: var(--subtle);
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .stats-row span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stats-row i {
            font-size: 10.5px;
        }

        /* Divider */
        .card-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 0 0 14px;
        }

        /* Progress */
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .progress-label span:first-child {
            color: var(--subtle);
        }

        .progress-label span:last-child {
            font-weight: 700;
            color: var(--brand);
        }

        .progress-bar-wrap {
            height: 4px;
            background: #e8edf3;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 14px;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--brand), #60a5fa);
            border-radius: 4px;
            transition: width .6s cubic-bezier(.4, 0, .2, 1);
        }

        /* Updated at */
        .updated-at {
            font-size: 11px;
            color: var(--subtle);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Enter button */
        .btn-enter {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: var(--brand-light);
            color: var(--brand);
            border: 1px solid var(--brand-border);
            border-radius: var(--radius-btn);
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background .15s, border-color .15s, color .15s, box-shadow .15s;
            margin-top: auto;
        }

        .btn-enter:hover {
            background: var(--brand);
            color: #fff;
            border-color: var(--brand);
            box-shadow: 0 4px 12px rgba(37, 99, 235, .2);
        }

        .btn-enter-template {
            background: var(--indigo-light);
            color: var(--indigo);
            border-color: var(--indigo-border);
        }

        .btn-enter-template:hover {
            background: var(--indigo);
            border-color: var(--indigo);
            color: #fff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, .2);
        }

        .btn-enter i {
            font-size: 11px;
            transition: transform .15s;
        }

        .btn-enter:hover i {
            transform: translateX(2px);
        }

        /* ─── Empty state ─────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-icon-wrap {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: var(--brand-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .empty-icon-wrap i {
            font-size: 2rem;
            color: var(--brand);
            opacity: .6;
        }

        .empty-state h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin: 0 0 6px;
        }

        .empty-state p {
            font-size: 13.5px;
            color: var(--muted);
            margin: 0;
        }

        /* ─── Responsive ──────────────────────────────────── */
        @media (max-width: 767.98px) {
            .page-header {
                align-items: stretch;
                flex-direction: column;
            }

            .btn-create {
                justify-content: center;
                width: 100%;
            }

            .courses-grid {
                grid-template-columns: 1fr;
            }

            .stats-row {
                gap: 8px 12px;
            }

            .course-section-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .course-filter-grid {
                grid-template-columns: 1fr;
            }

            .course-filter-actions {
                flex-direction: column;
            }
        }
    </style>

    <div class="courses-page">
        {{-- Page Header --}}
        <div class="page-header">
            <div>
                <div class="page-eyebrow">
                    <span class="page-eyebrow-dot"></span>
                    SmartLMS
                </div>
                <h1 class="page-title">
                    {{ auth()->user()->role === 'student' ? 'Khóa học của em' : 'Khóa học của tôi' }}
                </h1>
                <p class="page-subtitle">
                    {{ auth()->user()->role === 'student'
                        ? 'Tiếp tục học các khóa đang tham gia và theo dõi tiến độ của em.'
                        : 'Quản lý khóa đang dạy, khóa mẫu và nội dung học tập trên SmartLMS.' }}
                </p>
            </div>
            @if (auth()->user()->role === 'teacher' || auth()->user()->role === 'admin')
                <a href="{{ route('courses.create') }}" class="btn-create">
                    <i class="fas fa-plus"></i> Tạo khóa học mới
                </a>
            @endif
        </div>

        @if (auth()->user()->role !== 'student')
            <form action="{{ route('courses.index') }}" method="GET" class="course-filters">
                <div class="course-filter-grid">
                    <div>
                        <label class="course-filter-label">Tìm kiếm</label>
                        <input type="text" name="search" class="form-control course-filter-control"
                            placeholder="Tên hoặc mô tả khóa học..." value="{{ $filters['search'] ?? '' }}">
                    </div>

                    <div>
                        <label class="course-filter-label">Chương trình</label>
                        <select name="program_id" class="form-select course-filter-control">
                            <option value="">Tất cả</option>
                            @foreach ($filterPrograms as $program)
                                <option value="{{ $program->id }}" @selected(($filters['program_id'] ?? '') == $program->id)>
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="course-filter-label">Loại khóa</label>
                        <select name="course_type" class="form-select course-filter-control">
                            <option value="">Tất cả</option>
                            <option value="delivery" @selected(($filters['course_type'] ?? '') === 'delivery')>Đang dạy</option>
                            <option value="template" @selected(($filters['course_type'] ?? '') === 'template')>Khóa mẫu</option>
                        </select>
                    </div>

                    <div>
                        <label class="course-filter-label">Trạng thái</label>
                        <select name="status" class="form-select course-filter-control">
                            <option value="">Tất cả</option>
                            <option value="published" @selected(($filters['status'] ?? '') === 'published')>Published</option>
                            <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
                            <option value="hidden" @selected(($filters['status'] ?? '') === 'hidden')>Hidden</option>
                            <option value="archived" @selected(($filters['status'] ?? '') === 'archived')>Archived</option>
                        </select>
                    </div>

                    <div>
                        <label class="course-filter-label">Lớp</label>
                        <select name="class_id" class="form-select course-filter-control">
                            <option value="">Tất cả</option>
                            @foreach ($filterClasses as $classroom)
                                <option value="{{ $classroom->id }}" @selected(($filters['class_id'] ?? '') == $classroom->id)>
                                    {{ $classroom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="course-filter-actions">
                        <button type="submit" class="btn btn-primary px-3">
                            <i class="fas fa-filter me-1"></i>Lọc
                        </button>
                        <a href="{{ route('courses.index') }}" class="btn btn-light px-3">
                            <i class="fas fa-rotate-left"></i>
                        </a>
                    </div>
                </div>
            </form>
        @endif

        {{-- Content --}}
        @if ($courses->isEmpty())
            <div class="empty-state">
                <div class="empty-icon-wrap">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3>Chưa có khóa học nào</h3>
                <p>Hãy tạo hoặc tham gia khóa học đầu tiên để bắt đầu.</p>
            </div>
        @else
            {{-- Delivery courses --}}
            @if ($deliveryCourses->isNotEmpty())
                <section class="course-section">
                    <div class="course-section-header">
                        <div class="section-title-group">
                            <span class="section-icon">
                                <i
                                    class="fas fa-{{ auth()->user()->role === 'student' ? 'book-open' : 'chalkboard-teacher' }}"></i>
                            </span>
                            <div>
                                <h2 class="course-section-title">
                                    {{ auth()->user()->role === 'student' ? 'Khóa đang học' : 'Khóa đang dạy' }}
                                </h2>
                                <p class="course-section-subtitle">
                                    {{ auth()->user()->role === 'student'
                                        ? 'Các khóa em đang tham gia trong lớp học của mình.'
                                        : 'Các khóa triển khai cho lớp thật, có học sinh và tiến độ học tập.' }}
                                </p>
                            </div>
                        </div>
                        <span class="course-section-count">{{ $deliveryCourses->count() }} khóa</span>
                    </div>
                    <div class="courses-grid">
                        @foreach ($deliveryCourses as $course)
                            @include('courses.partials.course-card', ['course' => $course])
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Template courses --}}
            @if (auth()->user()->role !== 'student' && $templateCourses->isNotEmpty())
                <section class="course-section">
                    <div class="course-section-header">
                        <div class="section-title-group">
                            <span class="section-icon section-icon-indigo">
                                <i class="fas fa-layer-group"></i>
                            </span>
                            <div>
                                <h2 class="course-section-title">Khóa mẫu</h2>
                                <p class="course-section-subtitle">
                                    Nội dung chuẩn dùng để tạo nhanh khóa mới, không tính học sinh hay tiến độ.
                                </p>
                            </div>
                        </div>
                        <span class="course-section-count">{{ $templateCourses->count() }} mẫu</span>
                    </div>
                    <div class="courses-grid">
                        @foreach ($templateCourses as $course)
                            @include('courses.partials.course-card', ['course' => $course])
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
@endsection
