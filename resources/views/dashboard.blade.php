@extends('layouts.app')

@section('title', 'Bảng điều khiển')

@section('content')
    <div class="container-fluid px-0">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold text-dark">
                    <i class="fas fa-tachometer-alt me-2 text-primary"></i>Chào mừng trở lại, {{ auth()->user()->name }}!
                </h2>
                <p class="text-muted">Dưới đây là tóm tắt tiến độ học tập của bạn.</p>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">

            <div class="col">
                <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-left: 5px solid #0d6efd !important;">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-book-open fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-uppercase fw-semibold text-muted mb-1 small">Khóa học của tôi</h6>
                            <h3 class="mb-0 fw-bold">5</h3>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 py-2">
                        <a href="{{ route('courses.index') }}" class="small text-primary text-decoration-none">Xem chi tiết
                            <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 border-0 shadow-sm overflow-hidden"
                    style="border-left: 5px solid #198754 !important;">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-file-upload fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-uppercase fw-semibold text-muted mb-1 small">Bài tập đã nộp</h6>
                            <h3 class="mb-0 fw-bold">12</h3>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 py-2">
                        <a href="#" class="small text-success text-decoration-none">Xem lịch sử <i
                                class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 border-0 shadow-sm overflow-hidden"
                    style="border-left: 5px solid #ffc107 !important;">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded">
                            <i class="fas fa-bell fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-uppercase fw-semibold text-muted mb-1 small">Thông báo mới</h6>
                            <h3 class="mb-0 fw-bold">3</h3>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 py-2">
                        <a href="#" class="small text-warning text-decoration-none">Xem tất cả <i
                                class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>

        </div>

        <div class="row mt-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Tiếp tục bài học gần đây</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Bạn chưa có bài học nào đang dang dở. Hãy bắt đầu một khóa học mới!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
