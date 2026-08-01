# Kiểm thử SmartLMS

## E2E bằng Playwright

Chạy các luồng công khai trên cả máy tính và điện thoại:

```bash
npm run test:e2e
```

Để kiểm thử điều hướng theo vai trò, cấu hình tài khoản thử nghiệm riêng. Không dùng tài khoản thật của học viên trong CI:

```bash
E2E_ADMIN_USERNAME=... E2E_ADMIN_PASSWORD=... \
E2E_TEACHER_USERNAME=... E2E_TEACHER_PASSWORD=... \
E2E_STUDENT_USERNAME=... E2E_STUDENT_PASSWORD=... \
npm run test:e2e
```

Có thể đổi máy chủ bằng `E2E_BASE_URL`. Mặc định E2E đọc trang tại `https://smartlms.io.vn`.

## Kiểm thử tải 50–100 người dùng

Kịch bản mặc định chỉ đọc trang công khai và không thay đổi dữ liệu:

```bash
LOAD_DURATION=30 npm run test:load:50
LOAD_DURATION=30 npm run test:load:100
```

Để đo các trang sau đăng nhập, chỉ sử dụng tài khoản tải chuyên dụng trên môi trường thử nghiệm:

```bash
LOAD_USERNAME=... LOAD_PASSWORD=... LOAD_DURATION=60 npm run test:load:100
```

Các biến điều chỉnh:

- `LOAD_BASE_URL`: máy chủ đích, mặc định `http://127.0.0.1:8000`.
- `LOAD_PATHS`: danh sách trang đọc, phân cách bằng dấu phẩy.
- `LOAD_P95_LIMIT_MS`: ngưỡng p95, mặc định 1.500 ms.
- `LOAD_ERROR_RATE_LIMIT`: ngưỡng tỷ lệ lỗi, mặc định 0,01 tương ứng 1%.

Không chạy tải có đăng nhập trên production bằng tài khoản học viên thật. Mỗi người dùng ảo tạo một phiên đăng nhập và có thể làm tăng bảng session/nhật ký truy cập.

## Kết quả chuẩn ngày 01/08/2026

Đích đo là Docker cục bộ, luồng đọc công khai, thời gian 10 giây mỗi mức:

| Mức tải | Yêu cầu/giây | p50 | p95 | p99 | Lỗi |
|---:|---:|---:|---:|---:|---:|
| 50 người | 290,8 | 167 ms | 222 ms | 244 ms | 0% |
| 100 người | 282,6 | 367 ms | 434 ms | 450 ms | 0% |

Cả hai mức đạt ngưỡng p95 dưới 1.500 ms và tỷ lệ lỗi dưới 1%. Đây là kết quả cho trang công khai; cần tài khoản thử nghiệm riêng để xác lập chuẩn cho dashboard, khóa học và bài tập sau đăng nhập.
