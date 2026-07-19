@extends('layouts.app')

@section('title', 'Tiến độ lớp - ' . $classroom->name)

@push('styles')
    @vite('resources/css/pages/class-progress.css')
@endpush

@section('content')
    <div class="lms-page">

        <x-ui.page-header title="Dashboard tiến độ: {{ $classroom->name }}" :breadcrumbs="[
            ['label' => 'Lớp học', 'url' => route('classes.index')],
            ['label' => $classroom->code],
        ]">
            <x-slot:meta>
                <span><i class="fa-solid fa-chalkboard-teacher" aria-hidden="true"></i>
                    {{ $classroom->teacher->name }}</span>
                <span><i class="fa-solid fa-users" aria-hidden="true"></i>
                    {{ $classReport['student_count'] }} học sinh</span>
            </x-slot:meta>
            <x-slot:actions>
                <button type="button" class="lms-btn lms-btn-primary" data-ai-scope="class">
                    <i class="fa-solid fa-robot"></i> Phân tích AI toàn lớp
                </button>
                <a href="{{ route('classes.students.index', $classroom->id) }}" class="lms-btn lms-btn-outline">
                    <i class="fa-solid fa-user-graduate"></i> Danh sách học sinh
                </a>
                <a href="{{ route('classes.index') }}" class="lms-btn lms-btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        {{-- AI Analysis Panel --}}
        <div class="lms-ai-panel d-none" id="aiAnalysisPanel">
            <div class="lms-ai-header">
                <div>
                    <h2 class="lms-ai-title">
                        <i class="fa-solid fa-wand-magic-sparkles" style="color:var(--lms-blue);"></i>
                        Phân tích học tập bằng AI
                    </h2>
                    <div class="lms-ai-scope" id="aiAnalysisScope">Đang chờ dữ liệu phân tích</div>
                </div>
                <button type="button" class="lms-btn lms-btn-outline lms-btn-sm" id="aiAnalysisClose">
                    <i class="fa-solid fa-xmark"></i> Đóng
                </button>
            </div>
            <div class="lms-ai-body">
                <div id="aiAnalysisLoading" class="d-none">
                    <div class="lms-ai-loading">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        AI đang phân tích dữ liệu học tập...
                    </div>
                </div>
                <div id="aiAnalysisError" class="lms-alert-msg d-none"></div>
                <div id="aiAnalysisContent" class="d-none">
                    <div class="lms-ai-summary" id="aiSummary"></div>
                    <div class="lms-ai-columns">
                        <div class="lms-ai-col">
                            <div class="lms-ai-col-title">
                                <i class="fa-solid fa-triangle-exclamation" style="color:var(--lms-danger);"></i>
                                Rủi ro phát hiện
                            </div>
                            <div id="aiRisks"></div>
                        </div>
                        <div class="lms-ai-col">
                            <div class="lms-ai-col-title">
                                <i class="fa-solid fa-lightbulb" style="color:var(--lms-warning);"></i>
                                Hành động đề xuất
                            </div>
                            <div id="aiActions"></div>
                        </div>
                        <div class="lms-ai-col">
                            <div class="lms-ai-col-title">
                                <i class="fa-solid fa-comment-dots" style="color:var(--lms-blue);"></i>
                                Nhận xét học sinh
                            </div>
                            <div id="aiComments"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <div class="lms-card" style="margin-bottom:1.5rem;">
            <form action="{{ route('classes.progress', $classroom->id) }}" method="GET" class="lms-filter">
                <div class="lms-filter-group" style="flex:2; min-width:200px;">
                    <label>Khóa học</label>
                    <select name="course_id" class="lms-select" style="width:100%;">
                        <option value="">Tất cả khóa học</option>
                        @foreach ($availableCourses as $course)
                            <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? '') == $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lms-filter-group" style="justify-content:flex-end;">
                    <label style="visibility:hidden;">x</label>
                    <label class="lms-checkbox-wrap">
                        <input type="checkbox" name="attention_only" value="1" @checked($filters['attention_only'])>
                        Chỉ học sinh cần chú ý
                    </label>
                </div>
                <div style="display:flex; gap:8px; align-items:flex-end;">
                    <button type="submit" class="lms-btn lms-btn-primary" style="height:36px; padding:0 14px;">
                        <i class="fa-solid fa-filter"></i> Lọc
                    </button>
                    <a href="{{ route('classes.progress', $classroom->id) }}" class="lms-btn-reset" title="Xóa bộ lọc">
                        <i class="fa-solid fa-rotate-left" style="font-size:13px;"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- Class stats --}}
        <x-ui.stat-grid>
            <x-ui.stat-card label="Hoàn thành bài học" value="{{ $classReport['lesson_completion_rate'] }}%">
                <div class="lms-prog-bar">
                    <div class="lms-prog-fill" style="width:{{ $classReport['lesson_completion_rate'] }}%;"></div>
                </div>
                <div class="lms-stat-sub">{{ $classReport['lesson_completed'] }}/{{ $classReport['lesson_total'] }} lượt
                </div>
            </x-ui.stat-card>
            <x-ui.stat-card label="Tỷ lệ nộp bài" value="{{ $classReport['assignment_submission_rate'] }}%"
                tone="success">
                <div class="lms-prog-bar">
                    <div class="lms-prog-fill green" style="width:{{ $classReport['assignment_submission_rate'] }}%;">
                    </div>
                </div>
                <div class="lms-stat-sub">
                    {{ $classReport['assignment_submitted'] }}/{{ $classReport['assignment_total'] }} lượt</div>
            </x-ui.stat-card>
            <x-ui.stat-card label="Điểm trung bình" :value="$classReport['score_average'] ?? '—'">
                <div class="lms-stat-sub">Bài tập & quiz đã có</div>
                <div class="lms-stat-sub">Thiếu {{ $classReport['missing_assignment_total'] }} lượt bài</div>
            </x-ui.stat-card>
            <x-ui.stat-card label="Cần chú ý" :value="$classReport['needs_attention_count']" tone="danger">
                <div class="lms-stat-sub">{{ $classReport['absence_total'] }} lượt vắng toàn lớp</div>
                <div class="lms-stat-sub">{{ $classReport['pending_quiz_total'] }} lượt quiz chưa làm</div>
            </x-ui.stat-card>
        </x-ui.stat-grid>

        {{-- Course cards --}}
        @if (count($courseReports) > 0)
            <div class="lms-courses">
                @forelse ($courseReports as $courseReport)
                    <div class="lms-course-card">
                        <div class="lms-course-card-header">
                            <div class="lms-course-card-title">{{ $courseReport['course']->title }}</div>
                            <span class="lms-course-badge">{{ $courseReport['report']['student_count'] }} HS</span>
                        </div>
                        <div class="lms-course-prog-label">Bài học:
                            {{ $courseReport['report']['lesson_completion_rate'] }}%</div>
                        <div class="lms-prog-bar" style="margin:0 0 8px;">
                            <div class="lms-prog-fill"
                                style="width:{{ $courseReport['report']['lesson_completion_rate'] }}%;"></div>
                        </div>
                        <div class="lms-course-tags">
                            <span class="lms-course-tag"><i class="fa-solid fa-paperclip" style="font-size:11px;"></i> Nộp bài
                                {{ $courseReport['report']['assignment_submission_rate'] }}%</span>
                            <span class="lms-course-tag"><i class="fa-solid fa-star" style="font-size:11px;"></i> TB
                                {{ $courseReport['report']['score_average'] ?? 'N/A' }}</span>
                            <span class="lms-course-tag"><i class="fa-solid fa-user-clock" style="font-size:11px;"></i>
                                {{ $courseReport['report']['needs_attention_count'] }} cần chú ý</span>
                        </div>
                    </div>
                @empty
                    <div style="grid-column:1/-1;">
                        <div
                            style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:var(--lms-radius); padding:14px 16px; font-size:13.5px; color:#1E40AF;">
                            Lớp chưa được gán khóa học.
                        </div>
                    </div>
                @endforelse
            </div>
        @endif

        {{-- Student progress table --}}
        <div class="lms-card">
            <div class="lms-card-header">
                <h2 class="lms-card-title"><i class="fa-solid fa-chart-line"></i> Tiến độ từng học sinh</h2>
                <span class="lms-count">{{ $studentProgress->count() }} kết quả</span>
            </div>
            <div class="lms-table-wrap">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>Học sinh</th>
                            <th>Bài học</th>
                            <th>Bài tập</th>
                            <th>Quiz</th>
                            <th>Điểm TB</th>
                            <th>Vắng</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($studentProgress as $summary)
                            @php
                                $student = $summary['student'];
                                $modalId = 'progressStudentModal' . $student->id;
                            @endphp
                            <tr>
                                <td>
                                    <div class="lms-student-name">{{ $student->name }}</div>
                                    @if ($student->username)
                                        <div class="lms-student-email"><i class="fa-solid fa-id-badge"></i> {{ $student->username }}</div>
                                    @endif
                                    @if ($student->student_code)
                                        <div class="lms-student-email"><i class="fa-solid fa-hashtag"></i> {{ $student->student_code }}</div>
                                    @endif
                                    <div class="lms-student-email">{{ $student->email }}</div>
                                </td>
                                <td>
                                    <div class="lms-micro">
                                        <div class="lms-micro-val">
                                            {{ $summary['lesson_completed'] }}/{{ $summary['lesson_total'] }}</div>
                                        <div class="lms-micro-bar">
                                            <div class="lms-micro-fill"
                                                style="width:{{ $summary['lesson_progress'] }}%;"></div>
                                        </div>
                                        <div class="lms-micro-sub">{{ $summary['lesson_progress'] }}%</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="lms-micro">
                                        <div class="lms-micro-val">
                                            {{ $summary['assignment_submitted_count'] }}/{{ $summary['assignment_total'] }}
                                            đã nộp</div>
                                        <div class="lms-micro-bar">
                                            <div class="lms-micro-fill green"
                                                style="width:{{ $summary['assignment_total'] > 0 ? round(($summary['assignment_submitted_count'] / $summary['assignment_total']) * 100) : 0 }}%;">
                                            </div>
                                        </div>
                                        <div class="lms-micro-sub">{{ $summary['assignment_missing_count'] }} thiếu</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="lms-micro">
                                        <div class="lms-micro-val">
                                            {{ $summary['quiz_attempted_count'] }}/{{ $summary['quiz_total'] }}</div>
                                        <div class="lms-micro-sub">{{ $summary['quiz_pending_count'] }} chưa làm</div>
                                    </div>
                                </td>
                                <td style="font-size:14px; font-weight:700; color:var(--lms-text);">
                                    {{ $summary['score_average'] ?? '—' }}
                                </td>
                                <td
                                    style="font-size:13.5px; font-weight:600; {{ $summary['absence_count'] > 0 ? 'color:var(--lms-danger)' : 'color:var(--lms-muted)' }}">
                                    {{ $summary['absence_count'] }}
                                </td>
                                <td>
                                    @if ($summary['needs_attention'])
                                        <span class="lms-badge lms-badge-danger">Cần chú ý</span>
                                    @else
                                        <span class="lms-badge lms-badge-success">Ổn định</span>
                                    @endif
                                    <div class="lms-alerts">
                                        @forelse ($summary['alerts'] as $alert)
                                            <div class="lms-alert-item {{ $alert['level'] }}">
                                                <i class="fa-solid fa-circle-exclamation"></i>
                                                <span>{{ $alert['text'] }}</span>
                                            </div>
                                        @empty
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <div class="lms-row-actions">
                                        <button class="lms-btn-ai" data-ai-scope="student"
                                            data-student-id="{{ $student->id }}"
                                            data-student-name="{{ $student->name }}">
                                            <i class="fa-solid fa-robot"></i> AI
                                        </button>
                                        <button class="lms-btn-detail" data-bs-toggle="modal"
                                            data-bs-target="#{{ $modalId }}">
                                            <i class="fa-solid fa-list-check"></i> Chi tiết
                                        </button>
                                        <a href="{{ route('classes.students.show', ['classId' => $classroom->id, 'studentId' => $student->id, 'course_id' => $filters['course_id']]) }}"
                                            class="lms-btn-profile">
                                            Hồ sơ
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    style="text-align:center; padding:48px 20px; color:var(--lms-muted); font-size:13.5px;">
                                    Không có học sinh phù hợp với bộ lọc hiện tại.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modals --}}
    @foreach ($studentProgress as $summary)
        @php
            $student = $summary['student'];
            $modalId = 'progressStudentModal' . $student->id;
            $missingAssignments = $summary['assignment_details']->where('status', 'missing');
            $completedLessons = $summary['lesson_details']->where('is_completed', true);
            $attemptedQuizzes = $summary['quiz_details']->where('status', 'attempted');
        @endphp
        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">{{ $student->name }}</h5>
                            <div class="modal-subtitle">{{ $student->email }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px;">
                            <div class="lms-modal-section">
                                <div class="lms-modal-section-title"><i class="fa-solid fa-book"
                                        style="color:var(--lms-blue); margin-right:6px; font-size:13px;"></i>Bài học hoàn
                                    thành</div>
                                <div class="lms-modal-list">
                                    @forelse ($completedLessons as $lesson)
                                        <div class="lms-modal-item">
                                            <div class="lms-modal-item-title">{{ $lesson['title'] }}</div>
                                            <div class="lms-modal-item-sub">{{ $lesson['course_title'] }} ·
                                                {{ $lesson['module_title'] }}</div>
                                        </div>
                                    @empty
                                        <div class="lms-modal-empty">Chưa hoàn thành bài học nào.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="lms-modal-section">
                                <div class="lms-modal-section-title"><i class="fa-solid fa-clipboard-check"
                                        style="color:var(--lms-success); margin-right:6px; font-size:13px;"></i>Quiz đã làm
                                </div>
                                <div class="lms-modal-list">
                                    @forelse ($attemptedQuizzes as $quiz)
                                        <div class="lms-modal-item">
                                            <div class="lms-modal-item-title">{{ $quiz['title'] }}</div>
                                            <div class="lms-modal-item-sub">{{ $quiz['course_title'] }} · Điểm
                                                {{ $quiz['score'] ?? 'N/A' }}</div>
                                        </div>
                                    @empty
                                        <div class="lms-modal-empty">Chưa làm quiz nào.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="lms-modal-section">
                                <div class="lms-modal-section-title"><i class="fa-solid fa-triangle-exclamation"
                                        style="color:var(--lms-danger); margin-right:6px; font-size:13px;"></i>Bài tập còn
                                    thiếu</div>
                                <div class="lms-modal-list">
                                    @forelse ($missingAssignments as $assignment)
                                        <div class="lms-modal-item">
                                            <div class="lms-modal-item-title">{{ $assignment['title'] }}</div>
                                            <div class="lms-modal-item-sub">{{ $assignment['course_title'] }}</div>
                                            @if ($assignment['is_overdue'])
                                                <span class="lms-badge lms-badge-danger"
                                                    style="margin-top:4px; font-size:11px;">Quá hạn</span>
                                            @else
                                                <span class="lms-badge lms-badge-warning"
                                                    style="margin-top:4px; font-size:11px;">Chưa nộp</span>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="lms-modal-empty">Không còn bài tập thiếu.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="lms-btn lms-btn-outline" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const panel = document.getElementById('aiAnalysisPanel');
                const loading = document.getElementById('aiAnalysisLoading');
                const errorBox = document.getElementById('aiAnalysisError');
                const content = document.getElementById('aiAnalysisContent');
                const scopeLabel = document.getElementById('aiAnalysisScope');
                const summaryBox = document.getElementById('aiSummary');
                const risksBox = document.getElementById('aiRisks');
                const actionsBox = document.getElementById('aiActions');
                const commentsBox = document.getElementById('aiComments');

                const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const empty = (t) => `<div class="lms-ai-empty">${esc(t)}</div>`;

                const renderRisks = (risks = []) => {
                    if (!risks.length) return empty('AI chưa phát hiện rủi ro rõ ràng.');
                    return risks.map((r) => {
                        const cls = r.level === 'high' ? 'lms-badge-danger' : r.level === 'medium' ?
                            'lms-badge-warning' : 'lms-badge';
                        return `<div class="lms-ai-item">
                <span class="lms-badge ${cls}" style="margin-bottom:6px; font-size:11px;">${esc(r.level)}</span>
                <div class="lms-ai-item-name">${esc(r.student || 'Toàn lớp')}</div>
                <div class="lms-ai-item-sub">${esc(r.type || '')}</div>
                <div class="lms-ai-item-text">${esc(r.reason || '')}</div>
            </div>`;
                    }).join('');
                };

                const renderActions = (actions = []) => {
                    if (!actions.length) return empty('Chưa có hành động đề xuất.');
                    return actions.map((a) => `<div class="lms-ai-item">
            <div class="lms-ai-item-name">${esc(a.action || '')}</div>
            <div class="lms-ai-item-sub">${esc(a.student || 'Nhóm học sinh')} · Ưu tiên ${esc(a.priority || 'medium')}</div>
            <div class="lms-ai-item-text">${esc(a.reason || '')}</div>
        </div>`).join('');
                };

                const renderComments = (comments = []) => {
                    if (!comments.length) return empty('Chưa có nhận xét học sinh.');
                    return comments.map((c) => `<div class="lms-ai-item">
            <div class="lms-ai-item-name">${esc(c.student || 'Học sinh')}</div>
            <div class="lms-ai-item-text">${esc(c.comment || '')}</div>
        </div>`).join('');
                };

                const setState = (state, message = '') => {
                    panel.classList.remove('d-none');
                    loading.classList.toggle('d-none', state !== 'loading');
                    errorBox.classList.toggle('d-none', state !== 'error');
                    content.classList.toggle('d-none', state !== 'success');
                    if (state === 'error') errorBox.textContent = message;
                    panel.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                };

                document.querySelectorAll('[data-ai-scope]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const studentId = btn.dataset.studentId || '';
                        const studentName = btn.dataset.studentName || '';
                        const courseId = document.querySelector('select[name="course_id"]')
                            ?.value || '';
                        scopeLabel.textContent = studentId ? `Phân tích học sinh: ${studentName}` :
                            'Phân tích tổng quan toàn lớp';
                        setState('loading');

                        try {
                            const response = await axios.post(
                                '{{ route('classes.ai-analysis', $classroom->id) }}', {
                                    student_id: studentId || null,
                                    course_id: courseId || null,
                                }, {
                                    headers: {
                                        'X-CSRF-TOKEN': csrfToken
                                    }
                                });

                            const waitForOperation = async (url) => {
                                for (let attempt = 0; attempt < 90; attempt++) {
                                    const statusResponse = await axios.get(url);
                                    const operation = statusResponse.data;
                                    if (operation.status === 'completed') return operation.result || {};
                                    if (operation.status === 'failed') throw new Error(operation.message || 'Tác vụ AI thất bại.');
                                    await new Promise(resolve => setTimeout(resolve, 2000));
                                }
                                throw new Error('AI xử lý quá lâu. Bạn có thể tải lại trang và thử lại sau.');
                            };
                            const operationResult = response.data.queued
                                ? await waitForOperation(response.data.status_url)
                                : response.data;
                            const analysis = operationResult.analysis || {};
                            summaryBox.textContent = analysis.summary || 'AI chưa có tóm tắt.';
                            risksBox.innerHTML = renderRisks(analysis.risks || []);
                            actionsBox.innerHTML = renderActions(analysis.actions || []);
                            commentsBox.innerHTML = renderComments(analysis.student_comments || []);
                            setState('success');
                        } catch (error) {
                            setState('error', error.response?.data?.message ||
                                'Không thể gọi AI phân tích. Vui lòng thử lại.');
                        }
                    });
                });

                document.getElementById('aiAnalysisClose')?.addEventListener('click', () => {
                    panel.classList.add('d-none');
                });
            });
        </script>
    @endpush
@endsection
