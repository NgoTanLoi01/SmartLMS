<!DOCTYPE html>
<html lang="vi" prefix="og: https://ogp.me/ns#">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <meta name="description" content="@yield('meta_description')">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="author" content="SmartLMS.io.vn · NgoTanLoi">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="SmartLMS.io.vn">
    <meta property="og:title" content="@yield('title')">
    <meta property="og:description" content="@yield('meta_description')">
    <meta property="og:image" content="{{ asset('assets/images/branding/smartlms-logo.webp') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title')">
    <meta name="twitter:description" content="@yield('meta_description')">
    <meta name="twitter:image" content="{{ asset('assets/images/branding/smartlms-logo.webp') }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @stack('structured_data')
    @vite(['resources/css/pages/landing.css', 'resources/css/pages/marketing-pages.css', 'resources/css/pages/coastal-theme.css'])
</head>

<body class="marketing-body">
    <header class="site-header is-scrolled" id="siteHeader">
        <nav class="landing-nav" aria-label="Điều hướng chính">
            <a class="nav-logo" href="{{ route('home') }}" aria-label="SmartLMS.io.vn - Trang chủ">
                <img src="{{ asset('assets/images/branding/smartlms-logo.webp') }}" alt="SmartLMS.io.vn" width="2172" height="724">
            </a>

            <button class="nav-toggle" id="navToggle" type="button" aria-controls="navMenu" aria-expanded="false" aria-label="Mở menu">
                <span></span><span></span><span></span>
            </button>

            <div class="nav-menu" id="navMenu">
                <ul class="nav-links">
                    <li><a href="{{ route('marketing.training-management') }}">Giải pháp</a></li>
                    <li><a href="{{ route('marketing.class-management') }}">Lớp học</a></li>
                    <li><a href="{{ route('marketing.schedule-management') }}">Lịch học</a></li>
                    <li><a href="{{ route('marketing.rag-chatbot') }}">AI & dữ liệu</a></li>
                    <li><a href="{{ route('marketing.about') }}">Giới thiệu</a></li>
                </ul>
                <a class="nav-login" href="{{ route('login') }}">
                    Đăng nhập <x-ui.icon name="arrow-right" />
                </a>
            </div>
            <span class="scroll-progress" id="scrollProgress" aria-hidden="true"></span>
        </nav>
    </header>

    <main>@yield('content')</main>

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <a href="{{ route('home') }}" class="footer-logo">
                    <img src="{{ asset('assets/images/branding/smartlms-logo.webp') }}" alt="SmartLMS.io.vn" width="2172" height="724">
                </a>
                <p>SmartLMS.io.vn là hệ thống quản lý vòng đời đào tạo tích hợp AI, được phát triển độc lập tại Việt Nam bởi NgoTanLoi.</p>
            </div>
            <div class="footer-links marketing-footer-links">
                <div>
                    <strong>Giải pháp</strong>
                    <a href="{{ route('marketing.training-management') }}">Quản lý đào tạo</a>
                    <a href="{{ route('marketing.class-management') }}">Quản lý lớp học</a>
                    <a href="{{ route('marketing.schedule-management') }}">Quản lý lịch học</a>
                    <a href="{{ route('marketing.attendance') }}">Điểm danh học viên</a>
                </div>
                <div>
                    <strong>Nội dung & AI</strong>
                    <a href="{{ route('marketing.question-bank') }}">Ngân hàng câu hỏi</a>
                    <a href="{{ route('marketing.rag-chatbot') }}">Chatbot RAG giáo dục</a>
                    <a href="{{ route('marketing.about') }}">Giới thiệu SmartLMS</a>
                </div>
                <div>
                    <strong>Truy cập</strong>
                    <a href="{{ route('login') }}">Đăng nhập</a>
                    <a href="{{ route('home') }}">Trang chủ</a>
                    <a href="https://github.com/NgoTanLoi01/LMS_System" target="_blank" rel="noopener noreferrer">Mã nguồn</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2026 SmartLMS.io.vn. Sản phẩm độc lập, không thuộc Viettel.</span>
            <span>Phát triển bởi <a href="mailto:ngotanloi2424@gmail.com"><strong>NgoTanLoi</strong></a>.</span>
        </div>
    </footer>

    <script>
        const siteHeader = document.getElementById('siteHeader');
        const scrollProgress = document.getElementById('scrollProgress');
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');

        const updateHeader = () => {
            siteHeader.classList.toggle('is-scrolled', window.scrollY > 20);
            const scrollable = document.documentElement.scrollHeight - window.innerHeight;
            scrollProgress.style.transform = `scaleX(${scrollable > 0 ? Math.min(window.scrollY / scrollable, 1) : 0})`;
        };

        navToggle.addEventListener('click', () => {
            const open = navMenu.classList.toggle('is-open');
            navToggle.classList.toggle('is-open', open);
            navToggle.setAttribute('aria-expanded', String(open));
            navToggle.setAttribute('aria-label', open ? 'Đóng menu' : 'Mở menu');
        });
        navMenu.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
            navMenu.classList.remove('is-open');
            navToggle.classList.remove('is-open');
            navToggle.setAttribute('aria-expanded', 'false');
        }));
        window.addEventListener('scroll', updateHeader, { passive: true });
        updateHeader();
    </script>
</body>

</html>
