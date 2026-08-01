@extends('layouts.app')

@section('title', 'Cập nhật cấu hình: '.$quiz->title)

@section('content')
    <div class="container py-4" style="max-width: 1100px;">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('courses.show', $quiz->course_id) }}">{{ $quiz->course->title }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('quizzes.show', $quiz) }}">{{ $quiz->title }}</a></li>
                <li class="breadcrumb-item active">Cập nhật cấu hình</li>
            </ol>
        </nav>

        <form action="{{ route('quizzes.update', $quiz) }}" method="POST" class="card border-0 shadow-sm">
            @csrf
            @method('PUT')

            <div class="card-header bg-white border-0 px-4 pt-4">
                <h1 class="h4 fw-bold mb-1"><i class="fa-solid fa-sliders me-2 text-primary"></i>Cập nhật cấu hình bài kiểm tra</h1>
                <p class="text-muted mb-0">Bài đang làm giữ nguyên snapshot câu hỏi và thời hạn cũ. Cấu hình mới áp dụng từ lượt bắt đầu tiếp theo.</p>
            </div>

            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="quiz-title">Tên bài kiểm tra</label>
                        <input id="quiz-title" class="form-control" name="title" value="{{ old('title', $quiz->title) }}" maxlength="255" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="quiz-time-limit">Thời gian (phút)</label>
                        <input id="quiz-time-limit" type="number" class="form-control" name="time_limit" value="{{ old('time_limit', $quiz->time_limit) }}" min="1" max="480" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="quiz-max-attempts">Số lượt tối đa</label>
                        <input id="quiz-max-attempts" type="number" class="form-control" name="max_attempts" value="{{ old('max_attempts', $quiz->max_attempts ?? 1) }}" min="1" max="10" required>
                        <div class="form-text">Tính riêng trong từng ca thi.</div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="quiz-status">Trạng thái</label>
                        <select id="quiz-status" class="form-select" name="status" required>
                            <option value="published" @selected(old('status', $quiz->status) === 'published')>Đang mở cho học viên</option>
                            <option value="draft" @selected(old('status', $quiz->status) === 'draft')>Bản nháp</option>
                            <option value="hidden" @selected(old('status', $quiz->status) === 'hidden')>Tạm ẩn</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="quiz-available-from">Mở từ thời điểm</label>
                        <input id="quiz-available-from" type="datetime-local" class="form-control" name="available_from" value="{{ old('available_from', $quiz->available_from?->format('Y-m-d\TH:i')) }}">
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Cơ cấu câu hỏi</h2>
                        <p class="text-muted small mb-0">Không được chọn nhiều hơn số câu đang sẵn sàng trong ngân hàng.</p>
                    </div>
                    <span class="badge text-bg-primary fs-6" data-grand-total>0 câu</span>
                </div>

                <div class="table-responsive border rounded-3">
                    <table class="table align-middle mb-0" data-quiz-distribution>
                        <thead class="table-light">
                            <tr><th>Hình thức</th>@foreach ($difficultyLabels as $label)<th style="width:150px">{{ $label }}</th>@endforeach<th style="width:90px">Tổng</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($typeLabels as $type => $typeLabel)
                                <tr data-distribution-row>
                                    <th>{{ $typeLabel }}</th>
                                    @foreach ($difficultyLabels as $difficulty => $difficultyLabel)
                                        @php
                                            $available = (int) data_get($availability, "{$type}.{$difficulty}", 0);
                                            $selected = (int) old("question_distribution.{$type}.{$difficulty}", data_get($quiz->question_distribution, "{$type}.{$difficulty}", 0));
                                        @endphp
                                        <td>
                                            <input type="number" class="form-control" name="question_distribution[{{ $type }}][{{ $difficulty }}]" value="{{ $selected }}" min="0" max="{{ $available }}" data-dist-input>
                                            <small class="text-muted">Có {{ $available }}</small>
                                        </td>
                                    @endforeach
                                    <td class="fw-bold" data-row-total>0</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white border-0 px-4 pb-4 d-flex gap-2 justify-content-end">
                <a href="{{ route('quizzes.show', $quiz) }}" class="btn btn-light px-4">Hủy</a>
                <button class="btn btn-primary px-4" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Lưu cấu hình</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const table = document.querySelector('[data-quiz-distribution]');
            const grandTotal = document.querySelector('[data-grand-total]');
            if (!table || !grandTotal) return;

            const refresh = () => {
                let grand = 0;
                table.querySelectorAll('[data-distribution-row]').forEach(row => {
                    const total = Array.from(row.querySelectorAll('[data-dist-input]'))
                        .reduce((sum, input) => sum + Math.max(0, Number(input.value) || 0), 0);
                    row.querySelector('[data-row-total]').textContent = total;
                    grand += total;
                });
                grandTotal.textContent = `${grand} câu`;
            };

            table.addEventListener('input', refresh);
            refresh();
        });
    </script>
@endpush
