@extends('layouts.app')

@section('title', 'Danh sách khóa học')

@section('content')
    <style>
        .transition-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .transition-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        /* Fix chiều cao cố định cho phần mô tả để các card đều nhau */
        .card-description {
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        /* Giới hạn khu vực chứa badge lớp để không đẩy layout quá xa */
        .class-badges-container {
            min-height: 28px;
            max-height: 60px;
            overflow-y: auto;
        }
    </style>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0">Khóa học dành cho bạn</h3>
                <p class="text-muted small">Khám phá các kiến thức mới mỗi ngày</p>
            </div>
            @if (auth()->user()->role === 'teacher' || auth()->user()->role === 'admin')
                <a href="{{ route('courses.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="fas fa-plus me-2"></i>Tạo khóa học
                </a>
            @endif
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
            @foreach ($courses as $course)
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm transition-hover">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-primary bg-opacity-10 p-2 rounded">
                                    <i class="fas fa-layer-group text-primary"></i>
                                </div>
                                @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                    <div class="dropdown">
                                        <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li><a class="dropdown-item" href="{{ route('courses.edit', $course->id) }}"><i
                                                        class="fas fa-edit me-2 text-warning"></i>Sửa</a></li>
                                            <li>
                                                <form action="{{ route('courses.destroy', $course->id) }}" method="POST"
                                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i
                                                            class="fas fa-trash-alt me-2"></i>Xóa</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            <h5 class="card-title fw-bold text-dark">{{ $course->title }}</h5>
                            <p class="card-text text-muted small card-description mb-3">
                                {{ $course->description }}
                            </p>

                            {{-- Thông tin Giáo viên & Lớp đưa vào body để footer chỉ chứa nút/progress --}}
                            <div class="mt-auto">
                                <div class="d-flex align-items-center mb-2 text-muted">
                                    <i class="fas fa-user-tie me-2 small"></i>
                                    <span class="small fw-medium">GV: {{ $course->teacher->name }}</span>
                                </div>

                                <div class="class-badges-container d-flex flex-wrap gap-1 mb-2">
                                    @if ($course->classes->count() > 0)
                                        @foreach ($course->classes as $class)
                                            <span
                                                class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-normal"
                                                style="font-size: 0.65rem;">
                                                <i class="fas fa-users me-1"></i>{{ $class->name }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="badge bg-light text-muted fw-normal" style="font-size: 0.65rem;">Chưa
                                            gán lớp</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-white border-0 pb-4 pt-0">
                            @if (auth()->user()->role === 'student')
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1 small">
                                        <span class="text-muted small">Hoàn thành</span>
                                        <span
                                            class="fw-bold small {{ $course->progress == 100 ? 'text-success' : 'text-primary' }}">{{ $course->progress }}%</span>
                                    </div>
                                    <div class="progress" style="height: 5px; border-radius: 10px;">
                                        <div class="progress-bar {{ $course->progress == 100 ? 'bg-success' : 'bg-primary' }}"
                                            role="progressbar" style="width: {{ $course->progress }}%;"></div>
                                    </div>
                                </div>
                            @endif

                            <a href="{{ route('courses.show', $course->id) }}"
                                class="btn btn-outline-primary w-100 rounded-pill fw-bold py-2">
                                Vào học ngay
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
