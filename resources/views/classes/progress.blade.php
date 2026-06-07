@extends('layouts.app')

@section('title', 'Theo dõi tiến độ - ' . $classroom->name)

@section('content')
    <style>
        .progress-stat-card,
        .course-report-card {
            min-height: 100%;
        }

        .progress-table {
            min-width: 1040px;
        }

        .student-alerts {
            max-width: 280px;
        }

        .lesson-check-list {
            max-height: 260px;
            overflow-y: auto;
        }

        @media (max-width: 767.98px) {
            .progress-container {
                padding-left: .75rem !important;
                padding-right: .75rem !important;
            }

            .progress-filter-form .form-select,
            .progress-filter-form .btn,
            .progress-header-actions,
            .progress-header-actions .btn {
                width: 100%;
            }

            .student-alerts {
                max-width: none;
            }


        }

        .modal.fade .modal-dialog {
            transition: transform .3s ease-out;
            transform: translate(0, 190px) !important;
        }
    </style>

    <div class="container-fluid py-4 progress-container">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('classes.index') }}" class="text-decoration-none">Quản lý lớp học</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $classroom->code }}</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-1">Dashboard tiến độ: {{ $classroom->name }}</h3>
                <div class="text-muted small">
                    <i class="fas fa-chalkboard-teacher me-1"></i>{{ $classroom->teacher->name }}
                    <span class="mx-2">|</span>
                    <i class="fas fa-users me-1"></i>{{ $classReport['student_count'] }} học sinh
                </div>
            </div>
            <div class="progress-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('classes.students.index', $classroom->id) }}" class="btn btn-light border">
                    <i class="fas fa-user-graduate me-1"></i>Danh sách học sinh
                </a>
                <a href="{{ route('classes.index') }}" class="btn btn-light border">
                    <i class="fas fa-arrow-left me-1"></i>Quay lại lớp học
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body">
                <form action="{{ route('classes.progress', $classroom->id) }}" method="GET"
                    class="progress-filter-form row g-2 align-items-end">
                    <div class="col-12 col-md-5 col-xl-4">
                        <label class="form-label small text-muted mb-1">Khóa học</label>
                        <select name="course_id" class="form-select bg-light border-0 shadow-none">
                            <option value="">Tất cả khóa học của lớp</option>
                            @foreach ($availableCourses as $course)
                                <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? '') == $course->id)>
                                    {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-auto">
                        <div class="form-check mt-3">
                            <input type="checkbox" name="attention_only" value="1" class="form-check-input"
                                id="attentionOnly" @checked($filters['attention_only'])>
                            <label for="attentionOnly" class="form-check-label">Chỉ học sinh cần chú ý</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="fas fa-filter me-1"></i>Lọc
                        </button>
                        <a href="{{ route('classes.progress', $classroom->id) }}" class="btn btn-light border">
                            <i class="fas fa-rotate-left me-1"></i>Xóa lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="progress-stat-card bg-white border rounded-3 p-3 shadow-sm">
                    <div class="text-muted small mb-1">Tỷ lệ hoàn thành bài học</div>
                    <div class="h4 fw-bold mb-2">{{ $classReport['lesson_completion_rate'] }}%</div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar" style="width: {{ $classReport['lesson_completion_rate'] }}%;"></div>
                    </div>
                    <div class="text-muted small mt-2">
                        {{ $classReport['lesson_completed'] }}/{{ $classReport['lesson_total'] }} lượt bài học</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="progress-stat-card bg-white border rounded-3 p-3 shadow-sm">
                    <div class="text-muted small mb-1">Tỷ lệ nộp bài</div>
                    <div class="h4 fw-bold mb-2">{{ $classReport['assignment_submission_rate'] }}%</div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success"
                            style="width: {{ $classReport['assignment_submission_rate'] }}%;"></div>
                    </div>
                    <div class="text-muted small mt-2">
                        {{ $classReport['assignment_submitted'] }}/{{ $classReport['assignment_total'] }} lượt bài tập
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="progress-stat-card bg-white border rounded-3 p-3 shadow-sm">
                    <div class="text-muted small mb-1">Điểm trung bình</div>
                    <div class="h4 fw-bold mb-0">{{ $classReport['score_average'] ?? 'N/A' }}</div>
                    <div class="text-muted small mt-1">Từ điểm bài tập và quiz đã có</div>
                    <div class="text-muted small">Còn thiếu {{ $classReport['missing_assignment_total'] }} lượt bài tập
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="progress-stat-card bg-white border rounded-3 p-3 shadow-sm">
                    <div class="text-muted small mb-1">Số buổi vắng</div>
                    <div class="h4 fw-bold text-danger mb-0">{{ $classReport['absence_total'] }}</div>
                    <div class="text-muted small mt-1">{{ $classReport['needs_attention_count'] }} học sinh cần chú ý</div>
                    <div class="text-muted small">{{ $classReport['pending_quiz_total'] }} lượt quiz chưa làm</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            @forelse ($courseReports as $courseReport)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="course-report-card bg-white border rounded-3 p-3 shadow-sm">
                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <div class="fw-bold">{{ $courseReport['course']->title }}</div>
                            <span class="badge bg-light text-dark border">{{ $courseReport['report']['student_count'] }}
                                HS</span>
                        </div>
                        <div class="small text-muted mb-2">Bài học:
                            {{ $courseReport['report']['lesson_completion_rate'] }}%</div>
                        <div class="progress mb-3" style="height: 7px;">
                            <div class="progress-bar"
                                style="width: {{ $courseReport['report']['lesson_completion_rate'] }}%;"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 small text-muted">
                            <span><i class="fas fa-paperclip me-1"></i>Nộp bài
                                {{ $courseReport['report']['assignment_submission_rate'] }}%</span>
                            <span><i class="fas fa-star me-1"></i>TB
                                {{ $courseReport['report']['score_average'] ?? 'N/A' }}</span>
                            <span><i
                                    class="fas fa-user-clock me-1"></i>{{ $courseReport['report']['needs_attention_count'] }}
                                cần chú ý</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info border-0 shadow-sm mb-0">Lớp chưa được gán khóa học.</div>
                </div>
            @endforelse
        </div>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div
                class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="fas fa-chart-line me-2 text-primary"></i>Tiến độ từng học sinh</h6>
                <span class="badge bg-light text-dark border">{{ $studentProgress->count() }} kết quả</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table progress-table align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="px-4 py-3 border-0">Học sinh</th>
                                <th class="px-4 py-3 border-0">Bài học</th>
                                <th class="px-4 py-3 border-0">Bài tập</th>
                                <th class="px-4 py-3 border-0">Quiz</th>
                                <th class="px-4 py-3 border-0">Điểm TB</th>
                                <th class="px-4 py-3 border-0">Vắng</th>
                                <th class="px-4 py-3 border-0">Cần chú ý</th>
                                <th class="px-4 py-3 border-0 text-end">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($studentProgress as $summary)
                                @php
                                    $student = $summary['student'];
                                    $modalId = 'progressStudentModal' . $student->id;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="fw-bold">{{ $student->name }}</div>
                                        <div class="text-muted small">{{ $student->email }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="fw-bold">
                                            {{ $summary['lesson_completed'] }}/{{ $summary['lesson_total'] }}</div>
                                        <div class="progress mt-1" style="height: 7px;">
                                            <div class="progress-bar" style="width: {{ $summary['lesson_progress'] }}%;">
                                            </div>
                                        </div>
                                        <div class="text-muted small mt-1">{{ $summary['lesson_progress'] }}%</div>
                                    </td>
                                    <td class="px-4 py-3 small">
                                        <div class="fw-bold">
                                            {{ $summary['assignment_submitted_count'] }}/{{ $summary['assignment_total'] }}
                                            đã nộp</div>
                                        <div class="text-muted">{{ $summary['assignment_missing_count'] }} thiếu</div>
                                    </td>
                                    <td class="px-4 py-3 small">
                                        <div class="fw-bold">
                                            {{ $summary['quiz_attempted_count'] }}/{{ $summary['quiz_total'] }} đã làm
                                        </div>
                                        <div class="text-muted">{{ $summary['quiz_pending_count'] }} chưa làm</div>
                                    </td>
                                    <td class="px-4 py-3 fw-bold">{{ $summary['score_average'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $summary['absence_count'] }}</td>
                                    <td class="px-4 py-3">
                                        @if ($summary['needs_attention'])
                                            <span class="badge bg-danger mb-2">Cần chú ý</span>
                                        @else
                                            <span class="badge bg-success mb-2">Ổn định</span>
                                        @endif
                                        <div class="student-alerts">
                                            @forelse ($summary['alerts'] as $alert)
                                                <div class="small text-{{ $alert['level'] }} mb-1">
                                                    <i class="fas fa-circle-exclamation me-1"></i>{{ $alert['text'] }}
                                                </div>
                                            @empty
                                                <div class="small text-muted">Không có cảnh báo</div>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#{{ $modalId }}">
                                            <i class="fas fa-list-check me-1"></i>Chi tiết
                                        </button>
                                        <a href="{{ route('classes.students.show', ['classId' => $classroom->id, 'studentId' => $student->id, 'course_id' => $filters['course_id']]) }}"
                                            class="btn btn-sm btn-light border">
                                            Hồ sơ
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        Không có học sinh phù hợp với bộ lọc hiện tại.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @foreach ($studentProgress as $summary)
            @php
                $student = $summary['student'];
                $modalId = 'progressStudentModal' . $student->id;
                $missingAssignments = $summary['assignment_details']->where('status', 'missing');
                $completedLessons = $summary['lesson_details']->where('is_completed', true);
                $attemptedQuizzes = $summary['quiz_details']->where('status', 'attempted');
            @endphp
            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold">{{ $student->name }}</h5>
                                <div class="text-muted small">{{ $student->email }}</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12 col-lg-4">
                                    <h6 class="fw-bold">Bài học đã hoàn thành</h6>
                                    <div class="lesson-check-list border rounded-3 p-3">
                                        @forelse ($completedLessons as $lesson)
                                            <div class="border-bottom pb-2 mb-2">
                                                <div class="fw-medium small">{{ $lesson['title'] }}</div>
                                                <div class="text-muted small">{{ $lesson['course_title'] }} ·
                                                    {{ $lesson['module_title'] }}</div>
                                            </div>
                                        @empty
                                            <div class="text-muted small">Chưa hoàn thành bài học nào.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <h6 class="fw-bold">Quiz đã làm</h6>
                                    <div class="lesson-check-list border rounded-3 p-3">
                                        @forelse ($attemptedQuizzes as $quiz)
                                            <div class="border-bottom pb-2 mb-2">
                                                <div class="fw-medium small">{{ $quiz['title'] }}</div>
                                                <div class="text-muted small">{{ $quiz['course_title'] }} · Điểm
                                                    {{ $quiz['score'] ?? 'N/A' }}</div>
                                            </div>
                                        @empty
                                            <div class="text-muted small">Chưa làm quiz nào.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <h6 class="fw-bold">Bài tập còn thiếu</h6>
                                    <div class="lesson-check-list border rounded-3 p-3">
                                        @forelse ($missingAssignments as $assignment)
                                            <div class="border-bottom pb-2 mb-2">
                                                <div class="fw-medium small">{{ $assignment['title'] }}</div>
                                                <div class="text-muted small">{{ $assignment['course_title'] }}</div>
                                                @if ($assignment['is_overdue'])
                                                    <span class="badge bg-danger">Quá hạn</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Chưa nộp</span>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="text-muted small">Không còn bài tập thiếu.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
