# Báo cáo PHASE 3 — Technical Refactor

**Ngày thực hiện:** 09/08/2026

**Branch:** `codex/phase-3-technical-refactor`

**Phạm vi:** refactor theo từng use case; không thay đổi business behavior, schema hoặc hạ tầng.

## 1. Vấn đề đã xác nhận

| Khu vực | Hiện trạng trước refactor | Ảnh hưởng | Phạm vi đã xử lý |
|---|---|---|---|
| Attendance save | Controller validate, kiểm tra scope, diff cell, bulk upsert và dispatch job | Khó test riêng, controller chịu nhiều responsibility | Tách `SaveAttendanceRequest` và `SaveAttendance` |
| Assignment create | Controller validate, kiểm tra lesson-course, normalize extension, đặt default, create và notify | Rule tạo bài tập khó tái sử dụng và dễ lệch với update | Tách `CreateAssignmentRequest` và `CreateAssignment` |
| Teacher grading dashboard | Query count, assignment queue, quiz queue và format item nằm trong controller | Read model khó cô lập, controller dài | Tách `PendingGradingQuery` |
| DeepSeek provider calls | Token, URL, model và HTTP options lặp ở 6 vị trí | Dễ lệch timeout/endpoint, khó thay provider client | Tách `AIProviderClient` |
| Attendance JavaScript | 141 dòng script inline, inline event handler và một Axios CDN trùng layout | Khó build/test, dễ init nhầm khi DOM thay đổi | Tách Vite module có root guard và duplicate-init guard |

## 2. Kiến trúc sau refactor

```text
AttendanceController
  → SaveAttendanceRequest
  → SaveAttendance
  → AttendanceColumn / AttendanceData / DB
  → NotifyFrequentAttendanceAbsences

AssignmentController
  → CreateAssignmentRequest
  → CreateAssignment
  → AssignmentUploadTypes / Assignments
  → NotificationCenter

DashboardController
  → PendingGradingQuery
  → assignment_submissions / QuizAttempt

DeepSeekService
  → AIProviderClient
  → Laravel HTTP client
```

Các namespace mới bám modular monolith hiện tại:

- `App\Application\Attendance`
- `App\Application\Assessment`
- `App\Queries\Dashboard`
- `App\Http\Requests\Attendance`
- `App\Http\Requests\Assignment`

Không tạo generic repository, không chuyển SPA, không thêm microservice hoặc dependency.

## 3. Controller/service đã giảm responsibility

| Class | Trước Phase 3 | Sau Phase 3 | Thay đổi |
|---|---:|---:|---:|
| `AttendanceController` | 264 dòng | 185 dòng | -79 |
| `DashboardController` | 667 dòng | 606 dòng | -61 |
| `AssignmentController` | 680 dòng | 644 dòng | -36 |
| `DeepSeekService` | 1.121 dòng | 1.089 dòng | -32 |

Số dòng chỉ là chỉ báo phụ. Thay đổi chính là controller không còn sở hữu workflow lưu điểm danh/tạo bài tập, dashboard không còn tự xây grading queue, và `DeepSeekService` không còn trực tiếp cấu hình HTTP client.

## 4. Characterization test và coverage

Behavior được khóa trước hoặc trong từng extraction:

- Attendance: dirty-cell bulk write, no-op không write/job, scope course/student, status normalization, giữ note cũ, flash message, payload lỗi.
- Assignment: authorization, rich-text sanitizer, default grading scale, AI grading default, normalize extension, publish timestamp, notification dedupe, invalid payload không ghi DB.
- Dashboard: assignment/quiz queue order, normalized attention score, priority suggestion title/CTA, query budget cho ba role.
- AI: endpoint, Bearer token, model payload, RAG citation, trusted source block, quiz schema validation, Course Planner success/retry/timeout/provider error.
- Frontend: Attendance dùng Vite module, không còn inline handler/Axios CDN riêng, có root guard và duplicate-init guard.

Kết quả cuối phase:

- PHPUnit: **162 test, 797 assertion, tất cả pass**.
- So với baseline sau Phase 2: **+7 test, +40 assertion**.
- Laravel Pint toàn dự án: **pass**.
- Vite production build: **pass**.
- E2E Playwright: chưa chạy được assertion vì Chrome headless dừng ngay khi launch với `SIGABRT/EPERM`; không có tab SmartLMS đã đăng nhập trong trình duyệt app để smoke test thay thế.

## 5. Query Object

Đã tạo `PendingGradingQuery` theo đúng use case, không theo model:

- tổng bài assignment chưa chấm/đã chấm;
- tổng quiz chờ chấm;
- hợp nhất assignment và quiz thành grading queue;
- giữ nguyên thứ tự theo thời điểm chờ;
- giữ query budget Teacher Dashboard **≤ 15**.

Các query object nên tách tiếp, theo thứ tự:

1. `ClassProgressQuery` từ `ClassManagementController`.
2. `StudentDashboardQuery` từ nhánh học sinh trong `DashboardController`.
3. `AttendanceSummaryQuery` cho màn hình/xuất dữ liệu điểm danh.
4. `AssignmentCatalogQuery` nếu filter/stat tiếp tục mở rộng.

## 6. FormRequest

Đã thêm:

- `SaveAttendanceRequest`.
- `CreateAssignmentRequest`.

Policy chỉ được gọi tại controller; FormRequest chỉ xác nhận request có user, tránh duplicate Policy. Rule và message nghiệp vụ đầu vào được đặt gần request contract.

Inline validation còn lại đã xác nhận:

- `QuestionController`: 13 vị trí — ưu tiên cao tiếp theo.
- `ClassManagementController`: 6 vị trí.
- `AssignmentController`: 4 vị trí (update, grade, archive selection và submit).
- `CourseController`: 3 vị trí.
- `LessonController`: 3 vị trí.
- `AttendanceController`: 2 vị trí (add/update column).

## 7. Service decomposition AI

Responsibility hiện tại của `DeepSeekService` sau audit:

1. Chat/tutor orchestration.
2. Ghép local context + vector RAG.
3. Prompt construction.
4. PII processing qua `AiPiiSanitizer`.
5. Quiz question generation.
6. Learning/assignment analysis.
7. Teaching content/course plan generation.
8. Response validation/JSON decoding.
9. Usage/cost tracking.
10. Retry/error classification cho Course Planner.

Đã extract provider communication sang `AIProviderClient`; toàn bộ output, prompt, timeout, endpoint và retry behavior được giữ nguyên qua test.

Extraction tiếp theo nên là `CourseGenerationService`, vì đây là responsibility độc lập lớn nhất. Không nên tách utility nhỏ như JSON decode thành class riêng chỉ để giảm số dòng.

## 8. Blade components

Ba component được sử dụng nhiều nhất đã tồn tại và được kiểm chứng:

| Component | Số view sử dụng | Accessibility contract |
|---|---:|---|
| `PageHeader` | 9 | `<header>`, breadcrumb `<nav>`, `aria-current` |
| `Pagination` | 7 | navigation label, `aria-current`, `aria-disabled`, `rel=prev/next` |
| `EmptyState` | 6 | decorative icon ẩn khỏi screen reader, heading/action rõ |

Không thay markup hàng loạt vì chưa có bằng chứng visual regression test cho các trang legacy. `StatCard`, `StatusBadge`, `Button`, `RoleBadge` đã tồn tại nhưng mức adoption còn thấp.

## 9. JavaScript organization

`resources/js/pages/attendance.js` hiện:

- chỉ chạy khi `.att-page` tồn tại;
- chống duplicate initialization bằng `data-attendance-initialized`;
- gắn event listener trong module thay cho `onclick`/`onblur`;
- dùng route URL do Blade cấp qua data attribute;
- giữ dirty-cell submit, status cycle, note, filter, keyboard navigation và add-column behavior;
- không thêm dependency; tiếp tục dùng Axios đã có ở layout.

Bundle Attendance production: khoảng **3,97 KB**, gzip **1,64 KB**.

## 10. Dead code/duplication phát hiện

- Đã loại Axios CDN trùng riêng ở Attendance; layout đã tải Axios.
- Đã loại hai global function `updateColumnName` và `deleteColumn` cùng toàn bộ inline script Attendance.
- Chưa có PHP method nào đủ bằng chứng để xóa an toàn trong phạm vi này.
- `ClassManagementController::isAbsentValue()` trùng responsibility với `AttendanceStatus`; vẫn đang được gọi nên chưa phải dead code, cần characterization test trước khi hợp nhất.
- Normalize assignment extension còn lặp giữa create use case và update controller; cần tách cùng `UpdateAssignment`, không thay riêng trong commit này.

## 11. Migration và dữ liệu

- Không tạo migration.
- Không thay schema.
- Không đọc/ghi/chuyển đổi dữ liệu production.
- Không thêm package Composer/NPM.

## 12. Rủi ro còn lại

| Rủi ro | Mức | Hướng xử lý |
|---|---:|---|
| Chưa chạy được browser E2E do môi trường Chrome | Medium | Chạy `critical-pages-console` và Attendance interaction trong CI/host có Chrome hoạt động |
| `ClassManagementController` vẫn 1.026 dòng | High | Tách `ClassProgressQuery`/student snapshot theo characterization test |
| `QuestionController` còn 13 inline validation | High | Chuyển từng endpoint Question/AI sang FormRequest, không làm một lần |
| `DeepSeekService` còn 1.089 dòng | High | Extract `CourseGenerationService`, sau đó teaching/question generation |
| Dashboard student/admin query vẫn trong controller | Medium | Tách theo role read model, giữ query budgets |
| Blade legacy còn inline JS/CSS lớn | Medium | Di chuyển theo từng page có E2E/console test |
| Application use case còn dùng HTTP 422 exception cho scope mismatch | Low | Chỉ đổi sang exception mapping khi có contract chung, tránh đổi response hiện tại |
| TinyMCE chunk khoảng 1,3 MB | Medium | Đánh giá dynamic import theo modal/page; không xử lý trong Phase 3 vì cần perf baseline |

## 13. Commit đã tạo

1. `253ad4e refactor(attendance): tách use case lưu điểm danh`
2. `fa39aa1 refactor(dashboard): tách read model hàng chờ chấm`
3. `b428eb7 refactor(ai): tách client giao tiếp nhà cung cấp`
4. `c2fea19 refactor(frontend): tách JavaScript trang điểm danh`
5. `9d0e4e6 refactor(assignment): tách use case tạo bài tập`

## 14. Việc tiếp theo nên thực hiện

Lát cắt tiếp theo đề xuất: **`BuildClassProgress` + `ClassProgressQuery`**.

Lý do:

- `ClassManagementController` đang là controller lớn nhất (1.026 dòng).
- Class Progress có test/query budget nền từ Phase 2 và giá trị maintainability cao.
- Có thể tách read-only trước, ít rủi ro hơn import/replace roster.
- Đây là dependency tốt để sau đó tách AI Class Insight mà không trộn query học tập với prompt AI.
