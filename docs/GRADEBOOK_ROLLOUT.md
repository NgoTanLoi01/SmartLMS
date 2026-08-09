# SmartLMS Gradebook rollout runbook

## Trạng thái hiện tại

- Gradebook là schema cộng thêm và shadow projection; dữ liệu điểm legacy không bị xóa hoặc sửa bởi migration.
- `GRADEBOOK_READ_SOURCE=legacy` phải được giữ nguyên trong giai đoạn backfill/reconciliation.
- Assignment và Quiz chỉ project sang Gradebook khi có `grade_items` liên kết explicit.
- Khi còn đọc legacy, lỗi cấu hình/lock ở shadow projection được ghi warning để không phá flow production cũ.
- Sau cutover explicit sang `gradebook`, lock/finalization của Gradebook trở thành bắt buộc và lỗi projection sẽ rollback thay đổi Assessment.

## 1. Backup/checkpoint

Tạo backup database theo quy trình production hiện hành và lưu checksum/restore point trước khi migrate. Không dùng PHPUnit trên database production.

## 2. Tạo schema additive

Kiểm tra SQL trước:

```bash
docker compose exec app php artisan migrate --pretend --force \
  --path=database/migrations/2026_08_09_140000_create_gradebook_foundation.php
```

Sau khi duyệt SQL và backup:

```bash
docker compose exec app php artisan migrate --force \
  --path=database/migrations/2026_08_09_140000_create_gradebook_foundation.php
```

Migration chỉ tạo bảy bảng mới. Rollback chỉ được thực hiện khi chưa có dữ liệu Gradebook cần giữ.

## 3. Discovery theo từng course

```bash
docker compose exec app php artisan smartlms:gradebook-discover \
  --course=COURSE_ID \
  --output=storage/app/gradebook/course-COURSE_ID.json
```

File discovery luôn có `approved=false`. Người phụ trách phải xác nhận thủ công:

- period, category và tổng category weight bằng 100%;
- mapping HS1/HS2/Thi;
- `absence_policy` cho dữ liệu `vắng`;
- Assignment/Quiz/Exam và thang điểm;
- `attempt_policy` của từng Quiz;
- `approved_by` là admin hoặc giáo viên sở hữu course;
- đổi `approved=true` sau khi review.

Không suy luận Exam chỉ vì Quiz có session. Không đổi `vắng` thành 0 nếu chưa có quyết định nghiệp vụ.

## 4. Dry-run bắt buộc

```bash
docker compose exec app php artisan smartlms:gradebook-backfill \
  --manifest=storage/app/gradebook/course-COURSE_ID.json \
  --dry-run
```

Chỉ chạy thật khi `errors` rỗng và số lượng `graded`, `ungraded`, `missing`, `excused` đã được đối chiếu.

## 5. Shadow backfill

```bash
docker compose exec app php artisan smartlms:gradebook-backfill \
  --manifest=storage/app/gradebook/course-COURSE_ID.json
```

Backfill là transaction theo course và idempotent theo source version. Retry với cấu trúc đã lệch manifest sẽ bị từ chối thay vì tự sửa cấu hình.

## 6. Reconciliation

```bash
docker compose exec app php artisan smartlms:gradebook-reconcile \
  --manifest=storage/app/gradebook/course-COURSE_ID.json
```

Lưu report gồm `expected_count`, `matched_count`, `mismatch_count`, `checksum` và danh sách mismatch. Gate tối thiểu trước cutover:

- `passed=true` và `mismatch_count=0` cho mọi course trong phạm vi;
- chạy lại reconciliation sau một khoảng dual-write thực tế;
- giáo viên xác nhận formula, missing policy, rounding và attempt policy;
- kiểm tra Gradebook Teacher UI, finalize/reopen và audit log;
- nghiệm thu màn hình điểm thành phần dành cho học sinh.

## 7. Cutover

Chưa bật trong rollout Foundation này. Chỉ đổi:

```dotenv
GRADEBOOK_READ_SOURCE=gradebook
```

sau khi code đọc điểm của học sinh và mọi report chính thức đã chuyển sang Gradebook, reconciliation đạt gate và có kế hoạch rollback. Sau đổi ENV phải clear/rebuild config cache và smoke-test grade Assignment/Quiz, finalize/reopen.

## Rollback

- Trước cutover: đặt `GRADEBOOK_PROJECTION_ENABLED=false` để dừng shadow projection; flow legacy vẫn là source đọc.
- Không xóa bảng Gradebook nếu đã backfill hoặc có audit/finalization; giữ để điều tra và reconciliation.
- Nếu UI mới có vấn đề, bỏ liên kết điều hướng hoặc rollback application code; không rollback dữ liệu legacy.
- Sau cutover: rollback ENV về `GRADEBOOK_READ_SOURCE=legacy`, clear config cache và xác nhận flow Assessment. Reconcile lại trước lần cutover tiếp theo.
