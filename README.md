# 🎓 LMS Platform — Hệ Thống Quản Lý Giáo Dục Trực Tuyến

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-Container-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

> Hệ thống quản lý học tập (LMS) tinh gọn, hiệu quả — được phát triển bởi **NgoTanLoi**.  
> Dự án tập trung vào trải nghiệm người dùng (UX) mượt mà và khả năng quản lý nội dung linh hoạt cho giáo viên.

---

## ✨ Điểm Nổi Bật

Dự án không chỉ dừng lại ở CRUD cơ bản mà còn sở hữu những điểm chạm chuyên nghiệp:

| # | Tính năng | Mô tả |
|---|-----------|-------|
| 🧠 | **Smart Sidebar** | Giao diện học tập lấy cảm hứng từ Udemy — điều hướng không tải lại trang, xử lý Video YouTube thông minh |
| 🎨 | **Intuitive UX/UI** | Các công cụ quản lý (Sửa/Xóa) được thiết kế ẩn thông minh (Hover-to-Show), không gian học tập luôn sạch sẽ |
| 🐳 | **Dockerized Workflow** | Toàn bộ môi trường phát triển được đóng gói trong Docker — triển khai chỉ với 1 câu lệnh |
| 🏗️ | **Robust Architecture** | Chuẩn mực Resource Controller, Eloquent Relationships (1-N, N-N) và RBAC bảo mật hệ thống |

---

## 🚀 Tính Năng Hiện Tại

- [x] **Auth System** — Đăng nhập, phân quyền Role-based (Admin, Teacher, Student)
- [x] **Course Management** — Quản lý khóa học, phân cấp theo Module và Lesson
- [x] **Learning Mode** — Trình phát bài học hỗ trợ Video và Nội dung văn bản (Blade & JS)
- [x] **Real-time UX** — Cập nhật nội dung nhanh qua Modal và xử lý Logic phía Client

---

## 🛠 Công Nghệ Cốt Lõi

```
Backend   : Laravel 11 (PHP 8.2+)
Database  : MySQL 8.0 — tối ưu Index
Frontend  : Bootstrap 5 (Customized) · FontAwesome Pro · JavaScript ES6
DevOps    : Docker Compose (Nginx + PHP-FPM + MySQL)
```

---

## 📦 Triển Khai Nhanh (Quick Start)

### Yêu cầu hệ thống

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) >= 24.x
- Git

### Các bước cài đặt

**1. Clone & cấu hình môi trường**

```bash
git clone https://github.com/ngotanloi/lms-system.git
cd lms-system
cp .env.example .env
```

**2. Khởi chạy toàn bộ hệ thống bằng Docker**

```bash
docker compose up -d --build
```

**3. Khởi tạo ứng dụng Laravel**

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

**4. Truy cập ứng dụng**

```
http://localhost:8080
```

| Tài khoản | Email | Mật khẩu |
|-----------|-------|----------|
| Admin | admin@lms.dev | password |
| Teacher | teacher@lms.dev | password |
| Student | student@lms.dev | password |

---

## 📁 Cấu Trúc Dự Án

```
lms-system/
├── app/
│   ├── Http/Controllers/     # Resource Controllers (CRUD chuẩn mực)
│   ├── Models/               # Eloquent Models + Relationships
│   └── Policies/             # RBAC Authorization
├── resources/
│   ├── views/                # Blade Templates
│   └── js/                   # JavaScript ES6 (Modal, Sidebar logic)
├── database/
│   ├── migrations/           # Schema definitions
│   └── seeders/              # Dữ liệu mẫu
├── docker/                   # Nginx, PHP-FPM config
└── docker-compose.yml
```

---

## 🔮 Roadmap — Tầm Nhìn Phát Triển

- [ ] **Quiz System** — Hệ thống trắc nghiệm tính thời gian thực
- [ ] **File Submission** — Nộp bài tập thực hành và chấm điểm trực tuyến
- [ ] **Certificate** — Tự động xuất chứng chỉ PDF khi hoàn thành 100% bài học
- [ ] **AI Assistant** — Tích hợp Gemini API để giải đáp thắc mắc cho học viên trong từng bài học *(2026)*

---

## 🤝 Đóng Góp

Mọi đóng góp đều được chào đón! Vui lòng mở [Issue](https://github.com/ngotanloi/lms-system/issues) hoặc tạo [Pull Request](https://github.com/ngotanloi/lms-system/pulls).

1. Fork dự án
2. Tạo branch: `git checkout -b feature/ten-tinh-nang`
3. Commit: `git commit -m 'feat: mô tả tính năng'`
4. Push: `git push origin feature/ten-tinh-nang`
5. Mở Pull Request

---

## 📄 License

Dự án được phát hành theo giấy phép [MIT](LICENSE).

---

<div align="center">

**Maintained with ❤️ by [NgoTanLoi](https://github.com/ngotanloi)**

*Dự án được xây dựng với tư duy đặt người dùng làm trung tâm.*

</div>
