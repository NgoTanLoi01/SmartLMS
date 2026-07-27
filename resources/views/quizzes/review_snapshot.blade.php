@extends('layouts.app')

@section('title', 'Chi tiết bài làm: ' . $attempt->quiz->title)

@section('content')
    <div class="container py-4" style="max-width:900px">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
            <div>
                <a href="{{ route('courses.show', $attempt->quiz->course_id) }}" class="btn btn-outline-primary rounded-pill mb-2">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại khóa học
                </a>
                <h3 class="fw-bold mb-1">{{ $attempt->quiz->title }}</h3>
                @if($attempt->session)<div class="text-muted">{{ $attempt->session->name }}</div>@endif
            </div>
            <div class="card border-0 shadow-sm rounded-4 px-4 py-3 text-center">
                <div class="small text-muted fw-bold">KẾT QUẢ</div>
                <div class="fs-2 fw-bold {{ $attempt->score >= 5 ? 'text-success' : 'text-danger' }}">{{ $attempt->score }}/10</div>
            </div>
        </div>

        @foreach($attempt->attemptQuestions as $question)
            @php
                $answer = $question->answer?->answer_payload ?? $question->answer?->selected_option_id;
                $isCorrect = (bool) $question->answer?->is_correct;
                $hasAnswer = is_array($answer) ? collect($answer)->contains(fn($value) => !is_array($value) && $value !== null && trim((string) $value) !== '') : $answer !== null && trim((string) $answer) !== '';
                $isManual = $question->requiresManualGrading();
            @endphp
            <article class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
                @if($question->passage_content)
                    <div class="p-4 bg-light border-bottom">
                        <div class="fw-bold text-primary mb-2"><i class="fa-solid fa-file-lines"></i> {{ $question->passage_title }}</div>
                        <div style="white-space:pre-wrap;line-height:1.65">{{ $question->passage_content }}</div>
                    </div>
                @endif
                <div class="card-header bg-white p-4 border-bottom">
                    <div class="d-flex align-items-start gap-2 flex-wrap"><div class="fw-bold flex-grow-1"><span class="{{ $isManual ? 'text-primary' : ($isCorrect ? 'text-success' : 'text-danger') }}">Câu {{ $question->position }}.</span> {{ $question->question_text }}</div><span class="badge rounded-pill text-bg-light border">{{ $question->typeLabel() }}</span></div>
                    @if(!$hasAnswer)<span class="badge bg-secondary mt-2">Chưa trả lời</span>@endif
                </div>
                <div class="card-body p-4">
                    @if(in_array($question->question_type, ['single_choice', 'multiple_choice']))
                        @php
                            $selectedIds = collect(is_array($answer) ? $answer : [$answer])->map(fn($id) => (int) $id);
                            $correctIds = collect(data_get($question->answer_key_snapshot, 'option_ids', [$question->correct_option_id]))->map(fn($id) => (int) $id);
                        @endphp
                        @foreach($question->option_snapshot as $option)
                            @php
                                $chosen = $selectedIds->contains((int) $option['id']);
                                $correct = $correctIds->contains((int) $option['id']);
                                $classes = $correct ? 'border-success bg-success bg-opacity-10' : ($chosen ? 'border-danger bg-danger bg-opacity-10' : 'border-light');
                            @endphp
                            <div class="d-flex align-items-center gap-2 border rounded-3 p-3 mb-2 {{ $classes }}">
                                <i class="fa-{{ $chosen ? 'solid' : 'regular' }} fa-{{ $question->question_type === 'multiple_choice' ? 'square' : 'circle' }}{{ $chosen ? '-check' : '' }}"></i>
                                <span>{{ $option['text'] }}</span>
                                @if($correct)<span class="badge bg-success ms-auto">Đáp án đúng</span>@elseif($chosen)<span class="badge bg-danger ms-auto">Đã chọn</span>@endif
                            </div>
                        @endforeach
                    @elseif($question->question_type === 'true_false_group')
                        @php
                            $truthAnswers = is_array($answer) ? $answer : [];
                        @endphp
                        @foreach($question->option_snapshot as $index => $statement)
                            @php
                                $expected = (bool) data_get($question->answer_key_snapshot, 'statements.'.$statement['id']);
                                $answered = array_key_exists((string) $statement['id'], $truthAnswers);
                                $given = $answered ? (bool) $truthAnswers[(string) $statement['id']] : null;
                                $statementCorrect = $answered && $given === $expected;
                            @endphp
                            <div class="d-flex align-items-center gap-3 border rounded-3 p-3 mb-2 {{ $statementCorrect ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10' }}">
                                <strong>{{ $index + 1 }}.</strong><span class="flex-grow-1">{{ $statement['text'] }}</span>
                                <span class="badge {{ $answered ? ($statementCorrect ? 'bg-success' : 'bg-danger') : 'bg-secondary' }}">Bạn chọn: {{ $answered ? ($given ? 'Đúng' : 'Sai') : '—' }}</span>
                                <span class="badge bg-light text-success border">Đúng: {{ $expected ? 'Đúng' : 'Sai' }}</span>
                            </div>
                        @endforeach
                    @elseif($question->question_type === 'fill_blank')
                        @php
                            $blankAnswers = is_array($answer) ? $answer : [];
                        @endphp
                        @foreach(data_get($question->answer_key_snapshot, 'blanks', []) as $index => $blank)
                            @php
                                $studentValue = $blankAnswers[$index] ?? '';
                            @endphp
                            <div class="row g-2 align-items-center border rounded-3 p-3 mb-2">
                                <div class="col-md-2 fw-bold text-muted">Ô {{ $index + 1 }}</div>
                                <div class="col-md-5">Bạn trả lời: <strong>{{ $studentValue ?: '—' }}</strong></div>
                                <div class="col-md-5 text-success">Chấp nhận: <strong>{{ collect($blank['accepted'] ?? [])->implode(' / ') }}</strong></div>
                            </div>
                        @endforeach
                    @elseif($question->question_type === 'numeric')
                        <div class="border rounded-3 p-3 {{ $isCorrect ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10' }}">
                            <div>Bạn trả lời: <strong>{{ $answer ?: '—' }} {{ data_get($question->response_schema_snapshot, 'unit') }}</strong></div>
                            <div class="text-success mt-2">Đáp án: <strong>{{ data_get($question->answer_key_snapshot, 'target') }} ± {{ data_get($question->answer_key_snapshot, 'tolerance', 0) }} {{ data_get($question->response_schema_snapshot, 'unit') }}</strong></div>
                        </div>
                    @elseif($question->question_type === 'essay')
                        <div class="border rounded-3 p-3 bg-light" style="white-space:pre-wrap;line-height:1.7">{{ data_get($answer, 'text', '—') }}</div>
                    @elseif($question->question_type === 'code_debug')
                        <pre class="rounded-3 p-3 bg-dark text-light"><code>{{ data_get($answer, 'code', '') }}</code></pre>
                        @if(data_get($question->response_schema_snapshot, 'explanation_mode') !== 'disabled')<div class="border rounded-3 p-3 mt-2"><strong>Giải thích:</strong><div style="white-space:pre-wrap">{{ data_get($answer, 'explanation', '—') }}</div></div>@endif
                    @endif
                    @if($question->attachments->isNotEmpty())<div class="mt-3 d-flex flex-wrap gap-2">@foreach($question->attachments as $file)<a class="btn btn-sm btn-outline-primary" href="{{ route('quiz-attempt-attachments.download', $file) }}"><i class="fa-solid fa-paperclip"></i> {{ $file->original_name }}</a>@endforeach</div>@endif
                    @if($isManual)
                        <div class="alert {{ $question->answer?->grading_status === 'graded' ? 'alert-success' : 'alert-warning' }} mt-3 mb-0">
                            <strong>{{ $question->answer?->grading_status === 'graded' ? 'Điểm: '.number_format((float) $question->answer->score, 2).'/'.number_format((float) $question->max_score, 2) : 'Đang chờ giáo viên chấm' }}</strong>
                            @if($question->answer?->teacher_feedback)<div class="mt-2"><strong>Phản hồi:</strong> {{ $question->answer->teacher_feedback }}</div>@endif
                        </div>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endsection
