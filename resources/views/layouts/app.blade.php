<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SmartLMS') - Hệ thống quản lý học tập AI</title>

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://smartlms.io.vn/">
    <meta property="og:title" content="SmartLMS - Hệ thống học tập tích hợp AI">
    <meta property="og:description"
        content="Nền tảng quản lý giáo dục trực tuyến hỗ trợ huấn luyện AI dựa trên tài liệu học tập.">
    <meta property="og:image" content="{{ asset('favicon-v2.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon-v2.png') }}">

    {{-- <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet"> --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --sidebar-width: 255px;
            --navbar-height: 64px;
            --blue: #2563eb;
            --blue-light: #eff6ff;
            --blue-mid: #dbeafe;
            --surface: #ffffff;
            --bg: #f1f5f9;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --radius: 10px;
            --radius-lg: 14px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
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
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
        }

        .navbar-brand img {
            height: 44px;
            width: auto;
        }

        .user-btn {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--blue-light);
            border: 1px solid var(--blue-mid);
            border-radius: 999px;
            padding: 6px 16px 6px 8px;
            cursor: pointer;
            transition: background 0.15s;
            font-size: 14px;
            font-weight: 500;
            color: var(--blue);
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
            border-right: 1px solid var(--border);
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 16px 0 24px;
            transition: transform 0.3s cubic-bezier(.4, 0, .2, 1);
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
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 20px 20px 6px;
        }

        /* Nav links */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            margin: 1px 10px;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
        }

        .nav-link i {
            width: 20px;
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
            margin: 2px 10px 4px 38px !important;
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
            font-family: 'DM Sans', sans-serif;
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
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background 0.15s;
            width: 100%;
        }

        .btn-primary-solid:hover {
            background: #1d4ed8;
        }

        /* ── Alerts ── */
        .alert-success {
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

        /* ── Mobile ── */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0 !important;
                padding: 20px 16px;
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

            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('smartlms-logo-sharpened.png') }}" alt="SmartLMS">
            </a>

            <div class="user-btn dropdown ms-auto" data-bs-toggle="dropdown" id="userMenuBtn" aria-expanded="false">
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
                    <a class="nav-link {{ request()->is('courses*') ? 'active' : '' }}"
                        href="{{ route('courses.index') }}">
                        <i class="fas fa-graduation-cap"></i> Khóa học của tôi
                    </a>
                </li>

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
                    @if (Auth::user()->role === 'admin')
                        <li>
                            <a class="nav-link {{ request()->is('users*') ? 'active' : '' }}"
                                href="{{ route('users.index') }}">
                                <i class="fas fa-user-cog"></i> Quản lý người dùng
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
    @endauth

    <div class="wrapper">
        <main class="{{ Auth::check() ? 'main-content' : '' }}">
            <div class="container-fluid p-0">
                @if (session('success'))
                    <div class="alert-success">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
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
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        @if ($errors->any())
            (new bootstrap.Modal(document.getElementById('changePasswordModal'))).show();
        @endif
    </script>
</body>

</html>
