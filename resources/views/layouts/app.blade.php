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
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon-96.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite('resources/css/pages/app-layout.css')

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
                @if (Auth::user()->isAdmin() || Auth::user()->isTeacher())
                    <li>
                        <a class="nav-link {{ request()->routeIs('shared-documents.*') ? 'active' : '' }}"
                            href="{{ route('shared-documents.index') }}">
                            <i class="fas fa-box-archive"></i> Tài liệu chung
                        </a>
                    </li>
                @endif
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
                        <li>
                            <a class="nav-link {{ request()->is('system/ai-operations*') ? 'active' : '' }}"
                                href="{{ route('system.ai-operations.index') }}">
                                <i class="fas fa-microchip"></i> Theo dõi AI & Queue
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
                if (/\/download\/?$/.test(targetUrl.pathname)) return false;
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
