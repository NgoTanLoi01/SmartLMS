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
    <link
        href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap"
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
            *,
            *::before,
            *::after {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            :root {
                --blue: #2563eb;
                --blue-dark: #1d4ed8;
                --blue-deeper: #1e40af;
                --blue-light: #eff6ff;
                --blue-mid: #dbeafe;
                --indigo: #4f46e5;
                --cyan: #06b6d4;
                --emerald: #10b981;
                --amber: #f59e0b;
                --rose: #f43f5e;
                --navy: #0f172a;
                --slate: #1e293b;
                --slate-mid: #334155;
                --muted: #64748b;
                --muted-light: #94a3b8;
                --border: #e2e8f0;
                --border-light: #f1f5f9;
                --white: #ffffff;
                --bg: #f8fafc;
                --success: #10b981;
                --radius: 12px;
                --radius-lg: 18px;
                --radius-xl: 24px;
                --shadow-glow: 0 12px 40px rgba(37, 99, 235, 0.12);
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                font-family: 'Be Vietnam Pro', sans-serif;
                background: var(--white);
                color: var(--navy);
                overflow-x: hidden;
                -webkit-font-smoothing: antialiased;
            }

            /* ─── NAV ─── */
            nav {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 100;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px 48px;
                background: rgba(255, 255, 255, 0.75);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border-bottom: 1px solid transparent;
                transition: all .35s cubic-bezier(0.4, 0, 0.2, 1);
            }

            nav.scrolled {
                padding: 10px 48px;
                background: rgba(255, 255, 255, 0.92);
                border-bottom: 1px solid rgba(226, 232, 240, 0.8);
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            }

            .nav-logo img {
                height: 42px;
                width: auto;
                transition: height .3s;
            }

            nav.scrolled .nav-logo img {
                height: 36px;
            }

            .nav-links {
                display: flex;
                align-items: center;
                gap: 28px;
                list-style: none;
            }

            .nav-links a {
                color: var(--slate-mid);
                text-decoration: none;
                font-size: 14px;
                font-weight: 500;
                transition: color .2s;
                position: relative;
            }

            .nav-links a::after {
                content: '';
                position: absolute;
                bottom: -4px;
                left: 0;
                width: 0;
                height: 2px;
                background: var(--blue);
                transition: width .2s;
            }

            .nav-links a:hover {
                color: var(--blue);
            }

            .nav-links a:hover::after {
                width: 100%;
            }

            .nav-right {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .nav-cta {
                background: var(--blue);
                color: #fff;
                border: none;
                padding: 9px 20px;
                border-radius: 999px;
                font-size: 14px;
                font-weight: 600;
                font-family: 'Be Vietnam Pro', sans-serif;
                cursor: pointer;
                text-decoration: none;
                transition: all .2s;
                display: inline-block;
            }

            .nav-cta:hover {
                background: var(--blue-dark);
                transform: translateY(-1px);
                box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
            }

            /* ─── HERO ─── */
            .hero {
                min-height: 92vh;
                display: flex;
                align-items: center;
                padding: 124px 48px 92px;
                position: relative;
                overflow: hidden;
                background:
                    linear-gradient(90deg, rgba(248, 250, 252, .98) 0%, rgba(248, 250, 252, .94) 38%, rgba(248, 250, 252, .72) 62%, rgba(248, 250, 252, .86) 100%),
                    url("/auth-img1.png") no-repeat right 8% bottom 6% / min(46vw, 560px),
                    #f8fafc;
            }

            .hero-bg {
                position: absolute;
                inset: 0;
                pointer-events: none;
                z-index: 0;
                overflow: hidden;
            }

            .hero-bg::before {
                display: none;
            }

            .hero-bg::after {
                display: none;
            }

            .hero-bg-dot {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-image: radial-gradient(circle, rgba(37, 99, 235, 0.04) 1px, transparent 1px);
                background-size: 32px 32px;
                mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 20%, transparent 80%);
            }

            .hero-inner {
                max-width: 1200px;
                margin: 0 auto;
                width: 100%;
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(360px, 470px);
                gap: 56px;
                align-items: center;
                position: relative;
                z-index: 1;
            }

            .hero-kicker {
                display: grid;
                gap: 10px;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                margin-top: 32px;
                max-width: 640px;
            }

            .kicker-card {
                background: rgba(255, 255, 255, .76);
                border: 1px solid rgba(226, 232, 240, .92);
                border-radius: 16px;
                padding: 14px;
                box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
            }

            .kicker-value {
                color: var(--navy);
                font-size: 22px;
                font-weight: 800;
                line-height: 1;
                margin-bottom: 6px;
            }

            .kicker-label {
                color: var(--muted);
                font-size: 12px;
                font-weight: 600;
                line-height: 1.35;
            }

            .badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: rgba(37, 99, 235, 0.06);
                color: var(--blue);
                border: 1px solid rgba(37, 99, 235, 0.12);
                border-radius: 999px;
                padding: 6px 16px 6px 10px;
                font-size: 13px;
                font-weight: 600;
                margin-bottom: 24px;
                font-family: 'Be Vietnam Pro', sans-serif;
            }

            .badge-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--success);
                box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
                animation: pulse 2s infinite;
            }

            @keyframes pulse {

                0%,
                100% {
                    opacity: 1;
                    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
                }

                50% {
                    opacity: .8;
                    box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
                }
            }

            h1 {
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: clamp(40px, 5vw, 68px);
                font-weight: 800;
                line-height: 1.02;
                letter-spacing: -2.6px;
                margin-bottom: 24px;
                color: var(--navy);
            }

            h1 .accent {
                background: linear-gradient(135deg, var(--blue) 0%, var(--indigo) 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                position: relative;
            }

            .hero-desc {
                font-size: 18px;
                color: var(--muted);
                line-height: 1.75;
                margin-bottom: 34px;
                font-weight: 400;
                max-width: 620px;
            }

            .hero-actions {
                display: flex;
                gap: 14px;
                flex-wrap: wrap;
                align-items: center;
                margin-bottom: 48px;
            }

            .btn-primary {
                background: linear-gradient(135deg, var(--blue) 0%, var(--blue-dark) 100%);
                color: #fff;
                border: none;
                padding: 14px 28px;
                border-radius: 999px;
                font-size: 15px;
                font-weight: 600;
                font-family: 'Be Vietnam Pro', sans-serif;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all .3s;
                box-shadow: 0 8px 24px rgba(37, 99, 235, 0.3);
            }

            .btn-primary:hover {
                transform: translateY(-3px);
                box-shadow: 0 14px 32px rgba(37, 99, 235, 0.4);
            }

            .btn-ghost {
                color: var(--slate-mid);
                font-size: 15px;
                font-weight: 500;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: all .2s;
                padding: 14px 22px;
                border-radius: 999px;
                border: 1.5px solid var(--border);
                background: rgba(255, 255, 255, 0.5);
            }

            .btn-ghost:hover {
                border-color: var(--blue-mid);
                color: var(--blue);
                background: var(--blue-light);
            }

            .trust-bar {
                display: flex;
                align-items: center;
                gap: 24px;
                padding-top: 0;
                border-top: none;
                flex-wrap: wrap;
            }

            .trust-item {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 13.5px;
                color: var(--muted);
                font-weight: 500;
            }

            .trust-icon {
                width: 22px;
                height: 22px;
                background: #f0fdf4;
                color: var(--success);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: 700;
            }

            /* ─── HERO VISUAL ─── */
            .hero-visual {
                position: relative;
                perspective: 1000px;
            }

            .dashboard-card {
                background: rgba(255, 255, 255, 0.92);
                -webkit-backdrop-filter: blur(16px);
                border-radius: 22px;
                border: 1px solid rgba(226, 232, 240, 0.9);
                padding: 24px;
                box-shadow: 0 30px 70px rgba(15, 23, 42, 0.13);
                transition: transform 0.4s ease;
            }

            .dashboard-card:hover {
                transform: translateY(-5px) rotateX(2deg) rotateY(2deg);
            }

            .card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 20px;
            }

            .card-title {
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 15px;
                font-weight: 700;
                color: var(--navy);
            }

            .card-badge {
                background: #ecfdf5;
                color: #059669;
                font-size: 12px;
                font-weight: 600;
                padding: 4px 12px;
                border-radius: 999px;
            }

            .class-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .class-item {
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 12px 14px;
                border-radius: var(--radius);
                background: rgba(255, 255, 255, 0.6);
                border: 1px solid var(--border-light);
                transition: all .25s;
            }

            .product-shell {
                position: relative;
            }

            .command-top {
                align-items: center;
                background: #0f172a;
                border-radius: 18px 18px 0 0;
                color: rgba(255, 255, 255, .76);
                display: flex;
                gap: 8px;
                justify-content: space-between;
                margin: -24px -24px 20px;
                padding: 14px 18px;
            }

            .command-dots {
                display: flex;
                gap: 6px;
            }

            .command-dots span {
                border-radius: 999px;
                display: block;
                height: 8px;
                width: 8px;
            }

            .command-dots span:nth-child(1) {
                background: var(--rose);
            }

            .command-dots span:nth-child(2) {
                background: var(--amber);
            }

            .command-dots span:nth-child(3) {
                background: var(--emerald);
            }

            .command-label {
                font-size: 12px;
                font-weight: 700;
            }

            .ai-brief {
                background: #0f172a;
                border: 1px solid rgba(255, 255, 255, .08);
                border-radius: 18px;
                bottom: -34px;
                box-shadow: 0 22px 60px rgba(15, 23, 42, .24);
                color: #fff;
                left: -38px;
                max-width: 280px;
                padding: 16px;
                position: absolute;
            }

            .ai-brief__label {
                color: #67e8f9;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: .1em;
                margin-bottom: 8px;
                text-transform: uppercase;
            }

            .ai-brief__text {
                color: rgba(255, 255, 255, .78);
                font-size: 13px;
                line-height: 1.55;
            }

            .class-item:hover {
                border-color: var(--blue-mid);
                background: #fff;
                box-shadow: 0 4px 12px rgba(37, 99, 235, 0.06);
            }

            .class-icon {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
                flex-shrink: 0;
            }

            .class-info {
                flex: 1;
            }

            .class-name {
                font-size: 13.5px;
                font-weight: 600;
                color: var(--navy);
                margin-bottom: 3px;
            }

            .class-meta {
                font-size: 12px;
                color: var(--muted-light);
            }

            .class-progress {
                width: 78px;
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 5px;
                flex-shrink: 0;
            }

            .progress-bar {
                width: 78px;
                height: 5px;
                background: var(--border);
                border-radius: 4px;
                overflow: hidden;
            }

            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, var(--blue), var(--indigo));
                border-radius: 4px;
                transition: width 1s ease-out;
            }

            .progress-text {
                font-size: 11px;
                font-weight: 700;
                color: var(--blue);
                max-width: 78px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .floating-chip {
                position: absolute;
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.8);
                border-radius: var(--radius);
                padding: 10px 16px;
                box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 13px;
                font-weight: 600;
                color: var(--navy);
                white-space: nowrap;
            }

            .chip-1 {
                top: -24px;
                right: 16px;
                animation: float1 4s ease-in-out infinite;
            }

            .chip-2 {
                bottom: -20px;
                left: 12px;
                animation: float2 4.5s ease-in-out infinite;
            }

            @keyframes float1 {

                0%,
                100% {
                    transform: translateY(0)
                }

                50% {
                    transform: translateY(-10px)
                }
            }

            @keyframes float2 {

                0%,
                100% {
                    transform: translateY(0)
                }

                50% {
                    transform: translateY(8px)
                }
            }

            .chip-icon {
                width: 30px;
                height: 30px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 15px;
            }

            /* ─── FEATURES ─── */
            .features {
                padding: 112px 48px;
                background: #fff;
                position: relative;
            }

            .section-eyebrow {
                text-align: center;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                font-size: 12px;
                font-weight: 700;
                color: var(--blue);
                letter-spacing: .12em;
                text-transform: uppercase;
                margin-bottom: 16px;
            }

            .section-eyebrow::before,
            .section-eyebrow::after {
                content: '';
                width: 32px;
                height: 2px;
                background: var(--blue-mid);
                border-radius: 2px;
            }

            .section-head {
                text-align: center;
                margin-bottom: 64px;
            }

            .section-title {
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: clamp(28px, 3vw, 40px);
                font-weight: 800;
                letter-spacing: -1px;
                margin-bottom: 16px;
                color: var(--navy);
            }

            .section-desc {
                color: var(--muted);
                font-size: 16.5px;
                max-width: 520px;
                margin: 0 auto;
                line-height: 1.7;
            }

            .features-grid {
                max-width: 1140px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 24px;
            }

            .feature-card {
                background: #fff;
                border-radius: var(--radius-lg);
                border: 1px solid var(--border);
                padding: 32px 28px;
                transition: all .3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            }

            .ai-layer {
                background: #0f172a;
                color: #fff;
                overflow: hidden;
                padding: 104px 48px;
                position: relative;
            }

            .ai-layer::before {
                background-image:
                    linear-gradient(rgba(255, 255, 255, .045) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255, 255, 255, .045) 1px, transparent 1px);
                background-size: 42px 42px;
                content: '';
                inset: 0;
                opacity: .45;
                position: absolute;
            }

            .ai-layer-inner {
                align-items: center;
                display: grid;
                gap: 56px;
                grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr);
                margin: 0 auto;
                max-width: 1140px;
                position: relative;
                z-index: 1;
            }

            .ai-layer h2 {
                color: #fff;
                font-size: clamp(30px, 4vw, 48px);
                font-weight: 800;
                letter-spacing: -1.6px;
                line-height: 1.1;
                margin-bottom: 18px;
            }

            .ai-layer p {
                color: rgba(255, 255, 255, .68);
                font-size: 16px;
                line-height: 1.8;
                margin-bottom: 28px;
            }

            .ai-flow {
                display: grid;
                gap: 12px;
            }

            .ai-flow-card {
                align-items: center;
                background: rgba(255, 255, 255, .08);
                border: 1px solid rgba(255, 255, 255, .12);
                border-radius: 18px;
                display: flex;
                gap: 14px;
                padding: 16px;
            }

            .ai-flow-icon {
                align-items: center;
                background: rgba(103, 232, 249, .14);
                border-radius: 14px;
                color: #67e8f9;
                display: flex;
                flex-shrink: 0;
                font-size: 18px;
                height: 44px;
                justify-content: center;
                width: 44px;
            }

            .ai-flow-title {
                color: #fff;
                font-size: 14px;
                font-weight: 800;
                margin-bottom: 4px;
            }

            .ai-flow-desc {
                color: rgba(255, 255, 255, .58);
                font-size: 13px;
                line-height: 1.5;
            }

            .ai-console {
                background: rgba(255, 255, 255, .96);
                border-radius: 24px;
                box-shadow: 0 32px 80px rgba(0, 0, 0, .24);
                color: var(--navy);
                overflow: hidden;
            }

            .ai-console-head {
                align-items: center;
                background: #f8fafc;
                border-bottom: 1px solid var(--border);
                display: flex;
                justify-content: space-between;
                padding: 16px 18px;
            }

            .ai-console-title {
                font-size: 13px;
                font-weight: 800;
            }

            .ai-console-body {
                display: grid;
                gap: 12px;
                padding: 18px;
            }

            .ai-message {
                border-radius: 16px;
                font-size: 13px;
                line-height: 1.6;
                padding: 14px;
            }

            .ai-message.user {
                background: #eef2ff;
                color: #312e81;
                margin-left: 44px;
            }

            .ai-message.system {
                background: #f8fafc;
                border: 1px solid var(--border);
                color: var(--slate-mid);
                margin-right: 32px;
            }

            .ai-evidence {
                background: #ecfdf5;
                border: 1px solid #bbf7d0;
                border-radius: 14px;
                color: #065f46;
                font-size: 12px;
                font-weight: 700;
                padding: 10px 12px;
            }

            .feature-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: linear-gradient(90deg, var(--blue), var(--indigo));
                opacity: 0;
                transition: opacity .3s;
            }

            .feature-card:hover {
                background: #fff;
                border-color: rgba(37, 99, 235, 0.15);
                transform: translateY(-6px);
                box-shadow: var(--shadow-glow);
            }

            .feature-card:hover::before {
                opacity: 1;
            }

            .feature-icon {
                width: 50px;
                height: 50px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
                margin-bottom: 20px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            }

            .feature-title {
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 16px;
                font-weight: 700;
                color: var(--navy);
                margin-bottom: 10px;
            }

            .feature-desc {
                font-size: 14px;
                color: var(--muted);
                line-height: 1.7;
            }

            /* ─── HOW IT WORKS ─── */
            .how {
                padding: 120px 48px;
                background: linear-gradient(180deg, var(--bg) 0%, #fff 100%);
            }

            .steps {
                max-width: 960px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 32px;
                position: relative;
            }

            .steps::before {
                content: '';
                position: absolute;
                top: 36px;
                left: calc(16.5% + 20px);
                right: calc(16.5% + 20px);
                height: 2px;
                background: var(--blue-mid);
                mask-image: repeating-linear-gradient(90deg, #000 0, #000 6px, transparent 6px, transparent 14px);
                -webkit-mask-image: repeating-linear-gradient(90deg, #000 0, #000 6px, transparent 6px, transparent 14px);
            }

            .step {
                text-align: center;
                position: relative;
            }

            .step-num {
                width: 70px;
                height: 70px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--blue) 0%, var(--indigo) 100%);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 22px;
                font-weight: 800;
                margin: 0 auto 22px;
                position: relative;
                z-index: 1;
                box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
                transition: transform .3s, box-shadow .3s;
            }

            .step:hover .step-num {
                transform: scale(1.1);
                box-shadow: 0 14px 35px rgba(37, 99, 235, 0.4);
            }

            .step-title {
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 17px;
                font-weight: 700;
                color: var(--navy);
                margin-bottom: 10px;
            }

            .step-desc {
                font-size: 14px;
                color: var(--muted);
                line-height: 1.7;
                max-width: 240px;
                margin: 0 auto;
            }

            /* ─── CTA BANNER ─── */
            .cta-section {
                padding: 0 48px 120px;
            }

            .free-banner {
                max-width: 1140px;
                margin: 0 auto;
                padding: 64px 56px;
                background: linear-gradient(135deg, var(--blue) 0%, var(--indigo) 100%);
                border-radius: var(--radius-xl);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 32px;
                box-shadow: 0 30px 70px rgba(37, 99, 235, 0.3);
                position: relative;
                overflow: hidden;
            }

            .free-banner::before {
                content: '';
                position: absolute;
                top: -50%;
                right: -20%;
                width: 600px;
                height: 600px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.06);
                pointer-events: none;
            }

            .free-banner::after {
                content: '';
                position: absolute;
                bottom: -40%;
                left: -10%;
                width: 400px;
                height: 400px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.04);
                pointer-events: none;
            }

            .banner-content {
                position: relative;
                z-index: 1;
            }

            .banner-content h2 {
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: clamp(24px, 3vw, 36px);
                font-weight: 800;
                letter-spacing: -.5px;
                margin-bottom: 12px;
            }

            .banner-content p {
                font-size: 16px;
                opacity: .9;
                line-height: 1.6;
                display: flex;
                flex-wrap: wrap;
                gap: 16px;
            }

            .banner-content p span {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .btn-white {
                background: #fff;
                color: var(--blue);
                border: none;
                padding: 16px 32px;
                border-radius: 999px;
                font-size: 16px;
                font-weight: 700;
                font-family: 'Be Vietnam Pro', sans-serif;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                white-space: nowrap;
                transition: all .3s;
                flex-shrink: 0;
                position: relative;
                z-index: 1;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            }

            .btn-white:hover {
                transform: translateY(-3px) scale(1.02);
                box-shadow: 0 14px 30px rgba(0, 0, 0, 0.2);
            }

            /* ─── FOOTER ─── */
            footer {
                background: var(--navy);
                padding: 72px 48px 32px;
                color: rgba(255, 255, 255, 0.6);
            }

            .footer-container {
                max-width: 1140px;
                margin: 0 auto;
            }

            .footer-grid {
                display: grid;
                grid-template-columns: 1.6fr 1fr 1fr 1fr;
                gap: 48px;
                padding-bottom: 52px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            }

            .footer-brand .footer-logo {
                font-family: 'Be Vietnam Pro', sans-serif;
                font-weight: 800;
                font-size: 24px;
                color: #fff;
                text-decoration: none;
                display: inline-block;
                margin-bottom: 18px;
            }

            .footer-desc {
                font-size: 14.5px;
                line-height: 1.7;
                margin-bottom: 24px;
                color: rgba(255, 255, 255, 0.5);
                max-width: 300px;
            }

            .footer-socials {
                display: flex;
                gap: 10px;
            }

            .footer-socials a {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 38px;
                height: 38px;
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.06);
                color: rgba(255, 255, 255, 0.6);
                transition: all .25s;
            }

            .footer-socials a:hover {
                background: var(--blue);
                color: #fff;
                transform: translateY(-2px);
            }

            .footer-col h4 {
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 14px;
                font-weight: 700;
                color: #fff;
                margin-bottom: 20px;
                letter-spacing: 0.02em;
            }

            .footer-col ul {
                list-style: none;
                display: flex;
                flex-direction: column;
                gap: 14px;
            }

            .footer-col ul li a {
                color: rgba(255, 255, 255, 0.45);
                text-decoration: none;
                font-size: 14px;
                transition: color .2s, padding-left .2s;
                display: inline-block;
            }

            .footer-col ul li a:hover {
                color: rgba(255, 255, 255, 0.95);
                padding-left: 4px;
            }

            .footer-bottom {
                margin-top: 32px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 16px;
                font-size: 13px;
                color: rgba(255, 255, 255, 0.3);
            }

            .footer-bottom a {
                color: rgba(255, 255, 255, 0.4);
                text-decoration: none;
                transition: color .2s;
            }

            .footer-bottom a:hover {
                color: rgba(255, 255, 255, 0.8);
            }

            /* ─── ANIMATIONS ─── */
            .fade-in {
                opacity: 0;
                transform: translateY(30px);
                transition: opacity .8s cubic-bezier(0.4, 0, 0.2, 1), transform .8s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .fade-in.visible {
                opacity: 1;
                transform: translateY(0);
            }

            /* ─── RESPONSIVE ─── */
            @media (max-width: 1024px) {
                .features-grid {
                    grid-template-columns: repeat(2, 1fr);
                }

                .hero {
                    background:
                        linear-gradient(90deg, rgba(248, 250, 252, .98) 0%, rgba(248, 250, 252, .92) 100%),
                        url("/auth-img1.png") no-repeat right 4% top 110px / 360px,
                        #f8fafc;
                }

                .hero-inner,
                .ai-layer-inner {
                    grid-template-columns: 1fr;
                }

                .ai-brief {
                    left: 20px;
                }

                .footer-grid {
                    grid-template-columns: 1fr 1fr;
                    gap: 36px;
                }
            }

            @media (max-width: 768px) {
                nav {
                    padding: 12px 20px;
                }

                nav.scrolled {
                    padding: 10px 20px;
                }

                .nav-links {
                    display: none;
                }

                .hero {
                    background: #f8fafc;
                    padding: 96px 20px 64px;
                }

                .hero-inner {
                    grid-template-columns: 1fr;
                    gap: 32px;
                }

                h1 {
                    letter-spacing: -1.4px;
                }

                .hero-visual {
                    transform: none;
                    transform-origin: top center;
                }

                .hero-kicker {
                    grid-template-columns: 1fr;
                }

                .dashboard-card {
                    padding: 18px;
                }

                .command-top {
                    margin: -18px -18px 16px;
                }

                .class-item {
                    align-items: flex-start;
                    gap: 10px;
                }

                .class-progress {
                    display: none;
                }

                .ai-brief {
                    display: none;
                }

                .floating-chip {
                    display: none;
                }

                .features {
                    padding: 80px 20px;
                }

                .features-grid {
                    grid-template-columns: 1fr;
                }

                .how {
                    padding: 80px 20px;
                }

                .ai-layer {
                    padding: 80px 20px;
                }

                .ai-layer-inner {
                    grid-template-columns: 1fr;
                    gap: 32px;
                }

                .ai-message.user,
                .ai-message.system {
                    margin-left: 0;
                    margin-right: 0;
                }

                .steps {
                    grid-template-columns: 1fr;
                    gap: 32px;
                }

                .steps::before {
                    display: none;
                }

                .cta-section {
                    padding: 0 20px 80px;
                }

                .free-banner {
                    flex-direction: column;
                    padding: 40px 28px;
                    text-align: center;
                }

                .banner-content p {
                    justify-content: center;
                }

                footer {
                    padding: 48px 20px 24px;
                }

                .footer-grid {
                    grid-template-columns: 1fr;
                    gap: 32px;
                }

                .footer-bottom {
                    flex-direction: column;
                    text-align: center;
                }
            }
        </style>
    @endverbatim
</head>

<body>

    <!-- NAV -->
    <nav>
        <a class="nav-logo" href="{{ url('/') }}" aria-label="SmartLMS - Trang chủ">
            <img src="{{ asset('smartlms-logo-sharpened.png') }}" alt="SmartLMS Logo">
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
                <p class="hero-desc">Một không gian dạy học hiện đại nơi lớp học, khóa học, bài nộp, quiz và trợ giảng AI
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
                        <div class="trust-icon">✓</div>
                        Giáo viên thao tác nhanh
                    </div>
                    <div class="trust-item">
                        <div class="trust-icon">✓</div>
                        Học sinh học rõ luồng
                    </div>
                    <div class="trust-item">
                        <div class="trust-icon">✓</div>
                        AI hiểu nội dung khóa học
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

            <div class="hero-visual fade-in" style="transition-delay:.2s" aria-hidden="true">
                <div class="product-shell">
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
                        <div class="ai-brief__text">Ưu tiên chấm bài quá hạn trước, sau đó chuẩn bị ví dụ cho lớp Web cơ bản.</div>
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
            <p class="section-desc">Tập trung vào những việc giáo viên, học sinh và quản trị viên dùng mỗi ngày — rõ ràng,
                nhanh và có AI hỗ trợ đúng chỗ.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card fade-in">
                <div class="feature-icon" style="background:#eff6ff">🤖</div>
                <h3 class="feature-title">AI trợ giảng theo từng bài học</h3>
                <p class="feature-desc">Học sinh hỏi ngay trong bài đang học, AI trả lời dựa trên nội dung khóa học và
                    giúp giải thích lại dễ hiểu hơn.</p>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.08s">
                <div class="feature-icon" style="background:#f0fdf4">📚</div>
                <h3 class="feature-title">Soạn nội dung nhanh hơn</h3>
                <p class="feature-desc">Giáo viên có thể tạo bài tập, quiz, rubric và gợi ý tiêu chí đánh giá từ nội dung
                    bài học.</p>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.16s">
                <div class="feature-icon" style="background:#fef9ec">🏫</div>
                <h3 class="feature-title">Dashboard thao tác nhanh</h3>
                <p class="feature-desc">Hiển thị lớp sắp dạy, bài cần chấm ưu tiên và các gợi ý cần làm dựa trên dữ liệu
                    thật.</p>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.24s">
                <div class="feature-icon" style="background:#fdf2f8">📋</div>
                <h3 class="feature-title">Luồng khóa học dễ thao tác</h3>
                <p class="feature-desc">Học sinh tiếp tục học, làm quiz, nộp bài và xem trạng thái rõ ràng hơn trong cùng
                    một màn hình.</p>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.32s">
                <div class="feature-icon" style="background:#f0f4ff">🎮</div>
                <h3 class="feature-title">AI chấm bài có giáo viên duyệt</h3>
                <p class="feature-desc">AI phân tích bài nộp, so sánh rubric, chỉ ra điểm mạnh/yếu và đề xuất nhận xét để
                    giáo viên quyết định.</p>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.4s">
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
                <p>SmartLMS đưa AI vào đúng điểm cần hỗ trợ: khi học sinh chưa hiểu bài, khi giáo viên soạn nội dung, khi
                    chấm tự luận và khi cần rà soát chất lượng khóa học.</p>
                <div class="ai-flow">
                    <div class="ai-flow-card">
                        <div class="ai-flow-icon">01</div>
                        <div>
                            <div class="ai-flow-title">Hiểu ngữ cảnh khóa học</div>
                            <div class="ai-flow-desc">Dựa trên bài học, tài liệu và nội dung giáo viên đã đưa vào hệ thống.</div>
                        </div>
                    </div>
                    <div class="ai-flow-card">
                        <div class="ai-flow-icon">02</div>
                        <div>
                            <div class="ai-flow-title">Gợi ý hành động tiếp theo</div>
                            <div class="ai-flow-desc">Ưu tiên bài cần chấm, lớp sắp dạy và học sinh cần được hỗ trợ.</div>
                        </div>
                    </div>
                    <div class="ai-flow-card">
                        <div class="ai-flow-icon">03</div>
                        <div>
                            <div class="ai-flow-title">Giáo viên luôn là người duyệt cuối</div>
                            <div class="ai-flow-desc">AI đề xuất, giáo viên kiểm tra và quyết định phản hồi chính thức.</div>
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
                    <div class="ai-message system">Trong bài này, điều kiện giống như một cổng kiểm tra. Nếu điều kiện đúng,
                        chương trình chạy nhánh A; nếu sai, chạy nhánh B.</div>
                    <div class="ai-evidence">Dựa trên: Bài 7 · JavaScript cơ bản · Ví dụ if/else</div>
                    <div class="ai-message system">Gợi ý ôn tập: làm lại quiz 5 câu và thử viết ví dụ kiểm tra điểm số.</div>
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
                <p class="step-desc">Tạo lớp, gán giáo viên, thêm học sinh và đưa khóa học vào đúng nhóm người học.
                </p>
            </div>
            <div class="step fade-in" style="transition-delay:.15s">
                <div class="step-num">2</div>
                <h3 class="step-title">Kích hoạt nội dung & AI</h3>
                <p class="step-desc">Tạo bài học, bài tập, quiz và đưa tài liệu vào để AI hỗ trợ đúng ngữ cảnh.</p>
            </div>
            <div class="step fade-in" style="transition-delay:.3s">
                <div class="step-num">3</div>
                <h3 class="step-title">Theo dõi và tối ưu liên tục</h3>
                <p class="step-desc">Dashboard gợi ý việc cần làm, giáo viên xử lý nhanh và học sinh học theo lộ trình rõ
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
                <!-- Column 1: Brand Info -->
                <div class="footer-brand">
                    <a href="{{ url('/') }}" class="footer-logo">SmartLMS</a>
                    <p class="footer-desc">Nền tảng quản lý đào tạo tích hợp AI dành cho trung tâm, giáo viên và học sinh
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

                <!-- Column 2: Product -->
                <div class="footer-col">
                    <h4>Sản phẩm</h4>
                    <ul>
                        <li><a href="#features">Tính năng</a></li>
                        <li><a href="#how">Cách hoạt động</a></li>
                        <li><a href="#">Cập nhật mới</a></li>
                        <li><a href="#">Lộ trình phát triển</a></li>
                    </ul>
                </div>

                <!-- Column 3: Resources -->
                <div class="footer-col">
                    <h4>Tài nguyên</h4>
                    <ul>
                        <li><a href="#">Trung tâm trợ giúp</a></li>
                        <li><a href="#">Hướng dẫn sử dụng</a></li>
                        <li><a href="#">Cộng đồng</a></li>
                        <li><a href="mailto:ngotanloi2424@gmail.com" aria-label="Email">Liên hệ hỗ trợ</a></li>
                    </ul>
                </div>

                <!-- Column 4: Legal -->
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

    @verbatim
        <script>
            // Intersection Observer for fade-in animations
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

            // Navbar scroll effect
            const nav = document.querySelector('nav');
            const handleScroll = () => {
                if (window.scrollY > 40) {
                    nav.classList.add('scrolled');
                } else {
                    nav.classList.remove('scrolled');
                }
            };

            window.addEventListener('scroll', handleScroll, {
                passive: true
            });
            handleScroll(); // Init on load
        </script>
    @endverbatim

</body>

</html>
