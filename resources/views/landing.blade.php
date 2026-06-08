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
                min-height: 100vh;
                display: flex;
                align-items: center;
                padding: 120px 48px 80px;
                position: relative;
                overflow: hidden;
                background: #fff;
            }

            .hero-bg {
                position: absolute;
                inset: 0;
                pointer-events: none;
                z-index: 0;
                overflow: hidden;
            }

            .hero-bg::before {
                content: '';
                position: absolute;
                top: -20%;
                right: -10%;
                width: 800px;
                height: 800px;
                border-radius: 50%;
                background: radial-gradient(circle at 50% 50%, rgba(37, 99, 235, 0.08) 0%, transparent 65%);
            }

            .hero-bg::after {
                content: '';
                position: absolute;
                bottom: -15%;
                left: -10%;
                width: 600px;
                height: 600px;
                border-radius: 50%;
                background: radial-gradient(circle at 50% 50%, rgba(79, 70, 229, 0.06) 0%, transparent 65%);
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
                grid-template-columns: 1fr 1fr;
                gap: 72px;
                align-items: center;
                position: relative;
                z-index: 1;
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
                font-size: clamp(36px, 4vw, 56px);
                font-weight: 800;
                line-height: 1.1;
                letter-spacing: -2px;
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
                font-size: 17px;
                color: var(--muted);
                line-height: 1.75;
                margin-bottom: 40px;
                font-weight: 400;
                max-width: 520px;
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
                padding-top: 32px;
                border-top: 1px solid var(--border-light);
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
                background: rgba(255, 255, 255, 0.85);
                -webkit-backdrop-filter: blur(16px);
                border-radius: var(--radius-xl);
                border: 1px solid rgba(255, 255, 255, 0.6);
                padding: 24px;
                box-shadow: 0 30px 60px rgba(0, 0, 0, 0.08), var(--shadow-glow);
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
                width: 56px;
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 5px;
            }

            .progress-bar {
                width: 56px;
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
                padding: 120px 48px;
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
                background: var(--bg);
                border-radius: var(--radius-lg);
                border: 1px solid var(--border-light);
                padding: 32px 28px;
                transition: all .3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
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
                    padding: 100px 20px 60px;
                }

                .hero-inner {
                    grid-template-columns: 1fr;
                    gap: 40px;
                }

                .hero-visual {
                    transform: scale(0.9);
                    transform-origin: top center;
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
                    Miễn phí 100% · Không giới hạn
                </div>
                <h1>Phần mềm quản lý <span class="accent">trung tâm đào tạo</span> tích hợp AI</h1>
                <p class="hero-desc">SmartLMS giúp bạn quản lý lớp học, giao bài tập, xây dựng ngân hàng câu hỏi và huấn
                    luyện AI từ tài liệu của chính mình — hoàn toàn miễn phí.</p>
                <div class="hero-actions">
                    <a class="btn-primary" href="https://smartlms.io.vn/login">
                        Bắt đầu miễn phí
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <a class="btn-ghost" href="#features">Xem tính năng</a>
                </div>
                <div class="trust-bar">
                    <div class="trust-item">
                        <div class="trust-icon">✓</div>
                        Không cần cài đặt
                    </div>
                    <div class="trust-item">
                        <div class="trust-icon">✓</div>
                        Không cần thẻ tín dụng
                    </div>
                    <div class="trust-item">
                        <div class="trust-icon">✓</div>
                        Hỗ trợ tiếng Việt
                    </div>
                </div>
            </div>

            <div class="hero-visual fade-in" style="transition-delay:.2s" aria-hidden="true">
                <div style="position:relative">
                    <div class="floating-chip chip-1">
                        <div class="chip-icon" style="background:#f0fdf4">🤖</div>
                        AI đã sẵn sàng hỗ trợ
                    </div>
                    <div class="dashboard-card">
                        <div class="card-header">
                            <span class="card-title">Lớp học đang hoạt động</span>
                            <span class="card-badge">● 3 lớp</span>
                        </div>
                        <div class="class-list">
                            <div class="class-item">
                                <div class="class-icon" style="background:#eff6ff">💻</div>
                                <div class="class-info">
                                    <div class="class-name">Lập trình Web cơ bản</div>
                                    <div class="class-meta">24 học viên · Buổi 8/12</div>
                                </div>
                                <div class="class-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width:67%"></div>
                                    </div>
                                    <span class="progress-text">67%</span>
                                </div>
                            </div>
                            <div class="class-item">
                                <div class="class-icon" style="background:#fef9ec">🎨</div>
                                <div class="class-info">
                                    <div class="class-name">Thiết kế đồ họa</div>
                                    <div class="class-meta">18 học viên · Buổi 5/10</div>
                                </div>
                                <div class="class-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width:50%"></div>
                                    </div>
                                    <span class="progress-text">50%</span>
                                </div>
                            </div>
                            <div class="class-item">
                                <div class="class-icon" style="background:#f0fdf4">📊</div>
                                <div class="class-info">
                                    <div class="class-name">Excel nâng cao</div>
                                    <div class="class-meta">31 học viên · Buổi 3/8</div>
                                </div>
                                <div class="class-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width:38%"></div>
                                    </div>
                                    <span class="progress-text">38%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="floating-chip chip-2">
                        <div class="chip-icon" style="background:#fef3c7">📝</div>
                        42 câu hỏi mới từ AI
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
            <h2 class="section-title" id="features-title">Mọi thứ bạn cần để quản lý đào tạo</h2>
            <p class="section-desc">Được xây dựng riêng cho các trung tâm đào tạo tư nhân tại Việt Nam — đơn giản, thực
                dụng và đầy đủ.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card fade-in">
                <div class="feature-icon" style="background:#eff6ff">🤖</div>
                <h3 class="feature-title">Huấn luyện AI từ tài liệu</h3>
                <p class="feature-desc">Tải lên giáo trình hoặc slide bài giảng — AI sẽ học và tự động hỗ trợ học viên
                    giải đáp thắc mắc theo đúng nội dung bạn cung cấp.</p>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.08s">
                <div class="feature-icon" style="background:#f0fdf4">📚</div>
                <h3 class="feature-title">Ngân hàng câu hỏi thông minh</h3>
                <p class="feature-desc">Tạo và quản lý hàng trăm câu hỏi, phân loại theo chủ đề. AI có thể tự động gợi
                    ý câu hỏi mới từ tài liệu của bạn.</p>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.16s">
                <div class="feature-icon" style="background:#fef9ec">🏫</div>
                <h3 class="feature-title">Quản lý lớp học & lịch học</h3>
                <p class="feature-desc">Theo dõi danh sách học viên, sắp xếp lịch học, ghi nhận điểm danh và tiến độ
                    học tập một cách trực quan.</p>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.24s">
                <div class="feature-icon" style="background:#fdf2f8">📋</div>
                <h3 class="feature-title">Giao bài & quản lý khóa học</h3>
                <p class="feature-desc">Tạo bài tập, giao cho học viên và theo dõi kết quả nộp bài. Toàn bộ quy trình
                    tập trung tại một nơi duy nhất.</p>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.32s">
                <div class="feature-icon" style="background:#f0f4ff">🎮</div>
                <h3 class="feature-title">Công cụ hỗ trợ học tập</h3>
                <p class="feature-desc">Tích hợp máy tính điểm, trình soạn thảo code và các tiện ích giúp học viên học
                    hiệu quả hơn trong từng buổi học.</p>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.4s">
                <div class="feature-icon" style="background:#f0fdfa">👥</div>
                <h3 class="feature-title">Phân quyền rõ ràng</h3>
                <p class="feature-desc">Admin, giáo viên và học viên có giao diện riêng biệt, đảm bảo mỗi người chỉ
                    thấy và làm việc với đúng thông tin của mình.</p>
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
                <h3 class="step-title">Đăng nhập tài khoản</h3>
                <p class="step-desc">Truy cập smartlms.io.vn và đăng nhập — không cần tải hay cài đặt bất kỳ thứ gì.
                </p>
            </div>
            <div class="step fade-in" style="transition-delay:.15s">
                <div class="step-num">2</div>
                <h3 class="step-title">Tạo lớp & tải tài liệu</h3>
                <p class="step-desc">Thêm học viên, tạo lịch học và tải lên giáo trình để AI bắt đầu học theo nội dung
                    của bạn.</p>
            </div>
            <div class="step fade-in" style="transition-delay:.3s">
                <div class="step-num">3</div>
                <h3 class="step-title">Dạy học thông minh hơn</h3>
                <p class="step-desc">AI hỗ trợ học viên 24/7, bạn chỉ cần tập trung vào giảng dạy và theo dõi kết quả
                    học tập.</p>
            </div>
        </div>
    </section>

    <!-- CTA BANNER -->
    <div class="cta-section">
        <div class="free-banner fade-in">
            <div class="banner-content">
                <h2>Miễn phí hoàn toàn. Bắt đầu ngay hôm nay.</h2>
                <p>
                    <span>✓ Không giới hạn tính năng</span>
                    <span>✓ Không cần thẻ tín dụng</span>
                    <span>✓ Hỗ trợ tiếng Việt đầy đủ</span>
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
                    <p class="footer-desc">Phần mềm quản lý trung tâm đào tạo tích hợp AI, miễn phí 100% dành cho Việt
                        Nam. Đơn giản, thực dụng và đầy đủ.</p>
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
                <span>© 2026 SmartLMS. Được xây dựng với ❤️ tại Việt Nam.</span>
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
