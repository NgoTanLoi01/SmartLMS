@extends('layouts.app')

@section('title', 'Xem bài nộp')

@push('styles')
    @vite('resources/css/pages/submission-review.css')
@endpush

@section('content')
    <div class="srp">
        <div class="srp__shell">

            {{-- BACK --}}
            <a href="{{ route('courses.show', $course->id) }}" class="back-link">
                <i class="fa-solid fa-chevron-left"></i> Quay lại khóa học
            </a>

            {{-- HEADER --}}
            <div class="srp-header">
                <div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="bdg bdg--primary">{{ $assignmentTypeLabel }}</span>
                        @if ($submission->isGradePublished())
                            <span class="bdg bdg--success"><i class="fa-solid fa-circle-check"></i> Đã công bố</span>
                        @elseif ($canGrade && $submission->grade !== null)
                            <span class="bdg bdg--warning"><i class="fa-solid fa-pen"></i> Điểm nháp</span>
                        @else
                            <span class="bdg bdg--warning"><i class="fa-solid fa-hourglass-half"></i> Chưa chấm</span>
                        @endif
                    </div>
                    <h1 class="srp-header__title">{{ $assignment->title }}</h1>
                    <div class="srp-header__meta mt-1">
                        <span><i class="fa-solid fa-book me-1" style="color:var(--brand)"></i>{{ $course->title }}</span>
                        @if ($assignment->lesson)
                            <span class="sep">·</span>
                            <span>{{ $assignment->lesson->title }}</span>
                        @endif
                        <span class="sep">·</span>
                        <span><i class="fa-solid fa-calendar-days me-1"></i>Hạn:
                            {{ $assignment->due_date?->format('d/m/Y H:i') ?? '---' }}</span>
                    </div>
                    @if ($canGrade)
                        <nav class="submission-nav" aria-label="Chuyển bài nộp">
                            @if ($previousSubmissionId)
                                <a href="{{ route('assignments.submissions.review', $previousSubmissionId) }}">
                                    <i class="fa-solid fa-chevron-left"></i> Bài trước
                                </a>
                            @else
                                <span class="disabled"><i class="fa-solid fa-chevron-left"></i> Bài trước</span>
                            @endif
                            @if ($nextSubmissionId)
                                <a href="{{ route('assignments.submissions.review', $nextSubmissionId) }}">
                                    Bài tiếp <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            @else
                                <span class="disabled">Bài tiếp <i class="fa-solid fa-chevron-right"></i></span>
                            @endif
                        </nav>
                    @endif
                </div>
                @if ($canGrade)
                    <div class="review-tools">
                        <a href="{{ route('assignments.grades.export', $assignment->id) }}" class="review-tool-btn">
                            <i class="fa-solid fa-file-excel"></i> Xuất điểm
                        </a>
                        <button type="button" class="review-tool-btn" data-bs-toggle="modal" data-bs-target="#importGradesModal">
                            <i class="fa-solid fa-file-import"></i> Nhập điểm
                        </button>
                        <form method="POST" action="{{ route('assignments.submissions.download', $assignment->id) }}"
                            class="review-download-form">
                            @csrf
                            <select name="mode" aria-label="Phạm vi tải bài nộp">
                                <option value="all">Tất cả bài đã nộp</option>
                                <option value="ungraded">Chỉ bài chưa chấm</option>
                            </select>
                            <button type="submit" class="btn-download-zip">
                                <i class="fa-solid fa-file-zipper"></i> Tải bài (.zip)
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- GRADING WORKSPACE --}}
            <div class="grading-workspace {{ $canGrade ? '' : 'grading-workspace--student' }}">

                {{-- LEFT: STUDENT QUEUE --}}
                @if ($canGrade)
                    <aside class="grading-queue">
                    <div class="grading-queue__head">
                        <h2 class="grading-queue__title">Danh sách học viên</h2>
                        <div class="grading-queue__stats">
                            {{ $queueStats['pending'] }} chờ chấm · {{ $queueStats['draft'] }} nháp · {{ $queueStats['published'] }} công bố
                        </div>
                        <div class="grading-queue__search">
                            <i class="fa-solid fa-search"></i>
                            <input type="search" id="queueSearch" placeholder="Tìm học viên...">
                        </div>
                        <div class="grading-queue__filters" role="group" aria-label="Lọc trạng thái">
                            <button type="button" class="queue-filter active" data-queue-filter="all">Tất cả</button>
                            <button type="button" class="queue-filter" data-queue-filter="pending">Chờ chấm</button>
                            <button type="button" class="queue-filter" data-queue-filter="draft">Nháp</button>
                            <button type="button" class="queue-filter" data-queue-filter="published">Công bố</button>
                        </div>
                        <form id="bulkGradeStatusForm" method="POST" action="{{ route('assignments.grades.bulk-status', $assignment->id) }}"
                            class="grading-bulk-form">
                            @csrf
                            <input type="hidden" name="action" id="bulkGradeAction" value="publish">
                            <label><input type="checkbox" id="selectAllGraded"> Chọn bài có điểm</label>
                            <div class="grading-bulk-actions">
                                <button type="button" data-bulk-action="publish">Trả bài / công bố</button>
                                <button type="button" data-bulk-action="draft">Về nháp</button>
                            </div>
                        </form>
                    </div>
                    <div class="grading-queue__list" id="gradingQueueList">
                        @foreach ($gradingQueue as $item)
                            @php
                                $statusLabel = match ($item['status']) {
                                    'pending' => 'Chờ chấm',
                                    'draft' => 'Điểm nháp: ' . $item['grade'],
                                    'published' => 'Đã công bố: ' . $item['grade'],
                                    default => 'Chưa nộp',
                                };
                            @endphp
                            <div class="queue-student-row" data-status="{{ $item['status'] }}" data-name="{{ Str::lower($item['student_name']) }}">
                                @if ($item['submission_id'] && $item['grade'] !== null)
                                    <input type="checkbox" class="bulk-grade-checkbox" form="bulkGradeStatusForm"
                                        name="submission_ids[]" value="{{ $item['submission_id'] }}"
                                        aria-label="Chọn bài của {{ $item['student_name'] }}">
                                @endif
                                @if ($item['submission_id'])
                                    <a href="{{ route('assignments.submissions.review', $item['submission_id']) }}"
                                        class="queue-student {{ $item['is_current'] ? 'current' : '' }}">
                                @else
                                    <div class="queue-student disabled">
                                @endif
                                    <span class="queue-student__avatar">{{ mb_strtoupper(mb_substr($item['student_name'], 0, 1)) }}</span>
                                    <span class="queue-student__body">
                                        <span class="queue-student__name">{{ $item['student_name'] }}</span>
                                        <span class="queue-student__status {{ $item['status'] }}">{{ $statusLabel }}</span>
                                    </span>
                                @if ($item['submission_id']) </a> @else </div> @endif
                            </div>
                        @endforeach
                    </div>
                    </aside>
                @endif

                {{-- CENTER: CONTENT --}}
                <main class="grading-content">

                    {{-- INSTRUCTIONS --}}
                    <div class="panel">
                        <div class="panel__head">
                            <div class="panel__label">
                                <span class="icon-dot idot--blue"><i class="fa-solid fa-file-lines"></i></span>
                                Yêu cầu bài tập
                            </div>
                        </div>
                        <div class="panel__body">
                            <div class="instruction-box">
                                {!! $assignment->instructions !!}
                            </div>
                        </div>
                    </div>

                    {{-- RUBRIC --}}
                    <div class="panel" style="margin-top:1rem">
                        <div class="panel__head">
                            <div class="panel__label">
                                <span class="icon-dot idot--violet"><i class="fa-solid fa-list-check"></i></span>
                                Tiêu chí chấm điểm
                            </div>
                            <span class="bdg bdg--muted">Thang {{ $assignment->grading_scale ?? 10 }} điểm</span>
                        </div>
                        <div class="panel__body">
                            @if (trim((string) $assignment->grading_rubric))
                                <div class="instruction-box" style="white-space:pre-wrap;">{{ $assignment->grading_rubric }}</div>
                            @else
                                <div class="ai-notice">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>Bài tập này chưa có rubric cụ thể. AI vẫn có thể phân tích, nhưng chỉ dùng yêu cầu bài tập để tạo tiêu chí tạm.</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- STUDENT ANSWER --}}
                    <div class="panel" style="margin-top:1rem">
                        <div class="panel__head">
                            <div class="panel__label">
                                <span class="icon-dot idot--green"><i class="fa-solid fa-pen-nib"></i></span>
                                {{ $canGrade ? 'Bài làm học viên' : 'Bài làm của bạn' }}
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="submit-meta">
                                    <i class="fa-solid fa-clock"></i>
                                    Nộp lúc {{ $submission->formatSubmittedAt('H:i:s · d/m/Y') ?? '---' }}
                                </div>
                                @if ($fileUrl)
                                    <a href="{{ $fileUrl }}" target="_blank" class="bdg bdg--primary"
                                        style="text-decoration:none;padding:.3em .9em">
                                        <i class="fa-solid fa-download"></i> Tải file
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="panel__body">
                            @if ($fileUrl)
                                <div class="file-card mb-3">
                                    <div class="file-card__icon"><i class="fa-solid fa-paperclip"></i></div>
                                    <div>
                                        <div class="file-card__name">{{ $fileName ?? 'File bài làm đã đính kèm' }}</div>
                                        <div class="file-card__hint">
                                            @if ($filePreviewUrl)
                                                Có thể xem trước bên dưới hoặc tải file về máy.
                                            @else
                                                Định dạng này chưa hỗ trợ xem trước. Vui lòng tải file để kiểm tra.
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if ($filePreviewUrl)
                                    <div class="file-preview mb-3">
                                        <div class="file-preview__head">
                                            <div class="file-preview__title">
                                                <i class="fa-solid fa-eye"></i>
                                                <span>Xem trước bài nộp</span>
                                                <span class="file-preview__name">{{ $fileName }}</span>
                                            </div>
                                            <span class="bdg bdg--muted">{{ strtoupper($filePreviewType) }}</span>
                                        </div>
                                        @if ($filePreviewType === 'image')
                                            <img src="{{ $filePreviewUrl }}" alt="Xem trước bài nộp"
                                                class="file-preview__image">
                                        @else
                                            <iframe src="{{ $filePreviewUrl }}" class="file-preview__frame"
                                                sandbox="allow-same-origin"></iframe>
                                        @endif
                                    </div>
                                @else
                                    <div class="file-preview-empty mb-3">
                                        <i class="fa-solid fa-circle-info"></i>
                                        <div>
                                            Hệ thống hiện xem trước trực tiếp cho PDF, ảnh và HTML. Với Word/ZIP, giáo viên
                                            cần tải file để kiểm tra.
                                        </div>
                                    </div>
                                @endif
                            @endif

                            @if (trim((string) $submission->text_answer))
                                <div class="answer-box">{{ $submission->text_answer }}</div>
                            @else
                                <div class="answer-empty">
                                    <i class="fa-solid fa-file-slash"></i>
                                    <div>
                                        <strong>Không có nội dung tự luận</strong><br>
                                        Vui lòng kiểm tra file đính kèm nếu có.
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                </main>

                {{-- RIGHT: GRADING --}}
                <aside class="grading-aside">
                    <div class="grading-card">
                        <div class="panel">

                            @if ($canGrade)

                            {{-- STUDENT PROFILE --}}
                            <div class="student-profile">
                                <div class="student-avatar">
                                    {{ mb_strtoupper(mb_substr($submission->user?->name ?? 'HS', 0, 1)) }}</div>
                                <div>
                                    <div class="student-name">{{ $submission->user?->name ?? 'Học viên' }}</div>
                                    <div class="student-email">{{ $submission->user?->email }}</div>
                                </div>
                            </div>

                            {{-- AI SECTION --}}
                            <div class="ai-section">
                                @php
                                    $latestAiHistory = collect($submission->ai_analysis_history ?? [])->first() ?? [];
                                    $latestAiStrengths = $latestAiHistory['strengths'] ?? [];
                                    $latestAiImprovements = $latestAiHistory['improvements'] ?? [];
                                @endphp
                                @if (!$assignment->ai_grading_enabled)
                                    <div class="ai-notice">
                                        <i class="fa-solid fa-circle-info"></i>
                                        <span>AI hỗ trợ chấm đang tắt cho bài tập này.</span>
                                    </div>
                                @elseif (trim((string) $submission->text_answer) || $submission->file_path)
                                    <button type="button" id="aiAnalyzeBtn" class="btn-ai"
                                        data-submission-id="{{ $submission->id }}">
                                        <span class="ai-icon"><i class="fa-solid fa-robot"></i></span>
                                        {{ $submission->ai_analyzed_at ? 'AI phân tích lại' : 'AI phân tích bài làm' }}
                                    </button>
                                    @if ($submission->file_path)
                                        <div class="ai-notice mt-2">
                                            <i class="fa-solid fa-file-lines"></i>
                                            <span>AI sẽ thử đọc nội dung file PDF, DOCX, TXT, HTML, CSS, JS, PHP hoặc MD nếu có thể.</span>
                                        </div>
                                    @endif
                                    <div id="aiResultBox" class="ai-result"
                                        style="{{ $submission->ai_analyzed_at ? '' : 'display:none' }}">
                                        @if ($submission->ai_analyzed_at)
                                            <div class="ai-result__row">
                                                <div class="ai-result__key">Gợi ý điểm</div>
                                                <div class="ai-result__val">
                                                    <span class="ai-score-chip">{{ $submission->ai_suggested_score ?? '---' }}<span>/ {{ $assignment->grading_scale ?? 10 }}</span></span>
                                                </div>
                                            </div>
                                            @if ($submission->ai_feedback)
                                                <div class="ai-result__row">
                                                    <div class="ai-result__key">Nhận xét</div>
                                                    <div class="ai-result__val">{{ $submission->ai_feedback }}</div>
                                                </div>
                                            @endif
                                            @if (!empty($submission->ai_review_flags))
                                                <div class="ai-result__row">
                                                    <div class="ai-result__key">Cảnh báo</div>
                                                    <div class="ai-result__val">
                                                        <div class="ai-warning-list">
                                                            @foreach ($submission->ai_review_flags as $flag)
                                                                <div class="ai-warning {{ $flag['level'] ?? 'medium' }}">
                                                                    {{ $flag['message'] ?? 'Cần giáo viên xem kỹ hơn.' }}
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if (!empty($latestAiStrengths))
                                                <div class="ai-result__row">
                                                    <div class="ai-result__key">Điểm tốt</div>
                                                    <div class="ai-result__val">{{ implode('; ', $latestAiStrengths) }}</div>
                                                </div>
                                            @endif
                                            @if (!empty($latestAiImprovements))
                                                <div class="ai-result__row">
                                                    <div class="ai-result__key">Cần cải thiện</div>
                                                    <div class="ai-result__val">{{ implode('; ', $latestAiImprovements) }}</div>
                                                </div>
                                            @endif
                                            @if (!empty($submission->ai_rubric_breakdown))
                                                <div class="ai-result__row">
                                                    <div class="ai-result__key">Rubric</div>
                                                    <div class="ai-result__val">
                                                        @foreach ($submission->ai_rubric_breakdown as $item)
                                                            <div class="mb-2">
                                                                <strong>{{ $item['criterion'] ?? 'Tiêu chí' }}:</strong>
                                                                {{ $item['score'] ?? '---' }}/{{ $item['max_score'] ?? '---' }}
                                                                @if (!empty($item['comment']))
                                                                    <div class="text-muted">{{ $item['comment'] }}</div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            @if ($submission->ai_grading_notes)
                                                <div class="ai-result__row">
                                                    <div class="ai-result__key">Ghi chú</div>
                                                    <div class="ai-result__val">{{ $submission->ai_grading_notes }}</div>
                                                </div>
                                            @endif
                                            <div class="ai-result__row">
                                                <div class="ai-result__key">Cập nhật</div>
                                                <div class="ai-result__val">{{ $submission->ai_analyzed_at->format('H:i · d/m/Y') }}</div>
                                            </div>
                                            <div class="ai-actions">
                                                <button type="button" class="btn-ai-apply" id="useAiFeedbackBtn"
                                                    data-feedback="{{ e($submission->ai_feedback ?? '') }}">
                                                    <i class="fa-solid fa-comment-dots me-1"></i>Dùng nhận xét AI
                                                </button>
                                            </div>
                                            @if (!empty($submission->ai_analysis_history) && count($submission->ai_analysis_history) > 1)
                                                <div class="ai-history px-3 pb-3">
                                                    Đã phân tích {{ count($submission->ai_analysis_history) }} lần. Lần gần nhất đang được hiển thị.
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @else
                                    <div class="ai-notice">
                                        <i class="fa-solid fa-circle-info"></i>
                                        <span>Bài nộp chưa có nội dung tự luận hoặc file để AI phân tích.</span>
                                    </div>
                                @endif
                            </div>

                            {{-- GRADING FORM --}}
                            <div class="feedback-template-tools">
                                <label class="form-lbl" for="feedbackTemplateSelect">Mẫu nhận xét thường dùng</label>
                                <div class="feedback-template-row">
                                    <select id="feedbackTemplateSelect" class="form-ctrl">
                                        <option value="">Chọn mẫu nhận xét...</option>
                                        @foreach ($feedbackTemplates as $template)
                                            <option value="{{ $template->id }}" data-content="{{ $template->content }}"
                                                data-delete-url="{{ route('grading-feedback-templates.destroy', $template) }}">
                                                {{ $template->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" id="applyFeedbackTemplate" class="template-btn">Áp dụng</button>
                                </div>
                                <div class="feedback-template-links">
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#feedbackTemplateModal">
                                        <i class="fa-solid fa-plus"></i> Lưu mẫu mới
                                    </button>
                                    <button type="button" id="deleteFeedbackTemplate">
                                        <i class="fa-regular fa-trash-can"></i> Xóa mẫu đã chọn
                                    </button>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('assignments.grade', $submission->id) }}"
                                class="grading-form">
                                @csrf

                                @if ($rubricCriteria)
                                    <div class="quick-rubric">
                                        <div class="quick-rubric__head">
                                            <span class="form-lbl mb-0">Chấm nhanh theo rubric</span>
                                            <span id="rubricTotal">0/{{ $assignment->grading_scale ?? 10 }}</span>
                                        </div>
                                        @foreach ($rubricCriteria as $criterion)
                                            @php
                                                $savedRubricScore = data_get($submission->rubric_scores, $loop->index.'.score');
                                            @endphp
                                            <label class="quick-rubric__row">
                                                <span>{{ $criterion['label'] }}</span>
                                                <span class="quick-rubric__score">
                                                    <input type="number" name="rubric_scores[{{ $loop->index }}][score]"
                                                        class="rubric-score-input" min="0" step="0.1"
                                                        @if ($criterion['max_score']) max="{{ $criterion['max_score'] }}" @endif
                                                        value="{{ $savedRubricScore }}" aria-label="Điểm {{ $criterion['label'] }}">
                                                    @if ($criterion['max_score'])
                                                        <small>/{{ $criterion['max_score'] }}</small>
                                                    @endif
                                                </span>
                                            </label>
                                        @endforeach
                                        <div class="quick-rubric__hint">Nhập các tiêu chí để hệ thống tự cộng vào điểm cuối cùng.</div>
                                    </div>
                                @endif

                                <div class="form-group">
                                    <label class="form-lbl">Điểm số</label>
                                    <div class="score-input-wrap">
                                        <input type="number" name="grade" id="gradeInput" step="0.1" min="0"
                                            max="{{ $assignment->grading_scale ?? 10 }}" value="{{ $submission->grade }}" placeholder="0.0" required>
                                        <span class="score-max">/ {{ $assignment->grading_scale ?? 10 }}</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-lbl">Nhận xét</label>
                                    <textarea name="feedback" id="feedbackInput" class="form-ctrl" placeholder="Nhập nhận xét cho học viên...">{{ $submission->feedback }}</textarea>
                                </div>

                                <div class="grading-actions">
                                    <button type="submit" name="action" value="save_draft" class="btn-save btn-save-draft">
                                        <i class="fa-regular fa-floppy-disk"></i>
                                        Lưu nháp
                                    </button>
                                    <button type="submit" name="action" value="publish_next" class="btn-save btn-save-next">
                                        <i class="fa-solid fa-forward"></i>
                                        Công bố & bài tiếp
                                    </button>
                                    <button type="submit" name="action" value="publish" class="btn-save">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Công bố điểm
                                    </button>
                                </div>
                                <div class="grading-visibility-note">
                                    <i class="fa-solid fa-eye-slash"></i> Điểm nháp chỉ giáo viên/admin nhìn thấy.
                                </div>
                            </form>

                            @else
                                <div class="panel__head">
                                    <div class="panel__label">
                                        <span class="icon-dot idot--green"><i class="fa-solid fa-chart-simple"></i></span>
                                        Kết quả của bạn
                                    </div>
                                </div>
                                <div class="panel__body">
                                    @if ($gradeVisible)
                                        <div class="score-input-wrap mb-3">
                                            <strong>{{ $submission->grade }}</strong>
                                            <span class="score-max">/ {{ $assignment->grading_scale ?? 10 }}</span>
                                        </div>
                                        @if (trim((string) $submission->feedback))
                                            <div class="form-lbl mb-2">Nhận xét của giáo viên</div>
                                            <div class="instruction-box">{{ $submission->feedback }}</div>
                                        @endif
                                    @else
                                        <div class="ai-notice">
                                            <i class="fa-solid fa-hourglass-half"></i>
                                            <span>{{ $submission->grade !== null ? 'Điểm đang được giáo viên hoàn thiện và chưa công bố.' : 'Bài nộp của bạn đang chờ giáo viên chấm điểm.' }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                        </div>
                    </div>
                </aside>

            </div>
        </div>
    </div>

    @if ($canGrade)
        <div class="modal fade" id="importGradesModal" tabindex="-1" aria-labelledby="importGradesModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('assignments.grades.import', $assignment->id) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="importGradesModalLabel">Nhập điểm từ bảng tính</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">
                                Hãy xuất file điểm của bài tập này, cập nhật cột <strong>Điểm</strong>,
                                <strong>Trạng thái</strong> và <strong>Nhận xét</strong>, rồi nhập lại. Không thay đổi ID bài nộp.
                            </p>
                            <label for="gradesImportFile" class="form-label fw-semibold">File XLSX, XLS hoặc CSV</label>
                            <input id="gradesImportFile" type="file" name="file" class="form-control"
                                accept=".xlsx,.xls,.csv" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">Kiểm tra và nhập điểm</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="feedbackTemplateModal" tabindex="-1" aria-labelledby="feedbackTemplateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('grading-feedback-templates.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="feedbackTemplateModalLabel">Lưu mẫu nhận xét</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="feedbackTemplateTitle" class="form-label fw-semibold">Tên mẫu</label>
                                <input id="feedbackTemplateTitle" name="title" class="form-control" maxlength="100" required>
                            </div>
                            <div>
                                <label for="feedbackTemplateContent" class="form-label fw-semibold">Nội dung nhận xét</label>
                                <textarea id="feedbackTemplateContent" name="content" class="form-control" rows="5" maxlength="5000" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">Lưu mẫu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <form method="POST" id="deleteFeedbackTemplateForm" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection

@push('scripts')
    @if ($canGrade)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const search = document.getElementById('queueSearch');
            const filters = document.querySelectorAll('[data-queue-filter]');
            const students = document.querySelectorAll('#gradingQueueList [data-status]');
            let activeFilter = 'all';

            const applyQueueFilter = () => {
                const keyword = (search?.value || '').trim().toLocaleLowerCase('vi');
                students.forEach(student => {
                    const matchesStatus = activeFilter === 'all' || student.dataset.status === activeFilter;
                    const matchesName = !keyword || (student.dataset.name || '').includes(keyword);
                    student.style.display = matchesStatus && matchesName ? '' : 'none';
                });
            };

            search?.addEventListener('input', applyQueueFilter);
            filters.forEach(button => button.addEventListener('click', function() {
                filters.forEach(item => item.classList.remove('active'));
                this.classList.add('active');
                activeFilter = this.dataset.queueFilter;
                applyQueueFilter();
            }));

            document.addEventListener('keydown', function(event) {
                if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                    event.preventDefault();
                    document.querySelector('button[name="action"][value="publish_next"]')?.click();
                }
            });

            const bulkForm = document.getElementById('bulkGradeStatusForm');
            const bulkCheckboxes = Array.from(document.querySelectorAll('.bulk-grade-checkbox'));
            const selectAll = document.getElementById('selectAllGraded');
            selectAll?.addEventListener('change', () => {
                bulkCheckboxes.forEach(checkbox => {
                    const row = checkbox.closest('.queue-student-row');
                    if (row?.style.display !== 'none') checkbox.checked = selectAll.checked;
                });
            });
            document.querySelectorAll('[data-bulk-action]').forEach(button => button.addEventListener('click', function() {
                const selectedCount = bulkCheckboxes.filter(checkbox => checkbox.checked).length;
                if (!selectedCount) {
                    window.alert('Vui lòng chọn ít nhất một bài đã có điểm.');
                    return;
                }
                const publish = this.dataset.bulkAction === 'publish';
                const message = publish
                    ? `Trả bài và công bố điểm cho ${selectedCount} học viên đã chọn?`
                    : `Chuyển ${selectedCount} kết quả đã chọn về nháp? Học viên sẽ không còn thấy điểm.`;
                if (!window.confirm(message)) return;
                document.getElementById('bulkGradeAction').value = this.dataset.bulkAction;
                bulkForm.submit();
            }));

            const templateSelect = document.getElementById('feedbackTemplateSelect');
            const feedbackInput = document.getElementById('feedbackInput');
            document.getElementById('applyFeedbackTemplate')?.addEventListener('click', () => {
                const option = templateSelect?.selectedOptions?.[0];
                if (!option?.value) return;
                feedbackInput.value = option.dataset.content || '';
                feedbackInput.focus();
            });
            document.querySelector('[data-bs-target="#feedbackTemplateModal"]')?.addEventListener('click', () => {
                document.getElementById('feedbackTemplateContent').value = feedbackInput?.value || '';
            });
            document.getElementById('deleteFeedbackTemplate')?.addEventListener('click', () => {
                const option = templateSelect?.selectedOptions?.[0];
                if (!option?.value || !option.dataset.deleteUrl) {
                    window.alert('Vui lòng chọn mẫu cần xóa.');
                    return;
                }
                if (!window.confirm(`Xóa mẫu "${option.textContent.trim()}"?`)) return;
                const form = document.getElementById('deleteFeedbackTemplateForm');
                form.action = option.dataset.deleteUrl;
                form.submit();
            });

            const gradeInput = document.getElementById('gradeInput');
            const rubricInputs = Array.from(document.querySelectorAll('.rubric-score-input'));
            const rubricTotal = document.getElementById('rubricTotal');
            const updateRubricTotal = () => {
                const hasScores = rubricInputs.some(input => input.value !== '');
                const total = rubricInputs.reduce((sum, input) => sum + (Number.parseFloat(input.value) || 0), 0);
                if (rubricTotal) rubricTotal.textContent = `${Number(total.toFixed(2))}/{{ $assignment->grading_scale ?? 10 }}`;
                if (hasScores && gradeInput) gradeInput.value = Number(total.toFixed(2));
            };
            rubricInputs.forEach(input => input.addEventListener('input', updateRubricTotal));
            updateRubricTotal();
        });

        document.addEventListener('DOMContentLoaded', function() {
            const aiBtn = document.getElementById('aiAnalyzeBtn');
            if (!aiBtn) return;

            const resultBox = document.getElementById('aiResultBox');
            const feedbackInput = document.getElementById('feedbackInput');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const gradingScale = @json($assignment->grading_scale ?? 10);
            let latestAiFeedback = @json($submission->ai_feedback ?? '');

            const esc = (v) => String(v ?? '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

            aiBtn.addEventListener('click', function() {
                const orig = aiBtn.innerHTML;
                aiBtn.disabled = true;
                aiBtn.innerHTML =
                    `<span class="spinner-border spinner-border-sm me-1" style="width:14px;height:14px;border-width:2px"></span> AI đang phân tích…`;
                resultBox.style.display = 'block';
                resultBox.innerHTML =
                    `<div class="ai-result__row"><div class="ai-result__key">Trạng thái</div><div class="ai-result__val" style="color:var(--text-muted)">Đang đọc bài làm…</div></div>`;

                fetch(`/submissions/${aiBtn.dataset.submissionId}/ai-analysis`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({}),
                    })
                    .then(r => r.json().then(p => ({
                        ok: r.ok,
                        p
                    })))
                    .then(async ({
                        ok,
                        p
                    }) => {
                        if (!ok || !p.success) throw new Error(p.message ||
                            'AI chưa phân tích được bài làm.');

                        if (p.queued) {
                            for (let attempt = 0; attempt < 90; attempt++) {
                                const statusResponse = await fetch(p.status_url, { headers: { 'Accept': 'application/json' } });
                                const operation = await statusResponse.json();
                                if (operation.status === 'completed') {
                                    p = operation.result || {};
                                    break;
                                }
                                if (operation.status === 'failed') throw new Error(operation.message || 'AI chấm bài thất bại.');
                                await new Promise(resolve => setTimeout(resolve, 2000));
                            }
                            if (p.queued) throw new Error('AI xử lý quá lâu. Vui lòng kiểm tra lại sau.');
                        }

                        const a = p.analysis || {};
                        const strengths = Array.isArray(a.strengths) ? a.strengths : [];
                        const improvements = Array.isArray(a.improvements) ? a.improvements : [];
                        const rubricBreakdown = Array.isArray(a.rubric_breakdown) ? a.rubric_breakdown : [];
                        const reviewFlags = Array.isArray(a.review_flags) ? a.review_flags : [];
                        latestAiFeedback = a.feedback || '';

                            const rows = [{
                                key: 'Gợi ý điểm',
                                val: `<span class="ai-score-chip">${esc(a.suggested_score ?? '---')}<span>/ ${esc(gradingScale)}</span></span>`
                            },
                            {
                                key: 'Nhận xét',
                                val: esc(a.feedback || '---')
                            },
                            reviewFlags.length ? {
                                key: 'Cảnh báo',
                                val: `<div class="ai-warning-list">${reviewFlags.map(flag => `
                                    <div class="ai-warning ${esc(flag.level || 'medium')}">${esc(flag.message || 'Cần giáo viên xem kỹ hơn.')}</div>
                                `).join('')}</div>`
                            } : null,
                            rubricBreakdown.length ? {
                                key: 'Rubric',
                                val: rubricBreakdown.map(item => `
                                    <div style="margin-bottom:.55rem">
                                        <strong>${esc(item.criterion || 'Tiêu chí')}:</strong>
                                        ${esc(item.score ?? '---')}/${esc(item.max_score ?? '---')}
                                        ${item.comment ? `<div style="color:var(--text-muted)">${esc(item.comment)}</div>` : ''}
                                    </div>
                                `).join('')
                            } : null,
                            strengths.length ? {
                                key: 'Điểm tốt',
                                val: esc(strengths.join('; '))
                            } : null,
                            improvements.length ? {
                                key: 'Cần cải thiện',
                                val: esc(improvements.join('; '))
                            } : null,
                            a.grading_notes ? {
                                key: 'Ghi chú',
                                val: esc(a.grading_notes)
                            } : null,
                        ].filter(Boolean);

                        resultBox.innerHTML = rows.map(r =>
                            `<div class="ai-result__row"><div class="ai-result__key">${r.key}</div><div class="ai-result__val">${r.val}</div></div>`
                        ).join('') + `
                            <div class="ai-actions">
                                <button type="button" class="btn-ai-apply" id="useAiFeedbackBtn">
                                    <i class="fa-solid fa-comment-dots me-1"></i>Dùng nhận xét AI
                                </button>
                            </div>`;
                    })
                    .catch(err => {
                        resultBox.innerHTML =
                            `<div class="ai-result__row"><div class="ai-result__val" style="color:var(--danger)">${esc(err.message)}</div></div>`;
                    })
                    .finally(() => {
                        aiBtn.disabled = false;
                        aiBtn.innerHTML = orig;
                    });
            });

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('#useAiFeedbackBtn');
                if (!btn) return;

                const feedback = latestAiFeedback || btn.getAttribute('data-feedback') || '';
                if (!feedback) return;

                feedbackInput.value = feedback;
                feedbackInput.focus();
            });
        });
    </script>
    @endif
@endpush
