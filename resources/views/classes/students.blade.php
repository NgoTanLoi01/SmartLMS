@extends('layouts.app')

@section('title', 'Học viên - ' . $classroom->name)

@push('styles')
    <style>
        :root {
            --lms-blue: #1D4ED8;
            --lms-blue-light: #EFF6FF;
            --lms-blue-mid: #BFDBFE;
            --lms-surface: #F8FAFC;
            --lms-border: #E2E8F0;
            --lms-text: #0F172A;
            --lms-muted: #64748B;
            --lms-danger: #DC2626;
            --lms-danger-light: #FEF2F2;
            --lms-warning: #D97706;
            --lms-warning-light: #FFFBEB;
            --lms-success: #059669;
            --lms-success-light: #ECFDF5;
            --lms-radius: 10px;
            --lms-radius-sm: 6px;
        }

        body {
            background: #F1F5F9;
        }

        /* ── Layout ── */
        .lms-page {
            padding: 2rem 2rem 3rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ── Breadcrumb ── */
        .lms-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--lms-muted);
            margin-bottom: 6px;
        }

        .lms-breadcrumb a {
            color: var(--lms-muted);
            text-decoration: none;
        }

        .lms-breadcrumb a:hover {
            color: var(--lms-blue);
        }

        .lms-breadcrumb-sep {
            font-size: 10px;
            color: #CBD5E1;
        }

        /* ── Page header ── */
        .lms-page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.75rem;
            flex-wrap: wrap;
        }

        .lms-page-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--lms-text);
            margin: 0 0 4px;
            letter-spacing: -0.3px;
        }

        .lms-page-meta {
            font-size: 13px;
            color: var(--lms-muted);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .lms-page-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .lms-page-meta i {
            font-size: 13px;
        }

        /* ── Buttons ── */
        .lms-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: var(--lms-radius-sm);
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            border: 1px solid transparent;
            text-decoration: none;
            white-space: nowrap;
        }

        .lms-btn-primary {
            background: var(--lms-blue);
            color: #fff;
            border-color: var(--lms-blue);
        }

        .lms-btn-primary:hover {
            background: #1e40af;
            color: #fff;
        }

        .lms-btn-outline {
            background: #fff;
            color: var(--lms-text);
            border-color: var(--lms-border);
        }

        .lms-btn-outline:hover {
            background: var(--lms-surface);
            border-color: #94A3B8;
            color: var(--lms-text);
        }

        .lms-btn-success {
            background: #fff;
            color: #059669;
            border-color: #A7F3D0;
        }

        .lms-btn-success:hover {
            background: #ECFDF5;
            border-color: #6EE7B7;
        }

        .lms-btn-sm {
            padding: 5px 11px;
            font-size: 12.5px;
        }

        .lms-btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* ── Stat cards ── */
        .lms-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 1.75rem;
        }

        .lms-stat {
            background: #fff;
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius);
            padding: 18px 20px;
            position: relative;
            overflow: hidden;
        }

        .lms-stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--lms-blue);
            border-radius: var(--lms-radius) var(--lms-radius) 0 0;
        }

        .lms-stat.danger::before {
            background: var(--lms-danger);
        }

        .lms-stat.warning::before {
            background: var(--lms-warning);
        }

        .lms-stat.info::before {
            background: #0EA5E9;
        }

        .lms-stat-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--lms-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .lms-stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--lms-text);
            line-height: 1;
            margin-bottom: 5px;
        }

        .lms-stat.danger .lms-stat-value {
            color: var(--lms-danger);
        }

        .lms-stat.warning .lms-stat-value {
            color: var(--lms-warning);
        }

        .lms-stat.info .lms-stat-value {
            color: #0369A1;
        }

        .lms-stat-sub {
            font-size: 12px;
            color: var(--lms-muted);
        }

        /* ── Card ── */
        .lms-card {
            background: #fff;
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius);
            overflow: hidden;
        }

        .lms-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--lms-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .lms-card-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--lms-text);
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .lms-card-title i {
            color: var(--lms-blue);
            font-size: 15px;
        }

        /* ── Filter bar ── */
        .lms-filter {
            padding: 16px 20px;
            border-bottom: 1px solid var(--lms-border);
            background: var(--lms-surface);
            display: flex;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .lms-filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .lms-filter-group label {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--lms-muted);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .lms-input {
            height: 36px;
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius-sm);
            padding: 0 12px;
            font-size: 13.5px;
            background: #fff;
            color: var(--lms-text);
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .lms-input:focus {
            outline: none;
            border-color: var(--lms-blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }

        .lms-input-icon {
            position: relative;
        }

        .lms-input-icon i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            font-size: 14px;
        }

        .lms-input-icon input {
            padding-left: 32px;
        }

        .lms-select {
            height: 36px;
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius-sm);
            padding: 0 32px 0 12px;
            font-size: 13.5px;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 10px center;
            appearance: none;
            color: var(--lms-text);
            cursor: pointer;
            transition: border-color 0.15s;
        }

        .lms-select:focus {
            outline: none;
            border-color: var(--lms-blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }

        .lms-filter-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .lms-btn-reset {
            height: 36px;
            width: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius-sm);
            background: #fff;
            color: var(--lms-muted);
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
        }

        .lms-btn-reset:hover {
            background: var(--lms-surface);
            border-color: #94A3B8;
            color: var(--lms-text);
        }

        /* ── Table ── */
        .lms-table-wrap {
            overflow-x: auto;
        }

        .lms-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .lms-table thead th {
            padding: 11px 16px;
            background: var(--lms-surface);
            font-size: 11px;
            font-weight: 700;
            color: var(--lms-muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid var(--lms-border);
            white-space: nowrap;
        }

        .lms-table tbody tr {
            border-bottom: 1px solid #F1F5F9;
            transition: background 0.12s;
        }

        .lms-table tbody tr:last-child {
            border-bottom: none;
        }

        .lms-table tbody tr:hover {
            background: #F8FAFC;
        }

        .lms-table td {
            padding: 14px 16px;
            vertical-align: middle;
        }

        /* ── Avatar ── */
        .lms-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--lms-blue-light);
            color: var(--lms-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        /* ── Student info ── */
        .lms-student-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--lms-text);
            margin-bottom: 1px;
        }

        .lms-student-email {
            font-size: 12px;
            color: var(--lms-muted);
        }

        .lms-student-id {
            font-size: 11px;
            color: #94A3B8;
        }

        /* ── Badges ── */
        .lms-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 100px;
            font-size: 11.5px;
            font-weight: 600;
        }

        .lms-badge-danger {
            background: var(--lms-danger-light);
            color: var(--lms-danger);
        }

        .lms-badge-success {
            background: var(--lms-success-light);
            color: var(--lms-success);
        }

        .lms-badge-warning {
            background: var(--lms-warning-light);
            color: var(--lms-warning);
        }

        .lms-badge-info {
            background: #F0F9FF;
            color: #0369A1;
        }

        .lms-badge-gray {
            background: var(--lms-surface);
            color: var(--lms-muted);
            border: 1px solid var(--lms-border);
        }

        /* ── Alerts list ── */
        .lms-alerts {
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .lms-alert-item {
            font-size: 11.5px;
            display: flex;
            align-items: flex-start;
            gap: 5px;
            color: var(--lms-muted);
        }

        .lms-alert-item.danger {
            color: var(--lms-danger);
        }

        .lms-alert-item.warning {
            color: var(--lms-warning);
        }

        .lms-alert-item i {
            font-size: 11px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        /* ── Mini stat ── */
        .lms-mini-stat {
            font-size: 13.5px;
        }

        .lms-mini-val {
            font-weight: 600;
            color: var(--lms-text);
        }

        .lms-mini-sub {
            font-size: 12px;
            color: var(--lms-muted);
            margin-top: 2px;
        }

        /* ── Progress micro ── */
        .lms-micro-bar {
            height: 4px;
            background: #E2E8F0;
            border-radius: 2px;
            margin-top: 6px;
            overflow: hidden;
        }

        .lms-micro-fill {
            height: 100%;
            border-radius: 2px;
            background: var(--lms-blue);
        }

        .lms-micro-fill.success {
            background: var(--lms-success);
        }

        /* ── Action buttons ── */
        .lms-row-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .lms-action-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--lms-blue);
            text-decoration: none;
            padding: 5px 10px;
            border-radius: var(--lms-radius-sm);
            transition: background 0.12s;
        }

        .lms-action-link:hover {
            background: var(--lms-blue-light);
            color: var(--lms-blue);
        }

        .lms-action-link.danger {
            color: var(--lms-danger);
        }

        .lms-action-link.danger:hover {
            background: var(--lms-danger-light);
        }

        /* ── Empty state ── */
        .lms-empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--lms-muted);
        }

        .lms-empty-icon {
            font-size: 40px;
            color: #CBD5E1;
            margin-bottom: 14px;
            display: block;
        }

        .lms-empty h6 {
            font-size: 15px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 6px;
        }

        .lms-empty p {
            font-size: 13px;
        }

        /* ── Alert flash ── */
        .lms-flash {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 16px;
            border-radius: var(--lms-radius);
            font-size: 13.5px;
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }

        .lms-flash.success {
            background: var(--lms-success-light);
            border-color: #A7F3D0;
            color: #065F46;
        }

        .lms-flash.error {
            background: var(--lms-danger-light);
            border-color: #FECACA;
            color: #991B1B;
        }

        .lms-flash i {
            font-size: 16px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .lms-flash-close {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            color: inherit;
            opacity: 0.6;
            font-size: 16px;
            padding: 0;
            line-height: 1;
        }

        .lms-flash-close:hover {
            opacity: 1;
        }

        /* ── Modal ── */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
        }

        .modal-header {
            padding: 20px 24px 0;
            border: none;
        }

        .modal-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--lms-text);
        }

        .modal-body {
            padding: 20px 24px;
        }

        .modal-footer {
            padding: 0 24px 20px;
            border: none;
            gap: 8px;
        }

        .lms-form-group {
            margin-bottom: 16px;
        }

        .lms-form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--lms-muted);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 6px;
        }

        .lms-form-control {
            width: 100%;
            height: 40px;
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius-sm);
            padding: 0 13px;
            font-size: 14px;
            color: var(--lms-text);
            background: var(--lms-surface);
            transition: border-color 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
        }

        .lms-form-control:focus {
            outline: none;
            border-color: var(--lms-blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
            background: #fff;
        }

        .lms-info-box {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: var(--lms-radius-sm);
            padding: 12px 14px;
            font-size: 13px;
            color: #1E40AF;
            margin-bottom: 18px;
            display: flex;
            gap: 8px;
        }

        .lms-warning-box {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            border-radius: var(--lms-radius-sm);
            padding: 14px 16px;
            font-size: 13px;
            color: #92400E;
        }

        .lms-warning-box ul {
            margin: 8px 0 8px 16px;
            padding: 0;
        }

        .lms-warning-box li {
            margin-bottom: 4px;
        }

        .lms-warning-box a {
            color: #065F46;
            font-weight: 600;
        }

        /* ── Count badge ── */
        .lms-count {
            background: var(--lms-surface);
            border: 1px solid var(--lms-border);
            border-radius: 100px;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 600;
            color: var(--lms-muted);
        }

        /* ── Hover reveal on desktop ── */
        @media (hover: hover) {
            .lms-row-btn-delete {
                opacity: 0;
            }

            .lms-table tbody tr:hover .lms-row-btn-delete {
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .lms-page {
                padding: 1rem 1rem 2rem;
            }

            .lms-stats {
                grid-template-columns: 1fr 1fr;
            }

            .lms-filter {
                flex-direction: column;
            }

            .lms-filter-group,
            .lms-filter-group input,
            .lms-filter-group select {
                width: 100%;
            }

            .lms-btn-group {
                flex-direction: column;
                width: 100%;
            }

            .lms-btn-group .lms-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .lms-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="lms-page">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="lms-flash success" role="alert">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
                <button class="lms-flash-close" onclick="this.closest('.lms-flash').remove()">×</button>
            </div>
        @endif
        @if (session('error'))
            <div class="lms-flash error" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
                <button class="lms-flash-close" onclick="this.closest('.lms-flash').remove()">×</button>
            </div>
        @endif
        @if ($errors->any())
            <div class="lms-flash error" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <ul style="margin:0; padding-left:16px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button class="lms-flash-close" onclick="this.closest('.lms-flash').remove()">×</button>
            </div>
        @endif

        {{-- Page header --}}
        <div class="lms-page-header">
            <div>
                <nav class="lms-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('classes.index') }}">Lớp học</a>
                    <span class="lms-breadcrumb-sep">›</span>
                    <span>{{ $classroom->code }}</span>
                </nav>
                <h1 class="lms-page-title">{{ $classroom->name }}</h1>
                <div class="lms-page-meta">
                    <span><i class="fas fa-chalkboard-teacher"></i> {{ $classroom->teacher->name }}</span>
                    <span><i class="fas fa-users"></i> {{ $classroom->students->count() }} học sinh</span>
                </div>
            </div>

            @if (auth()->user()->role === 'admin' || auth()->id() === $classroom->teacher_id)
                <div class="lms-btn-group">
                    <a href="{{ route('classes.progress', $classroom->id) }}" class="lms-btn lms-btn-outline">
                        <i class="fas fa-chart-line"></i> Theo dõi tiến độ
                    </a>
                    <button class="lms-btn lms-btn-success" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                        <i class="fas fa-file-excel"></i> Nhập từ Excel
                    </button>
                    <button class="lms-btn lms-btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-user-plus"></i> Thêm học sinh
                    </button>
                </div>
            @endif
        </div>

        {{-- Stat cards --}}
        <div class="lms-stats">
            <div class="lms-stat">
                <div class="lms-stat-label">Tổng học sinh</div>
                <div class="lms-stat-value">{{ $classStats['total'] ?? $classroom->students->count() }}</div>
                <div class="lms-stat-sub">Đang hiển thị {{ $classStats['shown'] ?? $classroom->students->count() }}</div>
            </div>
            <div class="lms-stat danger">
                <div class="lms-stat-label">Cần theo dõi</div>
                <div class="lms-stat-value">{{ $classStats['needs_attention'] ?? 0 }}</div>
                <div class="lms-stat-sub">Cảnh báo học tập hoặc điểm danh</div>
            </div>
            <div class="lms-stat warning">
                <div class="lms-stat-label">Chưa nộp bài</div>
                <div class="lms-stat-value">{{ $classStats['missing_assignments'] ?? 0 }}</div>
                <div class="lms-stat-sub">Học sinh còn thiếu bài tập</div>
            </div>
            <div class="lms-stat info">
                <div class="lms-stat-label">Có lượt vắng</div>
                <div class="lms-stat-value">{{ $classStats['absent'] ?? 0 }}</div>
                <div class="lms-stat-sub">Từ dữ liệu điểm danh</div>
            </div>
        </div>

        {{-- Student table card --}}
        <div class="lms-card">
            <div class="lms-card-header">
                <h2 class="lms-card-title">
                    <i class="fas fa-users"></i> Danh sách học viên
                </h2>
                <span class="lms-count">{{ ($studentSummaries ?? collect())->count() }} kết quả</span>
            </div>

            {{-- Filter --}}
            <form action="{{ route('classes.students.index', $classroom->id) }}" method="GET" class="lms-filter">
                <div class="lms-filter-group" style="flex:2; min-width:200px;">
                    <label>Tìm kiếm</label>
                    <div class="lms-input-icon">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="lms-input" style="width:100%;"
                            placeholder="Tên, tên đăng nhập, mã HS hoặc email..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="lms-filter-group" style="flex:1.5; min-width:160px;">
                    <label>Khóa học</label>
                    <select name="course_id" class="lms-select" style="width:100%;">
                        <option value="">Tất cả khóa học</option>
                        @foreach ($availableCourses ?? collect() as $course)
                            <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? '') == $course->id)>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="lms-filter-group" style="flex:1.5; min-width:160px;">
                    <label>Trạng thái</label>
                    <select name="status" class="lms-select" style="width:100%;">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Tất cả</option>
                        <option value="needs_attention" @selected(($filters['status'] ?? '') === 'needs_attention')>Cần theo dõi</option>
                        <option value="missing_assignments" @selected(($filters['status'] ?? '') === 'missing_assignments')>Chưa nộp bài</option>
                        <option value="low_score" @selected(($filters['status'] ?? '') === 'low_score')>Điểm quiz thấp</option>
                        <option value="absent" @selected(($filters['status'] ?? '') === 'absent')>Có lượt vắng</option>
                        <option value="no_activity" @selected(($filters['status'] ?? '') === 'no_activity')>Chưa có hoạt động</option>
                    </select>
                </div>
                <div class="lms-filter-actions" style="padding-bottom:0;">
                    <button type="submit" class="lms-btn lms-btn-primary" style="height:36px; padding:0 14px;">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                    <a href="{{ route('classes.students.index', $classroom->id) }}" class="lms-btn-reset"
                        title="Xóa bộ lọc">
                        <i class="fas fa-rotate-left" style="font-size:13px;"></i>
                    </a>
                </div>
            </form>

            {{-- Table --}}
            <div class="lms-table-wrap">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>Học sinh</th>
                            <th>Tình trạng</th>
                            <th>Bài tập</th>
                            <th>Quiz</th>
                            <th>Điểm danh</th>
                            <th>Hoạt động gần nhất</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($studentSummaries ?? collect() as $summary)
                            @php $student = $summary['student']; @endphp
                            <tr>
                                {{-- Student --}}
                                <td>
                                    <div style="display:flex; align-items:center; gap:11px;">
                                        <div class="lms-avatar">{{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}</div>
                                        <div>
                                            <div class="lms-student-name">{{ $student->name }}</div>
                                            @if ($student->username)
                                                <div class="lms-student-email">
                                                    <i class="fas fa-id-badge"></i> {{ $student->username }}
                                                </div>
                                            @endif
                                            @if ($student->student_code)
                                                <div class="lms-student-email">
                                                    <i class="fas fa-hashtag"></i> {{ $student->student_code }}
                                                </div>
                                            @endif
                                            <div class="lms-student-email">{{ $student->email }}</div>
                                            <div class="lms-student-id">#{{ $student->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                {{-- Status --}}
                                <td>
                                    @if ($summary['needs_attention'])
                                        <span class="lms-badge lms-badge-danger"><i class="fas fa-circle"
                                                style="font-size:6px;"></i> Cần theo dõi</span>
                                    @else
                                        <span class="lms-badge lms-badge-success"><i class="fas fa-circle"
                                                style="font-size:6px;"></i> Ổn định</span>
                                    @endif
                                    <div class="lms-alerts">
                                        @forelse($summary['alerts'] as $alert)
                                            <div class="lms-alert-item {{ $alert['level'] }}">
                                                <i class="fas fa-circle-exclamation"></i>
                                                <span>{{ $alert['text'] }}</span>
                                            </div>
                                        @empty
                                            <div class="lms-alert-item"><span
                                                    style="font-size:11.5px; color:#94A3B8;">Chưa có cảnh báo</span></div>
                                        @endforelse
                                    </div>
                                </td>
                                {{-- Assignments --}}
                                <td>
                                    <div class="lms-mini-stat">
                                        <div class="lms-mini-val">
                                            {{ $summary['assignment_submitted_count'] }}/{{ $summary['assignment_total'] }}
                                        </div>
                                        <div class="lms-micro-bar">
                                            <div class="lms-micro-fill success"
                                                style="width:{{ $summary['assignment_total'] > 0 ? round(($summary['assignment_submitted_count'] / $summary['assignment_total']) * 100) : 0 }}%;">
                                            </div>
                                        </div>
                                        <div class="lms-mini-sub">{{ $summary['assignment_missing_count'] }} thiếu ·
                                            {{ $summary['assignment_overdue_missing_count'] }} quá hạn</div>
                                        <div class="lms-mini-sub">TB: {{ $summary['assignment_average'] ?? 'N/A' }}</div>
                                    </div>
                                </td>
                                {{-- Quiz --}}
                                <td>
                                    <div class="lms-mini-stat">
                                        <div class="lms-mini-val">
                                            {{ $summary['quiz_attempted_count'] }}/{{ $summary['quiz_total'] }}</div>
                                        <div class="lms-micro-bar">
                                            <div class="lms-micro-fill"
                                                style="width:{{ $summary['quiz_total'] > 0 ? round(($summary['quiz_attempted_count'] / $summary['quiz_total']) * 100) : 0 }}%;">
                                            </div>
                                        </div>
                                        <div class="lms-mini-sub">TB: {{ $summary['quiz_average'] ?? 'N/A' }}</div>
                                        <div class="lms-mini-sub">{{ $summary['quiz_pending_count'] }} chưa làm</div>
                                    </div>
                                </td>
                                {{-- Attendance --}}
                                <td>
                                    <div class="lms-mini-stat">
                                        <div class="lms-mini-val"
                                            style="{{ $summary['absence_count'] > 0 ? 'color:var(--lms-danger)' : '' }}">
                                            {{ $summary['absence_count'] }} lượt vắng
                                        </div>
                                        <div class="lms-mini-sub">{{ $summary['note_count'] }} ghi chú</div>
                                    </div>
                                </td>
                                {{-- Activity --}}
                                <td style="font-size:13px; color:var(--lms-muted);">
                                    @if ($summary['last_activity_at'])
                                        <div style="font-weight:600; color:var(--lms-text);">
                                            {{ $summary['last_activity_at']->format('d/m/Y') }}</div>
                                        <div style="font-size:12px;">{{ $summary['last_activity_at']->format('H:i') }}
                                        </div>
                                    @else
                                        <span style="font-size:12px; color:#94A3B8;">Chưa có</span>
                                    @endif
                                </td>
                                {{-- Actions --}}
                                <td>
                                    <div class="lms-row-actions">
                                        <a href="{{ route('classes.students.show', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                                            class="lms-action-link">
                                            <i class="fas fa-chart-line" style="font-size:12px;"></i> Hồ sơ
                                        </a>
                                        @if (auth()->user()->role === 'admin' || auth()->id() === $classroom->teacher_id)
                                            <form
                                                action="{{ route('classes.students.destroy', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                                                method="POST" class="d-inline lms-row-btn-delete">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="lms-action-link danger"
                                                    style="background:none; border:none; cursor:pointer; font-family:inherit;"
                                                    onclick="return confirm('Xóa {{ $student->name }} khỏi lớp?')">
                                                    <i class="fas fa-user-minus" style="font-size:12px;"></i> Xóa
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="lms-empty">
                                        <span class="lms-empty-icon"><i class="fas fa-user-graduate"></i></span>
                                        <h6>Không tìm thấy học sinh phù hợp</h6>
                                        <p>Hãy đổi bộ lọc hoặc thêm học sinh mới để bắt đầu.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal: Add Student --}}
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <form action="{{ route('classes.students.store', $classroom->id) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header" style="padding-bottom:16px;">
                    <div>
                        <h5 class="modal-title">Thêm học sinh mới</h5>
                        <p style="font-size:13px; color:var(--lms-muted); margin:4px 0 0;">Tạo tài khoản và gán vào
                            <strong>{{ $classroom->name }}</strong></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding-top:8px;">
                    <div class="lms-info-box">
                        <i class="fas fa-info-circle" style="flex-shrink:0; margin-top:1px;"></i>
                        <span>Học sinh sẽ được tạo tên đăng nhập từ họ tên. Nếu trùng tên, hệ thống sẽ ghép thêm mã học sinh hoặc số thứ tự.</span>
                    </div>
                    <div class="lms-form-group">
                        <label class="lms-form-label">Họ và tên</label>
                        <input type="text" name="name" class="lms-form-control" placeholder="Nguyễn Văn A"
                            required value="{{ old('name') }}">
                    </div>
                    <div class="lms-form-group">
                        <label class="lms-form-label">Mã học sinh <span style="font-weight:500; color:var(--lms-muted);">(không bắt buộc)</span></label>
                        <input type="text" name="student_code" class="lms-form-control" placeholder="VD: HS001"
                            value="{{ old('student_code') }}">
                    </div>
                    <div class="lms-form-group">
                        <label class="lms-form-label">Địa chỉ Email <span style="font-weight:500; color:var(--lms-muted);">(không bắt buộc)</span></label>
                        <input type="email" name="email" class="lms-form-control"
                            placeholder="nguyenvana@example.com" value="{{ old('email') }}">
                    </div>
                    <div class="lms-form-group" style="margin-bottom:0;">
                        <label class="lms-form-label">Mật khẩu khởi tạo</label>
                        <input type="password" name="password" class="lms-form-control" placeholder="Ít nhất 6 ký tự"
                            required minlength="6">
                    </div>
                </div>
                <div class="modal-footer" style="padding-top:16px;">
                    <button type="button" class="lms-btn lms-btn-outline" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="lms-btn lms-btn-primary">
                        <i class="fas fa-user-plus"></i> Thêm vào lớp
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Import Excel --}}
    <div class="modal fade" id="importExcelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
            <form action="{{ route('classes.students.import', $classroom->id) }}" method="POST"
                enctype="multipart/form-data" class="modal-content">
                @csrf
                <div class="modal-header" style="padding-bottom:16px;">
                    <div>
                        <h5 class="modal-title">Nhập danh sách từ Excel</h5>
                        <p style="font-size:13px; color:var(--lms-muted); margin:4px 0 0;">Tải lên file danh sách học sinh
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding-top:8px;">
                    <div class="lms-warning-box">
                        <strong style="font-size:13.5px;"><i class="fas fa-circle-info"
                                style="margin-right:5px;"></i>Hướng dẫn & Quy chuẩn</strong>
                        <ul>
                            <li>Hệ thống đọc từ <strong>Dòng số 5</strong></li>
                            <li>Cột: <strong>D</strong> (Mã HS) · <strong>E</strong> (Họ) · <strong>F</strong> (Tên)</li>
                            <li>Tên đăng nhập tự sinh từ họ tên, ví dụ <code>nguyenvana</code></li>
                            <li>Nếu trùng họ tên, hệ thống ghép thêm Mã HS hoặc số thứ tự</li>
                            <li>Mật khẩu mặc định: <code>123456</code></li>
                        </ul>
                        <a href="{{ asset('templates/mau_danh_sach_hoc_sinh.xlsx') }}"
                            style="display:inline-flex; align-items:center; gap:6px; margin-top:4px;">
                            <i class="fas fa-file-excel" style="color:#059669;"></i> Tải file biểu mẫu chuẩn (.xlsx)
                        </a>
                    </div>
                    <div class="lms-form-group" style="margin-top:16px; margin-bottom:0;">
                        <label class="lms-form-label">Chọn file đã điền dữ liệu</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required
                            style="font-size:13.5px;">
                    </div>
                </div>
                <div class="modal-footer" style="padding-top:16px;">
                    <button type="button" class="lms-btn lms-btn-outline" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="lms-btn lms-btn-success"
                        style="background:#059669; color:#fff; border-color:#059669;">
                        <i class="fas fa-upload"></i> Bắt đầu nhập
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
