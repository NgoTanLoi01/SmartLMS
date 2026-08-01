@extends('layouts.app')

@section('title', 'Chấm bài: '.$attempt->user->name)

@push('styles')
<style>
    .grade-workspace { max-width: 1380px; }
    .grade-topbar { padding: 24px 26px; background: linear-gradient(135deg, #123b86, #1768dd); color: #fff; border-radius: 22px; box-shadow: 0 16px 42px rgba(17, 66, 145, .18); }
    .grade-topbar__title { font-size: clamp(1.3rem, 2vw, 1.8rem); font-weight: 800; margin: 4px 0; }
    .grade-progress { height: 8px; background: rgba(255, 255, 255, .2); border-radius: 99px; overflow: hidden; }
    .grade-progress > span { display: block; height: 100%; background: #64e6ad; border-radius: inherit; }
    .grade-card { border: 1px solid #e4eaf3; border-radius: 20px; box-shadow: 0 10px 30px rgba(28, 51, 84, .06); overflow: hidden; scroll-margin-top: 90px; }
    .grade-card__head { padding: 18px 22px; background: #fff; border-bottom: 1px solid #edf0f5; }
    .grade-card__number { color: #1c62d6; font-size: .75rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; }
    .grade-card__question { color: #17243d; font-size: 1.03rem; font-weight: 750; line-height: 1.55; margin-top: 6px; }
    .grade-card__body { padding: 22px; background: #fff; }
    .answer-panel { padding: 18px; color: #24324a; background: #f7f9fc; border: 1px solid #e3e8f0; border-radius: 14px; line-height: 1.75; white-space: pre-wrap; }
    .grade-code { min-height: 260px; max-height: 480px; padding: 18px; overflow: auto; color: #dce7ff; background: #101a2d; border-radius: 14px; font: .86rem/1.65 'DM Mono', monospace; white-space: pre-wrap; }
    .rubric-box { margin-top: 22px; padding: 20px; background: #fbfcfe; border: 1px solid #dfe6f0; border-radius: 16px; }
    .rubric-item { display: grid; grid-template-columns: minmax(0, 1fr) 150px; align-items: center; gap: 16px; padding: 13px 0; border-bottom: 1px solid #e9edf3; }
    .rubric-item:last-child { border-bottom: 0; }
    .rubric-score-wrap { display: flex; align-items: center; overflow: hidden; background: #fff; border: 1px solid #cad5e4; border-radius: 11px; }
    .rubric-score-wrap input { width: 100%; min-width: 0; padding: 10px 8px 10px 12px; border: 0; outline: 0; font-weight: 800; }
    .rubric-score-wrap span { padding: 0 11px; color: #748198; border-left: 1px solid #e2e7ef; white-space: nowrap; }
    .rubric-total { display: inline-flex; align-items: center; gap: 5px; padding: 8px 12px; color: #174fa9; background: #eaf2ff; border-radius: 10px; font-weight: 800; }
    .grade-side { position: sticky; top: 86px; }
    .grade-side-card { padding: 20px; background: #fff; border: 1px solid #e4eaf3; border-radius: 18px; box-shadow: 0 10px 30px rgba(28, 51, 84, .06); }
    .question-jump { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
    .question-jump a { display: grid; place-items: center; height: 40px; color: #68768d; text-decoration: none; background: #f4f6fa; border: 1px solid #e1e6ee; border-radius: 10px; font-weight: 800; }
    .question-jump a.manual-pending { color: #9a5a00; background: #fff3d6; border-color: #f2cc7a; }
    .question-jump a.manual-graded { color: #087047; background: #e0f8ed; border-color: #99dfbf; }
    .grade-status { display: inline-flex; align-items: center; gap: 7px; padding: 7px 11px; border-radius: 999px; font-size: .76rem; font-weight: 800; }
    .grade-status.pending { color: #945600; background: #fff1cf; }
    .grade-status.graded { color: #09603e; background: #ddf8eb; }
    .grade-status.released { color: #114fa9; background: #e7f0ff; }
    .grade-actions { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-top: 18px; }
    @media (max-width: 991.98px) { .grade-side { position: static; } }
    @media (max-width: 575.98px) { .grade-topbar { padding: 20px; border-radius: 18px; } .grade-card__head, .grade-card__body { padding: 17px; } .rubric-item { grid-template-columns: 1fr; gap: 8px; } }
</style>
@endpush

@section('content')
@php
    $attemptStatus = match($attempt->status) {
        'pending_grading' => ['pending', 'fa-hourglass-half', 'Đang chờ chấm'],
        'released' => ['released', 'fa-paper-plane', 'Đã công bố'],
        default => ['graded', 'fa-circle-check', 'Đã chấm xong'],
    };
    $isReleased = $attempt->status === 'released';
@endphp
<div class="container-fluid grade-workspace py-4">
    <section class="grade-topbar mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <a href="{{ route('quizzes.submissions', $attempt->quiz) }}" class="btn btn-sm btn-light bg-white bg-opacity-10 border border-white border-opacity-25 text-white rounded-pill px-3 mb-3"><i class="fa-solid fa-arrow-left me-1"></i> Danh sách bài nộp</a>
                <div class="small text-uppercase fw-bold opacity-75">{{ $attempt->quiz->title }}</div>
                <h1 class="grade-topbar__title">{{ $attempt->user->name }}</h1>
                <div class="opacity-75 small">{{ $attempt->user->student_code ?: $attempt->user->email }} · Lượt {{ $attempt->attempt_number ?? 1 }} · Nộp lúc {{ $attempt->completed_at?->format('H:i, d/m/Y') }}</div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex justify-content-between small fw-bold mb-2"><span>Tiến độ chấm câu tự luận</span><span>{{ $gradingProgress['graded'] }}/{{ $gradingProgress['total'] }}</span></div>
                <div class="grade-progress"><span style="width:{{ $gradingProgress['percent'] }}%"></span></div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <span class="grade-status {{ $attemptStatus[0] }}"><i class="fa-solid {{ $attemptStatus[1] }}"></i>{{ $attemptStatus[2] }}</span>
                    <strong class="fs-5">{{ $attempt->score !== null ? number_format((float) $attempt->score, 2).'/10' : 'Chưa có điểm' }}</strong>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-4 shadow-sm"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 rounded-4 shadow-sm"><div class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation me-2"></i>Chưa thể lưu kết quả</div><ul class="mb-0 ps-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-4 align-items-start">
        <main class="col-xl-9 col-lg-8">
            @foreach($attempt->attemptQuestions as $question)
                @php
                    $answer = $question->answer;
                    $payload = $answer?->answer_payload ?? [];
                    $rubric = collect($question->gradingRubric());
                    $manual = $question->requiresManualGrading();
                    $graded = $answer?->grading_status === 'graded';
                @endphp
                <article id="grade-question-{{ $question->id }}" class="grade-card mb-4">
                    <header class="grade-card__head d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="grade-card__number">Câu {{ $question->position }} · {{ $question->typeLabel() }} · {{ number_format((float) $question->max_score, 2) }} điểm</div>
                            <div class="grade-card__question">{{ $question->question_text }}</div>
                        </div>
                        <span class="grade-status {{ $manual ? ($graded ? 'graded' : 'pending') : 'released' }}">
                            <i class="fa-solid {{ $manual ? ($graded ? 'fa-circle-check' : 'fa-hourglass-half') : 'fa-bolt' }}"></i>
                            {{ $manual ? ($graded ? 'Đã chấm' : 'Chờ chấm') : 'Tự động' }}
                        </span>
                    </header>
                    <div class="grade-card__body">
                        @if($question->question_type === 'essay')
                            <div class="small fw-bold text-muted mb-2">Bài làm của học viên</div>
                            <div class="answer-panel">{{ $payload['text'] ?? 'Thí sinh không nhập nội dung.' }}</div>
                        @elseif($question->question_type === 'code_debug')
                            <div class="row g-3">
                                <div class="col-xl-7"><div class="small fw-bold text-muted mb-2">Mã HTML/CSS đã sửa</div><pre class="grade-code mb-0"><code>{{ $payload['code'] ?? '' }}</code></pre></div>
                                <div class="col-xl-5"><div class="small fw-bold text-muted mb-2">Xem trước an toàn</div><iframe sandbox="" title="Xem trước HTML/CSS của học viên" class="w-100 rounded-3 border bg-white" style="min-height:260px" data-grade-preview data-code="{{ base64_encode($payload['code'] ?? '') }}"></iframe></div>
                            </div>
                            @if(data_get($question->response_schema_snapshot, 'explanation_mode') !== 'disabled')
                                <div class="mt-3"><div class="small fw-bold text-muted mb-2">Giải thích của học viên</div><div class="answer-panel">{{ $payload['explanation'] ?: 'Không có giải thích.' }}</div></div>
                            @endif
                        @else
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 rounded-3 bg-light border">
                                <span class="text-muted"><i class="fa-solid fa-bolt text-primary me-2"></i>Câu này đã được hệ thống tự động chấm.</span>
                                <strong>{{ number_format((float) ($answer?->score ?? 0), 2) }}/{{ number_format((float) $question->max_score, 2) }} điểm</strong>
                            </div>
                        @endif

                        @if($question->attachments->isNotEmpty())
                            <div class="mt-3"><div class="small fw-bold text-muted mb-2">Tệp đính kèm</div><div class="d-flex flex-wrap gap-2">@foreach($question->attachments as $file)<a class="btn btn-sm btn-outline-primary rounded-pill" href="{{ route('quiz-attempt-attachments.download', $file) }}"><i class="fa-solid fa-paperclip me-1"></i>{{ $file->original_name }}</a>@endforeach</div></div>
                        @endif

                        @if($manual)
                            <form class="rubric-box" method="POST" action="{{ route('quiz-attempts.grade-answer', [$attempt, $answer]) }}" data-rubric-form>
                                @csrf
                                @method('PUT')
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                    <div><h5 class="fw-bold mb-1">Chấm theo rubric</h5><div class="small text-muted">Nhập điểm cho từng tiêu chí, sau đó lưu nháp hoặc hoàn tất câu.</div></div>
                                    <span class="rubric-total"><span data-rubric-current>0.00</span> / {{ number_format((float) $question->max_score, 2) }}</span>
                                </div>
                                @foreach($rubric as $index => $criterion)
                                    <div class="rubric-item">
                                        <label for="rubric-{{ $question->id }}-{{ $index }}"><span class="fw-bold d-block">{{ $criterion['criterion'] }}</span><small class="text-muted">Tối đa {{ number_format((float) $criterion['max_score'], 2) }} điểm</small></label>
                                        <div class="rubric-score-wrap">
                                            <input id="rubric-{{ $question->id }}-{{ $index }}" type="number" name="rubric_scores[{{ $index }}]" min="0" max="{{ $criterion['max_score'] }}" step="0.01" value="{{ old('rubric_scores.'.$index, data_get($answer?->rubric_scores, $index)) }}" placeholder="0" data-rubric-score {{ $isReleased ? 'disabled' : '' }}>
                                            <span>/ {{ number_format((float) $criterion['max_score'], 2) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="mt-3">
                                    <label class="form-label fw-bold" for="feedback-{{ $question->id }}">Phản hồi cho học viên</label>
                                    <textarea id="feedback-{{ $question->id }}" class="form-control rounded-3" name="teacher_feedback" rows="4" maxlength="10000" placeholder="Nêu điểm làm tốt, nội dung cần cải thiện và hướng khắc phục..." {{ $isReleased ? 'disabled' : '' }}>{{ old('teacher_feedback', $answer?->teacher_feedback) }}</textarea>
                                    <div class="form-text">Phản hồi chỉ hiển thị với học viên sau khi điểm được công bố.</div>
                                </div>
                                @if(!$isReleased)
                                    <div class="grade-actions">
                                        <button class="btn btn-outline-secondary rounded-pill px-4" name="intent" value="draft"><i class="fa-regular fa-floppy-disk me-1"></i>Lưu nháp</button>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button class="btn btn-primary rounded-pill px-4 fw-bold" name="intent" value="complete"><i class="fa-solid fa-circle-check me-1"></i>{{ $graded ? 'Cập nhật điểm' : 'Hoàn tất câu' }}</button>
                                            @if($nextPendingAttempt)
                                                <button class="btn btn-dark rounded-pill px-4 fw-bold" name="intent" value="complete_next">Lưu & bài tiếp theo <i class="fa-solid fa-arrow-right ms-1"></i></button>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-primary border-0 mt-3 mb-0"><i class="fa-solid fa-lock me-2"></i>Điểm đã công bố. Phiếu chấm hiện ở chế độ chỉ đọc.</div>
                                @endif
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </main>

        <aside class="col-xl-3 col-lg-4">
            <div class="grade-side">
                <section class="grade-side-card mb-3">
                    <h6 class="fw-bold mb-3">Danh sách câu hỏi</h6>
                    <div class="question-jump">
                        @foreach($attempt->attemptQuestions as $question)
                            <a href="#grade-question-{{ $question->id }}" class="{{ $question->requiresManualGrading() ? ($question->answer?->grading_status === 'graded' ? 'manual-graded' : 'manual-pending') : '' }}" title="Câu {{ $question->position }}: {{ $question->typeLabel() }}">{{ $question->position }}</a>
                        @endforeach
                    </div>
                    <div class="small text-muted mt-3"><span class="text-warning me-2">●</span>Chờ chấm <span class="text-success ms-3 me-2">●</span>Đã chấm</div>
                </section>

                <section class="grade-side-card">
                    <div class="small text-muted text-uppercase fw-bold">Tổng điểm</div>
                    <div class="display-6 fw-bold text-primary my-2">{{ $attempt->score !== null ? number_format((float) $attempt->score, 2) : '—' }}<small class="fs-6 text-muted">/10</small></div>
                    <div class="small text-muted mb-3">Tự động: {{ number_format((float) ($attempt->auto_score ?? 0), 2) }} · Thủ công: {{ $attempt->manual_score !== null ? number_format((float) $attempt->manual_score, 2) : '—' }}</div>
                    @if($attempt->status === 'graded')
                        <form method="POST" action="{{ route('quiz-attempts.release', $attempt) }}" onsubmit="return confirm('Sau khi công bố, học viên sẽ xem được điểm và phản hồi. Tiếp tục?')">
                            @csrf
                            <button class="btn btn-success w-100 rounded-pill fw-bold py-2"><i class="fa-solid fa-paper-plane me-2"></i>Công bố điểm</button>
                        </form>
                    @elseif($isReleased)
                        <div class="alert alert-primary border-0 mb-0"><i class="fa-solid fa-circle-check me-2"></i>Đã công bố lúc {{ $attempt->result_released_at?->format('H:i d/m/Y') }}</div>
                    @else
                        <button class="btn btn-secondary w-100 rounded-pill" disabled><i class="fa-solid fa-lock me-2"></i>Chấm đủ câu để công bố</button>
                    @endif
                    @if($nextPendingAttempt)
                        <a class="btn btn-outline-primary w-100 rounded-pill mt-2" href="{{ route('quiz-attempts.grade', $nextPendingAttempt) }}">Bài đang chờ tiếp theo <i class="fa-solid fa-arrow-right ms-1"></i></a>
                    @endif
                </section>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-grade-preview]').forEach(frame => {
    const bytes = Uint8Array.from(atob(frame.dataset.code || ''), char => char.charCodeAt(0));
    const code = new TextDecoder().decode(bytes);
    frame.srcdoc = `<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline'; img-src data:; font-src data:"></head><body>${code}</body></html>`;
});

document.querySelectorAll('[data-rubric-form]').forEach(form => {
    const output = form.querySelector('[data-rubric-current]');
    const scores = [...form.querySelectorAll('[data-rubric-score]')];
    const updateTotal = () => {
        const total = scores.reduce((sum, field) => sum + (Number.parseFloat(field.value) || 0), 0);
        output.textContent = total.toFixed(2);
    };
    scores.forEach(field => field.addEventListener('input', updateTotal));
    updateTotal();
});
</script>
@endpush
