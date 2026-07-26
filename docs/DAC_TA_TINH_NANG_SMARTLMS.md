# ĐẶC TẢ TÍNH NĂNG HIỆN CÓ CỦA SMARTLMS

## 1. Thông tin tài liệu

| Thuộc tính | Giá trị |
|---|---|
| Tên hệ thống | SmartLMS |
| Loại tài liệu | Đặc tả chức năng hiện trạng (As-is Functional Specification) |
| Ngày rà soát | 26/07/2026 |
| Phạm vi | Giao diện web, nghiệp vụ, phân quyền và các tác vụ nền hiện có trong mã nguồn |
| Nhóm người dùng | Admin, Teacher, Student |
| Cơ sở xác định | Routes, middleware, policies, controllers, models, views, services, migrations và automated tests |

> **Quy ước:** “Đã có” nghĩa là mã nguồn và luồng xử lý đã tồn tại. Khả năng vận hành thực tế của AI, OCR, lưu trữ R2, queue và trò chơi thời gian thực còn phụ thuộc cấu hình hạ tầng tương ứng.

## 2. Tổng quan hệ thống

SmartLMS là hệ thống quản lý học tập trực tuyến theo cấu trúc:

**Chương trình học → Khóa học → Chương → Bài học → Bài tập / Bài kiểm tra / Học liệu**

Hệ thống hiện có ba vai trò chính:

| Phạm vi | Admin | Teacher | Student |
|---|---:|---:|---:|
| Quản trị tài khoản và hệ thống | Toàn hệ thống | Không | Không |
| Chương trình, khóa học, lớp | Toàn hệ thống | Dữ liệu do mình sở hữu/phụ trách | Chỉ xem nội dung lớp đang học |
| Nội dung, bài tập, quiz | Toàn hệ thống | Khóa học do mình sở hữu | Chỉ nội dung đã xuất bản và đã đến giờ mở |
| Điểm danh, chấm điểm | Toàn hệ thống | Khóa học do mình sở hữu | Chỉ xem dữ liệu của bản thân |
| Giảng dạy, hợp đồng, báo cáo | Toàn hệ thống | Dữ liệu của bản thân | Không |
| AI và tài liệu RAG | Toàn hệ thống | Khóa học do mình sở hữu | Hỏi đáp trong phạm vi được phép học |

### 2.1. Quy tắc truy cập chung

- Người dùng phải đăng nhập và tài khoản phải đang hoạt động, chưa hết hạn.
- Admin và Teacher đăng nhập bằng email; Student có thể đăng nhập bằng username được sinh từ họ tên/mã học sinh hoặc bằng email nội bộ.
- Hệ thống giới hạn đăng nhập sai: tối đa 5 lần theo tài khoản và IP, khóa tạm 60 giây.
- Phiên đăng nhập được tạo lại sau khi đăng nhập; phiên và CSRF token bị hủy khi đăng xuất.
- Khi tài khoản bị vô hiệu hóa hoặc hết hạn trong lúc đang dùng, middleware tự đăng xuất ở request kế tiếp.
- Mọi vai trò có thể xem thông tin tài khoản và đổi mật khẩu sau khi nhập đúng mật khẩu hiện tại.
- Hệ thống hiện chỉ cung cấp giao diện web dùng session/CSRF; chưa có public API `/api/*` cho mobile hoặc tích hợp bên thứ ba.

### 2.2. Quy tắc vòng đời nội dung

| Đối tượng | Trạng thái |
|---|---|
| Khóa học, bài học, bài tập, quiz | `draft`, `published`, `hidden`, `archived` |
| Chương | `published`, `archived` |
| Lớp học, lịch học | `active`, `hidden`, `archived` |
| Học liệu gắn vào khóa học | `published`, `hidden`, `archived` |
| Chương trình học | `draft`, `published`, `hidden` |

Student chỉ thấy nội dung khi đồng thời thỏa mãn các điều kiện sau:

1. Thuộc một lớp có trạng thái `active`.
2. Lớp được gắn với khóa học tương ứng.
3. Khóa học là loại triển khai (`delivery`), trạng thái `published` và đã đến `available_from` nếu có.
4. Bài học, bài tập hoặc quiz có trạng thái `published` và đã đến `available_from` nếu có.

---

# 3. NHÓM ADMIN

Admin có phạm vi dữ liệu toàn hệ thống và kế thừa hầu hết chức năng nghiệp vụ của Teacher.

## ADM-01. Dashboard quản trị

**Mục đích:** Cung cấp tổng quan nhanh về quy mô và tình trạng vận hành LMS.

**Chức năng đã có:**

- Thống kê tổng số Student, Teacher, lớp và khóa học triển khai.
- Biểu đồ phân bố tài khoản theo vai trò.
- Hiển thị số bài nộp đang chờ chấm.
- Hiển thị lịch học trong ngày.
- Danh sách người dùng mới tạo gần đây.
- Tổng quan các lớp đông học sinh, giáo viên phụ trách và khóa học được gắn.
- Danh sách khóa học mới gần đây.
- Cảnh báo số khóa học nháp, khóa học đã lưu trữ, lớp chưa phân giáo viên và lớp chưa gắn khóa học.
- Liên kết nhanh đến các khu vực quản lý liên quan.

## ADM-02. Quản lý tài khoản người dùng

**Chức năng đã có:**

- Xem danh sách tài khoản, phân trang 20 bản ghi/trang.
- Tìm kiếm theo họ tên, email, username hoặc mã học sinh.
- Lọc theo vai trò Admin/Teacher/Student.
- Lọc theo trạng thái đang hoạt động, bị vô hiệu hóa hoặc đã hết hạn.
- Tạo tài khoản Admin, Teacher hoặc Student.
- Email là bắt buộc với Admin/Teacher; Student có thể không cần email thật.
- Tự chuẩn hóa mã học sinh và tự sinh username/email nội bộ cho Student.
- Đặt ngày hết hạn tài khoản ngay khi tạo hoặc khi cập nhật vòng đời.
- Kích hoạt/vô hiệu hóa tài khoản và lưu lý do vô hiệu hóa.
- Theo dõi thời điểm đăng nhập gần nhất.
- Thu hồi toàn bộ session và personal access token khi tài khoản không còn quyền truy cập.
- Cấp lại mật khẩu về giá trị mặc định `123456` và thu hồi các phiên hiện có.
- Xóa tài khoản không còn ràng buộc sở hữu dữ liệu.

**Quy tắc nghiệp vụ:**

- Admin không thể tự vô hiệu hóa hoặc tự xóa chính mình.
- Không thể khóa/đặt hạn cho Admin đang hoạt động cuối cùng.
- Không thể xóa Teacher vẫn đang sở hữu dữ liệu; phải chuyển quyền sở hữu hoặc vô hiệu hóa trước.
- Thao tác cập nhật vòng đời tài khoản được ghi audit log.

## ADM-03. Quản lý chương trình học

**Chức năng đã có:**

- Tạo chương trình với tên, mã duy nhất, mô tả và trạng thái.
- Xem danh sách chương trình cùng người sở hữu và số khóa học.
- Xem chi tiết một chương trình, tách khóa mẫu và khóa triển khai.
- Thống kê số khóa mẫu, khóa triển khai, lớp và học sinh liên quan.
- Cập nhật thông tin chương trình.
- Xóa chương trình mà không xóa các khóa học liên quan.

## ADM-04. Quản lý khóa học

**Chức năng đã có:**

- Xem toàn bộ khóa học trong hệ thống.
- Tìm theo tên/mô tả; lọc theo chương trình, loại khóa, trạng thái và lớp.
- Phân loại khóa học thành:
  - `template`: khóa mẫu dùng để tái sử dụng nội dung.
  - `delivery`: khóa triển khai thực tế cho lớp học.
- Tạo khóa học mới với tên, mô tả, chương trình, loại khóa, trạng thái và thời điểm mở.
- Gắn một hoặc nhiều lớp khi tạo khóa triển khai.
- Tạo khóa học từ khóa mẫu.
- Khi nhân bản từ mẫu, hệ thống sao chép chương, bài học, file bài học, bài tập, quiz, câu hỏi riêng và liên kết ngân hàng câu hỏi.
- Cập nhật tên, mô tả, chương trình, loại, trạng thái và lịch mở.
- Lưu trữ khóa học mà vẫn giữ dữ liệu học tập.
- Chỉ Admin được xóa vĩnh viễn khóa học đã lưu trữ; thao tác xóa cả dữ liệu liên quan và file bài nộp/file bài học được lập chỉ mục.
- Xem dashboard từng khóa học gồm số học sinh, chương, bài học, bài tập, quiz, tỷ lệ hoàn thành bài học, tỷ lệ nộp bài, tỷ lệ làm quiz, số bài chờ chấm và điểm quiz trung bình.
- Mở chế độ trình chiếu bài giảng toàn màn hình và điều chỉnh cỡ chữ.

## ADM-05. Quản lý cấu trúc nội dung khóa học

**Chương:**

- Thêm, đổi tên và sắp xếp thứ tự bằng kéo/thả.
- Lưu trữ chương; các bài học và bài tập bên trong cũng được chuyển sang lưu trữ.

**Bài học:**

- Tạo/cập nhật tiêu đề, nội dung HTML, URL video/tài nguyên ngoài, chương chứa bài học, trạng thái và thời điểm mở.
- Trình soạn thảo nội dung hỗ trợ định dạng, bảng, liên kết, hình ảnh, mã nguồn, preview và fullscreen.
- Đính kèm PDF, Word, PowerPoint, Excel, ZIP hoặc ảnh; tối đa 20 MB.
- Tải file đính kèm qua endpoint có kiểm tra quyền.
- Sắp xếp thứ tự bài học.
- Lưu trữ bài học; bài tập gắn với bài học cũng được lưu trữ nhưng dữ liệu lịch sử được giữ lại.
- Tự gửi thông báo cho học sinh khi bài học được xuất bản lần đầu.

## ADM-06. Quản lý lớp và học sinh

**Lớp học:**

- Xem tất cả lớp; lọc theo trạng thái.
- Tạo lớp với tên, mã duy nhất, giáo viên phụ trách, trạng thái và các khóa học áp dụng.
- Cập nhật giáo viên, danh sách khóa học và trạng thái lớp.
- Lưu trữ lớp nhưng giữ nguyên thành viên, khóa học và tiến độ.

**Học sinh trong lớp:**

- Xem danh sách học sinh và tìm theo tên, email, username hoặc mã học sinh.
- Tạo tài khoản Student trực tiếp trong lớp; tự sinh username và email nội bộ nếu cần.
- Gỡ học sinh khỏi lớp mà không xóa tài khoản.
- Import Excel/CSV tối đa 5 MB theo hai chế độ:
  - **Bổ sung:** thêm/cập nhật học sinh, giữ nguyên sĩ số hiện tại.
  - **Thay thế:** xem trước số học sinh bị gỡ, xác nhận trong phiên 30 phút rồi mới đồng bộ.
- Chống gửi lặp yêu cầu xác nhận import bằng distributed lock.
- Ghi audit log số dòng xử lý, tài khoản tạo/cập nhật, học sinh đồng bộ/gỡ và dòng bị bỏ qua.

## ADM-07. Theo dõi tiến độ lớp và hồ sơ học sinh

**Chức năng đã có:**

- Xem tiến độ toàn lớp hoặc lọc theo từng khóa học.
- Xem riêng các học sinh cần chú ý.
- Tổng hợp tỷ lệ hoàn thành bài học, tỷ lệ nộp bài, điểm trung bình, số lượt vắng, bài tập thiếu và quiz chưa làm.
- Xem hồ sơ chi tiết từng học sinh gồm:
  - Tiến độ bài học và thời điểm hoàn thành.
  - Bài tập đã nộp/chưa nộp/quá hạn, điểm và nhận xét.
  - Quiz đã làm/chưa làm và điểm.
  - Dữ liệu điểm danh và ghi chú.
  - Hoạt động gần nhất.
  - Diễn biến điểm và xu hướng `improving`, `stable`, `declining` hoặc `insufficient_data`.
- Tự phát hiện học sinh cần chú ý khi có bài quá hạn, điểm quiz trung bình dưới 5, vắng nhiều hoặc chưa có hoạt động.
- Yêu cầu AI phân tích toàn lớp hoặc một học sinh; kết quả gồm tóm tắt, rủi ro, hành động đề xuất và nhận xét mẫu.
- Dữ liệu định danh học sinh được thay bằng mã tham chiếu trước khi gửi sang AI.

## ADM-08. Quản lý lịch học

**Chức năng đã có:**

- Xem lịch dạng calendar của toàn hệ thống.
- Tạo buổi học theo lớp, khóa học, ngày, giờ bắt đầu/kết thúc, phòng và ghi chú.
- Chỉ cho phép chọn khóa học đã được gắn với lớp.
- Cập nhật hoặc lưu trữ lịch học.
- Sao chép toàn bộ lịch từ một ngày sang ngày khác; tự bỏ qua lịch trùng.
- Import lịch từ Excel/CSV tối đa 5 MB; hỗ trợ lớp mặc định và khóa học mặc định.
- Báo số dòng nhập thành công, trùng, không hợp lệ, lớp/môn không khớp.
- Ghi audit log cho import, sao chép, cập nhật và lưu trữ lịch.
- Gửi thông báo cho học sinh khi thêm, thay đổi hoặc hủy buổi học.
- Với ghi chú “Thi kết thúc môn”, hệ thống chỉ giữ một lịch thi kết thúc cho mỗi cặp lớp–khóa học.

## ADM-09. Điểm danh và bảng theo dõi

**Chức năng đã có:**

- Xem toàn bộ học sinh của các lớp gắn với khóa học.
- Thêm cột loại điểm danh, điểm hoặc ghi chú.
- Gắn cột điểm danh với một buổi học cụ thể; một lịch chỉ có một cột điểm danh.
- Nhập trạng thái `present`, `absent`, `late`, `excused`, điểm và ghi chú theo từng học sinh.
- Chuẩn hóa nhiều cách nhập tiếng Việt/tiếng Anh về trạng thái điểm danh chuẩn.
- Đổi tên hoặc xóa cột; xóa cột sẽ xóa dữ liệu con theo cascade.
- Xuất bảng điểm danh khóa học ra Excel.
- Tự gửi cảnh báo chuyên cần khi học sinh có từ 3 lượt vắng/nghỉ trong khóa học.

## ADM-10. Quản lý bài tập và bài nộp

**Tạo và phát hành bài tập:**

- Tạo bài tập gắn bắt buộc với một bài học thuộc khóa học.
- Hỗ trợ ba loại: nộp file, tự luận hoặc file + tự luận.
- Cấu hình hướng dẫn, rubric, thang điểm 1–100, hạn nộp, định dạng file, dung lượng tối đa, AI hỗ trợ chấm, trạng thái và thời điểm mở.
- Gửi thông báo khi xuất bản và khi thay đổi hạn nộp.
- Cập nhật hoặc lưu trữ bài tập; bài nộp và điểm vẫn được giữ lại.

**Quản lý bài nộp:**

- Xem danh sách toàn bộ học sinh của khóa học và trạng thái chưa nộp/chờ chấm/đã chấm.
- Xem chi tiết nội dung tự luận và file bài nộp.
- Preview trực tiếp PDF, ảnh và HTML; các định dạng khác được tải xuống.
- Tải ZIP toàn bộ bài nộp, chỉ bài chưa chấm hoặc các bài được chọn.
- File ZIP gồm thư mục file bài làm và CSV tổng hợp mã học sinh, thời gian nộp, đúng hạn/nộp muộn, điểm và nhận xét.
- Chấm điểm theo đúng thang điểm của bài, nhập nhận xét, lưu hoặc lưu và chuyển sang bài chưa chấm tiếp theo.
- Gửi thông báo điểm/nhận xét cho học sinh và ghi audit log thay đổi.
- Yêu cầu AI gợi ý điểm, feedback, rubric breakdown, điểm mạnh, điểm cần cải thiện và cờ cần giáo viên kiểm tra.
- AI chỉ chạy khi bài tập bật `ai_grading_enabled`; kết quả là gợi ý, giáo viên quyết định điểm cuối cùng.
- Lưu tối đa 10 lần phân tích AI gần nhất cho mỗi bài nộp.

## ADM-11. Ngân hàng câu hỏi và bài kiểm tra

**Ngân hàng câu hỏi:**

- Xem câu hỏi có phân trang, lọc theo ngân hàng và khóa học.
- Tạo ngân hàng câu hỏi dùng chung và gắn với nhiều khóa học.
- Tạo/cập nhật/lưu trữ câu hỏi trắc nghiệm theo mức dễ, trung bình, khó.
- Mỗi câu hỏi có đúng 4 lựa chọn và một đáp án đúng.
- Import câu hỏi từ Excel tối đa 5 MB.
- AI tạo tối đa 20 câu/lần từ:
  - Toàn khóa học, một chương hoặc một bài học.
  - Tài liệu RAG đã upload cho khóa học.
  - Chủ đề nhập tay.
- Cho phép chọn và lưu câu hỏi AI sinh vào ngân hàng.

**Bài kiểm tra:**

- Tạo quiz với tên, thời lượng, số câu dễ/trung bình/khó, trạng thái và lịch mở.
- Tự bốc ngẫu nhiên câu hỏi và xáo trộn thứ tự câu/đáp án.
- Preview đề quiz.
- Xem danh sách lượt làm, học sinh, điểm và thời điểm hoàn thành.
- Lưu trữ quiz nhưng giữ lịch sử và điểm.

## ADM-12. Kho học liệu khóa học

**Chức năng đã có:**

- Xem các khóa học và số học liệu đã gắn.
- Quản lý thư viện học liệu có thể tái sử dụng; tìm theo tên/file và lọc theo loại.
- Upload file tối đa 50 MB hoặc thêm URL ngoài.
- Phân loại PDF, slide, video, website, code, ảnh, tài liệu và loại khác.
- Gắn học liệu có sẵn vào một khóa học.
- Giới hạn học liệu cho một lớp cụ thể hoặc gắn với một bài học.
- Cấu hình thời điểm mở, trạng thái và điều kiện mở theo bài học.
- Gỡ học liệu khỏi khóa học mà vẫn giữ file gốc.
- Lưu trữ file gốc và toàn bộ lượt gắn nếu là chủ sở hữu/Admin.
- Quét và đồng bộ học liệu từ cấu trúc file bài học/bài tập cũ bằng tác vụ queue.
- Gửi thông báo khi học liệu được xuất bản.

## ADM-13. Kho tài liệu chung giữa giáo viên

**Chức năng đã có:**

- Xem mọi tài liệu riêng tư hoặc chia sẻ trong phạm vi Admin.
- Tìm theo tiêu đề/tên file/mô tả; lọc theo phần mở rộng, thư mục, của tôi hoặc được chia sẻ.
- Upload tối đa 10 file/lần, mỗi file tối đa 20 MB.
- Hỗ trợ PDF, Word, Excel, PowerPoint, HTML, TXT, CSV, ZIP và ảnh.
- Phân quyền tài liệu `private` hoặc `teachers`.
- Chỉnh tiêu đề, mô tả, thư mục và phạm vi chia sẻ.
- Preview các định dạng được hỗ trợ và tải file từ kho R2.
- Xóa file vật lý và bản ghi tài liệu.

## ADM-14. Huấn luyện AI và chatbot RAG

**Huấn luyện AI:**

- Upload PDF tối đa 10 MB cho một khóa học hoặc cho phạm vi toàn cục.
- Chỉ Admin được upload tài liệu toàn cục; Teacher chỉ upload cho khóa mình quản lý.
- Xử lý PDF bằng queue: trích xuất văn bản/OCR, chia chunk, tạo Gemini embedding và lưu PostgreSQL/pgvector.
- Chỉ kích hoạt bộ chunk mới sau khi toàn bộ quá trình hoàn tất.
- Xem số chunk, khóa học, người upload và trạng thái tác vụ.
- Xóa tài liệu theo quyền sở hữu; Admin có thể xóa mọi tài liệu.

**Chatbot:**

- Có trên giao diện của mọi tài khoản đã đăng nhập.
- Nhận tối đa 30 tin nhắn/request, dùng 12 tin gần nhất làm ngữ cảnh hội thoại.
- Trả lời câu hỏi theo nội dung khóa học và tài liệu vector mà người dùng được quyền xem.
- Hỗ trợ ngữ cảnh bài học hiện tại và trả nguồn theo tài liệu/trang khi có kết quả RAG.
- Có trợ lý cá nhân cho câu hỏi về lịch, việc hôm nay, bài cần chấm/nộp và thông báo chưa đọc.
- Có rate limit riêng cho chatbot.

## ADM-15. AI hỗ trợ xây dựng và kiểm tra nội dung

- AI lập kế hoạch khóa học theo đối tượng, trình độ hiện tại, đầu ra, số buổi, thời lượng và ghi chú.
- Cho phép áp dụng kế hoạch: tạo chương ở trạng thái published và bài học ở trạng thái draft.
- AI soạn bản nháp bài tập, rubric, quiz hoặc tóm tắt bài học từ nội dung được chọn.
- Kiểm tra chất lượng khóa học và phân loại lỗi high/medium/low:
  - Bài học quá ngắn, thiếu video/tài liệu.
  - Bài tập thiếu rubric.
  - Quiz thiếu câu hỏi theo độ khó hoặc chưa cấu hình số câu.
  - Khóa học thiếu tài liệu chatbot.
  - Bài học thiếu ngữ cảnh.
  - Câu hỏi/đáp án trùng, thiếu hoặc mơ hồ.
- Các tác vụ AI sinh câu hỏi, phân tích lớp, chấm bài, embedding và đồng bộ học liệu được theo dõi bằng trạng thái queued/processing/completed/failed.

## ADM-16. Quản lý hoạt động giảng dạy

- Xem toàn bộ dòng giảng dạy; tìm theo môn, lớp, trung tâm hoặc kỳ.
- Lọc theo trung tâm, kỳ, trạng thái và khoảng ngày.
- Tạo/cập nhật dòng giảng dạy gồm Teacher, môn/khóa, lớp, trung tâm, kỳ, số buổi dự kiến, ngày bắt đầu/kết thúc, trạng thái và ghi chú.
- Tự khớp tên môn/lớp với Course/Classroom hiện có khi có thể.
- Trạng thái: đang dạy, hoàn thành, tạm hoãn, hủy, lưu trữ.
- Import Excel/CSV/TXT tối đa 10 MB; báo số bản ghi mới, cập nhật, không hợp lệ và cột bắt buộc bị thiếu.
- Lưu trữ dòng giảng dạy mà không xóa dữ liệu liên kết.

## ADM-17. Quản lý hợp đồng và thanh toán giáo viên

- Xem toàn bộ hợp đồng; tìm theo số hợp đồng, URL minh chứng, ghi chú hoặc dữ liệu giảng dạy.
- Lọc theo trạng thái và khoảng ngày ký.
- Tạo/cập nhật hợp đồng gồm Teacher, số hợp đồng duy nhất, ngày ký, tổng tiền, tiền đã nhận, trạng thái, ngày nhận, URL minh chứng và ghi chú.
- Gắn một hợp đồng với nhiều dòng giảng dạy của đúng giáo viên.
- Trạng thái: chưa nhận, nhận một phần, đã nhận, hủy, lưu trữ.
- Tự chuẩn hóa số tiền:
  - `received`: tiền đã nhận bằng tổng tiền.
  - `unpaid`: tiền đã nhận bằng 0 và xóa ngày nhận.
  - `cancelled`: tiền đã nhận bằng 0.
- Import Excel/CSV/TXT tối đa 10 MB.
- Tính tổng giá trị, đã nhận và còn lại.
- Ghi audit log khi tạo, cập nhật, import hoặc lưu trữ hợp đồng.

## ADM-18. Dashboard và báo cáo vận hành

**Dashboard vận hành:**

- Lọc theo tháng, năm và Teacher.
- Thống kê số buổi, số môn trong tháng, môn đang dạy, hợp đồng đã nhận, số tiền đã nhận, hợp đồng chờ và tiền còn thiếu.
- Xếp hạng trung tâm theo số lớp/số buổi.
- Hiển thị dòng giảng dạy và hợp đồng nhận tiền gần đây.

**Báo cáo vận hành:**

- Lọc theo trung tâm, kỳ, tháng, năm và Teacher.
- Tổng hợp số môn, môn hoàn thành, số buổi, tổng hợp đồng, đã nhận và còn lại.
- Nhóm kết quả theo trung tâm và theo kỳ.
- Xem chi tiết dòng giảng dạy và hợp đồng.
- Xuất Excel và mở bản in.

## ADM-19. Vận hành hệ thống

**Thông báo:**

- Xem danh sách thông báo cá nhân, lọc chưa đọc, đánh dấu một hoặc tất cả là đã đọc và mở liên kết hành động.

**Audit log:**

- Xem lịch sử thao tác; lọc theo hành động, người dùng và khoảng ngày.
- Xem old values, new values, metadata, IP và user agent tùy bản ghi.
- Xóa một log hoặc xóa hàng loạt theo bộ lọc.

**Theo dõi AI & Queue:**

- Xem 30 tác vụ/trang.
- Thống kê 30 ngày: tổng tác vụ, thất bại, đang chạy, tổng token và chi phí USD ước tính.
- Chủ tác vụ có thể polling trạng thái; Admin có thể xem mọi tác vụ.

**Kiểm tra lưu trữ:**

- Xem disk dùng cho bài nộp và trạng thái cấu hình R2.
- Kiểm thử upload → kiểm tra tồn tại → xóa file test trên R2 hoặc public disk.

**Backup:**

- Tạo backup database thủ công, tùy chọn upload R2.
- Xem lịch sử, bản gần nhất thành công/thất bại, dung lượng và vị trí lưu.
- Tải backup từ local hoặc remote storage.
- Hỗ trợ lịch backup tự động hằng ngày theo cấu hình, số bản local cần giữ và thời gian chạy.

## ADM-20. Công cụ dùng chung

- Công cụ tính điểm Trung cấp nghề: nhập nhiều môn, tín chỉ, điểm hệ số 1, hệ số 2 và điểm thi; tính điểm quá trình, điểm môn 40/60, trung bình hệ 10 và hệ 4 theo tín chỉ.
- Liên kết đến trình soạn thảo code bên ngoài.
- Cờ vua và cờ Caro theo phòng 6 số, truyền nước đi theo thời gian thực qua broadcasting/WebSocket.

---

# 4. NHÓM TEACHER

Teacher có gần đủ công cụ đào tạo như Admin nhưng bị giới hạn theo dữ liệu sở hữu/phụ trách.

## TCH-01. Dashboard giáo viên

- Thống kê số khóa học, lớp, học sinh và bài nộp chờ chấm thuộc phạm vi của mình.
- Biểu đồ bài nộp đã chấm/chờ chấm.
- Hiển thị lịch dạy tuần này, số ca hôm nay và ca dạy tiếp theo.
- Truy cập nhanh vào khóa học, điểm danh và chế độ trình chiếu.
- Danh sách bài nộp mới/chờ chấm ưu tiên.
- Danh sách học sinh có điểm trung bình dưới 5 cần chú ý.
- Gợi ý ưu tiên dựa trên lịch sắp tới, bài chờ chấm và học sinh cần theo dõi.

## TCH-02. Chương trình và khóa học của mình

- Tạo, xem, cập nhật và xóa chương trình do mình sở hữu.
- Xem/tìm/lọc các khóa học có `teacher_id` là tài khoản hiện tại.
- Tạo khóa mẫu hoặc khóa triển khai; chỉ dùng chương trình, khóa mẫu và lớp thuộc phạm vi của mình.
- Gắn khóa triển khai với lớp do mình phụ trách.
- Tạo khóa mới từ khóa mẫu của mình.
- Cập nhật, lưu trữ khóa học của mình.
- Không được xóa vĩnh viễn khóa học; quyền này chỉ dành cho Admin.
- Xem dashboard tiến độ từng khóa học và sử dụng chế độ trình chiếu.

## TCH-03. Soạn và quản lý nội dung

- Thêm/sửa/sắp xếp/lưu trữ chương và bài học trong khóa học của mình.
- Soạn HTML, thêm video/URL ngoài và file đính kèm tối đa 20 MB.
- Điều khiển trạng thái xuất bản và thời điểm mở.
- Tạo/cập nhật/lưu trữ bài tập và quiz.
- Dùng AI lập kế hoạch khóa học, soạn nội dung và kiểm tra chất lượng.
- Khi nội dung được xuất bản, hệ thống thông báo cho học sinh thuộc các lớp hợp lệ.

## TCH-04. Quản lý lớp và học sinh của mình

- Chỉ xem, tạo, cập nhật và lưu trữ lớp có `teacher_id` là mình.
- Khi tạo lớp, hệ thống tự gán Teacher hiện tại; Teacher không được gán lớp cho người khác.
- Gắn các khóa học triển khai do mình sở hữu vào lớp.
- Tạo Student trực tiếp, gỡ khỏi lớp, import bổ sung hoặc thay thế sĩ số có bước xem trước.
- Xem danh sách, bộ lọc cảnh báo, hồ sơ và tiến độ học tập của học sinh trong lớp mình.
- Dùng AI phân tích toàn lớp hoặc từng học sinh.

## TCH-05. Lịch dạy và điểm danh

- Xem calendar chỉ gồm lịch của các lớp mình phụ trách.
- Tạo lịch khi đồng thời quản lý lớp, quản lý khóa học và khóa đã được gắn vào lớp.
- Cập nhật/lưu trữ lịch thuộc phạm vi của mình.
- Sao chép lịch theo ngày và import Excel/CSV cho lớp của mình.
- Điểm danh và quản lý các cột điểm/ghi chú trong khóa học của mình.
- Xuất Excel bảng điểm danh.

## TCH-06. Bài tập, bài nộp và chấm điểm

- Xem bài tập thuộc khóa học mình sở hữu.
- Tạo bài nộp dạng file/tự luận/mixed, rubric, thang điểm và tùy chọn AI.
- Xem hàng đợi chấm của cả lớp, bài thiếu, bài chờ và bài đã chấm.
- Preview/tải file, tải ZIP hàng loạt kèm CSV.
- Chấm điểm, nhận xét và chuyển nhanh sang bài chờ chấm tiếp theo.
- Dùng AI gợi ý chấm và xem lịch sử phân tích; giáo viên vẫn là người lưu điểm chính thức.

## TCH-07. Ngân hàng câu hỏi và quiz

- Tạo ngân hàng câu hỏi cá nhân/dùng chung với các khóa mình quản lý.
- Xem và chỉnh câu hỏi do mình sở hữu hoặc thuộc ngân hàng đã gắn vào khóa học mình sở hữu.
- Thêm, cập nhật, lưu trữ hoặc import câu hỏi.
- AI sinh câu hỏi từ nội dung khóa học, tài liệu RAG hoặc chủ đề.
- Tạo quiz ngẫu nhiên theo cơ cấu độ khó, preview, xem kết quả học sinh và lưu trữ quiz.

## TCH-08. Học liệu, tài liệu chung và huấn luyện AI

- Quản lý thư viện học liệu do mình upload, phát sinh từ khóa học của mình hoặc đã gắn vào khóa học của mình.
- Upload file/link, tái sử dụng học liệu và cấu hình phạm vi lớp/bài học/thời gian mở.
- Chỉ được lưu trữ file gốc do mình sở hữu.
- Quét/đồng bộ học liệu cũ bằng queue.
- Upload, tìm, preview và tải tài liệu chung; chỉnh/xóa tài liệu do mình sở hữu.
- Xem tài liệu Teacher khác chia sẻ ở chế độ `teachers`; không sửa/xóa tài liệu của họ.
- Upload PDF huấn luyện AI cho khóa học mình sở hữu và xóa bộ chunk do mình upload.
- Không được upload tài liệu RAG toàn cục.

## TCH-09. Giảng dạy, hợp đồng và báo cáo cá nhân

- Xem/tìm/lọc/tạo/cập nhật/import/lưu trữ dòng giảng dạy của chính mình.
- Xem/tìm/lọc/tạo/cập nhật/import/lưu trữ hợp đồng thanh toán của chính mình.
- Chỉ gắn hợp đồng với dòng giảng dạy thuộc tài khoản hiện tại.
- Xem dashboard vận hành chỉ trên dữ liệu cá nhân.
- Xem báo cáo theo trung tâm, kỳ, tháng/năm; xuất Excel và in.
- Không thể chuyển dữ liệu nghiệp vụ sang Teacher khác.

## TCH-10. Thông báo, chatbot và công cụ

- Nhận/xem/đánh dấu thông báo cá nhân.
- Chatbot trả lời lịch dạy, buổi dạy tiếp theo, bài chờ chấm và thông báo chưa đọc.
- Chatbot tìm kiến thức trong các khóa học Teacher sở hữu và tài liệu được phép truy cập.
- Theo dõi trạng thái các tác vụ AI do mình khởi tạo.
- Đổi mật khẩu, dùng công cụ tính điểm, trình soạn code ngoài, cờ vua và cờ Caro.

---

# 5. NHÓM STUDENT

Student là vai trò học tập; phạm vi luôn bị giới hạn theo lớp đang hoạt động và nội dung đã xuất bản.

## STD-01. Đăng nhập và tài khoản

- Đăng nhập bằng username được sinh từ họ tên/mã học sinh hoặc email, kèm mật khẩu.
- Chọn ghi nhớ đăng nhập.
- Xem họ tên, email, vai trò, username và mã học sinh.
- Đổi mật khẩu sau khi xác thực mật khẩu hiện tại.
- Bị tự động đăng xuất nếu tài khoản bị Admin khóa hoặc hết hạn.

## STD-02. Dashboard học tập

- Xem tổng số khóa học đang tham gia.
- Xem lịch học tiếp theo và lịch trong tuần.
- Tiếp tục khóa học đang học dở.
- Xem tiến độ tối đa 5 khóa học gần đây theo số bài đã hoàn thành.
- Xem tối đa 5 bài tập sắp đến hạn chưa nộp và tổng số bài còn thiếu.
- Xem tối đa 5 quiz chưa làm và tổng số quiz đang chờ.
- Xem điểm quiz trung bình và biểu đồ 5 kết quả gần đây.
- Xem tối đa 4 điểm/nhận xét bài tập mới nhất.
- Nhận diện lịch có ghi chú thi bằng hiển thị nổi bật.

## STD-03. Danh sách và nội dung khóa học

- Chỉ xem khóa triển khai `published`, đã đến giờ mở, được gắn với lớp `active` mà Student tham gia.
- Tìm/lọc khóa học trong phạm vi được phép.
- Xem cấu trúc chương–bài học, nội dung HTML, video và file đính kèm.
- Chỉ thấy bài học `published` và đã đến `available_from`.
- Xem tiến độ toàn khóa và tiến độ từng chương.
- Đánh dấu bài học hoàn thành; thời điểm hoàn thành được lưu để tính tiến độ.
- Xem bài học tiếp theo/chưa hoàn thành và tiếp tục học.
- Xem giao diện responsive trên desktop/mobile.

## STD-04. Học liệu

- Xem danh sách khóa học có học liệu trong “Kho học liệu”.
- Xem học liệu đã xuất bản, đến thời điểm mở và đúng phạm vi lớp/bài học.
- Tải file học liệu hoặc mở liên kết ngoài.
- Không thể duyệt thư viện file gốc của Teacher/Admin.
- Không thể upload, chỉnh sửa hoặc gỡ học liệu.

## STD-05. Bài tập và nộp bài

- Xem danh sách bài tập được xuất bản thuộc khóa học đang học.
- Xem hướng dẫn, hạn nộp và trạng thái chưa nộp/đã nộp/đã chấm.
- Nộp theo ba hình thức:
  - File.
  - Nội dung tự luận tối thiểu 10 ký tự.
  - File kết hợp tự luận.
- Định dạng mặc định: PDF, DOCX, TXT, MD, HTML/HTM, CSS, JS, PHP và ảnh PNG/JPG/JPEG; giáo viên có thể cấu hình lại.
- Dung lượng mặc định 10 MB; giáo viên có thể cấu hình lại.
- Cập nhật bài nộp; file mới thay file cũ.
- Hủy bài nộp khi chưa được chấm.
- Xem lại bài nộp, tải/preview file theo quyền sở hữu.
- Sau khi có điểm, không thể hủy bài; điểm và feedback được hiển thị ở trang Điểm & nhận xét.

## STD-06. Làm bài kiểm tra

- Chỉ được mở quiz đã xuất bản, đến thời điểm mở và thuộc khóa học hợp lệ.
- Nhận một đề ngẫu nhiên theo số câu dễ/trung bình/khó; thứ tự câu và đáp án được xáo trộn.
- Làm bài trong giao diện toàn màn hình có đếm ngược và tiến độ trả lời.
- Phiên đề được lưu server-side; tải lại trang vẫn giữ đúng đề và thời gian còn lại.
- Khi hết thời gian, hệ thống từ chối kết quả quá hạn ngoài khoảng ân hạn 30 giây.
- Hệ thống chỉ chấm câu hỏi/đáp án thuộc đúng đề đã phát, chống sửa ID từ form.
- Chấm tự động theo thang 10.
- Mỗi Student chỉ có một lượt được ghi nhận cho mỗi quiz; chống gửi lặp bằng lock và unique constraint.
- Xem lại điểm, từng câu đã chọn, câu đúng/sai và đáp án đúng.

## STD-07. Lịch học cá nhân

- Xem calendar chỉ gồm lịch của lớp đang học và khóa học đang được xuất bản.
- Lọc lịch theo khóa học.
- Xem lịch hôm nay, từ hôm nay đến cuối tuần và 8 lịch sắp tới.
- Xem tối đa 5 lịch có ghi chú/thi sắp tới.
- Xem thời gian, lớp, phòng và ghi chú.
- Nhận thông báo khi lịch được thêm, thay đổi hoặc hủy.

## STD-08. Điểm, nhận xét và chuyên cần

- Lọc kết quả theo khóa học.
- Xem toàn bộ bài nộp và trạng thái chờ chấm/đã chấm.
- Xem điểm bài tập theo thang điểm riêng và nhận xét giáo viên.
- Xem kết quả quiz đã hoàn thành.
- Xem điểm trung bình chung quy đổi về hệ 10, điểm trung bình bài tập, quiz, số bài đã chấm/chờ chấm và số feedback.
- Xem 5 feedback gần nhất.
- Xem bảng điểm danh chỉ gồm dữ liệu của bản thân, gồm chuyên cần, điểm và ghi chú.
- Nhận cảnh báo khi có từ 3 lượt vắng/nghỉ trong một khóa học.

## STD-09. Thông báo và trợ lý AI

- Nhận thông báo khi có bài học, học liệu, bài tập, quiz hoặc lịch mới/thay đổi.
- Nhận thông báo khi bài tập được chấm hoặc có cảnh báo chuyên cần.
- Xem thông báo chưa đọc, đánh dấu đã đọc và mở thẳng nội dung liên quan.
- Hỏi chatbot về:
  - Lịch hôm nay, ngày mai, tuần này hoặc buổi học tiếp theo.
  - Bài tập chưa nộp sắp đến hạn.
  - Thông báo chưa đọc.
  - Nội dung bài học/khóa học và tài liệu RAG được phép truy cập.
- Chatbot không truy xuất tài liệu của lớp/khóa học ngoài quyền Student.

## STD-10. Công cụ và giải trí

- Dùng công cụ tính điểm Trung cấp nghề và quy đổi hệ 10/hệ 4.
- Mở trình soạn thảo code bên ngoài.
- Tạo hoặc tham gia phòng cờ vua/cờ Caro bằng mã 6 số.

---

# 6. Luồng thông báo tự động hiện có

| Sự kiện | Người nhận | Nội dung chính |
|---|---|---|
| Xuất bản bài học | Student của khóa | Có bài học mới |
| Xuất bản bài tập | Student của khóa | Có bài tập mới |
| Đổi hạn bài tập | Student của khóa | Hạn nộp mới |
| Xuất bản quiz | Student của khóa | Có bài kiểm tra mới |
| Xuất bản học liệu | Student của khóa/lớp được chọn | Có học liệu mới |
| Thêm/cập nhật/hủy lịch | Student của lớp | Thời gian và trạng thái lịch |
| Giáo viên chấm bài | Student nộp bài | Điểm và tình trạng nhận xét |
| Vắng từ 3 lượt | Student liên quan | Cảnh báo chuyên cần |

Thông báo hiện là thông báo trong ứng dụng. Email/push notification chưa được triển khai.

# 7. Các giới hạn và điểm cần ghi rõ trong đặc tả nghiệm thu

1. **Chưa có public API:** Không có API chính thức cho mobile hoặc tích hợp bên thứ ba.
2. **Chưa có chứng chỉ:** Chưa tự sinh PDF certificate khi hoàn thành khóa học.
3. **Chưa có email/push:** Thông báo chỉ nằm trong ứng dụng.
4. **Chưa multi-tenant:** Chưa tách dữ liệu cho nhiều trung tâm độc lập.
5. **Quiz chỉ một lượt:** Student không thể làm lại quiz sau khi đã có kết quả.
6. **Quiz chưa có chức năng cập nhật cấu hình riêng:** Hiện có tạo, xem/preview, xem kết quả và lưu trữ; chưa có route update quiz.
7. **Container ngân hàng câu hỏi chưa có CRUD đầy đủ:** Có tạo và gắn ngân hàng; CRUD hiện tập trung vào câu hỏi, chưa có route sửa/xóa chính ngân hàng.
8. **Hạn nộp bài đang khóa chủ yếu ở giao diện:** Giao diện khóa nộp/sửa sau hạn, nhưng controller nộp bài chưa kiểm tra `due_date`; cần bổ sung kiểm tra server-side nếu xem đây là quy tắc bắt buộc.
9. **Điều kiện mở học liệu theo bài học chưa kiểm tra hoàn thành:** Trường `unlock_when_lesson_id` và nhãn khóa đã có, nhưng logic hiện chưa đối chiếu bài học đó đã được Student hoàn thành; cần hoàn thiện trước khi nghiệm thu tính năng prerequisite.
10. **Đánh dấu bài học hiện là một chiều:** Endpoint chỉ đánh dấu hoàn thành, chưa có thao tác bỏ đánh dấu.
11. **AI chấm file scan/ảnh:** OCR có trong pipeline PDF huấn luyện RAG, nhưng bộ trích xuất file bài nộp cho AI chấm chưa OCR ảnh/PDF scan.
12. **AI và lưu trữ phụ thuộc hạ tầng:** Cần cấu hình DeepSeek, Gemini, PostgreSQL/pgvector, queue worker, Tesseract/Poppler và R2 tùy tính năng.
13. **Trò chơi phụ thuộc realtime:** Cờ vua/Caro cần broadcasting/Reverb/Pusher; luồng kết thúc phòng cờ vua cần kiểm thử tích hợp thêm.
14. **Chưa có kiểm chứng tải 10.000+ người dùng đồng thời:** Đây vẫn là hạng mục lộ trình.

# 8. Yêu cầu phi chức năng và bảo mật đã thể hiện trong mã nguồn

- Phân quyền hai lớp bằng role middleware và model policies.
- Cô lập dữ liệu theo chủ sở hữu khóa học, lớp, chương trình, hợp đồng và tài liệu.
- Kiểm tra quyền khi tải/preview file; không trả đường dẫn storage trực tiếp.
- Nội dung HTML bài học/bài tập được sanitize trước khi lưu/hiển thị.
- Rate limit riêng cho đăng nhập, chatbot và các luồng sinh AI.
- Quiz lưu đề server-side, kiểm tra option thuộc question, khóa nộp trùng và unique một lượt.
- Tài liệu RAG chỉ tìm chunk đang hoạt động và thuộc phạm vi khóa học người dùng được xem.
- Dữ liệu gửi sang AI được loại email/số điện thoại; phân tích lớp dùng mã học viên thay tên.
- Kết quả JSON của AI được kiểm tra schema trước khi dùng.
- Pipeline embedding dùng cơ chế staging và thay thế atomic để tránh bộ dữ liệu nửa chừng.
- Các hành động quan trọng được audit với người thao tác, IP, user agent và dữ liệu trước/sau.
- Backup hỗ trợ local và remote; kiểm tra storage dùng file test rồi tự xóa.

# 9. Nguồn mã chính đã đối chiếu

- `routes/web.php`: danh sách và phân quyền 158 web routes.
- `app/Http/Controllers/*`: hành vi nghiệp vụ cho từng chức năng.
- `app/Policies/*`: phạm vi dữ liệu và quyền theo vai trò/chủ sở hữu.
- `app/Models/*`: trạng thái, quan hệ và điều kiện nội dung hiển thị.
- `app/Services/*`: AI, RAG, backup, thông báo, lưu trữ và xử lý bài nộp.
- `resources/views/*`: chức năng thực tế được đưa ra giao diện.
- `tests/Feature/*` và `tests/Unit/*`: các trường hợp phân quyền, toàn vẹn quiz/import, AI/RAG, notification, backup storage và vòng đời tài khoản.

