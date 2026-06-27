{{-- ============================================================
     SHARED MODAL STYLES — inject once per page
     ============================================================ --}}
@once
    @push('styles')
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap');

            /* ── Base ── */
            .cm-modal .modal-content {
                border: none;
                border-radius: 18px;
                overflow: hidden;
                box-shadow: 0 24px 60px rgba(0, 0, 0, 0.14);
                font-family: 'Be Vietnam Pro', sans-serif;
            }

            /* ── Header ── */
            .cm-modal .modal-header {
                padding: 1.35rem 1.5rem 0;
                border: none;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .cm-header-icon {
                width: 36px;
                height: 36px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 0.95rem;
            }

            .cm-modal .modal-title {
                font-size: 0.95rem;
                font-weight: 800;
                color: #111827;
                letter-spacing: -0.01em;
                flex: 1;
                margin: 0;
            }

            .cm-modal .btn-close {
                width: 28px;
                height: 28px;
                border-radius: 8px;
                background-size: 10px;
                opacity: 0.4;
                transition: opacity 0.15s, background 0.15s;
                flex-shrink: 0;
            }

            .cm-modal .btn-close:hover {
                opacity: 0.8;
                background-color: #F3F4F6;
            }

            /* ── Body ── */
            .cm-modal .modal-body {
                padding: 1.25rem 1.5rem 0.5rem;
            }

            /* ── Footer ── */
            .cm-modal .modal-footer {
                padding: 1rem 1.5rem 1.35rem;
                border: none;
                gap: 0.5rem;
            }

            /* ── Field ── */
            .cm-field {
                margin-bottom: 1rem;
            }

            .cm-field:last-child {
                margin-bottom: 0;
            }

            .cm-label {
                display: block;
                font-size: 0.68rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #9CA3AF;
                margin-bottom: 0.4rem;
            }

            .cm-ctrl {
                width: 100%;
                background: #F9FAFB;
                border: 1.5px solid #E5E7EB;
                border-radius: 10px;
                padding: 0.65rem 0.9rem;
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 0.875rem;
                color: #111827;
                transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
                outline: none;
                appearance: none;
            }

            .cm-ctrl:focus {
                background: #fff;
                border-color: #6366F1;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
            }

            select.cm-ctrl {
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2.5'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 0.8rem center;
                padding-right: 2.25rem;
                cursor: pointer;
            }

            textarea.cm-ctrl {
                resize: vertical;
                min-height: 100px;
                line-height: 1.6;
            }

            .cm-hint {
                font-size: 0.75rem;
                color: #9CA3AF;
                margin-top: 0.3rem;
                font-style: italic;
            }

            /* Two-column grid inside modal body */
            .cm-row {
                display: grid;
                gap: 0.85rem;
            }

            .cm-row.cols-2 {
                grid-template-columns: 1fr 1fr;
            }

            .cm-row.cols-3 {
                grid-template-columns: 1fr 1fr 1fr;
            }

            @media (max-width: 576px) {

                .cm-row.cols-2,
                .cm-row.cols-3 {
                    grid-template-columns: 1fr;
                }
            }

            /* Info banner inside modal */
            .cm-info-banner {
                background: #EFF6FF;
                border: 1px solid #BFDBFE;
                border-radius: 10px;
                padding: 0.7rem 0.9rem;
                font-size: 0.78rem;
                color: #1E40AF;
                display: flex;
                align-items: flex-start;
                gap: 0.5rem;
                margin-bottom: 1rem;
                line-height: 1.5;
            }

            .cm-info-banner i {
                flex-shrink: 0;
                margin-top: 1px;
            }

            /* Section divider */
            .cm-section-title {
                font-size: 0.68rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #6366F1;
                display: flex;
                align-items: center;
                gap: 0.4rem;
                margin: 1.1rem 0 0.75rem;
                padding-bottom: 0.5rem;
                border-bottom: 1.5px solid #EEF2FF;
            }

            /* Difficulty inputs */
            .difficulty-grid {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 0.75rem;
            }

            .diff-card {
                background: #F9FAFB;
                border: 1.5px solid #E5E7EB;
                border-radius: 12px;
                padding: 0.85rem 0.65rem 0.65rem;
                text-align: center;
                transition: border-color 0.15s;
            }

            .diff-card:focus-within {
                border-color: #6366F1;
                background: #fff;
            }

            .diff-label {
                font-size: 0.65rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.07em;
                margin-bottom: 0.4rem;
            }

            .diff-easy {
                color: #15803D;
            }

            .diff-medium {
                color: #B45309;
            }

            .diff-hard {
                color: #B91C1C;
            }

            .diff-card input[type="number"] {
                width: 100%;
                border: none;
                background: transparent;
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 1.4rem;
                font-weight: 800;
                text-align: center;
                color: #111827;
                outline: none;
                padding: 0;
                -moz-appearance: textfield;
            }

            .diff-card input::-webkit-outer-spin-button,
            .diff-card input::-webkit-inner-spin-button {
                -webkit-appearance: none;
            }

            /* ── Submit button ── */
            .cm-btn {
                width: 100%;
                border: none;
                border-radius: 10px;
                padding: 0.75rem 1rem;
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 0.875rem;
                font-weight: 800;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                transition: all 0.15s;
                letter-spacing: 0.01em;
            }

            .cm-btn-primary {
                background: #4F46E5;
                color: #fff;
                box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            }

            .cm-btn-primary:hover {
                background: #4338CA;
                box-shadow: 0 6px 16px rgba(79, 70, 229, 0.38);
            }

            .cm-btn-amber {
                background: #F59E0B;
                color: #fff;
                box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
            }

            .cm-btn-amber:hover {
                background: #D97706;
            }

            .cm-btn-violet {
                background: #7C3AED;
                color: #fff;
                box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
            }

            .cm-btn-violet:hover {
                background: #6D28D9;
            }

            .cm-ai-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 8px;
            }

            .cm-ai-btn {
                align-items: center;
                background: #eff6ff;
                border: 1px solid #bfdbfe;
                border-radius: 8px;
                color: #1d4ed8;
                display: inline-flex;
                font-size: 12px;
                font-weight: 700;
                gap: 6px;
                min-height: 34px;
                padding: 7px 10px;
            }

            .cm-ai-btn:disabled {
                cursor: wait;
                opacity: .65;
            }

            /* ── Table modal ── */
            .cm-table {
                width: 100%;
                border-collapse: collapse;
            }

            .cm-table thead th {
                font-size: 0.65rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.07em;
                color: #9CA3AF;
                padding: 0.75rem 1.25rem;
                background: #F9FAFB;
                border-bottom: 1px solid #E5E7EB;
                white-space: nowrap;
            }

            .cm-table tbody tr {
                border-bottom: 1px solid #F3F4F6;
                transition: background 0.12s;
            }

            .cm-table tbody tr:last-child {
                border-bottom: none;
            }

            .cm-table tbody tr:hover {
                background: #F9FAFB;
            }

            .cm-table tbody td {
                padding: 0.85rem 1.25rem;
                font-size: 0.82rem;
                color: #374151;
                vertical-align: middle;
            }

            /* submissions modal header */
            .cm-modal-header-strip {
                background: #F9FAFB;
                border-bottom: 1px solid #E5E7EB;
                padding: 1rem 1.5rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .cm-modal-header-strip .modal-title {
                font-size: 0.9rem;
                font-weight: 800;
                color: #111827;
            }

            /* Color tokens for icons */
            .icon-blue {
                background: #EFF6FF;
                color: #2563EB;
            }

            .icon-amber {
                background: #FFFBEB;
                color: #D97706;
            }

            .icon-violet {
                background: #F5F3FF;
                color: #7C3AED;
            }

            .icon-green {
                background: #F0FDF4;
                color: #16A34A;
            }

            .modal.fade .modal-dialog {
                transform: translate(0, 100px);
                transition: transform 0.3s ease-out, opacity 0.3s ease-out;
            }
            #viewSubmissionsModal.fade.show .modal-dialog {
                transform: translate(0, 0);
            }

        </style>
    @endpush
@endonce

{{-- ============================================================
     1. MODAL: THÊM CHƯƠNG
     ============================================================ --}}
<div class="modal fade cm-modal" id="addModuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('modules.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="course_id" value="{{ $course->id }}">

            <div class="modal-header">
                <div class="cm-header-icon icon-blue"><i class="fas fa-folder-plus"></i></div>
                <h5 class="modal-title">Thêm chương mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="cm-field">
                    <label class="cm-label">Tên chương học</label>
                    <input type="text" name="title" class="cm-ctrl" placeholder="VD: Giới thiệu..."
                        required autofocus>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="cm-btn cm-btn-primary">
                    <i class="fas fa-check"></i> Lưu chương
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================
     2. MODAL: SỬA CHƯƠNG
     ============================================================ --}}
<div class="modal fade cm-modal" id="editModuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editModuleForm" method="POST" class="modal-content">
            @csrf @method('PUT')

            <div class="modal-header">
                <div class="cm-header-icon icon-amber"><i class="fas fa-folder"></i></div>
                <h5 class="modal-title">Sửa chương</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="cm-field">
                    <label class="cm-label">Tên chương học</label>
                    <input type="text" name="title" id="editModuleTitle" class="cm-ctrl" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="cm-btn cm-btn-amber">
                    <i class="fas fa-save"></i> Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================
     3. MODAL: THÊM BÀI HỌC
     ============================================================ --}}
<div class="modal fade cm-modal" id="addLessonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('lessons.store') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf

            <div class="modal-header">
                <div class="cm-header-icon icon-blue"><i class="fas fa-book-open"></i></div>
                <h5 class="modal-title">Thêm bài học mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Chương</label>
                        <select name="module_id" class="cm-ctrl" required>
                            @foreach ($course->modules as $module)
                                <option value="{{ $module->id }}">{{ $module->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Tiêu đề bài học</label>
                        <input type="text" name="title" class="cm-ctrl" placeholder="VD: HTML cơ bản"
                            required>
                    </div>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Link video / tài liệu ngoài</label>
                        <input type="url" name="video_url" class="cm-ctrl" placeholder="https://youtube.com/...">
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Tài liệu đính kèm</label>
                        <input type="file" name="attachment" class="cm-ctrl"
                            style="padding:0.5rem 0.75rem; cursor:pointer;">
                        <div class="cm-hint">PDF, Word, ZIP — bỏ trống nếu không có</div>
                    </div>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Trạng thái xuất bản</label>
                        <select name="status" class="cm-ctrl">
                            <option value="published">Published - mở cho học sinh</option>
                            <option value="draft">Draft - bản nháp</option>
                            <option value="hidden">Hidden - ẩn khỏi học sinh</option>
                            <option value="archived">Archived - lưu trữ</option>
                        </select>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Mở từ thời điểm</label>
                        <input type="datetime-local" name="available_from" class="cm-ctrl">
                        <div class="cm-hint">Bỏ trống nếu mở ngay.</div>
                    </div>
                </div>

                <div class="cm-field">
                    <label class="cm-label">Nội dung chi tiết</label>
                    <textarea name="content" id="addLessonContent" class="cm-ctrl" rows="4"
                        placeholder="Mô tả nội dung bài học..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="cm-btn cm-btn-primary">
                    <i class="fas fa-plus"></i> Lưu bài học
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================
     4. MODAL: SỬA BÀI HỌC
     ============================================================ --}}
<div class="modal fade cm-modal" id="editLessonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editLessonForm" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf @method('PUT')

            <div class="modal-header">
                <div class="cm-header-icon icon-amber"><i class="fas fa-edit"></i></div>
                <h5 class="modal-title">Sửa bài học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Chương</label>
                        <select name="module_id" id="editLessonModule" class="cm-ctrl" required>
                            @foreach ($course->modules as $module)
                                <option value="{{ $module->id }}">{{ $module->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Tiêu đề bài học</label>
                        <input type="text" name="title" id="editLessonTitle" class="cm-ctrl" required>
                    </div>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Link video / tài liệu ngoài</label>
                        <input type="url" name="video_url" id="editLessonVideo" class="cm-ctrl"
                            placeholder="https://...">
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Tài liệu đính kèm mới</label>
                        <input type="file" name="attachment" class="cm-ctrl"
                            style="padding:0.5rem 0.75rem; cursor:pointer;">
                        <div class="cm-hint">Bỏ trống nếu không thay đổi</div>
                    </div>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Trạng thái xuất bản</label>
                        <select name="status" id="editLessonStatus" class="cm-ctrl">
                            <option value="published">Published - mở cho học sinh</option>
                            <option value="draft">Draft - bản nháp</option>
                            <option value="hidden">Hidden - ẩn khỏi học sinh</option>
                            <option value="archived">Archived - lưu trữ</option>
                        </select>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Mở từ thời điểm</label>
                        <input type="datetime-local" name="available_from" id="editLessonAvailableFrom" class="cm-ctrl">
                        <div class="cm-hint">Bỏ trống nếu mở ngay.</div>
                    </div>
                </div>

                <div class="cm-field">
                    <label class="cm-label">Nội dung chi tiết</label>
                    <textarea name="content" id="editLessonContent" class="cm-ctrl" rows="4"></textarea>
                    <div class="cm-ai-actions">
                        <button type="button" class="cm-ai-btn" data-ai-draft="lesson_summary">
                            <i class="fas fa-wand-magic-sparkles"></i>AI tóm tắt từ tài liệu/bài học
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="cm-btn cm-btn-amber">
                    <i class="fas fa-save"></i> Cập nhật bài học
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================
     5. MODAL: THÊM BÀI TẬP
     ============================================================ --}}
<div class="modal fade cm-modal" id="addCourseAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('assignments.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="course_id" value="{{ $course->id }}">

            <div class="modal-header">
                <div class="cm-header-icon icon-amber"><i class="fas fa-tasks"></i></div>
                <h5 class="modal-title">Thêm bài tập thực hành</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="cm-field">
                    <label class="cm-label">Tiêu đề bài tập</label>
                    <input type="text" name="title" id="addAssignmentTitle" class="cm-ctrl"
                        placeholder="VD: Bài tập thực hành HTML cơ bản" required>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Loại bài tập</label>
                        <select name="type" id="addAssignmentType" class="cm-ctrl" required>
                            <option value="file">Nộp file</option>
                            <option value="essay">Tự luận</option>
                            <option value="mixed">File + tự luận</option>
                        </select>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Thuộc bài học</label>
                        <select name="lesson_id" id="addAssignmentLesson" class="cm-ctrl" required>
                            <option value="" disabled selected>-- Chọn bài học --</option>
                            @foreach ($course->modules as $module)
                                <optgroup label="{{ $module->title }}">
                                    @foreach ($module->lessons as $lesson)
                                        <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <div class="cm-ai-actions">
                            <button type="button" class="cm-ai-btn" data-ai-draft="assignment">
                                <i class="fas fa-wand-magic-sparkles"></i>AI soạn bài tập
                            </button>
                        </div>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Hạn nộp (Deadline)</label>
                        <input type="datetime-local" name="due_date" class="cm-ctrl" required>
                    </div>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Trạng thái xuất bản</label>
                        <select name="status" class="cm-ctrl">
                            <option value="published">Published - mở cho học sinh</option>
                            <option value="draft">Draft - bản nháp</option>
                            <option value="hidden">Hidden - ẩn khỏi học sinh</option>
                            <option value="archived">Archived - lưu trữ</option>
                            <option value="archived">Archived - lưu trữ</option>
                        </select>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Mở từ thời điểm</label>
                        <input type="datetime-local" name="available_from" class="cm-ctrl">
                        <div class="cm-hint">Bỏ trống nếu mở ngay.</div>
                    </div>
                </div>

                <div class="cm-field">
                    <label class="cm-label">Nội dung yêu cầu</label>
                    <textarea name="instructions" id="addAssignmentInstructions" class="cm-ctrl" rows="5"
                        placeholder="Nhập yêu cầu chi tiết..."></textarea>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Thang điểm</label>
                        <input type="number" name="grading_scale" id="addAssignmentGradingScale" class="cm-ctrl" value="10" min="1" max="100">
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">AI hỗ trợ chấm</label>
                        <select name="ai_grading_enabled" class="cm-ctrl">
                            <option value="1">Bật AI hỗ trợ chấm</option>
                            <option value="0">Tắt AI hỗ trợ chấm</option>
                        </select>
                    </div>
                </div>

                <div class="cm-field">
                    <label class="cm-label">Tiêu chí chấm điểm</label>
                    <textarea name="grading_rubric" id="addAssignmentRubric" class="cm-ctrl" rows="4"
                        placeholder="VD: Đúng yêu cầu: 4 điểm&#10;Đầy đủ ý: 3 điểm&#10;Ví dụ minh họa: 2 điểm&#10;Trình bày rõ ràng: 1 điểm"></textarea>
                    <div class="cm-hint">AI sẽ ưu tiên chấm theo tiêu chí này để tránh nhận xét lan man.</div>
                    <div class="cm-ai-actions">
                        <button type="button" class="cm-ai-btn" data-ai-draft="rubric" data-ai-target="add">
                            <i class="fas fa-list-check"></i>AI tạo tiêu chí
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="cm-btn cm-btn-amber">
                    <i class="fas fa-save"></i> Lưu bài tập
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================
     6. MODAL: SỬA BÀI TẬP
     ============================================================ --}}
<div class="modal fade cm-modal" id="editAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editAssignmentForm" method="POST" class="modal-content">
            @csrf @method('PUT')
            <input type="hidden" name="course_id" value="{{ $course->id }}">

            <div class="modal-header">
                <div class="cm-header-icon icon-amber"><i class="fas fa-edit"></i></div>
                <h5 class="modal-title">Sửa bài tập</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="cm-field">
                    <label class="cm-label">Tiêu đề bài tập</label>
                    <input type="text" name="title" id="editAssignmentTitle" class="cm-ctrl" required>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Loại bài tập</label>
                        <select name="type" id="editAssignmentType" class="cm-ctrl" required>
                            <option value="file">Nộp file</option>
                            <option value="essay">Tự luận</option>
                            <option value="mixed">File + tự luận</option>
                        </select>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Thuộc bài học</label>
                        <select name="lesson_id" id="editAssignmentLesson" class="cm-ctrl" required>
                            @foreach ($course->modules as $module)
                                <optgroup label="{{ $module->title }}">
                                    @foreach ($module->lessons as $lesson)
                                        <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Hạn nộp (Deadline)</label>
                        <input type="datetime-local" name="due_date" id="editAssignmentDue" class="cm-ctrl"
                            required>
                    </div>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Trạng thái xuất bản</label>
                        <select name="status" id="editAssignmentStatus" class="cm-ctrl">
                            <option value="published">Published - mở cho học sinh</option>
                            <option value="draft">Draft - bản nháp</option>
                            <option value="hidden">Hidden - ẩn khỏi học sinh</option>
                            <option value="archived">Archived - lưu trữ</option>
                        </select>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Mở từ thời điểm</label>
                        <input type="datetime-local" name="available_from" id="editAssignmentAvailableFrom" class="cm-ctrl">
                        <div class="cm-hint">Bỏ trống nếu mở ngay.</div>
                    </div>
                </div>

                <div class="cm-field">
                    <label class="cm-label">Nội dung yêu cầu</label>
                    <textarea name="instructions" id="editAssignmentInstructions" class="cm-ctrl" rows="5"></textarea>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Thang điểm</label>
                        <input type="number" name="grading_scale" id="editAssignmentGradingScale" class="cm-ctrl"
                            value="10" min="1" max="100">
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">AI hỗ trợ chấm</label>
                        <select name="ai_grading_enabled" id="editAssignmentAiEnabled" class="cm-ctrl">
                            <option value="1">Bật AI hỗ trợ chấm</option>
                            <option value="0">Tắt AI hỗ trợ chấm</option>
                        </select>
                    </div>
                </div>

                <div class="cm-field">
                    <label class="cm-label">Tiêu chí chấm điểm</label>
                    <textarea name="grading_rubric" id="editAssignmentGradingRubric" class="cm-ctrl" rows="4"></textarea>
                    <div class="cm-hint">AI sẽ ưu tiên chấm theo tiêu chí này.</div>
                    <div class="cm-ai-actions">
                        <button type="button" class="cm-ai-btn" data-ai-draft="rubric" data-ai-target="edit">
                            <i class="fas fa-list-check"></i>AI tạo tiêu chí
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="cm-btn cm-btn-amber">
                    <i class="fas fa-save"></i> Cập nhật bài tập
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================
     7. MODAL: XEM BÀI NỘP (XL)
     ============================================================ --}}
<div class="modal fade cm-modal " id="viewSubmissionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="cm-modal-header-strip">
                <div class="cm-header-icon icon-green"><i class="fas fa-users"></i></div>
                <h5 class="modal-title" id="modal-assignment-name">Danh sách nộp bài</h5>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="cm-table">
                        <thead>
                            <tr>
                                <th>Học sinh</th>
                                <th>Trạng thái</th>
                                <th>Thời gian nộp</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="submissions-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     8. MODAL: TẠO ĐỀ THI TRẮC NGHIỆM
     ============================================================ --}}
<div class="modal fade cm-modal" id="addQuizModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('quizzes.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="course_id" value="{{ $course->id }}">

            <div class="modal-header">
                <div class="cm-header-icon icon-violet"><i class="fas fa-random"></i></div>
                <h5 class="modal-title">Tạo đề thi trắc nghiệm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Tên bài thi</label>
                        <input type="text" name="title" id="addQuizTitle" class="cm-ctrl" placeholder="VD: Kiểm tra giữa kỳ..."
                            required>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Thời gian (phút)</label>
                        <input type="number" name="time_limit" id="addQuizTimeLimit" class="cm-ctrl" value="30" min="1"
                            required>
                    </div>
                </div>

                <div class="cm-field">
                    <label class="cm-label">Nguồn AI gợi ý</label>
                    <select id="addQuizLessonSource" class="cm-ctrl">
                        <option value="" disabled selected>-- Chọn bài hoặc chương để AI gợi ý quiz --</option>
                        @foreach ($course->modules as $module)
                            <optgroup label="{{ $module->title }}">
                                <option value="module:{{ $module->id }}">Toàn chương: {{ $module->title }}</option>
                                @foreach ($module->lessons as $lesson)
                                    <option value="lesson:{{ $lesson->id }}">{{ $lesson->title }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="cm-ai-actions">
                        <button type="button" class="cm-ai-btn" data-ai-draft="quiz">
                            <i class="fas fa-wand-magic-sparkles"></i>AI gợi ý quiz
                        </button>
                    </div>
                </div>

                <div class="cm-section-title">
                    <i class="fas fa-layer-group"></i> Cấu trúc đề thi
                </div>

                <div class="cm-info-banner">
                    <i class="fas fa-magic"></i>
                    Hệ thống sẽ bốc ngẫu nhiên câu hỏi từ Ngân hàng và trộn đề riêng cho mỗi học sinh.
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Trạng thái xuất bản</label>
                        <select name="status" class="cm-ctrl">
                            <option value="published">Published - mở cho học sinh</option>
                            <option value="draft">Draft - bản nháp</option>
                            <option value="hidden">Hidden - ẩn khỏi học sinh</option>
                        </select>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Mở từ thời điểm</label>
                        <input type="datetime-local" name="available_from" class="cm-ctrl">
                        <div class="cm-hint">Bỏ trống nếu mở ngay.</div>
                    </div>
                </div>

                <div class="difficulty-grid">
                    <div class="diff-card">
                        <div class="diff-label diff-easy">Câu dễ</div>
                        <input type="number" name="easy_count" id="addQuizEasyCount" value="10" min="0" required>
                    </div>
                    <div class="diff-card">
                        <div class="diff-label diff-medium">Trung bình</div>
                        <input type="number" name="medium_count" id="addQuizMediumCount" value="5" min="0" required>
                    </div>
                    <div class="diff-card">
                        <div class="diff-label diff-hard">Câu khó</div>
                        <input type="number" name="hard_count" id="addQuizHardCount" value="5" min="0" required>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="cm-btn cm-btn-violet">
                    <i class="fas fa-random"></i> Tạo đề thi
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================
     SCRIPTS
     ============================================================ --}}
@push('scripts')
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ── TinyMCE init ──
            tinymce.init({
                selector: '#addAssignmentInstructions, #editAssignmentInstructions',
                height: 280,
                menubar: false,
                plugins: ['advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'anchor',
                    'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media',
                    'table', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image | code | help',
                content_style: "body { font-family: 'Be Vietnam Pro', sans-serif; font-size: 14px; }",
                paste_data_images: true,
                automatic_uploads: true,
                images_upload_url: '/tinymce/upload-image',
                valid_elements: 'p,b,strong,i,em,ul,ol,li,br,h1,h2,h3,h4,h5,h6,a[href|target],img[src|alt|width|height]',
                setup(editor) {
                    editor.on('change keyup', () => editor.save());
                }
            });

            // ── Fix Bootstrap modal + TinyMCE focus ──
            $(document).on('focusin', function(e) {
                if ($(e.target).closest('.tox-tinymce, .tox-tinymce-aux, .moxman-window').length) {
                    e.stopImmediatePropagation();
                }
            });

            // ── Trigger save on edit form submit ──
            document.getElementById('editAssignmentForm')?.addEventListener('submit', () => tinymce.triggerSave());
        });

        // ── Open edit assignment modal ──
        function openEditAssignmentModal(button) {
            const id = button.dataset.id;
            const title = JSON.parse(button.dataset.title || '""');
            const instructions = JSON.parse(button.dataset.instructions || '""');
            const dueDate = button.dataset.due;
            const lessonId = button.dataset.lesson;
            const status = button.dataset.status || 'published';
            const availableFrom = button.dataset.availableFrom || '';
            const type = button.dataset.type || 'file';
            const gradingRubric = JSON.parse(button.dataset.gradingRubric || '""');
            const gradingScale = button.dataset.gradingScale || '10';
            const aiEnabled = button.dataset.aiEnabled || '0';

            document.getElementById('editAssignmentForm').action = `/assignments/${id}`;
            document.getElementById('editAssignmentTitle').value = title;
            document.getElementById('editAssignmentType').value = type;
            document.getElementById('editAssignmentLesson').value = lessonId;
            document.getElementById('editAssignmentDue').value = dueDate;
            document.getElementById('editAssignmentStatus').value = status;
            document.getElementById('editAssignmentAvailableFrom').value = availableFrom;
            document.getElementById('editAssignmentInstructions').value = instructions;
            document.getElementById('editAssignmentGradingRubric').value = gradingRubric;
            document.getElementById('editAssignmentGradingScale').value = gradingScale;
            document.getElementById('editAssignmentAiEnabled').value = aiEnabled;

            setTimeout(() => {
                tinymce.get('editAssignmentInstructions')?.setContent(instructions);
            }, 300);

            new bootstrap.Modal(document.getElementById('editAssignmentModal')).show();
        }

        (function() {
            const aiDraftUrl = @json(route('ai.teaching-content.generate'));
            const courseId = @json($course->id);

            function editorContent(id) {
                return tinymce.get(id)?.getContent() || document.getElementById(id)?.value || '';
            }

            function setEditorContent(id, value) {
                const editor = tinymce.get(id);
                if (editor) {
                    editor.setContent(value || '');
                    editor.save();
                }
                const el = document.getElementById(id);
                if (el) el.value = value || '';
            }

            function selectedValue(id) {
                return document.getElementById(id)?.value || '';
            }

            function setValue(id, value) {
                const el = document.getElementById(id);
                if (el && value !== undefined && value !== null) {
                    el.value = value;
                }
            }

            function plainText(html) {
                const box = document.createElement('div');
                box.innerHTML = html || '';
                return box.textContent || box.innerText || '';
            }

            async function requestDraft(button, payload) {
                const oldText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>AI đang soạn...';

                try {
                    const res = await axios.post(aiDraftUrl, payload);
                    return res.data.draft || {};
                } catch (error) {
                    const message = error.response?.data?.message || 'AI chưa soạn được nội dung. Vui lòng thử lại.';
                    alert(message);
                    return null;
                } finally {
                    button.disabled = false;
                    button.innerHTML = oldText;
                }
            }

            function addAssignmentPayload(type) {
                return {
                    type,
                    course_id: courseId,
                    lesson_id: selectedValue('addAssignmentLesson'),
                    current_title: selectedValue('addAssignmentTitle'),
                    current_instructions: plainText(editorContent('addAssignmentInstructions')),
                };
            }

            function editAssignmentPayload(type) {
                return {
                    type,
                    course_id: courseId,
                    lesson_id: selectedValue('editAssignmentLesson'),
                    current_title: selectedValue('editAssignmentTitle'),
                    current_instructions: plainText(editorContent('editAssignmentInstructions')),
                };
            }

            document.addEventListener('click', async function(e) {
                const button = e.target.closest('[data-ai-draft]');
                if (!button) return;

                const type = button.dataset.aiDraft;

                if (type === 'assignment') {
                    if (!selectedValue('addAssignmentLesson')) {
                        alert('Bạn chọn bài học trước để AI soạn bài tập nhé.');
                        return;
                    }

                    const draft = await requestDraft(button, addAssignmentPayload('assignment'));
                    if (!draft) return;

                    setValue('addAssignmentTitle', draft.title);
                    setValue('addAssignmentType', draft.type || 'mixed');
                    setEditorContent('addAssignmentInstructions', draft.instructions);
                    setValue('addAssignmentRubric', draft.grading_rubric);
                    setValue('addAssignmentGradingScale', draft.grading_scale || 10);
                    return;
                }

                if (type === 'rubric') {
                    const target = button.dataset.aiTarget === 'edit' ? 'edit' : 'add';
                    const payload = target === 'edit' ? editAssignmentPayload('rubric') : addAssignmentPayload('rubric');

                    if (!payload.lesson_id && !payload.current_instructions) {
                        alert('Bạn chọn bài học hoặc nhập yêu cầu bài tập trước để AI tạo tiêu chí nhé.');
                        return;
                    }

                    const draft = await requestDraft(button, payload);
                    if (!draft) return;

                    if (target === 'edit') {
                        setValue('editAssignmentGradingRubric', draft.grading_rubric);
                        setValue('editAssignmentGradingScale', draft.grading_scale || 10);
                    } else {
                        setValue('addAssignmentRubric', draft.grading_rubric);
                        setValue('addAssignmentGradingScale', draft.grading_scale || 10);
                    }
                    return;
                }

                if (type === 'quiz') {
                    const quizSource = selectedValue('addQuizLessonSource');
                    if (!quizSource) {
                        alert('Bạn chọn bài học nguồn trước để AI gợi ý quiz nhé.');
                        return;
                    }

                    const [sourceType, sourceId] = quizSource.split(':');

                    const draft = await requestDraft(button, {
                        type: 'quiz',
                        course_id: courseId,
                        lesson_id: sourceType === 'lesson' ? sourceId : null,
                        module_id: sourceType === 'module' ? sourceId : null,
                    });
                    if (!draft) return;

                    setValue('addQuizTitle', draft.title);
                    setValue('addQuizTimeLimit', draft.time_limit || 20);
                    setValue('addQuizEasyCount', draft.easy_count ?? 5);
                    setValue('addQuizMediumCount', draft.medium_count ?? 5);
                    setValue('addQuizHardCount', draft.hard_count ?? 2);
                    return;
                }

                if (type === 'lesson_summary') {
                    if (!window.currentEditLessonId) {
                        alert('Bạn mở bài học cần sửa trước để AI tóm tắt nhé.');
                        return;
                    }

                    const draft = await requestDraft(button, {
                        type: 'lesson_summary',
                        course_id: courseId,
                        lesson_id: window.currentEditLessonId,
                        current_title: selectedValue('editLessonTitle'),
                        source_text: plainText(editorContent('editLessonContent')),
                    });
                    if (!draft) return;

                    setValue('editLessonTitle', draft.title);
                    setEditorContent('editLessonContent', draft.content);
                }
            });
        })();
    </script>
@endpush
