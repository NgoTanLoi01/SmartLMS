@extends('layouts.app')

@section('title', 'Quản lý bài tập')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 px-2">
            <div>
                <h3 class="fw-bold mb-0 text-dark">Danh sách bài tập</h3>
                <p class="text-muted mb-0 small">Quản lý các yêu cầu thực hành và nộp bài</p>
            </div>

            @if (auth()->user()->role === 'admin' || auth()->user()->role === 'teacher')
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#addAssignmentModal">
                    <i class="fas fa-plus me-1"></i> Tạo bài tập mới
                </button>
            @endif
        </div>

        <div class="row g-4">
            @forelse($assignments as $assignment)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 rounded-3 hover-shadow transition-all">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2 fw-bold small">
                                    <i class="fas fa-book me-1"></i> {{ $assignment->course->title }}
                                </div>

                                @if (auth()->user()->role === 'student')
                                    @php $submission = $assignment->submissions->first(); @endphp
                                    @if ($submission)
                                        <div class="badge bg-success rounded-pill px-2 py-1 small">
                                            <i class="fas fa-check"></i> Đã nộp
                                        </div>
                                    @else
                                        <div class="badge bg-warning text-dark rounded-pill px-2 py-1 small">
                                            <i class="fas fa-clock"></i> Chưa nộp
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <h5 class="fw-bold text-dark mb-2">{{ $assignment->title }}</h5>

                            {{-- Sử dụng strip_tags để loại bỏ thẻ HTML trước khi cắt chuỗi, tránh lỗi hiển thị trên Card --}}
                            <p class="text-muted small mb-3 flex-grow-1">
                                {{ Str::limit(strip_tags($assignment->instructions), 100) }}
                            </p>

                            <div class="bg-light rounded p-3 mb-3">
                                <div class="d-flex align-items-center mb-2 small">
                                    <i class="fas fa-calendar-alt text-danger me-2"></i>
                                    <span class="fw-bold">Hạn nộp:</span>
                                    <span class="ms-1">{{ $assignment->due_date->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="d-flex align-items-center small">
                                    <i class="fas fa-file-upload text-muted me-2"></i>
                                    <span class="text-muted">Định dạng: {{ $assignment->allowed_extensions }}</span>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                @if (auth()->user()->role === 'student')
                                    {{-- Truyền instructions đã loại bỏ tag vào data-attribute để Modal JS xử lý an toàn --}}
                                    <button class="btn btn-outline-primary rounded-pill fw-bold" data-bs-toggle="modal"
                                        data-bs-target="#submitAssignmentModal" data-id="{{ $assignment->id }}"
                                        data-title="{{ $assignment->title }}"
                                        data-instructions="{{ strip_tags($assignment->instructions) }}"
                                        data-extensions="{{ $assignment->allowed_extensions }}">
                                        {{ $submission ? 'Nộp lại bài làm' : 'Bắt đầu làm bài' }}
                                    </button>

                                    @if ($submission && $submission->grade)
                                        <div
                                            class="mt-2 p-2 bg-success bg-opacity-10 rounded border border-success border-opacity-25 text-center">
                                            <span class="small fw-bold text-success">Điểm:
                                                {{ $submission->grade }}/10</span>
                                        </div>
                                    @endif
                                @else
                                    <a href="#" class="btn btn-light rounded-pill fw-bold border">
                                        <i class="fas fa-eye me-1"></i> Xem bài nộp
                                        ({{ $assignment->submissions->count() }})
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="fas fa-tasks fa-3x text-muted opacity-50 mb-3"></i>
                    <h5 class="text-muted fw-bold">Chưa có bài tập nào được giao</h5>
                </div>
            @endforelse
        </div>
    </div>

    <div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('assignments.store') }}" method="POST" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">Giao bài tập mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold small text-muted">Tiêu đề bài tập</label>
                            <input type="text" name="title" class="form-control bg-light border-0 py-2"
                                placeholder="VD: Thực hành Migration" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Chọn khóa học</label>
                            <select name="course_id" class="form-select bg-light border-0 py-2" required>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">Yêu cầu bài tập</label>
                            <textarea name="instructions" class="form-control bg-light border-0" rows="4"
                                placeholder="Viết mô tả chi tiết yêu cầu nộp bài..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Hạn chót (Deadline)</label>
                            <input type="datetime-local" name="due_date" class="form-control bg-light border-0 py-2"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Trạng thái</label>
                            <select name="status" class="form-select bg-light border-0 py-2">
                                <option value="published">Xuất bản ngay</option>
                                <option value="draft">Lưu nháp</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Định dạng cho phép (Cách nhau dấu
                                phẩy)</label>
                            <input type="text" name="allowed_extensions" class="form-control bg-light border-0 py-2"
                                value="pdf,docx,zip,png,jpg,jpeg,html">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Dung lượng tối đa (KB)</label>
                            <input type="number" name="max_file_size" class="form-control bg-light border-0 py-2"
                                value="5120">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Tạo bài tập</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="submitAssignmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="submitForm" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="submitModalTitle">Nộp bài tập</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="fw-bold small mb-1">Hướng dẫn nộp bài:</h6>
                        <p class="small text-muted mb-0" id="submitInstructions"></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Chọn file từ máy tính</label>
                        <input type="file" name="file" class="form-control bg-light border-0 py-2" required>
                        <div class="form-text small">Chỉ chấp nhận file định dạng yêu cầu, tối đa 5MB.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">Gửi bài làm</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .hover-shadow:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
        }

        .transition-all {
            transition: all 0.3s ease;
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const submitModal = document.getElementById('submitAssignmentModal');
            let allowedExtensions = [];

            if (submitModal) {
                submitModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const title = button.getAttribute('data-title');
                    const instructions = button.getAttribute('data-instructions');
                    const extensions = button.getAttribute('data-extensions') ||
                        'pdf,docx,zip,png,jpg,jpeg';

                    allowedExtensions = extensions.split(',').map(e => e.trim().toLowerCase());

                    document.getElementById('submitModalTitle').innerText = 'Nộp bài: ' + title;
                    document.getElementById('submitInstructions').innerText = instructions;
                    document.getElementById('submitForm').action = `/assignments/${id}/submit`;

                    // Reset lỗi cũ mỗi lần mở modal
                    clearError();
                });
            }

            const form = document.getElementById('submitForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    clearError();

                    const fileInput = form.querySelector('input[type="file"]');
                    const file = fileInput.files[0];

                    if (!file) return; // để Laravel validate required

                    const maxSize = 5 * 1024 * 1024; // 5MB
                    const ext = file.name.split('.').pop().toLowerCase();

                    if (!allowedExtensions.includes(ext)) {
                        e.preventDefault();
                        showError(
                            `Định dạng file <strong>.${ext}</strong> không được chấp nhận. Vui lòng chọn file có định dạng: <strong>${allowedExtensions.join(', ')}</strong>.`
                        );
                        return;
                    }

                    if (file.size > maxSize) {
                        e.preventDefault();
                        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                        showError(
                            `File của bạn nặng <strong>${sizeMB} MB</strong>, vượt quá giới hạn cho phép <strong>5 MB</strong>. Vui lòng nén file hoặc chọn file nhỏ hơn.`
                        );
                        return;
                    }
                });
            }

            function showError(message) {
                let alert = document.getElementById('submitFileError');
                if (!alert) {
                    alert = document.createElement('div');
                    alert.id = 'submitFileError';
                    alert.className =
                        'alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 py-2 px-3 small';
                    alert.setAttribute('role', 'alert');
                    const fileInput = form.querySelector('.mb-3');
                    fileInput.parentNode.insertBefore(alert, fileInput);
                }
                alert.innerHTML = `
            <i class="fas fa-exclamation-circle mt-1 flex-shrink-0"></i>
            <div>${message}</div>
            <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="alert"></button>
        `;
            }

            function clearError() {
                const alert = document.getElementById('submitFileError');
                if (alert) alert.remove();
            }
        });
    </script>
@endpush
