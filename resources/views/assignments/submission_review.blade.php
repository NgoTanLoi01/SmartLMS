@extends('layouts.app')

@section('title', 'Xem bài nộp')

@push('styles')
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --brand: #4f7fff;
            --brand-dark: #2952e3;
            --brand-light: #eef3ff;
            --accent: #8b5cf6;
            --success: #10b981;
            --success-light: #ecfdf5;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --danger: #f43f5e;
            --danger-light: #fff1f4;

            --surface: #ffffff;
            --surface-2: #f7f9fc;
            --surface-3: #eef2f9;
            --border: #e5e9f0;
            --border-strong: #d1d9e6;
            --text: #0d1b2a;
            --text-2: #374151;
            --text-muted: #6b7280;
            --text-light: #9ca3af;

            --radius-sm: 10px;
            --radius: 16px;
            --radius-lg: 20px;

            --shadow-sm: 0 1px 4px rgba(0, 0, 0, .05);
            --shadow: 0 4px 20px rgba(0, 0, 0, .07), 0 1px 4px rgba(0, 0, 0, .04);
            --shadow-md: 0 8px 32px rgba(0, 0, 0, .09), 0 2px 8px rgba(0, 0, 0, .05);

            --font: 'Plus Jakarta Sans', sans-serif;
            --font-mono: 'DM Mono', monospace;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: var(--font);
            background: var(--surface-2);
        }

        /* ── PAGE WRAP ─────────────────────────────── */
        .srp {
            min-height: 100vh;
            padding: 20px 14px 40px;
        }

        @media (min-width: 768px) {
            .srp {
                padding: 28px 24px 56px;
            }
        }

        .srp__shell {
            max-width: 1240px;
            margin: 0 auto;
        }

        /* ── BACK LINK ─────────────────────────────── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .8rem;
            font-weight: 700;
            color: var(--text-muted);
            text-decoration: none;
            padding: .4rem .75rem .4rem .5rem;
            border-radius: 100px;
            transition: all .15s;
            margin-bottom: 1.1rem;
        }

        .back-link:hover {
            background: var(--surface);
            color: var(--text);
            box-shadow: var(--shadow-sm);
        }

        .back-link i {
            font-size: .78rem;
        }

        /* ── HEADER CARD ───────────────────────────── */
        .srp-header {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.4rem 1.6rem;
            margin-bottom: 1.25rem;
            box-shadow: var(--shadow);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .srp-header__title {
            font-size: 1.25rem;
            font-weight: 900;
            color: var(--text);
            margin: 0 0 .35rem;
            letter-spacing: -.03em;
            line-height: 1.3;
        }

        @media (min-width: 768px) {
            .srp-header__title {
                font-size: 1.5rem;
            }
        }

        .srp-header__meta {
            font-size: .82rem;
            color: var(--text-muted);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .35rem .6rem;
        }

        .srp-header__meta .sep {
            color: var(--border-strong);
        }

        /* ── BADGE ─────────────────────────────────── */
        .bdg {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .25em .75em;
            border-radius: 100px;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .03em;
            line-height: 1;
        }

        .bdg--primary {
            background: var(--brand-light);
            color: var(--brand-dark);
            border: 1px solid #c7d9ff;
        }

        .bdg--success {
            background: var(--success-light);
            color: #065f46;
        }

        .bdg--warning {
            background: var(--warning-light);
            color: #92400e;
        }

        .bdg--muted {
            background: var(--surface-3);
            color: var(--text-muted);
            border: 1px solid var(--border);
        }

        .bdg--danger {
            background: var(--danger-light);
            color: #be123c;
        }

        .bdg--accent {
            background: #f3efff;
            color: #6d28d9;
        }

        /* ── PANEL ─────────────────────────────────── */
        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .panel+.panel {
            margin-top: 1rem;
        }

        .panel__head {
            padding: .9rem 1.35rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .panel__label {
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .panel__label .icon-dot {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .72rem;
        }

        .idot--blue {
            background: var(--brand-light);
            color: var(--brand);
        }

        .idot--green {
            background: var(--success-light);
            color: var(--success);
        }

        .idot--amber {
            background: var(--warning-light);
            color: var(--warning);
        }

        .idot--violet {
            background: #f3efff;
            color: var(--accent);
        }

        .panel__body {
            padding: 1.25rem 1.35rem;
        }

        /* ── INSTRUCTION BOX ───────────────────────── */
        .instruction-box {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 1.1rem 1.25rem;
            line-height: 1.8;
            font-size: .9rem;
            color: var(--text-2);
        }

        /* ── ANSWER BOX ────────────────────────────── */
        .answer-box {
            background: var(--surface);
            border: 1.5px solid var(--border-strong);
            border-radius: var(--radius-sm);
            padding: 1.1rem 1.25rem;
            line-height: 1.85;
            font-size: .9rem;
            color: var(--text-2);
            white-space: pre-wrap;
            min-height: 200px;
            font-family: var(--font);
        }

        .answer-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            padding: 2.5rem 1.5rem;
            border: 1.5px dashed var(--border-strong);
            border-radius: var(--radius-sm);
            background: var(--surface-2);
            text-align: center;
            color: var(--text-muted);
            font-size: .84rem;
        }

        .answer-empty i {
            font-size: 1.5rem;
            opacity: .35;
        }

        /* ── FILE CARD ─────────────────────────────── */
        .file-card {
            display: flex;
            align-items: center;
            gap: .85rem;
            background: var(--brand-light);
            border: 1.5px dashed #93c5fd;
            border-radius: var(--radius-sm);
            padding: .9rem 1.1rem;
        }

        .file-card__icon {
            width: 40px;
            height: 40px;
            background: var(--brand);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .file-card__name {
            font-weight: 700;
            font-size: .85rem;
            color: var(--brand-dark);
        }

        .file-card__hint {
            font-size: .76rem;
            color: var(--text-muted);
            margin-top: .1rem;
        }

        .file-preview {
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            overflow: hidden;
            background: var(--surface);
        }

        .file-preview__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .7rem .9rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface-2);
            font-size: .78rem;
            color: var(--text-muted);
            flex-wrap: wrap;
        }

        .file-preview__title {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-weight: 800;
            color: var(--text-2);
            min-width: 0;
        }

        .file-preview__name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: min(420px, 72vw);
        }

        .file-preview__frame {
            width: 100%;
            height: 560px;
            border: 0;
            display: block;
            background: #fff;
        }

        .file-preview__image {
            display: block;
            max-width: 100%;
            max-height: 620px;
            margin: 0 auto;
            object-fit: contain;
            background: #fff;
        }

        .file-preview-empty {
            border: 1.5px dashed var(--border-strong);
            border-radius: var(--radius-sm);
            background: var(--surface-2);
            padding: 1rem;
            color: var(--text-muted);
            font-size: .82rem;
            display: flex;
            gap: .6rem;
            align-items: flex-start;
        }

        /* ── GRADING SIDEBAR ───────────────────────── */
        .grading-card {
            position: sticky;
            top: 20px;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        @media (max-width: 991.98px) {
            .grading-card {
                position: static;
            }
        }

        /* ── STUDENT PROFILE ───────────────────────── */
        .student-profile {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: 1.25rem 1.35rem;
            border-bottom: 1px solid var(--border);
        }

        .student-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--brand), var(--accent));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.05rem;
            flex-shrink: 0;
        }

        .student-name {
            font-weight: 800;
            font-size: .9rem;
            color: var(--text);
            margin-bottom: .1rem;
        }

        .student-email {
            font-size: .76rem;
            color: var(--text-muted);
            font-family: var(--font-mono);
        }

        /* ── AI SECTION ────────────────────────────── */
        .ai-section {
            padding: 1.1rem 1.35rem;
            border-bottom: 1px solid var(--border);
        }

        .btn-ai {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            width: 100%;
            padding: .62rem 1rem;
            border-radius: 100px;
            font-size: .82rem;
            font-weight: 700;
            border: 1.5px solid var(--border-strong);
            background: var(--surface);
            color: var(--text-2);
            cursor: pointer;
            transition: all .18s cubic-bezier(.4, 0, .2, 1);
            font-family: var(--font);
        }

        .btn-ai:hover:not(:disabled) {
            background: #f3efff;
            border-color: #c4b5fd;
            color: var(--accent);
        }

        .btn-ai:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .btn-ai .ai-icon {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            background: linear-gradient(135deg, var(--brand), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: .65rem;
        }

        /* AI result */
        .ai-result {
            margin-top: .85rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--surface-2);
            overflow: hidden;
            font-size: .82rem;
        }

        .ai-result__row {
            padding: .65rem .9rem;
            border-bottom: 1px solid var(--border);
            display: grid;
            grid-template-columns: 90px 1fr;
            gap: .5rem;
            align-items: baseline;
            line-height: 1.55;
        }

        .ai-result__row:last-child {
            border-bottom: none;
        }

        .ai-result__key {
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
        }

        .ai-result__val {
            color: var(--text-2);
        }

        .ai-score-chip {
            display: inline-flex;
            align-items: baseline;
            gap: .15rem;
            font-weight: 900;
            font-size: 1.25rem;
            color: var(--brand);
            letter-spacing: -.03em;
        }

        .ai-score-chip span {
            font-size: .75rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .ai-warning-list {
            display: grid;
            gap: .45rem;
        }

        .ai-warning {
            border: 1px solid #fed7aa;
            border-radius: 10px;
            background: #fff7ed;
            color: #9a3412;
            padding: .55rem .65rem;
        }

        .ai-warning.high {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }

        .ai-warning.low {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: var(--text-muted);
        }

        .ai-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            padding: .8rem .9rem;
            border-top: 1px solid var(--border);
            background: var(--surface);
        }

        .btn-ai-apply {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 999px;
            font-size: .76rem;
            font-weight: 800;
            padding: .5rem .75rem;
        }

        .ai-history {
            margin-top: .7rem;
            color: var(--text-muted);
            font-size: .76rem;
        }

        /* ── GRADING FORM ──────────────────────────── */
        .grading-form {
            padding: 1.1rem 1.35rem 1.35rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-lbl {
            display: block;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-muted);
            margin-bottom: .45rem;
        }

        .score-input-wrap {
            position: relative;
        }

        .score-input-wrap input {
            width: 100%;
            padding: .7rem 3rem .7rem 1rem;
            border: 1.5px solid var(--border-strong);
            border-radius: var(--radius-sm);
            font-size: 1.35rem;
            font-weight: 900;
            font-family: var(--font-mono);
            color: var(--text);
            background: var(--surface);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            -moz-appearance: textfield;
        }

        .score-input-wrap input::-webkit-inner-spin-button,
        .score-input-wrap input::-webkit-outer-spin-button {
            -webkit-appearance: none;
        }

        .score-input-wrap input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(79, 127, 255, .15);
        }

        .score-input-wrap .score-max {
            position: absolute;
            right: .85rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: .75rem;
            font-weight: 700;
            color: var(--text-muted);
        }

        textarea.form-ctrl {
            width: 100%;
            padding: .75rem 1rem;
            border: 1.5px solid var(--border-strong);
            border-radius: var(--radius-sm);
            font-size: .875rem;
            font-family: var(--font);
            color: var(--text-2);
            background: var(--surface);
            resize: vertical;
            min-height: 120px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            line-height: 1.65;
        }

        textarea.form-ctrl:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(79, 127, 255, .15);
        }

        textarea.form-ctrl::placeholder {
            color: var(--text-light);
        }

        .btn-save {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            width: 100%;
            padding: .7rem 1rem;
            border-radius: 100px;
            font-size: .85rem;
            font-weight: 800;
            border: none;
            background: var(--success);
            color: #fff;
            cursor: pointer;
            transition: all .18s cubic-bezier(.4, 0, .2, 1);
            font-family: var(--font);
            box-shadow: 0 4px 16px rgba(16, 185, 129, .28);
        }

        .btn-save:hover {
            background: #059669;
            box-shadow: 0 6px 20px rgba(16, 185, 129, .38);
            transform: translateY(-1px);
        }

        /* ── ALERT ─────────────────────────────────── */
        .ai-notice {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: .75rem .9rem;
            font-size: .8rem;
            color: var(--text-muted);
            display: flex;
            align-items: flex-start;
            gap: .55rem;
        }

        .ai-notice i {
            margin-top: .12rem;
            flex-shrink: 0;
            color: var(--text-light);
        }

        /* ── SUBMIT META ───────────────────────────── */
        .submit-meta {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .78rem;
            color: var(--text-muted);
        }

        /* ── RESPONSIVE ────────────────────────────── */
        @media (max-width: 767.98px) {
            .srp-header {
                padding: 1.1rem 1.15rem;
            }

            .srp-header__title {
                font-size: 1.1rem;
            }

            .panel__body {
                padding: 1rem;
            }

            .panel__head {
                padding: .8rem 1rem;
            }

            .ai-result__row {
                grid-template-columns: 1fr;
                gap: .2rem;
            }

            .file-preview__frame {
                height: 420px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="srp">
        <div class="srp__shell">

            {{-- BACK --}}
            <a href="{{ route('courses.show', $course->id) }}" class="back-link">
                <i class="fas fa-chevron-left"></i> Quay lại khóa học
            </a>

            {{-- HEADER --}}
            <div class="srp-header">
                <div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="bdg bdg--primary">{{ $assignmentTypeLabel }}</span>
                        @if ($submission->grade !== null)
                            <span class="bdg bdg--success"><i class="fas fa-check-circle"></i> Đã chấm</span>
                        @else
                            <span class="bdg bdg--warning"><i class="fas fa-hourglass-half"></i> Chưa chấm</span>
                        @endif
                    </div>
                    <h1 class="srp-header__title">{{ $assignment->title }}</h1>
                    <div class="srp-header__meta mt-1">
                        <span><i class="fas fa-book me-1" style="color:var(--brand)"></i>{{ $course->title }}</span>
                        @if ($assignment->lesson)
                            <span class="sep">·</span>
                            <span>{{ $assignment->lesson->title }}</span>
                        @endif
                        <span class="sep">·</span>
                        <span><i class="fas fa-calendar-alt me-1"></i>Hạn:
                            {{ $assignment->due_date?->format('d/m/Y H:i') ?? '---' }}</span>
                    </div>
                </div>
            </div>

            {{-- MAIN GRID --}}
            <div class="row g-3 g-lg-4 align-items-start">

                {{-- LEFT: CONTENT --}}
                <div class="col-lg-8">

                    {{-- INSTRUCTIONS --}}
                    <div class="panel">
                        <div class="panel__head">
                            <div class="panel__label">
                                <span class="icon-dot idot--blue"><i class="fas fa-file-alt"></i></span>
                                Yêu cầu bài tập
                            </div>
                        </div>
                        <div class="panel__body">
                            <div class="instruction-box">
                                {!! $assignment->instructions !!}
                            </div>
                        </div>
                    </div>

                    {{-- RUBRIC --}}
                    <div class="panel" style="margin-top:1rem">
                        <div class="panel__head">
                            <div class="panel__label">
                                <span class="icon-dot idot--violet"><i class="fas fa-list-check"></i></span>
                                Tiêu chí chấm điểm
                            </div>
                            <span class="bdg bdg--muted">Thang {{ $assignment->grading_scale ?? 10 }} điểm</span>
                        </div>
                        <div class="panel__body">
                            @if (trim((string) $assignment->grading_rubric))
                                <div class="instruction-box" style="white-space:pre-wrap;">{{ $assignment->grading_rubric }}</div>
                            @else
                                <div class="ai-notice">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Bài tập này chưa có rubric cụ thể. AI vẫn có thể phân tích, nhưng chỉ dùng yêu cầu bài tập để tạo tiêu chí tạm.</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- STUDENT ANSWER --}}
                    <div class="panel" style="margin-top:1rem">
                        <div class="panel__head">
                            <div class="panel__label">
                                <span class="icon-dot idot--green"><i class="fas fa-pen-nib"></i></span>
                                Bài làm học sinh
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="submit-meta">
                                    <i class="fas fa-clock"></i>
                                    Nộp lúc {{ $submission->formatSubmittedAt('H:i:s · d/m/Y') ?? '---' }}
                                </div>
                                @if ($fileUrl)
                                    <a href="{{ $fileUrl }}" target="_blank" class="bdg bdg--primary"
                                        style="text-decoration:none;padding:.3em .9em">
                                        <i class="fas fa-download"></i> Tải file
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="panel__body">
                            @if ($fileUrl)
                                <div class="file-card mb-3">
                                    <div class="file-card__icon"><i class="fas fa-paperclip"></i></div>
                                    <div>
                                        <div class="file-card__name">{{ $fileName ?? 'File bài làm đã đính kèm' }}</div>
                                        <div class="file-card__hint">
                                            @if ($filePreviewUrl)
                                                Có thể xem trước bên dưới hoặc tải file về máy.
                                            @else
                                                Định dạng này chưa hỗ trợ xem trước. Vui lòng tải file để kiểm tra.
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if ($filePreviewUrl)
                                    <div class="file-preview mb-3">
                                        <div class="file-preview__head">
                                            <div class="file-preview__title">
                                                <i class="fas fa-eye"></i>
                                                <span>Xem trước bài nộp</span>
                                                <span class="file-preview__name">{{ $fileName }}</span>
                                            </div>
                                            <span class="bdg bdg--muted">{{ strtoupper($filePreviewType) }}</span>
                                        </div>
                                        @if ($filePreviewType === 'image')
                                            <img src="{{ $filePreviewUrl }}" alt="Xem trước bài nộp"
                                                class="file-preview__image">
                                        @else
                                            <iframe src="{{ $filePreviewUrl }}" class="file-preview__frame"
                                                sandbox="allow-same-origin"></iframe>
                                        @endif
                                    </div>
                                @else
                                    <div class="file-preview-empty mb-3">
                                        <i class="fas fa-circle-info"></i>
                                        <div>
                                            Hệ thống hiện xem trước trực tiếp cho PDF, ảnh và HTML. Với Word/ZIP, giáo viên
                                            cần tải file để kiểm tra.
                                        </div>
                                    </div>
                                @endif
                            @endif

                            @if (trim((string) $submission->text_answer))
                                <div class="answer-box">{{ $submission->text_answer }}</div>
                            @else
                                <div class="answer-empty">
                                    <i class="fas fa-file-slash"></i>
                                    <div>
                                        <strong>Không có nội dung tự luận</strong><br>
                                        Vui lòng kiểm tra file đính kèm nếu có.
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

                {{-- RIGHT: GRADING --}}
                <div class="col-lg-4">
                    <div class="grading-card">
                        <div class="panel">

                            {{-- STUDENT PROFILE --}}
                            <div class="student-profile">
                                <div class="student-avatar">
                                    {{ mb_strtoupper(mb_substr($submission->user?->name ?? 'HS', 0, 1)) }}</div>
                                <div>
                                    <div class="student-name">{{ $submission->user?->name ?? 'Học sinh' }}</div>
                                    <div class="student-email">{{ $submission->user?->email }}</div>
                                </div>
                            </div>

                            {{-- AI SECTION --}}
                            <div class="ai-section">
                                @php
                                    $latestAiHistory = collect($submission->ai_analysis_history ?? [])->first() ?? [];
                                    $latestAiStrengths = $latestAiHistory['strengths'] ?? [];
                                    $latestAiImprovements = $latestAiHistory['improvements'] ?? [];
                                @endphp
                                @if (!$assignment->ai_grading_enabled)
                                    <div class="ai-notice">
                                        <i class="fas fa-info-circle"></i>
                                        <span>AI hỗ trợ chấm đang tắt cho bài tập này.</span>
                                    </div>
                                @elseif (trim((string) $submission->text_answer))
                                    <button type="button" id="aiAnalyzeBtn" class="btn-ai"
                                        data-submission-id="{{ $submission->id }}">
                                        <span class="ai-icon"><i class="fas fa-robot"></i></span>
                                        {{ $submission->ai_analyzed_at ? 'AI phân tích lại' : 'AI phân tích bài làm' }}
                                    </button>
                                    <div id="aiResultBox" class="ai-result"
                                        style="{{ $submission->ai_analyzed_at ? '' : 'display:none' }}">
                                        @if ($submission->ai_analyzed_at)
                                            <div class="ai-result__row">
                                                <div class="ai-result__key">Gợi ý điểm</div>
                                                <div class="ai-result__val">
                                                    <span class="ai-score-chip">{{ $submission->ai_suggested_score ?? '---' }}<span>/ {{ $assignment->grading_scale ?? 10 }}</span></span>
                                                </div>
                                            </div>
                                            @if ($submission->ai_feedback)
                                                <div class="ai-result__row">
                                                    <div class="ai-result__key">Nhận xét</div>
                                                    <div class="ai-result__val">{{ $submission->ai_feedback }}</div>
                                                </div>
                                            @endif
                                            @if (!empty($submission->ai_review_flags))
                                                <div class="ai-result__row">
                                                    <div class="ai-result__key">Cảnh báo</div>
                                                    <div class="ai-result__val">
                                                        <div class="ai-warning-list">
                                                            @foreach ($submission->ai_review_flags as $flag)
                                                                <div class="ai-warning {{ $flag['level'] ?? 'medium' }}">
                                                                    {{ $flag['message'] ?? 'Cần giáo viên xem kỹ hơn.' }}
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if (!empty($latestAiStrengths))
                                                <div class="ai-result__row">
                                                    <div class="ai-result__key">Điểm tốt</div>
                                                    <div class="ai-result__val">{{ implode('; ', $latestAiStrengths) }}</div>
                                                </div>
                                            @endif
                                            @if (!empty($latestAiImprovements))
                                                <div class="ai-result__row">
                                                    <div class="ai-result__key">Cần cải thiện</div>
                                                    <div class="ai-result__val">{{ implode('; ', $latestAiImprovements) }}</div>
                                                </div>
                                            @endif
                                            @if (!empty($submission->ai_rubric_breakdown))
                                                <div class="ai-result__row">
                                                    <div class="ai-result__key">Rubric</div>
                                                    <div class="ai-result__val">
                                                        @foreach ($submission->ai_rubric_breakdown as $item)
                                                            <div class="mb-2">
                                                                <strong>{{ $item['criterion'] ?? 'Tiêu chí' }}:</strong>
                                                                {{ $item['score'] ?? '---' }}/{{ $item['max_score'] ?? '---' }}
                                                                @if (!empty($item['comment']))
                                                                    <div class="text-muted">{{ $item['comment'] }}</div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            @if ($submission->ai_grading_notes)
                                                <div class="ai-result__row">
                                                    <div class="ai-result__key">Ghi chú</div>
                                                    <div class="ai-result__val">{{ $submission->ai_grading_notes }}</div>
                                                </div>
                                            @endif
                                            <div class="ai-result__row">
                                                <div class="ai-result__key">Cập nhật</div>
                                                <div class="ai-result__val">{{ $submission->ai_analyzed_at->format('H:i · d/m/Y') }}</div>
                                            </div>
                                            <div class="ai-actions">
                                                <button type="button" class="btn-ai-apply" id="useAiFeedbackBtn"
                                                    data-feedback="{{ e($submission->ai_feedback ?? '') }}">
                                                    <i class="fas fa-comment-dots me-1"></i>Dùng nhận xét AI
                                                </button>
                                            </div>
                                            @if (!empty($submission->ai_analysis_history) && count($submission->ai_analysis_history) > 1)
                                                <div class="ai-history px-3 pb-3">
                                                    Đã phân tích {{ count($submission->ai_analysis_history) }} lần. Lần gần nhất đang được hiển thị.
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @else
                                    <div class="ai-notice">
                                        <i class="fas fa-info-circle"></i>
                                        <span>AI chỉ phân tích được phần tự luận. Bài này cần giáo viên xem file thủ
                                            công.</span>
                                    </div>
                                @endif
                            </div>

                            {{-- GRADING FORM --}}
                            <form method="POST" action="{{ route('assignments.grade', $submission->id) }}"
                                class="grading-form">
                                @csrf

                                <div class="form-group">
                                    <label class="form-lbl">Điểm số</label>
                                    <div class="score-input-wrap">
                                        <input type="number" name="grade" id="gradeInput" step="0.1" min="0"
                                            max="{{ $assignment->grading_scale ?? 10 }}" value="{{ $submission->grade }}" placeholder="0.0" required>
                                        <span class="score-max">/ {{ $assignment->grading_scale ?? 10 }}</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-lbl">Nhận xét</label>
                                    <textarea name="feedback" id="feedbackInput" class="form-ctrl" placeholder="Nhập nhận xét cho học sinh...">{{ $submission->feedback }}</textarea>
                                </div>

                                <button type="submit" class="btn-save">
                                    <i class="fas fa-check-circle"></i>
                                    Lưu điểm và nhận xét
                                </button>
                            </form>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const aiBtn = document.getElementById('aiAnalyzeBtn');
            if (!aiBtn) return;

            const resultBox = document.getElementById('aiResultBox');
            const feedbackInput = document.getElementById('feedbackInput');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const gradingScale = @json($assignment->grading_scale ?? 10);
            let latestAiFeedback = @json($submission->ai_feedback ?? '');

            const esc = (v) => String(v ?? '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

            aiBtn.addEventListener('click', function() {
                const orig = aiBtn.innerHTML;
                aiBtn.disabled = true;
                aiBtn.innerHTML =
                    `<span class="spinner-border spinner-border-sm me-1" style="width:14px;height:14px;border-width:2px"></span> AI đang phân tích…`;
                resultBox.style.display = 'block';
                resultBox.innerHTML =
                    `<div class="ai-result__row"><div class="ai-result__key">Trạng thái</div><div class="ai-result__val" style="color:var(--text-muted)">Đang đọc bài làm…</div></div>`;

                fetch(`/submissions/${aiBtn.dataset.submissionId}/ai-analysis`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({}),
                    })
                    .then(r => r.json().then(p => ({
                        ok: r.ok,
                        p
                    })))
                    .then(({
                        ok,
                        p
                    }) => {
                        if (!ok || !p.success) throw new Error(p.message ||
                            'AI chưa phân tích được bài làm.');

                        const a = p.analysis || {};
                        const strengths = Array.isArray(a.strengths) ? a.strengths : [];
                        const improvements = Array.isArray(a.improvements) ? a.improvements : [];
                        const rubricBreakdown = Array.isArray(a.rubric_breakdown) ? a.rubric_breakdown : [];
                        const reviewFlags = Array.isArray(a.review_flags) ? a.review_flags : [];
                        latestAiFeedback = a.feedback || '';

                            const rows = [{
                                key: 'Gợi ý điểm',
                                val: `<span class="ai-score-chip">${esc(a.suggested_score ?? '---')}<span>/ ${esc(gradingScale)}</span></span>`
                            },
                            {
                                key: 'Nhận xét',
                                val: esc(a.feedback || '---')
                            },
                            reviewFlags.length ? {
                                key: 'Cảnh báo',
                                val: `<div class="ai-warning-list">${reviewFlags.map(flag => `
                                    <div class="ai-warning ${esc(flag.level || 'medium')}">${esc(flag.message || 'Cần giáo viên xem kỹ hơn.')}</div>
                                `).join('')}</div>`
                            } : null,
                            rubricBreakdown.length ? {
                                key: 'Rubric',
                                val: rubricBreakdown.map(item => `
                                    <div style="margin-bottom:.55rem">
                                        <strong>${esc(item.criterion || 'Tiêu chí')}:</strong>
                                        ${esc(item.score ?? '---')}/${esc(item.max_score ?? '---')}
                                        ${item.comment ? `<div style="color:var(--text-muted)">${esc(item.comment)}</div>` : ''}
                                    </div>
                                `).join('')
                            } : null,
                            strengths.length ? {
                                key: 'Điểm tốt',
                                val: esc(strengths.join('; '))
                            } : null,
                            improvements.length ? {
                                key: 'Cần cải thiện',
                                val: esc(improvements.join('; '))
                            } : null,
                            a.grading_notes ? {
                                key: 'Ghi chú',
                                val: esc(a.grading_notes)
                            } : null,
                        ].filter(Boolean);

                        resultBox.innerHTML = rows.map(r =>
                            `<div class="ai-result__row"><div class="ai-result__key">${r.key}</div><div class="ai-result__val">${r.val}</div></div>`
                        ).join('') + `
                            <div class="ai-actions">
                                <button type="button" class="btn-ai-apply" id="useAiFeedbackBtn">
                                    <i class="fas fa-comment-dots me-1"></i>Dùng nhận xét AI
                                </button>
                            </div>`;
                    })
                    .catch(err => {
                        resultBox.innerHTML =
                            `<div class="ai-result__row"><div class="ai-result__val" style="color:var(--danger)">${esc(err.message)}</div></div>`;
                    })
                    .finally(() => {
                        aiBtn.disabled = false;
                        aiBtn.innerHTML = orig;
                    });
            });

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('#useAiFeedbackBtn');
                if (!btn) return;

                const feedback = latestAiFeedback || btn.getAttribute('data-feedback') || '';
                if (!feedback) return;

                feedbackInput.value = feedback;
                feedbackInput.focus();
            });
        });
    </script>
@endpush
