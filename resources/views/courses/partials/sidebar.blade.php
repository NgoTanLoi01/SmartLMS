<div class="accordion accordion-flush" id="courseAccordion">

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
        @endphp

        <div class="accordion-item">
            {{-- Module header --}}
            <div class="module-header-wrapper d-flex align-items-center position-relative">
                <button class="accordion-button {{ $moduleIndex == 0 ? '' : 'collapsed' }} flex-grow-1 shadow-none py-0"
                    type="button" data-bs-toggle="collapse" data-bs-target="#module-{{ $module->id }}">
                    <div class="module-title-block py-3">
                        <span class="module-title-text">{{ $moduleIndex + 1 }}. {{ $module->title }}</span>
                        <span
                            class="module-meta">{{ $completedInModule }}/{{ $lessonCount }}{{ $durationStr ? ' · ' . $durationStr : '' }}</span>
                    </div>
                </button>

                @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                    <div class="action-buttons  d-flex align-items-center">
                        <a href="javascript:void(0)" class="btn-action btn-edit edit-module-btn"
                            data-id="{{ $module->id }}" data-title="{{ $module->title }}" data-bs-toggle="modal"
                            data-bs-target="#editModuleModal">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('modules.destroy', $module->id) }}" method="POST" class="d-inline mb-0">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-action btn-delete border-0 bg-transparent"
                                onclick="return confirm('Xóa chương này?')">
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
                    <div class="list-group list-group-flush">

                        @forelse ($module->lessons as $lessonIndex => $lesson)
                            @php
                                $isCompleted = in_array($lesson->id, $completedLessonIds ?? []);
                                $isVideo = !empty($lesson->video_url);
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

                            <div class="list-group-item border-0 px-0 py-0 lesson-item-wrapper d-flex align-items-center justify-content-between shadow-none"
                                style="min-width:0;">
                                <a href="javascript:void(0)"
                                    class="lesson-item text-decoration-none flex-grow-1 d-flex align-items-center gap-2 px-3 ps-4 py-2"
                                    style="min-width:0;" data-id="{{ $lesson->id }}"
                                    data-content="{{ $lesson->content }}" data-title="{{ $lesson->title }}"
                                    data-video="{{ $lesson->video_url }}" data-module="{{ $module->id }}"
                                    data-attachment="{{ $lesson->attachment ? asset('storage/' . $lesson->attachment) : '' }}"
                                    data-attachment-name="{{ $lesson->attachment ? basename($lesson->attachment) : '' }}">

                                    {{-- Icon trạng thái --}}
                                    @if ($isCompleted)
                                        <i class="fas fa-check-circle lesson-icon-done flex-shrink-0"
                                            id="icon-lesson-{{ $lesson->id }}"></i>
                                    @elseif ($isVideo)
                                        <i class="fas fa-play-circle lesson-icon-video flex-shrink-0"
                                            id="icon-lesson-{{ $lesson->id }}"></i>
                                    @else
                                        <i class="fas fa-file-alt lesson-icon-doc flex-shrink-0"
                                            id="icon-lesson-{{ $lesson->id }}"></i>
                                    @endif

                                    <div style="min-width:0; flex:1;">
                                        <div class="lesson-name-text">
                                            {{ $moduleIndex + 1 }}.{{ $lessonIndex + 1 }} {{ $lesson->title }}
                                        </div>
                                        @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                            <div class="lesson-dur-text">
                                                <span class="badge bg-{{ $lesson->status === 'published' ? 'success' : ($lesson->status === 'hidden' ? 'secondary' : 'warning text-dark') }}">
                                                    {{ strtoupper($lesson->status ?? 'published') }}
                                                </span>
                                                @if ($lesson->available_from)
                                                    <span class="ms-1">Mở: {{ $lesson->available_from->format('d/m/Y H:i') }}</span>
                                                @endif
                                            </div>
                                        @endif
                                        @if ($durLabel)
                                            <div class="lesson-dur-text">
                                                <i class="far fa-clock me-1"></i>{{ $durLabel }}
                                            </div>
                                        @endif
                                    </div>
                                </a>

                                @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                    <div class="action-buttons d-flex me-2">
                                        <a href="javascript:void(0)" class="btn-action btn-edit edit-lesson-btn"
                                            data-id="{{ $lesson->id }}" data-title="{{ $lesson->title }}"
                                            data-content="{{ $lesson->content }}"
                                            data-video="{{ $lesson->video_url }}" data-module="{{ $module->id }}"
                                            data-status="{{ $lesson->status ?? 'published' }}"
                                            data-available-from="{{ $lesson->available_from?->format('Y-m-d\TH:i') }}"
                                            data-bs-toggle="modal" data-bs-target="#editLessonModal">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('lessons.destroy', $lesson->id) }}" method="POST"
                                            class="d-inline mb-0">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-action btn-delete border-0 bg-transparent"
                                                onclick="return confirm('Xóa bài này?')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            {{-- Bài tập --}}
                            @foreach ($lesson->assignments as $assignment)
                                @php
                                    $submission =
                                        auth()->user()->role === 'student' && isset($userSubmissions[$assignment->id])
                                            ? $userSubmissions[$assignment->id]
                                            : null;
                                @endphp
                                <div class="list-group-item border-0 px-0 py-0 assignment-item-wrapper d-flex align-items-center justify-content-between shadow-none bg-light"
                                    style="min-width:0;">
                                    <a href="javascript:void(0)"
                                        class="assignment-item text-decoration-none flex-grow-1 d-flex align-items-center gap-2 px-3 ps-5 py-2"
                                        style="min-width:0;" data-id="{{ $assignment->id }}"
                                        data-title="{{ $assignment->title }}"
                                        data-instructions="{{ $assignment->instructions }}"
                                        data-due="{{ $assignment->due_date ? $assignment->due_date->format('d/m/Y H:i') : '' }}"
                                        data-raw-due="{{ $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : '' }}"
                                        data-status="{{ $submission ? 'submitted' : 'pending' }}"
                                        data-grade="{{ $submission->grade ?? '' }}"
                                        data-feedback="{{ $submission->feedback ?? '' }}"
                                        data-sub-id="{{ $submission ? $submission->id : '' }}"
                                        data-sub-time="{{ $submission ? $submission->submitted_at->format('H:i - d/m/Y') : '' }}"
                                        data-sub-file="{{ $submission ? asset('storage/' . $submission->file_path) : '' }}">
                                        <i
                                            class="{{ $submission ? 'fas fa-check-circle lesson-icon-done' : 'fas fa-file-signature lesson-icon-assign' }} flex-shrink-0"></i>
                                        <div style="min-width:0;">
                                            <div class="lesson-name-text fw-medium">{{ $assignment->title }}</div>
                                            @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                                <div class="lesson-dur-text">
                                                    <span class="badge bg-{{ $assignment->status === 'published' ? 'success' : ($assignment->status === 'hidden' ? 'secondary' : 'warning text-dark') }}">
                                                        {{ strtoupper($assignment->status ?? 'published') }}
                                                    </span>
                                                    @if ($assignment->available_from)
                                                        <span class="ms-1">Mở: {{ $assignment->available_from->format('d/m/Y H:i') }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </a>

                                    @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                        <div class="action-buttons d-flex me-2 gap-1">
                                            <a href="javascript:void(0)" class="btn-action btn-edit"
                                                onclick="openEditAssignmentModal(this)" data-id="{{ $assignment->id }}"
                                                data-title='@json($assignment->title)'
                                                data-instructions='@json($assignment->instructions)'
                                                data-due="{{ $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : '' }}"
                                                data-lesson="{{ $lesson->id }}"
                                                data-status="{{ $assignment->status ?? 'published' }}"
                                                data-available-from="{{ $assignment->available_from?->format('Y-m-d\TH:i') }}">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('assignments.destroy', $assignment->id) }}"
                                                method="POST" class="d-inline mb-0">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="btn-action btn-delete border-0 bg-transparent"
                                                    onclick="return confirm('Xóa bài tập này?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <a href="javascript:void(0)"
                                                class="btn-action text-primary view-submissions-btn border bg-white shadow-sm"
                                                data-id="{{ $assignment->id }}" data-bs-toggle="modal"
                                                data-bs-target="#viewSubmissionsModal" title="Chấm điểm">
                                                <i class="fas fa-users-cog"></i>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                        @empty
                            <div class="py-2 px-4 text-muted small fst-italic">Trống</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="p-4 text-center text-muted small">Chưa có nội dung.</div>
    @endforelse

    {{-- Quizzes --}}
    @if ($course->quizzes->count() > 0)
        <div class="accordion-item">
            <div class="module-header-wrapper d-flex align-items-center">
                <button class="accordion-button collapsed flex-grow-1 shadow-none py-0 bg-transparent"
                    style="color:#6f42c1;" type="button" data-bs-toggle="collapse"
                    data-bs-target="#course-quizzes-collapse">
                    <div class="module-title-block py-3">
                        <span class="module-title-text" style="color:#6f42c1;">
                            <i class="fas fa-clipboard-list me-2"></i>Bài kiểm tra
                        </span>
                        <span class="module-meta">{{ $course->quizzes->count() }} đề</span>
                    </div>
                </button>
            </div>
            <div id="course-quizzes-collapse" class="accordion-collapse collapse" data-bs-parent="#courseAccordion">
                <div class="accordion-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach ($course->quizzes as $quiz)
                            @php
                                $attempt =
                                    auth()->user()->role === 'student' && isset($userQuizAttempts[$quiz->id])
                                        ? $userQuizAttempts[$quiz->id]
                                        : null;
                            @endphp
                            <div class="list-group-item border-0 px-0 py-0 quiz-item-wrapper d-flex align-items-center justify-content-between shadow-none bg-white"
                                style="min-width:0;">
                                <a href="javascript:void(0)"
                                    class="quiz-item text-decoration-none flex-grow-1 d-flex align-items-center gap-2 px-3 ps-4 py-2"
                                    style="min-width:0;" data-id="{{ $quiz->id }}"
                                    data-title="{{ $quiz->title }}" data-duration="{{ $quiz->time_limit }}"
                                    data-status="{{ $attempt ? 'completed' : 'pending' }}"
                                    data-score="{{ $attempt ? $attempt->score : '' }}"
                                    data-attempt-id="{{ $attempt ? $attempt->id : '' }}">
                                    <i class="{{ $attempt ? 'fas fa-check-circle lesson-icon-done' : 'fas fa-stopwatch' }} flex-shrink-0"
                                        style="{{ $attempt ? '' : 'color:#6f42c1;' }}"></i>
                                    <div style="min-width:0;">
                                        <div class="lesson-name-text"
                                            style="{{ $attempt ? 'color:#198754;' : 'color:#6f42c1;' }}">
                                            {{ $quiz->title }}
                                        </div>
                                        @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                            <div class="lesson-dur-text">
                                                <span class="badge bg-{{ $quiz->status === 'published' ? 'success' : ($quiz->status === 'hidden' ? 'secondary' : 'warning text-dark') }}">
                                                    {{ strtoupper($quiz->status ?? 'published') }}
                                                </span>
                                                @if ($quiz->available_from)
                                                    <span class="ms-1">Mở: {{ $quiz->available_from->format('d/m/Y H:i') }}</span>
                                                @endif
                                            </div>
                                        @endif
                                        <div class="lesson-dur-text"><i
                                                class="far fa-clock me-1"></i>{{ $quiz->time_limit }} phút</div>
                                    </div>
                                </a>

                                @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                    <div class="action-buttons d-flex me-2 gap-1">
                                        <a href="{{ route('quizzes.submissions', $quiz->id) }}"
                                            class="btn-action text-white px-2 d-flex align-items-center"
                                            style="background:#198754;width:auto;text-decoration:none;border-radius:6px;font-size:11px;font-weight:600;gap:3px;">
                                            <i class="fas fa-chart-bar"></i> Điểm
                                        </a>
                                        <a href="{{ route('quizzes.show', $quiz->id) }}"
                                            class="btn-action text-white" style="background:#6f42c1;">
                                            <i class="fas fa-list-ul"></i>
                                        </a>
                                        <form action="{{ route('quizzes.destroy', $quiz->id) }}" method="POST"
                                            class="d-inline mb-0">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="btn-action btn-delete border-0 bg-transparent"
                                                onclick="return confirm('Xóa bài kiểm tra này?')">
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
