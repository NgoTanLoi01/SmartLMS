@extends('layouts.app')

@section('title', 'Chấm bài: '.$quiz->title)

@push('styles')
<style>
    .grading-page { max-width: 1320px; }
    .grading-hero { background: linear-gradient(135deg, #0f3c91 0%, #1769e0 100%); color: #fff; border-radius: 24px; padding: 28px 30px; box-shadow: 0 18px 45px rgba(15, 60, 145, .18); }
    .grading-hero__eyebrow { font-size: .76rem; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; opacity: .78; }
    .grading-hero__title { font-size: clamp(1.35rem, 2.5vw, 2rem); font-weight: 800; margin: 7px 0 5px; }
    .grading-hero__meta { color: rgba(255, 255, 255, .78); }
    .grading-stat { display: block; height: 100%; padding: 18px 20px; color: inherit; text-decoration: none; background: #fff; border: 1px solid #e5eaf3; border-radius: 18px; transition: .18s ease; }
    .grading-stat:hover { color: inherit; border-color: #9bbcf8; transform: translateY(-2px); box-shadow: 0 12px 28px rgba(31, 57, 101, .09); }
    .grading-stat.active { border-color: #2165e8; box-shadow: 0 0 0 3px rgba(33, 101, 232, .1); }
    .grading-stat__label { color: #71809a; font-size: .78rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .grading-stat__value { color: #17243d; font-size: 1.75rem; font-weight: 800; line-height: 1.1; margin-top: 8px; }
    .grading-filter, .grading-table-card { background: #fff; border: 1px solid #e6ebf3; border-radius: 20px; box-shadow: 0 10px 30px rgba(28, 51, 84, .06); }
    .grading-filter { padding: 18px; }
    .grading-filter .form-control, .grading-filter .form-select { min-height: 44px; border-color: #dce3ee; border-radius: 12px; }
    .grading-table-card { overflow: hidden; }
    .grading-table-card .table > :not(caption) > * > * { padding: 17px 18px; border-color: #edf0f5; }
    .grading-table-card thead th { color: #738198; background: #f8fafd; font-size: .73rem; letter-spacing: .06em; text-transform: uppercase; white-space: nowrap; }
    .student-avatar { display: grid; place-items: center; width: 42px; height: 42px; flex: 0 0 42px; color: #174ea6; background: #eaf2ff; border-radius: 13px; font-weight: 800; }
    .grading-status { display: inline-flex; align-items: center; gap: 7px; padding: 7px 10px; border-radius: 999px; font-size: .76rem; font-weight: 800; white-space: nowrap; }
    .grading-status--pending { color: #9a5a00; background: #fff3d6; }
    .grading-status--graded { color: #095b3b; background: #ddf8eb; }
    .grading-status--released { color: #0c4bb3; background: #e5efff; }
    .grading-status--submitted { color: #5f6673; background: #edf0f5; }
    .rubric-progress { min-width: 128px; }
    .rubric-progress .progress { height: 6px; background: #e9edf4; }
    .rubric-progress .progress-bar { background: #2165e8; }
    .score-pill { display: inline-flex; align-items: baseline; gap: 3px; padding: 7px 11px; border-radius: 10px; background: #eef4ff; color: #114eae; font-weight: 800; }
    .empty-grading { padding: 68px 24px; text-align: center; color: #7a879b; }
    @media (max-width: 767.98px) {
        .grading-hero { padding: 22px; border-radius: 18px; }
        .grading-table-card { border-radius: 16px; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid grading-page py-4">
    <section class="grading-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <a href="{{ route('courses.show', $quiz->course_id) }}" class="btn btn-sm btn-light bg-white bg-opacity-10 border border-white border-opacity-25 text-white rounded-pill px-3 mb-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Quay lại khóa học
                </a>
                <div class="grading-hero__eyebrow">Hàng đợi chấm bài</div>
                <h1 class="grading-hero__title">{{ $quiz->title }}</h1>
                <div class="grading-hero__meta">Theo dõi tiến độ chấm, phản hồi và công bố điểm cho học sinh.</div>
            </div>
            <a href="{{ route('quizzes.sessions.index', $quiz) }}" class="btn btn-light rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-calendar-days me-2 text-primary"></i>Quản lý ca thi
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-4 shadow-sm"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-4 shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-4">
        @php
            $statCards = [
                ['key' => 'all', 'label' => 'Tổng bài đã nộp', 'value' => $stats['total'], 'icon' => 'fa-layer-group'],
                ['key' => 'pending_grading', 'label' => 'Đang chờ chấm', 'value' => $stats['pending'], 'icon' => 'fa-hourglass-half'],
                ['key' => 'graded', 'label' => 'Đã chấm xong', 'value' => $stats['graded'], 'icon' => 'fa-circle-check'],
                ['key' => 'released', 'label' => 'Đã công bố', 'value' => $stats['released'], 'icon' => 'fa-paper-plane'],
            ];
        @endphp
        @foreach($statCards as $card)
            <div class="col-6 col-xl-3">
                <a class="grading-stat {{ $status === $card['key'] ? 'active' : '' }}" href="{{ route('quizzes.submissions', array_filter([$quiz->id, 'status' => $card['key'], 'session_id' => $sessionId, 'search' => $search])) }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div><div class="grading-stat__label">{{ $card['label'] }}</div><div class="grading-stat__value">{{ $card['value'] }}</div></div>
                        <i class="fa-solid {{ $card['icon'] }} text-primary opacity-75"></i>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('quizzes.submissions', $quiz) }}" class="grading-filter mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-5">
                <label for="grading-search" class="form-label small fw-bold text-muted">Tìm học sinh</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 rounded-start-3"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input id="grading-search" class="form-control border-start-0" name="search" value="{{ $search }}" placeholder="Tên, email hoặc mã học sinh">
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <label for="grading-status" class="form-label small fw-bold text-muted">Trạng thái</label>
                <select id="grading-status" class="form-select" name="status">
                    <option value="all" @selected($status === 'all')>Tất cả trạng thái</option>
                    <option value="pending_grading" @selected($status === 'pending_grading')>Đang chờ chấm</option>
                    <option value="graded" @selected($status === 'graded')>Đã chấm xong</option>
                    <option value="released" @selected($status === 'released')>Đã công bố</option>
                    <option value="submitted" @selected($status === 'submitted')>Đã nộp</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label for="grading-session" class="form-label small fw-bold text-muted">Ca thi</label>
                <select id="grading-session" class="form-select" name="session_id">
                    <option value="">Tất cả ca thi</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" @selected((int) $sessionId === (int) $session->id)>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1 rounded-3 fw-bold"><i class="fa-solid fa-filter me-1"></i> Lọc</button>
                <a class="btn btn-outline-secondary rounded-3" href="{{ route('quizzes.submissions', $quiz) }}" title="Xóa bộ lọc"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </div>
    </form>

    <section class="grading-table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Học sinh</th>
                        <th>Ca thi</th>
                        <th>Trạng thái</th>
                        <th>Tiến độ rubric</th>
                        <th>Điểm</th>
                        <th>Thời gian nộp</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attempts as $attempt)
                        @php
                            $manualTotal = (int) $attempt->manual_questions_count;
                            $manualGraded = (int) $attempt->graded_manual_questions_count;
                            $manualPercent = $manualTotal > 0 ? (int) round(($manualGraded / $manualTotal) * 100) : 100;
                            $statusMeta = match($attempt->status) {
                                'pending_grading' => ['pending', 'fa-hourglass-half', 'Chờ chấm'],
                                'graded' => ['graded', 'fa-circle-check', 'Đã chấm'],
                                'released' => ['released', 'fa-paper-plane', 'Đã công bố'],
                                default => ['submitted', 'fa-inbox', 'Đã nộp'],
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="student-avatar">{{ mb_strtoupper(mb_substr($attempt->user->name, 0, 1)) }}</div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $attempt->user->name }}</div>
                                        <div class="small text-muted">{{ $attempt->user->student_code ?: $attempt->user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="small fw-semibold">{{ $attempt->session?->name ?? 'Không theo ca' }}</span></td>
                            <td><span class="grading-status grading-status--{{ $statusMeta[0] }}"><i class="fa-solid {{ $statusMeta[1] }}"></i>{{ $statusMeta[2] }}</span></td>
                            <td>
                                @if($manualTotal > 0)
                                    <div class="rubric-progress">
                                        <div class="d-flex justify-content-between small mb-1"><span>{{ $manualGraded }}/{{ $manualTotal }} câu</span><strong>{{ $manualPercent }}%</strong></div>
                                        <div class="progress" role="progressbar" aria-label="Tiến độ chấm" aria-valuenow="{{ $manualPercent }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:{{ $manualPercent }}%"></div></div>
                                    </div>
                                @else
                                    <span class="small text-muted">Chấm tự động</span>
                                @endif
                            </td>
                            <td>@if($attempt->score !== null)<span class="score-pill">{{ number_format((float) $attempt->score, 2) }}<small>/10</small></span>@else<span class="text-muted">—</span>@endif</td>
                            <td><div class="small fw-semibold">{{ $attempt->completed_at?->format('H:i') }}</div><div class="small text-muted">{{ $attempt->completed_at?->format('d/m/Y') }}</div></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('quiz-attempts.grade', $attempt) }}" class="btn btn-sm {{ $attempt->status === 'pending_grading' ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill px-3 fw-bold">
                                        <i class="fa-solid {{ $attempt->status === 'pending_grading' ? 'fa-pen-to-square' : 'fa-eye' }} me-1"></i>{{ $attempt->status === 'pending_grading' ? 'Chấm bài' : 'Xem bài' }}
                                    </a>
                                    @if($attempt->status === 'graded')
                                        <form method="POST" action="{{ route('quiz-attempts.release', $attempt) }}" onsubmit="return confirm('Công bố điểm và phản hồi cho học sinh này?')">
                                            @csrf
                                            <button class="btn btn-sm btn-success rounded-pill px-3 fw-bold" title="Công bố điểm"><i class="fa-solid fa-paper-plane"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-grading"><i class="fa-regular fa-folder-open fa-3x mb-3 opacity-50"></i><h5 class="fw-bold text-dark">Không tìm thấy bài làm</h5><p class="mb-0">Thử thay đổi trạng thái, ca thi hoặc từ khóa tìm kiếm.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($attempts->hasPages())
            <div class="px-4 py-3 border-top">{{ $attempts->links() }}</div>
        @endif
    </section>
</div>
@endsection
