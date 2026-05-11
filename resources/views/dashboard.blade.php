@extends('layouts.app')

@section('title', 'Bảng điều khiển')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-dark mb-0">Xin chào, {{ auth()->user()->name }}! 👋</h3>
            <p class="text-muted mb-0">{{ \Carbon\Carbon::now()->format('l, d/m/Y') }}</p>
        </div>

        {{-- ========================================== --}}
        {{-- 1. GIAO DIỆN ADMIN --}}
        {{-- ========================================== --}}
        @if (auth()->user()->role === 'admin')
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 bg-primary text-white h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-white bg-opacity-25 p-3 rounded-3 me-3"><i class="fas fa-users fa-2x"></i></div>
                            <div>
                                <p class="mb-0 text-white-50 fw-bold">Tổng Học sinh</p>
                                <h3 class="mb-0 fw-bold">{{ $data['total_students'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 bg-info text-white h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-white bg-opacity-25 p-3 rounded-3 me-3"><i
                                    class="fas fa-chalkboard-teacher fa-2x"></i></div>
                            <div>
                                <p class="mb-0 text-white-50 fw-bold">Tổng Giáo viên</p>
                                <h3 class="mb-0 fw-bold">{{ $data['total_teachers'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 bg-success text-white h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-white bg-opacity-25 p-3 rounded-3 me-3"><i class="fas fa-layer-group fa-2x"></i>
                            </div>
                            <div>
                                <p class="mb-0 text-white-50 fw-bold">Khóa học / Lớp</p>
                                <h3 class="mb-0 fw-bold">{{ $data['total_courses'] }} / {{ $data['total_classes'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 bg-warning text-dark h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-dark bg-opacity-10 p-3 rounded-3 me-3"><i
                                    class="fas fa-globe text-dark fa-2x"></i></div>
                            <div>
                                <p class="mb-0 text-dark opacity-75 fw-bold">Đang Online</p>
                                <h3 class="mb-0 fw-bold">{{ $data['online_users'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-user-plus text-primary me-2"></i>Người dùng
                                đăng ký mới</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted small">
                                    <tr>
                                        <th class="px-4 py-3">Họ và tên</th>
                                        <th class="px-4 py-3">Email</th>
                                        <th class="px-4 py-3">Vai trò</th>
                                        <th class="px-4 py-3">Ngày tham gia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data['recent_users'] as $user)
                                        <tr>
                                            <td class="px-4 fw-bold">{{ $user->name }}</td>
                                            <td class="px-4 text-muted">{{ $user->email }}</td>
                                            <td class="px-4">
                                                <span
                                                    class="badge {{ $user->role == 'teacher' ? 'bg-info' : ($user->role == 'admin' ? 'bg-secondary' : 'bg-primary') }}">
                                                    {{ strtoupper($user->role) }}
                                                </span>
                                            </td>
                                            <td class="px-4 text-muted small">
                                                {{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-pie text-success me-2"></i>Tỷ lệ người
                                dùng</h6>
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center">
                            <div id="adminChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================================== --}}
            {{-- 2. GIAO DIỆN GIÁO VIÊN --}}
            {{-- ========================================== --}}
        @elseif(auth()->user()->role === 'teacher')
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 border-start border-danger border-5 h-100">
                        <div class="card-body">
                            <p class="text-muted fw-bold text-uppercase small mb-1">Cần xử lý ngay</p>
                            <h3 class="text-danger fw-bold mb-0">{{ $data['pending_grades'] }} <small
                                    class="fs-6 text-muted">bài tập chờ chấm</small></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 border-start border-primary border-5 h-100">
                        <div class="card-body">
                            <p class="text-muted fw-bold text-uppercase small mb-1">Khóa học phụ trách</p>
                            <h3 class="text-primary fw-bold mb-0">{{ $data['total_courses'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 border-start border-success border-5 h-100">
                        <div class="card-body">
                            <p class="text-muted fw-bold text-uppercase small mb-1">Tổng học sinh</p>
                            <h3 class="text-success fw-bold mb-0">{{ $data['total_students'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-inbox text-danger me-2"></i>Bài tập vừa nộp
                                (Chờ chấm)</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @forelse($data['recent_submissions'] as $sub)
                                    <div
                                        class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold text-dark">{{ $sub->student_name }}</div>
                                            <div class="small text-muted mb-1">Bài: <span
                                                    class="fw-bold">{{ $sub->assignment_title ?? 'N/A' }}</span></div>
                                            <div class="small"><span
                                                    class="badge bg-primary bg-opacity-10 text-primary border border-primary"><i
                                                        class="fas fa-book me-1"></i> Khóa:
                                                    {{ $sub->course_title ?? 'N/A' }}</span></div>
                                        </div>
                                        <div class="text-end">
                                            <span
                                                class="text-muted small d-block mb-2">{{ \Carbon\Carbon::parse($sub->created_at)->diffForHumans() }}</span>
                                            <a href="{{ route('courses.show', $sub->course_id ?? 0) }}"
                                                class="btn btn-sm btn-outline-danger rounded-pill px-3">Chấm ngay</a>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                                        <p class="mb-0">Tuyệt vời! Thầy / Cô đã chấm hết bài tập.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-donut text-primary me-2"></i>Tiến độ
                                chấm bài</h6>
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center">
                            <div id="teacherChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div
                            class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-calendar-alt text-primary me-2"></i>Lịch
                                dạy trong tuần</h6>
                            <span class="badge bg-primary bg-opacity-10 text-primary">Tuần này</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light small">
                                        <tr>
                                            <th class="px-4 py-2">Ngày / Thứ</th>
                                            <th class="px-4 py-2">Giờ dạy</th>
                                            <th class="px-4 py-2">Môn học / Lớp</th>
                                            <th class="px-4 py-2">Phòng</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (isset($data['week_schedule']))
                                            @forelse($data['week_schedule'] as $slot)
                                                <tr
                                                    class="{{ \Carbon\Carbon::parse($slot->schedule_date)->isToday() ? 'bg-primary bg-opacity-10' : '' }}">
                                                    <td class="px-4 py-3">
                                                        <div class="fw-bold">
                                                            {{ \Carbon\Carbon::parse($slot->schedule_date)->format('d/m') }}
                                                        </div>
                                                        <div class="small text-muted">Thứ
                                                            {{ \Carbon\Carbon::parse($slot->schedule_date)->dayOfWeek + 1 == 1 ? 'CN' : \Carbon\Carbon::parse($slot->schedule_date)->dayOfWeek + 1 }}
                                                        </div>
                                                    </td>
                                                    <td class="px-4">
                                                        <div class="text-primary fw-bold small">
                                                            {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }} -
                                                            {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                                        </div>
                                                    </td>
                                                    <td class="px-4">
                                                        <div class="fw-bold small text-dark">{{ $slot->course_title }}
                                                        </div>
                                                        <div class="small text-muted">Lớp: {{ $slot->class_name }}</div>
                                                    </td>
                                                    <td class="px-4">
                                                        <span
                                                            class="badge bg-light text-dark border">{{ $slot->room ?? 'Online' }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-5 text-muted">
                                                        <i class="fas fa-calendar-day fa-2x mb-2 opacity-25 d-block"></i>
                                                        <p class="mb-0 small">Không có lịch dạy trong tuần này.</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================================== --}}
            {{-- 3. GIAO DIỆN HỌC SINH --}}
            {{-- ========================================== --}}
        @else
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-primary text-white h-100"
                        style="background: linear-gradient(45deg, #0d6efd, #6f42c1);">
                        <div class="card-body p-4 text-center">
                            <h3 class="fw-bold text-white-50">Điểm Quiz trung bình</h3>
                            <h1 class="display-3 fw-bold mb-0" style="font-size: 8rem;">
                                {{ $data['average_score'] }}
                            </h1>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-clock text-warning me-2"></i>Việc cần làm
                                (Deadline & Bài kiểm tra)</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">

                                {{-- BÀI TẬP SẮP HẾT HẠN --}}
                                @if (isset($data['upcoming_deadlines']))
                                    @foreach ($data['upcoming_deadlines'] as $deadline)
                                        <div
                                            class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-warning text-dark me-2 mb-1">Bài tập</span>
                                                <span class="fw-bold d-block">{{ $deadline->title }}</span>
                                                <div class="small mt-1"><span
                                                        class="badge bg-light text-secondary border"><i
                                                            class="fas fa-book me-1"></i> Khóa:
                                                        {{ $deadline->course_title ?? 'N/A' }}</span></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="text-danger small fw-bold mb-1">
                                                    <i class="fas fa-hourglass-half me-1"></i> Hạn:
                                                    {{ \Carbon\Carbon::parse($deadline->due_date)->format('H:i - d/m/Y') }}
                                                </div>
                                                <a href="{{ route('courses.show', $deadline->course_id ?? 0) }}"
                                                    class="btn btn-sm btn-outline-warning rounded-pill px-3 text-dark fw-bold">Nộp
                                                    bài</a>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                {{-- BÀI KIỂM TRA CHƯA LÀM --}}
                                @if (isset($data['pending_quizzes']))
                                    @foreach ($data['pending_quizzes'] as $quiz)
                                        <div
                                            class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center bg-primary bg-opacity-10 border-bottom border-white">
                                            <div>
                                                <span class="badge bg-primary me-2 mb-1">Kiểm tra</span>
                                                <span class="fw-bold d-block text-primary">{{ $quiz->title }}</span>
                                                <div class="small mt-1"><span
                                                        class="badge bg-white text-secondary border"><i
                                                            class="fas fa-book me-1"></i> Khóa:
                                                        {{ $quiz->course_title ?? 'N/A' }}</span></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="text-primary small fw-bold mb-1">
                                                    <i class="fas fa-stopwatch me-1"></i> Thời gian:
                                                    {{ $quiz->time_limit }} phút
                                                </div>
                                                <a href="{{ route('courses.show', $quiz->course_id ?? 0) }}"
                                                    class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-bold">Làm
                                                    bài ngay</a>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                @if (empty($data['upcoming_deadlines']) && empty($data['pending_quizzes']))
                                    <div class="text-center py-4 text-muted">
                                        <i class="fas fa-glass-cheers fa-2x mb-2 text-success opacity-50 d-block"></i>
                                        <p class="mb-0">Tuyệt vời! Bạn đã hoàn thành hết các nhiệm vụ.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div
                            class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-calendar-check text-success me-2"></i>Lịch
                                học trong tuần</h6>
                            <span class="badge bg-success bg-opacity-10 text-success">Tuần này</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light small">
                                        <tr>
                                            <th class="px-4 py-2">Ngày / Thứ</th>
                                            <th class="px-4 py-2">Giờ học</th>
                                            <th class="px-4 py-2">Môn học / Lớp</th>
                                            <th class="px-4 py-2">Phòng</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (isset($data['week_schedule']))
                                            @forelse($data['week_schedule'] as $slot)
                                                <tr
                                                    class="{{ \Carbon\Carbon::parse($slot->schedule_date)->isToday() ? 'bg-success bg-opacity-10' : '' }}">
                                                    <td class="px-4 py-3">
                                                        <div class="fw-bold">
                                                            {{ \Carbon\Carbon::parse($slot->schedule_date)->format('d/m') }}
                                                        </div>
                                                        <div class="small text-muted">Thứ
                                                            {{ \Carbon\Carbon::parse($slot->schedule_date)->dayOfWeek + 1 == 1 ? 'CN' : \Carbon\Carbon::parse($slot->schedule_date)->dayOfWeek + 1 }}
                                                        </div>
                                                    </td>
                                                    <td class="px-4">
                                                        <div class="text-success fw-bold small">
                                                            {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }} -
                                                            {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                                        </div>
                                                    </td>
                                                    <td class="px-4">
                                                        <div class="fw-bold small text-dark">{{ $slot->course_title }}
                                                        </div>
                                                        <div class="small text-muted">Lớp: {{ $slot->class_name }}</div>
                                                    </td>
                                                    <td class="px-4">
                                                        <span
                                                            class="badge bg-light text-dark border">{{ $slot->room ?? 'Online' }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-5 text-muted">
                                                        <i class="fas fa-bed fa-2x mb-2 opacity-25 d-block"></i>
                                                        <p class="mb-0 small">Tuần này bạn không có lịch học.</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line text-success me-2"></i>Phổ điểm
                                các bài kiểm tra gần đây</h6>
                        </div>
                        <div class="card-body">
                            @if (count($data['chart_quiz_data']) > 0)
                                <div id="studentChart"></div>
                            @else
                                <p class="text-muted text-center py-4 mb-0">Bạn chưa làm bài kiểm tra nào.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @if (auth()->user()->role === 'admin')
                // Biểu đồ Admin: Tỷ lệ người dùng (Donut Chart)
                var adminOptions = {
                    series: @json($data['chart_role_data']),
                    labels: @json($data['chart_role_labels']),
                    chart: {
                        type: 'donut',
                        height: 300
                    },
                    colors: ['#0d6efd', '#17a2b8', '#6c757d'],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%'
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true
                    },
                    legend: {
                        position: 'bottom'
                    }
                };
                new ApexCharts(document.querySelector("#adminChart"), adminOptions).render();
            @elseif (auth()->user()->role === 'teacher')
                // Biểu đồ Giáo viên: Tình trạng chấm bài (Pie Chart)
                var teacherOptions = {
                    series: @json($data['chart_submission_data']),
                    labels: @json($data['chart_submission_labels']),
                    chart: {
                        type: 'pie',
                        height: 300
                    },
                    colors: ['#198754', '#dc3545'],
                    dataLabels: {
                        enabled: true
                    },
                    legend: {
                        position: 'bottom'
                    }
                };
                new ApexCharts(document.querySelector("#teacherChart"), teacherOptions).render();
            @else
                // Biểu đồ Học sinh: Điểm Quiz gần đây (Bar Chart)
                @if (count($data['chart_quiz_data']) > 0)
                    var studentOptions = {
                        series: [{
                            name: 'Điểm số',
                            data: @json($data['chart_quiz_data'])
                        }],
                        chart: {
                            type: 'bar',
                            height: 300,
                            toolbar: {
                                show: false
                            }
                        },
                        xaxis: {
                            categories: @json($data['chart_quiz_labels'])
                        },
                        yaxis: {
                            max: 10
                        },
                        colors: ['#6f42c1'],
                        plotOptions: {
                            bar: {
                                borderRadius: 4,
                                columnWidth: '40%'
                            }
                        },
                        dataLabels: {
                            enabled: true
                        }
                    };
                    new ApexCharts(document.querySelector("#studentChart"), studentOptions).render();
                @endif
            @endif
        });
    </script>
@endsection
