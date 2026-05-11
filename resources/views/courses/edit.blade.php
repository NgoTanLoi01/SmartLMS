@extends('layouts.app')

@section('title', 'Chỉnh sửa khóa học')

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('courses.index') }}" class="text-decoration-none">Khóa
                                học</a></li>
                        <li class="breadcrumb-item active">Chỉnh sửa</li>
                    </ol>
                </nav>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-0">
                        <h4 class="fw-bold mb-0">
                            <i class="fas fa-edit me-2"></i>Chỉnh sửa khóa học
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('courses.update', $course->id) }}" method="POST">
                            @csrf
                            @method('PUT') <div class="mb-3">
                                <label for="title" class="form-label fw-bold">Tên khóa học <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="title" id="title"
                                    class="form-control @error('title') is-invalid @enderror"
                                    value="{{ old('title', $course->title) }}" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label fw-bold">Mô tả khóa học <span
                                        class="text-danger">*</span></label>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                    rows="8" required>{{ old('description', $course->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex align-items-center gap-2 pt-3">
                                <button type="submit" class="btn btn-warning px-4 rounded-pill fw-bold text-white">
                                    <i class="fas fa-sync-alt me-2"></i>Cập nhật thay đổi
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
