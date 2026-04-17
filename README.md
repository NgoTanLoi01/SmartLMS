# 🎓 LMS Platform — Hệ Thống Quản Lý Giáo Dục Trực Tuyến

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-Container-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com)
[![AI-Integration](https://img.shields.io/badge/AI-Gemini--Pro-blue?style=for-the-badge&logo=google-gemini&logoColor=white)](https://ai.google.dev/)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

> Hệ thống quản lý học tập (LMS) thế hệ mới — tích hợp Trí tuệ nhân tạo để cá nhân hóa giáo dục.  
> Được phát triển bởi **NgoTanLoi** với mục tiêu tối ưu hóa quy trình dạy và học, định hướng nghiên cứu luận văn thạc sĩ.

---

## ✨ Điểm Nổi Bật

| # | Tính năng | Mô tả |
|---|-----------|-------|
| 🧠 | **Smart Sidebar** | Giao diện học tập lấy cảm hứng từ Udemy — điều hướng mượt mà, xử lý Video thông minh. |
| 🎨 | **Intuitive UX/UI** | Thiết kế Hover-to-Show, tập trung tối đa vào nội dung bài học, sạch sẽ và hiện đại. |
| 🐳 | **Dockerized Workflow** | Đóng gói toàn bộ môi trường (Nginx, PHP, MySQL) — sẵn sàng triển khai chỉ với 1 câu lệnh. |
| 🏗️ | **Robust Architecture** | Sử dụng Resource Controller, Eloquent Relationships và hệ thống RBAC (Policy/Middleware) bảo mật. |
| 🤖 | **AI-Native Core** | Sẵn sàng cho việc tích hợp các mô hình ngôn ngữ lớn (LLM) để chấm bài và hỗ trợ học viên. |

---

## 🚀 Tính Năng Hệ Thống

### 1. Quản lý lõi (Core Management)

- [x] **RBAC System** — Phân quyền chặt chẽ: Admin, Teacher, Student qua Middleware & Policies.
- [x] **Course Architecture** — Quản lý khóa học đa cấp (Course → Module → Lesson).
- [x] **Learning Mode** — Trình phát bài học hỗ trợ đa phương tiện (Video, Documents).
- [x] **Smart Interaction** — Xử lý Logic phía Client với JavaScript ES6 & Modal tương tác nhanh.

### 2. Hệ thống đánh giá & AI (AI & Assessment)

- [ ] **AI Smart Grading** — Tự động chấm điểm bài tập tự luận và nhận xét chi tiết bằng Gemini API *(Tiêu điểm nghiên cứu)*.
- [ ] **AI Quiz Generator** — Tự động tạo bộ câu hỏi trắc nghiệm từ nội dung file PDF bài giảng hoặc văn bản.
- [ ] **Personalized Learning Path** — AI phân tích lịch sử học tập để gợi ý lộ trình ôn tập riêng biệt cho từng học sinh.
- [ ] **AI Tutor Bot** — Trợ lý ảo hỗ trợ giải đáp thắc mắc về nội dung bài học 24/7 trực tiếp tại trang bài giảng.

---

## 🛠 Công Nghệ Cốt Lõi

```
Backend   : Laravel 11 (PHP 8.2+)
Database  : MySQL 8.0 — tối ưu hóa Index & Relationships
Frontend  : Bootstrap 5 (Customized) · FontAwesome Pro · JavaScript ES6
DevOps    : Docker Compose (Nginx + PHP-FPM + MySQL)
AI Stack  : Google Gemini API / OpenAI API (Integration via Laravel HTTP Client)
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

---

## 📁 Cấu Trúc Dự Án

```
lms-system/
├── app/
│   ├── Http/Controllers/     # Resource Controllers (Logic xử lý chuẩn mực)
│   ├── Models/               # Eloquent Models & Database Relationships
│   ├── Policies/             # RBAC Authorization (Phân quyền người dùng)
│   └── Services/AI/          # Logic tích hợp Gemini/OpenAI API (Service Pattern)
├── resources/
│   ├── views/                # Blade Templates (Giao diện người dùng)
│   └── js/                   # JavaScript ES6 (Xử lý Sidebar, Modal logic)
├── database/
│   ├── migrations/           # Định nghĩa cấu trúc Database
│   └── seeders/              # Dữ liệu mẫu (Users, Courses, Lessons)
├── docker/                   # Cấu hình môi trường Nginx, PHP-FPM
└── docker-compose.yml        # Orchestration toàn bộ hệ thống
```

---

## 🔮 Lộ Trình Phát Triển (Roadmap)

- [ ] Hoàn thiện khung quản lý khóa học và giao diện học tập.
- [ ] Xây dựng module Submission System (Nộp bài tập và quản lý file).
- [ ] Tích hợp Gemini AI để hỗ trợ chấm bài tự luận *(Phục vụ luận văn thạc sĩ)*.
- [ ] Tự động hóa việc cấp chứng chỉ (PDF Certificate) khi hoàn thành khóa học.
- [ ] Thực hiện Load Testing để đảm bảo hệ thống chịu tải 100+ người dùng đồng thời.

---

## 🤝 Đóng Góp

Mọi đóng góp đều được chào đón! Vui lòng mở **Issue** hoặc tạo **Pull Request**.

---

## 📄 License

Dự án được phát hành theo giấy phép [MIT](LICENSE).

---

<div align="center">
  Maintained with ❤️ by <strong>NgoTanLoi</strong><br/>
  Dự án hướng tới giải pháp giáo dục thông minh và cá nhân hóa.
</div>
