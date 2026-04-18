<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LMS PRO') - Hệ thống học tập</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --sidebar-width: 250px;
            --navbar-height: 56px;
        }

        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            margin: 0;
        }

        /* Navbar luôn nằm trên cùng */
        .navbar {
            z-index: 1060;
            height: var(--navbar-height);
        }

        /* Sidebar cố định bên trái */
        .sidebar {
            width: var(--sidebar-width);
            height: calc(100vh - var(--navbar-height));
            background: white;
            border-right: 1px solid #dee2e6;
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s;
        }

        /* Nội dung chính: Đẩy lùi sang phải bằng đúng độ rộng Sidebar */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            min-height: calc(100vh - var(--navbar-height));
            transition: all 0.3s;
        }

        /* Khi không đăng nhập (Trang Login) */
        .full-width {
            margin-left: 0 !important;
            width: 100%;
        }

        .nav-link {
            color: #333;
            border-radius: 8px;
            margin: 4px 10px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
        }

        .nav-link:hover {
            background-color: #f0f2f5;
        }

        .nav-link.active {
            background-color: #0d6efd;
            color: white !important;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
        }

        .nav-link i {
            width: 25px;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ Auth::check() ? route('dashboard') : url('/') }}">
                <i class="fas fa-graduation-cap me-2 text-primary"></i>LMS System
            </a>

            <div class="ms-auto d-flex align-items-center">
                @auth
                    <div class="dropdown">
                        <button class="btn btn-dark dropdown-toggle border-0" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> {{ Auth::user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li><span class="dropdown-item-text small text-muted">Vai trò:
                                    {{ ucfirst(Auth::user()->role) }}</span></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm px-3">Đăng nhập</a>
                @endauth
            </div>
        </div>
    </nav>

    <div class="wrapper">
        @auth
            <nav class="sidebar py-2 d-none d-md-block shadow-sm">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('dashboard*') ? 'active' : '' }}"
                            href="{{ route('dashboard') }}">
                            <i class="fas fa-th-large"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('courses*') ? 'active' : '' }}"
                            href="{{ route('courses.index') }}">
                            <i class="fas fa-book"></i> Khóa học của tôi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('assignments*') ? 'active' : '' }}" href="#">
                            <i class="fas fa-clipboard-list"></i> Bài tập nộp
                        </a>
                    </li>

                    @if (Auth::user()->role === 'admin' || Auth::user()->role === 'teacher')
                        <div class="px-4 mt-4 mb-2 small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Hệ
                            thống</div>

                        <!-- Cả Admin và Teacher đều quản lý được lớp học -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('classes*') ? 'active' : '' }}"
                                href="{{ Route::has('classes.index') ? route('classes.index') : '#' }}">
                                <i class="fas fa-chalkboard-teacher"></i> Quản lý lớp học
                            </a>
                        </li>

                        <!-- Chỉ Admin mới được quản lý toàn bộ hệ thống Người dùng -->
                        @if (Auth::user()->role === 'admin')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('users*') ? 'active' : '' }}"
                                    href="{{ Route::has('users.index') ? route('users.index') : '#' }}">
                                    <i class="fas fa-users-cog"></i> Quản lý người dùng
                                </a>
                            </li>
                        @endif
                    @endif
                </ul>
            </nav>
        @endauth

        <main class="{{ Auth::check() ? 'main-content' : 'full-width p-4' }}"
            style="margin-top: var(--navbar-height);">
            <div class="container-fluid">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
                        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    @stack('scripts')
</body>

</html>
