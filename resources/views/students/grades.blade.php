@extends('layouts.app')

@section('title', 'Điểm & nhận xét của tôi')

@section('content')
    <style>
        .grades-page {
            color: #0f172a;
        }

        .grades-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .grades-title {
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 4px;
            letter-spacing: 0;
        }

        .grades-subtitle {
            color: #64748b;
            margin: 0;
            font-size: 14px;
        }

        .grades-filter {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .grades-filter label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 6px;
        }

        .grades-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .grades-stat {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
        }

        .grades-stat-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .grades-stat-value {
            font-size: 24px;
            font-weight: 800;
            line-height: 1;
        }

        .grades-stat-note {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 8px;
        }

        .grades-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 18px;
        }

        .grades-panel-head {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .grades-panel-title {
            font-size: 15px;
            font-weight: 800;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .grades-table {
            margin: 0;
            min-width: 760px;
        }

        .grades-table th {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .04em;
            border-bottom-color: #e2e8f0;
            white-space: nowrap;
        }

        .grades-table td {
            vertical-align: middle;
            font-size: 14px;
        }

        .score-pill {
            display: inline-flex;
            align-items: baseline;
            gap: 3px;
            border-radius: 999px;
            padding: 5px 10px;
            font-weight: 800;
            background: #eff6ff;
            color: #1d4ed8;
            white-space: nowrap;
        }

        .score-pill span {
            font-size: 12px;
            color: #64748b;
            font-weight: 700;
        }

        .feedback-card {
            border-top: 1px solid #e2e8f0;
            padding: 14px 16px;
        }

        .feedback-card:first-child {
            border-top: 0;
        }

        .feedback-title {
            font-weight: 800;
            margin-bottom: 4px;
        }

        .feedback-meta {
            color: #64748b;
            font-size: 12.5px;
            margin-bottom: 8px;
        }

        .empty-state {
            padding: 36px 16px;
            text-align: center;
            color: #64748b;
        }

        .empty-state i {
            font-size: 28px;
            opacity: .35;
            display: block;
            margin-bottom: 10px;
        }

        @media (max-width: 991.98px) {
            .grades-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .grades-title {
                font-size: 20px;
            }

            .grades-filter {
                align-items: stretch;
                flex-direction: column;
            }

            .grades-filter .form-select,
            .grades-filter .btn {
                width: 100%;
            }

            .grades-stat-grid {
                grid-template-columns: 1fr;
            }

            .grades-stat {
                padding: 14px;
            }

            .grades-panel-head {
                padding: 12px 14px;
            }
        }
    </style>

    <div class="grades-page">
        <div class="grades-header">
            <div>
                <h1 class="grades-title">Điểm & nhận xét của tôi</h1>
                <p class="grades-subtitle">Theo dõi điểm bài tập, quiz và phản hồi từ giáo viên.</p>
            </div>
        </div>

        <form action="{{ route('students.grades') }}" method="GET" class="grades-filter">
            <div class="flex-grow-1">
                <label for="course_id">Khóa học</label>
                <select name="course_id" id="course_id" class="form-select">
                    <option value="">Tất cả khóa học</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? null) === $course->id)>
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-1"></i>Lọc
            </button>
            <a href="{{ route('students.grades') }}" class="btn btn-light border">
                <i class="fas fa-rotate-left"></i>
            </a>
        </form>

        <div class="grades-stat-grid">
            <div class="grades-stat">
                <div class="grades-stat-label">Điểm trung bình</div>
                <div class="grades-stat-value">{{ $stats['average_score'] !== null ? $stats['average_score'] : '—' }}</div>
                <div class="grades-stat-note">Tính theo thang 10</div>
            </div>
            <div class="grades-stat">
                <div class="grades-stat-label">Bài tập đã chấm</div>
                <div class="grades-stat-value">{{ $stats['graded_assignments'] }}</div>
                <div class="grades-stat-note">TB: {{ $stats['assignment_average'] !== null ? $stats['assignment_average'] . '/10' : '—' }}</div>
            </div>
            <div class="grades-stat">
                <div class="grades-stat-label">Quiz đã làm</div>
                <div class="grades-stat-value">{{ $stats['completed_quizzes'] }}</div>
                <div class="grades-stat-note">TB: {{ $stats['quiz_average'] !== null ? $stats['quiz_average'] . '/10' : '—' }}</div>
            </div>
            <div class="grades-stat">
                <div class="grades-stat-label">Nhận xét giáo viên</div>
                <div class="grades-stat-value">{{ $stats['feedback_count'] }}</div>
                <div class="grades-stat-note">{{ $stats['pending_assignments'] }} bài đang chờ chấm</div>
            </div>
        </div>

        <div class="grades-panel">
            <div class="grades-panel-head">
                <h2 class="grades-panel-title"><i class="fas fa-file-pen text-primary"></i>Bài tập đã nộp</h2>
                <span class="badge bg-primary-subtle text-primary rounded-pill">{{ $assignmentSubmissions->count() }} bài</span>
            </div>
            @if ($assignmentSubmissions->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    Chưa có bài tập nào được nộp trong phạm vi đang xem.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table grades-table align-middle">
                        <thead>
                            <tr>
                                <th>Bài tập</th>
                                <th>Khóa học</th>
                                <th>Ngày nộp</th>
                                <th>Điểm</th>
                                <th>Nhận xét</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assignmentSubmissions as $submission)
                                @php
                                    $assignment = $submission->assignment;
                                    $scale = $assignment?->grading_scale ?: 10;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $assignment?->title ?? 'Bài tập đã bị ẩn' }}</div>
                                        <div class="text-muted small">{{ $assignment?->due_date ? 'Hạn: ' . $assignment->due_date->format('d/m/Y H:i') : 'Không có hạn nộp' }}</div>
                                    </td>
                                    <td>{{ $assignment?->course?->title ?? '—' }}</td>
                                    <td>{{ $submission->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>
                                        @if ($submission->grade !== null)
                                            <span class="score-pill">{{ rtrim(rtrim(number_format((float) $submission->grade, 1), '0'), '.') }}<span>/{{ $scale }}</span></span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning rounded-pill">Chờ chấm</span>
                                        @endif
                                    </td>
                                    <td style="max-width:260px;">
                                        @if (trim((string) $submission->feedback))
                                            <span class="text-muted">{{ \Illuminate\Support\Str::limit($submission->feedback, 90) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($assignment?->course)
                                            <a href="{{ route('courses.show', $assignment->course_id) }}" class="btn btn-sm btn-light border rounded-pill">
                                                Vào khóa học
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="grades-panel">
            <div class="grades-panel-head">
                <h2 class="grades-panel-title"><i class="fas fa-clipboard-check text-success"></i>Quiz đã làm</h2>
                <span class="badge bg-success-subtle text-success rounded-pill">{{ $quizAttempts->count() }} lượt</span>
            </div>
            @if ($quizAttempts->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-clipboard-question"></i>
                    Chưa có quiz nào được hoàn thành trong phạm vi đang xem.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table grades-table align-middle">
                        <thead>
                            <tr>
                                <th>Quiz</th>
                                <th>Khóa học</th>
                                <th>Thời gian làm</th>
                                <th>Điểm</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($quizAttempts as $attempt)
                                <tr>
                                    <td class="fw-bold">{{ $attempt->quiz?->title ?? 'Quiz đã bị ẩn' }}</td>
                                    <td>{{ $attempt->quiz?->course?->title ?? '—' }}</td>
                                    <td>{{ $attempt->completed_at ? \Illuminate\Support\Carbon::parse($attempt->completed_at)->format('d/m/Y H:i') : '—' }}</td>
                                    <td>
                                        <span class="score-pill">{{ rtrim(rtrim(number_format((float) $attempt->score, 1), '0'), '.') }}<span>/10</span></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('quizzes.review', $attempt->id) }}" class="btn btn-sm btn-light border rounded-pill">
                                            Xem lại
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="grades-panel">
            <div class="grades-panel-head">
                <h2 class="grades-panel-title"><i class="fas fa-comment-dots text-info"></i>Nhận xét gần đây</h2>
            </div>
            @if ($recentFeedback->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-message"></i>
                    Chưa có nhận xét nào từ giáo viên.
                </div>
            @else
                @foreach ($recentFeedback as $submission)
                    <div class="feedback-card">
                        <div class="feedback-title">{{ $submission->assignment?->title ?? 'Bài tập' }}</div>
                        <div class="feedback-meta">
                            {{ $submission->assignment?->course?->title ?? '—' }}
                            @if ($submission->grade !== null)
                                · Điểm {{ rtrim(rtrim(number_format((float) $submission->grade, 1), '0'), '.') }}/{{ $submission->assignment?->grading_scale ?: 10 }}
                            @endif
                        </div>
                        <div>{{ $submission->feedback }}</div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection
