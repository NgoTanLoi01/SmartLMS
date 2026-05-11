@extends('layouts.app')

@section('title', 'Danh sách khóa học')

@section('content')
    <style>
        /* Nền phía sau màu xám rất nhạt giống mẫu */
        .course-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
        }

        .course-card {
            background: #ffffff;
            border: 1px solid #eef0f2;
            /* Viền mỏng nhẹ như ảnh mẫu */
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.08) !important;
            border-color: var(--primary-navy);
        }

        /* Biểu tượng khóa học - placeholder thay cho ảnh */
        .course-icon-box {
            height: 120px;
            background: linear-gradient(135deg, #f1f4f8 0%, #e2e8f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--primary-navy);
        }

        .card-title-custom {
            font-size: 1rem;
            font-weight: 700;
            color: #1a202c;
            line-height: 1.4;
            margin-bottom: 8px;
            height: 44px;
            /* Fix 2 dòng */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .teacher-info {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 15px;
        }

        /* Thanh tiến trình siêu mỏng giống mẫu */
        .progress-thin {
            height: 4px;
            background-color: #edf2f7;
            border-radius: 10px;
        }

        .btn-enter {
            background-color: var(--primary-navy);
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 10px;
            transition: 0.2s;
        }

        .btn-enter:hover {
            background-color: var(--primary-navy);
            color: white;
        }

        /* Tinh chỉnh viền cho card khóa học */
        .course-card {
            background: #ffffff;
            /* Viền màu xám cực nhẹ (#eef0f2), độ dày 1px */
            border: 1px solid #eef0f2 !important;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
        }

        /* Hiệu ứng khi hover: Viền đậm hơn một chút và đổi màu nhẹ */
        .course-card:hover {
            transform: translateY(-8px);
            border-color: rgba(26, 58, 90, 0.2) !important;
            /* Màu Navy nhạt khi hover */
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.06) !important;
        }

        /* Thêm border mỏng cho phần ảnh placeholder phía trên để tạo khối */
        .course-icon-box {
            height: 140px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--primary-navy);
            border-bottom: 1px solid #f1f3f5;
            /* Đường kẻ phân cách nhẹ giữa ảnh và nội dung */
        }
    </style>

    <div class="container-fluid course-container">
        <div class="d-flex justify-content-between align-items-center mb-5 px-2">
            <div>
                <h3 class="fw-bold mb-1 text-navy">Khóa học của tôi</h3>
                <p class="text-muted small mb-0">Học tập và phát triển kỹ năng mỗi ngày cùng SmartLMS</p>
            </div>
            @if (auth()->user()->role === 'teacher' || auth()->user()->role === 'admin')
                <a href="{{ route('courses.create') }}" class="btn btn-navy rounded-pill px-4 shadow-sm">
                    <i class="fas fa-plus me-2 small"></i>Tạo khóa học mới
                </a>
            @endif
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
            @foreach ($courses as $course)
                <div class="col">
                    <div class="card h-100 course-card border-0 shadow-sm">

                        <div class="course-icon-box position-relative">
                            <i class="fas fa-laptop-code opacity-25"></i>

                            @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                <div class="position-absolute top-0 end-0 m-2">
                                    <div class="dropdown">
                                        <button class="btn btn-white btn-sm shadow-sm rounded-circle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false"
                                            style="width: 32px; height: 32px; padding: 0; background: white;">
                                            <i class="fas fa-ellipsis-v text-muted"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2"
                                            style="border-radius: 12px;">
                                            <li>
                                                <a class="dropdown-item small rounded-2 py-2"
                                                    href="{{ route('courses.edit', $course->id) }}">
                                                    <i class="fas fa-edit me-2 text-warning"></i> Sửa khóa học
                                                </a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider opacity-50">
                                            </li>
                                            <li>
                                                <form action="{{ route('courses.destroy', $course->id) }}" method="POST"
                                                    onsubmit="return confirm('Thầy có chắc chắn muốn xóa khóa học này không?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="dropdown-item text-danger small rounded-2 py-2">
                                                        <i class="fas fa-trash-alt me-2"></i> Xóa khóa học
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="card-body p-4 d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-600 py-1 px-2"
                                    style="font-size: 0.7rem;">
                                    <i class="fas fa-users me-1"></i>{{ $course->classes->first()->name ?? 'Tự do' }}
                                </span>
                            </div>

                            <h5 class="card-title-custom mb-2">{{ $course->title }}</h5>
                            <p class="text-muted mb-3"
                                style="font-size: 0.85rem; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-height: 1.5;">
                                {{ $course->description ?? 'Chưa có mô tả chi tiết cho khóa học này.' }}
                            </p>

                            <div class="course-meta-info mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="teacher-avatar me-2">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($course->teacher->name) }}&background=3e80f9&color=fff"
                                            class="rounded-circle" style="width: 24px; height: 24px;" alt="GV">
                                    </div>
                                    <span class="small text-dark fw-medium">{{ $course->teacher->name }}</span>
                                </div>

                                <div class="d-flex gap-3 text-muted" style="font-size: 0.75rem;">
                                    <span><i class="fas fa-book-open me-1"></i> {{ $course->lessons_count ?? '0' }} bài
                                        học</span>
                                    <span><i class="fas fa-user-graduate me-1"></i> {{ $course->students_count ?? '0' }} học
                                        sinh</span>
                                </div>
                            </div>

                            @if (auth()->user()->role === 'student')
                                <div class="mt-auto mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted" style="font-size: 0.7rem;">Tiến độ</span>
                                        <span class="fw-bold text-primary"
                                            style="font-size: 0.7rem;">{{ $course->progress }}%</span>
                                    </div>
                                    <div class="progress progress-thin">
                                        <div class="progress-bar bg-primary" role="progressbar"
                                            style="width: {{ $course->progress }}%"></div>
                                    </div>
                                </div>
                            @else
                                <div class="mt-auto mb-3 text-muted" style="font-size: 0.7rem;">
                                    <i class="far fa-clock me-1"></i> Cập nhật: {{ $course->updated_at->diffForHumans() }}
                                </div>
                            @endif

                            <a href="{{ route('courses.show', $course->id) }}"
                                class="btn btn-enter w-100 shadow-sm border-0">
                                Vào học ngay <i class="fas fa-chevron-right ms-2 small"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
