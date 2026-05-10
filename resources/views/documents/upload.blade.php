@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">

                {{-- 1. Tiêu đề trang với Icon AI --}}
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary text-white rounded-3 p-3 me-3 shadow-sm">
                        <i class="fas fa-brain fa-2x"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">Huấn luyện Trí tuệ nhân tạo</h3>
                        <p class="text-muted mb-0">Cung cấp tài liệu để AI học kiến thức và hỗ trợ học sinh</p>
                    </div>
                </div>

                {{-- 2. Khối Cảnh báo & Lưu ý quan trọng --}}
                <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                    <div class="alert bg-warning-subtle border-0 border-start border-warning border-4 rounded-0 mb-0 p-4">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-warning-emphasis">Lưu ý quan trọng trước khi Train!</h6>
                                <p class="mb-2 text-dark-emphasis">
                                    Nếu file quá lớn (ví dụ sách trên 100 trang): Thầy <strong>nên chia nhỏ file
                                        PDF</strong> thành từng chương trước khi upload. Việc này giúp AI tìm kiếm chính xác
                                    hơn và tránh bị "nghẽn" API Google khi tạo Vector.
                                </p>
                                <ul class="small text-dark-emphasis mb-0 ps-3">
                                    <li>Chỉ sử dụng file PDF <strong>dạng văn bản</strong> (không dùng ảnh quét/scan).</li>
                                    <li>Hệ thống sử dụng Gemini 3 Flash để tạo Vector 3072 chiều.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. Form Upload tài liệu --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4 text-dark">
                            <i class="fas fa-cloud-upload-alt me-2 text-primary"></i>Tải lên tài liệu mới
                        </h5>

                        @if (session('success'))
                            <div class="alert alert-success border-0 shadow-sm mb-4">
                                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger border-0 shadow-sm mb-4">
                                <ul class="mb-0 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data"
                            id="uploadForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Khóa học áp
                                        dụng</label>
                                    <select name="course_id" class="form-select bg-light border-0 py-2" required>
                                        <option value="">-- Chọn khóa học để huấn luyện --</option>

                                        {{-- Lặp qua danh sách khóa học từ CSDL --}}
                                        @foreach ($courses as $course)
                                            <option value="{{ $course->id }}">{{ $course->title }}</option>
                                        @endforeach

                                        <option value="0">-- Dùng chung toàn hệ thống --</option>
                                    </select>
                                    @error('course_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Tài liệu (Định dạng
                                        PDF)</label>
                                    <input class="form-control bg-light border-0 py-2" type="file" name="file"
                                        accept="application/pdf" required>
                                    @error('file')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary fw-bold py-3 w-100 shadow-sm"
                                        id="btnUpload">
                                        <i class="fas fa-rocket me-2"></i> BẮT ĐẦU TRÍCH XUẤT KIẾN THỨC
                                    </button>
                                </div>
                            </div>
                        </form>

                        {{-- 4. Thanh tiến trình (Ẩn mặc định) --}}
                        <div id="progressContainer" class="mt-4 d-none">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small fw-bold text-primary" id="progressStatus">Đang khởi tạo cấu trúc
                                    AI...</span>
                                <span class="small fw-bold text-primary" id="progressPercent">0%</span>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div id="progressBar"
                                    class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                    role="progressbar" style="width: 0%"></div>
                            </div>
                            <div class="alert alert-info border-0 mt-3 small">
                                <i class="fas fa-info-circle me-1"></i>
                                Hệ thống đang phân tích ngữ cảnh và tạo tọa độ Vector. Vui lòng giữ kết nối internet và
                                không đóng trình duyệt.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. Danh sách tài liệu hiện có --}}
                <div class="card shadow-sm border-0 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-database me-2 text-primary"></i>Kho tri thức đã huấn luyện
                        </h6>
                        <span class="badge bg-light text-dark border">{{ $documents->count() }} tài liệu</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase text-muted">
                                        <th class="ps-4 py-3">Tên tài liệu</th>
                                        <th>Độ chi tiết</th>
                                        <th>Ngày nạp</th>
                                        <th class="text-end pe-4">Quản lý</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($documents as $doc)
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-file-pdf text-danger me-3 fa-lg"></i>
                                                    <span class="fw-medium text-dark">{{ $doc->document_name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary px-3 rounded-pill">
                                                    {{ $doc->total_chunks }} Vectors
                                                </span>
                                            </td>
                                            <td class="text-muted small">
                                                {{ $doc->created_at->format('d/m/Y') }}
                                            </td>
                                            <td class="text-end pe-4">
                                                <form action="{{ route('documents.destroy', $doc->document_name) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-link text-danger p-0 text-decoration-none"
                                                        onclick="return confirm('Bạn có chắc muốn xóa kiến thức này? AI sẽ không thể trả lời các nội dung liên quan nữa.')">
                                                        <i class="fas fa-trash-alt"></i> Xóa
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted small">
                                                <i class="fas fa-folder-open fa-3x d-block mb-3 opacity-25"></i>
                                                Chưa có dữ liệu tri thức nào được nạp.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Script xử lý thanh tiến trình thông minh --}}
    <script>
        document.getElementById('uploadForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnUpload');
            const container = document.getElementById('progressContainer');
            const bar = document.getElementById('progressBar');
            const percent = document.getElementById('progressPercent');
            const status = document.getElementById('progressStatus');

            // Hiệu ứng nút và hiện Progress Bar
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Hệ thống đang xử lý...';
            container.classList.remove('d-none');

            // Danh sách thông điệp mô phỏng quá trình xử lý thực tế
            const statusMessages = [
                "Đang đọc nội dung file PDF...",
                "Đang trích xuất văn bản thô...",
                "Đang chia nhỏ dữ liệu thành các đoạn ngữ cảnh...",
                "Đang kết nối tới Google Gemini API...",
                "Đang khởi tạo Vector 3072 chiều...",
                "Đang mã hóa kiến thức vào không gian đa chiều...",
                "Đang lưu trữ dữ liệu vào PostgreSQL Vector DB...",
                "Đang hoàn tất quá trình huấn luyện..."
            ];

            let width = 0;
            let messageIndex = 0;

            // Hàm chạy thanh tiến trình mô phỏng
            const interval = setInterval(function() {
                if (width >= 94) {
                    // Dừng lại ở 94% để chờ phản hồi thực từ Backend
                    clearInterval(interval);
                    status.innerText = "Đang kiểm tra và phản hồi...";
                } else {
                    // Chạy nhanh lúc đầu, chậm dần về sau để tạo cảm giác thực
                    let increment = 0;
                    if (width < 40) increment = 1.5;
                    else if (width < 70) increment = 0.4;
                    else if (width < 90) increment = 0.1;
                    else increment = 0.05;

                    width += increment;
                    bar.style.width = width + '%';
                    percent.innerText = Math.round(width) + '%';

                    // Thay đổi thông báo trạng thái dựa trên % hoàn thành
                    let msgStep = Math.floor(width / (100 / statusMessages.length));
                    if (statusMessages[msgStep] && status.innerText !== statusMessages[msgStep]) {
                        status.innerText = statusMessages[msgStep];
                    }
                }
            }, 100);
        });
    </script>
@endsection
