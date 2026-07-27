@extends('layouts.app')

@section('title', 'Giám sát: ' . $session->name)

@push('styles')
    @vite('resources/css/pages/quiz-sessions.css')
@endpush

@section('content')
    @php
        [$sessionState, $sessionStateLabel] = match (true) {
            $session->status === 'cancelled' => ['offline', 'Ca thi đã hủy'],
            $session->isOpen() => ['', 'Đang giám sát trực tiếp'],
            $session->starts_at->isFuture() => ['offline', 'Ca thi chưa bắt đầu'],
            default => ['offline', 'Ca thi đã kết thúc'],
        };
        $releaseLabel = match($session->result_release_policy) {
            'immediate' => 'Điểm công bố ngay',
            'manual' => 'Công bố thủ công',
            default => 'Công bố sau ca',
        };
    @endphp

    <div class="exam-admin-page">
        <nav class="exam-breadcrumb" aria-label="Điều hướng">
            <a href="{{ route('courses.show', $session->quiz->course_id) }}">{{ $session->quiz->course->title }}</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('quizzes.sessions.index', $session->quiz) }}">Ca thi</a>
            <i class="fa-solid fa-chevron-right"></i>
            <strong>Giám sát</strong>
        </nav>

        <header class="exam-hero monitor-hero">
            <div class="exam-hero-content">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="monitor-live {{ $sessionState }}" id="monitor-connection">{{ $sessionStateLabel }}</span>
                    <span class="monitor-countdown" id="session-countdown">--:--:--</span>
                </div>
                <h1>{{ $session->name }}</h1>
                <div class="exam-hero-meta">
                    <span><i class="fa-solid fa-file-circle-question"></i>{{ $session->quiz->title }}</span>
                    <span><i class="fa-regular fa-clock"></i>{{ $session->starts_at->format('H:i') }} – {{ $session->ends_at->format('H:i · d/m/Y') }}</span>
                    <span><i class="fa-solid fa-shield-halved"></i>{{ $releaseLabel }}</span>
                </div>
            </div>
            <div class="exam-hero-actions">
                <a href="{{ route('quizzes.sessions.index', $session->quiz) }}" class="exam-action exam-action-glass">
                    <i class="fa-solid fa-arrow-left"></i> Danh sách ca
                </a>
                @if(!$session->resultsAreReleased())
                    <form method="POST" action="{{ route('quiz-sessions.release', $session) }}">
                        @csrf
                        <button class="exam-action exam-action-primary" type="submit"
                            onclick="return confirm('Công bố kết quả cho toàn bộ thí sinh trong ca này?')">
                            <i class="fa-solid fa-bullhorn"></i> Công bố kết quả
                        </button>
                    </form>
                @else
                    <span class="exam-action exam-action-primary"><i class="fa-solid fa-circle-check"></i> Đã công bố</span>
                @endif
            </div>
        </header>

        <section class="exam-overview monitor-overview" id="monitor-summary" aria-label="Tổng quan giám sát">
            <article class="exam-metric">
                <div class="exam-metric-icon"><i class="fa-solid fa-users"></i></div>
                <div><div class="exam-metric-value" data-summary="total">0</div><div class="exam-metric-label">Tổng thí sinh</div></div>
            </article>
            <article class="exam-metric">
                <div class="exam-metric-icon"><i class="fa-regular fa-circle-play"></i></div>
                <div><div class="exam-metric-value" data-summary="not_started">0</div><div class="exam-metric-label">Chưa bắt đầu</div></div>
            </article>
            <article class="exam-metric">
                <div class="exam-metric-icon"><i class="fa-solid fa-pen-to-square"></i></div>
                <div><div class="exam-metric-value" data-summary="in_progress">0</div><div class="exam-metric-label">Đang làm bài</div></div>
            </article>
            <article class="exam-metric danger">
                <div class="exam-metric-icon"><i class="fa-solid fa-wifi"></i></div>
                <div><div class="exam-metric-value" data-summary="disconnected">0</div><div class="exam-metric-label">Mất kết nối</div></div>
            </article>
            <article class="exam-metric success">
                <div class="exam-metric-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div><div class="exam-metric-value" data-summary="submitted">0</div><div class="exam-metric-label">Đã nộp bài</div></div>
            </article>
        </section>

        <div class="monitor-alert" id="disconnect-alert" role="status">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><strong data-disconnected-count>0 thí sinh</strong> không gửi tín hiệu trong hơn 45 giây. Hãy kiểm tra kết nối hoặc liên hệ phòng thi.</span>
        </div>

        <section class="monitor-panel">
            <div class="monitor-toolbar">
                <div class="monitor-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" id="candidate-search" placeholder="Tìm tên hoặc mã thí sinh..." aria-label="Tìm thí sinh">
                </div>
                <div class="monitor-toolbar-right">
                    <span class="monitor-updated" id="last-updated">Đang kết nối máy chủ...</span>
                    <button class="monitor-refresh" id="refresh-monitor" type="button" title="Cập nhật ngay" aria-label="Cập nhật dữ liệu">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                </div>
            </div>

            <div class="monitor-filters" role="group" aria-label="Lọc trạng thái thí sinh">
                <button class="monitor-filter active" type="button" data-status-filter="all">Tất cả <span data-filter-count="all">0</span></button>
                <button class="monitor-filter" type="button" data-status-filter="not_started">Chưa bắt đầu <span data-filter-count="not_started">0</span></button>
                <button class="monitor-filter" type="button" data-status-filter="in_progress">Đang thi <span data-filter-count="in_progress">0</span></button>
                <button class="monitor-filter" type="button" data-status-filter="disconnected">Mất kết nối <span data-filter-count="disconnected">0</span></button>
                <button class="monitor-filter" type="button" data-status-filter="submitted">Đã nộp <span data-filter-count="submitted">0</span></button>
                <button class="monitor-filter" type="button" data-status-filter="pending_grading">Chờ chấm <span data-filter-count="pending_grading">0</span></button>
                <button class="monitor-filter" type="button" data-status-filter="graded">Đã chấm <span data-filter-count="graded">0</span></button>
                <button class="monitor-filter" type="button" data-status-filter="released">Đã công bố <span data-filter-count="released">0</span></button>
            </div>

            <div class="monitor-table-wrap">
                <table class="monitor-table">
                    <thead><tr><th>Thí sinh</th><th>Trạng thái</th><th>Tiến độ làm bài</th><th>Bắt đầu</th><th>Tín hiệu gần nhất</th><th>Nộp bài</th></tr></thead>
                    <tbody id="candidate-table">
                        <tr><td colspan="6" class="monitor-empty"><i class="fa-solid fa-spinner fa-spin me-2"></i>Đang tải dữ liệu giám sát...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const url = @json(route('quiz-sessions.monitor-data', $session));
            const startsAt = new Date(@json($session->starts_at->toIso8601String()));
            const endsAt = new Date(@json($session->ends_at->toIso8601String()));
            const sessionIsOpen = @json($session->isOpen());
            const sessionLabel = @json($sessionStateLabel);
            const state = { candidates: [], filter: 'all', query: '', loading: false };
            const statusMeta = {
                not_started: { label: 'Chưa bắt đầu', priority: 3 },
                in_progress: { label: 'Đang thi', priority: 1 },
                disconnected: { label: 'Mất kết nối', priority: 0 },
                submitted: { label: 'Đã nộp', priority: 2 },
                pending_grading: { label: 'Chờ chấm', priority: 2 },
                graded: { label: 'Đã chấm', priority: 3 },
                released: { label: 'Đã công bố', priority: 4 }
            };
            const table = document.getElementById('candidate-table');
            const refreshButton = document.getElementById('refresh-monitor');
            const connection = document.getElementById('monitor-connection');
            const updated = document.getElementById('last-updated');

            const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({
                '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;'
            }[char]));

            const initials = name => {
                const words = String(name || '').trim().split(/\s+/).filter(Boolean);
                return ((words[0]?.[0] || '') + (words.at(-1)?.[0] || '')).toLocaleUpperCase('vi') || 'HV';
            };

            const render = () => {
                const query = state.query.toLocaleLowerCase('vi');
                const visible = state.candidates
                    .filter(candidate => state.filter === 'all' || candidate.status === state.filter || (state.filter === 'submitted' && ['submitted','pending_grading','graded','released'].includes(candidate.status)))
                    .filter(candidate => `${candidate.name} ${candidate.student_code || ''}`.toLocaleLowerCase('vi').includes(query))
                    .sort((a, b) => (statusMeta[a.status]?.priority ?? 9) - (statusMeta[b.status]?.priority ?? 9) || a.name.localeCompare(b.name, 'vi'));

                if (visible.length === 0) {
                    table.innerHTML = '<tr><td colspan="6" class="monitor-empty"><i class="fa-solid fa-user-slash me-2"></i>Không có thí sinh phù hợp bộ lọc.</td></tr>';
                    return;
                }

                table.innerHTML = visible.map(candidate => {
                    const meta = statusMeta[candidate.status] || statusMeta.not_started;
                    const answered = Number(candidate.answered || 0);
                    const total = Number(candidate.total || 0);
                    const percent = total > 0 ? Math.min(100, Math.round(answered / total * 100)) : 0;
                    return `<tr>
                        <td data-label="Thí sinh"><div class="monitor-person"><div class="monitor-avatar">${escapeHtml(initials(candidate.name))}</div><div><strong>${escapeHtml(candidate.name)}</strong><small>${escapeHtml(candidate.student_code || 'Chưa có mã học viên')}</small></div></div></td>
                        <td data-label="Trạng thái"><span class="monitor-status ${escapeHtml(candidate.status)}">${meta.label}</span></td>
                        <td data-label="Tiến độ"><div class="monitor-progress-wrap"><div class="monitor-progress-meta"><span>${answered}/${total} câu</span><strong>${percent}%</strong></div><div class="monitor-progress ${['submitted','pending_grading','graded','released'].includes(candidate.status) ? 'complete' : ''}"><span style="width:${percent}%"></span></div></div></td>
                        <td data-label="Bắt đầu"><span class="monitor-time">${escapeHtml(candidate.started_at || '—')}</span></td>
                        <td data-label="Kết nối cuối"><span class="monitor-time">${escapeHtml(candidate.last_seen_at || '—')}</span></td>
                        <td data-label="Nộp bài"><span class="monitor-time">${escapeHtml(candidate.completed_at || '—')}</span></td>
                    </tr>`;
                }).join('');
            };

            const updateCounts = summary => {
                Object.entries(summary).forEach(([key, value]) => {
                    const element = document.querySelector(`[data-summary="${key}"]`);
                    if (element) element.textContent = value;
                });
                ['not_started','in_progress','disconnected','submitted','pending_grading','graded','released'].forEach(status => {
                    const element = document.querySelector(`[data-filter-count="${status}"]`);
                    if (element) element.textContent = summary[status] || 0;
                });
                document.querySelector('[data-filter-count="all"]').textContent = summary.total || 0;
                const disconnected = Number(summary.disconnected || 0);
                document.getElementById('disconnect-alert').classList.toggle('show', disconnected > 0);
                document.querySelector('[data-disconnected-count]').textContent = `${disconnected} thí sinh`;
            };

            const load = async () => {
                if (state.loading) return;
                state.loading = true;
                refreshButton.classList.add('loading');
                try {
                    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    if (!response.ok) throw new Error('Không thể tải dữ liệu');
                    const data = await response.json();
                    state.candidates = data.candidates || [];
                    updateCounts(data.summary || {});
                    render();
                    connection.classList.toggle('offline', !sessionIsOpen);
                    connection.textContent = sessionIsOpen ? 'Đang giám sát trực tiếp' : sessionLabel;
                    updated.textContent = `Cập nhật lúc ${new Date(data.server_time).toLocaleTimeString('vi-VN')}`;
                } catch (error) {
                    connection.classList.add('offline');
                    connection.textContent = 'Mất kết nối giám sát';
                    updated.textContent = 'Không thể cập nhật · đang thử lại';
                } finally {
                    state.loading = false;
                    refreshButton.classList.remove('loading');
                }
            };

            document.querySelectorAll('[data-status-filter]').forEach(button => button.addEventListener('click', () => {
                state.filter = button.dataset.statusFilter;
                document.querySelectorAll('[data-status-filter]').forEach(item => item.classList.toggle('active', item === button));
                render();
            }));
            document.getElementById('candidate-search').addEventListener('input', event => {
                state.query = event.target.value.trim();
                render();
            });
            refreshButton.addEventListener('click', load);

            const renderCountdown = () => {
                const now = new Date();
                const target = now < startsAt ? startsAt : endsAt;
                let seconds = Math.max(0, Math.floor((target - now) / 1000));
                if (now >= endsAt) {
                    document.getElementById('session-countdown').textContent = 'Đã kết thúc';
                    return;
                }
                const hours = Math.floor(seconds / 3600); seconds %= 3600;
                const minutes = Math.floor(seconds / 60); const remainingSeconds = seconds % 60;
                document.getElementById('session-countdown').textContent = `${now < startsAt ? 'Mở sau ' : 'Còn '}${String(hours).padStart(2,'0')}:${String(minutes).padStart(2,'0')}:${String(remainingSeconds).padStart(2,'0')}`;
            };

            renderCountdown();
            setInterval(renderCountdown, 1000);
            load();
            setInterval(() => { if (!document.hidden) load(); }, 15000);
        })();
    </script>
@endsection
