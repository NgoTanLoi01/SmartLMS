<!DOCTYPE html>
<html lang="vi" prefix="og: https://ogp.me/ns#">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- PRIMARY META --}}
    <title>@yield('title', 'SmartLMS – Phần mềm quản lý trung tâm đào tạo miễn phí, tích hợp AI')</title>
    <meta name="description" content="@yield('meta_description', 'SmartLMS là phần mềm quản lý trung tâm đào tạo miễn phí 100% dành cho Việt Nam. Quản lý lớp học, bài tập, ngân hàng câu hỏi và huấn luyện AI từ tài liệu của bạn — không cần cài đặt.')">
    <meta name="keywords"
        content="phần mềm quản lý trung tâm đào tạo, LMS miễn phí, hệ thống quản lý học tập, e-learning Việt Nam, quản lý lớp học online, SmartLMS, AI giáo dục">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="author" content="SmartLMS">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- OPEN GRAPH --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:url" content="https://smartlms.io.vn/">
    <meta property="og:site_name" content="SmartLMS">
    <meta property="og:title" content="SmartLMS – Phần mềm quản lý trung tâm đào tạo miễn phí, tích hợp AI">
    <meta property="og:description"
        content="Quản lý lớp học, bài tập, ngân hàng câu hỏi và huấn luyện AI từ tài liệu của bạn. Miễn phí 100%, không cần cài đặt, hỗ trợ tiếng Việt đầy đủ.">
    <meta property="og:image" content="{{ asset('favicon-v2.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- TWITTER CARD --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SmartLMS – Phần mềm quản lý trung tâm đào tạo miễn phí, tích hợp AI">
    <meta name="twitter:description"
        content="Quản lý lớp học, bài tập, ngân hàng câu hỏi và huấn luyện AI từ tài liệu của bạn. Miễn phí 100%.">
    <meta name="twitter:image" content="{{ asset('favicon-v2.png') }}">

    {{-- FAVICON --}}
    <link rel="icon" type="image/png" href="{{ asset('favicon-v2.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon-v2.png') }}">

    {{-- FONTS --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    {{-- STRUCTURED DATA --}}
    @verbatim
        <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "SmartLMS",
      "url": "https://smartlms.io.vn",
      "description": "Phần mềm quản lý trung tâm đào tạo tích hợp AI, miễn phí 100% dành cho Việt Nam",
      "applicationCategory": "EducationalApplication",
      "operatingSystem": "Web",
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "VND"
      },
      "inLanguage": "vi",
      "featureList": [
        "Quản lý lớp học và lịch học",
        "Ngân hàng câu hỏi thông minh",
        "Giao bài tập và theo dõi kết quả",
        "Huấn luyện AI từ tài liệu",
        "Phân quyền Admin, Giáo viên, Học viên"
      ]
    }
    </script>
        <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "SmartLMS",
      "url": "https://smartlms.io.vn",
      "logo": "https://smartlms.io.vn/favicon-v2.png"
    }
    </script>
    @endverbatim

    @verbatim

        <style>
            /* ============================================================
                               DESIGN TOKENS
                            ============================================================ */
            :root {
                --bg: #F5F6FB;
                --bg-deep: #0A0C16;
                --surface: #FFFFFF;
                --surface-glass: rgba(255, 255, 255, 0.55);
                --surface-glass-strong: rgba(255, 255, 255, 0.78);
                --border-glass: rgba(255, 255, 255, 0.6);
                --border-glass-dark: rgba(255, 255, 255, 0.09);

                --ink: #12131C;
                --ink-soft: #3C3F52;
                --ink-muted: #6C7086;
                --ink-on-dark: #EDEEF7;
                --ink-on-dark-muted: rgba(237, 238, 247, 0.62);

                --indigo: #5B5FEF;
                --indigo-2: #7C6FF0;
                --indigo-deep: #3E3FBF;
                --cyan: #3FD8C6;
                --violet: #A78BFA;
                --pink: #F472B6;
                --amber: #F5B942;

                --grad-primary: linear-gradient(135deg, var(--indigo) 0%, var(--indigo-2) 45%, var(--violet) 100%);
                --grad-border: linear-gradient(135deg, rgba(91, 95, 239, 0.55), rgba(63, 216, 198, 0.4));

                --r-lg: 28px;
                --r-md: 22px;
                --r-sm: 16px;

                --shadow-soft: 0 20px 60px -20px rgba(30, 32, 80, 0.18);
                --shadow-strong: 0 34px 90px -20px rgba(30, 32, 80, 0.32);
                --shadow-glow-indigo: 0 0 60px -10px rgba(91, 95, 239, 0.45);
                --shadow-glow-cyan: 0 0 60px -10px rgba(63, 216, 198, 0.4);

                --ease: cubic-bezier(.22, 1, .36, 1);
                --persp: 1800px;
            }

            @media (prefers-reduced-motion: reduce) {
                * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                    scroll-behavior: auto !important;
                }
            }

            * {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                font-family: 'Inter', -apple-system, sans-serif;
                color: var(--ink);
                background: var(--bg);
                overflow-x: hidden;
                line-height: 1.65;
                margin: 0;
            }

            h1,
            h2,
            h3,
            h4 {
                font-family: 'Space Grotesk', 'Inter', sans-serif;
                letter-spacing: -0.02em;
                color: var(--ink);
                margin: 0;
            }

            a {
                text-decoration: none;
                color: inherit;
            }

            img {
                max-width: 100%;
                display: block;
            }

            ::selection {
                background: rgba(91, 95, 239, 0.25);
            }

            :focus-visible {
                outline: 2px solid var(--indigo);
                outline-offset: 3px;
                border-radius: 8px;
            }

            .mono {
                font-family: 'IBM Plex Mono', monospace;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                font-size: 0.72rem;
                font-weight: 500;
            }

            .fade-in {
                opacity: 0;
                transform: translateY(28px);
                transition: opacity .8s var(--ease), transform .8s var(--ease);
            }

            .fade-in.visible {
                opacity: 1;
                transform: translateY(0);
            }

            .section-head {
                text-align: center;
                max-width: 680px;
                margin: 0 auto 56px;
            }

            .section-eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-family: 'IBM Plex Mono', monospace;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                font-size: 0.72rem;
                font-weight: 500;
                color: var(--indigo-deep);
                margin-bottom: 18px;
            }

            .section-eyebrow::before {
                content: '';
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: var(--grad-primary);
                box-shadow: 0 0 10px rgba(91, 95, 239, 0.7);
            }

            .section-title {
                font-size: clamp(1.9rem, 3.2vw, 2.6rem);
                font-weight: 600;
                margin-bottom: 16px;
            }

            .section-desc {
                color: var(--ink-muted);
                font-size: 1.05rem;
            }

            /* ============================================================
                               NAV
                            ============================================================ */
            nav {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1000;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 20px 48px;
                transition: background .4s var(--ease), backdrop-filter .4s var(--ease), box-shadow .4s var(--ease), padding .4s var(--ease);
            }

            nav.scrolled {
                background: rgba(255, 255, 255, 0.72);
                backdrop-filter: blur(18px);
                box-shadow: 0 8px 32px -12px rgba(30, 32, 80, 0.15);
                padding: 14px 48px;
            }

            .nav-logo {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .nav-logo img {
                height: 50px;
            }

            .nav-links {
                display: flex;
                align-items: center;
                gap: 36px;
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .nav-links a {
                color: var(--ink-soft);
                font-weight: 500;
                font-size: 0.95rem;
                position: relative;
                padding: 4px 0;
                transition: color .25s var(--ease);
            }

            .nav-links a::after {
                content: '';
                position: absolute;
                left: 0;
                bottom: -2px;
                height: 2px;
                width: 0%;
                background: var(--grad-primary);
                transition: width .3s var(--ease);
            }

            .nav-links a:hover {
                color: var(--ink);
            }

            .nav-links a:hover::after {
                width: 100%;
            }

            .nav-cta {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 11px 22px;
                border-radius: 100px;
                background: var(--grad-primary);
                color: #fff !important;
                font-weight: 600;
                font-size: 0.92rem;
                box-shadow: 0 14px 34px -10px rgba(91, 95, 239, 0.55);
                transition: transform .35s var(--ease), box-shadow .35s var(--ease);
            }

            .nav-cta:hover {
                transform: translateY(-2px);
                box-shadow: 0 18px 40px -8px rgba(91, 95, 239, 0.65);
            }

            @media (max-width:900px) {
                nav {
                    padding: 16px 20px;
                }

                .nav-links {
                    display: none;
                }
            }

            /* ============================================================
                               HERO
                            ============================================================ */
            .hero {
                position: relative;
                padding: 170px 24px 120px;
                overflow: hidden;
            }

            .hero-bg {
                position: absolute;
                inset: 0;
                z-index: 0;
            }

            .hero-bg::before,
            .hero-bg::after {
                content: '';
                position: absolute;
                border-radius: 50%;
                filter: blur(70px);
            }

            .hero-bg::before {
                width: 560px;
                height: 560px;
                top: -180px;
                left: -180px;
                background: radial-gradient(circle, rgba(91, 95, 239, 0.28), transparent 70%);
            }

            .hero-bg::after {
                width: 480px;
                height: 480px;
                top: -60px;
                right: -200px;
                background: radial-gradient(circle, rgba(63, 216, 198, 0.24), transparent 70%);
            }

            .hero-bg-dot {
                position: absolute;
                width: 400px;
                height: 400px;
                border-radius: 50%;
                bottom: -200px;
                left: 38%;
                background: radial-gradient(circle, rgba(167, 139, 250, 0.2), transparent 70%);
                filter: blur(60px);
            }

            .hero-inner {
                position: relative;
                z-index: 2;
                max-width: 1280px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: 1.05fr 1fr;
                gap: 56px;
                align-items: center;
            }

            @media (max-width:992px) {
                .hero-inner {
                    grid-template-columns: 1fr;
                    text-align: center;
                }
            }

            .badge {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 8px 16px;
                border-radius: 100px;
                background: var(--surface-glass);
                border: 1px solid var(--border-glass);
                backdrop-filter: blur(14px);
                font-size: 0.82rem;
                font-weight: 600;
                color: var(--indigo-deep);
                margin-bottom: 24px;
                box-shadow: var(--shadow-soft);
            }

            .badge-dot {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: var(--cyan);
                box-shadow: 0 0 8px var(--cyan);
            }

            .hero-content h1 {
                font-size: clamp(2.1rem, 3.6vw, 3.4rem);
                font-weight: 700;
                line-height: 1.12;
                margin-bottom: 22px;
            }

            .hero-content h1 .accent {
                background: var(--grad-primary);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }

            .hero-desc {
                font-size: 1.08rem;
                color: var(--ink-muted);
                max-width: 520px;
                margin-bottom: 34px;
            }

            @media (max-width:992px) {
                .hero-desc {
                    margin-inline: auto;
                }
            }

            .hero-actions {
                display: flex;
                gap: 16px;
                margin-bottom: 34px;
                flex-wrap: wrap;
            }

            @media (max-width:992px) {
                .hero-actions {
                    justify-content: center;
                }
            }

            .btn-primary {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 15px 28px;
                border-radius: 100px;
                background: var(--grad-primary);
                color: #fff;
                font-weight: 600;
                font-size: 0.98rem;
                box-shadow: 0 18px 44px -12px rgba(91, 95, 239, 0.55);
                transition: transform .35s var(--ease), box-shadow .35s var(--ease);
            }

            .btn-primary:hover {
                transform: translateY(-3px);
                box-shadow: 0 22px 50px -10px rgba(91, 95, 239, 0.65);
            }

            .btn-ghost {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 15px 26px;
                border-radius: 100px;
                background: var(--surface-glass);
                border: 1px solid var(--border-glass);
                backdrop-filter: blur(16px);
                color: var(--ink);
                font-weight: 600;
                font-size: 0.98rem;
                box-shadow: var(--shadow-soft);
                transition: transform .35s var(--ease), background .35s var(--ease);
            }

            .btn-ghost:hover {
                transform: translateY(-3px);
                background: #fff;
            }

            .trust-bar {
                display: flex;
                gap: 24px;
                flex-wrap: wrap;
                margin-bottom: 36px;
            }

            @media (max-width:992px) {
                .trust-bar {
                    justify-content: center;
                }
            }

            .trust-item {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 0.88rem;
                color: var(--ink-soft);
                font-weight: 500;
            }

            .trust-icon {
                width: 20px;
                height: 20px;
                border-radius: 50%;
                background: var(--grad-primary);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.68rem;
                flex-shrink: 0;
            }

            .hero-kicker {
                display: flex;
                gap: 16px;
                flex-wrap: wrap;
            }

            @media (max-width:992px) {
                .hero-kicker {
                    justify-content: center;
                }
            }

            .kicker-card {
                background: var(--surface-glass);
                border: 1px solid var(--border-glass);
                backdrop-filter: blur(14px);
                border-radius: var(--r-sm);
                padding: 16px 20px;
                min-width: 140px;
                box-shadow: var(--shadow-soft);
                transition: transform .4s var(--ease), box-shadow .4s var(--ease);
            }

            .kicker-card:hover {
                transform: translateY(-4px) translateZ(10px);
                box-shadow: var(--shadow-strong);
            }

            .kicker-value {
                font-family: 'Space Grotesk', sans-serif;
                font-weight: 700;
                font-size: 1.5rem;
                background: var(--grad-primary);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }

            .kicker-label {
                font-size: 0.8rem;
                color: var(--ink-muted);
                margin-top: 4px;
            }

            /* ---------- 3D HERO VISUAL ---------- */
            .hero-visual {
                perspective: var(--persp);
            }

            .product-shell {
                position: relative;
                transform-style: preserve-3d;
                transition: transform .5s var(--ease);
                transform: rotateX(4deg) rotateY(-6deg);
                max-width: 460px;
                margin: 0 auto;
            }

            @media (max-width:992px) {
                .product-shell {
                    transform: none !important;
                }
            }

            .dashboard-card {
                position: relative;
                transform: translateZ(0px);
                background: var(--surface-glass-strong);
                border: 1px solid var(--border-glass);
                backdrop-filter: blur(24px) saturate(180%);
                border-radius: var(--r-lg);
                padding: 26px;
                box-shadow: var(--shadow-strong), var(--shadow-glow-indigo);
            }

            .command-top {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 18px;
            }

            .command-dots {
                display: flex;
                gap: 6px;
            }

            .command-dots span {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: rgba(91, 95, 239, 0.35);
            }

            .command-label {
                font-family: 'IBM Plex Mono', monospace;
                font-size: 0.7rem;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: var(--ink-muted);
            }

            .card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }

            .card-title {
                font-weight: 600;
                font-size: 1rem;
            }

            .card-badge {
                font-family: 'IBM Plex Mono', monospace;
                font-size: 0.68rem;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                padding: 4px 10px;
                border-radius: 100px;
                background: rgba(63, 216, 198, 0.16);
                color: #1a9c8a;
                font-weight: 600;
            }

            .class-list {
                display: flex;
                flex-direction: column;
                gap: 14px;
            }

            .class-item {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .class-icon {
                width: 38px;
                height: 38px;
                border-radius: 12px;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'IBM Plex Mono', monospace;
                font-weight: 600;
                font-size: 0.78rem;
                color: var(--indigo-deep);
            }

            .class-info {
                flex: 1;
                min-width: 0;
            }

            .class-name {
                font-weight: 600;
                font-size: 0.9rem;
            }

            .class-meta {
                font-size: 0.78rem;
                color: var(--ink-muted);
                margin-top: 2px;
            }

            .class-progress {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 6px;
                flex-shrink: 0;
                width: 78px;
            }

            .progress-bar {
                width: 100%;
                height: 5px;
                border-radius: 100px;
                background: rgba(20, 20, 50, 0.08);
                overflow: hidden;
            }

            .progress-fill {
                height: 100%;
                border-radius: 100px;
                background: var(--grad-primary);
            }

            .progress-text {
                font-size: 0.7rem;
                color: var(--ink-muted);
                font-weight: 600;
            }

            .floating-chip {
                position: absolute;
                display: flex;
                align-items: center;
                gap: 10px;
                background: var(--surface-glass-strong);
                border: 1px solid var(--border-glass);
                backdrop-filter: blur(18px);
                border-radius: 100px;
                padding: 10px 16px;
                font-size: 0.82rem;
                font-weight: 600;
                color: var(--ink);
                box-shadow: var(--shadow-strong);
                transform: translateZ(70px);
                animation: floaty 6.5s ease-in-out infinite;
            }

            .chip-icon {
                width: 26px;
                height: 26px;
                border-radius: 8px;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'IBM Plex Mono', monospace;
                font-weight: 700;
                font-size: 0.62rem;
                color: var(--indigo-deep);
            }

            .chip-1 {
                top: -24px;
                left: -36px;
                animation-delay: 0s;
            }

            .chip-2 {
                bottom: 64px;
                right: -44px;
                animation-delay: 1.2s;
                transform: translateZ(90px);
            }

            @media (max-width:560px) {
                .floating-chip {
                    display: none;
                }
            }

            .ai-brief {
                position: absolute;
                left: 50%;
                bottom: -46px;
                transform: translate(-50%, 0) translateZ(50px);
                width: 88%;
                background: var(--bg-deep);
                color: var(--ink-on-dark);
                border-radius: var(--r-sm);
                padding: 16px 18px;
                box-shadow: var(--shadow-strong);
            }

            .ai-brief__label {
                font-family: 'IBM Plex Mono', monospace;
                font-size: 0.66rem;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--cyan);
                margin-bottom: 6px;
            }

            .ai-brief__text {
                font-size: 0.84rem;
                color: var(--ink-on-dark-muted);
                line-height: 1.5;
            }

            @media (max-width:560px) {
                .ai-brief {
                    position: relative;
                    left: auto;
                    bottom: auto;
                    transform: none;
                    margin-top: 20px;
                    width: 100%;
                }
            }

            @keyframes floaty {

                0%,
                100% {
                    transform: translateZ(70px) translateY(0px);
                }

                50% {
                    transform: translateZ(70px) translateY(-12px);
                }
            }

            /* ============================================================
                               FEATURES
                            ============================================================ */
            .features {
                padding: 120px 24px;
                max-width: 1280px;
                margin: 0 auto;
            }

            .features-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 24px;
                perspective: var(--persp);
            }

            @media (max-width:992px) {
                .features-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (max-width:640px) {
                .features-grid {
                    grid-template-columns: 1fr;
                }
            }

            .feature-card {
                position: relative;
                background: var(--surface);
                border-radius: var(--r-md);
                padding: 32px 28px;
                border: 1px solid rgba(20, 20, 50, 0.06);
                box-shadow: var(--shadow-soft);
                transform-style: preserve-3d;
                transition: transform .35s var(--ease), box-shadow .35s var(--ease);
                will-change: transform;
            }

            .feature-card:hover {
                box-shadow: var(--shadow-strong), var(--shadow-glow-indigo);
            }

            .feature-icon {
                width: 52px;
                height: 52px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.4rem;
                margin-bottom: 20px;
                transform: translateZ(20px);
            }

            .feature-title {
                font-size: 1.12rem;
                font-weight: 600;
                margin-bottom: 10px;
                transform: translateZ(15px);
            }

            .feature-desc {
                color: var(--ink-muted);
                font-size: 0.92rem;
                margin: 0;
            }

            /* ============================================================
                               AI LAYER (dark)
                            ============================================================ */
            .ai-layer {
                background: var(--bg-deep);
                color: var(--ink-on-dark);
                padding: 120px 24px;
                position: relative;
                overflow: hidden;
            }

            .ai-layer::before {
                content: '';
                position: absolute;
                width: 600px;
                height: 600px;
                border-radius: 50%;
                top: -220px;
                right: -220px;
                background: radial-gradient(circle, rgba(63, 216, 198, 0.18), transparent 70%);
                filter: blur(40px);
            }

            .ai-layer::after {
                content: '';
                position: absolute;
                width: 520px;
                height: 520px;
                border-radius: 50%;
                bottom: -220px;
                left: -180px;
                background: radial-gradient(circle, rgba(91, 95, 239, 0.2), transparent 70%);
                filter: blur(40px);
            }

            .ai-layer-inner {
                position: relative;
                z-index: 2;
                max-width: 1280px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 64px;
                align-items: center;
            }

            @media (max-width:992px) {
                .ai-layer-inner {
                    grid-template-columns: 1fr;
                }
            }

            .ai-layer-inner h2 {
                font-size: clamp(1.8rem, 3vw, 2.5rem);
                font-weight: 600;
                margin: 14px 0 18px;
                color: #fff;
                line-height: 1.24;
            }

            .ai-layer-inner>div>p {
                color: var(--ink-on-dark-muted);
                font-size: 1.02rem;
                margin-bottom: 36px;
            }

            .ai-flow {
                display: flex;
                flex-direction: column;
                gap: 18px;
            }

            .ai-flow-card {
                display: flex;
                align-items: flex-start;
                gap: 16px;
                background: rgba(255, 255, 255, 0.04);
                border: 1px solid var(--border-glass-dark);
                backdrop-filter: blur(14px);
                border-radius: var(--r-md);
                padding: 18px 20px;
                transition: transform .4s var(--ease), background .4s var(--ease);
            }

            .ai-flow-card:hover {
                transform: translateY(-4px) translateZ(6px);
                background: rgba(255, 255, 255, 0.07);
            }

            .ai-flow-icon {
                font-family: 'Space Grotesk', sans-serif;
                font-weight: 700;
                font-size: 1rem;
                color: #0A0C16;
                width: 40px;
                height: 40px;
                border-radius: 11px;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, var(--cyan), var(--indigo-2));
            }

            .ai-flow-title {
                font-weight: 600;
                font-size: 0.98rem;
                margin-bottom: 4px;
                color: #fff;
            }

            .ai-flow-desc {
                font-size: 0.86rem;
                color: var(--ink-on-dark-muted);
            }

            .ai-console {
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid var(--border-glass-dark);
                backdrop-filter: blur(20px);
                border-radius: var(--r-lg);
                padding: 26px;
                box-shadow: 0 40px 90px -30px rgba(0, 0, 0, 0.5), var(--shadow-glow-cyan);
                transform: perspective(var(--persp)) rotateY(4deg);
            }

            @media (max-width:992px) {
                .ai-console {
                    transform: none;
                }
            }

            .ai-console-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 20px;
            }

            .ai-console-title {
                font-weight: 600;
                color: #fff;
                font-size: 0.98rem;
            }

            .ai-console-body {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .ai-message {
                padding: 12px 16px;
                border-radius: 16px;
                font-size: 0.88rem;
                line-height: 1.5;
                max-width: 92%;
            }

            .ai-message.user {
                background: rgba(255, 255, 255, 0.08);
                color: var(--ink-on-dark);
                align-self: flex-end;
                border-bottom-right-radius: 4px;
            }

            .ai-message.system {
                background: linear-gradient(135deg, rgba(91, 95, 239, 0.22), rgba(63, 216, 198, 0.16));
                color: #fff;
                border-bottom-left-radius: 4px;
            }

            .ai-evidence {
                font-family: 'IBM Plex Mono', monospace;
                font-size: 0.7rem;
                color: var(--cyan);
                letter-spacing: 0.03em;
                padding-left: 4px;
            }

            /* ============================================================
                               HOW IT WORKS
                            ============================================================ */
            .how {
                padding: 120px 24px;
                max-width: 1100px;
                margin: 0 auto;
            }

            .steps {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 32px;
            }

            @media (max-width:768px) {
                .steps {
                    grid-template-columns: 1fr;
                }
            }

            .step {
                text-align: left;
            }

            .step-num {
                width: 64px;
                height: 64px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Space Grotesk', sans-serif;
                font-weight: 700;
                font-size: 1.25rem;
                color: #fff;
                background: var(--grad-primary);
                box-shadow: var(--shadow-glow-indigo);
                margin-bottom: 20px;
            }

            .step-title {
                font-size: 1.08rem;
                font-weight: 600;
                margin-bottom: 8px;
            }

            .step-desc {
                color: var(--ink-muted);
                font-size: 0.92rem;
            }

            /* ============================================================
                               CTA
                            ============================================================ */
            .cta-section {
                padding: 0 24px 120px;
                max-width: 1280px;
                margin: 0 auto;
            }

            .free-banner {
                position: relative;
                overflow: hidden;
                border-radius: var(--r-lg);
                background: var(--grad-primary);
                padding: 64px 56px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 32px;
                flex-wrap: wrap;
                box-shadow: var(--shadow-strong);
            }

            .free-banner::before {
                content: '';
                position: absolute;
                width: 260px;
                height: 260px;
                border-radius: 50%;
                top: -110px;
                left: -60px;
                background: rgba(255, 255, 255, 0.22);
                filter: blur(30px);
            }

            .free-banner::after {
                content: '';
                position: absolute;
                width: 220px;
                height: 220px;
                border-radius: 50%;
                bottom: -110px;
                right: -40px;
                background: rgba(255, 255, 255, 0.18);
                filter: blur(30px);
            }

            .banner-content {
                position: relative;
                z-index: 2;
                max-width: 600px;
            }

            .banner-content h2 {
                color: #fff;
                font-size: clamp(1.5rem, 2.6vw, 2rem);
                margin-bottom: 16px;
            }

            .banner-content p {
                display: flex;
                gap: 18px;
                flex-wrap: wrap;
                color: rgba(255, 255, 255, 0.9);
                font-size: 0.9rem;
                font-weight: 500;
                margin: 0;
            }

            .btn-white {
                position: relative;
                z-index: 2;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                flex-shrink: 0;
                padding: 15px 30px;
                border-radius: 100px;
                background: #fff;
                color: var(--indigo-deep);
                font-weight: 700;
                font-size: 0.98rem;
                transition: transform .35s var(--ease), box-shadow .35s var(--ease);
                box-shadow: 0 20px 40px -14px rgba(0, 0, 0, 0.35);
            }

            .btn-white:hover {
                transform: translateY(-3px);
            }

            /* ============================================================
                               FOOTER
                            ============================================================ */
            footer {
                background: var(--bg-deep);
                color: var(--ink-on-dark-muted);
                padding: 80px 24px 32px;
            }

            .footer-container {
                max-width: 1280px;
                margin: 0 auto;
            }

            .footer-grid {
                display: grid;
                grid-template-columns: 1.6fr 1fr 1fr 1fr;
                gap: 40px;
                margin-bottom: 56px;
            }

            @media (max-width:900px) {
                .footer-grid {
                    grid-template-columns: 1fr 1fr;
                }
            }

            @media (max-width:560px) {
                .footer-grid {
                    grid-template-columns: 1fr;
                }
            }

            .footer-logo {
                font-family: 'Space Grotesk', sans-serif;
                font-weight: 700;
                font-size: 1.4rem;
                color: #fff;
                display: inline-block;
                margin-bottom: 16px;
            }

            .footer-desc {
                font-size: 0.92rem;
                max-width: 320px;
                margin-bottom: 20px;
            }

            .footer-socials {
                display: flex;
                gap: 12px;
            }

            .footer-socials a {
                width: 38px;
                height: 38px;
                border-radius: 50%;
                border: 1px solid var(--border-glass-dark);
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--ink-on-dark-muted);
                transition: all .3s var(--ease);
            }

            .footer-socials a:hover {
                background: var(--grad-primary);
                color: #fff;
                border-color: transparent;
                transform: translateY(-3px);
            }

            .footer-col h4 {
                color: #fff;
                font-weight: 600;
                font-size: 0.85rem;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                margin-bottom: 18px;
            }

            .footer-col ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .footer-col ul li {
                margin-bottom: 12px;
            }

            .footer-col ul li a {
                font-size: 0.92rem;
                color: var(--ink-on-dark-muted);
                transition: color .25s var(--ease);
            }

            .footer-col ul li a:hover {
                color: #fff;
            }

            .footer-bottom {
                border-top: 1px solid var(--border-glass-dark);
                padding-top: 24px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 12px;
                font-size: 0.82rem;
            }

            .footer-bottom a {
                color: var(--cyan);
            }
        </style>
    </head>

    <body>

        <!-- NAV -->
        <nav>
            <a class="nav-logo" href="{{ url('/') }}" aria-label="SmartLMS - Trang chủ">
                <img src="https://smartlms.io.vn/smartlms-logo-nobg.png">
            </a>
            <ul class="nav-links">
                <li><a href="#features">Năng lực</a></li>
                <li><a href="#ai-layer">AI</a></li>
                <li><a href="#how">Triển khai</a></li>
            </ul>
            <div class="nav-right">
                <a class="nav-cta" href="https://smartlms.io.vn/login">Đăng nhập →</a>
            </div>
        </nav>

        <!-- HERO -->
        <section class="hero" aria-label="Giới thiệu SmartLMS">
            <div class="hero-bg">
                <div class="hero-bg-dot"></div>
            </div>
            <div class="hero-inner">
                <div class="hero-content fade-in">
                    <div class="badge">
                        <span class="badge-dot"></span>
                        LMS thế hệ mới · Tích hợp AI theo ngữ cảnh
                    </div>
                    <h1>SmartLMS cho trung tâm đào tạo <span class="accent">vận hành thông minh hơn</span></h1>
                    <p class="hero-desc">Một không gian dạy học hiện đại nơi lớp học, khóa học, bài nộp, quiz và trợ giảng
                        AI
                        cùng hoạt động trong một luồng thống nhất.</p>
                    <div class="hero-actions">
                        <a class="btn-primary" href="https://smartlms.io.vn/login">
                            Vào SmartLMS
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                        <a class="btn-ghost" href="#ai-layer">Khám phá AI</a>
                    </div>
                    <div class="trust-bar">
                        <div class="trust-item">
                            <div class="trust-icon">✓</div>Giáo viên thao tác nhanh
                        </div>
                        <div class="trust-item">
                            <div class="trust-icon">✓</div>Học sinh học rõ luồng
                        </div>
                        <div class="trust-item">
                            <div class="trust-icon">✓</div>AI hiểu nội dung khóa học
                        </div>
                    </div>
                    <div class="hero-kicker">
                        <div class="kicker-card">
                            <div class="kicker-value">1</div>
                            <div class="kicker-label">nơi quản lý toàn bộ lớp học</div>
                        </div>
                        <div class="kicker-card">
                            <div class="kicker-value">AI</div>
                            <div class="kicker-label">hỗ trợ soạn, học và chấm bài</div>
                        </div>
                        <div class="kicker-card">
                            <div class="kicker-value">24/7</div>
                            <div class="kicker-label">học sinh có trợ giảng theo bài</div>
                        </div>
                    </div>
                </div>

                <div class="hero-visual fade-in" id="heroVisual" style="transition-delay:.2s" aria-hidden="true">
                    <div class="product-shell" id="productShell">
                        <div class="floating-chip chip-1">
                            <div class="chip-icon" style="background:#ecfeff">AI</div>
                            Gợi ý việc cần làm
                        </div>
                        <div class="dashboard-card">
                            <div class="command-top">
                                <div class="command-dots"><span></span><span></span><span></span></div>
                                <div class="command-label">Teacher Command Center</div>
                            </div>
                            <div class="card-header">
                                <span class="card-title">Cần xử lý hôm nay</span>
                                <span class="card-badge">Live</span>
                            </div>
                            <div class="class-list">
                                <div class="class-item">
                                    <div class="class-icon" style="background:#eff6ff">01</div>
                                    <div class="class-info">
                                        <div class="class-name">Lớp sắp dạy</div>
                                        <div class="class-meta">Web cơ bản · 19:30 hôm nay</div>
                                    </div>
                                    <div class="class-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width:82%"></div>
                                        </div>
                                        <span class="progress-text">Sẵn sàng</span>
                                    </div>
                                </div>
                                <div class="class-item">
                                    <div class="class-icon" style="background:#fff7ed">02</div>
                                    <div class="class-info">
                                        <div class="class-name">Bài cần chấm ưu tiên</div>
                                        <div class="class-meta">5 bài quá hạn · 12 bài mới nộp</div>
                                    </div>
                                    <div class="class-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width:64%"></div>
                                        </div>
                                        <span class="progress-text">Ưu tiên</span>
                                    </div>
                                </div>
                                <div class="class-item">
                                    <div class="class-icon" style="background:#ecfdf5">03</div>
                                    <div class="class-info">
                                        <div class="class-name">AI trợ giảng khóa học</div>
                                        <div class="class-meta">Tóm tắt bài · tạo ví dụ · gợi ý ôn tập</div>
                                    </div>
                                    <div class="class-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width:92%"></div>
                                        </div>
                                        <span class="progress-text">AI</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="floating-chip chip-2">
                            <div class="chip-icon" style="background:#fef3c7">QA</div>
                            Quiz tạo nhanh từ bài học
                        </div>
                        <div class="ai-brief">
                            <div class="ai-brief__label">AI Insight</div>
                            <div class="ai-brief__text">Ưu tiên chấm bài quá hạn trước, sau đó chuẩn bị ví dụ cho lớp Web
                                cơ bản.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
        <section class="features" id="features" aria-labelledby="features-title">
            <div class="section-head fade-in">
                <div style="display:flex;justify-content:center">
                    <div class="section-eyebrow">Tính năng</div>
                </div>
                <h2 class="section-title" id="features-title">Nền tảng vận hành đào tạo theo thời gian thực</h2>
                <p class="section-desc">Tập trung vào những việc giáo viên, học sinh và quản trị viên dùng mỗi ngày — rõ
                    ràng,
                    nhanh và có AI hỗ trợ đúng chỗ.</p>
            </div>

            <div class="features-grid">
                <div class="feature-card fade-in tilt">
                    <div class="feature-icon" style="background:#eff6ff">🤖</div>
                    <h3 class="feature-title">AI trợ giảng theo từng bài học</h3>
                    <p class="feature-desc">Học sinh hỏi ngay trong bài đang học, AI trả lời dựa trên nội dung khóa học và
                        giúp giải thích lại dễ hiểu hơn.</p>
                </div>
                <div class="feature-card fade-in tilt" style="transition-delay:.08s">
                    <div class="feature-icon" style="background:#f0fdf4">📚</div>
                    <h3 class="feature-title">Soạn nội dung nhanh hơn</h3>
                    <p class="feature-desc">Giáo viên có thể tạo bài tập, quiz, rubric và gợi ý tiêu chí đánh giá từ nội
                        dung
                        bài học.</p>
                </div>
                <div class="feature-card fade-in tilt" style="transition-delay:.16s">
                    <div class="feature-icon" style="background:#fef9ec">🏫</div>
                    <h3 class="feature-title">Dashboard thao tác nhanh</h3>
                    <p class="feature-desc">Hiển thị lớp sắp dạy, bài cần chấm ưu tiên và các gợi ý cần làm dựa trên dữ
                        liệu
                        thật.</p>
                </div>
                <div class="feature-card fade-in tilt" style="transition-delay:.24s">
                    <div class="feature-icon" style="background:#fdf2f8">📋</div>
                    <h3 class="feature-title">Luồng khóa học dễ thao tác</h3>
                    <p class="feature-desc">Học sinh tiếp tục học, làm quiz, nộp bài và xem trạng thái rõ ràng hơn trong
                        cùng
                        một màn hình.</p>
                </div>
                <div class="feature-card fade-in tilt" style="transition-delay:.32s">
                    <div class="feature-icon" style="background:#f0f4ff">🎮</div>
                    <h3 class="feature-title">AI chấm bài có giáo viên duyệt</h3>
                    <p class="feature-desc">AI phân tích bài nộp, so sánh rubric, chỉ ra điểm mạnh/yếu và đề xuất nhận xét
                        để
                        giáo viên quyết định.</p>
                </div>
                <div class="feature-card fade-in tilt" style="transition-delay:.4s">
                    <div class="feature-icon" style="background:#f0fdfa">👥</div>
                    <h3 class="feature-title">Kiểm tra chất lượng khóa học</h3>
                    <p class="feature-desc">Phát hiện bài học quá ngắn, quiz thiếu câu hỏi, bài tập chưa có rubric hoặc nội
                        dung chưa đủ để AI trả lời.</p>
                </div>
            </div>
        </section>

        <!-- AI LAYER -->
        <section class="ai-layer" id="ai-layer" aria-labelledby="ai-layer-title">
            <div class="ai-layer-inner">
                <div class="fade-in">
                    <div class="section-eyebrow" style="color:#67e8f9">AI Layer</div>
                    <h2 id="ai-layer-title">AI không đứng riêng, AI nằm trong từng thao tác học tập</h2>
                    <p>SmartLMS đưa AI vào đúng điểm cần hỗ trợ: khi học sinh chưa hiểu bài, khi giáo viên soạn nội dung,
                        khi
                        chấm tự luận và khi cần rà soát chất lượng khóa học.</p>
                    <div class="ai-flow">
                        <div class="ai-flow-card">
                            <div class="ai-flow-icon">01</div>
                            <div>
                                <div class="ai-flow-title">Hiểu ngữ cảnh khóa học</div>
                                <div class="ai-flow-desc">Dựa trên bài học, tài liệu và nội dung giáo viên đã đưa vào hệ
                                    thống.</div>
                            </div>
                        </div>
                        <div class="ai-flow-card">
                            <div class="ai-flow-icon">02</div>
                            <div>
                                <div class="ai-flow-title">Gợi ý hành động tiếp theo</div>
                                <div class="ai-flow-desc">Ưu tiên bài cần chấm, lớp sắp dạy và học sinh cần được hỗ trợ.
                                </div>
                            </div>
                        </div>
                        <div class="ai-flow-card">
                            <div class="ai-flow-icon">03</div>
                            <div>
                                <div class="ai-flow-title">Giáo viên luôn là người duyệt cuối</div>
                                <div class="ai-flow-desc">AI đề xuất, giáo viên kiểm tra và quyết định phản hồi chính thức.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ai-console fade-in" style="transition-delay:.16s">
                    <div class="ai-console-head">
                        <div class="ai-console-title">AI Lesson Tutor</div>
                        <span class="card-badge">Context-aware</span>
                    </div>
                    <div class="ai-console-body">
                        <div class="ai-message user">Em chưa hiểu phần điều kiện trong JavaScript.</div>
                        <div class="ai-message system">Trong bài này, điều kiện giống như một cổng kiểm tra. Nếu điều kiện
                            đúng,
                            chương trình chạy nhánh A; nếu sai, chạy nhánh B.</div>
                        <div class="ai-evidence">Dựa trên: Bài 7 · JavaScript cơ bản · Ví dụ if/else</div>
                        <div class="ai-message system">Gợi ý ôn tập: làm lại quiz 5 câu và thử viết ví dụ kiểm tra điểm số.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section class="how" id="how" aria-labelledby="how-title">
            <div class="section-head fade-in">
                <div style="display:flex;justify-content:center">
                    <div class="section-eyebrow">Cách hoạt động</div>
                </div>
                <h2 class="section-title" id="how-title">Bắt đầu chỉ trong 3 bước đơn giản</h2>
                <p class="section-desc">Không cần cài đặt, không cần kỹ thuật — truy cập và dùng ngay trong 5 phút đầu
                    tiên.</p>
            </div>
            <div class="steps">
                <div class="step fade-in">
                    <div class="step-num">1</div>
                    <h3 class="step-title">Thiết lập lớp học</h3>
                    <p class="step-desc">Tạo lớp, gán giáo viên, thêm học sinh và đưa khóa học vào đúng nhóm người học.</p>
                </div>
                <div class="step fade-in" style="transition-delay:.15s">
                    <div class="step-num">2</div>
                    <h3 class="step-title">Kích hoạt nội dung & AI</h3>
                    <p class="step-desc">Tạo bài học, bài tập, quiz và đưa tài liệu vào để AI hỗ trợ đúng ngữ cảnh.</p>
                </div>
                <div class="step fade-in" style="transition-delay:.3s">
                    <div class="step-num">3</div>
                    <h3 class="step-title">Theo dõi và tối ưu liên tục</h3>
                    <p class="step-desc">Dashboard gợi ý việc cần làm, giáo viên xử lý nhanh và học sinh học theo lộ trình
                        rõ
                        ràng.</p>
                </div>
            </div>
        </section>

        <!-- CTA BANNER -->
        <div class="cta-section">
            <div class="free-banner fade-in">
                <div class="banner-content">
                    <h2>Sẵn sàng đưa trung tâm của bạn lên một nhịp vận hành mới?</h2>
                    <p>
                        <span>✓ Quản lý lớp học</span>
                        <span>✓ AI hỗ trợ giáo viên</span>
                        <span>✓ Học sinh thao tác dễ hơn</span>
                    </p>
                </div>
                <a class="btn-white" href="https://smartlms.io.vn/login">Truy cập SmartLMS →</a>
            </div>
        </div>

        <!-- FOOTER -->
        <footer>
            <div class="footer-container">
                <div class="footer-grid">
                    <div class="footer-brand">
                        <a href="{{ url('/') }}" class="footer-logo">SmartLMS</a>
                        <p class="footer-desc">Nền tảng quản lý đào tạo tích hợp AI dành cho trung tâm, giáo viên và học
                            sinh
                            Việt Nam. Rõ luồng, nhẹ thao tác và sẵn sàng mở rộng.</p>
                        <div class="footer-socials">
                            <a href="#" aria-label="Facebook">
                                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                            </a>
                            <a href="mailto:ngotanloi2424@gmail.com" aria-label="Email">
                                <svg width="18" height="18" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="footer-col">
                        <h4>Sản phẩm</h4>
                        <ul>
                            <li><a href="#features">Tính năng</a></li>
                            <li><a href="#how">Cách hoạt động</a></li>
                            <li><a href="#">Cập nhật mới</a></li>
                            <li><a href="#">Lộ trình phát triển</a></li>
                        </ul>
                    </div>

                    <div class="footer-col">
                        <h4>Tài nguyên</h4>
                        <ul>
                            <li><a href="#">Trung tâm trợ giúp</a></li>
                            <li><a href="#">Hướng dẫn sử dụng</a></li>
                            <li><a href="#">Cộng đồng</a></li>
                            <li><a href="mailto:ngotanloi2424@gmail.com" aria-label="Email">Liên hệ hỗ trợ</a></li>
                        </ul>
                    </div>

                    <div class="footer-col">
                        <h4>Pháp lý</h4>
                        <ul>
                            <li><a href="#">Điều khoản sử dụng</a></li>
                            <li><a href="#">Chính sách bảo mật</a></li>
                            <li><a href="#">Giấy phép mở</a></li>
                        </ul>
                    </div>
                </div>

                <div class="footer-bottom">
                    <span>© 2026 SmartLMS. Được xây dựng tại Việt Nam.</span>
                    <span><a href="https://smartlms.io.vn">smartlms.io.vn</a> · Phần mềm quản lý đào tạo AI miễn phí</span>
                </div>
            </div>
        </footer>

        <script>
            // Fade-in on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        e.target.classList.add('visible');
                        observer.unobserve(e.target);
                    }
                });
            }, {
                threshold: 0.15
            });
            document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

            // Navbar scroll state
            const nav = document.querySelector('nav');
            const handleScroll = () => {
                if (window.scrollY > 40) nav.classList.add('scrolled');
                else nav.classList.remove('scrolled');
            };
            window.addEventListener('scroll', handleScroll, {
                passive: true
            });
            handleScroll();

            // 3D tilt: hero product shell follows the cursor
            const heroVisual = document.getElementById('heroVisual');
            const shell = document.getElementById('productShell');
            if (heroVisual && shell && window.matchMedia('(min-width: 993px)').matches) {
                heroVisual.addEventListener('mousemove', (e) => {
                    const r = heroVisual.getBoundingClientRect();
                    const px = (e.clientX - r.left) / r.width - 0.5;
                    const py = (e.clientY - r.top) / r.height - 0.5;
                    const rotY = px * 16;
                    const rotX = -py * 12 + 4;
                    shell.style.transform = `rotateX(${rotX}deg) rotateY(${rotY}deg)`;
                });
                heroVisual.addEventListener('mouseleave', () => {
                    shell.style.transform = 'rotateX(4deg) rotateY(-6deg)';
                });
            }

            // 3D tilt: feature cards
            if (window.matchMedia('(min-width: 769px)').matches) {
                document.querySelectorAll('.tilt').forEach(card => {
                    card.addEventListener('mousemove', (e) => {
                        const r = card.getBoundingClientRect();
                        const px = (e.clientX - r.left) / r.width - 0.5;
                        const py = (e.clientY - r.top) / r.height - 0.5;
                        const rotY = px * 10;
                        const rotX = -py * 10;
                        card.style.transform =
                            `perspective(900px) rotateX(${rotX}deg) rotateY(${rotY}deg) translateY(-6px) translateZ(10px)`;
                    });
                    card.addEventListener('mouseleave', () => {
                        card.style.transform =
                            'perspective(900px) rotateX(0) rotateY(0) translateY(0) translateZ(0)';
                    });
                });
            }
        </script>
    @endverbatim

</body>

</html>
