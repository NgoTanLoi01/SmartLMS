<div class="col-md-4 col-lg-3">
    <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold small text-uppercase text-muted"><i class="fas fa-list-ol me-2"></i>Nội
                dung
                học tập</h6>
        </div>
        <div class="card-body p-0" style="max-height: 75vh; overflow-y: auto;">
            <div class="accordion accordion-flush" id="courseAccordion">

                @forelse ($course->modules as $index => $module)
                    <div class="accordion-item border-bottom">
                        <div class="position-relative module-header-wrapper d-flex align-items-center">
                            <button
                                class="accordion-button {{ $index == 0 ? '' : 'collapsed' }} py-3 fw-bold flex-grow-1 shadow-none"
                                type="button" data-bs-toggle="collapse" data-bs-target="#module-{{ $module->id }}">
                                <span class="text-truncate-custom me-4">{{ $module->title }}</span>
                            </button>

                            @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                <div class="action-buttons position-absolute end-0 me-5 d-flex align-items-center">
                                    <a href="javascript:void(0)" class="btn-action btn-edit edit-module-btn"
                                        data-id="{{ $module->id }}" data-title="{{ $module->title }}"
                                        data-bs-toggle="modal" data-bs-target="#editModuleModal">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('modules.destroy', $module->id) }}" method="POST"
                                        class="d-inline mb-0">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-action btn-delete border-0 bg-transparent"
                                            onclick="return confirm('Xóa chương này?')"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            @endif
                        </div>

                        <div id="module-{{ $module->id }}"
                            class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}"
                            data-bs-parent="#courseAccordion">
                            <div class="accordion-body p-0">
                                <div class="list-group list-group-flush">
                                    @forelse ($module->lessons as $lesson)
                                        @php $isCompleted = in_array($lesson->id, $completedLessonIds ?? []); @endphp
                                        <div
                                            class="list-group-item border-0 px-3 py-2 lesson-item-wrapper d-flex align-items-center justify-content-between shadow-none">
                                            <a href="javascript:void(0)"
                                                class="lesson-item text-decoration-none text-dark flex-grow-1 d-flex align-items-center"
                                                style="min-width: 0;" data-id="{{ $lesson->id }}"
                                                data-content="{{ $lesson->content }}" data-title="{{ $lesson->title }}"
                                                data-video="{{ $lesson->video_url }}"
                                                data-module="{{ $module->id }}"
                                                data-attachment="{{ $lesson->attachment ? asset('storage/' . $lesson->attachment) : '' }}"
                                                data-attachment-name="{{ $lesson->attachment ? basename($lesson->attachment) : '' }}">
                                                <i class="{{ $isCompleted ? 'fas fa-check-circle text-success' : 'far fa-play-circle text-primary' }} me-2 flex-shrink-0 lesson-icon"
                                                    id="icon-lesson-{{ $lesson->id }}"></i>
                                                <span class="small text-truncate-custom">{{ $lesson->title }}</span>
                                            </a>

                                            @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                                <div class="action-buttons d-flex ms-2">
                                                    <a href="javascript:void(0)"
                                                        class="btn-action btn-edit edit-lesson-btn"
                                                        data-id="{{ $lesson->id }}"
                                                        data-title="{{ $lesson->title }}"
                                                        data-content="{{ $lesson->content }}"
                                                        data-video="{{ $lesson->video_url }}"
                                                        data-module="{{ $module->id }}" data-bs-toggle="modal"
                                                        data-bs-target="#editLessonModal">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('lessons.destroy', $lesson->id) }}"
                                                        method="POST" class="d-inline mb-0">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                            class="btn-action btn-delete border-0 bg-transparent"
                                                            onclick="return confirm('Xóa bài này?')"><i
                                                                class="fas fa-times"></i></button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- ✅ BÀI TẬP --}}
                                        @foreach ($lesson->assignments as $assignment)
                                            @php
                                                $submission =
                                                    auth()->user()->role === 'student' &&
                                                    isset($userSubmissions[$assignment->id])
                                                        ? $userSubmissions[$assignment->id]
                                                        : null;
                                            @endphp
                                            <div
                                                class="list-group-item border-0 py-2 assignment-item-wrapper d-flex align-items-center justify-content-between shadow-none bg-light border-bottom">
                                                <div class="ms-4 flex-grow-1 d-flex align-items-center"
                                                    style="min-width: 0;">
                                                    <a href="javascript:void(0)"
                                                        class="assignment-item text-decoration-none text-dark flex-grow-1 d-flex align-items-center"
                                                        data-id="{{ $assignment->id }}"
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
                                                            class="{{ $submission ? 'fas fa-check-circle text-success' : 'fas fa-file-signature text-warning' }} me-2 flex-shrink-0"></i>
                                                        <span
                                                            class="small text-truncate-custom fw-medium">{{ $assignment->title }}</span>
                                                    </a>
                                                </div>

                                                @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                                    <div class="action-buttons d-flex ms-2 gap-1">
                                                        <a href="javascript:void(0)"
                                                            class="btn-action btn-edit edit-assignment-btn"
                                                            data-id="{{ $assignment->id }}"
                                                            data-title="{{ $assignment->title }}"
                                                            data-instructions="{{ $assignment->instructions }}"
                                                            data-due="{{ $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : '' }}"
                                                            data-lesson="{{ $lesson->id }}" data-bs-toggle="modal"
                                                            data-bs-target="#editAssignmentModal" title="Sửa bài tập">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form
                                                            action="{{ route('assignments.destroy', $assignment->id) }}"
                                                            method="POST" class="d-inline mb-0">
                                                            @csrf @method('DELETE')
                                                            <button type="submit"
                                                                class="btn-action btn-delete border-0 bg-transparent"
                                                                onclick="return confirm('Xóa bài tập này?')"
                                                                title="Xóa bài tập"><i
                                                                    class="fas fa-trash"></i></button>
                                                        </form>
                                                        <a href="javascript:void(0)"
                                                            class="btn-action text-primary view-submissions-btn border bg-white shadow-sm"
                                                            data-id="{{ $assignment->id }}"
                                                            title="Chấm điểm / Xem danh sách"><i
                                                                class="fas fa-users-cog"></i></a>
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

                {{-- ✅ MENU BÀI KIỂM TRA (QUIZZES) NẰM Ở CUỐI --}}
                @if ($course->quizzes->count() > 0)
                    <div class="accordion-item border-bottom bg-light">
                        <div class="position-relative module-header-wrapper d-flex align-items-center">
                            <button
                                class="accordion-button collapsed py-3 fw-bold flex-grow-1 shadow-none bg-transparent"
                                style="color: #6f42c1;" type="button" data-bs-toggle="collapse"
                                data-bs-target="#course-quizzes-collapse">
                                <i class="fas fa-clipboard-list me-2"></i> Bài kiểm tra
                                ({{ $course->quizzes->count() }})
                            </button>
                        </div>

                        <div id="course-quizzes-collapse" class="accordion-collapse collapse"
                            data-bs-parent="#courseAccordion">
                            <div class="accordion-body p-0">
                                <div class="list-group list-group-flush">
                                    @foreach ($course->quizzes as $quiz)
                                        @php
                                            $attempt =
                                                auth()->user()->role === 'student' &&
                                                isset($userQuizAttempts[$quiz->id])
                                                    ? $userQuizAttempts[$quiz->id]
                                                    : null;
                                        @endphp
                                        <div
                                            class="list-group-item border-0 py-2 quiz-item-wrapper d-flex align-items-center justify-content-between shadow-none bg-white border-bottom">
                                            <div class="ms-4 flex-grow-1 d-flex align-items-center"
                                                style="min-width: 0;">
                                                <a href="javascript:void(0)"
                                                    class="quiz-item text-decoration-none text-dark flex-grow-1 d-flex align-items-center"
                                                    data-id="{{ $quiz->id }}" data-title="{{ $quiz->title }}"
                                                    data-duration="{{ $quiz->time_limit }}"
                                                    data-status="{{ $attempt ? 'completed' : 'pending' }}"
                                                    data-score="{{ $attempt ? $attempt->score : '' }}">
                                                    <i class="{{ $attempt ? 'fas fa-check-circle text-success' : 'fas fa-stopwatch' }} me-2 flex-shrink-0"
                                                        style="{{ $attempt ? '' : 'color: #6f42c1;' }}"></i>
                                                    <span class="small text-truncate-custom fw-bold"
                                                        style="{{ $attempt ? 'color: #198754;' : 'color: #6f42c1;' }}">
                                                        {{ $quiz->title }} ({{ $quiz->time_limit }} phút)
                                                    </span>
                                                </a>
                                            </div>

                                            @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                                                <div class="action-buttons d-flex ms-2 gap-1">
                                                    <a href="{{ route('quizzes.show', $quiz->id) }}"
                                                        class="btn-action text-white shadow-sm"
                                                        style="background-color: #6f42c1;" title="Quản lý câu hỏi">
                                                        <i class="fas fa-list-ul"></i>
                                                    </a>
                                                    <form action="{{ route('quizzes.destroy', $quiz->id) }}"
                                                        method="POST" class="d-inline mb-0">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                            class="btn-action btn-delete border-0 bg-transparent"
                                                            onclick="return confirm('Xóa bài kiểm tra này?')"
                                                            title="Xóa đề thi"><i class="fas fa-trash"></i></button>
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
        </div>
    </div>
</div>
