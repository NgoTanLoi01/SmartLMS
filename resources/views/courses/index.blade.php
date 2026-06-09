@extends('layouts.app')

@section('title', 'Danh sách khóa học')

@section('content')
    <style>
        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 28px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--text);
            margin: 0 0 4px;
            line-height: 1.3;
        }

        .page-subtitle {
            font-size: 13.5px;
            color: var(--muted);
            margin: 0;
        }

        .btn-create {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--blue);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 9px 18px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Be Vietnam Pro', sans-serif;
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.15s;
            flex-shrink: 0;
        }

        .btn-create:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .btn-create i {
            font-size: 12px;
        }

        /* Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 18px;
        }

        .course-section {
            margin-bottom: 32px;
        }

        .course-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .course-section-title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }

        .course-section-subtitle {
            margin: 2px 0 0;
            font-size: 12px;
            color: #64748b;
        }

        .course-section-count {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            white-space: nowrap;
        }

        /* Card */
        .course-card {
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .course-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.08);
            border-color: #bfdbfe;
        }

        .course-card-template {
            border-style: dashed;
            border-color: #c7d2fe;
        }

        /* Thumb */
        .card-thumb {
            height: 120px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border-bottom: 1px solid #e8edf3;
        }

        .card-thumb .thumb-icon {
            font-size: 2.2rem;
            color: #93c5fd;
        }

        /* Action menu */
        .dropdown {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .card-menu-btn {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 13px;
            color: #64748b;
            transition: background 0.15s, color 0.15s;
            padding: 0;
        }

        .card-menu-btn:hover {
            background: #fff;
            color: #2563eb;
        }

        .card-dropdown {
            border: 1px solid #e8edf3;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
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
            color: #0f172a;
        }

        .card-dropdown .dropdown-item:hover {
            background: #eff6ff;
            color: #2563eb;
        }

        .card-dropdown .dropdown-item.text-danger:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        .card-dropdown .dropdown-divider {
            border-color: #f1f5f9;
            margin: 4px 0;
        }

        /* Card body */
        .card-body-inner {
            padding: 16px 18px 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .card-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 11px;
            font-weight: 500;
            padding: 3px 9px;
            border-radius: 6px;
            margin-bottom: 9px;
            max-width: 100%;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .card-badge-template {
            background: #eef2ff;
            color: #4f46e5;
        }

        .card-title {
            font-size: 14.5px;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.45;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 42px;
        }

        .card-desc {
            font-size: 12.5px;
            color: #64748b;
            line-height: 1.55;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 12px;
        }

        /* Teacher row */
        .teacher-row {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 9px;
        }

        .teacher-row img {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .teacher-row span {
            font-size: 12.5px;
            font-weight: 500;
            color: #334155;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        /* Stats */
        .stats-row {
            display: flex;
            gap: 12px;
            font-size: 11.5px;
            color: #94a3b8;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .stats-row span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stats-row i {
            font-size: 11px;
        }

        /* Divider */
        .card-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 0 0 12px;
        }

        /* Progress */
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 5px;
            color: #94a3b8;
        }

        .progress-label span:last-child {
            font-weight: 600;
            color: #2563eb;
        }

        .progress-bar-wrap {
            height: 4px;
            background: #e8edf3;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .progress-bar-fill {
            height: 100%;
            background: #2563eb;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        /* Updated at */
        .updated-at {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 12px;
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
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
            margin-top: auto;
        }

        .btn-enter:hover {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }

        .btn-enter-template {
            background: #eef2ff;
            color: #4f46e5;
            border-color: #c7d2fe;
        }

        .btn-enter-template:hover {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #fff;
        }

        .btn-enter i {
            font-size: 11px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            display: block;
            opacity: .4;
        }

        .empty-state p {
            font-size: 15px;
            margin: 0;
        }

        @media (max-width: 767.98px) {
            .page-header {
                align-items: stretch;
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
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">Khóa học của tôi</h1>
            <p class="page-subtitle">Học tập và phát triển kỹ năng mỗi ngày cùng SmartLMS</p>
        </div>
        @if (auth()->user()->role === 'teacher' || auth()->user()->role === 'admin')
            <a href="{{ route('courses.create') }}" class="btn-create">
                <i class="fas fa-plus"></i> Tạo khóa học mới
            </a>
        @endif
    </div>

    @if ($courses->isEmpty())
        <div class="empty-state">
            <i class="fas fa-graduation-cap"></i>
            <p>Chưa có khóa học nào. Hãy tạo hoặc tham gia khóa học đầu tiên!</p>
        </div>
    @else
        @if ($deliveryCourses->isNotEmpty())
            <section class="course-section">
                <div class="course-section-header">
                    <div>
                        <h2 class="course-section-title">Khóa đang dạy</h2>
                        <p class="course-section-subtitle">Các khóa triển khai cho lớp thật, có học sinh và tiến độ học tập.
                        </p>
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

        @if (auth()->user()->role !== 'student' && $templateCourses->isNotEmpty())
            <section class="course-section">
                <div class="course-section-header">
                    <div>
                        <h2 class="course-section-title">Khóa mẫu</h2>
                        <p class="course-section-subtitle">Nội dung chuẩn dùng để tạo nhanh khóa mới, không tính học sinh
                            hay tiến độ.</p>
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
@endsection
