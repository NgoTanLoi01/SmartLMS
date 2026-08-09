# SmartLMS Phase 2 — Core Optimization

Ngày kiểm chứng: 2026-08-09. Các số liệu benchmark chạy trên SQLite cô lập trong cùng PHP process; dùng để so sánh thuật toán trước/sau, không thay thế load test trên production MySQL/R2/Redis.

## 1. Attendance baseline và kết quả

Kịch bản: 8 cột điểm danh đã tồn tại, giáo viên đổi trạng thái ở cột mới nhất cho mỗi 10 học viên. Baseline frontend cũ gửi lại toàn bộ ma trận; backend cũ chạy một `upsert` cho từng cell và một query notification cho từng học viên.

| Học viên | Cell baseline | Query trước | Write trước | Dirty cell sau | Query sau | Write sau | Giảm query | Giảm write |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 30 | 240 | 275 | 240 | 3 | 6 | 1 | 97,8% | 99,6% |
| 50 | 400 | 455 | 400 | 5 | 6 | 1 | 98,7% | 99,8% |
| 100 | 800 | 905 | 800 | 10 | 6 | 1 | 99,3% | 99,9% |

Payload đầy đủ từ frontend cũ vẫn tương thích: 240/400/800 cell đều còn 6 query và 1 write. Latency dirty-payload đo được lần lượt 27,50 ms, 1,65 ms và 2,08 ms; baseline là 31,60 ms, 22,06 ms và 43,18 ms. Mốc 30 học viên chịu cold-start nên không dùng latency này làm SLO. Memory delta của benchmark dao động 0–4 MB và chưa đủ ổn định để kết luận.

Luồng sau refactor:

`dirty cells → validate course/roster → fetch current cells → bỏ no-op → transaction bulk upsert → queue notifications → aggregate absence query`

Backend không tin payload: mọi column phải thuộc course, mọi user phải thuộc roster của course. Unique `(attendance_column_id, user_id)` vẫn là lớp bảo vệ race condition cuối cùng. Job notification dùng queue `notifications`, unique job key và notification dedupe key nên retry an toàn.

Chạy lại benchmark:

```bash
php artisan test tests/Benchmarks/AttendanceSaveBenchmarkTest.php
ATTENDANCE_BENCHMARK_PAYLOAD=full php artisan test tests/Benchmarks/AttendanceSaveBenchmarkTest.php
```

## 2. Pagination audit

| List | Mức | Xác nhận | Xử lý phase này |
|---|---|---|---|
| Courses | P0 | `get()` toàn bộ và eager-load `classes.students` chỉ để đếm | Đã paginate 18; correlated distinct student count; aggregate stats bằng SQL; giữ query string |
| Documents/RAG | P0 | Group toàn bộ `document_chunks`, sau đó hydrate uploader/course cho toàn collection | Đã paginate 18 trên PostgreSQL; chỉ hydrate metadata của page hiện tại |
| Assignment submissions modal | P0 | Load toàn roster và toàn submissions rồi ghép bằng PHP | Đã paginate 25; eager-load submission của page; aggregate submitted count riêng |
| Assignments | Đạt | Main list đã paginate 18, filter chạy SQL | Không đổi; selector course/module là P2 |
| Notifications | Đạt | Paginate 20 và `withQueryString()` | Không đổi |
| Questions | P1 | Main list paginate 15; course/question-bank selector vẫn tải toàn bộ | Chưa đổi để tránh thay UX chọn ngân hàng |
| Students | P1 | Search/status và snapshot thực hiện trên toàn collection | Chưa cắt trang vì status phụ thuộc read model tổng hợp |
| Class Progress | P1 | Query count cố định nhưng hydrate toàn snapshot và dựng modal cho mọi học viên | Chưa dùng cache; cần read model SQL/page summary trước |
| Shared documents | Đạt | Paginate 18, filter/sort SQL | Không đổi |
| Grading review queue | P1 | Một assignment lớn vẫn dựng queue chi tiết toàn roster | Modal list P0 đã sửa; review page cần tách detail endpoint ở phase sau |

Không thêm index chỉ vì có pagination. Search hiện dùng `%keyword%`, B-tree không giải quyết được leading wildcard. Các filter/index dashboard và notification cần thiết đã tồn tại từ migration trước; index mới chỉ nên thêm sau `EXPLAIN` trên production-like MySQL/PostgreSQL.

## 3. Query audit và budgets

Dashboard đã được đo bằng listener trên read model hiện tại:

| Page | Đã đo | Budget regression |
|---|---:|---:|
| Teacher Dashboard | 12 | ≤ 15 |
| Student Dashboard | 11 | ≤ 15 |
| Admin Dashboard | 24 | ≤ 26 |
| Notifications | Chưa benchmark production-like | Đề xuất ≤ 4 |
| Class Progress | Fixed-query nhưng row volume chưa benchmark | Đề xuất ≤ 14 và chỉ hydrate page hiện tại |
| Grading review | Chưa có fixture 100 học viên | Đề xuất ≤ 10 cho page + ≤ 2 cho từng detail AJAX |
| Attendance show | Ma trận phụ thuộc số buổi × học viên | Đề xuất ≤ 8; cần giới hạn cửa sổ cột khi số buổi lớn |

Dashboard hiện dùng aggregate/subquery thay cho query trong loop. Class Progress dùng `loadSnapshotContext` gom dữ liệu theo tập ID nên không có N+1, nhưng vẫn tăng memory theo `students × courses × activities`; cache không được dùng để che điểm nghẽn này.

## 4. Redis architecture và rollback

Development Docker có Redis 7.4, AOF `everysec`, healthcheck và volume riêng. Image PHP cài `phpredis`, không thêm Composer package.

- Redis DB 0: queue và distributed locks.
- Redis DB 1: cache/rate limit.
- Namespace: `REDIS_PREFIX` và `CACHE_PREFIX`, phải chứa environment (`smartlms:production:`).
- Docker dùng `noeviction` để queue/lock không bị mất. Ở quy mô lớn, tách cache sang instance `allkeys-lru` bằng `REDIS_CACHE_HOST`; queue/locks tiếp tục ở instance `noeviction`.
- Rate limiter và `Cache::lock()` dùng cache store/lock connection hiện tại; khi `CACHE_STORE=redis`, chúng trở thành distributed.
- Reverb pub/sub chưa bật vì production hiện chỉ có một Reverb instance. Chỉ bật `REVERB_SCALING_ENABLED=true` khi chạy từ hai Reverb node trở lên.

Cutover an toàn theo bước:

1. Deploy Redis + PHP extension, vẫn giữ `CACHE_STORE=file`, `QUEUE_CONNECTION=database`; kiểm tra `redis-cli ping`.
2. Chuyển cache sang `CACHE_STORE=failover` hoặc `redis`, theo dõi error/latency. `CACHE_FAILOVER_STORES=redis,database` là fallback cho cache.
3. Dừng producer ngắn, drain bảng `jobs`, đặt `QUEUE_CONNECTION=redis`, restart bốn worker pool, chạy `smartlms:queue-health --json`.
4. Chỉ sau khi ổn định mới cân nhắc Reverb scaling.

Không tự động failover queue Redis sang database vì có thể xử lý job hai lần và phá thứ tự. Khi Redis queue lỗi, rollback có chủ đích: dừng worker/producer, đổi `QUEUE_CONNECTION=database`, restart worker và xác nhận lag. Cache rollback chỉ cần `CACHE_STORE=file` hoặc `database`.

## 5. Queue architecture

| Pool | Jobs hiện tại | Worker timeout | Ghi chú |
|---|---|---:|---|
| `default` | Job request-critical tương lai; hiện chưa có job riêng | 120s | Không nhận AI/document |
| `notifications` | `NotifyFrequentAttendanceAbsences` | 60s | 3 tries, timeout 30, backoff 5/30/120, unique + dedupe |
| `ai` | GenerateCoursePlan, GenerateQuizQuestions, AnalyzeAssignmentSubmission, AnalyzeLearningWithAi | 600s | Job có tries/backoff/timeout/failed state |
| `documents` | ProcessDocumentPdf, SyncLegacyLearningMaterials | 600s | Không chặn notification |

`smartlms:queue-health --json` xuất depth, oldest pending timestamp, lag seconds và failed job count cho collector. Chưa thêm Horizon: queue Redis và metric cơ bản đáp ứng nhu cầu hiện tại; Horizon chỉ có ROI khi cần autoscaling/supervisor UI theo dữ liệu vận hành.

## 6. Private storage policy

| Nhóm | Policy | Trạng thái |
|---|---|---|
| Submission | Private R2 ở production; controller-authorized proxy; checksum SHA-256 | Đã triển khai cho upload mới và migration local/public → R2 |
| Lesson attachments | Private disk cấu hình; Docker production mặc định R2 | Upload mới sẵn sàng; migrate từng batch sau submissions |
| Shared documents | Private R2; Policy download/preview | Đã có trước phase này |
| Learning materials | Disk metadata theo record | Chưa migrate; chạy sau lessons |
| Quiz essay attachments | Hard-code `local` | P0 còn lại trước horizontal scale |
| RAG source PDF | Hard-code `local`; document worker dùng local path | P0 còn lại trước khi app/worker nằm khác node |
| Generated/backup files | Local có retention, tùy chọn R2 | Giữ nguyên trong phase này |

Submission upload mới tạo checksum trước upload, retry tối đa ba lần, xác nhận object tồn tại và đúng size, cập nhật DB trước rồi mới xóa file cũ. Nếu DB write lỗi, object mới được dọn. Download/preview vẫn qua server-side Policy, không tạo public URL.

Migration vận hành nhóm submission:

```bash
SUBMISSION_FILESYSTEM_DISK=r2 php artisan smartlms:migrate-private-learning-files --group=submissions --dry-run
SUBMISSION_FILESYSTEM_DISK=r2 php artisan smartlms:migrate-private-learning-files --group=submissions
```

Mặc định bản nguồn được giữ làm checkpoint rollback. Sau thời gian xác minh, có thể chạy lại với `--delete-source`; lệnh chỉ xóa khi không còn reference nào dùng path/disk nguồn. Rollback trước khi xóa nguồn: đổi `file_disk` về disk cũ theo checkpoint/export DB. Không chạy `--delete-source` trước khi backup DB và kiểm tra checksum.

## 7. Remaining bottlenecks

1. RAG source PDF và quiz essay attachments còn phụ thuộc local disk.
2. Class Progress/Students vẫn hydrate snapshot toàn lớp; cần paginated read model mà không làm sai class aggregate.
3. Grading review dựng modal/detail cho toàn roster.
4. Question-bank/course selectors chưa có async search khi dữ liệu rất lớn.
5. Attendance show vẫn render toàn ma trận lịch sử; save đã tối ưu nhưng read cần windowing khi có hàng trăm buổi.
6. Chưa có production-like load test MySQL/PostgreSQL/R2/Redis; latency benchmark hiện chỉ mang tính so sánh thuật toán.

## 8. Nhóm commit đề xuất

1. `perf(attendance): bulk upsert dirty cells and queue absence notifications`
2. `perf(pagination): paginate course document and submission read models`
3. `infra(redis): add phpredis service cache configuration and healthcheck`
4. `infra(queue): split worker pools and expose queue lag metrics`
5. `feat(storage): harden private submission uploads and add checksums`
6. `docs(phase2): document baselines cutover rollback and remaining bottlenecks`
