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

        /* Thay toàn bộ phần sidebar-hidden cũ bằng đoạn này */
        .sidebar.sidebar-collapsed {
            width: 60px !important;
        }

        .main-content.sidebar-collapsed {
            margin-left: 60px !important;
        }

        /* Ẩn chữ và nhãn section khi thu gọn */
        .sidebar.sidebar-collapsed .nav-link span,
        .sidebar.sidebar-collapsed .section-label {
            display: none;
        }

        /* Căn giữa icon khi thu gọn */
        .sidebar.sidebar-collapsed .nav-link {
            justify-content: center;
            padding: 10px 0;
            margin: 4px 6px;
        }

        /* Tooltip hiện tên tính năng khi hover */
        .sidebar.sidebar-collapsed .nav-link {
            position: relative;
        }

        .sidebar.sidebar-collapsed .nav-link::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%);
            background: #333;
            color: #fff;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 6px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s;
            z-index: 9999;
        }

        .sidebar.sidebar-collapsed .nav-link:hover::after {
            opacity: 1;
        }
    </style>

    @stack('styles')
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            @auth
                <button id="sidebarToggleBtn" class="btn btn-dark border-0 me-2" type="button" title="Ẩn/Hiện menu">
                    <i class="fas fa-bars"></i>
                </button>
            @endauth

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
                            <li>
                                <span class="dropdown-item-text small text-muted">
                                    Vai trò: {{ ucfirst(Auth::user()->role) }}
                                </span>
                            </li>

                            <!-- NÚT ĐỔI MẬT KHẨU MỚI THÊM VÀO ĐÂY -->
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                    data-bs-target="#changePasswordModal">
                                    <i class="fas fa-key me-2 text-warning"></i> Đổi mật khẩu
                                </a>
                            </li>

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
                            href="{{ route('dashboard') }}" data-tooltip="Dashboard">
                            <i class="fas fa-th-large"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('courses*') ? 'active' : '' }}"
                            href="{{ route('courses.index') }}" data-tooltip="Khóa học của tôi">
                            <i class="fas fa-book"></i> <span>Khóa học của tôi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('assignments*') ? 'active' : '' }}"
                            href="{{ route('assignments.index') }}" data-tooltip="Bài tập nộp">
                            <i class="fas fa-clipboard-list"></i> <span>Bài tập nộp</span>
                        </a>
                    </li>

                    @if (Auth::user()->role === 'admin' || Auth::user()->role === 'teacher')
                        <div class="px-4 mt-4 mb-2 small text-muted text-uppercase fw-bold section-label"
                            style="font-size: 0.7rem;">
                            Hệ thống
                        </div>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('classes*') ? 'active' : '' }}"
                                href="{{ Route::has('classes.index') ? route('classes.index') : '#' }}"
                                data-tooltip="Quản lý lớp học">
                                <i class="fas fa-chalkboard-teacher"></i> <span>Quản lý lớp học</span>
                            </a>
                        </li>
                        @if (Auth::user()->role === 'admin')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('users*') ? 'active' : '' }}"
                                    href="{{ Route::has('users.index') ? route('users.index') : '#' }}"
                                    data-tooltip="Quản lý người dùng">
                                    <i class="fas fa-users-cog"></i> <span>Quản lý người dùng</span>
                                </a>
                            </li>
                        @endif
                    @endif
                </ul>
            </nav>
        @endauth
        <!-- Modal Đổi Mật Khẩu / Xem Thông Tin -->
        @auth
            <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog" style="margin-top: 80px">
                    <form action="{{ route('profile.password.update') }}" method="POST"
                        class="modal-content border-0 shadow">
                        @csrf
                        @method('PUT')
                        <div class="modal-header bg-light border-0">
                            <h5 class="modal-title fw-bold"><i class="fas fa-user-shield me-2 text-primary"></i>Thông tin
                                tài khoản</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body py-4">

                            <!-- THÔNG TIN CỐ ĐỊNH (Không cho sửa) -->
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="small fw-bold text-muted">Họ và tên</label>
                                    <input type="text" class="form-control bg-light text-muted"
                                        value="{{ Auth::user()->name }}" readonly>
                                </div>
                                <div class="col-md-7">
                                    <label class="small fw-bold text-muted">Email</label>
                                    <input type="email" class="form-control bg-light text-muted"
                                        value="{{ Auth::user()->email }}" readonly>
                                </div>
                                <div class="col-md-5">
                                    <label class="small fw-bold text-muted">Vai trò</label>
                                    <input type="text" class="form-control bg-light text-muted"
                                        value="{{ strtoupper(Auth::user()->role) }}" readonly>
                                </div>
                            </div>

                            <hr class="text-muted">

                            <!-- FORM ĐỔI MẬT KHẨU -->
                            <h6 class="fw-bold mb-3">Đổi mật khẩu mới</h6>
                            <div class="mb-3">
                                <label class="small fw-bold">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                <input type="password" name="current_password" class="form-control"
                                    placeholder="Nhập mật khẩu cũ..." required>
                                @error('current_password')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold">Mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" name="new_password" class="form-control"
                                    placeholder="Tối thiểu 6 ký tự..." required minlength="6">
                                @error('new_password')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold">Xác nhận mật khẩu mới <span
                                        class="text-danger">*</span></label>
                                <!-- Lưu ý: name phải là new_password_confirmation để Laravel tự động kiểm tra khớp -->
                                <input type="password" name="new_password_confirmation" class="form-control"
                                    placeholder="Nhập lại mật khẩu mới..." required minlength="6">
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 w-100 fw-bold">CẬP NHẬT MẬT
                                KHẨU</button>
                        </div>
                    </form>
                </div>
            </div>
        @endauth

        <!-- Hiển thị lỗi Validation của Modal ngay khi load trang nếu có lỗi -->
        @if ($errors->has('current_password') || $errors->has('new_password'))
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var myModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
                    myModal.show();
                });
            </script>
        @endif

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
    <script>
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        let sidebarOpen = localStorage.getItem('sidebarOpen') !== 'false';

        function applySidebarState() {
            if (sidebarOpen) {
                sidebar?.classList.remove('sidebar-collapsed');
                mainContent?.classList.remove('sidebar-collapsed');
                toggleBtn?.classList.remove('rotated');
            } else {
                sidebar?.classList.add('sidebar-collapsed');
                mainContent?.classList.add('sidebar-collapsed');
                toggleBtn?.classList.add('rotated');
            }
        }

        applySidebarState();

        toggleBtn?.addEventListener('click', function() {
            sidebarOpen = !sidebarOpen;
            localStorage.setItem('sidebarOpen', sidebarOpen);
            applySidebarState();
        });
    </script>

    @stack('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    @stack('scripts')
</body>

</html>
