@php
    $isEdit = ($mode ?? 'add') === 'edit';
@endphp
<div class="modal-header question-editor-header">
    <div>
        <span class="editor-eyebrow">NGÂN HÀNG CÂU HỎI</span>
        <h5 class="modal-title"><i class="fa-solid {{ $isEdit ? 'fa-pen-to-square' : 'fa-circle-plus' }}"></i>{{ $isEdit ? 'Chỉnh sửa câu hỏi' : 'Tạo câu hỏi mới' }}</h5>
        <p>Chọn hình thức, khai báo đáp án và có thể gắn ngữ liệu dùng chung.</p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
</div>

<div class="modal-body question-editor" data-question-editor="{{ $editorId }}">
    <div class="type-picker" role="radiogroup" aria-label="Loại câu hỏi">
        @foreach([
            'single_choice' => ['fa-circle-dot', 'Một đáp án', 'Chọn một phương án'],
            'multiple_choice' => ['fa-square-check', 'Nhiều đáp án', 'Chọn đủ phương án'],
            'true_false_group' => ['fa-toggle-on', 'Đúng / Sai', 'Nhiều nhận định'],
            'fill_blank' => ['fa-i-cursor', 'Điền khuyết', 'Một hoặc nhiều ô'],
            'numeric' => ['fa-calculator', 'Trả lời số', 'Có khoảng sai số'],
            'essay' => ['fa-pen-to-square', 'Tự luận', 'Giáo viên chấm'],
            'code_debug' => ['fa-code', 'Sửa lỗi HTML/CSS', 'Mã và giải thích'],
        ] as $value => [$icon, $label, $hint])
            <label class="type-card">
                <input type="radio" name="question_type" value="{{ $value }}" @checked($value === 'single_choice')>
                <span class="type-card-icon"><i class="fa-solid {{ $icon }}"></i></span>
                <span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span>
            </label>
        @endforeach
    </div>

    <div class="editor-section editor-basics">
        <div class="row-g">
            <div class="col-flex-2">
                <label class="form-label-sm">Khóa học</label>
                <select name="course_id" data-field="course_id" class="form-ctrl" required>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(!$isEdit && request('course_id') == $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-flex-2">
                <label class="form-label-sm">Ngân hàng</label>
                <select name="question_bank_id" data-field="question_bank_id" class="form-ctrl">
                    <option value="">Tự chọn theo khóa học</option>
                    @foreach ($questionBanks as $bank)
                        <option value="{{ $bank->id }}" @selected(!$isEdit && request('question_bank_id') == $bank->id)>{{ $bank->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-flex-1">
                <label class="form-label-sm">Độ khó</label>
                <select name="difficulty" data-field="difficulty" class="form-ctrl" required>
                    <option value="easy">Dễ</option>
                    <option value="medium" selected>Trung bình</option>
                    <option value="hard">Khó</option>
                </select>
            </div>
            <div class="col-flex-full">
                <label class="form-label-sm">Ngữ liệu dùng chung <span>Không bắt buộc</span></label>
                <select name="quiz_passage_id" data-field="quiz_passage_id" class="form-ctrl">
                    <option value="">Câu hỏi độc lập</option>
                    @foreach($passages as $passage)
                        <option value="{{ $passage->id }}">{{ $passage->course->title ?? '' }} — {{ $passage->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-flex-full">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <label class="form-label-sm">Nội dung câu hỏi</label>
                    <span class="field-helper" data-placeholder-helper hidden>Dùng [[1]], [[2]] để đặt vị trí ô trống</span>
                </div>
                <textarea name="question_text" data-field="question_text" class="form-ctrl question-content-input" rows="4" placeholder="Nhập nội dung câu hỏi..." maxlength="10000" required></textarea>
            </div>
        </div>
    </div>

    <section class="editor-section answer-designer" data-answer-section="options">
        <div class="designer-heading">
            <div><strong data-options-title>Các phương án</strong><small data-options-hint>Chọn một đáp án đúng.</small></div>
            <button type="button" class="mini-add-button" data-add-option><i class="fa-solid fa-plus"></i> Thêm phương án</button>
        </div>
        <div data-option-rows></div>
    </section>

    <section class="editor-section answer-designer" data-answer-section="blanks" hidden>
        <div class="designer-heading">
            <div><strong>Đáp án cho từng ô trống</strong><small>Ngăn cách nhiều đáp án được chấp nhận bằng dấu |</small></div>
            <label class="case-toggle"><input type="checkbox" name="case_sensitive" value="1"> Phân biệt hoa/thường</label>
        </div>
        <div class="blank-preview" data-blank-preview>Thêm [[1]] vào nội dung để tạo ô trống đầu tiên.</div>
        <div data-blank-rows></div>
    </section>

    <section class="editor-section answer-designer" data-answer-section="numeric" hidden>
        <div class="designer-heading"><div><strong>Đáp án số</strong><small>Hệ thống chấp nhận giá trị nằm trong khoảng sai số.</small></div></div>
        <div class="numeric-grid">
            <div><label class="form-label-sm">Giá trị đúng</label><input type="number" step="any" name="numeric_answer" data-field="numeric_answer" class="form-ctrl" placeholder="Ví dụ: 3.14"></div>
            <div><label class="form-label-sm">Sai số ±</label><input type="number" step="any" min="0" name="numeric_tolerance" data-field="numeric_tolerance" value="0" class="form-ctrl"></div>
            <div><label class="form-label-sm">Đơn vị</label><input type="text" name="numeric_unit" data-field="numeric_unit" class="form-ctrl" placeholder="cm, kg, m/s..."></div>
        </div>
    </section>

    <section class="editor-section answer-designer" data-answer-section="essay" hidden>
        <div class="designer-heading"><div><strong>Cấu hình câu tự luận</strong><small>Giáo viên chấm theo từng tiêu chí sau khi thí sinh nộp bài.</small></div></div>
        <div class="numeric-grid">
            <div><label class="form-label-sm">Điểm tối đa</label><input type="number" step="0.25" min="0.25" max="100" name="max_score" data-field="essay_max_score" value="1" class="form-ctrl"></div>
            <div><label class="form-label-sm">Giới hạn từ</label><input type="number" min="10" max="5000" name="word_limit" data-field="word_limit" value="500" class="form-ctrl"></div>
            <label class="case-toggle align-self-end"><input type="checkbox" name="allow_attachments" value="1" data-field="allow_attachments"> Cho phép đính kèm tối đa 3 tệp</label>
        </div>
        <div class="mt-3">
            <label class="form-label-sm">Rubric chấm điểm</label>
            <textarea name="rubric_text" data-field="essay_rubric_text" class="form-ctrl" rows="4" placeholder="Mỗi dòng: Tên tiêu chí | điểm">Mức độ đáp ứng yêu cầu | 1</textarea>
            <div class="field-helper mt-1">Tổng điểm rubric phải bằng điểm tối đa. Ví dụ: Nội dung chính xác | 3</div>
        </div>
    </section>

    <section class="editor-section answer-designer" data-answer-section="code_debug" hidden>
        <div class="designer-heading"><div><strong>Cấu hình bài sửa lỗi HTML/CSS</strong><small>Mã chỉ được xem trước trong iframe sandbox, không chạy JavaScript.</small></div></div>
        <div class="numeric-grid">
            <div><label class="form-label-sm">Điểm tối đa</label><input type="number" step="0.25" min="0.25" max="100" name="max_score" data-field="code_max_score" value="1" class="form-ctrl"></div>
            <div><label class="form-label-sm">Phần giải thích</label><select name="explanation_mode" data-field="explanation_mode" class="form-ctrl"><option value="required">Bắt buộc</option><option value="optional">Không bắt buộc</option><option value="disabled">Không sử dụng</option></select></div>
            <div><label class="form-label-sm">Giới hạn từ giải thích</label><input type="number" min="10" max="2000" name="explanation_word_limit" data-field="explanation_word_limit" value="150" class="form-ctrl"></div>
        </div>
        <div class="mt-3"><label class="form-label-sm">Mã HTML/CSS có lỗi ban đầu</label><textarea name="starter_code" data-field="starter_code" class="form-ctrl" rows="9" maxlength="50000" spellcheck="false" style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace" placeholder="Nhập một tài liệu HTML hoàn chỉnh hoặc đoạn HTML/CSS cần sửa..."></textarea></div>
        <div class="mt-3"><label class="form-label-sm">Rubric chấm điểm</label><textarea name="rubric_text" data-field="code_rubric_text" class="form-ctrl" rows="4" placeholder="Mỗi dòng: Tên tiêu chí | điểm">Mã sửa đúng và hiển thị đúng | 1</textarea></div>
    </section>

    <div class="editor-guidance" data-editor-guidance><i class="fa-solid fa-wand-magic-sparkles"></i><span>Thứ tự phương án sẽ được xáo trộn khi phát đề.</span></div>
</div>

<div class="modal-footer question-editor-footer">
    <span class="footer-assurance"><i class="fa-solid fa-shield-halved"></i> Bài đã phát không bị thay đổi</span>
    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Hủy</button>
    <button type="submit" class="btn-modal-submit"><i class="fa-solid fa-floppy-disk"></i> {{ $isEdit ? 'Lưu thay đổi' : 'Lưu vào kho' }}</button>
</div>
