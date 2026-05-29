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
    <link rel="icon" type="image/png" href="{{ asset('favicon-v2.png') }}">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        :root {
            --blue: #2563eb;
            --blue-dark: #1d4ed8;
            --blue-light: #eff6ff;
            --blue-mid: #dbeafe;
            --navy: #0f172a;
            --slate: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
            --white: #ffffff;
            --bg: #f8fafc;
            --radius: 12px;
            --radius-lg: 18px;
            --radius-xl: 24px;
        }

        html {
            scroll-behavior: smooth
        }

        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background: var(--white);
            color: var(--navy);
            overflow-x: hidden
        }

        /* NAV */
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
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        }

        .logo {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 20px;
            color: var(--blue);
            letter-spacing: -.5px;
            text-decoration: none
        }

        .logo span {
            color: var(--navy)
        }

        .nav-cta {
            background: var(--blue);
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Be Vietnam Pro', sans-serif;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s, transform .15s;
            display: inline-block;
        }

        .nav-cta:hover {
            background: var(--blue-dark);
            transform: translateY(-1px)
        }

        /* HERO */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 120px 48px 80px;
            background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 50%, #f8fafc 100%);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -200px;
            right: -200px;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-inner {
            max-width: 1100px;
            margin: 0 auto;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
            align-items: center
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--blue-light);
            color: var(--blue);
            border: 1px solid var(--blue-mid);
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--blue);
            animation: pulse 2s infinite
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1)
            }

            50% {
                opacity: .5;
                transform: scale(1.3)
            }
        }

        h1 {
            font-size: clamp(36px, 4vw, 54px);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -1.5px;
            margin-bottom: 20px;
        }

        h1 .accent {
            color: var(--blue)
        }

        .hero-desc {
            font-size: 17px;
            color: var(--muted);
            line-height: 1.7;
            margin-bottom: 36px;
            font-weight: 400
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center
        }

        .btn-primary {
            background: var(--blue);
            color: #fff;
            border: none;
            padding: 14px 28px;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Be Vietnam Pro', sans-serif;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all .2s;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover {
            background: var(--blue-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.35)
        }

        .btn-ghost {
            color: var(--navy);
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: gap .2s;
        }

        .btn-ghost:hover {
            gap: 10px
        }

        .hero-stats {
            display: flex;
            gap: 28px;
            margin-top: 40px;
            padding-top: 32px;
            border-top: 1px solid var(--border)
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 2px
        }

        .stat-num {
            font-size: 22px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -.5px
        }

        .stat-label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 500
        }

        /* HERO VISUAL */
        .hero-visual {
            position: relative
        }

        .dashboard-card {
            background: #fff;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            padding: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px
        }

        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--navy)
        }

        .card-badge {
            background: var(--blue-light);
            color: var(--blue);
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px
        }

        .class-list {
            display: flex;
            flex-direction: column;
            gap: 10px
        }

        .class-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: var(--radius);
            background: var(--bg);
            border: 1px solid var(--border);
            transition: border-color .2s;
        }

        .class-item:hover {
            border-color: var(--blue-mid)
        }

        .class-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .class-info {
            flex: 1
        }

        .class-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 2px
        }

        .class-meta {
            font-size: 11px;
            color: var(--muted)
        }

        .class-progress {
            width: 60px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }

        .progress-bar {
            width: 60px;
            height: 4px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden
        }

        .progress-fill {
            height: 100%;
            background: var(--blue);
            border-radius: 4px;
            transition: width .5s ease
        }

        .progress-text {
            font-size: 10px;
            font-weight: 600;
            color: var(--blue)
        }

        .floating-chip {
            position: absolute;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 10px 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 600;
            color: var(--navy);
        }

        .chip-1 {
            top: -20px;
            right: 20px;
            animation: float1 3s ease-in-out infinite
        }

        .chip-2 {
            bottom: -16px;
            left: 10px;
            animation: float2 3.5s ease-in-out infinite
        }

        @keyframes float1 {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(-8px)
            }
        }

        @keyframes float2 {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(6px)
            }
        }

        .chip-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px
        }

        /* FEATURES */
        .features {
            padding: 96px 48px;
            background: var(--bg)
        }

        .section-label {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            color: var(--blue);
            letter-spacing: .12em;
            text-transform: uppercase;
            margin-bottom: 12px
        }

        .section-title {
            text-align: center;
            font-size: clamp(28px, 3vw, 40px);
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 12px
        }

        .section-desc {
            text-align: center;
            color: var(--muted);
            font-size: 16px;
            max-width: 520px;
            margin: 0 auto 56px;
            line-height: 1.7
        }

        .features-grid {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px
        }

        .feature-card {
            background: #fff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 28px;
            transition: all .25s;
            cursor: default;
        }

        .feature-card:hover {
            border-color: var(--blue-mid);
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(37, 99, 235, 0.08)
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 18px;
        }

        .feature-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px
        }

        .feature-desc {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.65
        }

        /* HOW IT WORKS */
        .how {
            padding: 96px 48px;
            background: #fff
        }

        .steps {
            max-width: 900px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            position: relative
        }

        .steps::before {
            content: '';
            position: absolute;
            top: 32px;
            left: calc(16.5% + 16px);
            right: calc(16.5% + 16px);
            height: 1px;
            background: repeating-linear-gradient(90deg, var(--blue-mid) 0, var(--blue-mid) 6px, transparent 6px, transparent 12px);
        }

        .step {
            text-align: center;
            position: relative
        }

        .step-num {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--blue);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
            margin: 0 auto 16px;
            position: relative;
            z-index: 1;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }

        .step-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px
        }

        .step-desc {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.6
        }

        /* FREE BANNER */
        .free-banner {
            margin: 0 48px 96px;
            padding: 56px 48px;
            background: linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%);
            border-radius: var(--radius-xl);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 32px;
            box-shadow: 0 20px 60px rgba(37, 99, 235, 0.25);
        }

        .banner-content h2 {
            font-size: clamp(24px, 3vw, 36px);
            font-weight: 800;
            letter-spacing: -.5px;
            margin-bottom: 10px
        }

        .banner-content p {
            font-size: 16px;
            opacity: .85;
            line-height: 1.6
        }

        .btn-white {
            background: #fff;
            color: var(--blue);
            border: none;
            padding: 14px 28px;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Be Vietnam Pro', sans-serif;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
            transition: all .2s;
            flex-shrink: 0;
        }

        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15)
        }

        /* FOOTER */
        footer {
            background: var(--navy);
            color: rgba(255, 255, 255, 0.6);
            padding: 32px 48px;
            text-align: center;
            font-size: 14px;
        }

        footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none
        }

        footer a:hover {
            color: #fff
        }

        /* ANIMATIONS */
        .fade-in {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .6s ease, transform .6s ease
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0)
        }

        @media(max-width:768px) {
            nav {
                padding: 14px 20px
            }

            .hero {
                padding: 100px 20px 60px
            }

            .hero-inner {
                grid-template-columns: 1fr;
                gap: 40px
            }

            .hero-visual {
                display: none
            }

            .features {
                padding: 60px 20px
            }

            .features-grid {
                grid-template-columns: 1fr
            }

            .how {
                padding: 60px 20px
            }

            .steps {
                grid-template-columns: 1fr;
                gap: 24px
            }

            .steps::before {
                display: none
            }

            .free-banner {
                flex-direction: column;
                margin: 0 20px 60px;
                padding: 40px 28px
            }

            footer {
                padding: 24px 20px
            }
            
        }
    </style>
</head>
<body>

    <!-- NAV -->
    <nav>
        <a class="navbar-brand" href="{{ url('/') }}">
            <img style="height: 50px; width: auto;" src="{{ asset('smartlms-logo-sharpened.png') }}" alt="SmartLMS">
        </a>
        <a class="nav-cta" href="https://smartlms.io.vn/login">Đăng nhập →</a>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-content fade-in">
                <div class="badge">
                    <span class="badge-dot"></span>
                    Miễn phí hoàn toàn
                </div>
                <h1>Quản lý trung tâm đào tạo <span class="accent">thông minh hơn</span> với AI</h1>
                <p class="hero-desc">SmartLMS giúp bạn quản lý lớp học, giao bài tập, tạo ngân hàng câu hỏi và huấn
                    luyện AI từ tài liệu của chính mình — tất cả trong một nền tảng duy nhất.</p>
                <div class="hero-actions">
                    <a class="btn-primary" href="https://smartlms.io.vn/login">Dùng miễn phí ngay</a>
                    <a class="btn-ghost" href="#features">Xem tính năng <span>→</span></a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-num">100%</span>
                        <span class="stat-label">Miễn phí</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-num">AI</span>
                        <span class="stat-label">Tích hợp sẵn</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-num">5 phút</span>
                        <span class="stat-label">Để bắt đầu</span>
                    </div>
                </div>
            </div>

            <div class="hero-visual fade-in" style="transition-delay:.2s">
                <div style="position:relative">
                    <div class="floating-chip chip-1">
                        <div class="chip-icon" style="background:#f0fdf4">🤖</div>
                        AI đã huấn luyện xong
                    </div>
                    <div class="dashboard-card">
                        <div class="card-header">
                            <span class="card-title">Lớp học của tôi</span>
                            <span class="card-badge">3 lớp đang hoạt động</span>
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
    <section class="features" id="features">
        <div class="section-label">Tính năng</div>
        <h2 class="section-title">Mọi thứ bạn cần trong một nền tảng</h2>
        <p class="section-desc">Được xây dựng dành riêng cho các trung tâm đào tạo tư nhân tại Việt Nam</p>

        <div class="features-grid">
            <div class="feature-card fade-in">
                <div class="feature-icon" style="background:#eff6ff">🤖</div>
                <div class="feature-title">Huấn luyện AI từ tài liệu</div>
                <div class="feature-desc">Tải lên giáo trình, slide bài giảng — AI sẽ học và hỗ trợ học viên trả lời
                    câu
                    hỏi theo đúng nội dung của bạn.</div>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.1s">
                <div class="feature-icon" style="background:#f0fdf4">📚</div>
                <div class="feature-title">Ngân hàng câu hỏi thông minh</div>
                <div class="feature-desc">Tạo và quản lý hàng trăm câu hỏi, phân loại theo chủ đề. AI có thể tự động
                    gợi
                    ý câu hỏi từ tài liệu.</div>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.2s">
                <div class="feature-icon" style="background:#fef9ec">🏫</div>
                <div class="feature-title">Quản lý lớp học & lịch học</div>
                <div class="feature-desc">Theo dõi danh sách học viên, sắp xếp lịch học, ghi nhận điểm danh và tiến độ
                    học tập dễ dàng.</div>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.3s">
                <div class="feature-icon" style="background:#fdf2f8">📋</div>
                <div class="feature-title">Giao bài & quản lý khóa học</div>
                <div class="feature-desc">Tạo bài tập, giao cho học viên và theo dõi kết quả nộp bài. Tất cả tập trung
                    tại một nơi.</div>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.4s">
                <div class="feature-icon" style="background:#f0f4ff">🎮</div>
                <div class="feature-title">Công cụ hỗ trợ học tập</div>
                <div class="feature-desc">Tích hợp máy tính điểm, trình soạn thảo code và các công cụ giải trí giúp học
                    viên học hiệu quả hơn.</div>
            </div>
            <div class="feature-card fade-in" style="transition-delay:.5s">
                <div class="feature-icon" style="background:#f0fdfa">👥</div>
                <div class="feature-title">Phân quyền rõ ràng</div>
                <div class="feature-desc">Admin, giáo viên và học viên có giao diện riêng biệt, phù hợp với vai trò và
                    quyền hạn của từng người.</div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="how">
        <div class="section-label">Cách hoạt động</div>
        <h2 class="section-title">Bắt đầu chỉ trong 3 bước</h2>
        <p class="section-desc">Không cần cài đặt, không cần kỹ thuật — chỉ cần truy cập và dùng ngay</p>
        <div class="steps">
            <div class="step fade-in">
                <div class="step-num">1</div>
                <div class="step-title">Đăng nhập tài khoản</div>
                <div class="step-desc">Đăng nhập vào tài khoản của bạn để bắt đầu sử dụng SmartLMS.</div>
            </div>
            <div class="step fade-in" style="transition-delay:.15s">
                <div class="step-num">2</div>
                <div class="step-title">Tạo lớp & tải tài liệu</div>
                <div class="step-desc">Thêm học viên, tạo lịch học và tải lên giáo trình để huấn luyện AI cho lớp học
                    của bạn.</div>
            </div>
            <div class="step fade-in" style="transition-delay:.3s">
                <div class="step-num">3</div>
                <div class="step-title">Dạy học thông minh hơn</div>
                <div class="step-desc">AI hỗ trợ học viên 24/7, bạn chỉ cần tập trung vào việc giảng dạy và theo dõi
                    kết quả.</div>
            </div>
        </div>
    </section>

    <!-- CTA BANNER -->
    <div class="free-banner fade-in">
        <div class="banner-content">
            <h2>Miễn phí hoàn toàn. Bắt đầu ngay hôm nay.</h2>
            <p>Không giới hạn tính năng · Không cần thẻ tín dụng · Hỗ trợ tiếng Việt đầy đủ</p>
        </div>
        <a class="btn-white" href="https://smartlms.io.vn/login">Truy cập SmartLMS →</a>
    </div>

    <!-- FOOTER -->
    <footer>
        <p>© 2026 SmartLMS · <a href="https://smartlms.io.vn">smartlms.io.vn</a> · Hệ thống quản lý học tập AI miễn phí
            dành cho trung tâm đào tạo Việt Nam</p>
    </footer>

    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) e.target.classList.add('visible')
            });
        }, {
            threshold: 0.12
        });
        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
    </script>
</body>

</html>
