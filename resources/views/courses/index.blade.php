@extends('layouts.app')

@section('title', 'Danh sách khóa học')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0">Khóa học dành cho bạn</h3>
                <p class="text-muted small">Khám phá các kiến thức mới mỗi ngày</p>
            </div>
            @if (auth()->user()->role === 'teacher' || auth()->user()->role === 'admin')
                <a href="{{ route('courses.create') }}" class="btn btn-primary rounded-pill px-4">
                    <i class="fas fa-plus me-2"></i>Tạo khóa học
                </a>
            @endif
        </div>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            @foreach ($courses as $course)
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm transition-hover">
                        <div class="card-body">
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
                            <h5 class="card-title fw-bold">{{ $course->title }}</h5>
                            <p class="card-text text-muted small">{{ Str::limit($course->description, 100) }}</p>
                        </div>
                        <div class="card-footer bg-white border-0 pb-4">
                            <div class="d-flex align-items-center mb-3 text-muted">
                                <i class="fas fa-user-tie me-2 small"></i>
                                <span class="small">GV: {{ $course->teacher->name }}</span>
                            </div>
                            <a href="{{ route('courses.show', $course->id) }}"
                                class="btn btn-outline-primary w-100 rounded-pill fw-bold">
                                Vào học ngay
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
