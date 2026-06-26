<div class="accordion accordion-flush" id="courseAccordion">
    @php
        $isStudent = auth()->user()->role === 'student';
        $isManager = auth()->id() === $course->teacher_id || auth()->user()->role === 'admin';
        $firstPendingQuiz = $isStudent
            ? $course->quizzes->first(fn($quiz) => !isset($userQuizAttempts[$quiz->id]))
            : $course->quizzes->first();
        $currentTime = now();
    @endphp

    @forelse ($course->modules as $moduleIndex => $module)
        @php
            $lessonCount = $module->lessons->count();
            $completedInModule = $module->lessons
                ->filter(fn($l) => in_array($l->id, $completedLessonIds ?? []))
                ->count();
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
            $moduleAllDone = $isStudent && $lessonCount > 0 && $completedInModule === $lessonCount;
        @endphp

        <div class="accordion-item module-sortable-item" data-module-id="{{ $module->id }}">

            {{-- Module header --}}
            <div class="module-header-wrapper d-flex align-items-center position-relative">
                @if ($isManager)
                    <i class="fas fa-grip-vertical drag-handle ms-2" title="Kéo để sắp xếp chương"></i>
                @endif

                <button class="accordion-button {{ $moduleIndex == 0 ? '' : 'collapsed' }} flex-grow-1 shadow-none"
                    type="button" data-bs-toggle="collapse" data-bs-target="#module-{{ $module->id }}">
                    <div class="module-title-block" style="padding-left:{{ $isManager ? '8px' : '16px' }};">
                        <div class="d-flex align-items-center gap-2">
                            {{-- Module progress ring (student only) --}}
                            @if ($isStudent && $lessonCount > 0)
                                @php $pct = round($completedInModule / $lessonCount * 100); @endphp
                                <div style="position:relative;width:22px;height:22px;flex-shrink:0;">
                                    <svg width="22" height="22" viewBox="0 0 22 22"
                                        style="transform:rotate(-90deg);">
                                        <circle cx="11" cy="11" r="9" fill="none" stroke="#e5e7eb"
                                            stroke-width="2.5" />
                                        <circle cx="11" cy="11" r="9" fill="none"
                                            stroke="{{ $moduleAllDone ? '#22c55e' : '#3b82f6' }}" stroke-width="2.5"
                                            stroke-dasharray="{{ round(2 * 3.14159 * 9, 1) }}"
                                            stroke-dashoffset="{{ round((1 - $pct / 100) * 2 * 3.14159 * 9, 1) }}"
                                            stroke-linecap="round" />
                                    </svg>
                                    @if ($moduleAllDone)
                                        <i class="fas fa-check"
                                            style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:8px;color:#16a34a;"></i>
                                    @endif
                                </div>
                            @else
                                <div
                                    style="width:20px;height:20px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <span
                                        style="font-size:10px;font-weight:800;color:#2563eb;">{{ $moduleIndex + 1 }}</span>
                                </div>
                            @endif

                            <span class="module-title-text">{{ $module->title }}</span>
                        </div>
                        <span class="module-meta"
                            style="padding-left:{{ $isStudent && $lessonCount > 0 ? '28px' : '26px' }};">
                            {{ $completedInModule }}/{{ $lessonCount }}
                            bài{{ $durationStr ? ' · ' . $durationStr : '' }}
                        </span>
                    </div>
                </button>

                @if ($isManager)
                    <div class="action-buttons d-flex align-items-center pe-2">
                        <a href="javascript:void(0)" class="btn-action btn-edit edit-module-btn"
                            data-id="{{ $module->id }}" data-title="{{ $module->title }}" data-bs-toggle="modal"
                            data-bs-target="#editModuleModal" title="Sửa chương">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('modules.destroy', $module->id) }}" method="POST"
                            class="d-inline mb-0">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-action btn-delete border-0 bg-transparent"
                                onclick="return confirm('Lưu trữ chương này? Bài học và bài tập liên quan sẽ được ẩn nhưng dữ liệu vẫn được giữ lại.')"
                                title="Xóa chương">
                                <i class="fas fa-trash"></i>
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
                                $isCompleted = in_array($lesson->id, $completedLessonIds ?? []);
                                $isVideo = !empty($lesson->video_url);
                                $lessonAssignments = $lesson->assignments ?? collect();
                                $lessonNextAssignment = $isStudent
                                    ? $lessonAssignments->first(
                                        fn($a) => !isset($userSubmissions[$a->id]) &&
                                            !($a->due_date && $a->due_date->isPast()),
                                    )
                                    : $lessonAssignments->first();
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
                            <div class="list-group-item border-0 px-0 py-0 lesson-item-wrapper {{ $isCompleted ? 'completed-lesson' : '' }} d-flex align-items-center justify-content-between shadow-none"
                                data-lesson-id="{{ $lesson->id }}" style="min-width:0;">

                                @if ($isManager)
                                    <i class="fas fa-grip-vertical drag-handle ms-2" title="Kéo để sắp xếp bài học"></i>
                                @endif

                                <a href="javascript:void(0)"
                                    class="lesson-item text-decoration-none flex-grow-1 d-flex align-items-center gap-2 py-2 pe-2"
                                    style="min-width:0;padding-left:{{ $isManager ? '8px' : '16px' }};"
                                    data-id="{{ $lesson->id }}" data-content="{{ $lesson->content }}"
                                    data-title="{{ $lesson->title }}" data-video="{{ $lesson->video_url }}"
                                    data-module="{{ $module->id }}"
                                    data-attachment="{{ $lesson->attachment ? route('lessons.attachment', $lesson->id) : '' }}"
                                    data-attachment-name="{{ $lesson->attachment_original_name ?: ($lesson->attachment ? basename($lesson->attachment) : '') }}"
                                    data-next-assignment-id="{{ $lessonNextAssignment?->id ?? '' }}"
                                    data-next-assignment-title="{{ $lessonNextAssignment?->title ?? '' }}"
                                    data-next-quiz-id="{{ $firstPendingQuiz?->id ?? '' }}"
                                    data-next-quiz-title="{{ $firstPendingQuiz?->title ?? '' }}">

                                    {{-- Status icon --}}
                                    <div
                                        style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                                        background:{{ $isCompleted ? '#dcfce7' : ($isVideo ? '#eff6ff' : '#f3f4f6') }};">
                                        @if ($isCompleted)
                                            <i class="fas fa-check" style="font-size:11px;color:#16a34a;"
                                                id="icon-lesson-{{ $lesson->id }}"></i>
                                        @elseif ($isVideo)
                                            <i class="fas fa-play" style="font-size:10px;color:#2563eb;"
                                                id="icon-lesson-{{ $lesson->id }}"></i>
                                        @else
                                            <i class="fas fa-file-alt" style="font-size:11px;color:#6b7280;"
                                                id="icon-lesson-{{ $lesson->id }}"></i>
                                        @endif
                                    </div>

                                    <div style="min-width:0;flex:1;">
                                        <div class="lesson-name-text {{ $isCompleted ? 'text-decoration-line-through' : '' }}"
                                            style="{{ $isCompleted ? 'color:#6b7280;' : '' }}">
                                            {{ $moduleIndex + 1 }}.{{ $lessonIndex + 1 }} {{ $lesson->title }}
                                        </div>

                                        @if ($isStudent)
                                            <div class="sidebar-status-row">
                                                @if ($isCompleted)
                                                    <span class="sidebar-status-pill done"><i
                                                            class="fas fa-check"></i>Đã xong</span>
                                                @else
                                                    <span class="sidebar-status-pill pending"><i
                                                            class="far fa-circle"></i>Chưa học</span>
                                                @endif
                                                @if ($pendingAssignmentCount > 0)
                                                    <span class="sidebar-status-pill assignment">
                                                        <i
                                                            class="fas fa-file-signature"></i>{{ $pendingAssignmentCount }}
                                                        bài tập
                                                    </span>
                                                @endif
                                                @if ($durLabel)
                                                    <span class="sidebar-status-pill pending">
                                                        <i class="far fa-clock"></i>{{ $durLabel }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($isManager)
                                            <div
                                                class="lesson-dur-text d-flex align-items-center gap-1 flex-wrap mt-1">
                                                <span
                                                    class="badge bg-{{ $lesson->status === 'published' ? 'success' : ($lesson->status === 'hidden' ? 'secondary' : 'warning text-dark') }}"
                                                    style="font-size:10px;">
                                                    {{ strtoupper($lesson->status ?? 'published') }}
                                                </span>
                                                @if ($lesson->available_from && $lesson->available_from->gt($currentTime))
                                                    <span style="font-size:10px;color:#6b7280;">Mở:
                                                        {{ $lesson->available_from->format('d/m H:i') }}</span>
                                                @endif
                                                @if ($durLabel)
                                                    <span style="font-size:10px;color:#9ca3af;"><i
                                                            class="far fa-clock me-1"></i>{{ $durLabel }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </a>

                                @if ($isManager)
                                    <div class="action-buttons d-flex pe-2">
                                        <a href="javascript:void(0)" class="btn-action btn-edit edit-lesson-btn"
                                            data-id="{{ $lesson->id }}" data-title="{{ $lesson->title }}"
                                            data-content="{{ $lesson->content }}"
                                            data-video="{{ $lesson->video_url }}" data-module="{{ $module->id }}"
                                            data-status="{{ $lesson->status ?? 'published' }}"
                                            data-available-from="{{ $lesson->available_from?->format('Y-m-d\TH:i') }}"
                                            data-bs-toggle="modal" data-bs-target="#editLessonModal"
                                            title="Sửa bài học">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('lessons.destroy', $lesson->id) }}" method="POST"
                                            class="d-inline mb-0">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="btn-action btn-delete border-0 bg-transparent"
                                                onclick="return confirm('Lưu trữ bài học này?')" title="Xóa bài học">
                                                <i class="fas fa-times"></i>
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

                                    <a href="javascript:void(0)"
                                        class="assignment-item text-decoration-none flex-grow-1 d-flex align-items-center gap-2 py-2 pe-2 ps-5"
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
                                                <i class="fas fa-check" style="font-size:10px;color:#16a34a;"></i>
                                            @elseif ($assignmentOverdue)
                                                <i class="fas fa-lock" style="font-size:10px;color:#b91c1c;"></i>
                                            @else
                                                <i class="fas fa-file-signature"
                                                    style="font-size:10px;color:#d97706;"></i>
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
                                                                class="fas fa-check"></i>Đã nộp</span>
                                                    @elseif ($assignmentOverdue)
                                                        <span class="sidebar-status-pill overdue"><i
                                                                class="fas fa-lock"></i>Quá hạn</span>
                                                    @else
                                                        <span class="sidebar-status-pill assignment"><i
                                                                class="fas fa-paper-plane"></i>Cần nộp</span>
                                                    @endif
                                                    <span
                                                        class="sidebar-status-pill pending">{{ $assignmentTypeLabel }}</span>
                                                    @if ($assignment->due_date && !$submission)
                                                        <span
                                                            class="sidebar-status-pill {{ $assignmentOverdue ? 'overdue' : 'pending' }}">
                                                            <i class="fas fa-clock"></i>
                                                            {{ $assignmentOverdue ? 'Hết hạn' : 'Hạn ' . $assignment->due_date->format('d/m') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="lesson-dur-text mt-1">
                                                    <span class="badge bg-info text-dark"
                                                        style="font-size:10px;">{{ $assignmentTypeLabel }}</span>
                                                    @if ($isManager)
                                                        <span
                                                            class="badge bg-{{ $assignment->status === 'published' ? 'success' : ($assignment->status === 'hidden' ? 'secondary' : 'warning text-dark') }}"
                                                            style="font-size:10px;">
                                                            {{ strtoupper($assignment->status ?? 'published') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </a>

                                    @if ($isManager)
                                        <div class="action-buttons d-flex pe-2 gap-1">
                                            <a href="javascript:void(0)" class="btn-action btn-edit"
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
                                                title="Sửa bài tập">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('assignments.destroy', $assignment->id) }}"
                                                method="POST" class="d-inline mb-0">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="btn-action btn-delete border-0 bg-transparent"
                                                    onclick="return confirm('Lưu trữ bài tập này?')" title="Xóa">
                                                    <i class="fas fa-archive"></i>
                                                </button>
                                            </form>
                                            <a href="javascript:void(0)"
                                                class="btn-action text-primary view-submissions-btn"
                                                style="background:#eff6ff;border:1px solid #bfdbfe;"
                                                data-id="{{ $assignment->id }}" data-bs-toggle="modal"
                                                data-bs-target="#viewSubmissionsModal" title="Chấm điểm">
                                                <i class="fas fa-users-cog"></i>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                        @empty
                            <div class="py-3 px-4 text-center">
                                <span class="text-muted" style="font-size:12px;font-style:italic;">Chưa có bài
                                    học</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="p-5 text-center">
            <div
                style="width:48px;height:48px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="fas fa-folder-open" style="color:#9ca3af;font-size:18px;"></i>
            </div>
            <p class="text-muted mb-0" style="font-size:13px;">Chưa có nội dung</p>
        </div>
    @endforelse

    {{-- ── QUIZZES SECTION ── --}}
    @if ($course->quizzes->count() > 0)
        @php
            $doneQuizCount = $isStudent
                ? $course->quizzes->filter(fn($q) => isset($userQuizAttempts[$q->id]))->count()
                : 0;
        @endphp
        <div class="accordion-item" style="background:#faf8ff;">
            <div class="module-header-wrapper d-flex align-items-center" style="background:#faf8ff;">
                <button class="accordion-button collapsed flex-grow-1 shadow-none"
                    style="background:#faf8ff !important;color:#6f42c1;padding:0;" type="button"
                    data-bs-toggle="collapse" data-bs-target="#course-quizzes-collapse">
                    <div class="module-title-block ps-4">
                        <div class="d-flex align-items-center gap-2">
                            <div
                                style="width:20px;height:20px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-stopwatch" style="font-size:9px;color:#7c3aed;"></i>
                            </div>
                            <span class="module-title-text" style="color:#6f42c1;">Bài kiểm tra</span>
                        </div>
                        <span class="module-meta" style="padding-left:28px;color:#7c3aed;">
                            @if ($isStudent)
                                {{ $doneQuizCount }}/{{ $course->quizzes->count() }}
                                đề@else{{ $course->quizzes->count() }} đề
                            @endif
                        </span>
                    </div>
                </button>
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
                            @endphp
                            <div class="list-group-item border-0 px-0 py-0 quiz-item-wrapper {{ $attempt ? 'completed' : '' }} d-flex align-items-center justify-content-between shadow-none"
                                style="min-width:0;">

                                <a href="javascript:void(0)"
                                    class="quiz-item text-decoration-none flex-grow-1 d-flex align-items-center gap-2 py-2 pe-2 ps-4"
                                    style="min-width:0;" data-id="{{ $quiz->id }}"
                                    data-title="{{ $quiz->title }}" data-duration="{{ $quiz->time_limit }}"
                                    data-status="{{ $attempt ? 'completed' : 'pending' }}"
                                    data-score="{{ $attempt ? $attempt->score : '' }}"
                                    data-attempt-id="{{ $attempt ? $attempt->id : '' }}">

                                    <div
                                        style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                                        background:{{ $attempt ? '#dcfce7' : '#ede9fe' }};">
                                        @if ($attempt)
                                            <i class="fas fa-check" style="font-size:11px;color:#16a34a;"></i>
                                        @else
                                            <i class="fas fa-stopwatch" style="font-size:10px;color:#7c3aed;"></i>
                                        @endif
                                    </div>

                                    <div style="min-width:0;">
                                        <div class="lesson-name-text fw-semibold"
                                            style="color:{{ $attempt ? '#166534' : '#5b21b6' }};">
                                            {{ $quiz->title }}
                                        </div>
                                        @if ($isStudent)
                                            <div class="sidebar-status-row">
                                                @if ($attempt)
                                                    <span class="sidebar-status-pill done"><i
                                                            class="fas fa-check"></i>Đã làm</span>
                                                    <span
                                                        class="sidebar-status-pill pending">{{ $attempt->score }}/10
                                                        điểm</span>
                                                @else
                                                    <span class="sidebar-status-pill quiz"><i
                                                            class="fas fa-stopwatch"></i>Cần làm</span>
                                                    <span class="sidebar-status-pill pending"><i
                                                            class="far fa-clock"></i>{{ $quiz->time_limit }}
                                                        phút</span>
                                                @endif
                                            </div>
                                        @endif
                                        @if ($isManager)
                                            <div
                                                class="lesson-dur-text mt-1 d-flex align-items-center gap-1 flex-wrap">
                                                <span
                                                    class="badge bg-{{ $quiz->status === 'published' ? 'success' : ($quiz->status === 'hidden' ? 'secondary' : 'warning text-dark') }}"
                                                    style="font-size:10px;">
                                                    {{ strtoupper($quiz->status ?? 'published') }}
                                                </span>
                                                @if ($quiz->available_from && $quiz->available_from->gt($currentTime))
                                                    <span style="font-size:10px;color:#6b7280;">Mở:
                                                        {{ $quiz->available_from->format('d/m H:i') }}</span>
                                                @endif
                                                <span style="font-size:10px;color:#9ca3af;"><i
                                                        class="far fa-clock me-1"></i>{{ $quiz->time_limit }}
                                                    phút</span>
                                            </div>
                                        @endif
                                    </div>
                                </a>

                                @if ($isManager)
                                    <div class="action-buttons d-flex pe-2 gap-1">
                                        <a href="{{ route('quizzes.submissions', $quiz->id) }}"
                                            class="btn-action text-white d-flex align-items-center px-2"
                                            style="background:#198754;width:auto;text-decoration:none;border-radius:6px;font-size:10px;font-weight:700;gap:3px;"
                                            title="Xem điểm">
                                            <i class="fas fa-chart-bar"></i> Điểm
                                        </a>
                                        <a href="{{ route('quizzes.show', $quiz->id) }}"
                                            class="btn-action text-white" style="background:#7c3aed;"
                                            title="Soạn câu hỏi">
                                            <i class="fas fa-list-ul"></i>
                                        </a>
                                        <form action="{{ route('quizzes.destroy', $quiz->id) }}" method="POST"
                                            class="d-inline mb-0">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="btn-action btn-delete border-0 bg-transparent"
                                                onclick="return confirm('Lưu trữ bài kiểm tra này?')" title="Xóa">
                                                <i class="fas fa-trash"></i>
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

</div>
