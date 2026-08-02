@extends('layouts.app')

@section('title', 'Quản lý lớp học')

@push('styles')
    @vite('resources/css/pages/catalog-index.css')
@endpush

@section('content')
    @php
        $isAdmin = auth()->user()->role === 'admin';
        $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
    @endphp

    <div class="lms-page catalog-page classes-catalog-page">
        <x-ui.page-header title="Quản lý lớp học">
            <x-slot:meta>
                <span><i class="fa-solid fa-school" aria-hidden="true"></i>Quản lý lớp, học viên và khóa học được phân bổ</span>
            </x-slot:meta>
            <x-slot:actions>
                <x-ui.button icon="fa-plus" data-bs-toggle="modal" data-bs-target="#addClassModal">Tạo lớp học</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <section class="catalog-summary" aria-label="Tổng quan lớp học">
            <article class="catalog-summary-item">
                <span class="catalog-summary-icon tone-blue"><i class="fa-solid fa-school" aria-hidden="true"></i></span>
                <span><strong>{{ $classStats['total'] }}</strong><small>Tổng lớp học</small></span>
            </article>
            <article class="catalog-summary-item">
                <span class="catalog-summary-icon tone-green"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
                <span><strong>{{ $classStats['active'] }}</strong><small>Đang hoạt động</small></span>
            </article>
            <article class="catalog-summary-item">
                <span class="catalog-summary-icon tone-violet"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i></span>
                <span><strong>{{ $classStats['students'] }}</strong><small>Lượt học viên</small></span>
            </article>
            <article class="catalog-summary-item">
                <span class="catalog-summary-icon tone-amber"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
                <span><strong>{{ $classStats['courses'] }}</strong><small>Lượt phân bổ khóa</small></span>
            </article>
        </section>

        <form action="{{ route('classes.index') }}" method="GET" class="catalog-filter-panel class-filter-panel"
            aria-label="Bộ lọc lớp học">
            <div class="catalog-search-field">
                <label for="class-search">Tìm kiếm</label>
                <div class="catalog-input-with-icon">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="class-search" type="search" name="search" class="form-control"
                        placeholder="Tên hoặc mã lớp" value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>

            <div class="catalog-filter-field">
                <label for="class-status">Trạng thái</label>
                <select id="class-status" name="status" class="form-select">
                    <option value="">Đang hoạt động và đã ẩn</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Đang hoạt động</option>
                    <option value="hidden" @selected(($filters['status'] ?? '') === 'hidden')>Đã ẩn</option>
                    <option value="archived" @selected(($filters['status'] ?? '') === 'archived')>Đã lưu trữ</option>
                </select>
            </div>

            @if ($isAdmin)
                <div class="catalog-filter-field">
                    <label for="class-teacher">Giáo viên</label>
                    <select id="class-teacher" name="teacher_id" class="form-select">
                        <option value="">Tất cả giáo viên</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected(($filters['teacher_id'] ?? '') == $teacher->id)>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="catalog-filter-actions">
                <x-ui.button type="submit" icon="fa-filter">Áp dụng</x-ui.button>
                @if ($hasFilters)
                    <x-ui.button :href="route('classes.index')" tone="outline" icon="fa-rotate-left">Đặt lại</x-ui.button>
                @endif
            </div>
        </form>

        <div class="catalog-results-heading">
            <div>
                <h2>Danh sách lớp học</h2>
                <p>{{ $hasFilters ? 'Kết quả theo bộ lọc hiện tại' : 'Các lớp bạn có quyền quản lý' }}</p>
            </div>
            <span class="catalog-count">{{ $classes->total() }} lớp</span>
        </div>

        @if ($classes->isEmpty())
            <div class="catalog-empty-panel">
                <x-ui.empty-state
                    :title="$hasFilters ? 'Không tìm thấy lớp phù hợp' : 'Chưa có lớp học nào'"
                    :description="$hasFilters ? 'Hãy thay đổi từ khóa hoặc bộ lọc để xem thêm kết quả.' : 'Tạo lớp học đầu tiên để bắt đầu phân bổ khóa học và thêm học viên.'"
                    icon="fa-school">
                    @if ($hasFilters)
                        <x-ui.button :href="route('classes.index')" tone="outline" size="sm" icon="fa-rotate-left">Xóa bộ lọc</x-ui.button>
                    @else
                        <x-ui.button size="sm" icon="fa-plus" data-bs-toggle="modal" data-bs-target="#addClassModal">Tạo lớp học</x-ui.button>
                    @endif
                </x-ui.empty-state>
            </div>
        @else
            <div class="catalog-grid class-catalog-grid">
                @foreach ($classes as $class)
                    @php
                        $classStatus = $class->status ?? 'active';
                        $classStatusLabel = [
                            'active' => 'Đang hoạt động',
                            'hidden' => 'Đã ẩn',
                            'archived' => 'Đã lưu trữ',
                        ][$classStatus] ?? 'Đang hoạt động';
                        $teacherName = $class->teacher?->name ?? 'Chưa phân công';
                        $teacherInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($teacherName, 0, 1));
                    @endphp

                    <article class="catalog-card class-catalog-card">
                        <div class="class-card-accent" aria-hidden="true"></div>
                        <div class="catalog-card-body">
                            <div class="class-card-topline">
                                <div class="catalog-card-tags">
                                    <span class="class-code"><i class="fa-solid fa-hashtag" aria-hidden="true"></i>{{ $class->code }}</span>
                                    <span class="catalog-status status-{{ $classStatus }}">
                                        <i class="fa-solid fa-circle" aria-hidden="true"></i>{{ $classStatusLabel }}
                                    </span>
                                </div>

                                <div class="dropdown">
                                    <button class="catalog-icon-button" type="button" data-bs-toggle="dropdown"
                                        aria-expanded="false" aria-label="Mở thao tác cho lớp {{ $class->name }}">
                                        <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end catalog-dropdown">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('classes.progress', $class) }}">
                                                <i class="fa-solid fa-chart-line" aria-hidden="true"></i>Theo dõi lớp
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('classes.students.index', $class) }}">
                                                <i class="fa-solid fa-user-graduate" aria-hidden="true"></i>Quản lý học viên
                                            </a>
                                        </li>
                                        <li>
                                            <button class="dropdown-item edit-class-btn" type="button"
                                                data-id="{{ $class->id }}" data-name="{{ $class->name }}"
                                                data-code="{{ $class->code }}" data-teacher="{{ $class->teacher_id }}"
                                                data-status="{{ $classStatus }}" data-courses="{{ $class->courses->pluck('id')->values() }}"
                                                data-bs-toggle="modal" data-bs-target="#editClassModal">
                                                <i class="fa-solid fa-pen" aria-hidden="true"></i>Sửa lớp học
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('classes.destroy', $class) }}" method="POST"
                                                onsubmit="return confirm('Lưu trữ lớp học này? Học viên, khóa học và dữ liệu học tập vẫn được giữ lại.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i>Lưu trữ lớp học
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="class-card-heading">
                                <span class="class-card-icon" aria-hidden="true"><i class="fa-solid fa-school"></i></span>
                                <div>
                                    <h3 class="catalog-card-title">
                                        <a href="{{ route('classes.students.index', $class) }}">{{ $class->name }}</a>
                                    </h3>
                                    <p>Quản lý danh sách và hoạt động của học viên</p>
                                </div>
                            </div>

                            <div class="catalog-owner">
                                <span class="catalog-avatar" aria-hidden="true">{{ $teacherInitial }}</span>
                                <span><small>Giáo viên phụ trách</small><strong>{{ $teacherName }}</strong></span>
                            </div>

                            <dl class="catalog-metrics class-metrics" aria-label="Thông tin lớp học">
                                <div>
                                    <dt><i class="fa-solid fa-user-graduate" aria-hidden="true"></i>Học viên</dt>
                                    <dd>{{ $class->students_count }}</dd>
                                </div>
                                <div>
                                    <dt><i class="fa-solid fa-book-open" aria-hidden="true"></i>Khóa học</dt>
                                    <dd>{{ $class->courses_count }}</dd>
                                </div>
                            </dl>

                            <div class="class-course-list" aria-label="Khóa học được phân bổ">
                                @forelse ($class->courses->take(2) as $course)
                                    <span><i class="fa-solid fa-book" aria-hidden="true"></i>{{ $course->title }}</span>
                                @empty
                                    <span class="is-empty"><i class="fa-solid fa-link-slash" aria-hidden="true"></i>Chưa phân bổ khóa học</span>
                                @endforelse
                                @if ($class->courses_count > 2)
                                    <strong>+{{ $class->courses_count - 2 }} khóa</strong>
                                @endif
                            </div>

                            <div class="class-card-actions">
                                <a href="{{ route('classes.students.index', $class) }}" class="catalog-card-action is-primary">
                                    <i class="fa-solid fa-user-graduate" aria-hidden="true"></i>Học viên
                                </a>
                                <a href="{{ route('classes.progress', $class) }}" class="catalog-card-action">
                                    <i class="fa-solid fa-chart-line" aria-hidden="true"></i>Theo dõi lớp
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="catalog-pagination-panel">
                <x-ui.pagination :paginator="$classes" item-label="lớp học" />
            </div>
        @endif
    </div>

    <div class="modal fade catalog-modal" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form action="{{ route('classes.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <div>
                        <span class="catalog-modal-icon" aria-hidden="true"><i class="fa-solid fa-school-circle-check"></i></span>
                        <h2 class="modal-title" id="addClassModalLabel">Tạo lớp học mới</h2>
                        <p>Nhập thông tin cơ bản và phân bổ khóa học cho lớp.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="catalog-modal-grid">
                        <div class="catalog-form-group">
                            <label for="add-class-name">Tên lớp học <span aria-hidden="true">*</span></label>
                            <input id="add-class-name" type="text" name="name" class="form-control"
                                placeholder="Ví dụ: Lớp CNTT K24" value="{{ old('name') }}" required>
                        </div>
                        <div class="catalog-form-group">
                            <label for="add-class-code">Mã lớp <span aria-hidden="true">*</span></label>
                            <input id="add-class-code" type="text" name="code" class="form-control"
                                placeholder="Ví dụ: CNTT-K24" value="{{ old('code') }}" required>
                        </div>

                        @if ($isAdmin)
                            <div class="catalog-form-group catalog-form-full">
                                <label for="add-class-teacher">Giáo viên phụ trách <span aria-hidden="true">*</span></label>
                                <select id="add-class-teacher" name="teacher_id" class="form-select" required>
                                    <option value="">Chọn giáo viên</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="catalog-form-group catalog-form-full">
                            <label for="add-class-status">Trạng thái</label>
                            <select id="add-class-status" name="status" class="form-select">
                                <option value="active">Đang hoạt động</option>
                                <option value="hidden">Đã ẩn</option>
                                <option value="archived">Đã lưu trữ</option>
                            </select>
                        </div>

                        <fieldset class="catalog-course-picker catalog-form-full">
                            <legend>Phân bổ khóa học</legend>
                            <p>Có thể chọn nhiều khóa học và thay đổi sau.</p>
                            <div class="catalog-course-options">
                                @forelse ($courses as $course)
                                    <label class="catalog-course-option" for="add-course-{{ $course->id }}">
                                        <input id="add-course-{{ $course->id }}" type="checkbox" name="course_ids[]"
                                            value="{{ $course->id }}" @checked(in_array($course->id, old('course_ids', [])))>
                                        <span><i class="fa-solid fa-book-open" aria-hidden="true"></i>{{ $course->title }}</span>
                                    </label>
                                @empty
                                    <div class="catalog-options-empty">Chưa có khóa học khả dụng.</div>
                                @endforelse
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div class="modal-footer">
                    <x-ui.button tone="outline" data-bs-dismiss="modal">Hủy</x-ui.button>
                    <x-ui.button type="submit" icon="fa-plus">Tạo lớp học</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade catalog-modal" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form id="editClassForm" method="POST" class="modal-content" data-action-template="{{ url('/classes') }}/__CLASS_ID__">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div>
                        <span class="catalog-modal-icon" aria-hidden="true"><i class="fa-solid fa-pen-to-square"></i></span>
                        <h2 class="modal-title" id="editClassModalLabel">Cập nhật lớp học</h2>
                        <p>Điều chỉnh thông tin, trạng thái và khóa học được phân bổ.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="catalog-modal-grid">
                        <div class="catalog-form-group">
                            <label for="edit_name">Tên lớp học <span aria-hidden="true">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="catalog-form-group">
                            <label for="edit_code">Mã lớp <span aria-hidden="true">*</span></label>
                            <input type="text" name="code" id="edit_code" class="form-control" required>
                        </div>

                        @if ($isAdmin)
                            <div class="catalog-form-group catalog-form-full">
                                <label for="edit_teacher">Giáo viên phụ trách <span aria-hidden="true">*</span></label>
                                <select name="teacher_id" id="edit_teacher" class="form-select" required>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="catalog-form-group catalog-form-full">
                            <label for="edit_status">Trạng thái</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Đang hoạt động</option>
                                <option value="hidden">Đã ẩn</option>
                                <option value="archived">Đã lưu trữ</option>
                            </select>
                        </div>

                        <fieldset class="catalog-course-picker catalog-form-full">
                            <legend>Phân bổ khóa học</legend>
                            <p>Bỏ chọn khóa học nếu không còn sử dụng cho lớp này.</p>
                            <div class="catalog-course-options">
                                @forelse ($courses as $course)
                                    <label class="catalog-course-option" for="edit-course-{{ $course->id }}">
                                        <input id="edit-course-{{ $course->id }}" class="edit-course-checkbox" type="checkbox"
                                            name="course_ids[]" value="{{ $course->id }}">
                                        <span><i class="fa-solid fa-book-open" aria-hidden="true"></i>{{ $course->title }}</span>
                                    </label>
                                @empty
                                    <div class="catalog-options-empty">Chưa có khóa học khả dụng.</div>
                                @endforelse
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div class="modal-footer">
                    <x-ui.button tone="outline" data-bs-dismiss="modal">Hủy</x-ui.button>
                    <x-ui.button type="submit" icon="fa-floppy-disk">Lưu thay đổi</x-ui.button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var editForm = document.getElementById('editClassForm');
            if (!editForm) return;

            document.querySelectorAll('.edit-class-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    var classId = this.dataset.id;
                    editForm.action = editForm.dataset.actionTemplate.replace('__CLASS_ID__', classId);
                    document.getElementById('edit_name').value = this.dataset.name || '';
                    document.getElementById('edit_code').value = this.dataset.code || '';
                    document.getElementById('edit_status').value = this.dataset.status || 'active';

                    var teacherSelect = document.getElementById('edit_teacher');
                    if (teacherSelect) teacherSelect.value = this.dataset.teacher || '';

                    var courseIds = JSON.parse(this.dataset.courses || '[]').map(String);
                    editForm.querySelectorAll('.edit-course-checkbox').forEach(function(checkbox) {
                        checkbox.checked = courseIds.includes(checkbox.value);
                    });
                });
            });
        });
    </script>
@endpush
