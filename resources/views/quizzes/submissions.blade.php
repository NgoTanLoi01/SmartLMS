@extends('layouts.app')

@section('title', 'Bảng điểm: ' . $quiz->title)

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="{{ route('courses.show', $quiz->course_id) }}"
                   class="btn btn-outline-primary rounded-pill mb-2 px4 shadow-sm align-items-center back-to-course-btn">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại khóa học
                </a>
                <h3 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-chart-bar text-success me-2"></i>Bảng điểm: {{ $quiz->title }}
                </h3>
            </div>
            <div class="text-end text-muted small">
                Tổng số bài nộp: <strong class="text-dark fs-5">{{ $attempts->count() }}</strong>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="px-4 py-3">STT</th>
                                <th class="px-4 py-3">Học sinh</th>
                                <th class="px-4 py-3 text-center">Điểm số</th>
                                <th class="px-4 py-3">Thời gian nộp</th>
                                <th class="px-4 py-3 text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attempts as $index => $attempt)
                                <tr>
                                    <td class="px-4 fw-bold text-muted">{{ $index + 1 }}</td>
                                    <td class="px-4">
                                        <div class="fw-bold text-dark">{{ $attempt->user->name }}</div>
                                        <div class="small text-muted">{{ $attempt->user->email }}</div>
                                    </td>
                                    <td class="px-4 text-center">
                                        <span
                                            class="badge rounded-pill fs-6 {{ $attempt->score >= 5 ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-danger bg-opacity-10 text-danger border border-danger' }}">
                                            {{ $attempt->score }} / 10
                                        </span>
                                    </td>
                                    <td class="px-4 text-muted small">
                                        {{ \Carbon\Carbon::parse($attempt->completed_at)->format('H:i - d/m/Y') }}
                                    </td>
                                    <td class="px-4 text-end">
                                        <a href="{{ route('quizzes.review', $attempt->id) }}"
                                            class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm">
                                            <i class="fas fa-eye me-1"></i> Xem chi tiết
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
                                        <p class="mb-0">Chưa có học sinh nào nộp bài.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
