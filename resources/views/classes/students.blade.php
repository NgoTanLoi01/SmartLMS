@extends('layouts.app')

@section('title', 'Học viên - ' . $classroom->name)

@push('styles')
    @vite('resources/css/pages/class-students.css')
@endpush

@section('content')
    <div class="lms-page">

        {{-- Form validation messages --}}
        @if ($errors->any())
            <div class="lms-flash error" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul style="margin:0; padding-left:16px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button class="lms-flash-close" onclick="this.closest('.lms-flash').remove()">×</button>
            </div>
        @endif

        <x-ui.page-header :title="$classroom->name" :breadcrumbs="[
            ['label' => 'Lớp học', 'url' => route('classes.index')],
            ['label' => $classroom->code],
        ]">
            <x-slot:meta>
                <span><i class="fa-solid fa-chalkboard-teacher" aria-hidden="true"></i>
                    {{ $classroom->teacher->name }}</span>
                <span><i class="fa-solid fa-users" aria-hidden="true"></i>
                    {{ $classroom->students->count() }} học sinh</span>
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
                        <i class="fa-solid fa-user-plus"></i> Thêm học sinh
                    </button>
                </x-slot:actions>
            @endif
        </x-ui.page-header>

        {{-- Stat cards --}}
        <x-ui.stat-grid>
            <x-ui.stat-card label="Tổng học sinh" :value="$classStats['total'] ?? $classroom->students->count()"
                description="Đang hiển thị {{ $classStats['shown'] ?? $classroom->students->count() }}" />
            <x-ui.stat-card label="Cần theo dõi" :value="$classStats['needs_attention'] ?? 0" tone="danger"
                description="Cảnh báo học tập hoặc điểm danh" />
            <x-ui.stat-card label="Chưa nộp bài" :value="$classStats['missing_assignments'] ?? 0" tone="warning"
                description="Học sinh còn thiếu bài tập" />
            <x-ui.stat-card label="Có lượt vắng" :value="$classStats['absent'] ?? 0" tone="info"
                description="Từ dữ liệu điểm danh" />
        </x-ui.stat-grid>

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
                <div class="lms-filter-group" style="flex:2; min-width:200px;">
                    <label>Tìm kiếm</label>
                    <div class="lms-input-icon">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" name="search" class="lms-input" style="width:100%;"
                            placeholder="Tên, tên đăng nhập, mã HS hoặc email..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="lms-filter-group" style="flex:1.5; min-width:160px;">
                    <label>Khóa học</label>
                    <select name="course_id" class="lms-select" style="width:100%;">
                        <option value="">Tất cả khóa học</option>
                        @foreach ($availableCourses ?? collect() as $course)
                            <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? '') == $course->id)>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="lms-filter-group" style="flex:1.5; min-width:160px;">
                    <label>Trạng thái</label>
                    <select name="status" class="lms-select" style="width:100%;">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Tất cả</option>
                        <option value="needs_attention" @selected(($filters['status'] ?? '') === 'needs_attention')>Cần theo dõi</option>
                        <option value="missing_assignments" @selected(($filters['status'] ?? '') === 'missing_assignments')>Chưa nộp bài</option>
                        <option value="low_score" @selected(($filters['status'] ?? '') === 'low_score')>Điểm quiz thấp</option>
                        <option value="absent" @selected(($filters['status'] ?? '') === 'absent')>Có lượt vắng</option>
                        <option value="no_activity" @selected(($filters['status'] ?? '') === 'no_activity')>Chưa có hoạt động</option>
                    </select>
                </div>
                <div class="lms-filter-actions" style="padding-bottom:0;">
                    <button type="submit" class="lms-btn lms-btn-primary" style="height:36px; padding:0 14px;">
                        <i class="fa-solid fa-filter"></i> Lọc
                    </button>
                    <a href="{{ route('classes.students.index', $classroom->id) }}" class="lms-btn-reset"
                        title="Xóa bộ lọc">
                        <i class="fa-solid fa-rotate-left" style="font-size:13px;"></i>
                    </a>
                </div>
            </form>

            {{-- Table --}}
            <div class="lms-table-wrap">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>Học sinh</th>
                            <th>Tình trạng</th>
                            <th>Bài tập</th>
                            <th>Quiz</th>
                            <th>Điểm danh</th>
                            <th>Hoạt động gần nhất</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($studentSummaries ?? collect() as $summary)
                            @php $student = $summary['student']; @endphp
                            <tr>
                                {{-- Student --}}
                                <td>
                                    <div style="display:flex; align-items:center; gap:11px;">
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
                                <td>
                                    @if ($summary['needs_attention'])
                                        <span class="lms-badge lms-badge-danger"><i class="fa-solid fa-circle"
                                                style="font-size:6px;"></i> Cần theo dõi</span>
                                    @else
                                        <span class="lms-badge lms-badge-success"><i class="fa-solid fa-circle"
                                                style="font-size:6px;"></i> Ổn định</span>
                                    @endif
                                    <div class="lms-alerts">
                                        @forelse($summary['alerts'] as $alert)
                                            <div class="lms-alert-item {{ $alert['level'] }}">
                                                <i class="fa-solid fa-circle-exclamation"></i>
                                                <span>{{ $alert['text'] }}</span>
                                            </div>
                                        @empty
                                            <div class="lms-alert-item"><span
                                                    style="font-size:11.5px; color:#94A3B8;">Chưa có cảnh báo</span></div>
                                        @endforelse
                                    </div>
                                </td>
                                {{-- Assignments --}}
                                <td>
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
                                        <div class="lms-mini-sub">TB: {{ $summary['assignment_average'] ?? 'N/A' }}</div>
                                    </div>
                                </td>
                                {{-- Quiz --}}
                                <td>
                                    <div class="lms-mini-stat">
                                        <div class="lms-mini-val">
                                            {{ $summary['quiz_attempted_count'] }}/{{ $summary['quiz_total'] }}</div>
                                        <div class="lms-micro-bar">
                                            <div class="lms-micro-fill"
                                                style="width:{{ $summary['quiz_total'] > 0 ? round(($summary['quiz_attempted_count'] / $summary['quiz_total']) * 100) : 0 }}%;">
                                            </div>
                                        </div>
                                        <div class="lms-mini-sub">TB: {{ $summary['quiz_average'] ?? 'N/A' }}</div>
                                        <div class="lms-mini-sub">{{ $summary['quiz_pending_count'] }} chưa làm</div>
                                    </div>
                                </td>
                                {{-- Attendance --}}
                                <td>
                                    <div class="lms-mini-stat">
                                        <div class="lms-mini-val"
                                            style="{{ $summary['absence_count'] > 0 ? 'color:var(--lms-danger)' : '' }}">
                                            {{ $summary['absence_count'] }} lượt vắng
                                        </div>
                                        <div class="lms-mini-sub">{{ $summary['note_count'] }} ghi chú</div>
                                    </div>
                                </td>
                                {{-- Activity --}}
                                <td style="font-size:13px; color:var(--lms-muted);">
                                    @if ($summary['last_activity_at'])
                                        <div style="font-weight:600; color:var(--lms-text);">
                                            {{ $summary['last_activity_at']->format('d/m/Y') }}</div>
                                        <div style="font-size:12px;">{{ $summary['last_activity_at']->format('H:i') }}
                                        </div>
                                    @else
                                        <span style="font-size:12px; color:#94A3B8;">Chưa có</span>
                                    @endif
                                </td>
                                {{-- Actions --}}
                                <td>
                                    <div class="lms-row-actions">
                                        <a href="{{ route('classes.students.show', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                                            class="lms-action-link">
                                            <i class="fa-solid fa-chart-line" style="font-size:12px;"></i> Hồ sơ
                                        </a>
                                        @if (auth()->user()->role === 'admin' || auth()->id() === $classroom->teacher_id)
                                            <form
                                                action="{{ route('classes.students.destroy', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                                                method="POST" class="d-inline lms-row-btn-delete">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="lms-action-link danger"
                                                    style="background:none; border:none; cursor:pointer; font-family:inherit;"
                                                    onclick="return confirm('Xóa {{ $student->name }} khỏi lớp?')">
                                                    <i class="fa-solid fa-user-minus" style="font-size:12px;"></i> Xóa
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="lms-empty">
                                        <span class="lms-empty-icon"><i class="fa-solid fa-user-graduate"></i></span>
                                        <h6>Không tìm thấy học sinh phù hợp</h6>
                                        <p>Hãy đổi bộ lọc hoặc thêm học sinh mới để bắt đầu.</p>
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
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <form action="{{ route('classes.students.store', $classroom->id) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header" style="padding-bottom:16px;">
                    <div>
                        <h5 class="modal-title">Thêm học sinh mới</h5>
                        <p style="font-size:13px; color:var(--lms-muted); margin:4px 0 0;">Tạo tài khoản và gán vào
                            <strong>{{ $classroom->name }}</strong></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding-top:8px;">
                    <div class="lms-info-box">
                        <i class="fa-solid fa-circle-info" style="flex-shrink:0; margin-top:1px;"></i>
                        <span>Học sinh sẽ được tạo tên đăng nhập từ họ tên. Nếu trùng tên, hệ thống sẽ ghép thêm mã học sinh hoặc số thứ tự.</span>
                    </div>
                    <div class="lms-form-group">
                        <label class="lms-form-label">Họ và tên</label>
                        <input type="text" name="name" class="lms-form-control" placeholder="Nguyễn Văn A"
                            required value="{{ old('name') }}">
                    </div>
                    <div class="lms-form-group">
                        <label class="lms-form-label">Mã học sinh <span style="font-weight:500; color:var(--lms-muted);">(không bắt buộc)</span></label>
                        <input type="text" name="student_code" class="lms-form-control" placeholder="VD: HS001"
                            value="{{ old('student_code') }}">
                    </div>
                    <div class="lms-form-group">
                        <label class="lms-form-label">Địa chỉ Email <span style="font-weight:500; color:var(--lms-muted);">(không bắt buộc)</span></label>
                        <input type="email" name="email" class="lms-form-control"
                            placeholder="nguyenvana@example.com" value="{{ old('email') }}">
                    </div>
                    <div class="lms-form-group" style="margin-bottom:0;">
                        <label class="lms-form-label">Mật khẩu khởi tạo</label>
                        <input type="password" name="password" class="lms-form-control" placeholder="Ít nhất 6 ký tự"
                            required minlength="6">
                    </div>
                </div>
                <div class="modal-footer" style="padding-top:16px;">
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
        <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
            <form action="{{ route('classes.students.import', $classroom->id) }}" method="POST"
                enctype="multipart/form-data" class="modal-content">
                @csrf
                <div class="modal-header" style="padding-bottom:16px;">
                    <div>
                        <h5 class="modal-title">Nhập danh sách từ Excel</h5>
                        <p style="font-size:13px; color:var(--lms-muted); margin:4px 0 0;">Tải lên file danh sách học sinh
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding-top:8px;">
                    <div class="lms-warning-box">
                        <strong style="font-size:13.5px;"><i class="fa-solid fa-circle-info"
                                style="margin-right:5px;"></i>Hướng dẫn & Quy chuẩn</strong>
                        <ul>
                            <li>Hệ thống đọc từ <strong>Dòng số 2</strong></li>
                            <li>Cột: <strong>D</strong> (Mã HS) · <strong>E</strong> (Họ) · <strong>F</strong> (Tên)</li>
                            <li>Tên đăng nhập tự sinh từ họ tên, ví dụ <code>nguyenvana</code></li>
                            <li>Nếu trùng họ tên, hệ thống ghép thêm Mã HS hoặc số thứ tự</li>
                            <li>Mật khẩu mặc định: <code>123456</code></li>
                        </ul>
                        <a href="{{ asset('templates/mau_danh_sach_hoc_sinh.xlsx') }}"
                            style="display:inline-flex; align-items:center; gap:6px; margin-top:4px;">
                            <i class="fa-solid fa-file-excel" style="color:#059669;"></i> Tải file biểu mẫu chuẩn (.xlsx)
                        </a>
                    </div>
                    <div class="lms-form-group" style="margin-top:16px; margin-bottom:0;">
                        <label class="lms-form-label">Chọn file đã điền dữ liệu</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required
                            style="font-size:13.5px;">
                    </div>
                </div>
                <div class="modal-footer" style="padding-top:16px;">
                    <button type="button" class="lms-btn lms-btn-outline" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="lms-btn lms-btn-success"
                        style="background:#059669; color:#fff; border-color:#059669;">
                        <i class="fa-solid fa-upload"></i> Bắt đầu nhập
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
