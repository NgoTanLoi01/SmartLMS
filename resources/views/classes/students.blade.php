@extends('layouts.app')

@section('title', 'Học viên - ' . $classroom->name)

@push('styles')
    @vite('resources/css/pages/class-students.css')
@endpush

@section('content')
    <div class="lms-page class-students-page">

        {{-- Form validation messages --}}
        @if ($errors->any())
            <div class="lms-flash error" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="lms-flash-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button class="lms-flash-close" onclick="this.closest('.lms-flash').remove()">×</button>
            </div>
        @endif

        <section class="student-roster-hero">
            <span class="student-roster-hero__accent" aria-hidden="true"></span>
            <x-ui.page-header :title="$classroom->name" :breadcrumbs="[
                ['label' => 'Lớp học', 'url' => route('classes.index')],
                ['label' => $classroom->code],
            ]">
            <x-slot:meta>
                <span><i class="fa-solid fa-chalkboard-teacher" aria-hidden="true"></i>
                    {{ $classroom->teacher->name }}</span>
                <span><i class="fa-solid fa-users" aria-hidden="true"></i>
                    {{ $classroom->students->count() }} học viên</span>
            </x-slot:meta>
            @if (auth()->user()->role === 'admin' || auth()->id() === $classroom->teacher_id)
                <x-slot:actions>
                    <a href="{{ route('classes.progress', $classroom->id) }}" class="lms-btn lms-btn-outline">
                        <i class="fa-solid fa-chart-line"></i> Theo dõi tiến độ
                    </a>
                    <button class="lms-btn lms-btn-success" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                        <i class="fa-solid fa-file-excel"></i> Nhập từ Excel
                    </button>
                    <button class="lms-btn lms-btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fa-solid fa-user-plus"></i> Thêm học viên
                    </button>
                </x-slot:actions>
            @endif
            </x-ui.page-header>

            {{-- Stat cards --}}
            <x-ui.stat-grid class="student-roster-summary">
                <x-ui.stat-card label="Tổng học viên" :value="$classStats['total'] ?? $classroom->students->count()"
                    description="Đang hiển thị {{ $classStats['shown'] ?? $classroom->students->count() }}" />
                <x-ui.stat-card label="Cần theo dõi" :value="$classStats['needs_attention'] ?? 0" tone="danger"
                    description="Cảnh báo học tập hoặc điểm danh" />
                <x-ui.stat-card label="Chưa nộp bài" :value="$classStats['missing_assignments'] ?? 0" tone="warning"
                    description="Học viên còn thiếu bài tập" />
                <x-ui.stat-card label="Có lượt vắng" :value="$classStats['absent'] ?? 0" tone="info"
                    description="Từ dữ liệu điểm danh" />
            </x-ui.stat-grid>
        </section>

        {{-- Student table card --}}
        <div class="lms-card">
            <div class="lms-card-header">
                <h2 class="lms-card-title">
                    <i class="fa-solid fa-users"></i> Danh sách học viên
                </h2>
                <span class="lms-count">{{ ($studentSummaries ?? collect())->count() }} kết quả</span>
            </div>

            {{-- Filter --}}
            <form action="{{ route('classes.students.index', $classroom->id) }}" method="GET" class="lms-filter">
                <div class="lms-filter-group student-filter-search">
                    <label for="student-search">Tìm kiếm</label>
                    <div class="lms-input-icon">
                        <i class="fa-solid fa-search"></i>
                        <input id="student-search" type="search" name="search" class="lms-input"
                            placeholder="Tên, tên đăng nhập, mã HS hoặc email..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="lms-filter-group student-filter-select">
                    <label for="student-course">Khóa học</label>
                    <select id="student-course" name="course_id" class="lms-select">
                        <option value="">Tất cả khóa học</option>
                        @foreach ($availableCourses ?? collect() as $course)
                            <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? '') == $course->id)>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="lms-filter-group student-filter-select">
                    <label for="student-status">Trạng thái</label>
                    <select id="student-status" name="status" class="lms-select">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Tất cả</option>
                        <option value="needs_attention" @selected(($filters['status'] ?? '') === 'needs_attention')>Cần theo dõi</option>
                        <option value="missing_assignments" @selected(($filters['status'] ?? '') === 'missing_assignments')>Chưa nộp bài</option>
                        <option value="low_score" @selected(($filters['status'] ?? '') === 'low_score')>Điểm kiểm tra thấp</option>
                        <option value="absent" @selected(($filters['status'] ?? '') === 'absent')>Có lượt vắng</option>
                        <option value="no_activity" @selected(($filters['status'] ?? '') === 'no_activity')>Chưa có hoạt động</option>
                    </select>
                </div>
                <div class="lms-filter-actions">
                    <button type="submit" class="lms-btn lms-btn-primary student-filter-submit">
                        <i class="fa-solid fa-filter"></i> Lọc
                    </button>
                    <a href="{{ route('classes.students.index', $classroom->id) }}" class="lms-btn-reset"
                        title="Xóa bộ lọc">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>

            {{-- Table --}}
            <div class="lms-table-wrap">
                <table class="lms-table student-roster-table">
                    <thead>
                        <tr>
                            <th>Học viên</th>
                            <th>Tình trạng</th>
                            <th>Bài tập</th>
                            <th>Bài kiểm tra</th>
                            <th>Điểm danh</th>
                            <th>Hoạt động gần nhất</th>
                            <th class="student-actions-heading">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($studentSummaries ?? collect() as $summary)
                            @php $student = $summary['student']; @endphp
                            <tr>
                                {{-- Student --}}
                                <td class="student-identity-cell" data-label="Học viên">
                                    <div class="student-identity">
                                        <div class="lms-avatar">{{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}</div>
                                        <div>
                                            <div class="lms-student-name">{{ $student->name }}</div>
                                            @if ($student->username)
                                                <div class="lms-student-email">
                                                    <i class="fa-solid fa-id-badge"></i> {{ $student->username }}
                                                </div>
                                            @endif
                                            @if ($student->student_code)
                                                <div class="lms-student-email">
                                                    <i class="fa-solid fa-hashtag"></i> {{ $student->student_code }}
                                                </div>
                                            @endif
                                            <div class="lms-student-email">{{ $student->email }}</div>
                                            <div class="lms-student-id">#{{ $student->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                {{-- Status --}}
                                <td data-label="Tình trạng">
                                    @if ($summary['needs_attention'])
                                        <span class="lms-badge lms-badge-danger"><i
                                                class="fa-solid fa-circle student-status-dot"></i> Cần theo dõi</span>
                                    @else
                                        <span class="lms-badge lms-badge-success"><i
                                                class="fa-solid fa-circle student-status-dot"></i> Ổn định</span>
                                    @endif
                                    <div class="lms-alerts">
                                        @forelse($summary['alerts'] as $alert)
                                            <div class="lms-alert-item {{ $alert['level'] }}">
                                                <i class="fa-solid fa-circle-exclamation"></i>
                                                <span>{{ $alert['text'] }}</span>
                                            </div>
                                        @empty
                                            <div class="lms-alert-item"><span
                                                    class="student-no-alert">Chưa có cảnh báo</span></div>
                                        @endforelse
                                    </div>
                                </td>
                                {{-- Assignments --}}
                                <td data-label="Bài tập">
                                    <div class="lms-mini-stat">
                                        <div class="lms-mini-val">
                                            {{ $summary['assignment_submitted_count'] }}/{{ $summary['assignment_total'] }}
                                        </div>
                                        <div class="lms-micro-bar">
                                            <div class="lms-micro-fill success"
                                                style="width:{{ $summary['assignment_total'] > 0 ? round(($summary['assignment_submitted_count'] / $summary['assignment_total']) * 100) : 0 }}%;">
                                            </div>
                                        </div>
                                        <div class="lms-mini-sub">{{ $summary['assignment_missing_count'] }} thiếu ·
                                            {{ $summary['assignment_overdue_missing_count'] }} quá hạn</div>
                                        <div class="lms-mini-sub">Trung bình: {{ $summary['assignment_average'] ?? 'Chưa có' }}</div>
                                    </div>
                                </td>
                                {{-- Quiz --}}
                                <td data-label="Bài kiểm tra">
                                    <div class="lms-mini-stat">
                                        <div class="lms-mini-val">
                                            {{ $summary['quiz_attempted_count'] }}/{{ $summary['quiz_total'] }}</div>
                                        <div class="lms-micro-bar">
                                            <div class="lms-micro-fill"
                                                style="width:{{ $summary['quiz_total'] > 0 ? round(($summary['quiz_attempted_count'] / $summary['quiz_total']) * 100) : 0 }}%;">
                                            </div>
                                        </div>
                                        <div class="lms-mini-sub">Trung bình: {{ $summary['quiz_average'] ?? 'Chưa có' }}</div>
                                        <div class="lms-mini-sub">{{ $summary['quiz_pending_count'] }} chưa làm</div>
                                    </div>
                                </td>
                                {{-- Attendance --}}
                                <td data-label="Điểm danh">
                                    <div class="lms-mini-stat">
                                        <div class="lms-mini-val {{ $summary['absence_count'] > 0 ? 'is-danger' : '' }}">
                                            {{ $summary['absence_count'] }} lượt vắng
                                        </div>
                                        <div class="lms-mini-sub">{{ $summary['note_count'] }} ghi chú</div>
                                    </div>
                                </td>
                                {{-- Activity --}}
                                <td class="student-activity" data-label="Hoạt động gần nhất">
                                    @if ($summary['last_activity_at'])
                                        <div class="student-activity-date">
                                            {{ $summary['last_activity_at']->format('d/m/Y') }}</div>
                                        <div class="student-activity-time">{{ $summary['last_activity_at']->format('H:i') }}
                                        </div>
                                    @else
                                        <span class="student-activity-empty">Chưa có</span>
                                    @endif
                                </td>
                                {{-- Actions --}}
                                <td class="student-actions-cell" data-label="Hành động">
                                    <div class="lms-row-actions">
                                        <a href="{{ route('classes.students.show', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                                            class="lms-action-link">
                                            <i class="fa-solid fa-chart-line"></i> Hồ sơ
                                        </a>
                                        @if (auth()->user()->role === 'admin' || auth()->id() === $classroom->teacher_id)
                                            <form
                                                action="{{ route('classes.students.destroy', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                                                method="POST" class="d-inline lms-row-btn-delete">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="lms-action-link danger"
                                                    onclick="return confirm('Xóa {{ $student->name }} khỏi lớp?')">
                                                    <i class="fa-solid fa-user-minus"></i> Xóa
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="student-empty-cell">
                                    <div class="lms-empty">
                                        <span class="lms-empty-icon"><i class="fa-solid fa-user-graduate"></i></span>
                                        <h6>Không tìm thấy học viên phù hợp</h6>
                                        <p>Hãy đổi bộ lọc hoặc thêm học viên mới để bắt đầu.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal: Add Student --}}
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered student-modal-dialog student-modal-dialog--sm">
            <form action="{{ route('classes.students.store', $classroom->id) }}" method="POST" class="modal-content student-modal">
                @csrf
                <div class="modal-header student-modal-header">
                    <div>
                        <h5 class="modal-title">Thêm học viên mới</h5>
                        <p class="student-modal-subtitle">Tạo tài khoản và gán vào
                            <strong>{{ $classroom->name }}</strong></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body student-modal-body">
                    <div class="lms-info-box">
                        <i class="fa-solid fa-circle-info student-info-icon"></i>
                        <span>Học viên sẽ được tạo tên đăng nhập từ họ tên. Nếu trùng tên, hệ thống sẽ ghép thêm mã học viên hoặc số thứ tự.</span>
                    </div>
                    <div class="lms-form-group">
                        <label class="lms-form-label">Họ và tên</label>
                        <input type="text" name="name" class="lms-form-control" placeholder="Nguyễn Văn A"
                            required value="{{ old('name') }}">
                    </div>
                    <div class="lms-form-group">
                        <label class="lms-form-label">Mã học viên <span class="student-field-optional">(không bắt buộc)</span></label>
                        <input type="text" name="student_code" class="lms-form-control" placeholder="VD: HS001"
                            value="{{ old('student_code') }}">
                    </div>
                    <div class="lms-form-group">
                        <label class="lms-form-label">Địa chỉ Email <span class="student-field-optional">(không bắt buộc)</span></label>
                        <input type="email" name="email" class="lms-form-control"
                            placeholder="nguyenvana@example.com" value="{{ old('email') }}">
                    </div>
                    <div class="lms-form-group student-form-group-last">
                        <label class="lms-form-label">Mật khẩu khởi tạo</label>
                        <input type="password" name="password" class="lms-form-control" placeholder="Ít nhất 6 ký tự"
                            required minlength="6">
                    </div>
                </div>
                <div class="modal-footer student-modal-footer">
                    <button type="button" class="lms-btn lms-btn-outline" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="lms-btn lms-btn-primary">
                        <i class="fa-solid fa-user-plus"></i> Thêm vào lớp
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Import Excel --}}
    <div class="modal fade" id="importExcelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered student-modal-dialog">
            <form action="{{ route('classes.students.import', $classroom->id) }}" method="POST"
                enctype="multipart/form-data" class="modal-content student-modal">
                @csrf
                <div class="modal-header student-modal-header">
                    <div>
                        <h5 class="modal-title">Nhập danh sách từ Excel</h5>
                        <p class="student-modal-subtitle">Tải lên file danh sách học viên
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body student-modal-body">
                    <div class="lms-warning-box">
                        <strong class="student-warning-title"><i class="fa-solid fa-circle-info"></i>Hướng dẫn & Quy chuẩn</strong>
                        <ul>
                            <li>Hệ thống đọc từ <strong>Dòng số 2</strong></li>
                            <li>Cột: <strong>D</strong> (Mã HV) · <strong>E</strong> (Họ) · <strong>F</strong> (Tên)</li>
                            <li>Tên đăng nhập tự sinh từ họ tên, ví dụ <code>nguyenvana</code></li>
                            <li>Nếu trùng họ tên, hệ thống ghép thêm Mã HV hoặc số thứ tự</li>
                            <li>Mật khẩu mặc định: <code>123456</code></li>
                        </ul>
                        <a href="{{ asset('templates/mau_danh_sach_hoc_sinh.xlsx') }}" class="student-template-link">
                            <i class="fa-solid fa-file-excel"></i> Tải file biểu mẫu chuẩn (.xlsx)
                        </a>
                    </div>
                    <div class="lms-form-group student-form-group-spaced">
                        <label class="lms-form-label">Chọn file đã điền dữ liệu</label>
                        <input type="file" name="file" class="form-control student-file-input" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div class="lms-form-group student-form-group-spaced">
                        <label class="lms-form-label">Cách cập nhật sĩ số</label>
                        <label class="student-import-mode">
                            <input type="radio" name="mode" value="append" checked>
                            <span><strong>Thêm/cập nhật</strong><br><small class="text-muted">Giữ nguyên học viên đang có và bổ sung dữ liệu từ file.</small></span>
                        </label>
                        <label class="student-import-mode student-import-mode--danger">
                            <input type="radio" name="mode" value="replace">
                            <span><strong>Thay thế toàn bộ sĩ số</strong><br><small class="text-danger">Hệ thống sẽ hiển thị chính xác học viên bị gỡ ở bước xác nhận tiếp theo.</small></span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer student-modal-footer">
                    <button type="button" class="lms-btn lms-btn-outline" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="lms-btn lms-btn-primary">
                        <i class="fa-solid fa-upload"></i> Bắt đầu nhập
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
