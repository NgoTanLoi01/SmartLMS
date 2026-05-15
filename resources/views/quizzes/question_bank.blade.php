@extends('layouts.app')

@section('title', 'Ngân hàng câu hỏi')
<style>
    .modal {
        z-index: 1060 !important;
    }
</style>
@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-0"><i class="fas fa-database text-primary me-2"></i>Ngân hàng câu hỏi</h3>
                <p class="text-muted mb-0 mt-1">Quản lý kho câu hỏi trắc nghiệm dùng để trộn đề thi ngẫu nhiên</p>
            </div>
            <div>
                <button class="btn btn-outline-success fw-bold shadow-sm me-2" data-bs-toggle="modal"
                    data-bs-target="#importQuestionModal">
                    <i class="fas fa-file-excel me-2"></i>Nhập từ Excel/CSV
                </button>
                <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                    <i class="fas fa-plus me-2"></i>Thêm câu hỏi
                </button>
                <a href="{{ route('quizzes.ai_generate') }}" class="btn btn-outline-primary fw-bold shadow-sm">
                    <i class="fas fa-magic me-2"></i> Tạo câu hỏi bằng AI
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body bg-light rounded-4">
                <form action="{{ route('questions.index') }}" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Lọc theo khóa học</label>
                        <select name="course_id" class="form-select border-0 shadow-sm" onchange="this.form.submit()">
                            <option value="">-- Tất cả khóa học --</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}"
                                    {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                    {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Thống kê hiện tại</label>
                        <div class="d-flex gap-3">
                            <span class="badge bg-success bg-opacity-10 text-success border border-success p-2 px-3">
                                Dễ: {{ $questions->where('difficulty', 'easy')->count() }}
                            </span>
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning p-2 px-3">
                                Trung bình: {{ $questions->where('difficulty', 'medium')->count() }}
                            </span>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger p-2 px-3">
                                Khó: {{ $questions->where('difficulty', 'hard')->count() }}
                            </span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small text-muted">
                            <tr>
                                <th class="px-4 py-3" width="4%">ID</th>
                                <th class="px-4 py-3" width="35%">Nội dung câu hỏi</th>
                                <th class="px-4 py-3" width="21%">Khóa học</th>
                                <th class="px-4 py-3" width="15%">Giáo viên</th>
                                <th class="px-4 py-3" width="10%">Độ khó</th>
                                <th class="px-4 py-3 text-end" width="15%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($questions as $question)
                                <tr>
                                    <td class="px-4 fw-bold text-muted">#{{ $question->id }}</td>
                                    <td class="px-4">
                                        <div class="fw-bold text-dark mb-1">{{ Str::limit($question->question_text, 80) }}
                                        </div>
                                        <div class="small text-muted">
                                            <span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>
                                                @php $correctOpt = $question->options->where('is_correct', true)->first(); @endphp
                                                {{ $correctOpt ? Str::limit($correctOpt->option_text, 40) : 'Chưa có đáp án đúng' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 text-muted small fw-bold">{{ $question->course->title ?? 'N/A' }}</td>

                                    {{-- Cột Giáo viên mới thêm --}}
                                    <td class="px-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2"
                                                style="width: 28px; height: 28px;">
                                                <i class="fas fa-user-tie small"></i>
                                            </div>
                                            <span
                                                class="small fw-bold text-dark">{{ $question->course->teacher->name ?? 'N/A' }}</span>
                                        </div>
                                    </td>

                                    <td class="px-4">
                                        @if ($question->difficulty == 'easy')
                                            <span class="badge bg-success">Dễ</span>
                                        @elseif($question->difficulty == 'medium')
                                            <span class="badge bg-warning text-dark">Trung bình</span>
                                        @else
                                            <span class="badge bg-danger">Khó</span>
                                        @endif
                                    </td>
                                    </td>
                                    <td class="px-4 text-end">
                                        <button type="button" class="btn btn-sm btn-light text-primary me-1"
                                            data-bs-toggle="modal" data-bs-target="#editQuestionModal"
                                            data-id="{{ $question->id }}" data-course="{{ $question->course_id }}"
                                            data-difficulty="{{ $question->difficulty }}"
                                            data-text="{{ htmlspecialchars($question->question_text) }}"
                                            data-options="{{ $question->options->sortBy('id')->values()->toJson() }}"
                                            title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form action="{{ route('questions.destroyBank', $question->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger"
                                                onclick="return confirm('Xóa câu hỏi này ra khỏi ngân hàng?')"
                                                title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i>
                                        <h5>Kho câu hỏi trống</h5>
                                        <p class="mb-0">Hãy chọn khóa học và thêm câu hỏi mới nhé!</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($questions->hasPages())
                    <div class="card-footer bg-white py-3 border-top">
                        {{ $questions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="addQuestionModal" tabindex="-1">
        <div class="modal-dialog modal-lg pad-top">
            <div class="modal-content border-0 shadow rounded-4">
                <form action="{{ route('questions.storeBank') }}" method="POST" id="questionForm">
                    @csrf
                    <div class="modal-header bg-light border-0 pb-3">
                        <h5 class="modal-title fw-bold text-primary"><i class="fas fa-pen-square me-2"></i>Chi tiết câu
                            hỏi
                            mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4 pt-3">
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-muted text-uppercase">Khóa học</label>
                                <select name="course_id" class="form-select" required>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}"
                                            {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                            {{ $course->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Độ khó</label>
                                <select name="difficulty" class="form-select" required>
                                    <option value="easy">Dễ</option>
                                    <option value="medium" selected>Trung bình</option>
                                    <option value="hard">Khó</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small text-muted text-uppercase">Nội dung câu hỏi</label>
                                <textarea name="question_text" class="form-control" rows="3" placeholder="Nhập câu hỏi tại đây..." required></textarea>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Các đáp án (Tích chọn đáp án đúng)</h6>

                        @for ($i = 1; $i <= 4; $i++)
                            <div class="input-group mb-3 shadow-sm rounded-3">
                                <div class="input-group-text bg-white border-end-0">
                                    <input class="form-check-input mt-0" type="radio" name="correct_option"
                                        value="{{ $i }}" {{ $i == 1 ? 'checked' : '' }} required
                                        title="Đánh dấu là đáp án đúng">
                                </div>
                                <span class="input-group-text bg-light fw-bold">Đáp án {{ chr(64 + $i) }}</span>
                                <input type="text" name="options[{{ $i }}]"
                                    class="form-control border-start-0" placeholder="Nhập nội dung đáp án..." required>
                            </div>
                        @endfor

                        <div class="alert alert-info small border-0 py-2 mt-3 mb-0">
                            <i class="fas fa-info-circle me-1"></i> Khi học sinh làm bài, thứ tự 4 đáp án này sẽ được xáo
                            trộn ngẫu nhiên.
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Lưu vào kho</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editQuestionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow rounded-4">
                <form action="" method="POST" id="editQuestionForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-light border-0 pb-3">
                        <h5 class="modal-title fw-bold text-primary"><i class="fas fa-edit me-2"></i>Chỉnh sửa câu hỏi
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4 pt-3">
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-muted text-uppercase">Khóa học</label>
                                <select name="course_id" id="edit_course_id" class="form-select" required>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Độ khó</label>
                                <select name="difficulty" id="edit_difficulty" class="form-select" required>
                                    <option value="easy">Dễ</option>
                                    <option value="medium">Trung bình</option>
                                    <option value="hard">Khó</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small text-muted text-uppercase">Nội dung câu hỏi</label>
                                <textarea name="question_text" id="edit_question_text" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Các đáp án (Tích chọn đáp án đúng)</h6>

                        @for ($i = 1; $i <= 4; $i++)
                            <div class="input-group mb-3 shadow-sm rounded-3">
                                <div class="input-group-text bg-white border-end-0">
                                    <input class="form-check-input mt-0" type="radio" name="correct_option"
                                        id="edit_correct_{{ $i }}" value="{{ $i }}" required>
                                </div>
                                <span class="input-group-text bg-light fw-bold">Đáp án {{ chr(64 + $i) }}</span>
                                <input type="text" name="options[{{ $i }}]"
                                    id="edit_option_{{ $i }}" class="form-control border-start-0" required>
                            </div>
                        @endfor
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="importQuestionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow rounded-4">
                <form action="{{ route('questions.importBank') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header bg-success text-white border-0 pb-3">
                        <h5 class="modal-title fw-bold"><i class="fas fa-file-upload me-2"></i>Nhập câu hỏi từ file</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4 pt-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">1. Chọn Khóa học</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">-- Vui lòng chọn khóa học --</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">2. Tải lên file xlsx</label>
                            <input type="file" name="file" class="form-control" accept=".xlsx" required>
                        </div>

                        <div class="alert alert-warning small border-0">
                            <strong>Hướng dẫn định dạng file Excel (Lưu dưới dạng .xlsx):</strong><br>
                            Tạo bảng gồm 7 cột theo đúng thứ tự sau (Cột A đến G):
                            <ol class="mb-0 mt-1">
                                <li>Nội dung câu hỏi</li>
                                <li>Độ khó (Gõ: <i>easy, medium, hard</i>)</li>
                                <li>Đáp án A</li>
                                <li>Đáp án B</li>
                                <li>Đáp án C</li>
                                <li>Đáp án D</li>
                                <li>Đáp án đúng (Gõ chữ: <i>A, B, C hoặc D</i>)</li>
                            </ol>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">Bắt đầu nhập</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var editModal = document.getElementById('editQuestionModal');

            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    // Lấy button đã kích hoạt Modal
                    var button = event.relatedTarget;

                    // Trích xuất thông tin từ các data-* attributes
                    var id = button.getAttribute('data-id');
                    var courseId = button.getAttribute('data-course');
                    var difficulty = button.getAttribute('data-difficulty');
                    var questionText = button.getAttribute('data-text');

                    // Phân tích chuỗi JSON của các đáp án
                    var optionsRaw = button.getAttribute('data-options');
                    var options = optionsRaw ? JSON.parse(optionsRaw) : [];

                    // Cập nhật URL Submit cho Form
                    var form = document.getElementById('editQuestionForm');
                    form.action = '/question-bank/' + id;

                    // Đổ dữ liệu vào các thẻ input
                    document.getElementById('edit_course_id').value = courseId;
                    document.getElementById('edit_difficulty').value = difficulty;
                    document.getElementById('edit_question_text').value = questionText;

                    // Xóa chọn radio trước (để reset form)
                    for (var j = 1; j <= 4; j++) {
                        document.getElementById('edit_correct_' + j).checked = false;
                    }

                    // Vòng lặp đổ dữ liệu 4 đáp án (dựa trên mảng JSON parse được)
                    for (var i = 0; i < 4; i++) {
                        var optNum = i + 1;
                        if (options[i]) {
                            document.getElementById('edit_option_' + optNum).value = options[i].option_text;
                            if (options[i].is_correct == 1 || options[i].is_correct === true) {
                                document.getElementById('edit_correct_' + optNum).checked = true;
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
