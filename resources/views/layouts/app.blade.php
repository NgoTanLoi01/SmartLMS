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
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite('resources/css/pages/app-layout.css')

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @stack('styles')
</head>

<body>
    <a class="skip-link" href="#main-content">Bỏ qua menu, đến nội dung chính</a>
    @auth
        {{-- ── Navbar ── --}}
        <nav class="navbar">
            <button class="hamburger me-3" id="sidebarToggle" type="button" aria-label="Mở menu"
                aria-controls="sidebar" aria-expanded="false">
                <i class="fa-solid fa-bars"></i>
            </button>

            <button class="sidebar-collapse-btn" id="sidebarCollapseToggle" type="button"
                aria-label="Thu gọn menu" title="Thu gọn menu" aria-expanded="true">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>

            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('smartlms-logo-sharpened.webp') }}" alt="SmartLMS" width="800" height="200">
            </a>

            <div class="dropdown ms-auto">
                <button class="topbar-icon-btn {{ ($topbarUnreadCount ?? 0) === 0 ? 'no-unread' : 'has-unread' }}" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thông báo">
                    <i class="fa-solid fa-bell"></i>
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

            <button class="user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" id="userMenuBtn"
                aria-expanded="false" aria-label="Mở menu tài khoản">
                <span class="avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                <span class="user-btn__identity">
                    <strong>{{ Auth::user()->name }}</strong>
                    <small>{{ \App\Support\UiLabels::role(Auth::user()->role) }}</small>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end mt-2" aria-labelledby="userMenuBtn">
                <li>
                    <span class="dropdown-item-text account-menu-role">
                        {{ \App\Support\UiLabels::role(Auth::user()->role) }}
                    </span>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fa-solid fa-key" style="color:#f59e0b;"></i> Đổi mật khẩu
                    </a>
                </li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"
                            style="background:none; border:none; width:100%; text-align:left;">
                            <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                        </button>
                    </form>
                </li>
            </ul>
        </nav>

        @include('layouts.partials.sidebar')

        {{-- ── Modal đổi mật khẩu ── --}}
        <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('profile.password.update') }}" method="POST" class="modal-content">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title" style="font-size:17px; font-weight:600;">
                            <i class="fa-solid fa-user-shield me-2" style="color:var(--blue);"></i>Thông tin tài khoản
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
                                <input type="text" class="form-control"
                                    value="{{ \App\Support\UiLabels::role(Auth::user()->role) }}"
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
                                    <label class="form-label">Mã học viên</label>
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
        <main id="main-content" class="{{ Auth::check() ? 'main-content' : '' }}" tabindex="-1">
            <div class="container-fluid p-0">
                @if (session('success'))
                    <div class="alert-success">
                        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
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
            const backdrop = document.getElementById('sidebarBackdrop');
            const mobileToggle = document.getElementById('sidebarToggle');
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

            sidebar.querySelectorAll('.sidebar-item, .sidebar-group__toggle').forEach((link) => {
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
                        const target = document.querySelector(link.getAttribute('data-bs-target'));
                        if (target) bootstrap.Collapse.getOrCreateInstance(target).show();
                    }
                }, true);
            });

            window.addEventListener('resize', () => applyState(storedCollapsed = (() => {
                try { return localStorage.getItem(storageKey) === '1'; } catch (error) { return false; }
            })(), false));

            const closeMobileSidebar = () => {
                sidebar.classList.remove('show');
                backdrop?.classList.remove('show');
                mobileToggle?.setAttribute('aria-expanded', 'false');
            };

            mobileToggle?.addEventListener('click', () => {
                const open = sidebar.classList.toggle('show');
                backdrop?.classList.toggle('show', open);
                mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            backdrop?.addEventListener('click', closeMobileSidebar);
            sidebar.querySelectorAll('a.sidebar-item').forEach((link) => link.addEventListener('click', closeMobileSidebar));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeMobileSidebar();
            });
        })();

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

                showTransition();
            });

            window.addEventListener('pageshow', function() {
                overlay.classList.remove('is-active');
                overlay.setAttribute('aria-hidden', 'true');
            });
        })();

        @if ($errors->hasAny(['current_password', 'new_password', 'new_password_confirmation']))
            (new bootstrap.Modal(document.getElementById('changePasswordModal'))).show();
        @endif
    </script>
</body>

</html>
