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
    <meta property="og:url" content="{{ route('home') }}">
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
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon-96.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon-96.png') }}">

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

    @vite('resources/css/pages/landing.css')

    </head>

    <body>

        <!-- NAV -->
        <nav>
            <a class="nav-logo" href="{{ route('home') }}" aria-label="SmartLMS - Trang chủ">
                <img src="{{ asset('smartlms-logo-nobg.png') }}" alt="SmartLMS Logo">
            </a>
            <ul class="nav-links">
                <li><a href="#features">Năng lực</a></li>
                <li><a href="#ai-layer">AI</a></li>
                <li><a href="#how">Triển khai</a></li>
            </ul>
            <div class="nav-right">
                <a class="nav-cta" href="{{ route('login') }}">Đăng nhập →</a>
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
                        <a class="btn-primary" href="{{ route('login') }}">
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
                <a class="btn-white" href="{{ route('login') }}">Truy cập SmartLMS →</a>
            </div>
        </div>

        <!-- FOOTER -->
        <footer>
            <div class="footer-container">
                <div class="footer-grid">
                    <div class="footer-brand">
                        <a href="{{ route('home') }}" class="footer-logo">SmartLMS</a>
                        <p class="footer-desc">Nền tảng quản lý đào tạo tích hợp AI dành cho trung tâm, giáo viên và học
                            sinh
                            Việt Nam. Rõ luồng, nhẹ thao tác và sẵn sàng mở rộng.</p>
                        <div class="footer-socials">
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
                            <li><a href="#ai-layer">AI trong học tập</a></li>
                            <li><a href="{{ route('login') }}">Đăng nhập</a></li>
                        </ul>
                    </div>

                    <div class="footer-col">
                        <h4>Tài nguyên</h4>
                        <ul>
                            <li><a href="https://github.com/NgoTanLoi01/LMS_System#readme" target="_blank" rel="noopener noreferrer">Hướng dẫn sử dụng</a></li>
                            <li><a href="#features">Tổng quan tính năng</a></li>
                            <li><a href="#ai-layer">Khả năng AI</a></li>
                            <li><a href="mailto:ngotanloi2424@gmail.com" aria-label="Email">Liên hệ hỗ trợ</a></li>
                        </ul>
                    </div>

                    <div class="footer-col">
                        <h4>Dự án</h4>
                        <ul>
                            <li><a href="https://github.com/NgoTanLoi01/LMS_System" target="_blank" rel="noopener noreferrer">Mã nguồn GitHub</a></li>
                            <li><a href="https://github.com/NgoTanLoi01/LMS_System/issues" target="_blank" rel="noopener noreferrer">Báo lỗi / góp ý</a></li>
                            <li><a href="mailto:ngotanloi2424@gmail.com">Liên hệ quản trị</a></li>
                        </ul>
                    </div>
                </div>

                <div class="footer-bottom">
                    <span>© 2026 SmartLMS. Được xây dựng tại Việt Nam.</span>
                    <span><a href="{{ route('home') }}">smartlms.io.vn</a> · Phần mềm quản lý đào tạo AI miễn phí</span>
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
</body>

</html>
