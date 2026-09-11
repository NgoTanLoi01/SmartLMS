@extends('layouts.app')

@section('title', 'Tạo khóa học mới')

@section('content')
    <div class="container py-4 legacy-form-page">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('courses.index') }}" class="text-decoration-none">Khóa
                                học</a></li>
                        <li class="breadcrumb-item active">Tạo mới</li>
                    </ol>
                </nav>

                <div class="card border-0 shadow-sm legacy-form-card">
                    <div class="card-header bg-white py-3 border-0 legacy-form-card__head">
                        <h4 class="fw-bold mb-0 text-primary">
                            <i class="fa-solid fa-plus-circle me-2"></i>Tạo khóa học mới
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

                            @if (auth()->user()->isAdmin())
                                <div class="mb-4">
                                    <label for="teacher_id" class="form-label fw-bold">Giáo viên phụ trách <span
                                            class="text-danger">*</span></label>
                                    <select name="teacher_id" id="teacher_id"
                                        class="form-select @error('teacher_id') is-invalid @enderror" required>
                                        <option value="">-- Chọn giáo viên --</option>
                                        @foreach ($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>
                                                {{ $teacher->name }} · {{ $teacher->email }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('teacher_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Khóa học sẽ thuộc quyền quản lý chuyên môn của giáo viên được chọn.</div>
                                    @if ($teachers->isEmpty())
                                        <div class="alert alert-warning mt-2 mb-0">Chưa có tài khoản giáo viên để giao phụ trách khóa học.</div>
                                    @endif
                                </div>
                            @endif

                            <div class="mb-4">
                                <label for="learning_program_id" class="form-label fw-bold">Chương trình học</label>
                                <select name="learning_program_id" id="learning_program_id" class="form-select">
                                    <option value="">Chưa gắn chương trình</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}" @selected(old('learning_program_id', request('learning_program_id')) == $program->id)>
                                            {{ $program->name }} ({{ $program->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Dùng để gom các khóa học cùng chương trình/môn học chuẩn.</div>
                            </div>

                            <div class="mb-4">
                                <label for="course_type" class="form-label fw-bold">Loại khóa học</label>
                                <select name="course_type" id="course_type" class="form-select" required>
                                    <option value="delivery" @selected(old('course_type', request('course_type', 'delivery')) === 'delivery')>Khóa đang dạy - triển khai cho lớp thật</option>
                                    <option value="template" @selected(old('course_type', request('course_type')) === 'template')>Khóa mẫu - dùng để nhân bản nội dung</option>
                                </select>
                                <div class="form-text">Khóa mẫu sẽ không hiển thị cho học viên và không tính tiến độ/học viên.</div>
                            </div>

                            <div class="mb-4">
                                <label for="template_course_id" class="form-label fw-bold">Khóa học nguồn</label>
                                <select name="template_course_id" id="template_course_id" class="form-select">
                                    <option value="">Tạo khóa học trống</option>
                                    @foreach ($sourceCourses as $sourceCourse)
                                        <option value="{{ $sourceCourse->id }}" @selected(old('template_course_id', request('template_course_id')) == $sourceCourse->id)>
                                            {{ $sourceCourse->isTemplate() ? '[Khóa mẫu]' : '[Khóa hiện có]' }}
                                            {{ $sourceCourse->title }}
                                            @if ($sourceCourse->learningProgram)
                                                - {{ $sourceCourse->learningProgram->name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Có thể dùng khóa mẫu hoặc khóa học hiện có làm nguồn. Hệ thống sao chép chương, bài học, bài tập, bài kiểm tra và liên kết ngân hàng câu hỏi; không sao chép học viên, tiến độ, bài nộp hoặc điểm danh.</div>
                            </div>

                            <div class="mb-4" id="classSelectionGroup">
                                <label class="form-label fw-bold">Lớp áp dụng</label>
                                @if ($availableClasses->isEmpty())
                                    <div class="alert alert-light border mb-0">
                                        Chưa có lớp học nào để gắn khóa học. Bạn vẫn có thể tạo khóa và gắn lớp sau.
                                    </div>
                                @else
                                    <div class="border rounded-3 p-3 bg-light">
                                        <div class="row g-2">
                                            @foreach ($availableClasses as $classroom)
                                                <div class="col-12 col-md-6">
                                                    <label class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" name="class_ids[]"
                                                            value="{{ $classroom->id }}" @checked(in_array($classroom->id, old('class_ids', [])))>
                                                        <span class="form-check-label">
                                                            {{ $classroom->name }}
                                                            @if (auth()->user()->role === 'admin' && $classroom->teacher)
                                                                <span class="text-muted small d-block">{{ $classroom->teacher->name }}</span>
                                                            @endif
                                                        </span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="form-text">Chỉ áp dụng cho khóa đang dạy. Khóa mẫu sẽ không gắn lớp.</div>
                                @endif
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label for="status" class="form-label fw-bold">Trạng thái xuất bản</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="published" @selected(old('status', 'published') === 'published')>Đã xuất bản - Học viên có thể xem</option>
                                        <option value="draft" @selected(old('status') === 'draft')>Bản nháp</option>
                                        <option value="hidden" @selected(old('status') === 'hidden')>Tạm ẩn khỏi học viên</option>
                                        <option value="archived" @selected(old('status') === 'archived')>Đã lưu trữ</option>
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
                                    <i class="fa-solid fa-save me-2"></i>Lưu khóa học
                                </button>
                                <a href="{{ route('courses.index') }}" class="btn btn-light px-4 rounded-pill">Hủy bỏ</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const courseType = document.getElementById('course_type');
            const classSelection = document.getElementById('classSelectionGroup');

            function toggleClassSelection() {
                if (!courseType || !classSelection) return;
                classSelection.style.display = courseType.value === 'template' ? 'none' : '';
            }

            toggleClassSelection();
            courseType?.addEventListener('change', toggleClassSelection);
        });
    </script>
@endsection
