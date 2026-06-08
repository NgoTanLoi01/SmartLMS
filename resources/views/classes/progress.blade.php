@extends('layouts.app')

@section('title', 'Tiến độ lớp - ' . $classroom->name)

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
            color: var(--lms-text);
            border-color: #94A3B8;
        }

        .lms-btn-sm {
            padding: 5px 11px;
            font-size: 12.5px;
        }

        .lms-btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* ── AI Panel ── */
        .lms-ai-panel {
            background: #fff;
            border: 1px solid #BFDBFE;
            border-left: 4px solid var(--lms-blue);
            border-radius: var(--lms-radius);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .lms-ai-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--lms-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .lms-ai-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--lms-text);
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .lms-ai-scope {
            font-size: 12px;
            color: var(--lms-muted);
            margin-top: 2px;
        }

        .lms-ai-body {
            padding: 20px;
        }

        .lms-ai-loading {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--lms-muted);
            font-size: 13.5px;
        }

        .lms-ai-section-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--lms-muted);
            margin-bottom: 12px;
        }

        .lms-ai-summary {
            font-size: 14px;
            line-height: 1.7;
            color: var(--lms-text);
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--lms-border);
        }

        .lms-ai-columns {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .lms-ai-col {
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius-sm);
            padding: 16px;
        }

        .lms-ai-col-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--lms-text);
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 14px;
        }

        .lms-ai-col-title i {
            font-size: 14px;
        }

        .lms-ai-item {
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid #F1F5F9;
        }

        .lms-ai-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .lms-ai-item-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--lms-text);
            margin-bottom: 2px;
        }

        .lms-ai-item-sub {
            font-size: 12px;
            color: var(--lms-muted);
            margin-bottom: 4px;
        }

        .lms-ai-item-text {
            font-size: 13px;
            color: var(--lms-text);
            line-height: 1.5;
        }

        .lms-ai-empty {
            font-size: 13px;
            color: var(--lms-muted);
        }

        .lms-alert-msg {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            border-radius: var(--lms-radius-sm);
            padding: 12px 14px;
            font-size: 13px;
            color: #92400E;
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

        .lms-stat.success::before {
            background: var(--lms-success);
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

        .lms-stat-sub {
            font-size: 12px;
            color: var(--lms-muted);
            margin-top: 2px;
        }

        .lms-prog-bar {
            height: 5px;
            background: #E2E8F0;
            border-radius: 3px;
            margin: 10px 0 6px;
            overflow: hidden;
        }

        .lms-prog-fill {
            height: 100%;
            border-radius: 3px;
            background: var(--lms-blue);
        }

        .lms-prog-fill.green {
            background: var(--lms-success);
        }

        /* ── Course cards ── */
        .lms-courses {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 1.75rem;
        }

        .lms-course-card {
            background: #fff;
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius);
            padding: 16px 18px;
        }

        .lms-course-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 10px;
        }

        .lms-course-card-title {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--lms-text);
            line-height: 1.3;
        }

        .lms-course-badge {
            background: var(--lms-surface);
            border: 1px solid var(--lms-border);
            border-radius: 100px;
            padding: 2px 8px;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--lms-muted);
            white-space: nowrap;
        }

        .lms-course-prog-label {
            font-size: 12px;
            color: var(--lms-muted);
            margin-bottom: 5px;
        }

        .lms-course-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
        }

        .lms-course-tag {
            font-size: 11.5px;
            color: var(--lms-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ── Main table card ── */
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
            gap: 10px;
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
        }

        .lms-count {
            background: var(--lms-surface);
            border: 1px solid var(--lms-border);
            border-radius: 100px;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 600;
            color: var(--lms-muted);
        }

        /* ── Filter ── */
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

        .lms-checkbox-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 13.5px;
            color: var(--lms-text);
            padding-bottom: 4px;
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
            color: var(--lms-text);
        }

        /* ── Table ── */
        .lms-table-wrap {
            overflow-x: auto;
        }

        .lms-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 960px;
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

        .lms-student-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--lms-text);
            margin-bottom: 2px;
        }

        .lms-student-email {
            font-size: 12px;
            color: var(--lms-muted);
        }

        /* ── Micro progress ── */
        .lms-micro {
            font-size: 13.5px;
        }

        .lms-micro-val {
            font-weight: 600;
            color: var(--lms-text);
        }

        .lms-micro-bar {
            height: 4px;
            background: #E2E8F0;
            border-radius: 2px;
            margin: 5px 0 3px;
            overflow: hidden;
        }

        .lms-micro-fill {
            height: 100%;
            border-radius: 2px;
            background: var(--lms-blue);
        }

        .lms-micro-fill.green {
            background: var(--lms-success);
        }

        .lms-micro-sub {
            font-size: 11.5px;
            color: var(--lms-muted);
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

        /* ── Alert items ── */
        .lms-alerts {
            margin-top: 7px;
            display: flex;
            flex-direction: column;
            gap: 3px;
            max-width: 240px;
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

        /* ── Row actions ── */
        .lms-row-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
        }

        .lms-btn-ai {
            background: var(--lms-blue);
            color: #fff;
            border: none;
            border-radius: var(--lms-radius-sm);
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.15s;
        }

        .lms-btn-ai:hover {
            background: #1e40af;
        }

        .lms-btn-detail {
            background: #fff;
            color: var(--lms-blue);
            border: 1px solid var(--lms-blue-mid);
            border-radius: var(--lms-radius-sm);
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.15s;
            text-decoration: none;
        }

        .lms-btn-detail:hover {
            background: var(--lms-blue-light);
            color: var(--lms-blue);
        }

        .lms-btn-profile {
            background: #fff;
            color: var(--lms-muted);
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius-sm);
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.15s;
            text-decoration: none;
        }

        .lms-btn-profile:hover {
            background: var(--lms-surface);
            color: var(--lms-text);
        }

        /* ── Modals ── */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
        }

        .modal-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--lms-border);
        }

        .modal-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--lms-text);
        }

        .modal-subtitle {
            font-size: 12.5px;
            color: var(--lms-muted);
            margin-top: 2px;
        }

        .modal-body {
            padding: 20px 24px;
        }

        .modal-footer {
            padding: 16px 24px 20px;
            border-top: 1px solid var(--lms-border);
        }

        .lms-modal-section {
            margin-bottom: 4px;
        }

        .lms-modal-section-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--lms-text);
            margin-bottom: 10px;
        }

        .lms-modal-list {
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius-sm);
            padding: 14px;
            max-height: 240px;
            overflow-y: auto;
        }

        .lms-modal-item {
            padding-bottom: 10px;
            margin-bottom: 10px;
            border-bottom: 1px solid #F1F5F9;
        }

        .lms-modal-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .lms-modal-item-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--lms-text);
            margin-bottom: 2px;
        }

        .lms-modal-item-sub {
            font-size: 12px;
            color: var(--lms-muted);
        }

        .lms-modal-empty {
            font-size: 13px;
            color: var(--lms-muted);
        }

        /* ── Responsive ── */
        @media (max-width: 1100px) {
            .lms-courses {
                grid-template-columns: repeat(2, 1fr);
            }

            .lms-ai-columns {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .lms-page {
                padding: 1rem 1rem 2rem;
            }

            .lms-stats {
                grid-template-columns: 1fr 1fr;
            }

            .lms-courses {
                grid-template-columns: 1fr;
            }

            .lms-filter {
                flex-direction: column;
            }

            .lms-filter-group,
            .lms-filter-group select {
                width: 100%;
            }

            .lms-btn-group {
                flex-direction: column;
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

        {{-- Page header --}}
        <div class="lms-page-header">
            <div>
                <nav class="lms-breadcrumb">
                    <a href="{{ route('classes.index') }}">Lớp học</a>
                    <span class="lms-breadcrumb-sep">›</span>
                    <span>{{ $classroom->code }}</span>
                </nav>
                <h1 class="lms-page-title">Dashboard tiến độ: {{ $classroom->name }}</h1>
                <div class="lms-page-meta">
                    <span><i class="fas fa-chalkboard-teacher" style="font-size:12px;"></i>
                        {{ $classroom->teacher->name }}</span>
                    <span><i class="fas fa-users" style="font-size:12px;"></i> {{ $classReport['student_count'] }} học
                        sinh</span>
                </div>
            </div>
            <div class="lms-btn-group">
                <button type="button" class="lms-btn lms-btn-primary" data-ai-scope="class">
                    <i class="fas fa-robot"></i> Phân tích AI toàn lớp
                </button>
                <a href="{{ route('classes.students.index', $classroom->id) }}" class="lms-btn lms-btn-outline">
                    <i class="fas fa-user-graduate"></i> Danh sách học sinh
                </a>
                <a href="{{ route('classes.index') }}" class="lms-btn lms-btn-outline">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>

        {{-- AI Analysis Panel --}}
        <div class="lms-ai-panel d-none" id="aiAnalysisPanel">
            <div class="lms-ai-header">
                <div>
                    <h2 class="lms-ai-title">
                        <i class="fas fa-wand-magic-sparkles" style="color:var(--lms-blue);"></i>
                        Phân tích học tập bằng AI
                    </h2>
                    <div class="lms-ai-scope" id="aiAnalysisScope">Đang chờ dữ liệu phân tích</div>
                </div>
                <button type="button" class="lms-btn lms-btn-outline lms-btn-sm" id="aiAnalysisClose">
                    <i class="fas fa-xmark"></i> Đóng
                </button>
            </div>
            <div class="lms-ai-body">
                <div id="aiAnalysisLoading" class="d-none">
                    <div class="lms-ai-loading">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        AI đang phân tích dữ liệu học tập...
                    </div>
                </div>
                <div id="aiAnalysisError" class="lms-alert-msg d-none"></div>
                <div id="aiAnalysisContent" class="d-none">
                    <div class="lms-ai-summary" id="aiSummary"></div>
                    <div class="lms-ai-columns">
                        <div class="lms-ai-col">
                            <div class="lms-ai-col-title">
                                <i class="fas fa-triangle-exclamation" style="color:var(--lms-danger);"></i>
                                Rủi ro phát hiện
                            </div>
                            <div id="aiRisks"></div>
                        </div>
                        <div class="lms-ai-col">
                            <div class="lms-ai-col-title">
                                <i class="fas fa-lightbulb" style="color:var(--lms-warning);"></i>
                                Hành động đề xuất
                            </div>
                            <div id="aiActions"></div>
                        </div>
                        <div class="lms-ai-col">
                            <div class="lms-ai-col-title">
                                <i class="fas fa-comment-dots" style="color:var(--lms-blue);"></i>
                                Nhận xét học sinh
                            </div>
                            <div id="aiComments"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <div class="lms-card" style="margin-bottom:1.5rem;">
            <form action="{{ route('classes.progress', $classroom->id) }}" method="GET" class="lms-filter">
                <div class="lms-filter-group" style="flex:2; min-width:200px;">
                    <label>Khóa học</label>
                    <select name="course_id" class="lms-select" style="width:100%;">
                        <option value="">Tất cả khóa học</option>
                        @foreach ($availableCourses as $course)
                            <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? '') == $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lms-filter-group" style="justify-content:flex-end;">
                    <label style="visibility:hidden;">x</label>
                    <label class="lms-checkbox-wrap">
                        <input type="checkbox" name="attention_only" value="1" @checked($filters['attention_only'])>
                        Chỉ học sinh cần chú ý
                    </label>
                </div>
                <div style="display:flex; gap:8px; align-items:flex-end;">
                    <button type="submit" class="lms-btn lms-btn-primary" style="height:36px; padding:0 14px;">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                    <a href="{{ route('classes.progress', $classroom->id) }}" class="lms-btn-reset" title="Xóa bộ lọc">
                        <i class="fas fa-rotate-left" style="font-size:13px;"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- Class stats --}}
        <div class="lms-stats">
            <div class="lms-stat">
                <div class="lms-stat-label">Hoàn thành bài học</div>
                <div class="lms-stat-value">{{ $classReport['lesson_completion_rate'] }}%</div>
                <div class="lms-prog-bar">
                    <div class="lms-prog-fill" style="width:{{ $classReport['lesson_completion_rate'] }}%;"></div>
                </div>
                <div class="lms-stat-sub">{{ $classReport['lesson_completed'] }}/{{ $classReport['lesson_total'] }} lượt
                </div>
            </div>
            <div class="lms-stat success">
                <div class="lms-stat-label">Tỷ lệ nộp bài</div>
                <div class="lms-stat-value">{{ $classReport['assignment_submission_rate'] }}%</div>
                <div class="lms-prog-bar">
                    <div class="lms-prog-fill green" style="width:{{ $classReport['assignment_submission_rate'] }}%;">
                    </div>
                </div>
                <div class="lms-stat-sub">
                    {{ $classReport['assignment_submitted'] }}/{{ $classReport['assignment_total'] }} lượt</div>
            </div>
            <div class="lms-stat">
                <div class="lms-stat-label">Điểm trung bình</div>
                <div class="lms-stat-value">{{ $classReport['score_average'] ?? '—' }}</div>
                <div class="lms-stat-sub">Bài tập & quiz đã có</div>
                <div class="lms-stat-sub">Thiếu {{ $classReport['missing_assignment_total'] }} lượt bài</div>
            </div>
            <div class="lms-stat">
                <div class="lms-stat-label">Cần chú ý</div>
                <div class="lms-stat-value" style="color:var(--lms-danger);">{{ $classReport['needs_attention_count'] }}
                </div>
                <div class="lms-stat-sub">{{ $classReport['absence_total'] }} lượt vắng toàn lớp</div>
                <div class="lms-stat-sub">{{ $classReport['pending_quiz_total'] }} lượt quiz chưa làm</div>
            </div>
        </div>

        {{-- Course cards --}}
        @if (count($courseReports) > 0)
            <div class="lms-courses">
                @forelse ($courseReports as $courseReport)
                    <div class="lms-course-card">
                        <div class="lms-course-card-header">
                            <div class="lms-course-card-title">{{ $courseReport['course']->title }}</div>
                            <span class="lms-course-badge">{{ $courseReport['report']['student_count'] }} HS</span>
                        </div>
                        <div class="lms-course-prog-label">Bài học:
                            {{ $courseReport['report']['lesson_completion_rate'] }}%</div>
                        <div class="lms-prog-bar" style="margin:0 0 8px;">
                            <div class="lms-prog-fill"
                                style="width:{{ $courseReport['report']['lesson_completion_rate'] }}%;"></div>
                        </div>
                        <div class="lms-course-tags">
                            <span class="lms-course-tag"><i class="fas fa-paperclip" style="font-size:11px;"></i> Nộp bài
                                {{ $courseReport['report']['assignment_submission_rate'] }}%</span>
                            <span class="lms-course-tag"><i class="fas fa-star" style="font-size:11px;"></i> TB
                                {{ $courseReport['report']['score_average'] ?? 'N/A' }}</span>
                            <span class="lms-course-tag"><i class="fas fa-user-clock" style="font-size:11px;"></i>
                                {{ $courseReport['report']['needs_attention_count'] }} cần chú ý</span>
                        </div>
                    </div>
                @empty
                    <div style="grid-column:1/-1;">
                        <div
                            style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:var(--lms-radius); padding:14px 16px; font-size:13.5px; color:#1E40AF;">
                            Lớp chưa được gán khóa học.
                        </div>
                    </div>
                @endforelse
            </div>
        @endif

        {{-- Student progress table --}}
        <div class="lms-card">
            <div class="lms-card-header">
                <h2 class="lms-card-title"><i class="fas fa-chart-line"></i> Tiến độ từng học sinh</h2>
                <span class="lms-count">{{ $studentProgress->count() }} kết quả</span>
            </div>
            <div class="lms-table-wrap">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>Học sinh</th>
                            <th>Bài học</th>
                            <th>Bài tập</th>
                            <th>Quiz</th>
                            <th>Điểm TB</th>
                            <th>Vắng</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($studentProgress as $summary)
                            @php
                                $student = $summary['student'];
                                $modalId = 'progressStudentModal' . $student->id;
                            @endphp
                            <tr>
                                <td>
                                    <div class="lms-student-name">{{ $student->name }}</div>
                                    <div class="lms-student-email">{{ $student->email }}</div>
                                </td>
                                <td>
                                    <div class="lms-micro">
                                        <div class="lms-micro-val">
                                            {{ $summary['lesson_completed'] }}/{{ $summary['lesson_total'] }}</div>
                                        <div class="lms-micro-bar">
                                            <div class="lms-micro-fill"
                                                style="width:{{ $summary['lesson_progress'] }}%;"></div>
                                        </div>
                                        <div class="lms-micro-sub">{{ $summary['lesson_progress'] }}%</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="lms-micro">
                                        <div class="lms-micro-val">
                                            {{ $summary['assignment_submitted_count'] }}/{{ $summary['assignment_total'] }}
                                            đã nộp</div>
                                        <div class="lms-micro-bar">
                                            <div class="lms-micro-fill green"
                                                style="width:{{ $summary['assignment_total'] > 0 ? round(($summary['assignment_submitted_count'] / $summary['assignment_total']) * 100) : 0 }}%;">
                                            </div>
                                        </div>
                                        <div class="lms-micro-sub">{{ $summary['assignment_missing_count'] }} thiếu</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="lms-micro">
                                        <div class="lms-micro-val">
                                            {{ $summary['quiz_attempted_count'] }}/{{ $summary['quiz_total'] }}</div>
                                        <div class="lms-micro-sub">{{ $summary['quiz_pending_count'] }} chưa làm</div>
                                    </div>
                                </td>
                                <td style="font-size:14px; font-weight:700; color:var(--lms-text);">
                                    {{ $summary['score_average'] ?? '—' }}
                                </td>
                                <td
                                    style="font-size:13.5px; font-weight:600; {{ $summary['absence_count'] > 0 ? 'color:var(--lms-danger)' : 'color:var(--lms-muted)' }}">
                                    {{ $summary['absence_count'] }}
                                </td>
                                <td>
                                    @if ($summary['needs_attention'])
                                        <span class="lms-badge lms-badge-danger">Cần chú ý</span>
                                    @else
                                        <span class="lms-badge lms-badge-success">Ổn định</span>
                                    @endif
                                    <div class="lms-alerts">
                                        @forelse ($summary['alerts'] as $alert)
                                            <div class="lms-alert-item {{ $alert['level'] }}">
                                                <i class="fas fa-circle-exclamation"></i>
                                                <span>{{ $alert['text'] }}</span>
                                            </div>
                                        @empty
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <div class="lms-row-actions">
                                        <button class="lms-btn-ai" data-ai-scope="student"
                                            data-student-id="{{ $student->id }}"
                                            data-student-name="{{ $student->name }}">
                                            <i class="fas fa-robot"></i> AI
                                        </button>
                                        <button class="lms-btn-detail" data-bs-toggle="modal"
                                            data-bs-target="#{{ $modalId }}">
                                            <i class="fas fa-list-check"></i> Chi tiết
                                        </button>
                                        <a href="{{ route('classes.students.show', ['classId' => $classroom->id, 'studentId' => $student->id, 'course_id' => $filters['course_id']]) }}"
                                            class="lms-btn-profile">
                                            Hồ sơ
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    style="text-align:center; padding:48px 20px; color:var(--lms-muted); font-size:13.5px;">
                                    Không có học sinh phù hợp với bộ lọc hiện tại.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modals --}}
    @foreach ($studentProgress as $summary)
        @php
            $student = $summary['student'];
            $modalId = 'progressStudentModal' . $student->id;
            $missingAssignments = $summary['assignment_details']->where('status', 'missing');
            $completedLessons = $summary['lesson_details']->where('is_completed', true);
            $attemptedQuizzes = $summary['quiz_details']->where('status', 'attempted');
        @endphp
        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">{{ $student->name }}</h5>
                            <div class="modal-subtitle">{{ $student->email }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px;">
                            <div class="lms-modal-section">
                                <div class="lms-modal-section-title"><i class="fas fa-book"
                                        style="color:var(--lms-blue); margin-right:6px; font-size:13px;"></i>Bài học hoàn
                                    thành</div>
                                <div class="lms-modal-list">
                                    @forelse ($completedLessons as $lesson)
                                        <div class="lms-modal-item">
                                            <div class="lms-modal-item-title">{{ $lesson['title'] }}</div>
                                            <div class="lms-modal-item-sub">{{ $lesson['course_title'] }} ·
                                                {{ $lesson['module_title'] }}</div>
                                        </div>
                                    @empty
                                        <div class="lms-modal-empty">Chưa hoàn thành bài học nào.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="lms-modal-section">
                                <div class="lms-modal-section-title"><i class="fas fa-clipboard-check"
                                        style="color:var(--lms-success); margin-right:6px; font-size:13px;"></i>Quiz đã làm
                                </div>
                                <div class="lms-modal-list">
                                    @forelse ($attemptedQuizzes as $quiz)
                                        <div class="lms-modal-item">
                                            <div class="lms-modal-item-title">{{ $quiz['title'] }}</div>
                                            <div class="lms-modal-item-sub">{{ $quiz['course_title'] }} · Điểm
                                                {{ $quiz['score'] ?? 'N/A' }}</div>
                                        </div>
                                    @empty
                                        <div class="lms-modal-empty">Chưa làm quiz nào.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="lms-modal-section">
                                <div class="lms-modal-section-title"><i class="fas fa-triangle-exclamation"
                                        style="color:var(--lms-danger); margin-right:6px; font-size:13px;"></i>Bài tập còn
                                    thiếu</div>
                                <div class="lms-modal-list">
                                    @forelse ($missingAssignments as $assignment)
                                        <div class="lms-modal-item">
                                            <div class="lms-modal-item-title">{{ $assignment['title'] }}</div>
                                            <div class="lms-modal-item-sub">{{ $assignment['course_title'] }}</div>
                                            @if ($assignment['is_overdue'])
                                                <span class="lms-badge lms-badge-danger"
                                                    style="margin-top:4px; font-size:11px;">Quá hạn</span>
                                            @else
                                                <span class="lms-badge lms-badge-warning"
                                                    style="margin-top:4px; font-size:11px;">Chưa nộp</span>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="lms-modal-empty">Không còn bài tập thiếu.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="lms-btn lms-btn-outline" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const panel = document.getElementById('aiAnalysisPanel');
                const loading = document.getElementById('aiAnalysisLoading');
                const errorBox = document.getElementById('aiAnalysisError');
                const content = document.getElementById('aiAnalysisContent');
                const scopeLabel = document.getElementById('aiAnalysisScope');
                const summaryBox = document.getElementById('aiSummary');
                const risksBox = document.getElementById('aiRisks');
                const actionsBox = document.getElementById('aiActions');
                const commentsBox = document.getElementById('aiComments');

                const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const empty = (t) => `<div class="lms-ai-empty">${esc(t)}</div>`;

                const renderRisks = (risks = []) => {
                    if (!risks.length) return empty('AI chưa phát hiện rủi ro rõ ràng.');
                    return risks.map((r) => {
                        const cls = r.level === 'high' ? 'lms-badge-danger' : r.level === 'medium' ?
                            'lms-badge-warning' : 'lms-badge';
                        return `<div class="lms-ai-item">
                <span class="lms-badge ${cls}" style="margin-bottom:6px; font-size:11px;">${esc(r.level)}</span>
                <div class="lms-ai-item-name">${esc(r.student || 'Toàn lớp')}</div>
                <div class="lms-ai-item-sub">${esc(r.type || '')}</div>
                <div class="lms-ai-item-text">${esc(r.reason || '')}</div>
            </div>`;
                    }).join('');
                };

                const renderActions = (actions = []) => {
                    if (!actions.length) return empty('Chưa có hành động đề xuất.');
                    return actions.map((a) => `<div class="lms-ai-item">
            <div class="lms-ai-item-name">${esc(a.action || '')}</div>
            <div class="lms-ai-item-sub">${esc(a.student || 'Nhóm học sinh')} · Ưu tiên ${esc(a.priority || 'medium')}</div>
            <div class="lms-ai-item-text">${esc(a.reason || '')}</div>
        </div>`).join('');
                };

                const renderComments = (comments = []) => {
                    if (!comments.length) return empty('Chưa có nhận xét học sinh.');
                    return comments.map((c) => `<div class="lms-ai-item">
            <div class="lms-ai-item-name">${esc(c.student || 'Học sinh')}</div>
            <div class="lms-ai-item-text">${esc(c.comment || '')}</div>
        </div>`).join('');
                };

                const setState = (state, message = '') => {
                    panel.classList.remove('d-none');
                    loading.classList.toggle('d-none', state !== 'loading');
                    errorBox.classList.toggle('d-none', state !== 'error');
                    content.classList.toggle('d-none', state !== 'success');
                    if (state === 'error') errorBox.textContent = message;
                    panel.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                };

                document.querySelectorAll('[data-ai-scope]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const studentId = btn.dataset.studentId || '';
                        const studentName = btn.dataset.studentName || '';
                        const courseId = document.querySelector('select[name="course_id"]')
                            ?.value || '';
                        scopeLabel.textContent = studentId ? `Phân tích học sinh: ${studentName}` :
                            'Phân tích tổng quan toàn lớp';
                        setState('loading');

                        try {
                            const response = await axios.post(
                                '{{ route('classes.ai-analysis', $classroom->id) }}', {
                                    student_id: studentId || null,
                                    course_id: courseId || null,
                                }, {
                                    headers: {
                                        'X-CSRF-TOKEN': csrfToken
                                    }
                                });

                            const analysis = response.data.analysis || {};
                            summaryBox.textContent = analysis.summary || 'AI chưa có tóm tắt.';
                            risksBox.innerHTML = renderRisks(analysis.risks || []);
                            actionsBox.innerHTML = renderActions(analysis.actions || []);
                            commentsBox.innerHTML = renderComments(analysis.student_comments || []);
                            setState('success');
                        } catch (error) {
                            setState('error', error.response?.data?.message ||
                                'Không thể gọi AI phân tích. Vui lòng thử lại.');
                        }
                    });
                });

                document.getElementById('aiAnalysisClose')?.addEventListener('click', () => {
                    panel.classList.add('d-none');
                });
            });
        </script>
    @endpush
@endsection
