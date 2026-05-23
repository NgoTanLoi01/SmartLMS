@extends('layouts.app')

@section('title', 'Quản lý lớp học')

@section('content')
    <style>
        .page-title {
            font-size: 22px;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 4px;
        }

        .page-subtitle {
            font-size: 13.5px;
            color: #64748b;
            margin: 0 0 24px;
        }

        .btn-create {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 9px 18px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
            white-space: nowrap;
        }

        .btn-create:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .btn-create i {
            font-size: 12px;
        }

        /* ── Grid ── */
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(370px, 1fr));
            gap: 20px;
        }

        /* ── Card ── */
        .class-card {
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .class-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 28px rgba(37, 99, 235, 0.08);
            border-color: #bfdbfe;
        }

        .card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .class-code {
            display: inline-block;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 7px;
            letter-spacing: .02em;
        }

        .card-meta {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-count {
            font-size: 12.5px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .student-count i {
            font-size: 11px;
        }

        /* 3-dot menu */
        .menu-btn {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e8edf3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            color: #64748b;
            cursor: pointer;
            transition: background 0.15s;
            padding: 0;
        }

        .menu-btn:hover {
            background: #f1f5f9;
            color: #334155;
        }

        .card-dropdown {
            border: 1px solid #e8edf3 !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.09) !important;
            padding: 6px !important;
            min-width: 170px;
        }

        .card-dropdown .dropdown-item {
            border-radius: 8px;
            font-size: 13.5px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #0f172a;
        }

        .card-dropdown .dropdown-item:hover {
            background: #eff6ff;
            color: #2563eb;
        }

        .card-dropdown .dropdown-item.text-danger {
            color: #dc2626 !important;
        }

        .card-dropdown .dropdown-item.text-danger:hover {
            background: #fef2f2;
        }

        .card-dropdown .dropdown-divider {
            border-color: #f1f5f9;
            margin: 4px 0;
        }

        /* Card body */
        .class-name {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 4px;
            line-height: 1.35;
        }

        .class-teacher {
            font-size: 13px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 0;
        }

        .class-teacher i {
            font-size: 12px;
        }

        /* Manage button */
        .btn-manage {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            background: #f8fafc;
            color: #2563eb;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s, border-color 0.15s;
            margin-top: auto;
        }

        .btn-manage:hover {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #2563eb;
        }

        .btn-manage i {
            font-size: 12px;
        }

        /* Empty */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 2.8rem;
            display: block;
            margin-bottom: 14px;
            opacity: .35;
        }

        .empty-state p {
            font-size: 14px;
            margin: 0;
        }

        /* ── Modals ── */
        .modal-content {
            border: 1px solid #e8edf3;
            border-radius: 14px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 20px 24px 0;
            border: none;
        }

        .modal-title {
            font-size: 17px;
            font-weight: 600;
            color: #0f172a;
        }

        .modal-body {
            padding: 18px 24px;
        }

        .modal-footer {
            padding: 0 24px 20px;
            border: none;
            gap: 8px;
        }

        .form-label-sm {
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
            display: block;
            margin-bottom: 5px;
        }

        .form-ctrl {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: #0f172a;
            background: #fff;
            transition: border-color 0.15s, box-shadow 0.15s;
            appearance: auto;
        }

        .form-ctrl:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
            outline: none;
        }

        .form-group {
            margin-bottom: 14px;
        }

        /* Course checkbox list */
        .course-list {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            padding: 10px 12px;
            max-height: 150px;
            overflow-y: auto;
        }

        .course-list::-webkit-scrollbar {
            width: 4px;
        }

        .course-list::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 4px;
        }

        .course-check-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 5px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .course-check-item:last-child {
            border-bottom: none;
        }

        .course-check-item input {
            cursor: pointer;
            accent-color: #2563eb;
            flex-shrink: 0;
        }

        .course-check-item label {
            font-size: 13.5px;
            color: #334155;
            cursor: pointer;
            margin: 0;
        }

        .role-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 11px;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 6px;
            margin-left: 6px;
            vertical-align: middle;
        }

        .btn-modal-cancel {
            background: #f1f5f9;
            color: #334155;
            border: none;
            border-radius: 10px;
            padding: 9px 20px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-modal-cancel:hover {
            background: #e2e8f0;
        }

        .btn-modal-submit {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 9px 22px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-modal-submit:hover {
            background: #1d4ed8;
        }
    </style>

    {{-- Header --}}
    <div
        style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:24px;">
        <div>
            <h1 class="page-title">Danh sách lớp học</h1>
            <p class="page-subtitle">Quản lý các lớp học và học viên</p>
        </div>
        @if (in_array(auth()->user()->role, ['admin', 'teacher']))
            <button class="btn-create" data-bs-toggle="modal" data-bs-target="#addClassModal">
                <i class="fas fa-plus"></i> Tạo lớp mới
            </button>
        @endif
    </div>

    {{-- Grid --}}
    @if ($classes->isEmpty())
        <div class="empty-state">
            <i class="fas fa-school"></i>
            <p>Chưa có lớp học nào. Hãy tạo lớp đầu tiên!</p>
        </div>
    @else
        <div class="classes-grid">
            @foreach ($classes as $class)
                <div class="class-card">
                    {{-- Top row --}}
                    <div class="card-top">
                        <span class="class-code">{{ $class->code }}</span>
                        <div class="card-meta">
                            <span class="student-count">
                                <i class="fas fa-users"></i> {{ $class->students_count }} HS
                            </span>
                            <div class="dropdown">
                                <button class="menu-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end card-dropdown">
                                    <li>
                                        <a class="dropdown-item edit-class-btn" href="#" data-id="{{ $class->id }}"
                                            data-name="{{ $class->name }}" data-code="{{ $class->code }}"
                                            data-teacher="{{ $class->teacher_id }}"
                                            data-courses="{{ $class->courses->pluck('id') }}" data-bs-toggle="modal"
                                            data-bs-target="#editClassModal">
                                            <i class="fas fa-edit" style="color:#f59e0b;"></i> Sửa lớp học
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <form action="{{ route('classes.destroy', $class->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger"
                                                style="background:none; border:none; width:100%; text-align:left;"
                                                onclick="return confirm('Toàn bộ học sinh sẽ bị gỡ khỏi lớp. Bạn có chắc muốn xóa lớp này?')">
                                                <i class="fas fa-trash-alt"></i> Xóa lớp học
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Info --}}
                    <div>
                        <h2 class="class-name">{{ $class->name }}</h2>
                        <p class="class-teacher">
                            <i class="fas fa-chalkboard-teacher"></i>
                            {{ $class->teacher->name ?? 'Chưa phân công' }}
                        </p>
                    </div>

                    {{-- Manage button --}}
                    <a href="{{ route('classes.students.index', $class->id) }}" class="btn-manage">
                        <i class="fas fa-user-graduate"></i> Quản lý học sinh
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ══ MODAL TẠO LỚP ══ --}}
    @if (in_array(auth()->user()->role, ['admin', 'teacher']))
        <div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('classes.store') }}" method="POST" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tạo lớp học mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label-sm">Tên lớp học</label>
                            <input type="text" name="name" class="form-ctrl" placeholder="VD: Lớp 12A1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label-sm">Mã lớp</label>
                            <input type="text" name="code" class="form-ctrl" placeholder="VD: 12A1-2025" required>
                        </div>

                        @if (auth()->user()->role === 'admin')
                            <div class="form-group">
                                <label class="form-label-sm">Phân công giáo viên</label>
                                <select name="teacher_id" class="form-ctrl" required>
                                    <option value="">-- Chọn giáo viên --</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label-sm">
                                Phân bổ khóa học
                                @if (auth()->user()->role === 'teacher')
                                    <span class="role-badge">Khóa học của bạn</span>
                                @endif
                            </label>
                            <div class="course-list">
                                @forelse($courses as $course)
                                    <div class="course-check-item">
                                        <input class="course-checkbox" type="checkbox" name="course_ids[]"
                                            value="{{ $course->id }}" id="add_course_{{ $course->id }}">
                                        <label for="add_course_{{ $course->id }}">{{ $course->title }}</label>
                                    </div>
                                @empty
                                    <p style="font-size:13px; color:#94a3b8; margin:0; font-style:italic;">
                                        {{ auth()->user()->role === 'admin' ? 'Chưa có khóa học nào.' : 'Bạn chưa có khóa học nào.' }}
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn-modal-submit">Tạo lớp ngay</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ══ MODAL SỬA LỚP ══ --}}
    <div class="modal fade" id="editClassModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editClassForm" method="POST" class="modal-content">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Sửa thông tin lớp học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label-sm">Tên lớp học</label>
                        <input type="text" name="name" id="edit_name" class="form-ctrl" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label-sm">Mã lớp</label>
                        <input type="text" name="code" id="edit_code" class="form-ctrl" required>
                    </div>

                    @if (auth()->user()->role === 'admin')
                        <div class="form-group">
                            <label class="form-label-sm">Phân công giáo viên</label>
                            <select name="teacher_id" id="edit_teacher" class="form-ctrl" required>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label-sm">
                            Phân bổ khóa học
                            @if (auth()->user()->role === 'teacher')
                                <span class="role-badge">Khóa học của bạn</span>
                            @endif
                        </label>
                        <div class="course-list">
                            @forelse($courses as $course)
                                <div class="course-check-item">
                                    <input class="course-checkbox" type="checkbox" name="course_ids[]"
                                        value="{{ $course->id }}" id="edit_course_{{ $course->id }}">
                                    <label for="edit_course_{{ $course->id }}">{{ $course->title }}</label>
                                </div>
                            @empty
                                <p style="font-size:13px; color:#94a3b8; margin:0; font-style:italic;">Không có khóa học
                                    khả dụng.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn-modal-submit">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

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

                    const teacherSel = document.getElementById('edit_teacher');
                    if (teacherSel) teacherSel.value = this.getAttribute('data-teacher');

                    const courseIds = JSON.parse(this.getAttribute('data-courses') || '[]');
                    document.querySelectorAll('#editClassForm .course-checkbox').forEach(cb => cb
                        .checked = false);
                    courseIds.forEach(id => {
                        const cb = document.querySelector(
                            `#editClassForm .course-checkbox[value="${id}"]`);
                        if (cb) cb.checked = true;
                    });
                });
            });
        });
    </script>
@endpush
