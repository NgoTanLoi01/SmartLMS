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
                $selected = $question->answer?->selected_option_id;
                $isCorrect = $selected !== null && (int) $selected === (int) $question->correct_option_id;
            @endphp
            <article class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
                @if($question->passage_content)
                    <div class="p-4 bg-light border-bottom">
                        <div class="fw-bold text-primary mb-2"><i class="fa-solid fa-file-lines"></i> {{ $question->passage_title }}</div>
                        <div style="white-space:pre-wrap;line-height:1.65">{{ $question->passage_content }}</div>
                    </div>
                @endif
                <div class="card-header bg-white p-4 border-bottom">
                    <div class="fw-bold"><span class="{{ $isCorrect ? 'text-success' : 'text-danger' }}">Câu {{ $question->position }}.</span> {{ $question->question_text }}</div>
                    @if(!$selected)<span class="badge bg-secondary mt-2">Chưa trả lời</span>@endif
                </div>
                <div class="card-body p-4">
                    @foreach($question->option_snapshot as $option)
                        @php
                            $chosen = (int) $selected === (int) $option['id'];
                            $correct = (int) $question->correct_option_id === (int) $option['id'];
                            $classes = $correct ? 'border-success bg-success bg-opacity-10' : ($chosen ? 'border-danger bg-danger bg-opacity-10' : 'border-light');
                        @endphp
                        <div class="d-flex align-items-center gap-2 border rounded-3 p-3 mb-2 {{ $classes }}">
                            <i class="fa-{{ $chosen ? 'solid' : 'regular' }} fa-circle{{ $chosen ? '-check' : '' }}"></i>
                            <span>{{ $option['text'] }}</span>
                            @if($correct)<span class="badge bg-success ms-auto">Đáp án đúng</span>@elseif($chosen)<span class="badge bg-danger ms-auto">Đã chọn</span>@endif
                        </div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
@endsection
