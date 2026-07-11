<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SmartLMS - Hệ thống quản lý học tập AI thông minh')</title>
    <meta name="description" content="@yield('meta_description', 'SmartLMS – Nền tảng quản lý học tập tích hợp AI. Quản lý lớp học, giao bài tập, ngân hàng câu hỏi và theo dõi kết quả học tập thông minh.')">
    <meta name="keywords" content="LMS, quản lý học tập, hệ thống giáo dục AI, e-learning, SmartLMS">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://smartlms.io.vn/">
    <meta property="og:title" content="SmartLMS - Hệ thống học tập tích hợp AI">
    <meta property="og:description"
        content="Nền tảng quản lý giáo dục trực tuyến hỗ trợ huấn luyện AI dựa trên tài liệu học tập.">
    <meta property="og:image" content="{{ asset('favicon-v2.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon-v2.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --sidebar-width: 272px;
            --sidebar-collapsed-width: 82px;
            --navbar-height: 72px;
            --blue: #2563eb;
            --blue-light: #eff6ff;
            --blue-mid: #dbeafe;
            --surface: #ffffff;
            --bg: #f6f8fc;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --radius: 14px;
            --radius-lg: 20px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background: var(--bg);
            margin: 0;
            color: var(--text);
            font-size: 15px;
        }

        /* ── Navbar ── */
        .navbar {
            height: var(--navbar-height);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1060;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(226, 232, 240, .8);
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 0 28px;
        }

        .navbar-brand img {
            height: 42px;
            width: auto;
        }

        .topbar-search {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: var(--muted);
            display: flex;
            flex: 1 1 360px;
            gap: 10px;
            max-width: 520px;
            min-height: 42px;
            padding: 0 16px;
        }

        .topbar-search input {
            background: transparent;
            border: 0;
            color: var(--text);
            flex: 1;
            font-size: 14px;
            min-width: 0;
            outline: none;
        }

        .topbar-action {
            align-items: center;
            background: var(--blue);
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            gap: 8px;
            min-height: 42px;
            padding: 0 16px;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 12px 24px rgba(37, 99, 235, .22);
        }

        .topbar-action:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .topbar-icon-btn {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: var(--muted);
            display: inline-flex;
            height: 42px;
            justify-content: center;
            position: relative;
            width: 42px;
            transition: background .2s, color .2s, border-color .2s, box-shadow .2s, transform .2s;
        }

        .topbar-icon-btn:hover { color:#2563eb; transform:translateY(-2px); box-shadow:0 8px 20px rgba(37,99,235,.16); }
        .topbar-icon-btn.has-unread { background:linear-gradient(135deg,#2563eb,#7c3aed); border-color:transparent; box-shadow:0 10px 24px rgba(37,99,235,.34); color:#fff; }
        .topbar-icon-btn.has-unread:hover { color:#fff; box-shadow:0 13px 30px rgba(37,99,235,.44); }
        .topbar-icon-btn.has-unread::before { animation:notificationPulse 2.4s ease-out infinite; border:2px solid rgba(37,99,235,.5); border-radius:999px; content:''; inset:-5px; pointer-events:none; position:absolute; }
        .topbar-icon-btn.has-unread i { animation:notificationBell 4s ease-in-out infinite; transform-origin:50% 10%; }
        .topbar-icon-btn.has-unread::after { display:none; }

        @keyframes notificationPulse { 0%{opacity:.7;transform:scale(.85)} 70%,100%{opacity:0;transform:scale(1.3)} }
        @keyframes notificationBell { 0%,82%,100%{transform:rotate(0)} 86%{transform:rotate(14deg)} 90%{transform:rotate(-12deg)} 94%{transform:rotate(8deg)} 97%{transform:rotate(-5deg)} }

        @media (prefers-reduced-motion: reduce) {
            .topbar-icon-btn.has-unread::before,
            .topbar-icon-btn.has-unread i { animation:none; }
        }

        .topbar-icon-btn::after {
            background: #ef4444;
            border: 2px solid #fff;
            border-radius: 999px;
            content: '';
            height: 9px;
            position: absolute;
            right: 9px;
            top: 9px;
            width: 9px;
        }

        .topbar-icon-btn.no-unread::after { display: none; }
        .notification-menu { width: min(390px, calc(100vw - 24px)); padding: 0; overflow: hidden; }
        .notification-menu-head { align-items:center; display:flex; justify-content:space-between; padding:14px 16px; border-bottom:1px solid #e2e8f0; }
        .notification-menu-title { font-size:14px; font-weight:800; margin:0; }
        .notification-badge { align-items:center; background:#ef4444; border:2px solid #fff; border-radius:999px; box-shadow:0 4px 10px rgba(239,68,68,.36); color:#fff; display:flex; font-size:10px; font-weight:900; height:21px; justify-content:center; min-width:21px; padding:0 5px; position:absolute; right:-6px; top:-7px; z-index:2; }
        .notification-item { border-bottom:1px solid #f1f5f9; display:block; padding:12px 16px; text-decoration:none; white-space:normal; }
        .notification-item.unread { background:#eff6ff; }
        .notification-item-title { color:#0f172a; font-size:13px; font-weight:750; margin-bottom:3px; }
        .notification-item-message { color:#64748b; font-size:12px; line-height:1.45; }
        .notification-item-time { color:#94a3b8; font-size:11px; margin-top:5px; }
        .notification-menu-footer { display:block; font-size:12px; font-weight:750; padding:12px; text-align:center; text-decoration:none; }

        .user-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 6px 16px 6px 8px;
            cursor: pointer;
            transition: background 0.15s;
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
        }

        .user-btn:hover {
            background: var(--blue-mid);
        }

        .avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--blue);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-width);
            height: calc(100vh - var(--navbar-height));
            background: var(--surface);
            border-right: 1px solid rgba(226, 232, 240, .9);
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 18px 12px 26px;
            transition: width 0.3s cubic-bezier(.4, 0, .2, 1), transform 0.3s cubic-bezier(.4, 0, .2, 1);
        }

        .sidebar-collapse-btn {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 999px;
            color: var(--text);
            cursor: pointer;
            display: inline-flex;
            flex: 0 0 42px;
            font-size: 19px;
            height: 42px;
            justify-content: center;
            transition: background .2s, color .2s;
            width: 42px;
        }

        .sidebar-collapse-btn:hover {
            background: var(--blue-light);
            color: var(--blue);
        }

        body.sidebar-collapsed .sidebar {
            width: var(--sidebar-collapsed-width);
            padding-left: 10px;
            padding-right: 10px;
        }

        body.sidebar-collapsed .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        body.sidebar-collapsed .sidebar .nav-link {
            font-size: 0;
            gap: 0;
            justify-content: center;
            min-height: 48px;
            padding: 11px 8px;
            position: relative;
        }

        body.sidebar-collapsed .sidebar .nav-link > i:not(.chevron) {
            font-size: 18px;
            width: 26px;
        }

        body.sidebar-collapsed .sidebar .nav-section,
        body.sidebar-collapsed .sidebar .chevron,
        body.sidebar-collapsed .sidebar .collapse {
            display: none !important;
        }

        body.sidebar-collapsed .sidebar .nav-link.active {
            box-shadow: 0 9px 20px rgba(37, 99, 235, .24);
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        /* Section labels */
        .nav-section {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 22px 14px 8px;
        }

        /* Nav links */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 12px;
            margin: 3px 0;
            border-radius: 16px;
            font-size: 14.5px;
            font-weight: 700;
            color: var(--muted);
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
        }

        .nav-link i {
            width: 22px;
            text-align: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .nav-link:hover {
            background: var(--blue-light);
            color: var(--blue);
        }

        .nav-link.active {
            background: var(--blue);
            color: #fff;
            box-shadow: 0 14px 26px rgba(37, 99, 235, .24);
        }

        /* Chevron toggle */
        .nav-link .chevron {
            margin-left: auto;
            font-size: 11px;
            transition: transform 0.25s;
        }

        .nav-link[aria-expanded="true"] .chevron {
            transform: rotate(180deg);
        }

        /* Sub-menu */
        .sub-menu {
            margin: 4px 0 6px 34px !important;
            border-left: 1.5px solid var(--border);
            padding-left: 8px !important;
        }

        .sub-menu .nav-link {
            margin: 1px 0;
            padding: 7px 10px;
            font-size: 13.5px;
            font-weight: 400;
            color: var(--muted);
        }

        .sub-menu .nav-link:hover {
            color: var(--blue);
            background: var(--blue-light);
        }

        .sub-menu .nav-link.active-sub {
            color: var(--blue);
            font-weight: 500;
        }

        /* ── Main content ── */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 28px 28px 40px;
            margin-top: var(--navbar-height);
            min-height: calc(100vh - var(--navbar-height));
            transition: margin-left 0.3s cubic-bezier(.4, 0, .2, 1);
        }

        /* ── Dropdown menu ── */
        .dropdown-menu {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            padding: 6px;
            min-width: 200px;
        }

        .dropdown-item {
            border-radius: var(--radius);
            padding: 8px 14px;
            font-size: 14px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dropdown-item:hover {
            background: var(--blue-light);
            color: var(--blue);
        }

        .dropdown-item.text-danger:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        .dropdown-divider {
            border-color: var(--border);
            margin: 4px 0;
        }

        /* ── Modal ── */
        .modal-content {
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 20px 24px 0;
            border: none;
        }

        .modal-body {
            padding: 16px 24px;
        }

        .modal-footer {
            padding: 0 24px 20px;
            border: none;
        }

        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 5px;
        }

        .form-control {
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 9px 13px;
            font-size: 14px;
            font-family: 'Be Vietnam Pro', sans-serif;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-control:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            outline: none;
        }

        .form-control[readonly] {
            background: var(--bg);
            color: var(--muted);
            cursor: not-allowed;
        }

        .form-control.is-invalid {
            border-color: #dc2626;
        }

        .invalid-feedback {
            font-size: 12.5px;
            color: #dc2626;
            margin-top: 4px;
        }

        .btn-primary-solid {
            background: var(--blue);
            color: #fff;
            border: none;
            border-radius: var(--radius);
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Be Vietnam Pro', sans-serif;
            cursor: pointer;
            transition: background 0.15s;
            width: 100%;
        }

        .btn-primary-solid:hover {
            background: #1d4ed8;
        }

        /* ── Alerts ── */
        .alert-success,
        .alert-error {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            border-radius: var(--radius);
            padding: 12px 16px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }

        /* ── Mobile ── */
        @media (max-width: 768px) {
            body.sidebar-collapsed .sidebar {
                width: var(--sidebar-width);
                padding: 18px 12px 26px;
            }

            body.sidebar-collapsed .sidebar .nav-link {
                font-size: 14.5px;
                gap: 12px;
                justify-content: flex-start;
                padding: 11px 12px;
            }

            body.sidebar-collapsed .sidebar .nav-link > i:not(.chevron) {
                font-size: 15px;
                width: 22px;
            }

            body.sidebar-collapsed .sidebar .nav-section {
                display: block !important;
            }

            body.sidebar-collapsed .sidebar .chevron {
                display: inline-block !important;
            }

            body.sidebar-collapsed .sidebar .collapse.show {
                display: block !important;
            }

            .sidebar-collapse-btn {
                display: none;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0 !important;
                padding: 18px 14px;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .hamburger {
                display: flex !important;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                border-radius: var(--radius);
                background: var(--bg);
                border: 1px solid var(--border);
                cursor: pointer;
                font-size: 16px;
                color: var(--text);
            }
        }

        .hamburger {
            display: none;
        }

        .page-transition {
            align-items: center;
            background: #ffffff;
            display: flex;
            inset: 0;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            position: fixed;
            transition: opacity .18s ease;
            z-index: 3000;
        }

        .page-transition.is-active {
            opacity: 1;
            pointer-events: auto;
        }

        .page-transition__card {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .page-transition__image {
            display: block;
            height: 400px;
            object-fit: contain;
            width: 400px;
        }

        @media (max-width: 768px) {
            .page-transition__image {
                height: 190px;
                width: 190px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .page-transition {
                display: none;
            }
        }

        @media (max-width: 992px) {
            .topbar-search,
            .topbar-action {
                display: none;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @stack('styles')
</head>

<body>
    @auth
        {{-- ── Navbar ── --}}
        <nav class="navbar">
            <button class="hamburger me-3" id="sidebarToggle" aria-label="Mở menu">
                <i class="fas fa-bars"></i>
            </button>

            <button class="sidebar-collapse-btn" id="sidebarCollapseToggle" type="button"
                aria-label="Thu gọn menu" title="Thu gọn menu" aria-expanded="true">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>

            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('smartlms-logo-sharpened.png') }}" alt="SmartLMS">
            </a>

            <div class="dropdown ms-auto">
                <button class="topbar-icon-btn {{ ($topbarUnreadCount ?? 0) === 0 ? 'no-unread' : 'has-unread' }}" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thông báo">
                    <i class="fas fa-bell"></i>
                    @if (($topbarUnreadCount ?? 0) > 0)
                        <span class="notification-badge">{{ $topbarUnreadCount > 99 ? '99+' : $topbarUnreadCount }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-menu mt-2">
                    <div class="notification-menu-head">
                        <h6 class="notification-menu-title">Thông báo</h6>
                        <span class="text-muted small">{{ $topbarUnreadCount ?? 0 }} chưa đọc</span>
                    </div>
                    @forelse (($topbarNotifications ?? collect()) as $notification)
                        <a class="notification-item {{ $notification->read_at ? '' : 'unread' }}"
                            href="{{ route('notifications.open', $notification) }}">
                            <div class="notification-item-title">{{ $notification->title }}</div>
                            <div class="notification-item-message">{{ Str::limit($notification->message, 105) }}</div>
                            <div class="notification-item-time">{{ $notification->created_at->diffForHumans() }}</div>
                        </a>
                    @empty
                        <div class="text-center text-muted small py-4">Chưa có thông báo.</div>
                    @endforelse
                    <a class="notification-menu-footer" href="{{ route('notifications.index') }}">Xem tất cả thông báo</a>
                </div>
            </div>

            <div class="user-btn dropdown" data-bs-toggle="dropdown" id="userMenuBtn" aria-expanded="false">
                <div class="avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                <span>{{ Auth::user()->name }}</span>
                <i class="fas fa-chevron-down" style="font-size:11px; opacity:.6;"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end mt-2" aria-labelledby="userMenuBtn">
                <li>
                    <span class="dropdown-item text-muted"
                        style="font-size:12px; font-weight:600; letter-spacing:.05em; cursor:default;">
                        {{ strtoupper(Auth::user()->role) }}
                    </span>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fas fa-key" style="color:#f59e0b;"></i> Đổi mật khẩu
                    </a>
                </li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"
                            style="background:none; border:none; width:100%; text-align:left;">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </button>
                    </form>
                </li>
            </ul>
        </nav>

        {{-- ── Sidebar ── --}}
        <aside class="sidebar" id="sidebar">
            <ul class="nav flex-column" style="list-style:none; padding:0; margin:0;">

                {{-- Main --}}
                <li>
                    <a class="nav-link {{ request()->is('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="fas fa-house-chimney"></i> Trang chủ
                    </a>
                </li>
                <li>
                    <a class="nav-link {{ request()->is('courses*') && !request()->routeIs('courses.materials.*') ? 'active' : '' }}"
                        href="{{ route('courses.index') }}">
                        <i class="fas fa-graduation-cap"></i> Khóa học của tôi
                    </a>
                </li>
                <li>
                    <a class="nav-link {{ request()->routeIs('materials.index') || request()->routeIs('courses.materials.*') ? 'active' : '' }}"
                        href="{{ route('materials.index') }}">
                        <i class="fas fa-folder-open"></i> Kho học liệu
                    </a>
                </li>
                @if (Auth::user()->role === 'student')
                    <li>
                        <a class="nav-link {{ request()->routeIs('students.schedule') ? 'active' : '' }}"
                            href="{{ route('students.schedule') }}">
                            <i class="fas fa-calendar-days"></i> Lịch học cá nhân
                        </a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->routeIs('students.grades') ? 'active' : '' }}"
                            href="{{ route('students.grades') }}">
                            <i class="fas fa-chart-line"></i> Điểm & nhận xét
                        </a>
                    </li>
                @endif

                {{-- Công cụ hỗ trợ --}}
                <li>
                    <a class="nav-link {{ request()->routeIs('tools.grade-calculator') ? 'active' : '' }}"
                        data-bs-toggle="collapse" href="#toolsMenu" role="button"
                        aria-expanded="{{ request()->routeIs('tools.grade-calculator') ? 'true' : 'false' }}">
                        <i class="fas fa-toolbox"></i> Công cụ hỗ trợ
                        <i class="fas fa-chevron-down chevron"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('tools.grade-calculator') ? 'show' : '' }}" id="toolsMenu">
                        <ul class="sub-menu" style="list-style:none; padding:0; margin:0;">
                            <li>
                                <a class="nav-link {{ request()->routeIs('tools.grade-calculator') ? 'active-sub' : '' }}"
                                    href="{{ route('tools.grade-calculator') }}">
                                    <i class="fas fa-calculator"></i> Tính điểm nghề
                                </a>
                            </li>
                            <li>
                                <a class="nav-link" href="https://ngotanloi.my.canva.site/code-editer" target="_blank"
                                    rel="noopener">
                                    <i class="fas fa-code"></i> Trình soạn thảo Code
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Góc giải trí --}}
                <li>
                    <a class="nav-link {{ request()->is('tools/chess*') || request()->is('tools/caro*') ? 'active' : '' }}"
                        data-bs-toggle="collapse" href="#entertainmentMenu" role="button"
                        aria-expanded="{{ request()->is('tools/chess*') || request()->is('tools/caro*') ? 'true' : 'false' }}">
                        <i class="fas fa-gamepad"></i> Góc giải trí
                        <i class="fas fa-chevron-down chevron"></i>
                    </a>
                    <div class="collapse {{ request()->is('tools/chess*') || request()->is('tools/caro*') ? 'show' : '' }}"
                        id="entertainmentMenu">
                        <ul class="sub-menu" style="list-style:none; padding:0; margin:0;">
                            <li>
                                <a class="nav-link {{ request()->is('tools/chess*') ? 'active-sub' : '' }}"
                                    href="{{ route('tools.chess.index') }}">
                                    <i class="fas fa-chess"></i> Cờ vua
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ request()->is('tools/caro*') ? 'active-sub' : '' }}"
                                    href="{{ route('tools.caro.index') }}">
                                    <i class="fas fa-times-circle"></i> Cờ Caro
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Admin / Teacher sections --}}
                @if (in_array(Auth::user()->role, ['admin', 'teacher']))
                    <div class="nav-section">Hệ thống AI</div>
                    <li>
                        <a class="nav-link {{ request()->is('documents*') ? 'active' : '' }}"
                            href="{{ route('documents.upload') }}">
                            <i class="fas fa-robot"></i> Huấn luyện AI
                        </a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->is('question-bank*') ? 'active' : '' }}"
                            href="{{ route('questions.index') }}">
                            <i class="fas fa-layer-group"></i> Ngân hàng câu hỏi
                        </a>
                    </li>

                    <div class="nav-section">Quản lý</div>
                    <li>
                        <a class="nav-link {{ request()->is('programs*') ? 'active' : '' }}"
                            href="{{ route('programs.index') }}">
                            <i class="fas fa-sitemap"></i> Chương trình học
                        </a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->is('classes*') ? 'active' : '' }}"
                            href="{{ route('classes.index') }}">
                            <i class="fas fa-chalkboard"></i> Quản lý lớp học
                        </a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->is('schedules*') ? 'active' : '' }}"
                            href="{{ Route::has('schedules.index') ? route('schedules.index') : '#' }}">
                            <i class="fas fa-calendar-alt"></i> Quản lý lịch học
                        </a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->is('teaching*') ? 'active' : '' }}"
                            href="{{ route('teaching.index') }}">
                            <i class="fas fa-briefcase"></i> Giảng dạy
                        </a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->is('payments*') ? 'active' : '' }}"
                            href="{{ route('payments.index') }}">
                            <i class="fas fa-file-invoice-dollar"></i> Thanh toán
                        </a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->is('operations/dashboard*') ? 'active' : '' }}"
                            href="{{ route('operations.dashboard') }}">
                            <i class="fas fa-chart-pie"></i> Dashboard vận hành
                        </a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->is('reports/operations*') ? 'active' : '' }}"
                            href="{{ route('reports.operations') }}">
                            <i class="fas fa-chart-column"></i> Báo cáo vận hành
                        </a>
                    </li>
                    @if (Auth::user()->role === 'admin')
                        <li>
                            <a class="nav-link {{ request()->is('users*') ? 'active' : '' }}"
                                href="{{ route('users.index') }}">
                                <i class="fas fa-user-cog"></i> Quản lý người dùng
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ request()->is('system/storage*') ? 'active' : '' }}"
                                href="{{ route('system.storage.index') }}">
                                <i class="fas fa-cloud"></i> Kiểm tra lưu trữ
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ request()->is('system/backups*') ? 'active' : '' }}"
                                href="{{ route('system.backups.index') }}">
                                <i class="fas fa-database"></i> Backup dữ liệu
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ request()->is('audit-logs*') ? 'active' : '' }}"
                                href="{{ route('audit-logs.index') }}">
                                <i class="fas fa-shield-halved"></i> Audit log
                            </a>
                        </li>
                    @endif
                @endif
            </ul>
        </aside>

        {{-- ── Modal đổi mật khẩu ── --}}
        <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('profile.password.update') }}" method="POST" class="modal-content">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title" style="font-size:17px; font-weight:600;">
                            <i class="fas fa-user-shield me-2" style="color:var(--blue);"></i>Thông tin tài khoản
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="{{ Auth::user()->email }}" readonly>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Vai trò</label>
                                <input type="text" class="form-control" value="{{ strtoupper(Auth::user()->role) }}"
                                    readonly>
                            </div>
                            @if (Auth::user()->username)
                                <div class="col-12">
                                    <label class="form-label">Tên đăng nhập</label>
                                    <input type="text" class="form-control" value="{{ Auth::user()->username }}" readonly>
                                </div>
                            @endif
                            @if (Auth::user()->student_code)
                                <div class="col-12">
                                    <label class="form-label">Mã học sinh</label>
                                    <input type="text" class="form-control" value="{{ Auth::user()->student_code }}" readonly>
                                </div>
                            @endif
                        </div>

                        <hr style="border-color: var(--border); margin: 16px 0;">
                        <p style="font-size:14px; font-weight:600; margin-bottom:14px;">Đổi mật khẩu mới</p>

                        <div class="mb-3">
                            <label class="form-label">Mật khẩu hiện tại <span style="color:#dc2626">*</span></label>
                            <input type="password" name="current_password"
                                class="form-control @error('current_password') is-invalid @enderror"
                                placeholder="Nhập mật khẩu cũ để xác nhận" required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới <span style="color:#dc2626">*</span></label>
                            <input type="password" name="new_password"
                                class="form-control @error('new_password') is-invalid @enderror"
                                placeholder="Tối thiểu 6 ký tự" required minlength="6">
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-1">
                            <label class="form-label">Xác nhận mật khẩu mới <span style="color:#dc2626">*</span></label>
                            <input type="password" name="new_password_confirmation" class="form-control"
                                placeholder="Nhập lại mật khẩu mới" required minlength="6">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn-primary-solid">Cập nhật mật khẩu</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="page-transition" id="pageTransition" aria-hidden="true">
            <div class="page-transition__card">
                <img class="page-transition__image" src="{{ asset('preloader.gif') }}" alt="" aria-hidden="true">
            </div>
        </div>
    @endauth

    <div class="wrapper">
        <main class="{{ Auth::check() ? 'main-content' : '' }}">
            <div class="container-fluid p-0">
                @if (session('success'))
                    <div class="alert-success">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    @auth
        @include('partials.chatbot')
    @endauth

    @stack('scripts')

    <script>
        (() => {
            const body = document.body;
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarCollapseToggle');
            if (!sidebar || !toggle) return;

            const storageKey = 'smartlms.sidebarCollapsed';
            const applyState = (collapsed, persist = true) => {
                body.classList.toggle('sidebar-collapsed', collapsed && window.innerWidth > 768);
                document.documentElement.classList.remove('sidebar-will-collapse');
                toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                toggle.setAttribute('aria-label', collapsed ? 'Mở rộng menu' : 'Thu gọn menu');
                toggle.setAttribute('title', collapsed ? 'Mở rộng menu' : 'Thu gọn menu');
                if (persist) {
                    try { localStorage.setItem(storageKey, collapsed ? '1' : '0'); } catch (error) {}
                }
            };

            let storedCollapsed = false;
            try { storedCollapsed = localStorage.getItem(storageKey) === '1'; } catch (error) {}
            applyState(storedCollapsed, false);

            sidebar.querySelectorAll('.nav-link').forEach((link) => {
                const label = link.textContent.replace(/\s+/g, ' ').trim();
                if (label) {
                    link.setAttribute('title', label);
                    link.setAttribute('aria-label', label);
                }
            });

            toggle.addEventListener('click', () => applyState(!body.classList.contains('sidebar-collapsed')));

            sidebar.querySelectorAll('[data-bs-toggle="collapse"]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    if (window.innerWidth > 768 && body.classList.contains('sidebar-collapsed')) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        applyState(false);
                    }
                }, true);
            });

            window.addEventListener('resize', () => applyState(storedCollapsed = (() => {
                try { return localStorage.getItem(storageKey) === '1'; } catch (error) { return false; }
            })(), false));
        })();

        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        (() => {
            const overlay = document.getElementById('pageTransition');
            if (!overlay || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            const currentUrl = new URL(window.location.href);

            const shouldAnimate = (link, event) => {
                if (!link || event.defaultPrevented || event.button !== 0) return false;
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
                if (link.target && link.target !== '_self') return false;
                if (link.hasAttribute('download')) return false;
                if (link.dataset.fileDownload !== undefined) return false;
                if (link.dataset.bsToggle || link.dataset.noPageTransition !== undefined) return false;

                const href = link.getAttribute('href');
                if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:')) return false;

                const targetUrl = new URL(link.href, window.location.href);
                if (targetUrl.origin !== window.location.origin) return false;
                if (/^\/materials\/[^/]+\/download\/?$/.test(targetUrl.pathname)) return false;
                if (/^\/lessons\/[^/]+\/attachment\/?$/.test(targetUrl.pathname)) return false;
                if (/^\/courses\/[^/]+\/attendance\/export\/?$/.test(targetUrl.pathname)) return false;
                if (targetUrl.href === currentUrl.href) return false;
                if (targetUrl.pathname === currentUrl.pathname && targetUrl.search === currentUrl.search && targetUrl.hash) return false;

                return true;
            };

            const showTransition = () => {
                overlay.classList.add('is-active');
                overlay.setAttribute('aria-hidden', 'false');
            };

            document.addEventListener('click', function(event) {
                const link = event.target.closest('a[href]');
                if (!shouldAnimate(link, event)) return;

                event.preventDefault();
                showTransition();

                window.setTimeout(() => {
                    window.location.href = link.href;
                }, 420);
            });

            window.addEventListener('pageshow', function() {
                overlay.classList.remove('is-active');
                overlay.setAttribute('aria-hidden', 'true');
            });
        })();

        @if ($errors->any())
            (new bootstrap.Modal(document.getElementById('changePasswordModal'))).show();
        @endif
    </script>
</body>

</html>
