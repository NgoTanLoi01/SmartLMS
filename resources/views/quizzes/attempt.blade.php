@extends('layouts.app')

@section('title', 'Làm bài: ' . $quiz->title)

@push('styles')
    <style>
        :root {
            --qz-purple: #6f42c1;
            --qz-purple-dk: #5a32a3;
            --qz-purple-light: #f3effe;
            --qz-purple-mid: #ede9fb;
            --qz-border: #e8e3f5;
            --qz-text: #1e1b4b;
            --qz-muted: #6b7280;
            --qz-surface: #ffffff;
            --qz-bg: #f5f3ff;
            --qz-danger: #dc2626;
            --qz-radius: 14px;
            --qz-shadow: 0 1px 4px rgba(111, 66, 193, 0.07), 0 4px 16px rgba(111, 66, 193, 0.06);

            /* Timer dimensions — referenced in JS too */
            --qz-timer-w: 130px;
        }

        /* ── Full-screen shell ── */
        .qz-shell {
            position: fixed;
            inset: 0;
            background: var(--qz-bg);
            z-index: 99999;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        /* ── Top bar ── */
        .qz-topbar {
            position: sticky;
            top: 0;
            z-index: 100001;
            background: var(--qz-surface);
            border-bottom: 1px solid var(--qz-border);
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 1px 8px rgba(111, 66, 193, 0.08);
            flex-shrink: 0;
        }

        .qz-topbar-left {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .qz-quiz-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--qz-purple);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .qz-quiz-meta {
            font-size: 0.74rem;
            color: var(--qz-muted);
            white-space: nowrap;
        }

        /* Timer */
        .qz-timer {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--qz-purple-light);
            border: 1.5px solid var(--qz-border);
            border-radius: 12px;
            padding: 7px 16px;
            flex-shrink: 0;
            transition: background 0.3s, border-color 0.3s;
        }

        .qz-timer.urgent {
            background: #fef2f2;
            border-color: #fecaca;
            animation: qz-pulse-border 1s ease infinite;
        }

        @keyframes qz-pulse-border {

            0%,
            100% {
                border-color: #fecaca;
            }

            50% {
                border-color: var(--qz-danger);
            }
        }

        .qz-timer-icon {
            font-size: 1.05rem;
            color: var(--qz-purple);
            flex-shrink: 0;
        }

        .qz-timer.urgent .qz-timer-icon {
            color: var(--qz-danger);
        }

        .qz-timer-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            line-height: 1;
        }

        .qz-timer-label {
            font-size: 0.63rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            color: var(--qz-muted);
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        #qz-countdown {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--qz-text);
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.04em;
        }

        .qz-timer.urgent #qz-countdown {
            color: var(--qz-danger);
        }

        /* Progress bar */
        .qz-progress-bar {
            height: 3px;
            background: #e5e7eb;
            flex-shrink: 0;
        }

        .qz-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--qz-purple), #a78bfa);
            transition: width 0.4s ease;
            width: 0%;
        }

        /* ── Main content ── */
        .qz-main {
            flex: 1;
            padding: 32px 16px 100px;
            display: flex;
            justify-content: center;
        }

        .qz-inner {
            width: 100%;
            max-width: 760px;
        }

        /* Page heading */
        .qz-heading {
            text-align: center;
            margin-bottom: 32px;
        }

        .qz-heading h1 {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--qz-purple);
            margin-bottom: 6px;
        }

        .qz-heading-meta {
            font-size: 0.82rem;
            color: var(--qz-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .qz-warn-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--qz-danger);
            border-radius: 99px;
            padding: 3px 12px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 8px;
        }

        /* ── Question card ── */
        .qz-qcard {
            background: var(--qz-surface);
            border: 1px solid var(--qz-border);
            border-radius: var(--qz-radius);
            margin-bottom: 16px;
            box-shadow: var(--qz-shadow);
            overflow: hidden;
            transition: box-shadow 0.2s;
            animation: qz-fadein 0.22s ease both;
        }

        .qz-qcard.answered {
            border-color: #c4b5fd;
        }

        @keyframes qz-fadein {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .qz-qcard-head {
            padding: 16px 18px 14px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            border-bottom: 1px solid var(--qz-border);
            background: var(--qz-surface);
        }

        .qz-q-num {
            background: var(--qz-purple);
            color: #fff;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 3px 10px;
            flex-shrink: 0;
            line-height: 1.7;
            margin-top: 1px;
        }

        .qz-q-text {
            font-size: 0.93rem;
            font-weight: 600;
            color: var(--qz-text);
            line-height: 1.55;
            flex: 1;
        }

        /* Options */
        .qz-options {
            padding: 4px 0;
        }

        .qz-option-label {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 18px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.12s;
            user-select: none;
        }

        .qz-option-label:last-child {
            border-bottom: none;
        }

        .qz-option-label:hover {
            background: var(--qz-purple-light);
        }

        .qz-option-label input[type="radio"] {
            display: none;
        }

        .qz-opt-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--qz-muted);
            flex-shrink: 0;
            transition: border-color 0.15s, background 0.15s, color 0.15s;
        }

        .qz-option-label input[type="radio"]:checked~.qz-opt-wrap .qz-opt-circle,
        .qz-option-label:has(input:checked) .qz-opt-circle {
            background: var(--qz-purple);
            border-color: var(--qz-purple);
            color: #fff;
        }

        .qz-option-label:has(input:checked) {
            background: var(--qz-purple-light);
        }

        .qz-opt-text {
            font-size: 0.875rem;
            color: #374151;
            line-height: 1.45;
            flex: 1;
        }

        .qz-option-label:has(input:checked) .qz-opt-text {
            color: var(--qz-purple);
            font-weight: 500;
        }

        /* ── Submit bar ── */
        .qz-submit-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 100000;
            background: var(--qz-surface);
            border-top: 1px solid var(--qz-border);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 -4px 20px rgba(111, 66, 193, 0.08);
        }

        .qz-answered-stat {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .qz-answered-stat .num {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--qz-purple);
        }

        .qz-answered-stat .lbl {
            font-size: 0.73rem;
            color: var(--qz-muted);
        }

        .qz-submit-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 12px 32px;
            background: var(--qz-purple);
            color: #fff;
            border: none;
            border-radius: 99px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
            box-shadow: 0 2px 12px rgba(111, 66, 193, 0.35);
            flex-shrink: 0;
        }

        .qz-submit-btn:hover:not(:disabled) {
            background: var(--qz-purple-dk);
            box-shadow: 0 4px 18px rgba(111, 66, 193, 0.45);
        }

        .qz-submit-btn:active:not(:disabled) {
            transform: scale(0.97);
        }

        .qz-submit-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        /* Mini dots navigator */
        .qz-dot-nav {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            max-width: 280px;
        }

        .qz-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e5e7eb;
            border: 1.5px solid #d1d5db;
            transition: background 0.15s, border-color 0.15s;
            flex-shrink: 0;
        }

        .qz-dot.done {
            background: var(--qz-purple);
            border-color: var(--qz-purple);
        }

        /* Locked */
        .form-locked {
            pointer-events: none;
            opacity: 0.6;
        }

        /* Error state */
        .qz-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--qz-danger);
            border-radius: 12px;
            padding: 20px 24px;
            text-align: center;
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* ── Responsive ── */
        @media (max-width: 575px) {
            .qz-topbar {
                padding: 0 14px;
                height: 52px;
            }

            .qz-quiz-name {
                font-size: 0.85rem;
            }

            .qz-timer {
                padding: 5px 10px;
                gap: 7px;
            }

            #qz-countdown {
                font-size: 1rem;
            }

            .qz-heading h1 {
                font-size: 1.15rem;
            }

            .qz-main {
                padding: 20px 10px 90px;
            }

            .qz-qcard-head {
                padding: 13px 14px 11px;
            }

            .qz-option-label {
                padding: 12px 14px;
            }

            .qz-submit-bar {
                padding: 10px 14px;
                gap: 10px;
            }

            .qz-submit-btn {
                padding: 10px 22px;
                font-size: 0.84rem;
            }

            .qz-dot-nav {
                display: none;
            }

            .qz-answered-stat .num {
                font-size: 0.95rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $labels = ['A', 'B', 'C', 'D'];
        $total = $examQuestions->count();
    @endphp

    <div class="qz-shell">

        {{-- ── Top bar ── --}}
        <header class="qz-topbar">
            <div class="qz-topbar-left">
                <span class="qz-quiz-name"><i class="fas fa-pen-alt me-1" style="opacity:.6;"></i>{{ $quiz->title }}</span>
                <span class="qz-quiz-meta">{{ $total }} câu &nbsp;·&nbsp; {{ $quiz->time_limit }} phút</span>
            </div>
            <div class="qz-timer" id="qz-timer">
                <i class="fas fa-clock qz-timer-icon" id="qz-clock-icon"></i>
                <div class="qz-timer-inner">
                    <span class="qz-timer-label">Còn lại</span>
                    <span id="qz-countdown">--:--</span>
                </div>
            </div>
        </header>

        {{-- Progress fill --}}
        <div class="qz-progress-bar">
            <div class="qz-progress-fill" id="qz-progress"></div>
        </div>

        {{-- ── Main ── --}}
        <main class="qz-main">
            <div class="qz-inner">

                <div class="qz-heading">
                    <h1>{{ $quiz->title }}</h1>
                    <div class="qz-heading-meta">
                        <span><i class="fas fa-list-ol me-1"></i> {{ $total }} câu hỏi</span>
                        <span><i class="fas fa-clock me-1"></i> {{ $quiz->time_limit }} phút</span>
                    </div>
                    <div><span class="qz-warn-chip"><i class="fas fa-exclamation-triangle"></i> Không tải lại trang (F5)
                            trong quá trình làm bài</span></div>
                    <div><span class="qz-warn-chip" style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;">
                            <i class="fas fa-rotate-left"></i> Nếu lỡ thoát, bạn có thể quay lại khi còn thời gian
                        </span></div>
                </div>

                <form id="quiz-form" action="{{ route('quizzes.submit', $quiz->id) }}" method="POST">
                    @csrf
                    @foreach ($examQuestions as $q)
                        <input type="hidden" name="question_ids[]" value="{{ $q->id }}">
                    @endforeach

                    @forelse ($examQuestions as $index => $question)
                        <div class="qz-qcard" id="qcard-{{ $question->id }}" style="animation-delay:{{ $index * 0.035 }}s">
                            <div class="qz-qcard-head">
                                <span class="qz-q-num">{{ $index + 1 }}</span>
                                <p class="qz-q-text mb-0">{{ $question->question_text }}</p>
                            </div>
                            <div class="qz-options">
                                @foreach ($question->options as $optIndex => $option)
                                    <label class="qz-option-label">
                                        <input type="radio" name="answers[{{ $question->id }}]"
                                            value="{{ $option->id }}" data-qid="{{ $question->id }}" class="qz-radio">
                                        <div class="qz-opt-wrap" style="display:flex;align-items:center;gap:10px;flex:1;">
                                            <div class="qz-opt-circle">{{ $labels[$optIndex] ?? '' }}</div>
                                            <span class="qz-opt-text">{{ $option->option_text }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="qz-error">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Ngân hàng câu hỏi không có đủ dữ liệu để tạo đề. Hãy báo cho giáo viên!
                        </div>
                    @endforelse
                </form>
            </div>
        </main>

        {{-- ── Submit bar ── --}}
        @if ($total > 0)
            <div class="qz-submit-bar">
                <div class="qz-dot-nav" id="qz-dots">
                    @for ($i = 0; $i < $total; $i++)
                        <div class="qz-dot" id="dot-{{ $i }}"></div>
                    @endfor
                </div>
                <div class="qz-answered-stat">
                    <span class="num"><span id="qz-answered-count">0</span>/{{ $total }}</span>
                    <span class="lbl">câu đã trả lời</span>
                </div>
                <button type="button" id="btn-submit-quiz" class="qz-submit-btn">
                    <i class="fas fa-paper-plane"></i> Nộp bài
                </button>
            </div>
        @endif

    </div>{{-- /qz-shell --}}

    @push('scripts')
        <script>
            (function() {
                const TOTAL = {{ $total }};
                let remaining = {{ $remainingSeconds ?? $quiz->time_limit * 60 }};
                let answered = 0;
                const storageKey = 'smartlms_quiz_answers_{{ auth()->id() }}_{{ $quiz->id }}';

                const timerEl = document.getElementById('qz-timer');
                const countdown = document.getElementById('qz-countdown');
                const clockIcon = document.getElementById('qz-clock-icon');
                const progressEl = document.getElementById('qz-progress');
                const countEl = document.getElementById('qz-answered-count');
                const form = document.getElementById('quiz-form');
                const btnSubmit = document.getElementById('btn-submit-quiz');

                /* ── Answered tracking ── */
                const answeredSet = new Set();

                function markAnswered(radio) {
                        const qid = this.dataset.qid;
                        const qcard = document.getElementById('qcard-' + qid);
                        const idx = parseInt(this.closest('.qz-qcard').querySelector('.qz-q-num')
                            .textContent) - 1;

                        if (!answeredSet.has(qid)) {
                            answeredSet.add(qid);
                            answered++;
                            if (countEl) countEl.textContent = answered;

                            const dot = document.getElementById('dot-' + idx);
                            if (dot) dot.classList.add('done');

                            if (qcard) qcard.classList.add('answered');
                        }

                        // Update progress
                        if (progressEl) progressEl.style.width = (answered / TOTAL * 100) + '%';
                }

                function saveDraftAnswers() {
                    const draft = {};
                    document.querySelectorAll('.qz-radio:checked').forEach(radio => {
                        draft[radio.dataset.qid] = radio.value;
                    });
                    localStorage.setItem(storageKey, JSON.stringify(draft));
                }

                function restoreDraftAnswers() {
                    try {
                        const draft = JSON.parse(localStorage.getItem(storageKey) || '{}');
                        Object.entries(draft).forEach(([qid, optionId]) => {
                            const radio = document.querySelector(`.qz-radio[data-qid="${qid}"][value="${optionId}"]`);
                            if (radio) {
                                radio.checked = true;
                                markAnswered.call(radio);
                            }
                        });
                    } catch (error) {
                        localStorage.removeItem(storageKey);
                    }
                }

                document.querySelectorAll('.qz-radio').forEach(radio => {
                    radio.addEventListener('change', function() {
                        markAnswered.call(this);
                        saveDraftAnswers();
                    });
                });

                restoreDraftAnswers();

                /* ── Countdown ── */
                function pad(n) {
                    return String(n).padStart(2, '0');
                }

                function renderCountdown() {
                    const m = Math.floor(Math.max(remaining, 0) / 60);
                    const s = Math.max(remaining, 0) % 60;
                    if (countdown) countdown.textContent = pad(m) + ':' + pad(s);
                }

                renderCountdown();

                const tick = setInterval(function() {
                    remaining--;

                    renderCountdown();

                    if (remaining < 60 && timerEl) {
                        timerEl.classList.add('urgent');
                        clockIcon && clockIcon.classList.add('fa-beat-fade');
                    }

                    if (remaining <= 0) {
                        clearInterval(tick);
                        if (countdown) countdown.textContent = '00:00';
                        if (form) form.classList.add('form-locked');
                        if (btnSubmit) btnSubmit.disabled = true;
                        window.removeEventListener('beforeunload', guard);
                        localStorage.removeItem(storageKey);
                        alert('⏳ Đã hết thời gian làm bài! Hệ thống đang tự động thu bài.');
                        form && form.submit();
                    }
                }, 1000);

                /* ── Submit ── */
                if (btnSubmit) {
                    btnSubmit.addEventListener('click', function() {
                        const msg = answered < TOTAL ?
                            `Cảnh báo: Bạn mới làm được ${answered}/${TOTAL} câu.\nBạn có chắc chắn muốn nộp bài sớm không?` :
                            'Bạn đã hoàn thành tất cả câu hỏi.\nXác nhận nộp bài?';

                        if (confirm(msg)) {
                            clearInterval(tick);
                            btnSubmit.disabled = true;
                            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Đang nộp bài...';
                            form.classList.add('form-locked');
                            window.removeEventListener('beforeunload', guard);
                            localStorage.removeItem(storageKey);
                            form.submit();
                        }
                    });
                }

                /* ── Guard reload ── */
                function guard(e) {
                    if (remaining > 0 && (!btnSubmit || !btnSubmit.disabled)) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                }
                window.addEventListener('beforeunload', guard);
            })();
        </script>
    @endpush
@endsection
