@extends('layouts.app')

@section('title', 'Xem trước đề thi: ' . $quiz->title)

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="{{ route('courses.show', $quiz->course_id) }}"
                    class="btn btn-outline-primary rounded-pill mb-2 px-4 shadow-sm align-items-center back-to-course-btn">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại khóa học
                </a>
                <h3 class="fw-bold mb-0" style="color: #6f42c1;">
                    <i class="fas fa-magic me-2"></i>Xem trước: {{ $quiz->title }}
                </h3>
                <p class="text-muted mb-0 mt-1">
                    Thời gian làm bài: <strong>{{ $quiz->time_limit }} phút</strong> |
                    Tổng số câu: <strong>{{ $examQuestions->count() }} câu</strong>
                </p>
            </div>
            <div>
                <button onclick="window.location.reload()" class="btn btn-success fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fas fa-sync-alt me-2"></i>Tải lại đề ngẫu nhiên khác
                </button>
            </div>
        </div>

        <div class="row g-4">
            {{-- THÔNG TIN CẤU HÌNH ĐỀ THI --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                        <h5 class="mb-0 fw-bold" style="color: #6f42c1;">
                            <i class="fas fa-cogs me-2"></i>Cấu trúc đề thi
                        </h5>
                    </div>
                    <div class="card-body bg-light">
                        <div class="alert alert-info border-0 small shadow-sm mb-4">
                            <i class="fas fa-info-circle me-1"></i> Hệ thống đã chuyển sang chế độ <strong>Ngân hàng câu
                                hỏi</strong>. Mỗi học sinh sẽ nhận được một đề thi khác nhau dựa trên cấu trúc bên dưới.
                        </div>

                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <span class="text-muted fw-bold">Tổng số câu Dễ:</span>
                            <span class="badge bg-success fs-6">{{ $quiz->easy_count }} câu</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <span class="text-muted fw-bold">Tổng số câu Trung bình:</span>
                            <span class="badge bg-warning text-dark fs-6">{{ $quiz->medium_count }} câu</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4 border-bottom pb-2">
                            <span class="text-muted fw-bold">Tổng số câu Khó:</span>
                            <span class="badge bg-danger fs-6">{{ $quiz->hard_count }} câu</span>
                        </div>

                        <a href="{{ route('questions.index', ['course_id' => $quiz->course_id]) }}"
                            class="btn text-white w-100 rounded-pill shadow-sm fw-bold py-2"
                            style="background-color: #6f42c1;">
                            <i class="fas fa-database me-2"></i> Quản lý Ngân hàng câu hỏi
                        </a>
                    </div>
                </div>
            </div>

            {{-- BẢN XEM TRƯỚC ĐỀ THI (PREVIEW) --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-eye me-2 text-muted"></i>Bản xem trước đề thi
                        </h5>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                            {{ $examQuestions->count() }} câu hỏi
                        </span>
                    </div>
                    <div class="card-body p-4">
                        @php $labels = ['A', 'B', 'C', 'D']; @endphp

                        @forelse ($examQuestions as $index => $question)
                            <div class="border rounded p-4 mb-4 bg-white shadow-sm position-relative">
                                <div class="position-absolute top-0 end-0 m-3">
                                    @if ($question->difficulty == 'easy')
                                        <span class="badge bg-success">Dễ</span>
                                    @elseif($question->difficulty == 'medium')
                                        <span class="badge bg-warning text-dark">Trung bình</span>
                                    @else
                                        <span class="badge bg-danger">Khó</span>
                                    @endif
                                </div>

                                <h6 class="fw-bold mb-3 pe-5 text-dark lh-base">
                                    <span style="color: #6f42c1; font-size: 1.1rem;">Câu {{ $index + 1 }}:</span>
                                    {{ $question->question_text }}
                                </h6>

                                <div class="row g-3">
                                    @foreach ($question->options as $key => $option)
                                        <div class="col-md-6">
                                            <div
                                                class="p-3 border rounded small d-flex align-items-center h-100 {{ $option->is_correct ? 'bg-success bg-opacity-10 border-success text-success fw-bold shadow-sm' : 'bg-light text-muted' }}">
                                                <span
                                                    class="me-2 fs-6 {{ $option->is_correct ? 'text-success' : 'text-secondary fw-bold' }}">{{ $labels[$loop->index] ?? '' }}.</span>
                                                <span class="flex-grow-1">{{ $option->option_text }}</span>
                                                @if ($option->is_correct)
                                                    <i class="fas fa-check-circle ms-2 fs-5"></i>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 opacity-75"></i>
                                <h5 class="text-dark fw-bold">Không đủ câu hỏi trong Ngân hàng!</h5>
                                <p class="text-muted mb-4">Kho câu hỏi hiện tại không đủ số lượng để tạo đề theo cấu trúc
                                    bạn yêu cầu.<br>Vui lòng vào Ngân hàng câu hỏi để bổ sung thêm.</p>
                                <a href="{{ route('questions.index', ['course_id' => $quiz->course_id]) }}"
                                    class="btn btn-primary rounded-pill px-4 shadow-sm">
                                    <i class="fas fa-plus me-2"></i>Thêm câu hỏi ngay
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
