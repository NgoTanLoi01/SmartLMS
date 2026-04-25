@extends('layouts.app')

@section('title', 'Chi tiết bài làm: ' . $attempt->quiz->title)

@section('content')
    <style>
        /* Giúp nút Radio rõ nét hơn khi bị khóa (disabled) */
        .form-check-input:disabled {
            opacity: 1;
        }

        .radio-correct:checked {
            background-color: #198754;
            border-color: #198754;
        }

        .radio-wrong:checked {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>

    <div class="container py-4" style="max-width: 850px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('courses.show', $attempt->quiz->course_id) }}"
                class="btn btn-white shadow-sm rounded-pill px-4 fw-bold text-primary">
                <i class="fas fa-arrow-left me-2"></i> Trở về khóa học
            </a>
            <h4 class="fw-bold text-dark mb-0">Chi tiết bài làm</h4>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-5 p-4 text-center"
            style="background: linear-gradient(145deg, #f8f9fa 0%, #e9ecef 100%);">
            <h2 class="fw-bold mb-3" style="color: #6f42c1;">{{ $attempt->quiz->title }}</h2>
            <div class="bg-white d-inline-block px-5 py-3 rounded-4 shadow-sm">
                <span
                    class="fs-1 fw-bold {{ $attempt->score >= 5 ? 'text-success' : 'text-danger' }}">{{ $attempt->score }}</span>
                <span class="fs-5 text-muted fw-bold"> / 10 Điểm</span>
            </div>
            <p class="text-muted mt-3 mb-0 small fw-bold"><i class="fas fa-clock me-1"></i> Nộp lúc:
                {{ \Carbon\Carbon::parse($attempt->completed_at)->format('H:i - d/m/Y') }}</p>
        </div>

        @foreach ($attempt->quiz->questions as $index => $question)
            @php
                $selectedOptionId = $studentAnswers[$question->id] ?? null;
                $isCorrectAnswer = false;
                if ($selectedOptionId) {
                    $isCorrectAnswer = $question->options
                        ->where('id', $selectedOptionId)
                        ->where('is_correct', 1)
                        ->isNotEmpty();
                }
            @endphp

            <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden {{ $isCorrectAnswer ? 'border-start border-success' : 'border-start border-danger' }}"
                style="border-left-width: 6px !important;">
                <div class="card-header bg-white py-3 px-4 border-bottom">

                    <h5 class="mb-0 fw-bold lh-base">
                        <span
                            class="{{ $isCorrectAnswer ? 'text-success' : 'text-danger' }} me-1">{{ $index + 1 }}.</span>
                        <span class="text-dark">{{ $question->question_text }}</span>
                    </h5>

                    @if (!$selectedOptionId)
                        <span class="badge bg-secondary mt-2"><i class="fas fa-exclamation-circle me-1"></i> Chưa trả
                            lời</span>
                    @endif

                </div>

                <div class="card-body px-4 pb-4 pt-3">
                    <div class="list-group list-group-flush border-0">
                        @foreach ($question->options as $option)
                            @php
                                $isSelected = $selectedOptionId == $option->id;
                                $isCorrect = $option->is_correct;

                                $bgColor = 'bg-white';
                                $textColor = 'text-secondary';
                                $border = 'border-light';
                                $badge = '';
                                $radioClass = '';

                                if ($isCorrect && $isSelected) {
                                    // Chọn ĐÚNG
                                    $bgColor = 'bg-success bg-opacity-10';
                                    $border = 'border-success';
                                    $textColor = 'text-success fw-bold';
                                    $badge =
                                        '<span class="badge bg-success ms-auto"><i class="fas fa-check me-1"></i> Đã chọn đúng</span>';
                                    $radioClass = 'radio-correct';
                                } elseif ($isCorrect && !$isSelected) {
                                    // Đáp án đúng (nhưng học sinh không chọn)
                                    $bgColor = 'bg-success bg-opacity-10';
                                    $border = 'border-success border-opacity-50 border-dashed';
                                    $textColor = 'text-success fw-bold';
                                    $badge = '<span class="badge bg-success bg-opacity-75 ms-auto">Đáp án đúng</span>';
                                } elseif (!$isCorrect && $isSelected) {
                                    // Chọn SAI
                                    $bgColor = 'bg-danger bg-opacity-10';
                                    $border = 'border-danger';
                                    $textColor = 'text-danger fw-bold';
                                    $badge =
                                        '<span class="badge bg-danger ms-auto"><i class="fas fa-times me-1"></i> Đã chọn (Sai)</span>';
                                    $radioClass = 'radio-wrong';
                                }
                            @endphp

                            <label
                                class="list-group-item d-flex align-items-center p-3 mb-2 rounded-3 border {{ $bgColor }} {{ $border }}">
                                <input type="radio" class="form-check-input mt-0 me-3 {{ $radioClass }}" disabled
                                    {{ $isSelected ? 'checked' : '' }}>
                                <span class="{{ $textColor }} fs-6">{{ $option->option_text }}</span>
                                {!! $badge !!}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
