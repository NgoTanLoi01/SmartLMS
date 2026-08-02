{{-- ============================================================
     SHARED MODAL STYLES — inject once per page
     ============================================================ --}}
@once
    @push('styles')
        @vite('resources/css/pages/course-modals.css')
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
                <div class="cm-header-icon icon-blue"><i class="fa-solid fa-folder-plus"></i></div>
                <h5 class="modal-title">Thêm chương mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
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
                    <i class="fa-solid fa-check"></i> Lưu chương
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
                <div class="cm-header-icon icon-amber"><i class="fa-solid fa-folder"></i></div>
                <h5 class="modal-title">Sửa chương</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body">
                <div class="cm-field">
                    <label class="cm-label">Tên chương học</label>
                    <input type="text" name="title" id="editModuleTitle" class="cm-ctrl" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="cm-btn cm-btn-amber">
                    <i class="fa-solid fa-save"></i> Cập nhật
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
                <div class="cm-header-icon icon-blue"><i class="fa-solid fa-book-open"></i></div>
                <h5 class="modal-title">Thêm bài học mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
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
                            accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.png,.jpg,.jpeg,.webp"
                            style="padding:0.5rem 0.75rem; cursor:pointer;">
                        <div class="cm-hint">PDF, Office, ZIP hoặc ảnh PNG/JPG/WebP — tối đa 20 MB</div>
                    </div>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Trạng thái xuất bản</label>
                        <select name="status" class="cm-ctrl">
                            <option value="published">Đã xuất bản - học viên có thể xem</option>
                            <option value="draft">Bản nháp</option>
                            <option value="hidden">Tạm ẩn khỏi học viên</option>
                            <option value="archived">Đã lưu trữ</option>
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
                    <i class="fa-solid fa-plus"></i> Lưu bài học
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
                <div class="cm-header-icon icon-amber"><i class="fa-solid fa-edit"></i></div>
                <h5 class="modal-title">Sửa bài học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
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
                            accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.png,.jpg,.jpeg,.webp"
                            style="padding:0.5rem 0.75rem; cursor:pointer;">
                        <div class="cm-hint">PDF, Office, ZIP hoặc ảnh PNG/JPG/WebP — tối đa 20 MB</div>
                    </div>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Trạng thái xuất bản</label>
                        <select name="status" id="editLessonStatus" class="cm-ctrl">
                            <option value="published">Đã xuất bản - học viên có thể xem</option>
                            <option value="draft">Bản nháp</option>
                            <option value="hidden">Tạm ẩn khỏi học viên</option>
                            <option value="archived">Đã lưu trữ</option>
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
                            <i class="fa-solid fa-wand-magic-sparkles"></i>AI tóm tắt từ tài liệu/bài học
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="cm-btn cm-btn-amber">
                    <i class="fa-solid fa-save"></i> Cập nhật bài học
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
                <div class="cm-header-icon icon-amber"><i class="fa-solid fa-list-check"></i></div>
                <h5 class="modal-title">Thêm bài tập thực hành</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
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
                                <i class="fa-solid fa-wand-magic-sparkles"></i>AI soạn bài tập
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
                            <option value="published">Đã xuất bản - học viên có thể xem</option>
                            <option value="draft">Bản nháp</option>
                            <option value="hidden">Tạm ẩn khỏi học viên</option>
                            <option value="archived">Đã lưu trữ</option>
                            <option value="archived">Đã lưu trữ</option>
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
                            <i class="fa-solid fa-list-check"></i>AI tạo tiêu chí
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="cm-btn cm-btn-amber">
                    <i class="fa-solid fa-save"></i> Lưu bài tập
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
                <div class="cm-header-icon icon-amber"><i class="fa-solid fa-edit"></i></div>
                <h5 class="modal-title">Sửa bài tập</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
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
                            <option value="published">Đã xuất bản - học viên có thể xem</option>
                            <option value="draft">Bản nháp</option>
                            <option value="hidden">Tạm ẩn khỏi học viên</option>
                            <option value="archived">Đã lưu trữ</option>
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
                            <i class="fa-solid fa-list-check"></i>AI tạo tiêu chí
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="cm-btn cm-btn-amber">
                    <i class="fa-solid fa-save"></i> Cập nhật bài tập
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
                <div class="cm-header-icon icon-green"><i class="fa-solid fa-users"></i></div>
                <h5 class="modal-title" id="modal-assignment-name">Danh sách nộp bài</h5>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="cm-table">
                        <thead>
                            <tr>
                                <th>Học viên</th>
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
     8. MODAL: TẠO ĐỀ THI HỖN HỢP
     ============================================================ --}}
<div class="modal fade cm-modal" id="addQuizModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form action="{{ route('quizzes.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="course_id" value="{{ $course->id }}">

            <div class="modal-header">
                <div class="cm-header-icon icon-violet"><i class="fa-solid fa-random"></i></div>
                <h5 class="modal-title">Tạo đề thi hỗn hợp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
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
                    <label class="cm-label">Số lượt làm tối đa</label>
                    <input type="number" name="max_attempts" class="cm-ctrl" value="1" min="1" max="10" required>
                    <div class="cm-hint">Nếu có ca thi, giới hạn này được tính riêng cho từng ca.</div>
                </div>

                <div class="cm-field">
                    <label class="cm-label">Nguồn AI gợi ý</label>
                    <select id="addQuizLessonSource" class="cm-ctrl">
                        <option value="" disabled selected>-- Chọn bài hoặc chương để AI gợi ý bài kiểm tra --</option>
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
                            <i class="fa-solid fa-wand-magic-sparkles"></i>AI gợi ý quiz
                        </button>
                    </div>
                </div>

                <div class="cm-section-title">
                    <i class="fa-solid fa-layer-group"></i> Cấu trúc đề thi
                </div>

                <div class="cm-info-banner">
                    <i class="fa-solid fa-sliders"></i>
                    <span>Nhập chính xác số câu cần lấy ở từng hình thức và độ khó. Số “Có” là lượng câu hiện sẵn sàng trong ngân hàng.</span>
                </div>

                <div class="cm-row cols-2">
                    <div class="cm-field">
                        <label class="cm-label">Trạng thái xuất bản</label>
                        <select name="status" class="cm-ctrl">
                            <option value="published">Đã xuất bản - học viên có thể xem</option>
                            <option value="draft">Bản nháp</option>
                            <option value="hidden">Tạm ẩn khỏi học viên</option>
                        </select>
                    </div>
                    <div class="cm-field">
                        <label class="cm-label">Mở từ thời điểm</label>
                        <input type="datetime-local" name="available_from" class="cm-ctrl">
                        <div class="cm-hint">Bỏ trống nếu mở ngay.</div>
                    </div>
                </div>

                @php
                    $quizTypeIcons = [
                        'single_choice' => 'fa-circle-dot',
                        'multiple_choice' => 'fa-square-check',
                        'true_false_group' => 'fa-toggle-on',
                        'fill_blank' => 'fa-i-cursor',
                        'numeric' => 'fa-calculator',
                    ];
                @endphp
                <div class="quiz-distribution" data-quiz-distribution>
                    <div class="quiz-dist-head"><span>Hình thức</span><span class="diff-easy">Dễ</span><span class="diff-medium">Trung bình</span><span class="diff-hard">Khó</span><span>Tổng</span></div>
                    @foreach($quizQuestionTypeLabels as $type => $typeLabel)
                        <div class="quiz-dist-row" data-distribution-row>
                            <div class="quiz-dist-type"><span><i class="fa-solid {{ $quizTypeIcons[$type] ?? 'fa-circle-question' }}"></i></span><strong>{{ $typeLabel }}</strong></div>
                            @foreach($quizDifficultyLabels as $difficulty => $difficultyLabel)
                                @php
                                    $available = (int) data_get($quizQuestionAvailability, "{$type}.{$difficulty}", 0);
                                    $fieldId = $type === 'single_choice'
                                        ? ['easy' => 'addQuizEasyCount', 'medium' => 'addQuizMediumCount', 'hard' => 'addQuizHardCount'][$difficulty]
                                        : "quizDist_{$type}_{$difficulty}";
                                    $previousValue = data_get(old('question_distribution', []), "{$type}.{$difficulty}");
                                    $defaultValue = $previousValue !== null
                                        ? min(max((int) $previousValue, 0), $available)
                                        : ($type === 'single_choice' && $difficulty === 'easy' ? min(5, $available) : 0);
                                @endphp
                                <label class="quiz-dist-cell">
                                    <input type="number" name="question_distribution[{{ $type }}][{{ $difficulty }}]" id="{{ $fieldId }}" value="{{ $defaultValue }}" min="0" max="{{ $available }}" data-dist-input data-type="{{ $type }}" data-difficulty="{{ $difficulty }}" data-available="{{ $available }}" aria-label="{{ $typeLabel }} - {{ $difficultyLabel }}">
                                    <small>Có {{ $available }}</small>
                                </label>
                            @endforeach
                            <strong class="quiz-dist-total" data-row-total>0</strong>
                        </div>
                    @endforeach
                    <div class="quiz-dist-summary"><span><i class="fa-solid fa-list-check"></i> Tổng số câu trong đề</span><strong data-grand-total>0 câu</strong></div>
                </div>
                <div class="quiz-dist-error" data-distribution-error hidden>Hãy chọn ít nhất một câu hỏi.</div>
                <div class="quiz-ai-advice" data-ai-matrix-advice hidden>
                    <div class="quiz-ai-advice__title"><i class="fa-solid fa-wand-magic-sparkles"></i> Đề xuất của AI</div>
                    <p data-ai-matrix-rationale></p>
                    <div data-ai-shortage-list></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="cm-btn cm-btn-violet">
                    <i class="fa-solid fa-random"></i> Tạo đề thi
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const distribution = document.querySelector('[data-quiz-distribution]');
            if (!distribution) return;
            const inputs = Array.from(distribution.querySelectorAll('[data-dist-input]'));
            const error = document.querySelector('[data-distribution-error]');
            const advice = document.querySelector('[data-ai-matrix-advice]');
            const rationale = document.querySelector('[data-ai-matrix-rationale]');
            const shortageList = document.querySelector('[data-ai-shortage-list]');
            const typeLabels = @json($quizQuestionTypeLabels);
            const difficultyLabels = @json($quizDifficultyLabels);
            const generatorUrl = @json(route('quizzes.ai_generate'));
            const courseId = @json($course->id);

            const refresh = () => {
                inputs.forEach(input => {
                    const available = Number(input.dataset.available) || 0;
                    const value = Number(input.value) || 0;
                    input.value = Math.max(0, Math.min(value, available));
                });

                let grandTotal = 0;
                distribution.querySelectorAll('[data-distribution-row]').forEach(row => {
                    const total = Array.from(row.querySelectorAll('[data-dist-input]')).reduce((sum, input) => sum + (Number(input.value) || 0), 0);
                    row.querySelector('[data-row-total]').textContent = total;
                    row.classList.toggle('has-selection', total > 0);
                    grandTotal += total;
                });
                distribution.querySelector('[data-grand-total]').textContent = `${grandTotal} câu`;
                error.hidden = grandTotal > 0;
                inputs[0]?.setCustomValidity(grandTotal > 0 ? '' : 'Hãy chọn ít nhất một câu hỏi.');
            };

            inputs.forEach(input => input.addEventListener('input', () => {
                delete input.dataset.aiRequested;
                advice.hidden = true;
                const available = Number(input.dataset.available);
                if ((Number(input.value) || 0) > available) input.value = available;
                if ((Number(input.value) || 0) < 0) input.value = 0;
                refresh();
            }));
            window.refreshQuizDistribution = refresh;
            window.quizDistributionInventory = () => inputs.reduce((result, input) => {
                result[input.dataset.type] ??= {};
                result[input.dataset.type][input.dataset.difficulty] = Number(input.dataset.available) || 0;
                return result;
            }, {});
            window.quizDistributionTotal = () => inputs.reduce((sum, input) => sum + (Number(input.value) || 0), 0);
            window.applyAiQuizDistribution = (matrix, explanation = '') => {
                const shortages = [];
                inputs.forEach(input => {
                    const requested = Number(matrix?.[input.dataset.type]?.[input.dataset.difficulty]) || 0;
                    const available = Number(input.dataset.available) || 0;
                    input.dataset.aiRequested = requested;
                    input.value = Math.min(requested, available);
                    if (requested > available) {
                        shortages.push({
                            type: input.dataset.type,
                            difficulty: input.dataset.difficulty,
                            missing: requested - available,
                        });
                    }
                });
                refresh();
                rationale.textContent = explanation || 'AI đã cân đối hình thức câu hỏi, độ khó và thời gian làm bài.';
                shortageList.innerHTML = shortages.length === 0
                    ? '<div class="quiz-ai-stock-ok"><i class="fa-solid fa-circle-check"></i> Ngân hàng hiện đủ câu cho cơ cấu được đề xuất.</div>'
                    : `<div class="quiz-ai-shortage-title">Cần sinh bổ sung ${shortages.reduce((sum, item) => sum + item.missing, 0)} câu:</div>${shortages.map(item => {
                        const params = new URLSearchParams({course_id: courseId, question_type: item.type, difficulty: item.difficulty, quantity: Math.min(item.missing, 20)});
                        return `<a class="quiz-ai-shortage" href="${generatorUrl}?${params.toString()}"><span>${typeLabels[item.type]} · ${difficultyLabels[item.difficulty]}</span><strong>Thiếu ${item.missing} câu <i class="fa-solid fa-arrow-up-right-from-square"></i></strong></a>`;
                    }).join('')}`;
                advice.hidden = false;
            };
            refresh();
        });
    </script>
@endpush

{{-- ============================================================
     9. MODAL: KIỂM TRA CHẤT LƯỢNG KHÓA HỌC
     ============================================================ --}}
<div class="modal fade cm-modal" id="courseQualityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="cm-header-icon icon-amber"><i class="fa-solid fa-shield-halved"></i></div>
                <h5 class="modal-title">Kiểm tra chất lượng khóa học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div id="course-quality-content" class="text-muted small">
                    Bấm kiểm tra để hệ thống rà soát nội dung khóa học.
                </div>
            </div>
        </div>
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
                        alert('Bạn chọn bài học nguồn trước để AI gợi ý bài kiểm tra nhé.');
                        return;
                    }

                    const [sourceType, sourceId] = quizSource.split(':');

                    const draft = await requestDraft(button, {
                        type: 'quiz',
                        course_id: courseId,
                        lesson_id: sourceType === 'lesson' ? sourceId : null,
                        module_id: sourceType === 'module' ? sourceId : null,
                        requirements: {
                            total_questions: Math.max(window.quizDistributionTotal?.() || 0, 10),
                            inventory: window.quizDistributionInventory?.() || {},
                        },
                    });
                    if (!draft) return;

                    setValue('addQuizTitle', draft.title);
                    setValue('addQuizTimeLimit', draft.time_limit || 20);
                    window.applyAiQuizDistribution?.(draft.question_distribution || {}, draft.rationale || '');
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

        (function() {
            const checkBtn = document.getElementById('course-quality-check-btn');
            const content = document.getElementById('course-quality-content');
            const modalEl = document.getElementById('courseQualityModal');
            if (!checkBtn || !content || !modalEl) return;

            const esc = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            function severityLabel(level) {
                return {
                    high: 'Cần xử lý',
                    medium: 'Nên cải thiện',
                    low: 'Gợi ý',
                }[level] || 'Gợi ý';
            }

            function renderReport(data) {
                const summary = data.summary || {};
                const issues = Array.isArray(data.issues) ? data.issues : [];

                if (!issues.length) {
                    content.innerHTML = `
                        <div class="cm-info-banner mb-0">
                            <i class="fa-solid fa-circle-check"></i>
                            Chưa phát hiện vấn đề lớn. Khóa học đang khá ổn để vận hành.
                        </div>`;
                    return;
                }

                content.innerHTML = `
                    <div class="quality-summary">
                        <div class="quality-stat"><div class="quality-stat-label">Tổng vấn đề</div><div class="quality-stat-value">${esc(summary.total || 0)}</div></div>
                        <div class="quality-stat"><div class="quality-stat-label">Cần xử lý</div><div class="quality-stat-value">${esc(summary.high || 0)}</div></div>
                        <div class="quality-stat"><div class="quality-stat-label">Nên cải thiện</div><div class="quality-stat-value">${esc(summary.medium || 0)}</div></div>
                        <div class="quality-stat"><div class="quality-stat-label">Gợi ý</div><div class="quality-stat-value">${esc(summary.low || 0)}</div></div>
                    </div>
                    <div class="quality-issues">
                        ${issues.map(issue => `
                            <div class="quality-issue ${esc(issue.severity || 'medium')}">
                                <div class="quality-issue-title">
                                    <span class="badge bg-${issue.severity === 'high' ? 'danger' : (issue.severity === 'low' ? 'secondary' : 'warning text-dark')} me-1">${esc(severityLabel(issue.severity))}</span>
                                    ${esc(issue.title || 'Mục cần kiểm tra')}
                                </div>
                                <div class="quality-issue-text">${esc(issue.message || '')}</div>
                                <div class="quality-issue-text"><strong>Gợi ý:</strong> ${esc(issue.suggestion || '')}</div>
                            </div>
                        `).join('')}
                    </div>`;
            }

            checkBtn.addEventListener('click', async function() {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
                content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><div class="mt-2">Đang kiểm tra nội dung khóa học...</div></div>';

                try {
                    const res = await axios.post(checkBtn.dataset.url, {});
                    renderReport(res.data || {});
                } catch (error) {
                    content.innerHTML = `<div class="alert alert-danger mb-0">${esc(error.response?.data?.message || 'Không kiểm tra được khóa học lúc này.')}</div>`;
                }
            });
        })();
    </script>
@endpush
