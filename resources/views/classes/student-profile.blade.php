@extends('layouts.app')

@section('title', 'Hồ sơ học sinh - ' . $student->name)

@section('content')
    <style>
        .profile-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
        }

        .profile-stat-card {
            min-height: 100%;
        }

        .profile-table {
            min-width: 760px;
        }

        @media (max-width: 767.98px) {
            .student-profile-container {
                padding-left: .75rem !important;
                padding-right: .75rem !important;
            }

            .profile-header-actions,
            .profile-header-actions .btn,
            .profile-course-filter,
            .profile-course-filter .form-select,
            .profile-course-filter .btn {
                width: 100%;
            }

            .profile-stat-card {
                min-height: auto;
            }
        }
    </style>

    <div class="container-fluid py-4 student-profile-container">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="profile-avatar bg-primary bg-opacity-10 text-primary">
                    {{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}
                </div>
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item">
                                <a href="{{ route('classes.students.index', $classroom->id) }}" class="text-decoration-none">
                                    {{ $classroom->name }}
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Hồ sơ học sinh</li>
                        </ol>
                    </nav>
                    <h3 class="fw-bold mb-1">{{ $student->name }}</h3>
                    <div class="text-muted small">
                        <i class="fas fa-envelope me-1"></i>{{ $student->email }}
                        <span class="mx-2">|</span>
                        <i class="fas fa-chalkboard-teacher me-1"></i>{{ $classroom->teacher->name }}
                    </div>
                </div>
            </div>
            <div class="profile-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('classes.students.index', $classroom->id) }}" class="btn btn-light border">
                    <i class="fas fa-arrow-left me-1"></i>Quay lại lớp
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body">
                <form action="{{ route('classes.students.show', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                    method="GET" class="profile-course-filter row g-2 align-items-end">
                    <div class="col-12 col-md-5 col-xl-4">
                        <label class="form-label small text-muted mb-1">Xem theo khóa học</label>
                        <select name="course_id" class="form-select bg-light border-0 shadow-none">
                            <option value="">Tất cả khóa học của lớp</option>
                            @foreach ($availableCourses as $course)
                                <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? '') == $course->id)>
                                    {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="fas fa-filter me-1"></i>Lọc
                        </button>
                        <a href="{{ route('classes.students.show', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                            class="btn btn-light border">
                            <i class="fas fa-rotate-left me-1"></i>Xóa lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @if (count($studentProfile['alerts']) > 0)
            <div class="alert alert-warning border-0 shadow-sm">
                <div class="fw-bold mb-2"><i class="fas fa-triangle-exclamation me-1"></i>Cảnh báo cần theo dõi</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($studentProfile['alerts'] as $alert)
                        <span class="badge bg-{{ $alert['level'] }}">{{ $alert['text'] }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="profile-stat-card bg-white border rounded-3 p-3 shadow-sm">
                    <div class="text-muted small mb-1">Tiến độ bài học</div>
                    <div class="h4 fw-bold mb-2">{{ $studentProfile['lesson_progress'] }}%</div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar" style="width: {{ $studentProfile['lesson_progress'] }}%;"></div>
                    </div>
                    <div class="text-muted small mt-2">{{ $studentProfile['lesson_completed'] }}/{{ $studentProfile['lesson_total'] }} bài đã hoàn thành</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="profile-stat-card bg-white border rounded-3 p-3 shadow-sm">
                    <div class="text-muted small mb-1">Bài tập</div>
                    <div class="h4 fw-bold mb-0">{{ $studentProfile['assignment_submitted_count'] }}/{{ $studentProfile['assignment_total'] }}</div>
                    <div class="text-muted small mt-1">{{ $studentProfile['assignment_missing_count'] }} thiếu, {{ $studentProfile['assignment_overdue_missing_count'] }} quá hạn</div>
                    <div class="text-muted small">Điểm TB: {{ $studentProfile['assignment_average'] ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="profile-stat-card bg-white border rounded-3 p-3 shadow-sm">
                    <div class="text-muted small mb-1">Quiz</div>
                    <div class="h4 fw-bold mb-0">{{ $studentProfile['quiz_average'] ?? 'N/A' }}</div>
                    <div class="text-muted small mt-1">{{ $studentProfile['quiz_attempted_count'] }}/{{ $studentProfile['quiz_total'] }} bài đã làm</div>
                    <div class="text-muted small">{{ $studentProfile['quiz_pending_count'] }} bài chưa làm</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="profile-stat-card bg-white border rounded-3 p-3 shadow-sm">
                    <div class="text-muted small mb-1">Điểm danh & ghi chú</div>
                    <div class="h4 fw-bold mb-0">{{ $studentProfile['absence_count'] }}</div>
                    <div class="text-muted small mt-1">Lượt vắng/nghỉ</div>
                    <div class="text-muted small">{{ $studentProfile['note_count'] }} ghi chú từ điểm danh</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-tasks me-2 text-primary"></i>Theo dõi bài tập</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table profile-table align-middle mb-0">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="px-4 py-3 border-0">Bài tập</th>
                                        <th class="px-4 py-3 border-0">Khóa học</th>
                                        <th class="px-4 py-3 border-0">Hạn nộp</th>
                                        <th class="px-4 py-3 border-0">Trạng thái</th>
                                        <th class="px-4 py-3 border-0">Điểm</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($studentProfile['assignment_details'] as $assignment)
                                        <tr>
                                            <td class="px-4 py-3 fw-medium">{{ $assignment['title'] }}</td>
                                            <td class="px-4 py-3 text-muted small">{{ $assignment['course_title'] }}</td>
                                            <td class="px-4 py-3 text-muted small">
                                                {{ $assignment['due_date'] ? \Illuminate\Support\Carbon::parse($assignment['due_date'])->format('d/m/Y H:i') : 'Không có' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                @if ($assignment['status'] === 'submitted')
                                                    <span class="badge bg-success">Đã nộp</span>
                                                @elseif ($assignment['is_overdue'])
                                                    <span class="badge bg-danger">Quá hạn</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Chưa nộp</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-muted small">{{ $assignment['grade'] ?? 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Chưa có bài tập trong phạm vi đang xem.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-clipboard-check me-2 text-primary"></i>Kết quả quiz</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table profile-table align-middle mb-0">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="px-4 py-3 border-0">Quiz</th>
                                        <th class="px-4 py-3 border-0">Khóa học</th>
                                        <th class="px-4 py-3 border-0">Trạng thái</th>
                                        <th class="px-4 py-3 border-0">Điểm</th>
                                        <th class="px-4 py-3 border-0">Hoàn thành</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($studentProfile['quiz_details'] as $quiz)
                                        <tr>
                                            <td class="px-4 py-3 fw-medium">{{ $quiz['title'] }}</td>
                                            <td class="px-4 py-3 text-muted small">{{ $quiz['course_title'] }}</td>
                                            <td class="px-4 py-3">
                                                @if ($quiz['status'] === 'attempted')
                                                    <span class="badge bg-success">Đã làm</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Chưa làm</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-muted small">{{ $quiz['score'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-muted small">
                                                {{ $quiz['completed_at'] ? \Illuminate\Support\Carbon::parse($quiz['completed_at'])->format('d/m/Y H:i') : 'Chưa có' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Chưa có quiz trong phạm vi đang xem.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-book-open me-2 text-primary"></i>Khóa học đang theo dõi</h6>
                    </div>
                    <div class="card-body">
                        @forelse ($studentProfile['courses'] as $course)
                            <div class="border-bottom pb-3 mb-3">
                                <div class="fw-bold">{{ $course->title }}</div>
                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit($course->description ?? 'Chưa có mô tả', 110) }}</div>
                            </div>
                        @empty
                            <div class="text-muted small">Lớp chưa được gán khóa học.</div>
                        @endforelse
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-note-sticky me-2 text-primary"></i>Ghi chú từ điểm danh</h6>
                    </div>
                    <div class="card-body">
                        @forelse ($studentProfile['notes'] as $note)
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="fw-bold small">{{ $note['title'] }}</div>
                                    <div class="text-muted small">{{ $note['updated_at'] ? $note['updated_at']->format('d/m/Y') : '' }}</div>
                                </div>
                                <div class="text-muted small mb-1">{{ $note['course_title'] }}</div>
                                <div>{{ $note['value'] }}</div>
                            </div>
                        @empty
                            <div class="text-muted small">
                                Chưa có ghi chú điểm danh. Ghi chú riêng cho từng học sinh cần thêm nơi lưu dữ liệu mới nên chưa được triển khai trong bản không đổi CSDL.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
