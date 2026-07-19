@extends('layouts.app')

@section('title', 'Xem trước đề thi: ' . $quiz->title)

@push('styles')
    <style>
        :root {
            --qp-purple: var(--sl-primary);
            --qp-purple-light: var(--sl-primary-soft);
            --qp-purple-mid: var(--sl-primary-subtle);
            --qp-border: var(--sl-border);
            --qp-easy: var(--sl-success);
            --qp-easy-bg: var(--sl-success-soft);
            --qp-easy-border: var(--sl-success-border);
            --qp-med: var(--sl-warning);
            --qp-med-bg: var(--sl-warning-soft);
            --qp-med-border: var(--sl-warning-border);
            --qp-hard: var(--sl-danger);
            --qp-hard-bg: var(--sl-danger-soft);
            --qp-hard-border: var(--sl-danger-border);
            --qp-text: var(--sl-text);
            --qp-muted: var(--sl-text-muted);
            --qp-surface: var(--sl-surface);
            --qp-bg: var(--sl-bg);
            --qp-radius: var(--sl-radius-md);
            --qp-shadow: var(--sl-shadow-sm);
        }

        /* ── Layout ── */
        .qp-wrap {
            background: var(--qp-bg);
            min-height: 100vh;
            padding: 28px 0 60px;
        }

        /* ── Breadcrumb ── */
        .qp-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.83rem;
            font-weight: 500;
            color: var(--qp-purple);
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 99px;
            border: 1.5px solid var(--qp-border);
            background: var(--qp-surface);
            transition: background 0.15s, box-shadow 0.15s;
            margin-bottom: 18px;
        }

        .qp-back:hover {
            background: var(--qp-purple-light);
            box-shadow: 0 2px 8px rgba(111, 66, 193, 0.12);
            color: var(--qp-purple);
        }

        /* ── Page header ── */
        .qp-page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        .qp-page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--qp-purple);
            margin: 0 0 4px;
            line-height: 1.3;
        }

        .qp-page-meta {
            font-size: 0.83rem;
            color: var(--qp-muted);
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .qp-page-meta strong {
            color: var(--qp-text);
        }

        .qp-reload-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 99px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
            box-shadow: 0 2px 10px rgba(22, 163, 74, 0.3);
            flex-shrink: 0;
        }

        .qp-reload-btn:hover {
            background: #15803d;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.4);
        }

        .qp-reload-btn:active {
            transform: scale(0.97);
        }

        /* ── Grid ── */
        .qp-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 24px;
            align-items: start;
        }

        /* ── Sidebar ── */
        .qp-sidebar {
            position: sticky;
            top: 80px;
        }

        .qp-card {
            background: var(--qp-surface);
            border-radius: var(--qp-radius);
            border: 1px solid var(--qp-border);
            box-shadow: var(--qp-shadow);
            overflow: hidden;
        }

        .qp-card-head {
            padding: 16px 20px;
            border-bottom: 1px solid var(--qp-border);
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--qp-purple);
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--qp-purple-light);
        }

        .qp-card-body {
            padding: 20px;
        }

        /* Alert info */
        .qp-alert {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.8rem;
            color: #1e40af;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        /* Stat rows */
        .qp-stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.85rem;
        }

        .qp-stat-row:last-of-type {
            border-bottom: none;
            padding-bottom: 4px;
        }

        .qp-stat-label {
            color: var(--qp-muted);
            font-weight: 500;
        }

        .qp-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            padding: 3px 10px;
            border-radius: 99px;
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1;
        }

        .qp-badge.easy {
            background: var(--qp-easy-bg);
            color: var(--qp-easy);
            border: 1px solid var(--qp-easy-border);
        }

        .qp-badge.medium {
            background: var(--qp-med-bg);
            color: var(--qp-med);
            border: 1px solid var(--qp-med-border);
        }

        .qp-badge.hard {
            background: var(--qp-hard-bg);
            color: var(--qp-hard);
            border: 1px solid var(--qp-hard-border);
        }

        .qp-badge.count {
            background: var(--qp-purple-mid);
            color: var(--qp-purple);
            border: 1px solid var(--qp-border);
        }

        /* Progress bar */
        .qp-diff-bar {
            margin: 18px 0 20px;
        }

        .qp-diff-bar-label {
            font-size: 0.75rem;
            color: var(--qp-muted);
            margin-bottom: 6px;
            font-weight: 500;
        }

        .qp-bar-track {
            height: 8px;
            border-radius: 99px;
            background: #f3f4f6;
            display: flex;
            overflow: hidden;
            gap: 2px;
        }

        .qp-bar-seg {
            height: 100%;
            border-radius: 99px;
            transition: width 0.4s ease;
        }

        .qp-bar-seg.easy {
            background: var(--qp-easy);
        }

        .qp-bar-seg.medium {
            background: var(--qp-med);
        }

        .qp-bar-seg.hard {
            background: var(--qp-hard);
        }

        .qp-bar-legend {
            display: flex;
            gap: 12px;
            margin-top: 7px;
            flex-wrap: wrap;
        }

        .qp-bar-legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.73rem;
            color: var(--qp-muted);
        }

        .qp-legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* Manage btn */
        .qp-manage-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 11px;
            background: var(--qp-purple);
            color: #fff;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: background 0.15s, box-shadow 0.15s;
            box-shadow: 0 2px 10px rgba(111, 66, 193, 0.25);
            margin-top: 4px;
        }

        .qp-manage-btn:hover {
            background: #5a32a3;
            color: #fff;
            box-shadow: 0 4px 14px rgba(111, 66, 193, 0.35);
        }

        /* ── Main content ── */
        .qp-main-head {
            padding: 16px 20px;
            border-bottom: 1px solid var(--qp-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--qp-surface);
        }

        .qp-main-head-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--qp-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qp-count-chip {
            background: var(--qp-purple-mid);
            color: var(--qp-purple);
            border: 1px solid var(--qp-border);
            border-radius: 99px;
            padding: 3px 12px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .qp-main-body {
            padding: 24px;
        }

        /* ── Question card ── */
        .qp-question {
            background: var(--qp-surface);
            border: 1px solid var(--qp-border);
            border-radius: var(--qp-radius);
            padding: 20px 22px;
            margin-bottom: 18px;
            position: relative;
            transition: box-shadow 0.2s;
            animation: qp-slidein 0.25s ease both;
        }

        .qp-question:hover {
            box-shadow: 0 4px 20px rgba(111, 66, 193, 0.1);
        }

        @keyframes qp-slidein {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Số câu + badge khó */
        .qp-q-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .qp-q-number {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--qp-text);
            flex: 1;
            line-height: 1.5;
        }

        .qp-q-num-chip {
            background: var(--qp-purple);
            color: #fff;
            border-radius: 8px;
            padding: 2px 10px;
            font-size: 0.78rem;
            font-weight: 700;
            flex-shrink: 0;
            line-height: 1.6;
        }

        .qp-diff-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 600;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .qp-diff-tag.easy {
            background: var(--qp-easy-bg);
            color: var(--qp-easy);
            border: 1px solid var(--qp-easy-border);
        }

        .qp-diff-tag.medium {
            background: var(--qp-med-bg);
            color: var(--qp-med);
            border: 1px solid var(--qp-med-border);
        }

        .qp-diff-tag.hard {
            background: var(--qp-hard-bg);
            color: var(--qp-hard);
            border: 1px solid var(--qp-hard-border);
        }

        /* Divider */
        .qp-q-divider {
            height: 1px;
            background: var(--qp-border);
            margin: 0 0 16px;
        }

        /* Options grid */
        .qp-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .qp-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 14px;
            border-radius: 10px;
            border: 1.5px solid var(--qp-border);
            background: #fafafa;
            font-size: 0.855rem;
            color: var(--qp-muted);
            transition: border-color 0.15s, background 0.15s;
        }

        .qp-option.correct {
            background: var(--qp-easy-bg);
            border-color: var(--qp-easy);
            color: var(--qp-easy);
            font-weight: 600;
        }

        .qp-option-label {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            background: #e5e7eb;
            color: #374151;
            font-weight: 700;
            font-size: 0.78rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .qp-option.correct .qp-option-label {
            background: var(--qp-easy);
            color: #fff;
        }

        .qp-option-text {
            flex: 1;
            line-height: 1.45;
        }

        .qp-check-icon {
            flex-shrink: 0;
            color: var(--qp-easy);
            font-size: 1rem;
        }

        /* ── Empty state ── */
        .qp-empty {
            text-align: center;
            padding: 64px 24px;
        }

        .qp-empty-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #fffbeb;
            border: 2px solid #fde68a;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: #d97706;
        }

        .qp-empty h5 {
            font-weight: 700;
            color: var(--qp-text);
            margin-bottom: 8px;
        }

        .qp-empty p {
            color: var(--qp-muted);
            font-size: 0.875rem;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .qp-add-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: var(--qp-purple);
            color: #fff;
            border-radius: 99px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            transition: background 0.15s, box-shadow 0.15s;
            box-shadow: 0 2px 10px rgba(111, 66, 193, 0.25);
        }

        .qp-add-btn:hover {
            background: #5a32a3;
            color: #fff;
        }

        /* ── Responsive ── */
        @media (max-width: 991px) {
            .qp-grid {
                grid-template-columns: 1fr;
            }

            .qp-sidebar {
                position: static;
            }

            .qp-options {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575px) {
            .qp-wrap {
                padding: 16px 0 48px;
            }

            .qp-page-title {
                font-size: 1.2rem;
            }

            .qp-page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .qp-reload-btn {
                justify-content: center;
            }

            .qp-main-body {
                padding: 16px;
            }

            .qp-question {
                padding: 16px;
            }

            .qp-q-header {
                flex-direction: column;
                gap: 8px;
            }

            .qp-diff-tag {
                align-self: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $total = $examQuestions->count();
        $easyPct = $total > 0 ? round(($quiz->easy_count / $total) * 100) : 0;
        $medPct = $total > 0 ? round(($quiz->medium_count / $total) * 100) : 0;
        $hardPct = $total > 0 ? 100 - $easyPct - $medPct : 0;
        $labels = ['A', 'B', 'C', 'D'];
    @endphp

    <div class="qp-wrap">
        <div class="container-fluid" style="max-width: 1200px;">

            {{-- Back --}}
            <a href="{{ route('courses.show', $quiz->course_id) }}" class="qp-back">
                <i class="fa-solid fa-arrow-left"></i> Quay lại khóa học
            </a>

            {{-- Page header --}}
            <div class="qp-page-header">
                <div>
                    <h1 class="qp-page-title">
                        <i class="fa-solid fa-eye me-2" style="font-size:1.2rem; opacity:.7;"></i>{{ $quiz->title }}
                    </h1>
                    <div class="qp-page-meta">
                        <span><i class="fa-solid fa-clock me-1"></i> Thời gian: <strong>{{ $quiz->time_limit }}
                                phút</strong></span>
                        <span><i class="fa-solid fa-list-ol me-1"></i> Tổng câu: <strong>{{ $total }} câu</strong></span>
                    </div>
                </div>
                <button onclick="window.location.reload()" class="qp-reload-btn">
                    <i class="fa-solid fa-rotate"></i> Tải đề ngẫu nhiên khác
                </button>
            </div>

            {{-- Grid --}}
            <div class="qp-grid">

                {{-- ── Sidebar ── --}}
                <aside class="qp-sidebar">
                    <div class="qp-card">
                        <div class="qp-card-head">
                            <i class="fa-solid fa-layer-group"></i> Cấu trúc đề thi
                        </div>
                        <div class="qp-card-body">

                            <div class="qp-alert">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                Chế độ <strong>Ngân hàng câu hỏi</strong> đang hoạt động. Mỗi học sinh nhận đề khác nhau
                                theo cấu trúc này.
                            </div>

                            <div class="qp-stat-row">
                                <span class="qp-stat-label"><i class="fa-solid fa-smile me-1" style="color:var(--qp-easy)"></i>
                                    Dễ</span>
                                <span class="qp-badge easy">{{ $quiz->easy_count }} câu</span>
                            </div>
                            <div class="qp-stat-row">
                                <span class="qp-stat-label"><i class="fa-solid fa-meh me-1" style="color:var(--qp-med)"></i>
                                    Trung bình</span>
                                <span class="qp-badge medium">{{ $quiz->medium_count }} câu</span>
                            </div>
                            <div class="qp-stat-row">
                                <span class="qp-stat-label"><i class="fa-solid fa-frown me-1" style="color:var(--qp-hard)"></i>
                                    Khó</span>
                                <span class="qp-badge hard">{{ $quiz->hard_count }} câu</span>
                            </div>

                            {{-- Progress bar --}}
                            @if ($total > 0)
                                <div class="qp-diff-bar">
                                    <div class="qp-diff-bar-label">Tỉ lệ phân bổ</div>
                                    <div class="qp-bar-track">
                                        <div class="qp-bar-seg easy" style="width:{{ $easyPct }}%"></div>
                                        <div class="qp-bar-seg medium" style="width:{{ $medPct }}%"></div>
                                        <div class="qp-bar-seg hard" style="width:{{ $hardPct }}%"></div>
                                    </div>
                                    <div class="qp-bar-legend">
                                        <span class="qp-bar-legend-item"><span class="qp-legend-dot"
                                                style="background:var(--qp-easy)"></span> Dễ {{ $easyPct }}%</span>
                                        <span class="qp-bar-legend-item"><span class="qp-legend-dot"
                                                style="background:var(--qp-med)"></span> TB {{ $medPct }}%</span>
                                        <span class="qp-bar-legend-item"><span class="qp-legend-dot"
                                                style="background:var(--qp-hard)"></span> Khó {{ $hardPct }}%</span>
                                    </div>
                                </div>
                            @endif

                            <a href="{{ route('questions.index', ['course_id' => $quiz->course_id]) }}"
                                class="qp-manage-btn">
                                <i class="fa-solid fa-database"></i> Quản lý Ngân hàng câu hỏi
                            </a>
                        </div>
                    </div>
                </aside>

                {{-- ── Main ── --}}
                <main>
                    <div class="qp-card">
                        <div class="qp-main-head">
                            <div class="qp-main-head-title">
                                <i class="fa-solid fa-file-lines" style="color:var(--qp-muted); font-size:1rem;"></i>
                                Bản xem trước đề thi
                            </div>
                            <span class="qp-count-chip">{{ $total }} câu hỏi</span>
                        </div>

                        <div class="qp-main-body">
                            @forelse ($examQuestions as $index => $question)

                                @php
                                    $diff = $question->difficulty;
                                    $diffLabel = match ($diff) {
                                        'easy' => 'Dễ',
                                        'medium' => 'Trung bình',
                                        default => 'Khó',
                                    };
                                    $diffIcon = match ($diff) {
                                        'easy' => 'fa-smile',
                                        'medium' => 'fa-meh',
                                        default => 'fa-frown',
                                    };
                                @endphp

                                <div class="qp-question" style="animation-delay: {{ $index * 0.04 }}s">
                                    <div class="qp-q-header">
                                        <div class="qp-q-number">
                                            <span class="qp-q-num-chip">{{ $index + 1 }}</span>
                                            {{ $question->question_text }}
                                        </div>
                                        <span class="qp-diff-tag {{ $diff }}">
                                            <i class="fa-solid {{ $diffIcon }}"></i> {{ $diffLabel }}
                                        </span>
                                    </div>

                                    <div class="qp-q-divider"></div>

                                    <div class="qp-options">
                                        @foreach ($question->options as $key => $option)
                                            <div class="qp-option {{ $option->is_correct ? 'correct' : '' }}">
                                                <div class="qp-option-label">{{ $labels[$loop->index] ?? '' }}</div>
                                                <span class="qp-option-text">{{ $option->option_text }}</span>
                                                @if ($option->is_correct)
                                                    <i class="fa-solid fa-circle-check qp-check-icon"></i>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            @empty

                                <div class="qp-empty">
                                    <div class="qp-empty-icon">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                    </div>
                                    <h5>Không đủ câu hỏi trong Ngân hàng!</h5>
                                    <p>
                                        Kho câu hỏi hiện tại không đủ số lượng để tạo đề theo cấu trúc bạn yêu cầu.<br>
                                        Vui lòng bổ sung thêm câu hỏi vào ngân hàng.
                                    </p>
                                    <a href="{{ route('questions.index', ['course_id' => $quiz->course_id]) }}"
                                        class="qp-add-btn">
                                        <i class="fa-solid fa-plus"></i> Thêm câu hỏi ngay
                                    </a>
                                </div>

                            @endforelse
                        </div>
                    </div>
                </main>

            </div>{{-- /qp-grid --}}
        </div>
    </div>
@endsection
