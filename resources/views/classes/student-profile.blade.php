@extends('layouts.app')

@section('title', 'Hồ sơ - ' . $student->name)

@push('styles')
    <style>
        :root {
            --lms-blue: var(--sl-primary);
            --lms-blue-light: var(--sl-primary-soft);
            --lms-surface: var(--sl-surface-muted);
            --lms-border: var(--sl-border);
            --lms-text: var(--sl-text);
            --lms-muted: var(--sl-text-muted);
            --lms-danger: var(--sl-danger);
            --lms-danger-light: var(--sl-danger-soft);
            --lms-warning: var(--sl-warning);
            --lms-warning-light: var(--sl-warning-soft);
            --lms-success: var(--sl-success);
            --lms-success-light: var(--sl-success-soft);
            --lms-radius: var(--sl-radius-sm);
            --lms-radius-sm: var(--sl-radius-xs);
        }

        body {
            background: #F1F5F9;
        }

        .lms-page {
            padding: 2rem 2rem 3rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ── Breadcrumb ── */
        .lms-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--lms-muted);
            margin-bottom: 6px;
        }

        .lms-breadcrumb a {
            color: var(--lms-muted);
            text-decoration: none;
        }

        .lms-breadcrumb a:hover {
            color: var(--lms-blue);
        }

        .lms-breadcrumb-sep {
            font-size: 10px;
            color: #CBD5E1;
        }

        /* ── Profile header ── */
        .lms-profile-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1.5rem;
            margin-bottom: 1.75rem;
            flex-wrap: wrap;
        }

        .lms-profile-identity {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .lms-profile-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--lms-blue-light);
            color: var(--lms-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .lms-profile-name {
            font-size: 22px;
            font-weight: 700;
            color: var(--lms-text);
            margin: 0 0 4px;
            letter-spacing: -0.3px;
        }

        .lms-profile-meta {
            font-size: 13px;
            color: var(--lms-muted);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .lms-profile-meta i {
            font-size: 12px;
        }

        /* ── Buttons ── */
        .lms-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: var(--lms-radius-sm);
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            border: 1px solid transparent;
            text-decoration: none;
            white-space: nowrap;
        }

        .lms-btn-outline {
            background: #fff;
            color: var(--lms-text);
            border-color: var(--lms-border);
        }

        .lms-btn-outline:hover {
            background: var(--lms-surface);
            color: var(--lms-text);
            border-color: #94A3B8;
        }

        .lms-btn-primary {
            background: var(--lms-blue);
            color: #fff;
            border-color: var(--lms-blue);
        }

        .lms-btn-primary:hover {
            background: #1e40af;
            color: #fff;
        }

        /* ── Alert banner ── */
        .lms-alert-banner {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            border-radius: var(--lms-radius);
            padding: 14px 18px;
            margin-bottom: 1.5rem;
        }

        .lms-alert-banner-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #92400E;
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 10px;
        }

        .lms-alert-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .lms-alert-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
        }

        .lms-alert-tag.danger {
            background: var(--lms-danger-light);
            color: var(--lms-danger);
        }

        .lms-alert-tag.warning {
            background: var(--lms-warning-light);
            color: var(--lms-warning);
        }

        .lms-alert-tag.secondary {
            background: var(--lms-surface);
            color: var(--lms-muted);
            border: 1px solid var(--lms-border);
        }

        /* ── Stat cards ── */
        .lms-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 1.75rem;
        }

        .lms-stat {
            background: #fff;
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius);
            padding: 18px 20px;
        }

        .lms-stat-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--lms-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .lms-stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--lms-text);
            line-height: 1;
            margin-bottom: 5px;
        }

        .lms-stat-sub {
            font-size: 12px;
            color: var(--lms-muted);
            margin-top: 2px;
        }

        .lms-prog-bar {
            height: 6px;
            background: #E2E8F0;
            border-radius: 3px;
            margin: 10px 0 6px;
            overflow: hidden;
        }

        .lms-prog-fill {
            height: 100%;
            border-radius: 3px;
            background: var(--lms-blue);
        }

        /* ── Card ── */
        .lms-card {
            background: #fff;
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius);
            overflow: hidden;
        }

        .lms-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--lms-border);
        }

        .lms-card-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--lms-text);
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .lms-card-title i {
            color: var(--lms-blue);
            font-size: 15px;
        }

        /* ── Filter ── */
        .lms-filter-card {
            background: #fff;
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius);
            padding: 16px 20px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .lms-filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .lms-filter-group label {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--lms-muted);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .lms-select {
            height: 36px;
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius-sm);
            padding: 0 32px 0 12px;
            font-size: 13.5px;
            background: var(--lms-surface) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 10px center;
            appearance: none;
            color: var(--lms-text);
            cursor: pointer;
            transition: border-color 0.15s;
        }

        .lms-select:focus {
            outline: none;
            border-color: var(--lms-blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }

        .lms-btn-reset {
            height: 36px;
            width: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--lms-border);
            border-radius: var(--lms-radius-sm);
            background: #fff;
            color: var(--lms-muted);
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
        }

        .lms-btn-reset:hover {
            background: var(--lms-surface);
            color: var(--lms-text);
            border-color: #94A3B8;
        }

        /* ── Table ── */
        .lms-table-wrap {
            overflow-x: auto;
        }

        .lms-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 680px;
        }

        .lms-table thead th {
            padding: 11px 16px;
            background: var(--lms-surface);
            font-size: 11px;
            font-weight: 700;
            color: var(--lms-muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid var(--lms-border);
            white-space: nowrap;
        }

        .lms-table tbody tr {
            border-bottom: 1px solid #F1F5F9;
            transition: background 0.12s;
        }

        .lms-table tbody tr:last-child {
            border-bottom: none;
        }

        .lms-table tbody tr:hover {
            background: #F8FAFC;
        }

        .lms-table td {
            padding: 13px 16px;
            vertical-align: middle;
            font-size: 13.5px;
        }

        .lms-table .td-title {
            font-weight: 600;
            color: var(--lms-text);
            font-size: 13.5px;
        }

        .lms-table .td-muted {
            font-size: 12.5px;
            color: var(--lms-muted);
        }

        /* ── Badges ── */
        .lms-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 100px;
            font-size: 11.5px;
            font-weight: 600;
        }

        .lms-badge-danger {
            background: var(--lms-danger-light);
            color: var(--lms-danger);
        }

        .lms-badge-success {
            background: var(--lms-success-light);
            color: var(--lms-success);
        }

        .lms-badge-warning {
            background: var(--lms-warning-light);
            color: var(--lms-warning);
        }

        /* ── Side panels ── */
        .lms-course-item {
            padding-bottom: 14px;
            margin-bottom: 14px;
            border-bottom: 1px solid var(--lms-border);
        }

        .lms-course-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .lms-course-item-title {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--lms-text);
            margin-bottom: 3px;
        }

        .lms-course-item-desc {
            font-size: 12px;
            color: var(--lms-muted);
            line-height: 1.5;
        }

        .lms-note-item {
            padding-bottom: 14px;
            margin-bottom: 14px;
            border-bottom: 1px solid var(--lms-border);
        }

        .lms-note-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .lms-note-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 3px;
        }

        .lms-note-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--lms-text);
        }

        .lms-note-date {
            font-size: 11.5px;
            color: #94A3B8;
            white-space: nowrap;
        }

        .lms-note-course {
            font-size: 12px;
            color: var(--lms-muted);
            margin-bottom: 4px;
        }

        .lms-note-content {
            font-size: 13px;
            color: var(--lms-text);
        }

        /* ── Empty ── */
        .lms-td-empty {
            text-align: center;
            padding: 32px 20px !important;
            color: var(--lms-muted);
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .lms-page {
                padding: 1rem 1rem 2rem;
            }

            .lms-stats {
                grid-template-columns: 1fr 1fr;
            }

            .lms-filter-card {
                flex-direction: column;
            }

            .lms-filter-group,
            .lms-filter-group select {
                width: 100%;
            }

            .lms-profile-header {
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .lms-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="lms-page">

        {{-- Profile header --}}
        <div class="lms-profile-header">
            <div class="lms-profile-identity">
                <div class="lms-profile-avatar">{{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}</div>
                <div>
                    <nav class="lms-breadcrumb">
                        <a href="{{ route('classes.students.index', $classroom->id) }}">{{ $classroom->name }}</a>
                        <span class="lms-breadcrumb-sep">›</span>
                        <span>Hồ sơ học sinh</span>
                    </nav>
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
            <div>
                <a href="{{ route('classes.students.index', $classroom->id) }}" class="lms-btn lms-btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại lớp
                </a>
            </div>
        </div>

        {{-- Course filter --}}
        <div class="lms-filter-card">
            <form action="{{ route('classes.students.show', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                method="GET" style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap; width:100%;">
                <div class="lms-filter-group" style="flex:2; min-width:200px;">
                    <label>Xem theo khóa học</label>
                    <select name="course_id" class="lms-select" style="width:100%;">
                        <option value="">Tất cả khóa học của lớp</option>
                        @foreach ($availableCourses as $course)
                            <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? '') == $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <button type="submit" class="lms-btn lms-btn-primary" style="height:36px; padding:0 14px;">
                        <i class="fa-solid fa-filter"></i> Lọc
                    </button>
                    <a href="{{ route('classes.students.show', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                        class="lms-btn-reset" title="Xóa bộ lọc">
                        <i class="fa-solid fa-rotate-left" style="font-size:13px;"></i>
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
        <div class="lms-stats">
            <div class="lms-stat">
                <div class="lms-stat-label">Tiến độ bài học</div>
                <div class="lms-stat-value">{{ $studentProfile['lesson_progress'] }}%</div>
                <div class="lms-prog-bar">
                    <div class="lms-prog-fill" style="width:{{ $studentProfile['lesson_progress'] }}%;"></div>
                </div>
                <div class="lms-stat-sub">{{ $studentProfile['lesson_completed'] }}/{{ $studentProfile['lesson_total'] }}
                    bài hoàn thành</div>
            </div>
            <div class="lms-stat">
                <div class="lms-stat-label">Bài tập</div>
                <div class="lms-stat-value">{{ $studentProfile['assignment_submitted_count'] }}<span
                        style="font-size:16px; font-weight:500; color:var(--lms-muted);">/{{ $studentProfile['assignment_total'] }}</span>
                </div>
                <div class="lms-stat-sub">{{ $studentProfile['assignment_missing_count'] }} thiếu ·
                    {{ $studentProfile['assignment_overdue_missing_count'] }} quá hạn</div>
                <div class="lms-stat-sub">TB điểm: {{ $studentProfile['assignment_average'] ?? 'N/A' }}</div>
            </div>
            <div class="lms-stat">
                <div class="lms-stat-label">Quiz TB</div>
                <div class="lms-stat-value">{{ $studentProfile['quiz_average'] ?? '—' }}</div>
                <div class="lms-stat-sub">
                    {{ $studentProfile['quiz_attempted_count'] }}/{{ $studentProfile['quiz_total'] }} đã làm</div>
                <div class="lms-stat-sub">{{ $studentProfile['quiz_pending_count'] }} chưa làm</div>
            </div>
            <div class="lms-stat">
                <div class="lms-stat-label">Điểm danh</div>
                <div class="lms-stat-value"
                    style="{{ $studentProfile['absence_count'] > 0 ? 'color:var(--lms-danger)' : '' }}">
                    {{ $studentProfile['absence_count'] }}</div>
                <div class="lms-stat-sub">Lượt vắng/nghỉ</div>
                <div class="lms-stat-sub">{{ $studentProfile['note_count'] }} ghi chú điểm danh</div>
            </div>
        </div>

        {{-- Main content grid --}}
        <div style="display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start;">

            {{-- Left: Tables --}}
            <div style="display:flex; flex-direction:column; gap:20px;">

                {{-- Assignments --}}
                <div class="lms-card">
                    <div class="lms-card-header">
                        <h2 class="lms-card-title"><i class="fa-solid fa-list-check"></i> Theo dõi bài tập</h2>
                    </div>
                    <div class="lms-table-wrap">
                        <table class="lms-table">
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
                                        <td class="td-title">{{ $assignment['title'] }}</td>
                                        <td class="td-muted">{{ $assignment['course_title'] }}</td>
                                        <td style="font-size:12.5px; color:var(--lms-muted);">
                                            {{ $assignment['due_date'] ? \Illuminate\Support\Carbon::parse($assignment['due_date'])->format('d/m/Y H:i') : 'Không có' }}
                                        </td>
                                        <td>
                                            @if ($assignment['status'] === 'submitted')
                                                <span class="lms-badge lms-badge-success">Đã nộp</span>
                                            @elseif ($assignment['is_overdue'])
                                                <span class="lms-badge lms-badge-danger">Quá hạn</span>
                                            @else
                                                <span class="lms-badge lms-badge-warning">Chưa nộp</span>
                                            @endif
                                        </td>
                                        <td style="font-size:13px; color:var(--lms-muted);">
                                            {{ $assignment['grade'] ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="lms-td-empty">Chưa có bài tập trong phạm vi đang xem.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Quiz --}}
                <div class="lms-card">
                    <div class="lms-card-header">
                        <h2 class="lms-card-title"><i class="fa-solid fa-clipboard-check"></i> Kết quả quiz</h2>
                    </div>
                    <div class="lms-table-wrap">
                        <table class="lms-table">
                            <thead>
                                <tr>
                                    <th>Quiz</th>
                                    <th>Khóa học</th>
                                    <th>Trạng thái</th>
                                    <th>Điểm</th>
                                    <th>Hoàn thành</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($studentProfile['quiz_details'] as $quiz)
                                    <tr>
                                        <td class="td-title">{{ $quiz['title'] }}</td>
                                        <td class="td-muted">{{ $quiz['course_title'] }}</td>
                                        <td>
                                            @if ($quiz['status'] === 'attempted')
                                                <span class="lms-badge lms-badge-success">Đã làm</span>
                                            @else
                                                <span class="lms-badge lms-badge-warning">Chưa làm</span>
                                            @endif
                                        </td>
                                        <td style="font-size:13px; color:var(--lms-muted);">{{ $quiz['score'] ?? '—' }}
                                        </td>
                                        <td style="font-size:12.5px; color:var(--lms-muted);">
                                            {{ $quiz['completed_at'] ? \Illuminate\Support\Carbon::parse($quiz['completed_at'])->format('d/m/Y H:i') : '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="lms-td-empty">Chưa có quiz trong phạm vi đang xem.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right sidebar --}}
            <div style="display:flex; flex-direction:column; gap:20px;">

                {{-- Courses --}}
                <div class="lms-card">
                    <div class="lms-card-header">
                        <h2 class="lms-card-title"><i class="fa-solid fa-book-open"></i> Khóa học đang theo dõi</h2>
                    </div>
                    <div style="padding:16px 20px;">
                        @forelse ($studentProfile['courses'] as $course)
                            <div class="lms-course-item">
                                <div class="lms-course-item-title">{{ $course->title }}</div>
                                <div class="lms-course-item-desc">
                                    {{ \Illuminate\Support\Str::limit($course->description ?? 'Chưa có mô tả', 110) }}
                                </div>
                            </div>
                        @empty
                            <div style="font-size:13px; color:var(--lms-muted);">Lớp chưa được gán khóa học.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Notes --}}
                <div class="lms-card">
                    <div class="lms-card-header">
                        <h2 class="lms-card-title"><i class="fa-solid fa-note-sticky"></i> Ghi chú điểm danh</h2>
                    </div>
                    <div style="padding:16px 20px;">
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
                            <div style="font-size:13px; color:var(--lms-muted); line-height:1.6;">
                                Chưa có ghi chú điểm danh. Tính năng này cần bảng dữ liệu mới và chưa được triển khai trong
                                bản hiện tại.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media (max-width: 1024px) {
            .lms-page>div:last-child {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
@endsection
