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
                            <i class="fa-solid fa-edit me-2"></i>Chỉnh sửa khóa học
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

                            <div class="mb-4">
                                <label for="learning_program_id" class="form-label fw-bold">Chương trình học</label>
                                <select name="learning_program_id" id="learning_program_id" class="form-select">
                                    <option value="">Chưa gắn chương trình</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}" @selected(old('learning_program_id', $course->learning_program_id) == $program->id)>
                                            {{ $program->name }} ({{ $program->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Gắn khóa học này vào một chương trình/môn học chuẩn.</div>
                            </div>

                            <div class="mb-4">
                                <label for="course_type" class="form-label fw-bold">Loại khóa học</label>
                                <select name="course_type" id="course_type" class="form-select" required>
                                    <option value="delivery" @selected(old('course_type', $course->course_type) === 'delivery')>Khóa đang dạy - triển khai cho lớp thật</option>
                                    <option value="template" @selected(old('course_type', $course->course_type) === 'template')>Khóa mẫu - dùng để nhân bản nội dung</option>
                                </select>
                                <div class="form-text">Khóa mẫu sẽ được tách riêng khỏi danh sách khóa đang dạy.</div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label for="status" class="form-label fw-bold">Trạng thái xuất bản</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="published" @selected(old('status', $course->status) === 'published')>Published - Học sinh có thể thấy</option>
                                        <option value="draft" @selected(old('status', $course->status) === 'draft')>Draft - Bản nháp</option>
                                        <option value="hidden" @selected(old('status', $course->status) === 'hidden')>Hidden - Ẩn khỏi học sinh</option>
                                        <option value="archived" @selected(old('status', $course->status) === 'archived')>Archived - Lưu trữ</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="available_from" class="form-label fw-bold">Mở từ thời điểm</label>
                                    <input type="datetime-local" name="available_from" id="available_from"
                                        class="form-control"
                                        value="{{ old('available_from', $course->available_from?->format('Y-m-d\TH:i')) }}">
                                    <div class="form-text">Bỏ trống nếu muốn mở ngay khi published.</div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2 pt-3">
                                <button type="submit" class="btn btn-warning px-4 rounded-pill fw-bold text-white">
                                    <i class="fa-solid fa-rotate me-2"></i>Cập nhật thay đổi
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
