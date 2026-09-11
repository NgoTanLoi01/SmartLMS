@extends('layouts.app')

@section('title', 'Hồ sơ - ' . $student->name)

@push('styles')
    @vite('resources/css/pages/class-students.css')
@endpush

@section('content')
    <div class="lms-page student-profile-page">

        {{-- Profile header --}}
        <section class="lms-profile-header student-profile-hero">
            <span class="student-profile-hero__accent" aria-hidden="true"></span>
            <div class="lms-profile-identity">
                <div class="lms-profile-avatar">{{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}</div>
                <div>
                    <div class="student-profile-eyebrow"><i class="fa-solid fa-user-graduate"></i> Hồ sơ học tập</div>
                    <h1 class="lms-profile-name">{{ $student->name }}</h1>
                    <div class="lms-profile-meta">
                        @if ($student->username)
                            <span><i class="fa-solid fa-id-badge"></i> {{ $student->username }}</span>
                        @endif
                        @if ($student->student_code)
                            <span><i class="fa-solid fa-hashtag"></i> {{ $student->student_code }}</span>
                        @endif
                        <span><i class="fa-solid fa-envelope"></i> {{ $student->email }}</span>
                        <span><i class="fa-solid fa-chalkboard-teacher"></i> {{ $classroom->teacher->name }}</span>
                    </div>
                </div>
            </div>
            <div class="student-profile-hero__actions">
                <a href="{{ route('classes.students.index', $classroom->id) }}" class="lms-btn lms-btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại lớp
                </a>
            </div>
        </section>

        {{-- Course filter --}}
        <div class="lms-filter-card student-profile-filter">
            <form action="{{ route('classes.students.show', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                method="GET" class="student-profile-filter__form">
                <div class="lms-filter-group student-profile-filter__field">
                    <label for="profile-course">Xem theo khóa học</label>
                    <select id="profile-course" name="course_id" class="lms-select">
                        <option value="">Tất cả khóa học của lớp</option>
                        @foreach ($availableCourses as $course)
                            <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? '') == $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lms-filter-actions student-profile-filter__actions">
                    <button type="submit" class="lms-btn lms-btn-primary student-filter-submit">
                        <i class="fa-solid fa-filter"></i> Lọc
                    </button>
                    <a href="{{ route('classes.students.show', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                        class="lms-btn-reset" title="Xóa bộ lọc">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- Alerts --}}
        @if (count($studentProfile['alerts']) > 0)
            <div class="lms-alert-banner">
                <div class="lms-alert-banner-title">
                    <i class="fa-solid fa-triangle-exclamation"></i> Cảnh báo cần theo dõi
                </div>
                <div class="lms-alert-tags">
                    @foreach ($studentProfile['alerts'] as $alert)
                        <span class="lms-alert-tag {{ $alert['level'] }}">{{ $alert['text'] }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Stats --}}
        <div class="lms-stats student-profile-stats">
            <div class="lms-stat student-profile-stat">
                <span class="student-profile-stat__icon"><i class="fa-solid fa-book-open"></i></span>
                <div class="lms-stat-label">Tiến độ bài học</div>
                <div class="lms-stat-value">{{ $studentProfile['lesson_progress'] }}%</div>
                <div class="lms-prog-bar">
                    <div class="lms-prog-fill" style="width:{{ $studentProfile['lesson_progress'] }}%;"></div>
                </div>
                <div class="lms-stat-sub">{{ $studentProfile['lesson_completed'] }}/{{ $studentProfile['lesson_total'] }}
                    bài hoàn thành</div>
            </div>
            <div class="lms-stat student-profile-stat warning">
                <span class="student-profile-stat__icon"><i class="fa-solid fa-list-check"></i></span>
                <div class="lms-stat-label">Bài tập</div>
                <div class="lms-stat-value">{{ $studentProfile['assignment_submitted_count'] }}<span
                        class="student-profile-stat__ratio">/{{ $studentProfile['assignment_total'] }}</span>
                </div>
                <div class="lms-stat-sub">{{ $studentProfile['assignment_missing_count'] }} thiếu ·
                    {{ $studentProfile['assignment_overdue_missing_count'] }} quá hạn</div>
                <div class="lms-stat-sub">Điểm trung bình: {{ $studentProfile['assignment_average'] ?? 'Chưa có' }}</div>
            </div>
            <div class="lms-stat student-profile-stat success">
                <span class="student-profile-stat__icon"><i class="fa-solid fa-clipboard-check"></i></span>
                <div class="lms-stat-label">Điểm kiểm tra trung bình</div>
                <div class="lms-stat-value">{{ $studentProfile['quiz_average'] ?? '—' }}</div>
                <div class="lms-stat-sub">
                    {{ $studentProfile['quiz_attempted_count'] }}/{{ $studentProfile['quiz_total'] }} đã làm</div>
                <div class="lms-stat-sub">{{ $studentProfile['quiz_pending_count'] }} chưa làm</div>
            </div>
            <div class="lms-stat student-profile-stat {{ $studentProfile['absence_count'] > 0 ? 'danger' : 'info' }}">
                <span class="student-profile-stat__icon"><i class="fa-solid fa-calendar-check"></i></span>
                <div class="lms-stat-label">Điểm danh</div>
                <div class="lms-stat-value">
                    {{ $studentProfile['absence_count'] }}</div>
                <div class="lms-stat-sub">Lượt vắng/nghỉ</div>
                <div class="lms-stat-sub">{{ $studentProfile['note_count'] }} ghi chú điểm danh</div>
            </div>
        </div>

        {{-- Main content grid --}}
        <div class="student-profile-layout">

            {{-- Left: Tables --}}
            <div class="student-profile-main">

                {{-- Assignments --}}
                <div class="lms-card">
                    <div class="lms-card-header">
                        <h2 class="lms-card-title"><i class="fa-solid fa-list-check"></i> Theo dõi bài tập</h2>
                    </div>
                    <div class="lms-table-wrap">
                        <table class="lms-table student-detail-table">
                            <thead>
                                <tr>
                                    <th>Bài tập</th>
                                    <th>Khóa học</th>
                                    <th>Hạn nộp</th>
                                    <th>Trạng thái</th>
                                    <th>Điểm</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($studentProfile['assignment_details'] as $assignment)
                                    <tr>
                                        <td class="td-title" data-label="Bài tập">{{ $assignment['title'] }}</td>
                                        <td class="td-muted" data-label="Khóa học">{{ $assignment['course_title'] }}</td>
                                        <td class="student-detail-date" data-label="Hạn nộp">
                                            {{ $assignment['due_date'] ? \Illuminate\Support\Carbon::parse($assignment['due_date'])->format('d/m/Y H:i') : 'Không có' }}
                                        </td>
                                        <td data-label="Trạng thái">
                                            @if ($assignment['status'] === 'submitted')
                                                <span class="lms-badge lms-badge-success">Đã nộp</span>
                                            @elseif ($assignment['is_overdue'])
                                                <span class="lms-badge lms-badge-danger">Quá hạn</span>
                                            @else
                                                <span class="lms-badge lms-badge-warning">Chưa nộp</span>
                                            @endif
                                        </td>
                                        <td class="student-detail-score" data-label="Điểm">
                                            {{ $assignment['grade'] ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="lms-td-empty student-detail-empty">Chưa có bài tập trong phạm vi đang xem.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Quiz --}}
                <div class="lms-card">
                    <div class="lms-card-header">
                        <h2 class="lms-card-title"><i class="fa-solid fa-clipboard-check"></i> Kết quả bài kiểm tra</h2>
                    </div>
                    <div class="lms-table-wrap">
                        <table class="lms-table student-detail-table">
                            <thead>
                                <tr>
                                    <th>Bài kiểm tra</th>
                                    <th>Khóa học</th>
                                    <th>Trạng thái</th>
                                    <th>Điểm</th>
                                    <th>Hoàn thành</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($studentProfile['quiz_details'] as $quiz)
                                    <tr>
                                        <td class="td-title" data-label="Bài kiểm tra">{{ $quiz['title'] }}</td>
                                        <td class="td-muted" data-label="Khóa học">{{ $quiz['course_title'] }}</td>
                                        <td data-label="Trạng thái">
                                            @if ($quiz['status'] === 'attempted')
                                                <span class="lms-badge lms-badge-success">Đã làm</span>
                                            @else
                                                <span class="lms-badge lms-badge-warning">Chưa làm</span>
                                            @endif
                                        </td>
                                        <td class="student-detail-score" data-label="Điểm">{{ $quiz['score'] ?? '—' }}
                                        </td>
                                        <td class="student-detail-date" data-label="Hoàn thành">
                                            {{ $quiz['completed_at'] ? \Illuminate\Support\Carbon::parse($quiz['completed_at'])->format('d/m/Y H:i') : '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="lms-td-empty student-detail-empty">Chưa có bài kiểm tra trong phạm vi đang xem.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right sidebar --}}
            <aside class="student-profile-aside">

                {{-- Courses --}}
                <div class="lms-card">
                    <div class="lms-card-header">
                        <h2 class="lms-card-title"><i class="fa-solid fa-book-open"></i> Khóa học đang theo dõi</h2>
                    </div>
                    <div class="student-profile-panel-body">
                        @forelse ($studentProfile['courses'] as $course)
                            <div class="lms-course-item">
                                <div class="lms-course-item-title">{{ $course->title }}</div>
                                <div class="lms-course-item-desc">
                                    {{ \Illuminate\Support\Str::limit($course->description ?? 'Chưa có mô tả', 110) }}
                                </div>
                            </div>
                        @empty
                            <div class="student-profile-panel-empty">Lớp chưa được gán khóa học.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Notes --}}
                <div class="lms-card">
                    <div class="lms-card-header">
                        <h2 class="lms-card-title"><i class="fa-solid fa-note-sticky"></i> Ghi chú điểm danh</h2>
                    </div>
                    <div class="student-profile-panel-body">
                        @forelse ($studentProfile['notes'] as $note)
                            <div class="lms-note-item">
                                <div class="lms-note-header">
                                    <span class="lms-note-title">{{ $note['title'] }}</span>
                                    <span
                                        class="lms-note-date">{{ $note['updated_at'] ? $note['updated_at']->format('d/m/Y') : '' }}</span>
                                </div>
                                <div class="lms-note-course">{{ $note['course_title'] }}</div>
                                <div class="lms-note-content">{{ $note['value'] }}</div>
                            </div>
                        @empty
                            <div class="student-profile-panel-empty">
                                Chưa có ghi chú điểm danh. Tính năng này cần bảng dữ liệu mới và chưa được triển khai trong
                                bản hiện tại.
                            </div>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
