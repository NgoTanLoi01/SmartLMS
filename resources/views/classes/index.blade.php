@extends('layouts.app')

@section('title', 'Quản lý lớp học')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0 text-dark">Danh sách lớp học</h3>
                <p class="text-muted mb-0 small">Quản lý các lớp học và học viên</p>
            </div>

            @if (in_array(auth()->user()->role, ['admin', 'teacher']))
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#addClassModal">
                    <i class="fas fa-plus me-1"></i> Tạo lớp mới
                </button>
            @endif
        </div>

        <div class="row g-4">
            @forelse($classes as $class)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 rounded-3 hover-shadow transition-all">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 fw-bold">
                                    {{ $class->code }}
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="text-muted small me-3">
                                        <i class="fas fa-users me-1"></i> {{ $class->students_count }} HS
                                    </div>

                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle border-0 text-muted shadow-none"
                                            style="width: 30px; height: 30px;" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-1">
                                            <li>
                                                <a class="dropdown-item edit-class-btn" href="#"
                                                    data-id="{{ $class->id }}" data-name="{{ $class->name }}"
                                                    data-code="{{ $class->code }}" data-teacher="{{ $class->teacher_id }}"
                                                    data-courses="{{ $class->courses->pluck('id') }}" data-bs-toggle="modal"
                                                    data-bs-target="#editClassModal">
                                                    <i class="fas fa-edit me-2 text-warning"></i> Sửa lớp học
                                                </a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <form action="{{ route('classes.destroy', $class->id) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"
                                                        onclick="return confirm('Toàn bộ học sinh sẽ bị gỡ khỏi lớp. Bạn có chắc muốn xóa lớp này?')">
                                                        <i class="fas fa-trash-alt me-2"></i> Xóa lớp học
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">{{ $class->name }}</h5>
                            <p class="text-muted small mb-3">
                                <i class="fas fa-chalkboard-teacher me-1"></i> GV: {{ $class->teacher->name ?? 'N/A' }}
                            </p>

                            <a href="{{ route('classes.students.index', $class->id) }}"
                                class="btn btn-light w-100 rounded-pill text-primary fw-medium border">
                                <i class="fas fa-user-graduate me-1"></i> Quản lý học sinh
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="fas fa-school fa-3x text-muted opacity-50 mb-3"></i>
                    <h5 class="text-muted fw-bold">Chưa có lớp học nào</h5>
                </div>
            @endforelse
        </div>
    </div>

    {{-- MODAL TẠO LỚP --}}
    @if (in_array(auth()->user()->role, ['admin', 'teacher']))
        <div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('classes.store') }}" method="POST" class="modal-content border-0 shadow">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark">Tạo lớp học mới</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body py-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Tên lớp học</label>
                            <input type="text" name="name" class="form-control bg-light border-0 py-2" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Mã lớp</label>
                            <input type="text" name="code" class="form-control bg-light border-0 py-2" required>
                        </div>

                        @if (auth()->user()->role === 'admin')
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Phân công Giáo viên</label>
                                <select name="teacher_id" class="form-select bg-light border-0 py-2" required>
                                    <option value="">-- Chọn Giáo viên --</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="mb-0">
                            <label class="form-label fw-bold small text-muted">
                                Phân bổ Khóa học
                                @if (auth()->user()->role === 'teacher')
                                    <span class="badge bg-info-subtle text-info fw-normal">(Khóa học của bạn)</span>
                                @endif
                            </label>
                            <div class="border rounded bg-light p-2" style="max-height: 150px; overflow-y: auto;">
                                @forelse($courses as $course)
                                    <div class="form-check">
                                        <input class="form-check-input course-checkbox" type="checkbox" name="course_ids[]"
                                            value="{{ $course->id }}" id="add_course_{{ $course->id }}">
                                        <label class="form-check-label small" for="add_course_{{ $course->id }}">
                                            {{ $course->title }}
                                        </label>
                                    </div>
                                @empty
                                    <span class="small text-muted fst-italic">
                                        {{ auth()->user()->role === 'admin' ? 'Chưa có khóa học nào.' : 'Bạn chưa có khóa học nào để phân bổ.' }}
                                    </span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Tạo lớp ngay</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL SỬA LỚP --}}
    <div class="modal fade" id="editClassModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editClassForm" method="POST" class="modal-content border-0 shadow">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">Sửa thông tin lớp học</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Tên lớp học</label>
                        <input type="text" name="name" id="edit_name" class="form-control bg-light border-0 py-2"
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Mã lớp</label>
                        <input type="text" name="code" id="edit_code" class="form-control bg-light border-0 py-2"
                            required>
                    </div>

                    @if (auth()->user()->role === 'admin')
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Phân công Giáo viên</label>
                            <select name="teacher_id" id="edit_teacher" class="form-select bg-light border-0 py-2"
                                required>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted">
                            Phân bổ Khóa học
                            @if (auth()->user()->role === 'teacher')
                                <span class="badge bg-info-subtle text-info fw-normal">(Khóa học của bạn)</span>
                            @endif
                        </label>
                        <div class="border rounded bg-light p-2" style="max-height: 150px; overflow-y: auto;">
                            @forelse($courses as $course)
                                <div class="form-check">
                                    <input class="form-check-input course-checkbox" type="checkbox" name="course_ids[]"
                                        value="{{ $course->id }}" id="edit_course_{{ $course->id }}">
                                    <label class="form-check-label small" for="edit_course_{{ $course->id }}">
                                        {{ $course->title }}
                                    </label>
                                </div>
                            @empty
                                <span class="small text-muted fst-italic">Không có khóa học khả dụng.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning text-dark fw-medium rounded-pill px-4 shadow-sm">Lưu
                        thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .hover-shadow:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
        }

        .transition-all {
            transition: all 0.3s ease;
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.edit-class-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const classId = this.getAttribute('data-id');
                    document.getElementById('editClassForm').action = `/classes/${classId}`;
                    document.getElementById('edit_name').value = this.getAttribute('data-name');
                    document.getElementById('edit_code').value = this.getAttribute('data-code');

                    const teacherSelect = document.getElementById('edit_teacher');
                    if (teacherSelect) {
                        teacherSelect.value = this.getAttribute('data-teacher');
                    }

                    const courseIds = JSON.parse(this.getAttribute('data-courses') || '[]');
                    document.querySelectorAll('#editClassForm .course-checkbox').forEach(cb => cb
                        .checked = false);
                    courseIds.forEach(id => {
                        const checkbox = document.querySelector(
                            `#editClassForm .course-checkbox[value="${id}"]`);
                        if (checkbox) checkbox.checked = true;
                    });
                });
            });
        });
    </script>
@endpush
