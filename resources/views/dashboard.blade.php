@extends('layouts.app')

@section('title', 'Bảng điều khiển')

@push('styles')
    <style>
        /* =========================================
                   CORE VARIABLES & RESET
                ========================================= */
        :root {
            --brand: #3e80f9;
            --brand-dark: #2563eb;
            --brand-light: #dbeafe;
            --accent: #6f42c1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;

            --surface: #ffffff;
            --surface-2: #f8fafc;
            --border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
            --text-light: #94a3b8;

            --radius-sm: 8px;
            --radius: 16px;
            --radius-lg: 24px;

            --shadow-sm: 0 1px 3px rgba(0, 0, 0, .06), 0 1px 2px rgba(0, 0, 0, .04);
            --shadow: 0 4px 16px rgba(0, 0, 0, .08), 0 1px 4px rgba(0, 0, 0, .04);
            --shadow-lg: 0 12px 40px rgba(0, 0, 0, .10), 0 4px 12px rgba(0, 0, 0, .06);
        }

        /* =========================================
                   GREETING BANNER
                ========================================= */
        .greeting-banner {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 50%, var(--accent) 100%);
            border-radius: var(--radius-lg);
            min-height: 200px;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            margin-bottom: 1.75rem;
            box-shadow: 0 16px 48px rgba(62, 128, 249, .30);
        }

        .greeting-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 80% at 70% -20%, rgba(255, 255, 255, .12) 0%, transparent 60%),
                radial-gradient(ellipse 40% 60% at 100% 100%, rgba(111, 66, 193, .25) 0%, transparent 55%);
            pointer-events: none;
        }

        .greeting-banner__pattern {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* opacity: .08; */
            pointer-events: none;
            mix-blend-mode: overlay;
        }

        .greeting-banner__content {
            position: relative;
            z-index: 1;
            padding: 2.25rem 2.5rem;
        }

        .greeting-banner__content .greeting-title {
            font-size: 1.65rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: .25rem;
            letter-spacing: -.02em;
        }

        .greeting-banner__content .greeting-date {
            color: rgba(255, 255, 255, .72);
            font-size: .9rem;
            font-weight: 500;
            margin: 0;
        }

        .greeting-banner__img-wrap {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            height: 100%;
            padding-bottom: 0;
        }

        .greeting-banner__img {
            max-height: 175px;
            width: auto;
            filter: drop-shadow(0 8px 24px rgba(0, 0, 0, .25));
            transform: translateY(4px);
        }

        /* =========================================
                   STAT CARDS
                ========================================= */
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            padding: 1.4rem 1.6rem;
            display: flex;
            align-items: center;
            gap: 1.1rem;
            transition: transform .2s ease, box-shadow .2s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card__icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .stat-card__label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            margin-bottom: .2rem;
        }

        .stat-card__value {
            font-size: 1.9rem;
            font-weight: 800;
            line-height: 1;
            color: var(--text);
            letter-spacing: -.03em;
        }

        /* colour variants */
        .stat-card--blue .stat-card__icon {
            background: #eff6ff;
            color: var(--brand);
        }

        .stat-card--teal .stat-card__icon {
            background: #ecfeff;
            color: var(--info);
        }

        .stat-card--green .stat-card__icon {
            background: #ecfdf5;
            color: var(--success);
        }

        .stat-card--amber .stat-card__icon {
            background: #fffbeb;
            color: var(--warning);
        }

        .stat-card--red .stat-card__icon {
            background: #fef2f2;
            color: var(--danger);
        }

        .stat-card--violet .stat-card__icon {
            background: #f5f3ff;
            color: var(--accent);
        }

        /* border-left accent style (teacher) */
        .stat-card--accent-left {
            border-left: 4px solid transparent;
            padding-left: 1.4rem;
        }

        .stat-card--accent-left.stat-card--red {
            border-left-color: var(--danger);
        }

        .stat-card--accent-left.stat-card--blue {
            border-left-color: var(--brand);
        }

        .stat-card--accent-left.stat-card--green {
            border-left-color: var(--success);
        }

        /* =========================================
                   GENERIC CARD / PANEL
                ========================================= */
        .panel {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
            height: 100%;
        }

        .panel__header {
            padding: .875rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--surface);
        }

        .panel__title {
            font-size: .875rem;
            font-weight: 700;
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .panel__title i {
            font-size: .95rem;
        }

        /* =========================================
                   TABLE
                ========================================= */
        .tbl {
            width: 100%;
            border-collapse: collapse;
        }

        .tbl thead th {
            background: var(--surface-2);
            color: var(--text-muted);
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: .65rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }

        .tbl tbody td {
            padding: .85rem 1.25rem;
            border-bottom: 1px solid var(--border);
            font-size: .875rem;
            color: var(--text);
            vertical-align: middle;
        }

        .tbl tbody tr:last-child td {
            border-bottom: none;
        }

        .tbl tbody tr:hover td {
            background: var(--surface-2);
        }

        .tbl tbody tr.is-today td {
            background: #eff6ff;
        }

        .tbl tbody tr.is-past td {
            opacity: .5;
        }

        /* =========================================
                   BADGE
                ========================================= */
        .bdg {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .22em .65em;
            border-radius: 100px;
            font-size: .72rem;
            font-weight: 700;
            line-height: 1;
        }

        .bdg--primary {
            background: var(--brand-light);
            color: var(--brand-dark);
        }

        .bdg--info {
            background: #cffafe;
            color: #0e7490;
        }

        .bdg--success {
            background: #d1fae5;
            color: #065f46;
        }

        .bdg--warning {
            background: #fef3c7;
            color: #92400e;
        }

        .bdg--danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .bdg--muted {
            background: var(--surface-2);
            color: var(--text-muted);
            border: 1px solid var(--border);
        }

        .bdg--dark {
            background: #1e293b;
            color: #f1f5f9;
        }

        /* =========================================
                   LIST ITEMS (submission feed)
                ========================================= */
        .feed-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }

        .feed-item:last-child {
            border-bottom: none;
        }

        .feed-item:hover {
            background: var(--surface-2);
        }

        .feed-item__name {
            font-weight: 700;
            font-size: .875rem;
            color: var(--text);
            margin-bottom: .15rem;
        }

        .feed-item__meta {
            font-size: .78rem;
            color: var(--text-muted);
        }

        .feed-item__time {
            font-size: .75rem;
            color: var(--text-muted);
            margin-bottom: .5rem;
        }

        /* =========================================
                   TODO / DEADLINE LIST
                ========================================= */
        .todo-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: .9rem 1.5rem;
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }

        .todo-item:last-child {
            border-bottom: none;
        }

        .todo-item:hover {
            background: var(--surface-2);
        }

        .todo-item--quiz {
            background: #eff6ff;
        }

        .todo-item--quiz:hover {
            background: #dbeafe;
        }

        .todo-item__label {
            font-weight: 700;
            font-size: .875rem;
            color: var(--text);
            margin-bottom: .15rem;
        }

        .todo-item__sub {
            font-size: .78rem;
            color: var(--text-muted);
        }

        .todo-item__deadline {
            font-size: .78rem;
            font-weight: 600;
            color: var(--danger);
            margin-bottom: .4rem;
        }

        .todo-item__time-limit {
            font-size: .78rem;
            font-weight: 600;
            color: var(--brand);
            margin-bottom: .4rem;
        }

        /* =========================================
                   SCORE HERO (student avg)
                ========================================= */
        .score-hero {
            background: linear-gradient(135deg, #1d4ed8 0%, var(--accent) 100%);
            border-radius: var(--radius);
            padding: 2rem;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            box-shadow: var(--shadow-lg);
            border: none;
        }

        .score-hero__label {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: rgba(255, 255, 255, .65);
        }

        .score-hero__value {
            font-size: 6rem;
            font-weight: 900;
            line-height: 1;
            color: #fff;
            letter-spacing: -.06em;
        }

        .score-hero__sub {
            font-size: .8rem;
            color: rgba(255, 255, 255, .55);
        }

        /* =========================================
                   BTN
                ========================================= */
        .btn-xs {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .35rem .85rem;
            border-radius: 100px;
            font-size: .75rem;
            font-weight: 700;
            cursor: pointer;
            border: 1.5px solid transparent;
            text-decoration: none;
            transition: all .15s;
            white-space: nowrap;
        }

        .btn-xs--danger {
            background: #fef2f2;
            color: var(--danger);
            border-color: #fecaca;
        }

        .btn-xs--danger:hover {
            background: var(--danger);
            color: #fff;
        }

        .btn-xs--warning {
            background: #fffbeb;
            color: #92400e;
            border-color: #fde68a;
        }

        .btn-xs--warning:hover {
            background: var(--warning);
            color: #fff;
        }

        .btn-xs--primary {
            background: var(--brand);
            color: #fff;
        }

        .btn-xs--primary:hover {
            background: var(--brand-dark);
        }

        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            margin-bottom: 1.5rem;
        }

        .quick-action {
            align-items: center;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text);
            display: inline-flex;
            font-size: .875rem;
            font-weight: 700;
            gap: .55rem;
            min-height: 44px;
            padding: .7rem 1rem;
            text-decoration: none;
            transition: background .15s, border-color .15s, color .15s;
        }

        .quick-action:hover {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: var(--brand-dark);
        }

        .compact-card {
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.25rem;
        }

        .compact-card:last-child {
            border-bottom: none;
        }

        .progress-line {
            background: #e2e8f0;
            border-radius: 999px;
            height: 8px;
            overflow: hidden;
        }

        .progress-line span {
            background: var(--brand);
            display: block;
            height: 100%;
        }

        /* =========================================
                   EMPTY STATE
                ========================================= */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: .75rem;
            opacity: .3;
            display: block;
        }

        .empty-state p {
            font-size: .875rem;
            margin: 0;
        }

        /* =========================================
                   CHART WRAPPER
                ========================================= */
        .chart-wrap {
            padding: 1.25rem;
        }

        /* =========================================
                   RESPONSIVE
                ========================================= */
        @media (max-width: 576px) {
            .greeting-banner__content {
                padding: 1.75rem 1.5rem;
            }

            .greeting-banner__content .greeting-title {
                font-size: 1.3rem;
            }

            .greeting-banner__img-wrap {
                display: none;
            }

            .stat-card__value {
                font-size: 1.55rem;
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-wrap {
                padding-top: 0.75rem !important;
                padding-bottom: 1.25rem !important;
            }

            .greeting-banner {
                min-height: 150px;
                border-radius: var(--radius);
                margin-bottom: 1rem !important;
            }

            .greeting-banner__content {
                padding: 1.25rem;
            }

            .stat-card {
                padding: 1rem;
                gap: 0.85rem;
            }

            .stat-card__icon {
                width: 44px;
                height: 44px;
                font-size: 1.1rem;
            }

            .panel__header {
                align-items: flex-start;
                flex-wrap: wrap;
                gap: 0.5rem;
                padding: 0.85rem 1rem;
            }

            .tbl {
                min-width: 640px;
            }

            .tbl thead th,
            .tbl tbody td {
                padding: 0.7rem 1rem;
            }

            .chart-wrap {
                overflow-x: auto;
                padding: 1rem;
            }

            .chart-wrap>div {
                max-width: 100%;
            }

            .feed-item,
            .todo-item {
                align-items: stretch;
                flex-direction: column;
                gap: 0.75rem;
                padding: 0.9rem 1rem;
            }

            .feed-item>div:last-child,
            .todo-item>div:last-child {
                text-align: left !important;
            }

            .btn-xs {
                justify-content: center;
                width: 100%;
                white-space: normal;
            }

            .quick-actions {
                flex-direction: column;
            }

            .quick-action {
                justify-content: center;
                width: 100%;
            }

            .score-hero {
                padding: 1.5rem;
            }

            .score-hero__value {
                font-size: 4rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="dashboard-wrap container-fluid py-4">

        {{-- ══════════════════════════════════════
     GREETING BANNER
    ══════════════════════════════════════ --}}
        <div class="greeting-banner mb-4">
            <img src="{{ asset('grettings-pattern.png') }}" alt="" class="greeting-banner__pattern">
            <div class="row w-100 align-items-center g-0">
                <div class="col-sm-7">
                    <div class="greeting-banner__content">
                        <h3 class="greeting-title">Xin chào, {{ auth()->user()->name }}! 👋</h3>
                        <p class="greeting-date">
                            <i class="far fa-calendar-alt me-1"></i>
                            {{ \Carbon\Carbon::now()->translatedFormat('l, d/m/Y') }}
                        </p>
                    </div>
                </div>
                <div class="col-sm-5 d-none d-sm-flex greeting-banner__img-wrap pe-3">
                    <img src="{{ asset('gretting-img.png') }}" class="greeting-banner__img" alt="Greeting">
                </div>
            </div>
        </div>

        @php $role = auth()->user()->role; @endphp

        <div class="quick-actions">
            @if ($role === 'admin')
                <a href="{{ route('users.index') }}" class="quick-action"><i class="fas fa-users-cog"></i> Quản lý người dùng</a>
                <a href="{{ route('classes.index') }}" class="quick-action"><i class="fas fa-school"></i> Quản lý lớp</a>
                <a href="{{ route('courses.index') }}" class="quick-action"><i class="fas fa-book-open"></i> Quản lý khóa học</a>
                <a href="{{ route('documents.upload') }}" class="quick-action"><i class="fas fa-robot"></i> Huấn luyện AI</a>
            @elseif ($role === 'teacher')
                <a href="{{ route('classes.index') }}" class="quick-action"><i class="fas fa-school"></i> Lớp của tôi</a>
                <a href="{{ route('courses.index') }}" class="quick-action"><i class="fas fa-book-open"></i> Khóa học của tôi</a>
                <a href="{{ route('assignments.index') }}" class="quick-action"><i class="fas fa-clipboard-check"></i> Chấm bài</a>
                <a href="{{ route('schedules.index') }}" class="quick-action"><i class="fas fa-calendar-alt"></i> Lịch dạy</a>
            @else
                <a href="{{ route('courses.index') }}" class="quick-action"><i class="fas fa-book-open"></i> Vào học</a>
                <a href="{{ route('assignments.index') }}" class="quick-action"><i class="fas fa-paper-plane"></i> Bài tập</a>
            @endif
        </div>

        {{-- ══════════════════════════════════════
     ADMIN VIEW
    ══════════════════════════════════════ --}}
        @if ($role === 'admin')

            {{-- Stat row --}}
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-3">
                    <div class="stat-card stat-card--blue">
                        <div class="stat-card__icon"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="stat-card__label">Học sinh</div>
                            <div class="stat-card__value">{{ $data['total_students'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="stat-card stat-card--teal">
                        <div class="stat-card__icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div>
                            <div class="stat-card__label">Giáo viên</div>
                            <div class="stat-card__value">{{ $data['total_teachers'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="stat-card stat-card--green">
                        <div class="stat-card__icon"><i class="fas fa-layer-group"></i></div>
                        <div>
                            <div class="stat-card__label">Khóa / Lớp</div>
                            <div class="stat-card__value">
                                {{ $data['total_courses'] }}<span
                                    style="font-size:1rem;font-weight:600;color:var(--text-muted)">/{{ $data['total_classes'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="stat-card stat-card--amber">
                        <div class="stat-card__icon"><i class="fas fa-hourglass-half"></i></div>
                        <div>
                            <div class="stat-card__label">Bài chờ chấm</div>
                            <div class="stat-card__value">{{ $data['pending_grades'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table + Chart --}}
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-user-plus" style="color:var(--brand)"></i>
                                Người dùng mới
                            </h6>
                        </div>
                        <div class="table-responsive">
                            <table class="tbl">
                                <thead>
                                    <tr>
                                        <th>Họ và tên</th>
                                        <th>Email</th>
                                        <th>Vai trò</th>
                                        <th>Ngày tham gia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['recent_users'] as $user)
                                        @php $r = $user->role; @endphp
                                        <tr>
                                            <td class="fw-bold">{{ $user->name }}</td>
                                            <td style="color:var(--text-muted)">{{ $user->email }}</td>
                                            <td>
                                                <span
                                                    class="bdg {{ $r === 'teacher' ? 'bdg--info' : ($r === 'admin' ? 'bdg--dark' : 'bdg--primary') }}">
                                                    {{ strtoupper($r) }}
                                                </span>
                                            </td>
                                            <td style="color:var(--text-muted);font-size:.8rem">
                                                {{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state">
                                                    <i class="fas fa-users"></i>
                                                    <p>Chưa có người dùng mới.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-chart-pie" style="color:var(--success)"></i>
                                Tỷ lệ người dùng
                            </h6>
                        </div>
                        <div class="chart-wrap d-flex justify-content-center align-items-center" style="min-height:260px">
                            <div id="adminChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-12 col-xl-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-calendar-day" style="color:var(--warning)"></i>
                                Lịch học hôm nay
                            </h6>
                            <span class="bdg bdg--warning">{{ ($data['today_schedules'] ?? collect())->count() }} ca</span>
                        </div>
                        @forelse ($data['today_schedules'] ?? [] as $slot)
                            <div class="compact-card">
                                <div class="fw-bold">{{ $slot->course_title }}</div>
                                <div class="text-muted small">{{ $slot->class_name }} · {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}</div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <i class="fas fa-calendar-check"></i>
                                <p>Hôm nay chưa có lịch học.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-school" style="color:var(--brand)"></i>
                                Lớp nổi bật
                            </h6>
                            <a href="{{ route('classes.index') }}" class="btn-xs btn-xs--primary">Xem lớp</a>
                        </div>
                        @forelse ($data['class_overview'] ?? [] as $class)
                            <div class="compact-card">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <div class="fw-bold">{{ $class->name }}</div>
                                        <div class="text-muted small">{{ $class->teacher->name ?? 'Chưa phân công' }}</div>
                                    </div>
                                    <span class="bdg bdg--primary">{{ $class->students_count }} HS</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <i class="fas fa-school"></i>
                                <p>Chưa có lớp học.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-book-open" style="color:var(--success)"></i>
                                Khóa học mới
                            </h6>
                            <a href="{{ route('courses.index') }}" class="btn-xs btn-xs--primary">Xem khóa</a>
                        </div>
                        @forelse ($data['recent_courses'] ?? [] as $course)
                            <div class="compact-card">
                                <div class="fw-bold">{{ $course->title }}</div>
                                <div class="text-muted small">{{ $course->teacher->name ?? 'Chưa rõ giáo viên' }}</div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <i class="fas fa-book"></i>
                                <p>Chưa có khóa học.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════
     TEACHER VIEW
    ══════════════════════════════════════ --}}
        @elseif ($role === 'teacher')
            {{-- Stat row --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card stat-card--red stat-card--accent-left">
                        <div class="stat-card__icon"><i class="fas fa-hourglass-half"></i></div>
                        <div>
                            <div class="stat-card__label">Bài chờ chấm</div>
                            <div class="stat-card__value" style="color:var(--danger)">{{ $data['pending_grades'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card stat-card--blue stat-card--accent-left">
                        <div class="stat-card__icon"><i class="fas fa-book-open"></i></div>
                        <div>
                            <div class="stat-card__label">Khóa học phụ trách</div>
                            <div class="stat-card__value" style="color:var(--brand)">{{ $data['total_courses'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card stat-card--green stat-card--accent-left">
                        <div class="stat-card__icon"><i class="fas fa-user-graduate"></i></div>
                        <div>
                            <div class="stat-card__label">Tổng học sinh</div>
                            <div class="stat-card__value" style="color:var(--success)">{{ $data['total_students'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-xl-7">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-school" style="color:var(--brand)"></i>
                                Lớp phụ trách
                            </h6>
                            <a href="{{ route('classes.index') }}" class="btn-xs btn-xs--primary">Quản lý lớp</a>
                        </div>
                        <div class="row g-0">
                            @forelse ($data['teacher_classes'] ?? [] as $class)
                                <div class="col-12 col-lg-6">
                                    <div class="compact-card h-100">
                                        <div class="d-flex justify-content-between gap-2 mb-2">
                                            <div>
                                                <div class="fw-bold">{{ $class->name }}</div>
                                                <div class="text-muted small">{{ $class->code }} · {{ $class->courses->count() }} khóa học</div>
                                            </div>
                                            <span class="bdg bdg--primary">{{ $class->students_count }} HS</span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="{{ route('classes.progress', $class->id) }}" class="btn-xs btn-xs--primary">
                                                <i class="fas fa-chart-line"></i> Tiến độ
                                            </a>
                                            <a href="{{ route('classes.students.index', $class->id) }}" class="btn-xs btn-xs--warning">
                                                <i class="fas fa-user-graduate"></i> Học sinh
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="empty-state">
                                        <i class="fas fa-school"></i>
                                        <p>Thầy / Cô chưa được phân công lớp.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-5">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-user-clock" style="color:var(--danger)"></i>
                                Học sinh cần chú ý
                            </h6>
                            <span class="bdg bdg--danger">Ưu tiên</span>
                        </div>
                        @forelse ($data['attention_students'] ?? [] as $student)
                            <div class="compact-card">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <div class="fw-bold">{{ $student->name }}</div>
                                        <div class="text-muted small">{{ $student->class_name }} · {{ $student->email }}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="bdg {{ $student->avg_grade !== null && $student->avg_grade < 5 ? 'bdg--danger' : 'bdg--muted' }}">
                                            TB {{ $student->avg_grade !== null ? round($student->avg_grade, 1) : 'N/A' }}
                                        </div>
                                        <div class="mt-2">
                                            <a href="{{ route('classes.students.show', ['classId' => $student->class_id, 'studentId' => $student->id]) }}" class="btn-xs btn-xs--primary">
                                                Hồ sơ
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <i class="fas fa-check-circle" style="color:var(--success)"></i>
                                <p>Chưa có học sinh cần ưu tiên theo dõi.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Submissions + Chart --}}
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-inbox" style="color:var(--danger)"></i>
                                Bài vừa nộp — chờ chấm
                            </h6>
                        </div>
                        <div style="max-height:340px;overflow-y:auto;">
                            @forelse ($data['recent_submissions'] as $sub)
                                <div class="feed-item">
                                    <div>
                                        <div class="feed-item__name">{{ $sub->student_name }}</div>
                                        <div class="feed-item__meta">{{ $sub->assignment_title ?? 'N/A' }}</div>
                                        <div class="mt-1">
                                            <span class="bdg bdg--primary">
                                                <i class="fas fa-book"></i> {{ $sub->course_title ?? 'N/A' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0">
                                        <div class="feed-item__time">
                                            {{ \Carbon\Carbon::parse($sub->created_at)->diffForHumans() }}
                                        </div>
                                        <a href="{{ route('courses.show', $sub->course_id ?? 0) }}"
                                            class="btn-xs btn-xs--danger">
                                            <i class="fas fa-pen"></i> Chấm ngay
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <p>Tuyệt vời! Thầy / Cô đã chấm hết bài.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-chart-pie" style="color:var(--brand)"></i>
                                Tiến độ chấm bài
                            </h6>
                        </div>
                        <div class="chart-wrap d-flex justify-content-center align-items-center" style="min-height:260px">
                            <div id="teacherChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Weekly schedule --}}
            <div class="row g-3">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-calendar-alt" style="color:var(--brand)"></i>
                                Lịch dạy tuần này
                            </h6>
                            <span class="bdg bdg--primary">Tuần này</span>
                        </div>
                        <div class="table-responsive">
                            <table class="tbl">
                                <thead>
                                    <tr>
                                        <th>Ngày</th>
                                        <th>Giờ dạy</th>
                                        <th>Môn / Lớp</th>
                                        <th>Phòng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['week_schedule'] ?? [] as $slot)
                                        @php
                                            $d = \Carbon\Carbon::parse($slot->schedule_date);
                                            $today = $d->isToday();
                                            $past = $d->isPast() && !$today;
                                        @endphp
                                        <tr class="{{ $today ? 'is-today' : ($past ? 'is-past' : '') }}">
                                            <td>
                                                <div class="fw-bold" style="font-size:.875rem">{{ $d->format('d/m/Y') }}
                                                </div>
                                                <div style="font-size:.78rem;color:var(--text-muted)">
                                                    {{ $d->translatedFormat('l') }}</div>
                                            </td>
                                            <td>
                                                <span style="font-size:.85rem;font-weight:700;color:var(--brand)">
                                                    {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}
                                                    – {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold" style="font-size:.875rem">{{ $slot->course_title }}
                                                </div>
                                                <div style="font-size:.78rem;color:var(--text-muted)">Lớp:
                                                    {{ $slot->class_name }}</div>
                                            </td>
                                            <td>
                                                @if ($today)
                                                    <span class="bdg bdg--success mb-1">Hôm nay</span><br>
                                                @elseif ($past)
                                                    <span class="bdg bdg--muted mb-1">Đã dạy</span><br>
                                                @endif
                                                <span class="bdg bdg--muted">{{ $slot->room ?? 'Online' }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state">
                                                    <i class="fas fa-calendar-times"></i>
                                                    <p><strong>Chưa có lịch dạy</strong><br>Lịch giảng dạy sẽ hiển thị tại
                                                        đây.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════
     STUDENT VIEW
    ══════════════════════════════════════ --}}
        @else
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="stat-card stat-card--blue">
                        <div class="stat-card__icon"><i class="fas fa-book-open"></i></div>
                        <div>
                            <div class="stat-card__label">Khóa học đang học</div>
                            <div class="stat-card__value">{{ $data['total_courses'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card stat-card--amber">
                        <div class="stat-card__icon"><i class="fas fa-file-signature"></i></div>
                        <div>
                            <div class="stat-card__label">Bài tập còn thiếu</div>
                            <div class="stat-card__value">{{ $data['missing_assignments_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card stat-card--violet">
                        <div class="stat-card__icon"><i class="fas fa-clipboard-list"></i></div>
                        <div>
                            <div class="stat-card__label">Quiz chưa làm</div>
                            <div class="stat-card__value">{{ $data['pending_quizzes_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Score hero + Deadline list --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="score-hero">
                        <div class="score-hero__label">Điểm Quiz trung bình</div>
                        <div class="score-hero__value">{{ $data['average_score'] }}</div>
                        <div class="score-hero__sub">/ 10 điểm</div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-clock" style="color:var(--warning)"></i>
                                Deadline & Bài kiểm tra
                            </h6>
                        </div>
                        <div style="max-height:300px;overflow-y:auto">
                            @php
                                $deadlines = $data['upcoming_deadlines'] ?? [];
                                $quizzes = $data['pending_quizzes'] ?? [];
                            @endphp

                            @foreach ($deadlines as $dl)
                                <div class="todo-item">
                                    <div>
                                        <span class="bdg bdg--warning mb-1">Bài tập</span>
                                        <div class="todo-item__label">{{ $dl->title }}</div>
                                        <div class="todo-item__sub">
                                            <i class="fas fa-book me-1"></i>{{ $dl->course_title ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0">
                                        <div class="todo-item__deadline">
                                            <i class="fas fa-hourglass-half me-1"></i>
                                            {{ \Carbon\Carbon::parse($dl->due_date)->format('H:i - d/m/Y') }}
                                        </div>
                                        <a href="{{ route('courses.show', $dl->course_id ?? 0) }}"
                                            class="btn-xs btn-xs--warning">Nộp bài</a>
                                    </div>
                                </div>
                            @endforeach

                            @foreach ($quizzes as $quiz)
                                <div class="todo-item todo-item--quiz">
                                    <div>
                                        <span class="bdg bdg--primary mb-1">Kiểm tra</span>
                                        <div class="todo-item__label" style="color:var(--brand)">{{ $quiz->title }}
                                        </div>
                                        <div class="todo-item__sub">
                                            <i class="fas fa-book me-1"></i>{{ $quiz->course_title ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0">
                                        <div class="todo-item__time-limit">
                                            <i class="fas fa-stopwatch me-1"></i>{{ $quiz->time_limit }} phút
                                        </div>
                                        <a href="{{ route('courses.show', $quiz->course_id ?? 0) }}"
                                            class="btn-xs btn-xs--primary">Làm ngay</a>
                                    </div>
                                </div>
                            @endforeach

                            @if (count($deadlines) === 0 && count($quizzes) === 0)
                                <div class="empty-state">
                                    <i class="fas fa-glass-cheers" style="color:var(--success)"></i>
                                    <p>Tuyệt vời! Bạn đã hoàn thành hết các nhiệm vụ.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-chart-line" style="color:var(--brand)"></i>
                                Tiến độ khóa học của tôi
                            </h6>
                            <a href="{{ route('courses.index') }}" class="btn-xs btn-xs--primary">Vào học</a>
                        </div>
                        <div class="row g-0">
                            @forelse ($data['course_progress'] ?? [] as $course)
                                <div class="col-12 col-lg-6">
                                    <div class="compact-card">
                                        <div class="d-flex justify-content-between gap-2 mb-2">
                                            <div>
                                                <div class="fw-bold">{{ $course->title }}</div>
                                                <div class="text-muted small">{{ $course->lesson_completed }}/{{ $course->lesson_total }} bài học hoàn thành</div>
                                            </div>
                                            <span class="bdg bdg--primary">{{ $course->progress }}%</span>
                                        </div>
                                        <div class="progress-line">
                                            <span style="width: {{ $course->progress }}%"></span>
                                        </div>
                                        <div class="mt-3">
                                            <a href="{{ route('courses.show', $course->id) }}" class="btn-xs btn-xs--primary">
                                                Tiếp tục học
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="empty-state">
                                        <i class="fas fa-book-open"></i>
                                        <p>Bạn chưa được gán khóa học.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Weekly schedule --}}
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-calendar-check" style="color:var(--success)"></i>
                                Lịch học tuần này
                            </h6>
                            <span class="bdg bdg--success">Tuần này</span>
                        </div>
                        <div class="table-responsive">
                            <table class="tbl">
                                <thead>
                                    <tr>
                                        <th>Ngày</th>
                                        <th>Giờ học</th>
                                        <th>Môn / Lớp</th>
                                        <th>Phòng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['week_schedule'] ?? [] as $slot)
                                        @php
                                            $d = \Carbon\Carbon::parse($slot->schedule_date);
                                            $today = $d->isToday();
                                            $past = $d->isPast() && !$today;
                                        @endphp
                                        <tr class="{{ $today ? 'is-today' : ($past ? 'is-past' : '') }}">
                                            <td>
                                                <div class="fw-bold" style="font-size:.875rem">{{ $d->format('d/m/Y') }}
                                                </div>
                                                <div style="font-size:.78rem;color:var(--text-muted)">
                                                    {{ $d->translatedFormat('l') }}</div>
                                            </td>
                                            <td>
                                                <span style="font-size:.85rem;font-weight:700;color:var(--brand)">
                                                    {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}
                                                    – {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold" style="font-size:.875rem">{{ $slot->course_title }}
                                                </div>
                                                <div style="font-size:.78rem;color:var(--text-muted)">Lớp:
                                                    {{ $slot->class_name }}</div>
                                            </td>
                                            <td>
                                                @if ($today)
                                                    <span class="bdg bdg--success mb-1">Hôm nay</span><br>
                                                @elseif ($past)
                                                    <span class="bdg bdg--muted mb-1">Đã học</span><br>
                                                @endif
                                                <span class="bdg bdg--muted">{{ $slot->room ?? 'Online' }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state">
                                                    <i class="fas fa-calendar-times"></i>
                                                    <p><strong>Chưa có lịch học</strong><br>Lịch học sẽ hiển thị tại đây.
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quiz score chart --}}
            <div class="row g-3">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <i class="fas fa-chart-bar" style="color:var(--accent)"></i>
                                Điểm các bài kiểm tra gần đây
                            </h6>
                        </div>
                        <div class="chart-wrap">
                            @if (!empty($data['chart_quiz_data']))
                                <div id="studentChart"></div>
                            @else
                                <div class="empty-state">
                                    <i class="fas fa-chart-bar"></i>
                                    <p>Bạn chưa làm bài kiểm tra nào.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        @endif

    </div>{{-- /container-fluid --}}
    @push('scripts')
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const base = {
                    chart: {
                        fontFamily: 'inherit',
                        toolbar: {
                            show: false
                        }
                    },
                    legend: {
                        position: 'bottom',
                        fontSize: '13px'
                    },
                    dataLabels: {
                        enabled: true
                    },
                    tooltip: {
                        theme: 'light'
                    },
                };

                @if (auth()->user()->role === 'admin')
                    new ApexCharts(document.querySelector("#adminChart"), {
                        ...base,
                        series: @json($data['chart_role_data']),
                        labels: @json($data['chart_role_labels']),
                        chart: {
                            ...base.chart,
                            type: 'donut',
                            height: 280
                        },
                        colors: ['#3e80f9', '#06b6d4', '#6c757d'],
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '65%'
                                }
                            }
                        },
                    }).render();
                @elseif (auth()->user()->role === 'teacher')
                    new ApexCharts(document.querySelector("#teacherChart"), {
                        ...base,
                        series: @json($data['chart_submission_data']),
                        labels: @json($data['chart_submission_labels']),
                        chart: {
                            ...base.chart,
                            type: 'pie',
                            height: 280
                        },
                        colors: ['#10b981', '#ef4444'],
                    }).render();
                @else
                    @if (count($data['chart_quiz_data']) > 0)
                        new ApexCharts(document.querySelector("#studentChart"), {
                            ...base,
                            series: [{
                                name: 'Điểm số',
                                data: @json($data['chart_quiz_data'])
                            }],
                            chart: {
                                ...base.chart,
                                type: 'bar',
                                height: 300
                            },
                            xaxis: {
                                categories: @json($data['chart_quiz_labels'])
                            },
                            yaxis: {
                                max: 10,
                                tickAmount: 5
                            },
                            colors: ['#6f42c1'],
                            plotOptions: {
                                bar: {
                                    borderRadius: 6,
                                    columnWidth: '42%'
                                }
                            },
                        }).render();
                    @endif
                @endif
            });
        </script>
    @endpush
@endsection
