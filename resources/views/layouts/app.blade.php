<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Learning Management System') - SmartLMS</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon-v2.png') }}">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --sidebar-width: 260px;
            --navbar-height: 70px;
            --primary-navy: #3e80f9;
            --accent-blue: #0d6efd;
            --bg-light: #f4f7f9;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            margin: 0;
            color: #2d3436;
        }

        /* Navbar */
        .navbar {
            height: var(--navbar-height);
            z-index: 1060;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
        }

        .btn-navy {
            background-color: var(--primary-navy);
            color: white;
            transition: all 0.2s;
        }

        /* Sidebar Chuyên Nghiệp */
        .sidebar {
            width: var(--sidebar-width);
            height: calc(100vh - var(--navbar-height));
            background: white;
            border-right: 1px solid rgba(0, 0, 0, 0.05);
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            z-index: 1000;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            margin-top: var(--navbar-height);
            min-height: calc(100vh - var(--navbar-height));
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #ecf2fe;
        }

        /* Menu Cấp 1 */
        .nav-link {
            color: #636e72;
            font-weight: 500;
            border-radius: 10px;
            margin: 4px 15px;
            padding: 10px 15px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }

        .nav-link:hover {
            background-color: #f0f4f8;
            color: var(--primary-navy);
        }

        .nav-link.active {
            background-color: var(--primary-navy) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(62, 128, 249, 0.2);
        }

        .nav-link i {
            width: 28px;
            font-size: 1.1rem;
        }

        /* Dropdown Công cụ */
        #toolsMenu {
            /* border-left: 2px solid #edf2f7; */
            margin-bottom: 10px;
            padding-left: 5px;
            margin-left: 30px;
        }

        #toolsMenu .nav-link {
            margin: 2px 10px 2px 0 !important;
            padding: 8px 12px !important;
            font-size: 0.85rem !important;
            color: #718096;
            background: transparent !important;
            box-shadow: none !important;
        }

        #toolsMenu .nav-link:hover {
            color: var(--primary-navy) !important;
            background: #f7fafc !important;
        }

        .transition-all {
            transition: transform 0.3s ease;
        }

        .fa-rotate-180 {
            transform: rotate(180deg);
        }

        .modal-content {
            border-radius: 20px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0 !important;
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @stack('styles')
</head>

<body>
    @auth
        <nav class="navbar navbar-expand-lg navbar-light fixed-top border-bottom shadow-sm">
            <div class="container-fluid px-4">
                <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
                    <img src="{{ asset('smartlms-logo-sharpened.png') }}" alt="Smart LMS"
                        style="height: 55px; width: auto;">
                </a>
                <div class="ms-auto d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-navy dropdown-toggle rounded-pill px-3" type="button"
                            data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i> {{ Auth::user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-3 p-2"
                            style="border-radius: 15px;">
                            <li><span
                                    class="dropdown-item-text small text-muted text-center fw-bold">{{ strtoupper(Auth::user()->role) }}</span>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item py-2 rounded-3" href="#" data-bs-toggle="modal"
                                    data-bs-target="#changePasswordModal"><i class="fas fa-key me-2 text-warning"></i> Đổi
                                    mật khẩu</a></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger py-2 rounded-3"><i
                                            class="fas fa-sign-out-alt me-2"></i> Đăng xuất</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    @endauth

    <div class="wrapper">
        @auth
            <aside class="sidebar py-3 shadow-sm">
                <div class="d-flex flex-column h-100">
                    <ul class="nav flex-column mb-auto">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('dashboard*') ? 'active' : '' }}"
                                href="{{ route('dashboard') }}">
                                <i class="fas fa-home"></i> <span>Trang chủ</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('courses*') ? 'active' : '' }}"
                                href="{{ route('courses.index') }}">
                                <i class="fas fa-graduation-cap"></i> <span>Khóa học của tôi</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center justify-content-between {{ request()->is('tools*') ? 'active' : '' }}"
                                data-bs-toggle="collapse" href="#toolsMenu" role="button">
                                <div><i class="fas fa-toolbox"></i> <span>Công cụ hỗ trợ</span></div>
                                <i
                                    class="fas fa-chevron-down small transition-all {{ request()->is('tools*') ? 'fa-rotate-180' : '' }}"></i>
                            </a>
                            <div class="collapse {{ request()->is('tools*') ? 'show' : '' }}" id="toolsMenu">
                                <ul class="nav flex-column border-start">
                                    <li class="nav-item">
                                        <a class="nav-link py-2 small {{ request()->routeIs('tools.grade-calculator') ? 'text-primary fw-bold' : '' }}"
                                            href="{{ route('tools.grade-calculator') }}">
                                            <i class="fas fa-calculator me-2"></i> Tính điểm nghề
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-2 small" href="https://ngotanloi.my.canva.site/code-editer"
                                            target="_blank">
                                            <i class="fas fa-code me-2"></i> Trình soạn thảo Code
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        @if (in_array(Auth::user()->role, ['admin', 'teacher']))
                            <div class="px-4 mt-4 mb-2 small text-muted text-uppercase fw-bold section-label"
                                style="letter-spacing: 1px;">Hệ thống AI</div>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('documents*') ? 'active' : '' }}"
                                    href="{{ route('documents.upload') }}">
                                    <i class="fas fa-robot"></i> <span>Huấn luyện AI</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('question-bank*') ? 'active' : '' }}"
                                    href="{{ route('questions.index') }}">
                                    <i class="fas fa-layer-group"></i> <span>Ngân hàng câu hỏi</span>
                                </a>
                            </li>
                            <div class="px-4 mt-4 mb-2 small text-muted text-uppercase fw-bold section-label"
                                style="letter-spacing: 1px;">Quản lý</div>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('classes*') ? 'active' : '' }}"
                                    href="{{ route('classes.index') }}">
                                    <i class="fas fa-chalkboard"></i> <span>Quản lý lớp học</span>
                                </a>
                            </li>
                            @if (Auth::user()->role === 'admin')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->is('users*') ? 'active' : '' }}"
                                        href="{{ route('users.index') }}">
                                        <i class="fas fa-user-cog"></i> <span>Quản lý người dùng</span>
                                    </a>
                                </li>
                            @endif
                        @endif
                    </ul>
                </div>
            </aside>

            <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="{{ route('profile.password.update') }}" method="POST"
                        class="modal-content border-0 shadow-lg">
                        @csrf
                        @method('PUT')
                        <div class="modal-header bg-white border-0 pt-4 px-4">
                            <h5 class="modal-title fw-bold text-navy">
                                <i class="fas fa-user-shield me-2 text-primary"></i>Thông tin tài khoản
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="small fw-bold text-muted mb-1">Họ và tên</label>
                                    <input type="text" class="form-control bg-light border-0 shadow-none"
                                        value="{{ Auth::user()->name }}" readonly style="cursor: not-allowed;">
                                </div>
                                <div class="col-md-7">
                                    <label class="small fw-bold text-muted mb-1">Email</label>
                                    <input type="email" class="form-control bg-light border-0 shadow-none"
                                        value="{{ Auth::user()->email }}" readonly style="cursor: not-allowed;">
                                </div>
                                <div class="col-md-5">
                                    <label class="small fw-bold text-muted mb-1">Vai trò</label>
                                    <input type="text" class="form-control bg-light border-0 shadow-none"
                                        value="{{ strtoupper(Auth::user()->role) }}" readonly
                                        style="cursor: not-allowed;">
                                </div>
                            </div>

                            <hr class="text-muted opacity-25 mb-4">

                            <h6 class="fw-bold mb-3 text-navy">Đổi mật khẩu mới</h6>

                            <div class="mb-3">
                                <label class="small fw-bold mb-1">Mật khẩu hiện tại <span
                                        class="text-danger">*</span></label>
                                <input type="password" name="current_password"
                                    class="form-control shadow-sm @error('current_password') is-invalid @enderror"
                                    placeholder="Nhập mật khẩu cũ để xác nhận" required>
                                @error('current_password')
                                    <div class="invalid-feedback fw-bold">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold mb-1">Mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" name="new_password"
                                    class="form-control shadow-sm @error('new_password') is-invalid @enderror"
                                    placeholder="Tối thiểu 6 ký tự" required minlength="6">
                                @error('new_password')
                                    <div class="invalid-feedback fw-bold">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold mb-1">Xác nhận mật khẩu mới <span
                                        class="text-danger">*</span></label>
                                <input type="password" name="new_password_confirmation" class="form-control shadow-sm"
                                    placeholder="Nhập lại mật khẩu mới" required minlength="6">
                            </div>
                        </div>
                        <div class="modal-footer border-0 pb-4 px-4 pt-0">
                            <button type="submit" class="btn btn-navy w-100 py-2 fw-bold rounded-pill shadow">
                                CẬP NHẬT MẬT KHẨU
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endauth

        <main class="{{ Auth::check() ? 'main-content' : 'full-width' }}">
            <div class="container-fluid p-0">
                @if (session('success'))
                    <div class="alert alert-success border-0 shadow-sm rounded-3 m-4">
                        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
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
        $(document).ready(function() {
            // Kiểm tra nếu biến $errors của Laravel có chứa bất kỳ lỗi nào
            @if ($errors->any())
                var passwordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
                passwordModal.show();
            @endif
        });
    </script>
</body>

</html>
