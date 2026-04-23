@extends('layouts.app')

@section('title', 'Làm bài: ' . $quiz->title)

@section('content')
    <style>
        /* Biến vùng làm bài thành Toàn màn hình, đè lên mọi Layout cũ (Sidebar, Navbar) */
        .quiz-fullscreen-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #f8f9fa;
            z-index: 99999;
            overflow-y: auto;
            padding: 40px 15px;
        }

        .timer-fixed {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100000;
            background: white;
            border: 2px solid #6f42c1;
        }

        .option-wrapper {
            transition: all 0.2s;
            cursor: pointer;
        }

        .option-wrapper:hover {
            background-color: #f1f3f5;
        }

        input[type="radio"] {
            transform: scale(1.3);
            accent-color: #6f42c1;
            cursor: pointer;
        }

        /* Khi hết giờ, làm mờ form và chặn click nhưng vẫn cho phép submit dữ liệu */
        .form-locked {
            pointer-events: none;
            opacity: 0.6;
        }
    </style>

    <div class="quiz-fullscreen-wrapper">
        <div class="container" style="max-width: 800px;">
            <div class="text-center mb-5">
                <h2 class="fw-bold" style="color: #6f42c1;">{{ $quiz->title }}</h2>
                <p class="text-muted mb-0">Tổng số câu: <strong>{{ $quiz->questions->count() }} câu</strong> | Thời gian:
                    <strong>{{ $quiz->time_limit }} phút</strong>
                </p>
                <div class="mt-2 text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>Không tải lại trang (F5)
                    trong quá trình làm bài.</div>
            </div>

            <div class="timer-fixed p-3 rounded-4 shadow-lg d-flex align-items-center">
                <i class="fas fa-clock fa-2x me-2 text-danger" id="clock-icon"></i>
                <div class="text-center px-2">
                    <span class="d-block small text-muted fw-bold lh-1 mb-1">CÒN LẠI</span>
                    <span id="countdown-timer" class="fs-4 fw-bold text-dark lh-1">--:--</span>
                </div>
            </div>

            <form id="quiz-form" action="{{ route('quizzes.submit', $quiz->id) }}" method="POST">
                @csrf

                @forelse ($quiz->questions as $index => $question)
                    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="mb-0 fw-bold lh-base" style="color: #2c3e50;">
                                <span style="color: #6f42c1;">Câu {{ $index + 1 }}:</span> {{ $question->question_text }}
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @foreach ($question->options as $option)
                                    <label
                                        class="list-group-item border-0 border-bottom p-3 option-wrapper d-flex align-items-center">
                                        <input type="radio" name="answers[{{ $question->id }}]"
                                            value="{{ $option->id }}" class="me-3">
                                        <span class="fs-6">{{ $option->option_text }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info text-center shadow-sm border-0">Đề thi này chưa có câu hỏi nào.</div>
                @endforelse

                @if ($quiz->questions->count() > 0)
                    <div class="text-center mt-5 mb-5 pb-5">
                        <button type="button" id="btn-submit-quiz"
                            class="btn btn-lg text-white rounded-pill px-5 shadow-lg fw-bold transition-hover"
                            style="background-color: #6f42c1; border: 3px solid white;">
                            <i class="fas fa-paper-plane me-2"></i> NỘP BÀI NGAY
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            let timeLimitMinutes = {{ $quiz->time_limit }};
            let timeRemainingSeconds = timeLimitMinutes * 60;

            const timerDisplay = document.getElementById('countdown-timer');
            const clockIcon = document.getElementById('clock-icon');
            const quizForm = document.getElementById('quiz-form');
            const btnSubmit = document.getElementById('btn-submit-quiz');

            const countdownInterval = setInterval(function() {
                timeRemainingSeconds--;

                let minutes = Math.floor(timeRemainingSeconds / 60);
                let seconds = timeRemainingSeconds % 60;

                if (minutes < 10) minutes = "0" + minutes;
                if (seconds < 10) seconds = "0" + seconds;

                timerDisplay.innerText = minutes + ":" + seconds;

                if (timeRemainingSeconds < 60) {
                    timerDisplay.classList.replace('text-dark', 'text-danger');
                    clockIcon.classList.add('fa-beat-fade');
                }

                if (timeRemainingSeconds <= 0) {
                    clearInterval(countdownInterval);
                    timerDisplay.innerText = "00:00";

                    // Chặn người dùng click thêm nhưng KHÔNG dùng thuộc tính disabled để form vẫn gửi được dữ liệu
                    quizForm.classList.add('form-locked');
                    if (btnSubmit) btnSubmit.disabled = true;

                    // Sử dụng alert mặc định của trình duyệt để đảm bảo luôn chạy được
                    alert('⏳ Đã hết thời gian làm bài! Hệ thống đang tự động thu bài của bạn...');

                    // Tự động submit
                    window.removeEventListener('beforeunload', preventReload);
                    quizForm.submit();
                }
            }, 1000);

            if (btnSubmit) {
                btnSubmit.addEventListener('click', function() {
                    let totalQuestions = {{ $quiz->questions->count() }};
                    let answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;

                    let message = (answeredQuestions < totalQuestions) ?
                        `Cảnh báo: Bạn mới làm được ${answeredQuestions}/${totalQuestions} câu.\nBạn có chắc chắn muốn nộp bài sớm không?` :
                        'Bạn đã hoàn thành tất cả câu hỏi.\nXác nhận nộp bài?';

                    if (confirm(message)) {
                        clearInterval(countdownInterval);
                        btnSubmit.disabled = true;
                        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Đang nộp bài...';
                        quizForm.classList.add('form-locked');
                        window.removeEventListener('beforeunload', preventReload);
                        quizForm.submit();
                    }
                });
            }

            function preventReload(e) {
                if (timeRemainingSeconds > 0 && (!btnSubmit || !btnSubmit.disabled)) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            }
            window.addEventListener('beforeunload', preventReload);
        </script>
    @endpush
@endsection
