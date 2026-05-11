@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="row">
            {{-- Cột trái: Cấu hình thông số --}}
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-magic me-2"></i>Cấu hình AI sinh câu hỏi</h6>
                    </div>
                    <div class="card-body p-4">
                        <form id="aiGenForm">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase">Môn học / Khóa học</label>
                                <select class="form-select bg-light border-0" id="course_id" required>
                                    <option value="">-- Chọn khóa học đã có tài liệu --</option>

                                    {{-- Đổ dữ liệu danh sách khóa học từ Database --}}
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                                    @endforeach

                                </select>
                                <div class="form-text small">AI sẽ dựa vào tài liệu PDF Thầy / Cô đã nạp cho khóa học này.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase">Chủ đề chi tiết</label>
                                <input type="text" class="form-control bg-light border-0" id="topic"
                                    placeholder="Ví dụ: Middleware, Grid System..." required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-uppercase">Độ khó</label>
                                    <select class="form-select bg-light border-0" id="difficulty">
                                        <option value="Dễ">Dễ</option>
                                        <option value="Trung bình" selected>Trung bình</option>
                                        <option value="Khó">Khó</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-uppercase">Số lượng</label>
                                    <input type="number" class="form-control bg-light border-0" id="quantity"
                                        value="5" min="1" max="20">
                                </div>
                            </div>

                            <hr class="my-4 opacity-50">

                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" id="btnGenerate">
                                <i class="fas fa-microchip me-2"></i> BẮT ĐẦU SINH CÂU HỎI
                            </button>
                        </form>
                    </div>
                </div>

                <div class="alert bg-success-subtle border-0 border-start border-success border-4 rounded-0 mb-0 p-4">
                    <div class="d-flex">
                        <div>
                            <h6 class="fw-bold text-success
                            "><i class="fas fa-lightbulb me-2"></i>Mẹo nhỏ:</h6>
                            <p class="small text-muted mb-0">
                                Chủ đề càng chi tiết, AI sẽ tìm kiếm trong tài liệu càng chính xác. Thầy / Cô có thể chọn lọc lại
                                câu hỏi trước khi lưu chính thức vào ngân hàng.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cột phải: Kết quả hiển thị --}}
            <div class="col-lg-8">
                <div id="aiResultArea">
                    {{-- Trạng thái trống --}}
                    <div class="card border-0 shadow-sm text-center py-5" id="emptyState">
                        <div class="card-body">
                            <img src="https://cdn-icons-png.flaticon.com/512/3344/3344177.png"
                                style="width: 120px; opacity: 0.5" class="mb-3">
                            <h5 class="text-muted">Chưa có câu hỏi nào được sinh ra</h5>
                            <p class="text-muted small">Hãy chọn thông số và nhấn "Bắt đầu sinh câu hỏi" để AI làm việc.</p>
                        </div>
                    </div>

                    {{-- Loading (Ẩn mặc định) --}}
                    <div class="text-center d-none my-5" id="loadingState">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        </div>
                        <h5 class="fw-bold">AI đang "đọc" tài liệu và soạn đề...</h5>
                        <p class="text-muted">Quá trình này có thể mất 10-20 giây tùy số lượng câu hỏi.</p>
                    </div>

                    {{-- Danh sách câu hỏi AI sinh ra (Ẩn mặc định) --}}
                    <div id="questionPreviewList" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0 text-primary">Kết quả sinh từ AI</h5>
                            <button class="btn btn-success fw-bold shadow-sm" id="btnSaveAll">
                                <i class="fas fa-save me-2"></i> LƯU TẤT CẢ VÀO NGÂN HÀNG
                            </button>
                        </div>

                        <div id="questionsContainer">
                            {{-- Các câu hỏi sẽ được Append vào đây bằng JS --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Template mẫu cho 1 câu hỏi (Dùng để clone bằng JS) --}}
    <template id="questionTemplate">
        <div class="card shadow-sm border-0 mb-3 question-card border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h6 class="fw-bold text-dark mb-3 q-title">Câu hỏi 1: [Nội dung câu hỏi]</h6>
                    <button class="btn btn-sm btn-outline-danger border-0 btn-remove-q"><i
                            class="fas fa-times"></i></button>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6 small">A. <span class="opt-a">...</span></div>
                    <div class="col-md-6 small">B. <span class="opt-b">...</span></div>
                    <div class="col-md-6 small text-success fw-bold">C. <span class="opt-c">... (Đúng)</span></div>
                    <div class="col-md-6 small">D. <span class="opt-d">...</span></div>
                </div>
                <div class="bg-light p-2 rounded small border-start border-success border-3">
                    <i class="fas fa-check-circle text-success me-2"></i><strong>Giải thích:</strong> <span
                        class="explanation">...</span>
                </div>
            </div>
        </div>
    </template>

    <script>
        // URL API từ Laravel (sử dụng route name)
        const processUrl = "{{ route('quizzes.ai_generate.process') }}";
        const saveUrl = "{{ route('quizzes.ai_generate.save') }}";
        const csrfToken = "{{ csrf_token() }}";

        // Biến toàn cục để lưu trữ tạm các câu hỏi AI vừa sinh ra
        let generatedQuestions = [];

        // ==========================================
        // 1. XỬ LÝ KHI NHẤN NÚT "BẮT ĐẦU SINH CÂU HỎI"
        // ==========================================
        document.getElementById('aiGenForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Cập nhật UI: Ẩn trạng thái rỗng, hiện trạng thái Đang tải
            document.getElementById('emptyState').classList.add('d-none');
            document.getElementById('questionPreviewList').classList.add('d-none');
            document.getElementById('loadingState').classList.remove('d-none');
            document.getElementById('btnGenerate').disabled = true;

            // Thu thập dữ liệu từ Form
            const payload = {
                course_id: document.getElementById('course_id').value,
                topic: document.getElementById('topic').value,
                difficulty: document.getElementById('difficulty').value,
                quantity: document.getElementById('quantity').value
            };

            try {
                // Gọi API bằng Fetch
                const response = await fetch(processUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                // Xử lý nếu Backend báo lỗi (VD: Chưa có tài liệu PDF)
                if (!response.ok) {
                    alert("Lỗi: " + (data.error || data.message || "Không thể sinh câu hỏi."));
                    resetUI();
                    return;
                }

                // Tùy thuộc vào DeepSeek trả về mảng trực tiếp hay bọc trong object
                generatedQuestions = data.questions ? data.questions : data;

                if (!Array.isArray(generatedQuestions) || generatedQuestions.length === 0) {
                    alert("AI không thể tạo được câu hỏi từ nội dung này. Thầy / Cô thử đổi chủ đề nhé!");
                    resetUI();
                    return;
                }

                // Hiển thị câu hỏi ra màn hình
                renderQuestions();

                // Cập nhật UI: Ẩn loading, hiện danh sách
                document.getElementById('loadingState').classList.add('d-none');
                document.getElementById('questionPreviewList').classList.remove('d-none');

            } catch (error) {
                console.error(error);
                alert("Lỗi kết nối đến máy chủ AI. Thầy / Cô vui lòng thử lại!");
                resetUI();
            } finally {
                document.getElementById('btnGenerate').disabled = false;
            }
        });

        // ==========================================
        // 2. HÀM HIỂN THỊ CÂU HỎI RA GIAO DIỆN
        // ==========================================
        function renderQuestions() {
            const container = document.getElementById('questionsContainer');
            container.innerHTML = ''; // Xóa sạch dữ liệu cũ
            const template = document.getElementById('questionTemplate').content;

            generatedQuestions.forEach((q, index) => {
                const clone = template.cloneNode(true);

                // Điền nội dung câu hỏi
                clone.querySelector('.q-title').innerText = `Câu hỏi ${index + 1}: ${q.question}`;

                // Điền 4 đáp án và tô màu đáp án đúng
                const optionElements = [
                    clone.querySelector('.opt-a'),
                    clone.querySelector('.opt-b'),
                    clone.querySelector('.opt-c'),
                    clone.querySelector('.opt-d')
                ];

                q.options.forEach((optText, optIndex) => {
                    let textNode = optText;
                    if (optIndex === q.correct_index) {
                        textNode = `<span class="text-success fw-bold">${optText} (Đúng)</span>`;
                        // Reset class cho thẻ div cha
                        optionElements[optIndex].parentElement.classList.add('text-success', 'fw-bold');
                    }
                    optionElements[optIndex].innerHTML = textNode;
                });

                // Điền phần giải thích
                clone.querySelector('.explanation').innerText = q.explanation || "Không có giải thích chi tiết.";

                // Xử lý nút Xóa (Nút dấu X) - Dành cho giáo viên bỏ câu không ưng ý
                clone.querySelector('.btn-remove-q').addEventListener('click', function(e) {
                    e.target.closest('.question-card').remove();
                    generatedQuestions.splice(index, 1);
                    if (generatedQuestions.length === 0) resetUI();
                });

                container.appendChild(clone);
            });
        }

        // ==========================================
        // 3. XỬ LÝ KHI NHẤN "LƯU TẤT CẢ VÀO NGÂN HÀNG"
        // ==========================================
        document.getElementById('btnSaveAll').addEventListener('click', async function() {
            if (generatedQuestions.length === 0) return;

            const btnSave = this;
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Đang lưu...';
            btnSave.disabled = true;

            const payload = {
                course_id: document.getElementById('course_id').value,
                difficulty: document.getElementById('difficulty').value,
                questions: generatedQuestions
            };

            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok) {
                    alert("Thành công! " + data.success);
                    // Chuyển hướng giáo viên về trang Ngân hàng câu hỏi
                    window.location.href = "{{ route('questions.index') }}";
                } else {
                    alert("Lỗi khi lưu: " + (data.message || "Vui lòng kiểm tra lại."));
                    btnSave.innerHTML = '<i class="fas fa-save me-2"></i> LƯU TẤT CẢ VÀO NGÂN HÀNG';
                    btnSave.disabled = false;
                }
            } catch (error) {
                console.error(error);
                alert("Lỗi kết nối khi lưu dữ liệu.");
                btnSave.innerHTML = '<i class="fas fa-save me-2"></i> LƯU TẤT CẢ VÀO NGÂN HÀNG';
                btnSave.disabled = false;
            }
        });

        // Hàm tiện ích: Đưa UI về trạng thái ban đầu
        function resetUI() {
            document.getElementById('loadingState').classList.add('d-none');
            document.getElementById('questionPreviewList').classList.add('d-none');
            document.getElementById('emptyState').classList.remove('d-none');
            document.getElementById('btnGenerate').disabled = false;
        }
    </script>
@endsection
