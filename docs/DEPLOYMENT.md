# Triển khai SmartLMS

Tài liệu này mô tả quy trình kiểm tra và triển khai production bằng Docker Compose. Tất cả lệnh được chạy từ thư mục gốc dự án.

## 1. Yêu cầu

- Docker Engine và Docker Compose v2.
- PHP 8.3–8.4 và Composer 2 để cài dependency backend được bind mount vào container.
- Node.js 24 và npm để tạo frontend assets production.
- Git.
- Quyền đọc secret production và quyền quản trị máy chủ.
- DNS/reverse proxy hoặc Cloudflare Tunnel trỏ đến cổng ứng dụng đã cấu hình.

## 2. Kiểm tra trước khi triển khai

Mọi thay đổi trên `main` phải vượt qua workflow `CI` gồm:

1. PHPUnit trên PHP 8.4.
2. Laravel Pint ở chế độ kiểm tra (`vendor/bin/pint --test`).
3. Frontend build trên Node.js 24 bằng `npm ci` và `npm run build`.

Có thể chạy tương đương tại local:

```bash
composer install
php artisan test
vendor/bin/pint --test
npm ci
npm run build
docker compose config --quiet
```

Không triển khai nếu bất kỳ bước nào thất bại.

## 3. Chuẩn bị môi trường lần đầu

```bash
cp .env.example .env
php scripts/rotate-secrets.php
```

Điền các giá trị riêng của môi trường như `APP_URL`, API key, email, R2 và cấu hình backup. Không commit `.env`.

Kho tài liệu chung sử dụng cùng bucket R2 với bài học/bài tập và được tách bằng tiền tố `shared-documents/`. Cấu hình production tối thiểu:

```dotenv
SHARED_DOCUMENT_FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=<access-key>
R2_SECRET_ACCESS_KEY=<secret-key>
R2_BUCKET=<bucket-name>
R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
R2_REGION=auto
R2_USE_PATH_STYLE_ENDPOINT=false
```

Bucket nên để private vì tài liệu được tải xuống thông qua controller có Policy kiểm tra quyền, không qua URL công khai.

Cài dependency và tạo frontend assets trước khi khởi động container. Cấu hình Compose hiện tại bind mount mã nguồn từ host, vì vậy `vendor/` và `public/build/` phải tồn tại trên máy triển khai:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
```

Kiểm tra Compose trước khi khởi động:

```bash
docker compose config --quiet
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
```

Kiểm tra container đã nhận đúng disk cho kho tài liệu:

```bash
docker compose exec app php artisan tinker --execute="dump(config('filesystems.shared_document_disk'));"
```

Kết quả mong đợi là `r2`.

MySQL, PostgreSQL và Reverb chỉ được mở trong Docker network. Chỉ Nginx publish cổng ứng dụng ra host.

## 4. Triển khai phiên bản mới

Tạo backup trước khi thay đổi container hoặc database:

```bash
docker compose exec app php artisan smartlms:backup
```

Sau khi CI của commit cần triển khai đã xanh:

```bash
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
docker compose build --pull
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
docker compose ps
curl --fail http://localhost:${APP_PORT:-8000}/up
```

Kiểm tra thêm queue worker và Reverb:

```bash
docker compose logs --tail=100 queue-worker
docker compose logs --tail=100 reverb
```

## 5. Rotate secret trên hệ thống đã có dữ liệu

Luôn tạo backup trước. Với MySQL/PostgreSQL đang chạy và volume hiện hữu:

```bash
docker compose exec app php artisan smartlms:backup
php scripts/rotate-secrets.php --sync-running-databases
docker compose up -d --force-recreate
```

Rotation `APP_KEY` làm session/cookie cũ mất hiệu lực. Người dùng sẽ phải đăng nhập lại.

## 6. Rollback

Rollback mã nguồn về commit đã xác nhận ổn định, sau đó build và recreate container:

```bash
git switch --detach <commit-an-toan>
docker compose build
docker compose up -d
```

Không tự động rollback migration có thay đổi dữ liệu. Nếu cần khôi phục database, dùng bản backup gần nhất và thực hiện trong maintenance window.

## 7. Checklist sau triển khai

- `docker compose ps` cho thấy tất cả service đang chạy và MySQL healthy.
- `/up` trả HTTP 200.
- Đăng nhập web hoạt động; `APP_DEBUG` vẫn tắt.
- Queue worker không có lỗi lặp lại.
- Reverb lắng nghe tại cổng nội bộ 8080.
- MySQL/PostgreSQL/Reverb không có host port binding.
- Luồng upload, chấm bài và tác vụ AI được kiểm tra nhanh nếu bản phát hành liên quan.
- Giáo viên có thể tải lên rồi tải xuống một tệp thử trong **Tài liệu chung**; giáo viên khác không thể sửa hoặc xóa tệp đó.
