@extends('layouts.app')

@section('title', 'Làm bài: ' . $quiz->title)

@push('styles')
    <style>
        .exam-shell { position:fixed; inset:0; z-index:99999; background:#f4f7fb; display:flex; flex-direction:column; color:#182230; }
        .exam-header { min-height:68px; background:#173f8f; color:white; display:flex; align-items:center; justify-content:space-between; gap:18px; padding:10px 22px; }
        .exam-title { font-weight:800; font-size:1rem; }
        .exam-meta { opacity:.82; font-size:.78rem; margin-top:3px; }
        .exam-header-actions { display:flex; align-items:center; gap:14px; flex-wrap:wrap; justify-content:flex-end; }
        .exam-connection { font-size:.8rem; font-weight:700; display:flex; align-items:center; gap:6px; }
        .exam-connection::before { content:''; width:9px; height:9px; border-radius:50%; background:#22c55e; }
        .exam-connection.offline::before { background:#ef4444; }
        .exam-connection.saving::before { background:#f59e0b; }
        .exam-timer { background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.22); border-radius:10px; padding:7px 12px; font-size:1.1rem; font-weight:800; font-variant-numeric:tabular-nums; }
        .exam-timer.urgent { background:#b91c1c; }
        .exam-body { min-height:0; flex:1; display:grid; grid-template-columns:minmax(0,1fr) 280px; }
        .exam-content { overflow:auto; padding:28px; }
        .question-card { max-width:900px; margin:0 auto; background:white; border:1px solid #dce3ed; border-radius:16px; box-shadow:0 8px 24px rgba(15,23,42,.06); overflow:hidden; }
        .question-card.with-passage { max-width:1200px; display:grid; grid-template-columns:minmax(320px,45%) minmax(0,1fr); }
        .passage-panel { padding:24px; background:#fbfdff; border-right:1px solid #dce3ed; max-height:65vh; overflow:auto; }
        .passage-title { font-weight:800; color:#173f8f; margin-bottom:12px; }
        .passage-content { white-space:pre-wrap; line-height:1.7; font-size:.94rem; }
        .passage-source { margin-top:14px; color:#64748b; font-size:.75rem; font-style:italic; }
        .question-head { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; padding:22px 24px; border-bottom:1px solid #e8edf4; }
        .question-number { color:#173f8f; font-size:.82rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; margin-bottom:8px; }
        .question-type-pill { display:inline-flex; align-items:center; gap:5px; margin-left:8px; padding:3px 8px; border-radius:999px; background:#edf4ff; color:#1d4ed8; font-size:.68rem; font-weight:800; text-transform:none; letter-spacing:0; }
        .question-text { font-size:1.08rem; line-height:1.65; font-weight:650; }
        .question-instruction { color:#64748b; font-size:.78rem; margin-top:8px; font-weight:500; }
        .flag-button { border:1px solid #f3c96a; background:#fff9e9; color:#8a5b00; border-radius:10px; padding:8px 12px; font-weight:700; white-space:nowrap; }
        .flag-button.active { background:#f59e0b; color:#fff; border-color:#f59e0b; }
        .option-list { padding:14px 24px 22px; }
        .exam-option { display:flex; gap:12px; align-items:center; padding:14px; margin-top:10px; border:1px solid #dce3ed; border-radius:12px; cursor:pointer; transition:.15s; }
        .exam-option:hover { border-color:#82a9e8; background:#f7faff; }
        .exam-option:has(input:checked) { border-color:#2563eb; background:#eff6ff; box-shadow:0 0 0 1px #2563eb; }
        .exam-option input { width:18px; height:18px; accent-color:#2563eb; }
        .option-label { width:28px; height:28px; border-radius:50%; background:#eaf0fb; color:#173f8f; font-weight:800; display:grid; place-items:center; flex:0 0 auto; }
        .true-false-list { display:grid; gap:10px; padding:16px 24px 24px; }
        .true-false-row { display:grid; grid-template-columns:32px minmax(0,1fr) auto; gap:12px; align-items:center; border:1px solid #dce3ed; border-radius:12px; padding:12px; }
        .statement-index { width:28px; height:28px; display:grid; place-items:center; border-radius:8px; background:#ede9fe; color:#6d28d9; font-weight:800; font-size:.78rem; }
        .statement-text { font-size:.9rem; line-height:1.5; }
        .truth-buttons { display:flex; gap:6px; }
        .truth-choice { margin:0; cursor:pointer; }
        .truth-choice input { position:absolute; opacity:0; }
        .truth-choice span { min-width:54px; display:block; text-align:center; border:1px solid #cbd5e1; border-radius:8px; padding:7px 9px; color:#64748b; font-size:.75rem; font-weight:750; }
        .truth-choice input:checked + span { background:#2563eb; border-color:#2563eb; color:#fff; }
        .structured-answer { padding:18px 24px 24px; display:grid; gap:12px; }
        .blank-entry { display:grid; grid-template-columns:84px minmax(0,1fr); gap:10px; align-items:center; }
        .blank-entry label { color:#475569; font-size:.8rem; font-weight:750; }
        .structured-input { width:100%; border:1px solid #cbd5e1; border-radius:11px; padding:12px 14px; font-size:.95rem; transition:.15s; }
        .structured-input:focus { outline:0; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
        .numeric-entry { display:flex; align-items:center; gap:10px; }
        .numeric-entry .structured-input { max-width:320px; }
        .numeric-unit { padding:9px 12px; border-radius:9px; background:#f1f5f9; color:#475569; font-size:.85rem; font-weight:700; }
        .exam-nav-actions { max-width:900px; margin:18px auto 0; display:flex; justify-content:space-between; gap:12px; }
        .exam-btn { border:1px solid #cbd5e1; background:#fff; border-radius:10px; padding:10px 18px; font-weight:750; color:#334155; }
        .exam-btn.primary { background:#1677e8; border-color:#1677e8; color:white; }
        .exam-btn:disabled { opacity:.45; }
        .exam-sidebar { background:white; border-left:1px solid #dce3ed; padding:20px; overflow:auto; }
        .save-status { padding:10px 12px; border-radius:10px; background:#f0fdf4; color:#166534; font-size:.78rem; margin-bottom:16px; }
        .save-status.error { background:#fef2f2; color:#991b1b; }
        .navigator-title { font-weight:800; margin-bottom:6px; }
        .navigator-help { color:#64748b; font-size:.75rem; margin-bottom:14px; }
        .question-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:8px; }
        .question-dot { height:38px; border:1px solid #cbd5e1; background:#fff; border-radius:9px; font-weight:750; color:#475569; position:relative; }
        .question-dot.answered { background:#dcfce7; border-color:#86efac; color:#166534; }
        .question-dot.flagged::after { content:''; position:absolute; right:3px; top:3px; width:7px; height:7px; border-radius:50%; background:#f59e0b; }
        .question-dot.current { color:white; background:#2563eb; border-color:#2563eb; }
        .exam-summary { margin-top:18px; font-size:.82rem; color:#475569; display:grid; gap:5px; }
        .submit-exam { width:100%; margin-top:18px; border:0; background:#173f8f; color:white; padding:12px; border-radius:10px; font-weight:800; }
        @media(max-width:800px) {
            .exam-header { padding:8px 12px; }
            .exam-body { grid-template-columns:1fr; }
            .exam-content { padding:14px; padding-bottom:190px; }
            .exam-sidebar { position:fixed; left:0; right:0; bottom:0; height:175px; border-left:0; border-top:1px solid #dce3ed; padding:12px; z-index:2; }
            .question-grid { grid-template-columns:repeat(10,1fr); }
            .question-dot { height:30px; font-size:.75rem; }
            .navigator-help,.exam-summary { display:none; }
            .submit-exam { margin-top:10px; }
            .question-card.with-passage { grid-template-columns:1fr; }
            .passage-panel { border-right:0; border-bottom:1px solid #dce3ed; max-height:38vh; }
            .true-false-row { grid-template-columns:30px 1fr; }
            .truth-buttons { grid-column:2; }
            .blank-entry { grid-template-columns:1fr; gap:5px; }
        }
    </style>
@endpush

@section('content')
    @php
        $questions = $attempt->attemptQuestions;
        $answerMap = $questions->mapWithKeys(fn($question) => [$question->id => $question->answer?->answer_payload ?? $question->answer?->selected_option_id]);
        $answeredIds = $questions->filter(function($question) use ($answerMap) {
            $answer = $answerMap->get($question->id);
            if (is_array($answer)) return collect($answer)->contains(fn($value) => $value !== null && trim((string) $value) !== '');
            return $answer !== null && trim((string) $answer) !== '';
        })->pluck('id');
        $flags = collect($attempt->flagged_question_ids ?? [])->map(fn($id) => (int) $id);
        $initialPosition = min(max((int) $attempt->current_position, 1), max($questions->count(), 1));
    @endphp

    <div class="exam-shell">
        <header class="exam-header">
            <div>
                <div class="exam-title">{{ $quiz->title }}</div>
                <div class="exam-meta">
                    {{ auth()->user()->name }}
                    @if($attempt->session) · {{ $attempt->session->name }} @endif
                    · {{ $questions->count() }} câu
                </div>
            </div>
            <div class="exam-header-actions">
                <span class="exam-connection" id="connection-status">Đang kết nối</span>
                <span class="exam-timer" id="exam-timer">--:--</span>
            </div>
        </header>

        <div class="exam-body">
            <main class="exam-content">
                @foreach($questions as $question)
                    <section class="question-card exam-question {{ $question->passage_content ? 'with-passage' : '' }}" data-position="{{ $question->position }}"
                        data-question-id="{{ $question->id }}" data-question-type="{{ $question->question_type }}" @if($question->position !== $initialPosition) hidden @endif>
                        @if($question->passage_content)
                            <aside class="passage-panel">
                                <div class="passage-title"><i class="fa-solid fa-file-lines"></i> {{ $question->passage_title }}</div>
                                <div class="passage-content">{{ $question->passage_content }}</div>
                                @if($question->passage_source_label)<div class="passage-source">Nguồn: {{ $question->passage_source_label }}</div>@endif
                            </aside>
                        @endif
                        <div class="question-panel">
                            <div class="question-head">
                                <div>
                                    <div class="question-number">Câu {{ $question->position }} / {{ $questions->count() }} <span class="question-type-pill">{{ $question->typeLabel() }}</span></div>
                                    <div class="question-text">{{ $question->question_text }}</div>
                                    <div class="question-instruction">
                                        @switch($question->question_type)
                                            @case('multiple_choice') Chọn tất cả phương án đúng. @break
                                            @case('true_false_group') Chọn Đúng hoặc Sai cho từng nhận định. @break
                                            @case('fill_blank') Điền câu trả lời vào từng ô; không cần nhập dấu |. @break
                                            @case('numeric') Nhập giá trị số{{ data_get($question->response_schema_snapshot, 'unit') ? ' theo đơn vị '.data_get($question->response_schema_snapshot, 'unit') : '' }}. @break
                                            @default Chọn một phương án đúng nhất.
                                        @endswitch
                                    </div>
                                </div>
                                <button type="button" class="flag-button {{ $flags->contains($question->id) ? 'active' : '' }}"
                                    data-flag-question="{{ $question->id }}">
                                    <i class="fa-regular fa-flag"></i> Xem lại
                                </button>
                            </div>
                            @if(in_array($question->question_type, ['single_choice', 'multiple_choice']))
                                @php
                                    $selectedOptions = collect(is_array($answerMap->get($question->id)) ? $answerMap->get($question->id) : [$answerMap->get($question->id)])->map(fn($id) => (int) $id);
                                @endphp
                                <div class="option-list">
                                    @foreach($question->option_snapshot as $optionIndex => $option)
                                        <label class="exam-option">
                                            <input type="{{ $question->question_type === 'multiple_choice' ? 'checkbox' : 'radio' }}" name="answer_{{ $question->id }}{{ $question->question_type === 'multiple_choice' ? '[]' : '' }}" value="{{ $option['id'] }}"
                                                data-answer-question="{{ $question->id }}" @checked($selectedOptions->contains((int) $option['id']))>
                                            <span class="option-label">{{ chr(65 + $optionIndex) }}</span><span>{{ $option['text'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif($question->question_type === 'true_false_group')
                                @php
                                    $truthAnswers = is_array($answerMap->get($question->id)) ? $answerMap->get($question->id) : [];
                                @endphp
                                <div class="true-false-list">
                                    @foreach($question->option_snapshot as $statementIndex => $statement)
                                        <div class="true-false-row">
                                            <span class="statement-index">{{ $statementIndex + 1 }}</span><span class="statement-text">{{ $statement['text'] }}</span>
                                            <div class="truth-buttons">
                                                @foreach(['1' => 'Đúng', '0' => 'Sai'] as $value => $label)
                                                    <label class="truth-choice"><input type="radio" name="truth_{{ $question->id }}_{{ $statement['id'] }}" value="{{ $value }}" data-answer-question="{{ $question->id }}" data-statement-id="{{ $statement['id'] }}" @checked(array_key_exists((string) $statement['id'], $truthAnswers) && (bool) $truthAnswers[(string) $statement['id']] === ($value === '1'))><span>{{ $label }}</span></label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($question->question_type === 'fill_blank')
                                @php
                                    $blankAnswers = is_array($answerMap->get($question->id)) ? $answerMap->get($question->id) : [];
                                @endphp
                                <div class="structured-answer">
                                    @for($blankIndex = 0; $blankIndex < (int) data_get($question->response_schema_snapshot, 'blank_count', 0); $blankIndex++)
                                        <div class="blank-entry"><label for="blank_{{ $question->id }}_{{ $blankIndex }}">Ô trống {{ $blankIndex + 1 }}</label><input id="blank_{{ $question->id }}_{{ $blankIndex }}" class="structured-input" type="text" value="{{ $blankAnswers[$blankIndex] ?? '' }}" maxlength="1000" autocomplete="off" data-answer-question="{{ $question->id }}" data-blank-index="{{ $blankIndex }}" placeholder="Nhập câu trả lời"></div>
                                    @endfor
                                </div>
                            @else
                                <div class="structured-answer"><div class="numeric-entry"><input class="structured-input" type="text" inputmode="decimal" value="{{ $answerMap->get($question->id) }}" maxlength="100" autocomplete="off" data-answer-question="{{ $question->id }}" placeholder="Nhập kết quả số">@if(data_get($question->response_schema_snapshot, 'unit'))<span class="numeric-unit">{{ data_get($question->response_schema_snapshot, 'unit') }}</span>@endif</div></div>
                            @endif
                        </div>
                    </section>
                @endforeach

                <div class="exam-nav-actions">
                    <button type="button" class="exam-btn" id="previous-question"><i class="fa-solid fa-arrow-left"></i> Câu trước</button>
                    <button type="button" class="exam-btn primary" id="next-question">Câu tiếp <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </main>

            <aside class="exam-sidebar">
                <div class="save-status" id="save-status"><i class="fa-solid fa-cloud-check"></i> Mọi thay đổi đã được lưu</div>
                <div class="navigator-title">Danh sách câu hỏi</div>
                <div class="navigator-help">Xanh lá: đã trả lời · Chấm cam: cần xem lại</div>
                <div class="question-grid">
                    @foreach($questions as $question)
                        <button type="button" class="question-dot {{ $answeredIds->contains($question->id) ? 'answered' : '' }} {{ $flags->contains($question->id) ? 'flagged' : '' }} {{ $question->position === $initialPosition ? 'current' : '' }}"
                            data-go-position="{{ $question->position }}">{{ $question->position }}</button>
                    @endforeach
                </div>
                <div class="exam-summary">
                    <div>Đã trả lời: <strong id="answered-count">{{ $answeredIds->count() }}</strong>/{{ $questions->count() }}</div>
                    <div>Đánh dấu xem lại: <strong id="flagged-count">{{ $flags->count() }}</strong></div>
                </div>
                <form method="POST" action="{{ route('quizzes.submit', $quiz) }}" id="submit-form">
                    @csrf
                    <input type="hidden" name="attempt_id" value="{{ $attempt->id }}">
                    <button type="button" class="submit-exam" id="submit-button"><i class="fa-solid fa-paper-plane"></i> Nộp bài</button>
                </form>
            </aside>
        </div>
    </div>

    <script>
        (() => {
            const csrf = @json(csrf_token());
            const autosaveUrl = @json(route('quizzes.autosave', $attempt));
            const heartbeatUrl = @json(route('quizzes.heartbeat', $attempt));
            const total = {{ $questions->count() }};
            let current = {{ $initialPosition }};
            let remaining = {{ $remainingSeconds }};
            let submitting = false;
            const flags = new Set(@json($flags->values()));
            const pendingQuestions = new Set();
            const inputTimers = new Map();
            const saveStatus = document.getElementById('save-status');
            const connectionStatus = document.getElementById('connection-status');

            const request = async (url, method, payload) => {
                const response = await fetch(url, {
                    method,
                    credentials: 'same-origin',
                    headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
                    body: JSON.stringify(payload)
                });
                if (!response.ok) throw new Error(response.status === 422 ? 'Không thể lưu thay đổi' : 'Mất kết nối máy chủ');
                return response.json();
            };

            const setConnection = (state, text) => {
                connectionStatus.className = 'exam-connection' + (state ? ` ${state}` : '');
                connectionStatus.textContent = text;
            };

            const updateCounts = () => {
                document.getElementById('answered-count').textContent = document.querySelectorAll('.question-dot.answered').length;
                document.getElementById('flagged-count').textContent = flags.size;
            };

            const collectAnswer = questionId => {
                const card = document.querySelector(`[data-question-id="${questionId}"]`);
                const type = card.dataset.questionType;
                const inputs = Array.from(card.querySelectorAll('[data-answer-question]'));
                if (type === 'single_choice') {
                    const checked = inputs.find(input => input.checked);
                    return checked ? Number(checked.value) : null;
                }
                if (type === 'multiple_choice') return inputs.filter(input => input.checked).map(input => Number(input.value));
                if (type === 'true_false_group') {
                    return Object.fromEntries(inputs.filter(input => input.checked).map(input => [input.dataset.statementId, input.value === '1']));
                }
                if (type === 'fill_blank') return inputs.sort((a, b) => Number(a.dataset.blankIndex) - Number(b.dataset.blankIndex)).map(input => input.value.trim());
                return inputs[0]?.value.trim() ?? '';
            };

            const answerIsComplete = questionId => {
                const card = document.querySelector(`[data-question-id="${questionId}"]`);
                const type = card.dataset.questionType;
                const answer = collectAnswer(questionId);
                if (type === 'true_false_group') return Object.keys(answer).length === card.querySelectorAll('[data-statement-id]').length / 2;
                if (type === 'fill_blank') return answer.length > 0 && answer.every(value => value !== '');
                if (Array.isArray(answer)) return answer.length > 0;
                return answer !== null && answer !== '';
            };

            const updateQuestionState = questionId => {
                const card = document.querySelector(`[data-question-id="${questionId}"]`);
                const dot = document.querySelector(`[data-go-position="${card.dataset.position}"]`);
                dot.classList.toggle('answered', answerIsComplete(questionId));
                updateCounts();
            };

            const showQuestion = (position) => {
                current = Math.min(Math.max(position, 1), total);
                document.querySelectorAll('.exam-question').forEach(el => el.hidden = Number(el.dataset.position) !== current);
                document.querySelectorAll('[data-go-position]').forEach(el => el.classList.toggle('current', Number(el.dataset.goPosition) === current));
                document.getElementById('previous-question').disabled = current === 1;
                document.getElementById('next-question').disabled = current === total;
                document.querySelector('.exam-content').scrollTop = 0;
            };

            const saveQuestion = async (questionId) => {
                pendingQuestions.add(Number(questionId));
                setConnection('saving', 'Đang lưu');
                saveStatus.className = 'save-status';
                saveStatus.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang lưu đáp án...';
                try {
                    const data = await request(autosaveUrl, 'PUT', {
                        attempt_question_id: Number(questionId),
                        answer: collectAnswer(questionId),
                        flagged: flags.has(Number(questionId)),
                        current_position: current
                    });
                    remaining = Math.min(remaining, Number(data.remaining_seconds));
                    pendingQuestions.delete(Number(questionId));
                    setConnection('', 'Đang kết nối');
                    saveStatus.innerHTML = `<i class="fa-solid fa-cloud-check"></i> Đã lưu lúc ${data.saved_at}`;
                } catch (error) {
                    setConnection('offline', 'Chưa đồng bộ');
                    saveStatus.className = 'save-status error';
                    saveStatus.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Chưa lưu được. Hệ thống sẽ thử lại.';
                    throw error;
                }
            };

            const retryPending = async () => {
                if (!navigator.onLine || submitting || pendingQuestions.size === 0) return;
                for (const questionId of Array.from(pendingQuestions)) {
                    try { await saveQuestion(questionId); } catch (_) { break; }
                }
            };

            document.querySelectorAll('[data-answer-question]').forEach(input => {
                const eventName = ['text', 'number'].includes(input.type) ? 'input' : 'change';
                input.addEventListener(eventName, event => {
                    const id = Number(event.target.dataset.answerQuestion);
                    updateQuestionState(id);
                    clearTimeout(inputTimers.get(id));
                    inputTimers.set(id, setTimeout(async () => {
                        try { await saveQuestion(id); } catch (_) { setTimeout(() => saveQuestion(id).catch(() => {}), 3000); }
                    }, eventName === 'input' ? 650 : 0));
                });
            });

            document.querySelectorAll('[data-flag-question]').forEach(button => button.addEventListener('click', async () => {
                const id = Number(button.dataset.flagQuestion);
                flags.has(id) ? flags.delete(id) : flags.add(id);
                button.classList.toggle('active', flags.has(id));
                document.querySelector(`[data-go-position="${button.closest('.exam-question').dataset.position}"]`).classList.toggle('flagged', flags.has(id));
                updateCounts();
                try { await saveQuestion(id); } catch (_) {}
            }));

            document.querySelectorAll('[data-go-position]').forEach(button => button.addEventListener('click', () => showQuestion(Number(button.dataset.goPosition))));
            document.getElementById('previous-question').addEventListener('click', () => showQuestion(current - 1));
            document.getElementById('next-question').addEventListener('click', () => showQuestion(current + 1));

            const renderTimer = () => {
                const timer = document.getElementById('exam-timer');
                const minutes = Math.floor(Math.max(remaining, 0) / 60);
                const seconds = Math.max(remaining, 0) % 60;
                timer.textContent = `${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
                timer.classList.toggle('urgent', remaining <= 300);
            };
            renderTimer();
            setInterval(() => {
                remaining--;
                renderTimer();
                if (remaining <= 0 && !submitting) {
                    submitting = true;
                    document.getElementById('submit-form').submit();
                }
            }, 1000);

            setInterval(async () => {
                if (submitting) return;
                try {
                    const data = await request(heartbeatUrl, 'POST', {current_position: current});
                    remaining = Math.min(remaining, Number(data.remaining_seconds));
                    setConnection('', 'Đang kết nối');
                } catch (_) { setConnection('offline', 'Mất kết nối'); }
            }, 20000);

            window.addEventListener('online', () => {
                setConnection('', 'Đang kết nối');
                retryPending();
            });
            window.addEventListener('offline', () => setConnection('offline', 'Mất kết nối'));
            setInterval(retryPending, 5000);
            window.addEventListener('beforeunload', event => {
                if (!submitting) { event.preventDefault(); event.returnValue = ''; }
            });

            document.getElementById('submit-button').addEventListener('click', () => {
                const answered = document.querySelectorAll('.question-dot.answered').length;
                const message = `Bạn đã trả lời ${answered}/${total} câu và đánh dấu ${flags.size} câu.\nXác nhận nộp bài?`;
                if (!confirm(message)) return;
                submitting = true;
                document.getElementById('submit-button').disabled = true;
                document.getElementById('submit-button').textContent = 'Đang nộp bài...';
                document.getElementById('submit-form').submit();
            });

            showQuestion(current);
        })();
    </script>
@endsection
