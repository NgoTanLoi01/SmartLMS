@extends('layouts.app')

@section('title', 'Chấm bài: '.$attempt->user->name)

@section('content')
<div class="container py-4" style="max-width:1180px">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('quizzes.submissions', $attempt->quiz) }}" class="btn btn-sm btn-outline-primary rounded-pill mb-3"><i class="fa-solid fa-arrow-left"></i> Danh sách bài nộp</a>
            <h3 class="fw-bold mb-1">Chấm bài: {{ $attempt->user->name }}</h3>
            <div class="text-muted">{{ $attempt->quiz->title }} · Nộp lúc {{ $attempt->completed_at?->format('H:i d/m/Y') }}</div>
        </div>
        <div class="card border-0 shadow-sm rounded-4 px-4 py-3">
            <div class="small text-muted text-uppercase fw-bold">Trạng thái</div>
            <div class="fw-bold {{ $attempt->status === 'pending_grading' ? 'text-warning' : 'text-success' }}">
                {{ $attempt->status === 'pending_grading' ? 'Đang chờ chấm' : ($attempt->status === 'released' ? 'Đã công bố' : 'Đã chấm xong') }}
            </div>
            <div class="small text-muted mt-1">Điểm hiện tại: {{ $attempt->score !== null ? number_format($attempt->score, 2).'/10' : 'Chưa có điểm cuối' }}</div>
        </div>
    </div>

    @foreach($attempt->attemptQuestions as $question)
        @php
            $answer = $question->answer;
            $payload = $answer?->answer_payload ?? [];
            $rubric = collect(data_get($question->answer_key_snapshot, 'rubric', []));
        @endphp
        <article class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-white px-4 py-3 d-flex justify-content-between align-items-start gap-3">
                <div><div class="text-primary small fw-bold text-uppercase">Câu {{ $question->position }} · {{ $question->typeLabel() }}</div><div class="fw-bold mt-1">{{ $question->question_text }}</div></div>
                <span class="badge rounded-pill {{ $question->requiresManualGrading() ? ($answer?->grading_status === 'graded' ? 'bg-success' : 'bg-warning text-dark') : 'bg-light text-dark' }}">
                    {{ $question->requiresManualGrading() ? ($answer?->grading_status === 'graded' ? 'Đã chấm' : 'Chờ chấm') : 'Tự động: '.number_format((float) ($answer?->score ?? 0), 2).'/'.number_format((float) $question->max_score, 2) }}
                </span>
            </div>
            <div class="card-body p-4">
                @if($question->question_type === 'essay')
                    <div class="p-3 rounded-3 border bg-light" style="white-space:pre-wrap;line-height:1.7">{{ $payload['text'] ?? 'Thí sinh không nhập nội dung.' }}</div>
                @elseif($question->question_type === 'code_debug')
                    <div class="row g-3">
                        <div class="col-lg-6"><div class="small fw-bold text-muted mb-2">Mã đã sửa</div><pre class="p-3 rounded-3 bg-dark text-light mb-0" style="min-height:260px;max-height:480px;overflow:auto"><code>{{ $payload['code'] ?? '' }}</code></pre></div>
                        <div class="col-lg-6"><div class="small fw-bold text-muted mb-2">Xem trước sandbox</div><iframe sandbox="" class="w-100 rounded-3 border" style="min-height:260px" data-grade-preview data-code="{{ base64_encode($payload['code'] ?? '') }}"></iframe></div>
                    </div>
                    @if(data_get($question->response_schema_snapshot, 'explanation_mode') !== 'disabled')
                        <div class="mt-3"><div class="small fw-bold text-muted mb-2">Giải thích của thí sinh</div><div class="p-3 rounded-3 border bg-light" style="white-space:pre-wrap">{{ $payload['explanation'] ?? 'Không có giải thích.' }}</div></div>
                    @endif
                @else
                    <div class="text-muted">Câu này đã được hệ thống tự động chấm.</div>
                @endif

                @if($question->attachments->isNotEmpty())
                    <div class="mt-3"><div class="small fw-bold text-muted mb-2">Tệp đính kèm</div><div class="d-flex flex-wrap gap-2">@foreach($question->attachments as $file)<a class="btn btn-sm btn-outline-primary" href="{{ route('quiz-attempt-attachments.download', $file) }}"><i class="fa-solid fa-paperclip"></i> {{ $file->original_name }}</a>@endforeach</div></div>
                @endif

                @if($question->requiresManualGrading())
                    <form class="mt-4 pt-4 border-top" method="POST" action="{{ route('quiz-attempts.grade-answer', [$attempt, $answer]) }}">
                        @csrf @method('PUT')
                        <h6 class="fw-bold mb-3">Rubric chấm điểm</h6>
                        <div class="row g-3">
                            @foreach($rubric as $index => $criterion)
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">{{ $criterion['criterion'] }} <span class="text-muted">/ {{ number_format((float) $criterion['max_score'], 2) }}</span></label>
                                    <input class="form-control" type="number" name="rubric_scores[{{ $index }}]" min="0" max="{{ $criterion['max_score'] }}" step="0.01" value="{{ old('rubric_scores.'.$index, data_get($answer?->rubric_scores, $index, 0)) }}" required>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3"><label class="form-label small fw-bold">Phản hồi cho thí sinh</label><textarea class="form-control" name="teacher_feedback" rows="4" maxlength="10000" placeholder="Nêu điểm tốt và nội dung cần cải thiện...">{{ old('teacher_feedback', $answer?->teacher_feedback) }}</textarea></div>
                        <div class="d-flex justify-content-between align-items-center gap-3 mt-3"><small class="text-muted">Điểm được tính theo tổng các tiêu chí. Có thể chấm lại trước khi công bố.</small><button class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk"></i> Lưu điểm câu này</button></div>
                    </form>
                @endif
            </div>
        </article>
    @endforeach
</div>

<script>
document.querySelectorAll('[data-grade-preview]').forEach(frame => {
    const bytes = Uint8Array.from(atob(frame.dataset.code || ''), char => char.charCodeAt(0));
    const code = new TextDecoder().decode(bytes);
    frame.srcdoc = `<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline'; img-src data:; font-src data:"></head><body>${code}</body></html>`;
});
</script>
@endsection
