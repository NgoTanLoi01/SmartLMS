<div class="accordion accordion-flush" id="courseAccordion">
    @php
        $isStudent = auth()->user()->role === 'student';
        $isManager = auth()->id() === $course->teacher_id || auth()->user()->role === 'admin';
        $currentTime = now();
    @endphp

    @forelse ($course->modules as $moduleIndex => $module)
        @php
            $lessonCount = $module->lessons->count();
            $totalSeconds = $module->lessons->sum(fn($l) => $l->duration_seconds ?? 0);
            $durationStr =
                $totalSeconds > 0
                    ? sprintf(
                        '%02d:%02d:%02d',
                        floor($totalSeconds / 3600),
                        floor(($totalSeconds % 3600) / 60),
                        $totalSeconds % 60,
                    )
                    : null;
        @endphp

        <div class="accordion-item module-sortable-item" data-module-id="{{ $module->id }}">

            {{-- Module header --}}
            <div class="module-header-wrapper d-flex align-items-center position-relative">
                @if ($isManager)
                    <i class="fa-solid fa-grip-vertical drag-handle ms-2" title="Kéo để sắp xếp chương"></i>
                @endif

                <button class="accordion-button {{ $moduleIndex == 0 ? '' : 'collapsed' }} flex-grow-1 shadow-none"
                    type="button" data-bs-toggle="collapse" data-bs-target="#module-{{ $module->id }}"
                    aria-expanded="{{ $moduleIndex === 0 ? 'true' : 'false' }}"
                    aria-controls="module-{{ $module->id }}">
                    <div class="module-title-block" style="padding-left:{{ $isManager ? '8px' : '16px' }};">
                        <div class="d-flex align-items-center gap-2">
                            <span class="module-number-badge">Chương {{ str_pad((string) ($moduleIndex + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="module-title-text">{{ $module->title }}</span>
                        </div>
                        <span class="module-meta module-meta--numbered">
                            {{ $lessonCount }} bài học{{ $durationStr ? ' · ' . $durationStr : '' }}
                        </span>
                    </div>
                </button>

                @if ($isManager)
                    <div class="action-buttons d-flex align-items-center pe-2">
                        <button type="button" class="btn-action btn-edit edit-module-btn border-0"
                            data-id="{{ $module->id }}" data-title="{{ $module->title }}" data-bs-toggle="modal"
                            data-bs-target="#editModuleModal" title="Sửa chương" aria-label="Sửa chương {{ $module->title }}">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <form action="{{ route('modules.destroy', $module->id) }}" method="POST"
                            class="d-inline mb-0">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-action btn-delete border-0 bg-transparent"
                                onclick="return confirm('Lưu trữ chương này? Bài học và bài tập liên quan sẽ được ẩn nhưng dữ liệu vẫn được giữ lại.')"
                                title="Xóa chương">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <div id="module-{{ $module->id }}"
                class="accordion-collapse collapse {{ $moduleIndex == 0 ? 'show' : '' }}"
                data-bs-parent="#courseAccordion">
                <div class="accordion-body p-0">
                    <div class="list-group list-group-flush lesson-sortable-list" data-module-id="{{ $module->id }}">

                        @forelse ($module->lessons as $lessonIndex => $lesson)
                            @php
                                $isVideo = !empty($lesson->video_url);
                                $lessonAssignments = $lesson->assignments ?? collect();
                                $pendingAssignmentCount = $isStudent
                                    ? $lessonAssignments->filter(fn($a) => !isset($userSubmissions[$a->id]))->count()
                                    : $lessonAssignments->count();
                                $durSec = $lesson->duration_seconds ?? 0;
                                $durLabel =
                                    $durSec > 0
                                        ? ($durSec >= 3600
                                            ? sprintf(
                                                '%d:%02d:%02d',
                                                floor($durSec / 3600),
                                                floor(($durSec % 3600) / 60),
                                                $durSec % 60,
                                            )
                                            : sprintf('%d:%02d', floor($durSec / 60), $durSec % 60))
                                        : null;
                            @endphp

                            {{-- ── LESSON ROW ── --}}
                            <div class="list-group-item border-0 px-0 py-0 lesson-item-wrapper d-flex align-items-center justify-content-between shadow-none"
                                data-lesson-id="{{ $lesson->id }}" style="min-width:0;">

                                @if ($isManager)
                                    <i class="fa-solid fa-grip-vertical drag-handle ms-2" title="Kéo để sắp xếp bài học"></i>
                                @endif

                                <button type="button"
                                    class="lesson-item course-outline-trigger text-decoration-none flex-grow-1 d-flex align-items-center gap-2 py-2 pe-2 border-0 bg-transparent text-start"
                                    style="min-width:0;padding-left:{{ $isManager ? '8px' : '16px' }};"
                                    data-id="{{ $lesson->id }}" data-content-url="{{ route('lessons.content', $lesson) }}"
                                    data-title="{{ $lesson->title }}" data-video="{{ $lesson->video_url }}"
                                    data-module="{{ $module->id }}" data-module-title="{{ $module->title }}"
                                    data-module-number="{{ $moduleIndex + 1 }}" data-lesson-number="{{ $lessonIndex + 1 }}"
                                    data-duration-label="{{ $durLabel }}"
                                    data-attachment="{{ $lesson->attachment ? route('lessons.attachment', $lesson->id) : '' }}"
                                    data-attachment-name="{{ $lesson->attachment_original_name ?: ($lesson->attachment ? basename($lesson->attachment) : '') }}">

                                    <span class="lesson-order-badge" aria-hidden="true">{{ $moduleIndex + 1 }}.{{ $lessonIndex + 1 }}</span>

                                    <div style="min-width:0;flex:1;">
                                        <div class="lesson-name-text">{{ $lesson->title }}</div>

                                        @if ($isStudent)
                                            <div class="sidebar-status-row">
                                                <span class="sidebar-status-pill lesson-type"><i
                                                        class="fa-solid {{ $isVideo ? 'fa-circle-play' : 'fa-file-lines' }}"></i>{{ $isVideo ? 'Video' : 'Bài đọc' }}</span>
                                                @if ($pendingAssignmentCount > 0)
                                                    <span class="sidebar-status-pill assignment">
                                                        <i
                                                            class="fa-solid fa-file-signature"></i>{{ $pendingAssignmentCount }}
                                                        bài tập
                                                    </span>
                                                @endif
                                                @if ($durLabel)
                                                    <span class="sidebar-status-pill pending">
                                                        <i class="fa-regular fa-clock"></i>{{ $durLabel }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($isManager)
                                            <div
                                                class="lesson-dur-text d-flex align-items-center gap-1 flex-wrap mt-1">
                                                <span
                                                    class="badge bg-{{ $lesson->status === 'published' ? 'success' : ($lesson->status === 'hidden' ? 'secondary' : 'warning text-dark') }}"
                                                    style="font-size: 11px;">
                                                    {{ match ($lesson->status ?? 'published') { 'draft' => 'Bản nháp', 'hidden' => 'Đang ẩn', 'archived' => 'Đã lưu trữ', default => 'Đã xuất bản' } }}
                                                </span>
                                                @if ($lesson->available_from && $lesson->available_from->gt($currentTime))
                                                    <span style="font-size: 11px;color:#6b7280;">Mở:
                                                        {{ $lesson->available_from->format('d/m H:i') }}</span>
                                                @endif
                                                @if ($durLabel)
                                                    <span style="font-size: 11px;color:#9ca3af;"><i
                                                            class="fa-regular fa-clock me-1"></i>{{ $durLabel }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </button>

                                @if ($isManager)
                                    <div class="action-buttons d-flex pe-2">
                                        <button type="button" class="btn-action btn-edit edit-lesson-btn border-0"
                                            data-id="{{ $lesson->id }}" data-title="{{ $lesson->title }}"
                                            data-content-url="{{ route('lessons.content', $lesson) }}"
                                            data-video="{{ $lesson->video_url }}" data-module="{{ $module->id }}"
                                            data-status="{{ $lesson->status ?? 'published' }}"
                                            data-available-from="{{ $lesson->available_from?->format('Y-m-d\TH:i') }}"
                                            data-bs-toggle="modal" data-bs-target="#editLessonModal"
                                            title="Sửa bài học" aria-label="Sửa bài học {{ $lesson->title }}">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <form action="{{ route('lessons.destroy', $lesson->id) }}" method="POST"
                                            class="d-inline mb-0">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="btn-action btn-delete border-0 bg-transparent"
                                                onclick="return confirm('Lưu trữ bài học này?')" title="Xóa bài học">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            {{-- ── ASSIGNMENT ROWS ── --}}
                            @foreach ($lesson->assignments as $assignment)
                                @php
                                    $submission =
                                        $isStudent && isset($userSubmissions[$assignment->id])
                                            ? $userSubmissions[$assignment->id]
                                            : null;
                                    $assignmentOverdue =
                                        $assignment->due_date && $assignment->due_date->isPast() && !$submission;
                                    $assignmentTypeLabel = match ($assignment->type ?? 'file') {
                                        'essay' => 'Tự luận',
                                        'mixed' => 'File + tự luận',
                                        default => 'Nộp file',
                                    };
                                @endphp

                                <div class="list-group-item border-0 px-0 py-0 assignment-item-wrapper {{ $submission ? 'submitted' : '' }} d-flex align-items-center justify-content-between shadow-none"
                                    style="min-width:0;">

                                    <button type="button"
                                        class="assignment-item course-outline-trigger text-decoration-none flex-grow-1 d-flex align-items-center gap-2 py-2 pe-2 ps-5 border-0 bg-transparent text-start"
                                        style="min-width:0;" data-id="{{ $assignment->id }}"
                                        data-title="{{ $assignment->title }}"
                                        data-instructions="{{ $assignment->instructions }}"
                                        data-due="{{ $assignment->due_date ? $assignment->due_date->format('d/m/Y H:i') : '' }}"
                                        data-raw-due="{{ $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : '' }}"
                                        data-assignment-type="{{ $assignment->type ?? 'file' }}"
                                        data-status="{{ $submission ? 'submitted' : 'pending' }}"
                                        data-grade="{{ $submission->grade ?? '' }}"
                                        data-feedback="{{ $submission->feedback ?? '' }}"
                                        data-sub-id="{{ $submission ? $submission->id : '' }}"
                                        data-sub-time="{{ $submission ? $submission->formatSubmittedAt('H:i:s - d/m/Y') : '' }}"
                                        data-sub-file="{{ $submission && $submission->file_path ? route('assignments.submissions.file', $submission->id) : '' }}"
                                        data-text-answer='@json($submission?->text_answer ?? '')'>

                                        <div
                                            style="width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                                            background:{{ $submission ? '#dcfce7' : ($assignmentOverdue ? '#fee2e2' : '#fef3c7') }};">
                                            @if ($submission)
                                                <i class="fa-solid fa-check" style="font-size: 11px;color:#16a34a;"></i>
                                            @elseif ($assignmentOverdue)
                                                <i class="fa-solid fa-lock" style="font-size: 11px;color:#b91c1c;"></i>
                                            @else
                                                <i class="fa-solid fa-file-signature"
                                                    style="font-size: 11px;color:#d97706;"></i>
                                            @endif
                                        </div>

                                        <div style="min-width:0;">
                                            <div class="lesson-name-text fw-semibold"
                                                style="color:{{ $submission ? '#166534' : ($assignmentOverdue ? '#991b1b' : '#92400e') }};">
                                                {{ $assignment->title }}
                                            </div>
                                            @if ($isStudent)
                                                <div class="sidebar-status-row">
                                                    @if ($submission)
                                                        <span class="sidebar-status-pill done"><i
                                                                class="fa-solid fa-check"></i>Đã nộp</span>
                                                    @elseif ($assignmentOverdue)
                                                        <span class="sidebar-status-pill overdue"><i
                                                                class="fa-solid fa-lock"></i>Quá hạn</span>
                                                    @else
                                                        <span class="sidebar-status-pill assignment"><i
                                                                class="fa-solid fa-paper-plane"></i>Cần nộp</span>
                                                    @endif
                                                    <span
                                                        class="sidebar-status-pill pending">{{ $assignmentTypeLabel }}</span>
                                                    @if ($assignment->due_date && !$submission)
                                                        <span
                                                            class="sidebar-status-pill {{ $assignmentOverdue ? 'overdue' : 'pending' }}">
                                                            <i class="fa-solid fa-clock"></i>
                                                            {{ $assignmentOverdue ? 'Hết hạn' : 'Hạn ' . $assignment->due_date->format('d/m') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="lesson-dur-text mt-1">
                                                    <span class="badge bg-info text-dark"
                                                        style="font-size: 11px;">{{ $assignmentTypeLabel }}</span>
                                                    @if ($isManager)
                                                        <span
                                                            class="badge bg-{{ $assignment->status === 'published' ? 'success' : ($assignment->status === 'hidden' ? 'secondary' : 'warning text-dark') }}"
                                                            style="font-size: 11px;">
                                                            {{ match ($assignment->status ?? 'published') { 'draft' => 'Bản nháp', 'hidden' => 'Đang ẩn', 'archived' => 'Đã lưu trữ', default => 'Đã xuất bản' } }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </button>

                                    @if ($isManager)
                                        <div class="action-buttons d-flex pe-2 gap-1">
                                            <button type="button" class="btn-action btn-edit border-0"
                                                onclick="openEditAssignmentModal(this)"
                                                data-id="{{ $assignment->id }}"
                                                data-title='@json($assignment->title)'
                                                data-instructions='@json($assignment->instructions)'
                                                data-grading-rubric='@json($assignment->grading_rubric)'
                                                data-grading-scale="{{ $assignment->grading_scale ?? 10 }}"
                                                data-ai-enabled="{{ $assignment->ai_grading_enabled ? '1' : '0' }}"
                                                data-due="{{ $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : '' }}"
                                                data-lesson="{{ $lesson->id }}"
                                                data-type="{{ $assignment->type ?? 'file' }}"
                                                data-status="{{ $assignment->status ?? 'published' }}"
                                                data-available-from="{{ $assignment->available_from?->format('Y-m-d\TH:i') }}"
                                                title="Sửa bài tập" aria-label="Sửa bài tập {{ $assignment->title }}">
                                                <i class="fa-solid fa-edit"></i>
                                            </button>
                                            <form action="{{ route('assignments.destroy', $assignment->id) }}"
                                                method="POST" class="d-inline mb-0">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="btn-action btn-delete border-0 bg-transparent"
                                                    onclick="return confirm('Lưu trữ bài tập này?')" title="Xóa">
                                                    <i class="fa-solid fa-archive"></i>
                                                </button>
                                            </form>
                                            <button type="button"
                                                class="btn-action text-primary view-submissions-btn"
                                                style="background:#eff6ff;border:1px solid #bfdbfe;"
                                                data-id="{{ $assignment->id }}" data-bs-toggle="modal"
                                                data-bs-target="#viewSubmissionsModal" title="Chấm điểm"
                                                aria-label="Chấm bài {{ $assignment->title }}">
                                                <i class="fa-solid fa-users-gear"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                        @empty
                            <div class="course-empty-state">
                                <div class="course-empty-state__icon">
                                    <i class="fa-solid fa-file-lines"></i>
                                </div>
                                <div class="course-empty-state__title">Chưa có bài học</div>
                                <p class="course-empty-state__desc">Chương này chưa có nội dung học tập.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="course-empty-state">
            <div class="course-empty-state__icon">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <div class="course-empty-state__title">Chưa có nội dung</div>
            <p class="course-empty-state__desc">Khóa học chưa có chương hoặc bài học nào.</p>
        </div>
    @endforelse

    {{-- ── QUIZZES SECTION ── --}}
    @if ($course->quizzes->count() > 0)
        <div class="accordion-item" style="background:#faf8ff;">
            <div class="module-header-wrapper d-flex align-items-center" style="background:#faf8ff;">
                <button class="accordion-button collapsed flex-grow-1 shadow-none"
                    style="background:#faf8ff !important;color:#6f42c1;padding:0;" type="button"
                    data-bs-toggle="collapse" data-bs-target="#course-quizzes-collapse">
                    <div class="module-title-block ps-4">
                        <div class="d-flex align-items-center gap-2">
                            <div
                                style="width:20px;height:20px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa-solid fa-stopwatch" style="font-size: 11px;color:#7c3aed;"></i>
                            </div>
                            <span class="module-title-text" style="color:#6f42c1;">Bài kiểm tra</span>
                        </div>
                        <span class="module-meta" style="padding-left:28px;color:#7c3aed;">
                            {{ $course->quizzes->count() }} bài kiểm tra
                        </span>
                    </div>
                </button>
                @if ($isManager && $course->archivedQuizzes->isNotEmpty())
                    <a href="{{ route('quizzes.archived', $course) }}"
                        class="me-3 d-inline-flex align-items-center gap-1 text-decoration-none"
                        style="padding:5px 9px;border:1px solid #ddd6fe;border-radius:8px;background:#fff;color:#6b7280;font-size:11px;font-weight:700;white-space:nowrap;"
                        title="Mở kho lưu trữ bài kiểm tra">
                        <i class="fa-solid fa-box-archive"></i>
                        Kho lưu trữ <span class="badge rounded-pill" style="background:#ede9fe;color:#6d28d9;">{{ $course->archivedQuizzes->count() }}</span>
                    </a>
                @endif
            </div>

            <div id="course-quizzes-collapse" class="accordion-collapse collapse" data-bs-parent="#courseAccordion">
                <div class="accordion-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach ($course->quizzes as $quiz)
                            @php
                                $attempt =
                                    $isStudent && isset($userQuizAttempts[$quiz->id])
                                        ? $userQuizAttempts[$quiz->id]
                                        : null;
                                $resultReleased = $attempt?->resultIsReleased() ?? false;
                                $canRetry = $isStudent && ($userQuizCanRetry[$quiz->id] ?? false);
                                $attemptCompleted = (bool) $attempt?->completed_at;
                                $attemptState = match (true) {
                                    ! $attempt => 'not_started',
                                    $attempt->isInProgress() => 'in_progress',
                                    ! $attemptCompleted => 'not_started',
                                    $resultReleased => 'released',
                                    $attempt->status === 'pending_grading' => 'pending_grading',
                                    $attempt->status === 'graded' => 'graded_private',
                                    default => 'submitted_private',
                                };
                            @endphp
                            <div class="list-group-item border-0 px-0 py-0 quiz-item-wrapper {{ $attemptCompleted ? 'completed' : '' }} d-flex align-items-center justify-content-between shadow-none"
                                style="min-width:0;">

                                <button type="button"
                                    class="quiz-item course-outline-trigger text-decoration-none flex-grow-1 d-flex align-items-center gap-2 py-2 pe-2 ps-4 border-0 bg-transparent text-start"
                                    style="min-width:0;" data-id="{{ $quiz->id }}"
                                    data-title="{{ $quiz->title }}" data-duration="{{ $quiz->time_limit }}"
                                    data-status="{{ $attemptState }}"
                                    data-score="{{ $attempt && $resultReleased ? $attempt->score : '' }}"
                                    data-result-released="{{ $resultReleased ? '1' : '0' }}"
                                    data-can-retry="{{ $canRetry ? '1' : '0' }}"
                                    data-attempt-id="{{ $attempt ? $attempt->id : '' }}">

                                    <div
                                        style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                                        background:{{ $attemptCompleted ? '#dcfce7' : '#ede9fe' }};">
                                        @if ($attemptCompleted)
                                            <i class="fa-solid fa-check" style="font-size:11px;color:#16a34a;"></i>
                                        @else
                                            <i class="fa-solid fa-stopwatch" style="font-size: 11px;color:#7c3aed;"></i>
                                        @endif
                                    </div>

                                    <div style="min-width:0;">
                                        <div class="lesson-name-text fw-semibold"
                                            style="color:{{ $attemptCompleted ? '#166534' : '#5b21b6' }};">
                                            {{ $quiz->title }}
                                        </div>
                                        @if ($isStudent)
                                            <div class="sidebar-status-row">
                                                @if ($attemptCompleted)
                                                    <span class="sidebar-status-pill done"><i
                                                            class="fa-solid fa-check"></i>Đã làm</span>
                                                    @if($attempt?->status === 'pending_grading')
                                                        <span class="sidebar-status-pill pending"><i class="fa-solid fa-hourglass-half"></i>Chờ giáo viên chấm</span>
                                                    @elseif($resultReleased)
                                                        <span class="sidebar-status-pill pending">{{ $attempt->score }}/10 điểm</span>
                                                    @elseif($attemptState === 'graded_private')
                                                        <span class="sidebar-status-pill pending"><i class="fa-solid fa-lock"></i>Đã chấm · Chờ công bố</span>
                                                    @else
                                                        <span class="sidebar-status-pill pending"><i class="fa-solid fa-lock"></i>Chờ công bố</span>
                                                    @endif
                                                @elseif($attempt?->status === 'in_progress')
                                                    <span class="sidebar-status-pill quiz"><i class="fa-solid fa-pen"></i>Đang làm</span>
                                                    <span class="sidebar-status-pill pending">Tiếp tục bài thi</span>
                                                @else
                                                    <span class="sidebar-status-pill quiz"><i
                                                            class="fa-solid fa-stopwatch"></i>Cần làm</span>
                                                    <span class="sidebar-status-pill pending"><i
                                                            class="fa-regular fa-clock"></i>{{ $quiz->time_limit }}
                                                        phút</span>
                                                @endif
                                            </div>
                                        @endif
                                        @if ($isManager)
                                            <div
                                                class="lesson-dur-text mt-1 d-flex align-items-center gap-1 flex-wrap">
                                                <span
                                                    class="badge bg-{{ $quiz->status === 'published' ? 'success' : ($quiz->status === 'hidden' ? 'secondary' : 'warning text-dark') }}"
                                                    style="font-size: 11px;">
                                                    {{ match ($quiz->status ?? 'published') { 'draft' => 'Bản nháp', 'hidden' => 'Đang ẩn', 'archived' => 'Đã lưu trữ', default => 'Đã xuất bản' } }}
                                                </span>
                                                @if ($quiz->available_from && $quiz->available_from->gt($currentTime))
                                                    <span style="font-size: 11px;color:#6b7280;">Mở:
                                                        {{ $quiz->available_from->format('d/m H:i') }}</span>
                                                @endif
                                                <span style="font-size: 11px;color:#9ca3af;"><i
                                                        class="fa-regular fa-clock me-1"></i>{{ $quiz->time_limit }}
                                                    phút</span>
                                            </div>
                                        @endif
                                    </div>
                                </button>

                                @if ($isManager)
                                    <div class="action-buttons d-flex pe-2 gap-1">
                                        <a href="{{ route('quizzes.sessions.index', $quiz) }}"
                                            class="btn-action text-white d-flex align-items-center px-2"
                                            style="background:#0d6efd;width:auto;text-decoration:none;border-radius:6px;font-size:11px;font-weight:700;gap:3px;"
                                            title="Quản lý ca thi">
                                            <i class="fa-solid fa-calendar-days"></i> Ca thi
                                        </a>
                                        <a href="{{ route('quizzes.submissions', $quiz->id) }}"
                                            class="btn-action text-white d-flex align-items-center px-2"
                                            style="background:#198754;width:auto;text-decoration:none;border-radius:6px;font-size: 11px;font-weight:700;gap:3px;"
                                            title="Xem điểm">
                                            <i class="fa-solid fa-chart-bar"></i> Điểm
                                        </a>
                                        <a href="{{ route('quizzes.show', $quiz->id) }}"
                                            class="btn-action text-white" style="background:#7c3aed;"
                                            title="Soạn câu hỏi">
                                            <i class="fa-solid fa-list-ul"></i>
                                        </a>
                                        <form action="{{ route('quizzes.destroy', $quiz->id) }}" method="POST"
                                            class="d-inline mb-0">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="btn-action btn-delete border-0 bg-transparent"
                                                onclick="return confirm('Lưu trữ bài kiểm tra này?')" title="Xóa">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="course-outline-no-results d-none" data-outline-empty role="status" aria-live="polite">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <strong>Không tìm thấy nội dung</strong>
        <span>Thử nhập tên chương hoặc bài học khác.</span>
    </div>

</div>
