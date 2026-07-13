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

### Quản lý lõi (Core Management)

- ✅ **RBAC System** — Phân quyền chặt chẽ: **Admin**, **Teacher**, **Student** thông qua Middleware & Policies
- ✅ **Course Architecture** — Quản lý khóa học đa cấp: **Course → Module → Lesson**
- ✅ **Learning Mode** — Trình phát bài học hỗ trợ đa phương tiện (Video, Documents)
- ✅ **Smart Interaction** — Xử lý logic phía client bằng JavaScript ES6 & Modal tương tác nhanh

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
docker compose exec app php artisan migrate --seed --force
docker compose exec app php artisan optimize
```

### Cấu hình production an toàn

- Không commit `.env`; Docker build cũng loại `.env` khỏi image qua `.dockerignore`.
- `docker-compose.yml` yêu cầu `APP_KEY`, mật khẩu database và credential Reverb lấy từ `.env`.
- MySQL, PostgreSQL và Reverb chỉ mở trong Docker network; lưu lượng ứng dụng/WebSocket đi qua Nginx.
- Khi rotate trên hệ thống đã có volume đang chạy, dùng `php scripts/rotate-secrets.php --sync-running-databases`, sau đó recreate container bằng `docker compose up -d --force-recreate`.
- Rotation `APP_KEY` làm mất hiệu lực session/cookie cũ. Luôn backup database trước khi rotation production.

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

- [ ] Xây dựng module Submission System (nộp bài tập, quản lý file)
- [ ] Tích hợp AI để hỗ trợ chấm bài tự luận tự động
- [ ] Tự động cấp chứng chỉ (PDF Certificate) khi hoàn thành khóa học
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
