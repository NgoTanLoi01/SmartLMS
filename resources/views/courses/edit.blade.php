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
                                        <option value="published" @selected(old('status', $course->status) === 'published')>Đã xuất bản - Học viên có thể xem</option>
                                        <option value="draft" @selected(old('status', $course->status) === 'draft')>Bản nháp</option>
                                        <option value="hidden" @selected(old('status', $course->status) === 'hidden')>Tạm ẩn khỏi học viên</option>
                                        <option value="archived" @selected(old('status', $course->status) === 'archived')>Đã lưu trữ</option>
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

                @if ($course->isTemplate())
                    <div class="alert alert-primary border-0 shadow-sm mt-4 d-flex align-items-center gap-3">
                        <i class="fa-solid fa-code-branch fs-3"></i>
                        <div>
                            <div class="fw-bold">Phiên bản khóa mẫu v{{ $course->template_version ?? 1 }}</div>
                            <div class="small">Phiên bản tự tăng khi chương, bài học, bài tập, bài kiểm tra hoặc câu hỏi của khóa mẫu thay đổi.</div>
                        </div>
                    </div>
                @elseif ($course->sourceTemplate)
                    @php
                        $syncState = $course->template_sync_state ?? [];
                        $sourceVersion = (int) ($course->sourceTemplate->template_version ?? 1);
                        $sourceSectionVersions = $course->sourceTemplate->template_section_versions ?? [];
                    @endphp
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-header bg-white border-0 px-4 pt-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div>
                                    <h4 class="fw-bold mb-1"><i class="fa-solid fa-arrows-rotate me-2 text-primary"></i>Đồng bộ từ khóa mẫu</h4>
                                    <div class="text-muted">Nguồn: <strong>{{ $course->sourceTemplate->title }}</strong> · phiên bản hiện tại v{{ $sourceVersion }}</div>
                                </div>
                                <span class="badge {{ (int) $course->synced_template_version === $sourceVersion ? 'text-bg-success' : 'text-bg-warning' }} fs-6">
                                    {{ (int) $course->synced_template_version === $sourceVersion ? 'Đã đồng bộ đầy đủ' : 'Có thay đổi mới' }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('courses.sync-template', $course) }}" method="POST">
                                @csrf
                                <p class="text-muted small">Chỉ các bản ghi có nguồn từ khóa mẫu được cập nhật tại chỗ. Nội dung tạo riêng trong khóa này được giữ nguyên; mục đã bỏ khỏi mẫu sẽ chuyển sang lưu trữ để bảo toàn dữ liệu học tập.</p>
                                <div class="row g-3 mb-4">
                                    @foreach (\App\Services\CourseCloningService::SECTION_LABELS as $section => $label)
                                        @php
                                            $sectionVersion = (int) ($syncState[$section] ?? 0);
                                            $sourceSectionVersion = (int) ($sourceSectionVersions[$section] ?? $sourceVersion);
                                        @endphp
                                        <div class="col-md-6">
                                            <label class="border rounded-3 p-3 w-100 d-flex align-items-center gap-3">
                                                <input class="form-check-input m-0" type="checkbox" name="sections[]" value="{{ $section }}" @checked($sectionVersion < $sourceSectionVersion)>
                                                <span class="flex-grow-1">
                                                    <strong class="d-block">{{ $label }}</strong>
                                                    <small class="{{ $sectionVersion === $sourceSectionVersion ? 'text-success' : 'text-warning' }}">
                                                        {{ $sectionVersion === $sourceSectionVersion ? "Đã ở v{$sourceSectionVersion}" : "Đang ở v{$sectionVersion} · có bản mới v{$sourceSectionVersion}" }}
                                                    </small>
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold" onclick="return confirm('Đồng bộ các nhóm nội dung đã chọn từ khóa mẫu?')">
                                    <i class="fa-solid fa-arrows-rotate me-2"></i>Đồng bộ phần đã chọn
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
