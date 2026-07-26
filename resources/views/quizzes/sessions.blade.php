@extends('layouts.app')

@section('title', 'Ca thi: ' . $quiz->title)

@push('styles')
    @vite('resources/css/pages/quiz-sessions.css')
@endpush

@section('content')
    @php
        $allAttempts = $quiz->sessions->flatMap->attempts;
        $totalCandidates = $quiz->sessions->sum(fn($item) => $item->candidates->count());
        $totalInProgress = $allAttempts->where('status', 'in_progress')->count();
        $totalSubmitted = $allAttempts->where('status', 'submitted')->count();
        $liveSessions = $quiz->sessions->filter(fn($item) => $item->isOpen())->count();
    @endphp

    <div class="exam-admin-page">
        <nav class="exam-breadcrumb" aria-label="Điều hướng">
            <a href="{{ route('courses.show', $quiz->course_id) }}">{{ $quiz->course->title ?? 'Khóa học' }}</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>{{ $quiz->title }}</span>
            <i class="fa-solid fa-chevron-right"></i>
            <strong>Ca thi</strong>
        </nav>

        <header class="exam-hero">
            <div class="exam-hero-content">
                <div class="exam-eyebrow">Trung tâm điều hành kỳ thi</div>
                <h1>Quản lý ca thi</h1>
                <div class="exam-hero-meta">
                    <span><i class="fa-solid fa-file-circle-question"></i>{{ $quiz->title }}</span>
                    <span><i class="fa-regular fa-clock"></i>{{ $quiz->time_limit }} phút</span>
                    <span><i class="fa-solid fa-layer-group"></i>{{ $quiz->easy_count + $quiz->medium_count + $quiz->hard_count }} câu</span>
                </div>
            </div>
            <div class="exam-hero-actions">
                <a class="exam-action exam-action-glass" href="{{ route('courses.show', $quiz->course_id) }}">
                    <i class="fa-solid fa-arrow-left"></i> Khóa học
                </a>
                <button class="exam-action exam-action-primary" type="button" data-bs-toggle="modal"
                    data-bs-target="#createSessionModal" @disabled($candidatePool->isEmpty())>
                    <i class="fa-solid fa-calendar-plus"></i> Tạo ca thi
                </button>
            </div>
        </header>

        @if($candidatePool->isEmpty())
            <div class="exam-alert" role="alert">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><strong>Chưa thể tạo ca thi.</strong> Khóa học chưa có học viên trong các lớp được liên kết. Hãy thêm lớp và học viên trước.</div>
            </div>
        @endif

        <section class="exam-overview" aria-label="Tổng quan kỳ thi">
            <article class="exam-metric">
                <div class="exam-metric-icon"><i class="fa-solid fa-calendar-days"></i></div>
                <div><div class="exam-metric-value">{{ $quiz->sessions->count() }}</div><div class="exam-metric-label">Tổng số ca thi</div></div>
            </article>
            <article class="exam-metric success">
                <div class="exam-metric-icon"><i class="fa-solid fa-signal"></i></div>
                <div><div class="exam-metric-value">{{ $liveSessions }}</div><div class="exam-metric-label">Ca đang diễn ra</div></div>
            </article>
            <article class="exam-metric">
                <div class="exam-metric-icon"><i class="fa-solid fa-users"></i></div>
                <div><div class="exam-metric-value">{{ $totalCandidates }}</div><div class="exam-metric-label">Lượt thí sinh được xếp</div></div>
            </article>
            <article class="exam-metric warning">
                <div class="exam-metric-icon"><i class="fa-solid fa-pen-to-square"></i></div>
                <div><div class="exam-metric-value">{{ $totalInProgress }}</div><div class="exam-metric-label">Đang làm · {{ $totalSubmitted }} đã nộp</div></div>
            </article>
        </section>

        <div class="exam-section-heading">
            <div><h2>Danh sách ca thi</h2><p>Theo dõi lịch, tiến độ và trạng thái công bố của từng ca.</p></div>
        </div>

        <div class="session-grid">
            @forelse($quiz->sessions as $session)
                @php
                    $submitted = $session->attempts->where('status', 'submitted')->count();
                    $inProgress = $session->attempts->where('status', 'in_progress')->count();
                    $candidateCount = $session->candidates->count();
                    $notStarted = max($candidateCount - $submitted - $inProgress, 0);
                    $completion = $candidateCount > 0 ? round(($submitted / $candidateCount) * 100) : 0;
                    [$state, $stateLabel] = match (true) {
                        $session->status === 'cancelled' => ['cancelled', 'Đã hủy'],
                        $session->status === 'closed' => ['ended', 'Đã đóng'],
                        $session->isOpen() => ['live', 'Đang diễn ra'],
                        $session->ends_at->isPast() => ['ended', 'Đã kết thúc'],
                        $session->starts_at->isFuture() => ['upcoming', 'Sắp diễn ra'],
                        default => ['upcoming', 'Đã lên lịch'],
                    };
                    $releaseLabel = match($session->result_release_policy) {
                        'immediate' => 'Công bố ngay khi nộp',
                        'manual' => 'Giáo viên công bố',
                        default => 'Sau khi kết thúc ca',
                    };
                @endphp

                <article class="session-card" data-state="{{ $state }}">
                    <div class="session-card-accent"></div>
                    <div class="session-card-body">
                        <div class="session-card-top">
                            <div>
                                <span class="session-state {{ $state }}">{{ $stateLabel }}</span>
                                <h3>{{ $session->name }}</h3>
                            </div>
                            <button class="exam-action exam-action-outline exam-action-sm" type="button"
                                data-bs-toggle="collapse" data-bs-target="#edit-session-{{ $session->id }}"
                                aria-controls="edit-session-{{ $session->id }}" aria-expanded="false">
                                <i class="fa-solid fa-sliders"></i> Thiết lập
                            </button>
                        </div>

                        <div class="session-schedule">
                            <div class="session-time-block">
                                <small>Bắt đầu</small>
                                <strong>{{ $session->starts_at->format('H:i · d/m/Y') }}</strong>
                            </div>
                            <i class="fa-solid fa-arrow-right session-time-arrow"></i>
                            <div class="session-time-block end">
                                <small>Kết thúc</small>
                                <strong>{{ $session->ends_at->format('H:i · d/m/Y') }}</strong>
                            </div>
                        </div>

                        <div class="session-info-row">
                            <span class="session-info-chip"><i class="fa-regular fa-hourglass-half"></i>{{ $session->starts_at->diffInMinutes($session->ends_at) }} phút mở ca</span>
                            <span class="session-info-chip"><i class="fa-solid fa-bullhorn"></i>{{ $releaseLabel }}</span>
                            @if($session->resultsAreReleased())
                                <span class="session-info-chip" style="color:var(--sl-success)"><i class="fa-solid fa-circle-check"></i>Đã công bố điểm</span>
                            @endif
                        </div>

                        <div class="session-stats">
                            <div class="session-stat"><strong>{{ $candidateCount }}</strong><small>Thí sinh</small></div>
                            <div class="session-stat"><strong style="color:var(--sl-primary)">{{ $inProgress }}</strong><small>Đang thi</small></div>
                            <div class="session-stat"><strong style="color:var(--sl-success)">{{ $submitted }}</strong><small>Đã nộp</small></div>
                        </div>
                        <div class="session-progress"><span style="width:{{ $completion }}%"></span></div>
                        <div class="session-progress-label"><span>{{ $notStarted }} chưa bắt đầu</span><span>{{ $completion }}% hoàn thành</span></div>

                        <div class="session-actions">
                            <a href="{{ route('quiz-sessions.monitor', $session) }}" class="exam-action exam-action-primary" style="background:var(--sl-primary);color:#fff;border-color:var(--sl-primary)">
                                <i class="fa-solid fa-display"></i> Mở giám sát
                            </a>
                            @if(!$session->resultsAreReleased())
                                <form method="POST" action="{{ route('quiz-sessions.release', $session) }}">
                                    @csrf
                                    <button class="exam-action exam-action-success" type="submit"
                                        onclick="return confirm('Công bố kết quả cho toàn bộ thí sinh trong ca này?')">
                                        <i class="fa-solid fa-bullhorn"></i> Công bố điểm
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="collapse session-edit-panel" id="edit-session-{{ $session->id }}">
                        @include('quizzes.partials.session-form', ['formSession' => $session, 'formId' => 'edit-'.$session->id])
                        @if(!$session->attempts->count())
                            <form method="POST" action="{{ route('quiz-sessions.destroy', $session) }}" class="mt-3">
                                @csrf @method('DELETE')
                                <button class="exam-action exam-action-danger exam-action-sm" type="submit"
                                    onclick="return confirm('Xóa ca thi này?')"><i class="fa-solid fa-trash"></i> Xóa ca thi</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="exam-empty" style="grid-column:1/-1">
                    <div class="exam-empty-icon"><i class="fa-solid fa-calendar-plus"></i></div>
                    <h3>Chưa có ca thi nào</h3>
                    <p>Tạo ca thi đầu tiên, thiết lập thời gian và chọn danh sách thí sinh được phép tham gia.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="modal fade" id="createSessionModal" tabindex="-1" aria-labelledby="createSessionTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header px-4 py-3">
                    <div><h5 class="modal-title fw-bold" id="createSessionTitle">Tạo ca thi mới</h5><div class="small text-muted mt-1">Thiết lập lịch và phân công thí sinh cho {{ $quiz->title }}.</div></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body p-4">@include('quizzes.partials.session-form', ['formSession' => null, 'formId' => 'create'])</div>
            </div>
        </div>
    </div>
@endsection
