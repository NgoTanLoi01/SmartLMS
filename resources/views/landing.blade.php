<!DOCTYPE html>
<html lang="vi" prefix="og: https://ogp.me/ns#">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>SmartLMS – Quản lý toàn bộ vòng đời đào tạo trên một hệ thống</title>
    <meta name="description"
        content="SmartLMS kết nối tài khoản, chương trình, khóa học, lớp học, lịch, điểm danh, bài tập, quiz, thanh toán, báo cáo và trợ lý AI có trích dẫn nguồn.">
    <meta name="keywords"
        content="SmartLMS, phần mềm quản lý đào tạo, LMS Việt Nam, quản lý lớp học, điểm danh, ngân hàng câu hỏi, chatbot RAG giáo dục">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="author" content="SmartLMS">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:url" content="{{ route('home') }}">
    <meta property="og:site_name" content="SmartLMS">
    <meta property="og:title" content="SmartLMS – Quản lý toàn bộ vòng đời đào tạo">
    <meta property="og:description"
        content="Từ quản trị tài khoản đến tổ chức đào tạo, đánh giá, vận hành và trợ lý AI có trích dẫn nguồn.">
    <meta property="og:image" content="{{ asset('favicon-v2.png') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SmartLMS – Hệ thống quản lý đào tạo tích hợp AI">
    <meta name="twitter:description" content="Một luồng thống nhất cho quản trị viên, giáo viên và học viên.">
    <meta name="twitter:image" content="{{ asset('favicon-v2.png') }}">

    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon-96.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon-96.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @verbatim
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "SoftwareApplication",
          "name": "SmartLMS",
          "url": "https://smartlms.io.vn",
          "description": "Hệ thống quản lý toàn bộ vòng đời đào tạo tích hợp AI có trích dẫn nguồn",
          "applicationCategory": "EducationalApplication",
          "operatingSystem": "Web",
          "inLanguage": "vi",
          "featureList": [
            "Quản lý vòng đời tài khoản và phân quyền",
            "Quản lý chương trình, khóa học, lớp học và lịch học",
            "Điểm danh, bài tập, quiz và ngân hàng câu hỏi",
            "Theo dõi giảng dạy, thanh toán và báo cáo vận hành",
            "Chatbot RAG dùng pgvector, OCR và trích dẫn nguồn"
          ]
        }
        </script>
    @endverbatim

    @vite('resources/css/pages/landing.css')
</head>

<body>
    <header class="site-header" id="siteHeader">
        <nav class="landing-nav" aria-label="Điều hướng chính">
            <a class="nav-logo" href="{{ route('home') }}" aria-label="SmartLMS - Trang chủ">
                <img src="{{ asset('smartlms-logo-nobg.webp') }}" alt="SmartLMS" width="800" height="200">
            </a>

            <button class="nav-toggle" id="navToggle" type="button" aria-controls="navMenu" aria-expanded="false"
                aria-label="Mở menu">
                <span></span><span></span><span></span>
            </button>

            <div class="nav-menu" id="navMenu">
                <ul class="nav-links">
                    <li><a href="#capabilities">Chức năng</a></li>
                    <li><a href="#roles">Theo vai trò</a></li>
                    <li><a href="#ai">AI & dữ liệu</a></li>
                    <li><a href="#workflow">Quy trình</a></li>
                    <li><a href="#operations">Vận hành</a></li>
                </ul>
                <a class="nav-login" href="{{ route('login') }}">
                    Đăng nhập <x-ui.icon name="arrow-right" />
                </a>
            </div>
            <span class="scroll-progress" id="scrollProgress" aria-hidden="true"></span>
        </nav>
    </header>

    <main>
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-orb hero-orb--one"></div>
            <div class="hero-orb hero-orb--two"></div>
            <div class="hero-inner">
                <div class="hero-copy reveal">
                    <div class="eyebrow-pill">
                        <span class="eyebrow-dot"></span>
                        LMS vận hành đào tạo · Tích hợp AI theo quyền truy cập
                    </div>
                    <h1 id="hero-title">Từ tài khoản đến báo cáo, <span>mọi nghiệp vụ đào tạo trong một luồng</span>
                    </h1>
                    <p class="hero-lead">SmartLMS kết nối quản trị viên, giáo viên và học viên trên cùng dữ liệu — từ
                        chương trình, khóa học, lớp, lịch, điểm danh đến bài tập, quiz, thanh toán và báo cáo.</p>

                    <div class="hero-actions">
                        <a class="button button--primary" href="{{ route('login') }}">
                            Truy cập hệ thống <x-ui.icon name="arrow-right" />
                        </a>
                        <a class="button button--secondary" href="#capabilities">
                            Xem bản đồ chức năng <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                        </a>
                    </div>

                    <div class="hero-trust" aria-label="Đặc điểm hệ thống">
                        <span><x-ui.icon name="success" /> Phân quyền 3 vai trò</span>
                        <span><x-ui.icon name="success" /> AI kèm nguồn tham khảo</span>
                        <span><x-ui.icon name="success" /> Dữ liệu theo lớp và khóa học</span>
                    </div>
                </div>

                <div class="hero-product reveal" aria-label="Minh họa trung tâm điều hành SmartLMS">
                    <div class="product-window">
                        <div class="product-topbar">
                            <div class="window-dots" aria-hidden="true"><span></span><span></span><span></span></div>
                            <div class="product-title"><i class="fa-solid fa-table-cells-large"
                                    aria-hidden="true"></i> Trung tâm
                                điều hành</div>
                            <div class="live-state"><span></span> Đồng bộ</div>
                        </div>
                        <div class="product-layout">
                            <aside class="product-sidebar" aria-hidden="true">
                                <div class="side-mark"><i class="fa-solid fa-graduation-cap"></i></div>
                                <span class="active"><i class="fa-solid fa-house"></i></span>
                                <span><i class="fa-solid fa-book-open"></i></span>
                                <span><i class="fa-solid fa-school"></i></span>
                                <span><i class="fa-solid fa-calendar-days"></i></span>
                                <span><i class="fa-solid fa-chart-line"></i></span>
                            </aside>
                            <div class="product-content">
                                <div class="product-heading">
                                    <div>
                                        <small>Không gian giáo viên</small>
                                        <strong>Việc cần xử lý hôm nay</strong>
                                    </div>
                                    <div class="product-avatar">GV</div>
                                </div>

                                <div class="signal-grid">
                                    <div class="signal-card signal-card--blue">
                                        <i class="fa-solid fa-chalkboard-user"></i>
                                        <span>Lớp sắp dạy</span>
                                        <strong>Đã có lịch</strong>
                                    </div>
                                    <div class="signal-card signal-card--amber">
                                        <i class="fa-solid fa-file-pen"></i>
                                        <span>Bài chờ chấm</span>
                                        <strong>Cần ưu tiên</strong>
                                    </div>
                                    <div class="signal-card signal-card--green">
                                        <i class="fa-solid fa-user-check"></i>
                                        <span>Điểm danh</span>
                                        <strong>Sẵn sàng</strong>
                                    </div>
                                </div>

                                <div class="work-card">
                                    <div class="work-card__head">
                                        <strong>Luồng công việc</strong>
                                        <span>Hôm nay</span>
                                    </div>
                                    <div class="work-row">
                                        <div class="work-icon work-icon--blue"><i class="fa-solid fa-clock"></i></div>
                                        <div><strong>Chuẩn bị lớp học</strong><small>Lịch học · nội dung · học
                                                viên</small>
                                        </div>
                                        <span class="work-status">Sắp tới</span>
                                    </div>
                                    <div class="work-row">
                                        <div class="work-icon work-icon--violet"><i class="fa-solid fa-robot"></i>
                                        </div>
                                        <div><strong>Phản hồi bài nộp</strong><small>AI gợi ý · giáo viên duyệt</small>
                                        </div>
                                        <span class="work-status work-status--ai">AI</span>
                                    </div>
                                    <div class="work-row">
                                        <div class="work-icon work-icon--green"><i
                                                class="fa-solid fa-chart-simple"></i>
                                        </div>
                                        <div><strong>Theo dõi tiến độ</strong><small>Bài học · quiz · điểm số</small>
                                        </div>
                                        <span class="work-status work-status--done">Đang chạy</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="product-float product-float--citation">
                        <i class="fa-solid fa-quote-left"></i>
                        <div><strong>Trả lời có căn cứ</strong><span>Tài liệu khóa học · trang 3</span></div>
                    </div>
                    <div class="product-float product-float--notice">
                        <i class="fa-solid fa-bell"></i><span>Thông báo theo tài khoản</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="module-strip" aria-label="Các phân hệ chính">
            <div class="module-strip__inner">
                <span><i class="fa-solid fa-shield-halved"></i> Tài khoản & phân quyền</span>
                <span><i class="fa-solid fa-layer-group"></i> Chương trình & khóa học</span>
                <span><i class="fa-solid fa-school"></i> Lớp & lịch học</span>
                <span><i class="fa-solid fa-clipboard-check"></i> Điểm danh & điểm số</span>
                <span><i class="fa-solid fa-file-pen"></i> Bài tập & quiz</span>
                <span><i class="fa-solid fa-chart-column"></i> Vận hành & báo cáo</span>
            </div>
        </section>

        <section class="section capabilities" id="capabilities" aria-labelledby="capabilities-title">
            <div class="section-heading reveal">
                <div class="section-kicker">Bản đồ chức năng thật</div>
                <h2 id="capabilities-title">Không chỉ là nơi đăng bài học</h2>
                <p>SmartLMS bao phủ bốn lớp công việc đang có trong hệ thống, giúp dữ liệu đi liền từ quản trị đến đào
                    tạo và vận hành.</p>
            </div>

            <div class="capability-grid">
                <article class="capability-card capability-card--primary reveal">
                    <div class="capability-number">01</div>
                    <div class="capability-icon"><i class="fa-solid fa-user-shield"></i></div>
                    <h3>Quản trị hệ thống</h3>
                    <p>Kiểm soát người dùng, quyền truy cập và trạng thái hệ thống từ một nơi.</p>
                    <ul>
                        <li><x-ui.icon name="check" /> Tài khoản Admin, Giáo viên, Học viên</li>
                        <li><x-ui.icon name="check" /> Kích hoạt, vô hiệu hóa và ngày hết hạn</li>
                        <li><x-ui.icon name="check" /> Thông báo cá nhân và nhật ký thao tác</li>
                        <li><x-ui.icon name="check" /> Backup, R2 và kiểm tra storage</li>
                    </ul>
                </article>

                <article class="capability-card capability-card--success reveal">
                    <div class="capability-number">02</div>
                    <div class="capability-icon"><i class="fa-solid fa-route"></i></div>
                    <h3>Tổ chức đào tạo</h3>
                    <p>Xây chương trình và đưa nội dung đến đúng lớp, đúng lịch, đúng người học.</p>
                    <ul>
                        <li><x-ui.icon name="check" /> Chương trình → Khóa học → Chương → Bài</li>
                        <li><x-ui.icon name="check" /> Lớp học, phân công giáo viên, nhập học viên</li>
                        <li><x-ui.icon name="check" /> Lịch học, nhân bản/import và lịch cá nhân</li>
                        <li><x-ui.icon name="check" /> Điểm danh, điểm số và tiến độ học tập</li>
                    </ul>
                </article>

                <article class="capability-card capability-card--warning reveal">
                    <div class="capability-number">03</div>
                    <div class="capability-icon"><i class="fa-solid fa-clipboard-question"></i></div>
                    <h3>Học tập & đánh giá</h3>
                    <p>Một luồng rõ ràng từ giao nhiệm vụ đến nộp bài, chấm điểm và xem kết quả.</p>
                    <ul>
                        <li><x-ui.icon name="check" /> Bài tập file hoặc tự luận, preview và phản hồi</li>
                        <li><x-ui.icon name="check" /> Ngân hàng câu hỏi, import và gắn khóa học</li>
                        <li><x-ui.icon name="check" /> Tạo quiz, làm bài, xem lại và thống kê</li>
                        <li><x-ui.icon name="check" /> Kho học liệu và tài liệu dùng chung</li>
                    </ul>
                </article>

                <article class="capability-card capability-card--ai reveal">
                    <div class="capability-number">04</div>
                    <div class="capability-icon"><i class="fa-solid fa-chart-pie"></i></div>
                    <h3>Nghiệp vụ trung tâm</h3>
                    <p>Kết nối hoạt động giảng dạy với hợp đồng, thanh toán và báo cáo quản trị.</p>
                    <ul>
                        <li><x-ui.icon name="check" /> Theo dõi dòng giảng dạy theo giáo viên</li>
                        <li><x-ui.icon name="check" /> Hợp đồng và trạng thái thanh toán</li>
                        <li><x-ui.icon name="check" /> Dashboard vận hành theo thời gian</li>
                        <li><x-ui.icon name="check" /> Xuất Excel và bản in báo cáo</li>
                    </ul>
                </article>
            </div>
        </section>

        <section class="section roles" id="roles" aria-labelledby="roles-title">
            <div class="roles-shell">
                <div class="section-heading section-heading--left reveal">
                    <div class="section-kicker">Đúng việc cho đúng vai trò</div>
                    <h2 id="roles-title">Mỗi người nhìn thấy một không gian phù hợp</h2>
                    <p>Quyền truy cập được giới hạn theo vai trò, khóa học và lớp học; không dùng một dashboard chung
                        cho
                        mọi người.</p>
                </div>

                <div class="roles-body">
                    <div class="role-tabs reveal" role="tablist" aria-label="Vai trò SmartLMS">
                        <button class="role-tab is-active" id="role-admin-tab" type="button" role="tab"
                            aria-selected="true" aria-controls="role-admin" data-role-target="role-admin"
                            tabindex="0">
                            <span class="role-tab__icon"><i class="fa-solid fa-user-shield"></i></span>
                            <span class="role-tab__copy"><strong>Quản trị viên</strong><small>Hệ thống & dữ
                                    liệu</small></span>
                            <i class="fa-solid fa-chevron-right role-tab__arrow"></i>
                        </button>
                        <button class="role-tab" id="role-teacher-tab" type="button" role="tab"
                            aria-selected="false" aria-controls="role-teacher" data-role-target="role-teacher"
                            tabindex="-1">
                            <span class="role-tab__icon"><i class="fa-solid fa-chalkboard-user"></i></span>
                            <span class="role-tab__copy"><strong>Giáo viên</strong><small>Lớp học & đánh
                                    giá</small></span>
                            <i class="fa-solid fa-chevron-right role-tab__arrow"></i>
                        </button>
                        <button class="role-tab" id="role-student-tab" type="button" role="tab"
                            aria-selected="false" aria-controls="role-student" data-role-target="role-student"
                            tabindex="-1">
                            <span class="role-tab__icon"><i class="fa-solid fa-user-graduate"></i></span>
                            <span class="role-tab__copy"><strong>Học viên</strong><small>Lịch học & kết
                                    quả</small></span>
                            <i class="fa-solid fa-chevron-right role-tab__arrow"></i>
                        </button>
                    </div>

                    <div class="role-panels reveal">
                        <article class="role-panel role-panel--admin is-active" id="role-admin" role="tabpanel"
                            aria-labelledby="role-admin-tab">
                            <div class="role-copy">
                                <span class="role-label">Không gian quản trị</span>
                                <h3>Nắm tình trạng hệ thống mà không phải ghép nhiều bảng dữ liệu</h3>
                                <p>Tài khoản, lớp học, nhật ký thao tác, tác vụ AI và sao lưu được đặt trong cùng một
                                    luồng quản trị.</p>
                                <div class="role-points">
                                    <span><i class="fa-solid fa-check"></i> Quản lý vòng đời tài khoản</span>
                                    <span><i class="fa-solid fa-check"></i> Phân quyền và cô lập dữ liệu</span>
                                    <span><i class="fa-solid fa-check"></i> Theo dõi backup và storage</span>
                                </div>
                            </div>
                            <div class="role-preview">
                                <div class="preview-head">
                                    <div><small>TRUNG TÂM VẬN HÀNH</small><strong>Cần theo dõi hôm nay</strong></div>
                                    <span><i></i> Ổn định</span>
                                </div>
                                <div class="preview-stat"><i class="fa-solid fa-user-clock"></i>
                                    <div><small>Tài khoản</small><b>2 tài khoản sắp hết hạn</b></div><em>Cần xem</em>
                                </div>
                                <div class="preview-stat"><i class="fa-solid fa-list-check"></i>
                                    <div><small>Nhật ký hệ thống</small><b>Hoạt động mới đã được ghi nhận</b></div>
                                    <em>Đã lưu</em>
                                </div>
                                <div class="preview-stat"><i class="fa-solid fa-cloud-arrow-up"></i>
                                    <div><small>Sao lưu gần nhất</small><b>Database và tệp trên R2</b></div><em>Thành
                                        công</em>
                                </div>
                                <div class="preview-foot"><span><i class="fa-solid fa-shield-halved"></i> Dữ liệu theo
                                        quyền truy cập</span><span>Cập nhật vừa xong</span></div>
                            </div>
                        </article>

                        <article class="role-panel role-panel--teacher" id="role-teacher" role="tabpanel"
                            aria-labelledby="role-teacher-tab" hidden>
                            <div class="role-copy">
                                <span class="role-label">Không gian giảng dạy</span>
                                <h3>Ưu tiên đúng buổi dạy và bài nộp đang chờ xử lý</h3>
                                <p>Giáo viên chuẩn bị nội dung, điểm danh, giao bài, tạo quiz và phản hồi học viên trong
                                    một luồng liền mạch.</p>
                                <div class="role-points">
                                    <span><i class="fa-solid fa-check"></i> Lịch dạy và điểm danh</span>
                                    <span><i class="fa-solid fa-check"></i> Bài tập và ngân hàng câu hỏi</span>
                                    <span><i class="fa-solid fa-check"></i> AI gợi ý, giáo viên quyết định</span>
                                </div>
                            </div>
                            <div class="role-preview">
                                <div class="preview-head">
                                    <div><small>KHÔNG GIAN GIÁO VIÊN</small><strong>Việc cần xử lý</strong></div>
                                    <span><i></i> 3 việc</span>
                                </div>
                                <div class="preview-stat"><i class="fa-solid fa-calendar-check"></i>
                                    <div><small>08:00 · WordPress 02</small><b>Chuẩn bị buổi học và điểm danh</b></div>
                                    <em>Sắp tới</em>
                                </div>
                                <div class="preview-stat"><i class="fa-solid fa-inbox"></i>
                                    <div><small>Bài nộp mới</small><b>5 bài đang chờ phản hồi</b></div><em>Ưu tiên</em>
                                </div>
                                <div class="preview-stat"><i class="fa-solid fa-chart-line"></i>
                                    <div><small>Tiến độ lớp</small><b>18/21 học viên đúng lộ trình</b></div><em>86%</em>
                                </div>
                                <div class="preview-foot"><span><i class="fa-solid fa-wand-magic-sparkles"></i> AI chỉ
                                        hỗ trợ gợi ý</span><span>Giáo viên duyệt kết quả</span></div>
                            </div>
                        </article>

                        <article class="role-panel role-panel--student" id="role-student" role="tabpanel"
                            aria-labelledby="role-student-tab" hidden>
                            <div class="role-copy">
                                <span class="role-label">Không gian học tập</span>
                                <h3>Biết hôm nay học gì, còn việc gì và kết quả ra sao</h3>
                                <p>Học viên thấy đúng khóa học được phân công, lịch cá nhân, hạn bài, phản hồi và kết
                                    quả của chính mình.</p>
                                <div class="role-points">
                                    <span><i class="fa-solid fa-check"></i> Lịch học cá nhân rõ ràng</span>
                                    <span><i class="fa-solid fa-check"></i> Bài học, bài tập và quiz</span>
                                    <span><i class="fa-solid fa-check"></i> Phản hồi và trợ lý học tập</span>
                                </div>
                            </div>
                            <div class="role-preview">
                                <div class="preview-head">
                                    <div><small>HÀNH TRÌNH HỌC TẬP</small><strong>Kế hoạch hôm nay</strong></div>
                                    <span><i></i> Đang học</span>
                                </div>
                                <div class="preview-stat"><i class="fa-solid fa-play"></i>
                                    <div><small>Tiếp tục học</small><b>Thiết kế giao diện với Flatsome</b></div>
                                    <em>45%</em>
                                </div>
                                <div class="preview-stat"><i class="fa-solid fa-paper-plane"></i>
                                    <div><small>Hạn nộp · 20:00</small><b>Bài thực hành số 4</b></div><em>Hôm nay</em>
                                </div>
                                <div class="preview-stat"><i class="fa-solid fa-message"></i>
                                    <div><small>Phản hồi mới</small><b>Giáo viên đã nhận xét bài làm</b></div><em>Đã
                                        xem</em>
                                </div>
                                <div class="preview-foot"><span><i class="fa-solid fa-lock"></i> Chỉ hiển thị dữ liệu
                                        của bạn</span><span>Đồng bộ theo lớp</span></div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="section ai-section" id="ai" aria-labelledby="ai-title">
            <div class="ai-backdrop"></div>
            <div class="ai-layout">
                <div class="ai-copy reveal">
                    <div class="section-kicker section-kicker--dark">AI có căn cứ, không trả lời mơ hồ</div>
                    <h2 id="ai-title">Trợ lý AI bám theo tài liệu và quyền của người dùng</h2>
                    <p>SmartLMS dùng RAG để truy xuất nội dung liên quan trước khi trả lời. Người dùng thấy tên tài
                        liệu,
                        phạm vi khóa học và số trang nguồn ngay trong câu trả lời.</p>

                    <div class="ai-feature-grid">
                        <div class="ai-feature"><i class="fa-solid fa-vector-square"></i>
                            <div><strong>pgvector + HNSW</strong><span>Tìm kiếm ngữ nghĩa trên tài liệu đang hoạt
                                    động</span></div>
                        </div>
                        <div class="ai-feature"><i class="fa-solid fa-file-waveform"></i>
                            <div><strong>OCR PDF scan</strong><span>Nhận dạng tài liệu tiếng Việt và tiếng Anh</span>
                            </div>
                        </div>
                        <div class="ai-feature"><i class="fa-solid fa-quote-right"></i>
                            <div><strong>Trích dẫn nguồn</strong><span>Tài liệu, khóa học và số trang rõ ràng</span>
                            </div>
                        </div>
                        <div class="ai-feature"><i class="fa-solid fa-shield-heart"></i>
                            <div><strong>Giảm PII</strong><span>Loại thông tin định danh trước khi gọi AI ngoài</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ai-demo reveal">
                    <div class="ai-demo__head">
                        <div class="ai-mascot"><img src="{{ asset('chatbot-mascot-v2.webp') }}"
                                alt="Mascot trợ lý SmartLMS"></div>
                        <div><strong>Trợ lý học tập AI</strong><span><i></i> Đang bám theo khóa học</span></div>
                    </div>
                    <div class="chat-bubble chat-bubble--user">Quy trình xử lý sai lệch điểm danh gồm những bước nào?
                    </div>
                    <div class="chat-bubble chat-bubble--ai">
                        <span class="answer-label">Câu trả lời từ tài liệu</span>
                        Tạm khóa bản điểm danh, xuất nhật ký, đối chiếu dữ liệu, ghi nhận điều chỉnh và mở lại sau khi
                        hoàn tất.
                    </div>
                    <div class="source-card">
                        <div class="source-icon"><i class="fa-solid fa-file-pdf"></i></div>
                        <div><strong>[S1] Sổ tay vận hành chương trình</strong><span>Toàn hệ thống · trang 3</span>
                        </div>
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="ai-guardrails">
                        <span><i class="fa-solid fa-gauge-high"></i> Rate limit riêng</span>
                        <span><i class="fa-solid fa-code"></i> Kiểm tra schema</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="section workflow" id="workflow" aria-labelledby="workflow-title">
            <div class="section-heading reveal">
                <div class="section-kicker">Một dữ liệu xuyên suốt</div>
                <h2 id="workflow-title">Vòng đời đào tạo trên SmartLMS</h2>
                <p>Mỗi bước tạo dữ liệu cho bước tiếp theo, giảm nhập lại và tránh tách rời giữa học vụ, giảng dạy và
                    báo cáo.</p>
            </div>

            <div class="workflow-track">
                <article class="workflow-step reveal">
                    <div class="workflow-index">01</div>
                    <div class="workflow-icon"><i class="fa-solid fa-user-plus"></i></div>
                    <h3>Cấp tài khoản</h3>
                    <p>Admin tạo người dùng, gán vai trò và quản lý trạng thái truy cập.</p>
                </article>
                <article class="workflow-step reveal">
                    <div class="workflow-index">02</div>
                    <div class="workflow-icon"><i class="fa-solid fa-diagram-project"></i></div>
                    <h3>Thiết kế chương trình</h3>
                    <p>Tổ chức khóa học, chương, bài học và học liệu.</p>
                </article>
                <article class="workflow-step reveal">
                    <div class="workflow-index">03</div>
                    <div class="workflow-icon"><i class="fa-solid fa-school-circle-check"></i></div>
                    <h3>Tổ chức lớp</h3>
                    <p>Gán giáo viên, học viên, khóa học và lập lịch học.</p>
                </article>
                <article class="workflow-step reveal">
                    <div class="workflow-index">04</div>
                    <div class="workflow-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                    <h3>Dạy & đánh giá</h3>
                    <p>Điểm danh, giao bài, quiz, chấm điểm và phản hồi.</p>
                </article>
                <article class="workflow-step reveal">
                    <div class="workflow-index">05</div>
                    <div class="workflow-icon"><i class="fa-solid fa-chart-column"></i></div>
                    <h3>Vận hành & báo cáo</h3>
                    <p>Tổng hợp giảng dạy, thanh toán và xuất báo cáo.</p>
                </article>
            </div>
        </section>

        <section class="section operations" id="operations" aria-labelledby="operations-title">
            <div class="operations-shell">
                <div class="operations-copy reveal">
                    <div class="section-kicker">Sẵn sàng cho vận hành thực tế</div>
                    <h2 id="operations-title">Không bỏ quên phần quản trị phía sau lớp học</h2>
                    <p>Từ phiên đăng nhập đến backup dữ liệu, SmartLMS có các lớp kiểm soát cần thiết để quản trị viên
                        theo dõi hệ thống sau khi triển khai.</p>
                    <a class="text-link" href="https://github.com/NgoTanLoi01/LMS_System#readme" target="_blank"
                        rel="noopener noreferrer">Xem tài liệu hệ thống <i
                            class="fa-solid fa-arrow-up-right-from-square"></i></a>
                </div>
                <div class="operations-grid reveal">
                    <article><i class="fa-solid fa-user-clock"></i>
                        <div><strong>Vòng đời tài khoản</strong><span>Ngày hết hạn, lý do vô hiệu hóa, lần đăng nhập
                                cuối và thu hồi phiên.</span></div>
                    </article>
                    <article><i class="fa-solid fa-bell"></i>
                        <div><strong>Thông báo cá nhân</strong><span>Lịch, bài tập, điểm số và cảnh báo đến đúng người
                                dùng.</span></div>
                    </article>
                    <article><i class="fa-solid fa-list-check"></i>
                        <div><strong>Audit & AI operations</strong><span>Truy vết thao tác quản trị và theo dõi tác vụ
                                AI.</span></div>
                    </article>
                    <article><i class="fa-solid fa-cloud"></i>
                        <div><strong>Backup & storage</strong><span>Backup database, lưu local/R2 và kiểm tra trạng thái
                                kho.</span></div>
                    </article>
                </div>
            </div>
        </section>

        <section class="final-cta" aria-labelledby="cta-title">
            <div class="final-cta__inner reveal">
                <div>
                    <span class="final-cta__label">SmartLMS đang được vận hành thực tế</span>
                    <h2 id="cta-title">Đăng nhập để tiếp tục công việc của bạn</h2>
                    <p>Tài khoản SmartLMS được quản trị viên cấp và phân quyền theo vai trò. Hệ thống không mở đăng ký
                        tự
                        do để bảo vệ dữ liệu đào tạo.</p>
                </div>
                <a class="button button--light" href="{{ route('login') }}">Đến trang đăng nhập <x-ui.icon
                        name="arrow-right" /></a>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <a href="{{ route('home') }}" class="footer-logo">
                    <img src="{{ asset('smartlms-logo-nobg.webp') }}" alt="SmartLMS" width="800" height="200">
                </a>
                <p>Hệ thống quản lý vòng đời đào tạo tích hợp AI dành cho trung tâm, giáo viên và học viên Việt Nam.</p>
            </div>
            <div class="footer-links">
                <div><strong>Sản phẩm</strong><a href="#capabilities">Chức năng</a><a href="#roles">Theo vai
                        trò</a><a href="#ai">AI & dữ liệu</a></div>
                <div><strong>Tài nguyên</strong><a href="https://github.com/NgoTanLoi01/LMS_System#readme"
                        target="_blank" rel="noopener noreferrer">Hướng dẫn</a><a
                        href="https://github.com/NgoTanLoi01/LMS_System" target="_blank" rel="noopener noreferrer">Mã
                        nguồn</a><a href="mailto:ngotanloi2424@gmail.com">Hỗ trợ</a></div>
                <div><strong>Truy cập</strong><a href="{{ route('login') }}">Đăng nhập</a><a
                        href="{{ route('home') }}">Trang chủ</a><a
                        href="https://github.com/NgoTanLoi01/LMS_System/issues" target="_blank"
                        rel="noopener noreferrer">Báo lỗi</a></div>
            </div>
        </div>
        <div class="footer-bottom"><span>© 2026 SmartLMS. Xây dựng tại Việt Nam.</span><span>Laravel · pgvector · AI có
                trích dẫn</span></div>
    </footer>

    <script>
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const revealItems = document.querySelectorAll('.reveal');
        const revealGroups = document.querySelectorAll('.capability-grid, .workflow-track, .operations-grid');

        revealGroups.forEach(group => {
            [...group.children].forEach((item, index) => {
                item.style.setProperty('--reveal-delay', `${Math.min(index * 70, 280)}ms`);
            });
        });

        if (reduceMotion || !('IntersectionObserver' in window)) {
            revealItems.forEach(item => item.classList.add('is-visible'));
        } else {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.12
            });
            revealItems.forEach(item => observer.observe(item));
        }

        const siteHeader = document.getElementById('siteHeader');
        const scrollProgress = document.getElementById('scrollProgress');
        const setHeaderState = () => {
            siteHeader.classList.toggle('is-scrolled', window.scrollY > 20);
            const scrollable = document.documentElement.scrollHeight - window.innerHeight;
            const progress = scrollable > 0 ? Math.min(window.scrollY / scrollable, 1) : 0;
            scrollProgress.style.transform = `scaleX(${progress})`;
        };
        window.addEventListener('scroll', setHeaderState, {
            passive: true
        });
        setHeaderState();

        const navigationLinks = [...document.querySelectorAll('.nav-links a[href^="#"]')];
        const observedSections = navigationLinks
            .map(link => document.querySelector(link.getAttribute('href')))
            .filter(Boolean);

        if ('IntersectionObserver' in window) {
            const sectionObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) return;
                    navigationLinks.forEach(link => {
                        link.classList.toggle('is-active', link.getAttribute('href') ===
                            `#${entry.target.id}`);
                    });
                });
            }, {
                rootMargin: '-30% 0px -58% 0px',
                threshold: 0
            });
            observedSections.forEach(section => sectionObserver.observe(section));
        }

        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        const closeMenu = () => {
            navMenu.classList.remove('is-open');
            navToggle.classList.remove('is-open');
            navToggle.setAttribute('aria-expanded', 'false');
            navToggle.setAttribute('aria-label', 'Mở menu');
        };
        navToggle.addEventListener('click', () => {
            const open = navMenu.classList.toggle('is-open');
            navToggle.classList.toggle('is-open', open);
            navToggle.setAttribute('aria-expanded', String(open));
            navToggle.setAttribute('aria-label', open ? 'Đóng menu' : 'Mở menu');
        });
        navMenu.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));

        const roleTabs = [...document.querySelectorAll('[data-role-target]')];
        const rolePanels = document.querySelectorAll('.role-panel');
        const activateRoleTab = (tab, moveFocus = false) => {
            roleTabs.forEach(item => {
                item.classList.remove('is-active');
                item.setAttribute('aria-selected', 'false');
                item.tabIndex = -1;
            });
            rolePanels.forEach(panel => {
                panel.classList.remove('is-active');
                panel.hidden = true;
            });
            tab.classList.add('is-active');
            tab.setAttribute('aria-selected', 'true');
            tab.tabIndex = 0;
            if (moveFocus) tab.focus();
            if (window.innerWidth <= 700) {
                tab.scrollIntoView({
                    behavior: reduceMotion ? 'auto' : 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            }
            const panel = document.getElementById(tab.dataset.roleTarget);
            panel.hidden = false;
            requestAnimationFrame(() => panel.classList.add('is-active'));
        };

        roleTabs.forEach((tab) => {
            tab.addEventListener('click', () => activateRoleTab(tab));
            tab.addEventListener('keydown', (event) => {
                const currentIndex = roleTabs.indexOf(tab);
                const keyTargets = {
                    ArrowRight: (currentIndex + 1) % roleTabs.length,
                    ArrowLeft: (currentIndex - 1 + roleTabs.length) % roleTabs.length,
                    Home: 0,
                    End: roleTabs.length - 1,
                };

                if (keyTargets[event.key] === undefined) return;
                event.preventDefault();
                activateRoleTab(roleTabs[keyTargets[event.key]], true);
            });
        });
    </script>
</body>

</html>
