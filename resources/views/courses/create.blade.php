@extends('layouts.app')

@section('title', 'Tạo khóa học mới')

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('courses.index') }}" class="text-decoration-none">Khóa
                                học</a></li>
                        <li class="breadcrumb-item active">Tạo mới</li>
                    </ol>
                </nav>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-0">
                        <h4 class="fw-bold mb-0 text-primary">
                            <i class="fas fa-plus-circle me-2"></i>Tạo khóa học mới
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('courses.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="title" class="form-label fw-bold">Tên khóa học <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="title" id="title"
                                    class="form-control @error('title') is-invalid @enderror"
                                    placeholder="Ví dụ: Lập trình Laravel chuyên sâu" value="{{ old('title') }}" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label fw-bold">Mô tả khóa học <span
                                        class="text-danger">*</span></label>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                    rows="8" placeholder="Viết mô tả chi tiết về nội dung khóa học..." required>{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label for="status" class="form-label fw-bold">Trạng thái xuất bản</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="published" @selected(old('status', 'published') === 'published')>Published - Học sinh có thể thấy</option>
                                        <option value="draft" @selected(old('status') === 'draft')>Draft - Bản nháp</option>
                                        <option value="hidden" @selected(old('status') === 'hidden')>Hidden - Ẩn khỏi học sinh</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="available_from" class="form-label fw-bold">Mở từ thời điểm</label>
                                    <input type="datetime-local" name="available_from" id="available_from"
                                        class="form-control" value="{{ old('available_from') }}">
                                    <div class="form-text">Bỏ trống nếu muốn mở ngay khi published.</div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2 pt-3">
                                <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold">
                                    <i class="fas fa-save me-2"></i>Lưu khóa học
                                </button>
                                <a href="{{ route('courses.index') }}" class="btn btn-light px-4 rounded-pill">Hủy bỏ</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
