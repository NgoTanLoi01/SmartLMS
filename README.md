# 🎓 SmartLMS — Hệ Thống Quản Lý Học Tập Trực Tuyến

[![CI](https://github.com/NgoTanLoi01/LMS_System/actions/workflows/ci.yml/badge.svg)](https://github.com/NgoTanLoi01/LMS_System/actions/workflows/ci.yml)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3--8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-Container-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

> Hệ thống quản lý học tập hiện đại — được xây dựng trên Laravel 13, đóng gói Docker hoàn chỉnh, với giao diện học tập trực quan.

---

## 🏗 Kiến Trúc Hệ Thống (System Architecture)
<img width="7200" height="6342" alt="LMS_AI_Architecture" src="https://github.com/user-attachments/assets/1453ef3c-85e6-4576-865e-b056bb605164" />

---

## ✨ Điểm Nổi Bật

| # | Tính năng | Mô tả |
|---|-----------|-------|
| 🧠 | **Smart Sidebar** | Giao diện học tập lấy cảm hứng từ Udemy — điều hướng mượt mà, xử lý video thông minh |
| 🎨 | **Intuitive UX/UI** | Thiết kế Hover-to-Show, tập trung tối đa vào nội dung bài học — sạch sẽ và hiện đại |
| 🐳 | **Dockerized Workflow** | Đóng gói toàn bộ môi trường (Nginx, PHP, MySQL) — triển khai chỉ với 1 câu lệnh |
| 🏗️ | **Robust Architecture** | Resource Controller, Eloquent Relationships và hệ thống RBAC (Policy/Middleware) bảo mật |

---

## 🚀 Tính Năng Đã Hoàn Thành

### Tài khoản và vận hành hệ thống

- ✅ **RBAC & Policy** — Phân quyền Admin, Giáo viên, Học viên và cô lập dữ liệu theo chủ sở hữu/lớp học.
- ✅ **Vòng đời tài khoản** — Kích hoạt, vô hiệu hóa, đặt ngày hết hạn, lưu lý do, theo dõi lần đăng nhập cuối và thu hồi phiên truy cập.
- ✅ **Thông báo & audit log** — Trung tâm thông báo cá nhân, lịch sử thao tác quản trị và theo dõi tác vụ AI.
- ✅ **Backup & storage health** — Backup database, lưu bản sao local/R2 và kiểm tra trạng thái kho lưu trữ.

### Quản lý đào tạo

- ✅ **Khóa học đa cấp** — Course → Module → Lesson, trạng thái xuất bản, lịch mở nội dung và học liệu dùng chung.
- ✅ **Lớp học & học viên** — Phân công giáo viên, nhập danh sách học sinh, hồ sơ học tập và cảnh báo học sinh cần chú ý.
- ✅ **Lịch học & điểm danh** — Lập/nhân bản/import lịch, điểm danh một chạm và xuất báo cáo Excel.
- ✅ **Chương trình học** — Quản lý chương trình, khóa học, lớp áp dụng và tiến độ hoàn thành.
- ✅ **Nghiệp vụ trung tâm** — Theo dõi giảng dạy, hợp đồng, thanh toán, dashboard và báo cáo vận hành.

### Học tập, đánh giá và AI

- ✅ **Submission System** — Giao bài, nộp file hoặc tự luận, chấm điểm, phản hồi và quản lý trạng thái bài nộp.
- ✅ **Quiz & Question Bank** — Ngân hàng câu hỏi, import, tạo đề, làm bài, xem lại và thống kê kết quả.
- ✅ **AI hỗ trợ giảng dạy** — Lập kế hoạch khóa học, tạo câu hỏi, phân tích lớp và gợi ý chấm bài có giáo viên duyệt.
- ✅ **RAG & trợ lý cá nhân hóa** — Tìm kiếm ngữ nghĩa bằng PostgreSQL/pgvector, giới hạn tài liệu theo quyền khóa học và trả lời kèm tên tài liệu/trang trích dẫn.
- ✅ **Kho tài liệu chung** — Chia sẻ tài liệu giữa giáo viên trên Cloudflare R2 với quyền sở hữu và phạm vi truy cập.

---

## 🛠 Công Nghệ Sử Dụng

```
Backend   : Laravel 13 (PHP 8.3–8.4)
Database  : MySQL 8.0 · PostgreSQL/pgvector
Frontend  : Bootstrap 5 · Tailwind CSS 4 · Vite 8 · JavaScript ES6
DevOps    : Docker Compose · Nginx · PHP-FPM · Queue Worker · Reverb
```

---

## 📦 Triển Khai Nhanh

> Hiện tại SmartLMS chỉ cung cấp giao diện web dùng session/CSRF. Dự án không public API `/api/*`; khi cần tích hợp mobile hoặc bên thứ ba, API phải được thiết kế riêng với JSON response, Sanctum token scope và test phân quyền.

### Yêu cầu hệ thống

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) >= 24.x
- PHP 8.3–8.4 và Composer 2
- Node.js 24 và npm
- Git

### Các bước cài đặt

**1. Clone & cấu hình môi trường**

```bash
git clone https://github.com/ngotanloi/lms-system.git
cd lms-system
cp .env.example .env
php scripts/rotate-secrets.php
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

**2. Khởi chạy toàn bộ hệ thống**

```bash
docker compose up -d --build
```

**3. Khởi tạo database và cache Laravel**

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
```

`db:seed` chỉ dành cho môi trường phát triển/dữ liệu mẫu. Không chạy seeder mặc định trên production vì có thể tạo tài khoản mẫu.

### Cấu hình production an toàn

- Không commit `.env`; Docker build cũng loại `.env` khỏi image qua `.dockerignore`.
- `docker-compose.yml` yêu cầu `APP_KEY`, mật khẩu database và credential Reverb lấy từ `.env`.
- MySQL, PostgreSQL và Reverb chỉ mở trong Docker network; lưu lượng ứng dụng/WebSocket đi qua Nginx.
- Khi rotate trên hệ thống đã có volume đang chạy, dùng `php scripts/rotate-secrets.php --sync-running-databases`, sau đó recreate container bằng `docker compose up -d --force-recreate`.
- Rotation `APP_KEY` làm mất hiệu lực session/cookie cũ. Luôn backup database trước khi rotation production.

### AI, pgvector và OCR

- Tất cả kết nối DeepSeek/Gemini đều xác minh TLS; không sử dụng `withoutVerifying()`.
- Dữ liệu gửi ra nhà cung cấp AI được loại email/số điện thoại và dùng mã tham chiếu thay cho danh tính học viên ở các luồng phân tích/chấm bài.
- Endpoint chatbot và sinh nội dung có rate limit riêng. Kết quả JSON từ AI được kiểm tra schema trước khi lưu hoặc trả về nghiệp vụ.
- Chatbot chỉ truy xuất chunk đang hoạt động và thuộc khóa học người dùng được phép xem. Câu trả lời hiển thị nguồn theo tài liệu, khóa học và số trang.
- Pipeline PDF tạo toàn bộ embedding ở trạng thái staging, chỉ thay thế phiên bản đang hoạt động trong một transaction sau khi tất cả chunk thành công. PDF scan được OCR bằng Poppler/Tesseract.
- PostgreSQL dùng image cố định `pgvector/pgvector:0.8.5-pg15`; migration nâng extension `vector` và tạo HNSW cosine dạng `halfvec(3072)` cho các chunk đang hoạt động. Cách này giữ embedding Gemini 3.072 chiều nhưng vẫn nằm trong giới hạn index 4.000 chiều của pgvector. Image ứng dụng đã bao gồm `pdftoppm` và Tesseract ngôn ngữ Việt/Anh, vì vậy phải pull/recreate PostgreSQL và rebuild image ứng dụng khi nâng cấp từ bản cũ.

Các biến cấu hình chính trong `.env`:

```dotenv
GEMINI_EMBEDDING_MODEL=gemini-embedding-001
AI_CHAT_RATE_LIMIT=20
AI_GENERATION_RATE_LIMIT=8
AI_RAG_RESULT_LIMIT=5
AI_RAG_CONTEXT_LIMIT=9000
AI_RAG_MAX_DISTANCE=0.65
AI_RAG_DISTANCE_MARGIN=0.18
AI_EMBEDDING_DIMENSIONS=3072
AI_EMBEDDING_CHUNK_SIZE=1200
AI_EMBEDDING_CHUNK_OVERLAP=200
AI_OCR_ENABLED=true
AI_OCR_LANGUAGES=vie+eng
AI_OCR_MAX_PAGES=50
```

`AI_EMBEDDING_DIMENSIONS` phải trùng với kiểu `vector(3072)` của bảng `document_chunks`. Sau khi deploy, chạy migration, rebuild container và làm mới cache cấu hình/route trước khi nạp lại tài liệu.

Hướng dẫn đầy đủ về kiểm tra trước deploy, backup, rollout, health check và rollback nằm tại [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## ✅ Continuous Integration

Workflow `.github/workflows/ci.yml` chạy khi push lên `main`, khi mở/cập nhật pull request và khi chạy thủ công:

- PHPUnit trên PHP 8.4.
- Laravel Pint trên toàn bộ PHP source.
- `npm ci` và Vite production build trên Node.js 24.

Trước khi push, nên chạy:

```bash
php artisan test
vendor/bin/pint --test
npm ci
npm run build
```

---

## 📁 Cấu Trúc Dự Án

```
lms-system/
├── app/
│   ├── Http/Controllers/     # Resource Controllers
│   ├── Models/               # Eloquent Models & Relationships
│   └── Policies/             # RBAC Authorization
├── resources/
│   ├── views/                # Blade Templates
│   └── js/                   # JavaScript ES6 (Sidebar, Modal)
├── database/
│   ├── migrations/           # Cấu trúc Database
│   └── seeders/              # Dữ liệu mẫu (Users, Courses, Lessons)
├── docker/                   # Cấu hình Nginx, PHP-FPM
└── docker-compose.yml        # Orchestration toàn bộ hệ thống
```

---

## 🔮 Lộ Trình Phát Triển

- [ ] Tự động cấp chứng chỉ (PDF Certificate) khi hoàn thành khóa học
- [ ] Email/push notification cho lịch học, hạn bài và kết quả
- [ ] Multi-tenant cho nhiều trung tâm độc lập
- [ ] Load Testing đảm bảo hệ thống chịu tải 10000+ người dùng đồng thời

---

## 🤝 Đóng Góp

Mọi đóng góp đều được chào đón! Vui lòng mở **Issue** hoặc tạo **Pull Request**.

---

## 📄 License

Dự án được phát hành theo giấy phép [MIT](LICENSE).

---

<div align="center">
  Maintained with ❤️ by <strong>NgoTanLoi</strong>
</div>
