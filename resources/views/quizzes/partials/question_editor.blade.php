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

    <div class="editor-guidance" data-editor-guidance><i class="fa-solid fa-wand-magic-sparkles"></i><span>Thứ tự phương án sẽ được xáo trộn khi phát đề.</span></div>
</div>

<div class="modal-footer question-editor-footer">
    <span class="footer-assurance"><i class="fa-solid fa-shield-halved"></i> Bài đã phát không bị thay đổi</span>
    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Hủy</button>
    <button type="submit" class="btn-modal-submit"><i class="fa-solid fa-floppy-disk"></i> {{ $isEdit ? 'Lưu thay đổi' : 'Lưu vào kho' }}</button>
</div>
