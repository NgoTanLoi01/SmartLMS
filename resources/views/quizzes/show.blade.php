@extends('layouts.app')

@section('title', 'Soạn câu hỏi: ' . $quiz->title)

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="{{ route('courses.show', $quiz->course_id) }}"
                    class="text-decoration-none text-muted small mb-2 d-inline-block">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại khóa học
                </a>
                <h3 class="fw-bold mb-0" style="color: #6f42c1;">
                    <i class="fas fa-stopwatch me-2"></i>{{ $quiz->title }}
                </h3>
                <p class="text-muted mb-0 mt-1">
                    Thời gian làm bài: <strong>{{ $quiz->time_limit }} phút</strong> |
                    Tổng số câu: <strong>{{ $quiz->questions->count() }} câu</strong>
                </p>
            </div>
        </div>

        <div class="row g-4">
            {{-- FORM NHẬP LIỆU (THÊM/SỬA) --}}
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold" style="color: #6f42c1;" id="form-title">
                            <i class="fas fa-plus-circle me-2"></i>Thêm câu hỏi mới
                        </h5>
                        <button type="button" id="btn-reset-form"
                            class="btn btn-sm btn-outline-secondary rounded-pill d-none" onclick="resetQuestionForm()">Hủy
                            sửa</button>
                    </div>
                    <div class="card-body bg-light">
                        <form action="{{ route('questions.store', $quiz->id) }}" method="POST" id="question-form">
                            @csrf
                            <div id="method-field"></div> {{-- Nơi chứa @method('PUT') khi sửa --}}

                            <div class="mb-3">
                                <label class="fw-bold small text-dark mb-2">Nội dung câu hỏi <span
                                        class="text-danger">*</span></label>
                                <textarea name="question_text" id="input_question_text" class="form-control border-0 shadow-sm" rows="3"
                                    placeholder="Nhập câu hỏi..." required></textarea>
                            </div>

                            <label class="fw-bold small text-dark mb-2">Nhập 4 đáp án và chọn đáp án đúng <span
                                    class="text-danger">*</span></label>

                            @php $labels = ['A', 'B', 'C', 'D']; @endphp
                            @foreach ($labels as $label)
                                <div class="input-group mb-2 shadow-sm">
                                    <div class="input-group-text bg-white border-0">
                                        <input class="form-check-input mt-0" type="radio" name="correct_option"
                                            value="{{ $label }}" id="radio_{{ $label }}" required
                                            title="Chọn làm đáp án đúng">
                                    </div>
                                    <span class="input-group-text bg-white border-0 fw-bold">{{ $label }}</span>
                                    <input type="text" name="option_{{ strtolower($label) }}"
                                        id="input_option_{{ $label }}" class="form-control border-0"
                                        placeholder="Nội dung đáp án {{ $label }}..." required>
                                </div>
                            @endforeach

                            <button type="submit" id="btn-submit-form"
                                class="btn text-white w-100 rounded-pill shadow-sm fw-bold py-2 mt-3"
                                style="background-color: #6f42c1;">
                                <i class="fas fa-save me-1"></i> Lưu câu hỏi
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- DANH SÁCH CÂU HỎI --}}
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-list-ol me-2 text-muted"></i>Danh sách câu hỏi
                            ({{ $quiz->questions->count() }})
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @forelse ($quiz->questions as $index => $question)
                            <div class="border rounded p-3 mb-3 bg-white shadow-sm position-relative">
                                <div class="position-absolute top-0 end-0 m-2 d-flex gap-2">
                                    {{-- Nút Sửa --}}
                                    <button type="button" class="btn btn-sm btn-outline-warning border-0"
                                        onclick="editQuestion('{{ $question->id }}', '{{ addslashes($question->question_text) }}', [
                                            @foreach ($question->options as $opt) { text: '{{ addslashes($opt->option_text) }}', correct: {{ $opt->is_correct ? 'true' : 'false' }} }, @endforeach
                                        ])">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    {{-- Nút Xóa --}}
                                    <form action="{{ route('questions.destroy', $question->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"
                                            onclick="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này không?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                <h6 class="fw-bold mb-3 pe-5 text-dark lh-base">
                                    <span style="color: #6f42c1;">Câu {{ $index + 1 }}:</span>
                                    {{ $question->question_text }}
                                </h6>

                                <div class="row g-2">
                                    @foreach ($question->options as $key => $option)
                                        <div class="col-md-6">
                                            <div
                                                class="p-2 border rounded small d-flex align-items-center {{ $option->is_correct ? 'bg-success bg-opacity-10 border-success text-success fw-bold' : 'bg-light text-muted' }}">
                                                <span
                                                    class="me-2 {{ $option->is_correct ? 'text-success' : 'text-secondary fw-bold' }}">{{ $labels[$key] ?? '' }}.</span>
                                                <span class="flex-grow-1">{{ $option->option_text }}</span>
                                                @if ($option->is_correct)
                                                    <i class="fas fa-check-circle ms-2"></i>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-50"></i>
                                <h6 class="text-muted">Chưa có câu hỏi nào.</h6>
                                <p class="small text-muted mb-0">Hãy sử dụng form bên trái để thêm câu hỏi.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function editQuestion(id, text, options) {
                const labels = ['A', 'B', 'C', 'D'];

                // 1. Cập nhật giao diện Form sang chế độ Sửa
                document.getElementById('form-title').innerHTML = '<i class="fas fa-edit me-2"></i>Đang sửa câu hỏi';
                document.getElementById('question-form').action = `/questions/${id}`;
                document.getElementById('method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';
                document.getElementById('btn-submit-form').innerHTML = '<i class="fas fa-check me-1"></i> Cập nhật câu hỏi';
                document.getElementById('btn-submit-form').style.backgroundColor = '#ffc107'; // Đổi sang màu vàng cảnh báo
                document.getElementById('btn-submit-form').style.color = '#000';
                document.getElementById('btn-reset-form').classList.remove('d-none');

                // 2. Đổ dữ liệu vào các ô input
                document.getElementById('input_question_text').value = text;
                options.forEach((opt, index) => {
                    let label = labels[index];
                    document.getElementById(`input_option_${label}`).value = opt.text;
                    if (opt.correct) {
                        document.getElementById(`radio_${label}`).checked = true;
                    }
                });

                // 3. Cuộn mượt mà lên đầu form
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            function resetQuestionForm() {
                document.getElementById('form-title').innerHTML = '<i class="fas fa-plus-circle me-2"></i>Thêm câu hỏi mới';
                document.getElementById('question-form').action = "{{ route('questions.store', $quiz->id) }}";
                document.getElementById('method-field').innerHTML = '';
                document.getElementById('btn-submit-form').innerHTML = '<i class="fas fa-save me-1"></i> Lưu câu hỏi';
                document.getElementById('btn-submit-form').style.backgroundColor = '#6f42c1';
                document.getElementById('btn-submit-form').style.color = '#fff';
                document.getElementById('btn-reset-form').classList.add('d-none');
                document.getElementById('question-form').reset();
            }
        </script>
    @endpush
@endsection
