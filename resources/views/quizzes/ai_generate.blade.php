@extends('layouts.app')

@section('content')
    <style>
        /* --- CUSTOM CSS FOR AI GENERATOR PAGE --- */
        :root {
            --ai-gradient: linear-gradient(135deg, var(--sl-primary) 0%, var(--sl-ai) 100%);
            --ai-gradient-btn: linear-gradient(135deg, var(--sl-primary) 0%, var(--sl-primary-hover) 100%);
            --ai-bg-light: var(--sl-bg);
        }

        body {
            background-color: var(--ai-bg-light);
        }

        /* Card Header Gradient */
        .card-ai-config .card-header {
            background: var(--ai-gradient) !important;
            border-bottom: none;
        }

        .ai-tip-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, .09), rgba(33, 150, 243, .06));
            border: 1px solid rgba(102, 126, 234, .18) !important;
        }

        @media (min-width: 992px) {
            .card-ai-config.sticky-top {
                max-height: calc(100vh - 40px);
                overflow-y: auto;
                scrollbar-gutter: stable;
            }

            .card-ai-config .card-header {
                position: sticky;
                top: 0;
                z-index: 2;
            }
        }

        @media (max-width: 991px) {
            .card-ai-config.sticky-top {
                position: static !important;
                max-height: none;
                overflow: visible;
            }
        }

        /* Form inputs styling */
        .form-control.ai-input,
        .form-select.ai-input {
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control.ai-input:focus,
        .form-select.ai-input:focus {
            border-color: var(--sl-primary);
            box-shadow: var(--sl-focus-ring);
        }

        /* Generate Button */
        .btn-ai-generate {
            background: var(--ai-gradient-btn);
            border: none;
            border-radius: 8px;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-ai-generate:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
        }

        .btn-ai-generate:disabled {
            opacity: 0.7;
            transform: none;
        }

        /* Question Card Styling */
        .ai-question-card {
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .ai-question-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .q-badge {
            background: var(--ai-gradient) !important;
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 20px;
        }

        .option-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.2s;
        }

        .option-item.is-correct {
            background-color: rgba(40, 167, 69, 0.08);
            border-color: rgba(40, 167, 69, 0.3);
            color: #155724;
            font-weight: 600;
        }

        .explanation-box {
            background: linear-gradient(to right, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            border-left: 4px solid #764ba2;
            border-radius: 0 8px 8px 0;
            padding: 12px 15px;
        }

        /* Save Button */
        .btn-save-all {
            background: var(--ai-gradient);
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-save-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Gradient Text */
        .text-gradient {
            background: var(--ai-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Pulse Animation for Loading */
        @keyframes aiPulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
            }
        }

        .ai-pulse {
            animation: aiPulse 2s infinite;
            border-radius: 50%;
            background: var(--ai-gradient);
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .source-options {
            display: grid;
            gap: 10px;
        }

        .source-option {
            align-items: flex-start;
            background: #fff;
            border: 1px solid #e0e6ed;
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            gap: 12px;
            padding: 13px 14px;
            transition: all .2s ease;
        }

        .source-option:hover {
            border-color: #9aa9ff;
            box-shadow: 0 8px 22px rgba(102, 126, 234, .12);
        }

        .source-option input {
            margin-top: 4px;
        }

        .source-option strong {
            color: #202634;
            display: block;
            font-size: 13px;
            line-height: 1.35;
        }

        .source-option span {
            color: #6b7280;
            display: block;
            font-size: 12px;
            line-height: 1.45;
            margin-top: 2px;
        }

        .source-option:has(input:checked) {
            background: linear-gradient(135deg, rgba(102, 126, 234, .08), rgba(33, 150, 243, .06));
            border-color: #667eea;
            box-shadow: 0 10px 24px rgba(102, 126, 234, .15);
        }

        .ai-context-box {
            background: #f8fafc;
            border: 1px solid #e8edf5;
            border-radius: 14px;
            padding: 14px;
        }

        .ai-editor-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .ai-editor-control {
            border: 1px solid #dbe3ef;
            border-radius: 9px;
            font-size: 13px;
            padding: 9px 11px;
            width: 100%;
        }

        .ai-answer-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            display: flex;
            gap: 9px;
            margin-top: 8px;
            padding: 8px;
        }

        .ai-answer-row.is-correct {
            background: #f0fdf4;
            border-color: #86efac;
        }

        .ai-answer-row input[type="text"] {
            background: transparent;
            border: 0;
            flex: 1;
            min-width: 0;
            outline: 0;
        }

        .ai-quality {
            border-radius: 10px;
            font-size: 12px;
            margin-top: 14px;
            padding: 10px 12px;
        }

        .ai-quality.good {
            background: #f0fdf4;
            color: #166534;
        }

        .ai-quality.needs_review {
            background: #fff7ed;
            color: #9a3412;
        }

        .ai-quality ul {
            margin: 6px 0 0;
            padding-left: 18px;
        }

        .ai-manual-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 12px;
        }

        .ai-manual-panel {
            background: #f8fafc;
            border: 1px solid #dbe3ef;
            border-radius: 12px;
            padding: 14px;
        }

        .ai-code-editor {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            min-height: 220px;
            tab-size: 2;
            white-space: pre;
        }

        .ai-rubric-row {
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 1fr) 120px;
            margin-top: 8px;
        }

        .ai-check-control {
            align-items: center;
            display: flex;
            gap: 8px;
            min-height: 42px;
        }

        .question-type-help {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 9px;
            color: #1e40af;
            font-size: 11.5px;
            line-height: 1.5;
            margin-top: 8px;
            padding: 8px 10px;
        }

        .ai-form-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #b91c1c;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.5;
            margin-bottom: 12px;
            padding: 10px 12px;
        }

        .ai-field-error {
            color: #dc2626;
            display: block;
            font-size: 11.5px;
            font-weight: 600;
            margin-top: 6px;
        }

        #aiGenForm [aria-invalid="true"] {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .12) !important;
        }

        @media (max-width: 767px) {
            .ai-manual-grid {
                grid-template-columns: 1fr;
            }

            .ai-rubric-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="container py-5">
        <div class="row g-4">
            {{-- Cột trái: Cấu hình thông số --}}
            <div class="col-lg-4">
                <div class="alert ai-tip-card border-0 rounded-4 p-3 shadow-sm mb-4">
                    <div class="d-flex align-items-start">
                        <div class="me-3 text-primary h5 mb-0"><i class="fa-solid fa-lightbulb"></i></div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Mẹo nhỏ từ AI</h6>
                            <p class="small text-muted mb-0">
                                Chủ đề càng chi tiết, AI càng tạo câu hỏi chính xác. Với tự luận hoặc sửa lỗi HTML/CSS,
                                nên nêu rõ năng lực cần đánh giá và kết quả mong đợi.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card shadow border-0 card-ai-config mb-4 sticky-top" style="top: 20px;">
                    <div class="card-header py-3">
                        <h6 class="mb-0 fw-bold text-white d-flex align-items-center">
                            <i class="fa-solid fa-robot me-2"></i> Cấu hình AI
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <form id="aiGenForm" novalidate>
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Môn học / Khóa học</label>
                                <select class="form-select ai-input" id="course_id" required>
                                    <option value="">-- Chọn khóa học --</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>
                                            {{ $course->title }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text mt-2 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                                    Luôn chọn khóa học để xác định ngân hàng lưu câu hỏi, kể cả khi dùng chủ đề nhập tay.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Nguồn tạo câu hỏi</label>
                                <div class="source-options">
                                    <label class="source-option">
                                        <input class="form-check-input source-type-input" type="radio" name="source_type"
                                            value="course_content" checked>
                                        <span>
                                            <strong>Nội dung khóa học</strong>
                                            <span>Lấy từ mô tả khóa học, chương và nội dung bài học.</span>
                                        </span>
                                    </label>
                                    <label class="source-option">
                                        <input class="form-check-input source-type-input" type="radio" name="source_type"
                                            value="document">
                                        <span>
                                            <strong>Tài liệu upload</strong>
                                            <span>Dùng tài liệu đã xử lý trong kho tri thức của khóa học.</span>
                                        </span>
                                    </label>
                                    <label class="source-option">
                                        <input class="form-check-input source-type-input" type="radio" name="source_type"
                                            value="topic">
                                        <span>
                                            <strong>Chủ đề nhập tay</strong>
                                            <span>Tạo nhanh theo chủ đề, không cần khóa học có tài liệu.</span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-4 ai-context-box" id="courseContentOptions">
                                <label class="form-label fw-bold small text-uppercase text-muted">Phạm vi nội dung</label>
                                <select class="form-select ai-input mb-3" id="content_scope">
                                    <option value="course">Toàn bộ khóa học</option>
                                    <option value="module">Một chương / module</option>
                                    <option value="lesson">Một bài học cụ thể</option>
                                </select>

                                <div class="mb-3 d-none" id="moduleSelectWrap">
                                    <label class="form-label fw-bold small text-muted">Chọn chương</label>
                                    <select class="form-select ai-input" id="module_id">
                                        <option value="">-- Chọn chương --</option>
                                    </select>
                                </div>

                                <div class="d-none" id="lessonSelectWrap">
                                    <label class="form-label fw-bold small text-muted">Chọn bài học</label>
                                    <select class="form-select ai-input" id="lesson_id">
                                        <option value="">-- Chọn bài học --</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">
                                    Chủ đề trọng tâm
                                    <span class="text-muted fw-normal" id="topicOptionalLabel">(không bắt buộc)</span>
                                </label>
                                <input type="text" class="form-control ai-input" id="topic"
                                    placeholder="VD: HTML semantic, Bootstrap Grid, React Hooks...">
                                <div class="form-text mt-2" id="topicHelpText">
                                    Bỏ trống nếu muốn AI tự chọn ý quan trọng từ phạm vi nội dung đã chọn.
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Hình thức câu
                                        hỏi</label>
                                    <select class="form-select ai-input" id="question_type">
                                        @foreach (\App\Models\Question::typeLabels() as $type => $label)
                                            <option value="{{ $type }}" @selected(request('question_type', 'single_choice') === $type)>
                                                {{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <div class="question-type-help" id="questionTypeHelp">AI sinh câu hỏi, đáp án và tự kiểm
                                        định chất lượng trước khi giáo viên duyệt.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Độ khó</label>
                                    <select class="form-select ai-input" id="difficulty">
                                        <option value="Dễ" @selected(request('difficulty') === 'easy')>Dễ</option>
                                        <option value="Trung bình" @selected(!request('difficulty') || request('difficulty') === 'medium')>Trung bình</option>
                                        <option value="Khó" @selected(request('difficulty') === 'hard')>Khó</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Số lượng</label>
                                    <input type="number" class="form-control ai-input" id="quantity"
                                        value="{{ min(20, max(1, (int) request('quantity', 5))) }}" min="1"
                                        max="20">
                                </div>
                            </div>

                            <div class="ai-form-error d-none" id="aiFormError" role="alert" aria-live="polite"></div>
                            <button type="submit" class="btn btn-primary btn-ai-generate w-100 py-3 fw-bold shadow-sm"
                                id="btnGenerate">
                                <i class="fa-solid fa-microchip me-2"></i> SINH CÂU HỎI
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            {{-- Cột phải: Kết quả hiển thị --}}
            <div class="col-lg-8">
                <div id="aiResultArea">
                    {{-- Trạng thái trống --}}
                    <div class="card border-0 shadow-sm text-center py-5 rounded-4" id="emptyState">
                        <div class="card-body py-5">
                            <div class="ai-pulse mx-auto mb-4">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            </div>
                            <h5 class="text-gradient fw-bold">Sẵn sàng sáng tạo!</h5>
                            <p class="text-muted small mt-2">Chỉ cần cấu hình bên trái, AI sẽ giúp Thầy/Cô soạn đề thi
                                trong
                                chớp mắt.</p>
                        </div>
                    </div>

                    {{-- Loading (Ẩn mặc định) --}}
                    <div class="text-center d-none my-5 py-5" id="loadingState">
                        <div class="spinner-border text-primary mb-4" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="fw-bold text-gradient">AI đang phân tích nguồn nội dung...</h5>
                        <p class="text-muted mt-2">Quá trình này mất 10-20s. Hãy pha một cốc cà phê nhé!</p>
                    </div>

                    {{-- Danh sách câu hỏi AI sinh ra (Ẩn mặc định) --}}
                    <div id="questionPreviewList" class="d-none">
                        <div
                            class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-4 shadow-sm">
                            <h5 class="fw-bold mb-0 text-gradient"><i class="fa-solid fa-list-check me-2"></i>Kết quả sinh
                                ra
                            </h5>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary fw-bold" id="btnReviewAll">
                                    <i class="fa-solid fa-shield-halved me-2"></i> KIỂM TRA LẠI
                                </button>
                                <button type="button" class="btn btn-primary btn-save-all fw-bold shadow-sm"
                                    id="btnSaveAll">
                                    <i class="fa-solid fa-cloud-arrow-up me-2"></i> LƯU VÀO NGÂN HÀNG
                                </button>
                            </div>
                        </div>

                        <div id="questionsContainer">
                            {{-- Các câu hỏi sẽ được Append vào đây bằng JS --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const processUrl = "{{ route('quizzes.ai_generate.process') }}";
        const saveUrl = "{{ route('quizzes.ai_generate.save') }}";
        const reviewUrl = "{{ route('quizzes.ai_generate.review') }}";
        const csrfToken = "{{ csrf_token() }}";
        const courseContextOptions = @json($courseContextOptions);

        let generatedQuestions = [];
        let tempIdCounter = 0; // Dùng để đánh dấu ID tạm thời cho việc xóa

        const courseSelect = document.getElementById('course_id');
        const sourceInputs = document.querySelectorAll('.source-type-input');
        const contentScopeSelect = document.getElementById('content_scope');
        const courseContentOptions = document.getElementById('courseContentOptions');
        const moduleSelectWrap = document.getElementById('moduleSelectWrap');
        const lessonSelectWrap = document.getElementById('lessonSelectWrap');
        const moduleSelect = document.getElementById('module_id');
        const lessonSelect = document.getElementById('lesson_id');
        const topicInput = document.getElementById('topic');
        const topicOptionalLabel = document.getElementById('topicOptionalLabel');
        const topicHelpText = document.getElementById('topicHelpText');
        const questionTypeSelect = document.getElementById('question_type');
        const questionTypeHelp = document.getElementById('questionTypeHelp');
        const aiFormError = document.getElementById('aiFormError');

        function selectedSourceType() {
            return document.querySelector('.source-type-input:checked')?.value || 'course_content';
        }

        function clearFormError() {
            aiFormError.classList.add('d-none');
            aiFormError.textContent = '';
            document.querySelectorAll('#aiGenForm .ai-field-error').forEach(error => error.remove());
            document.querySelectorAll('#aiGenForm [aria-invalid="true"]').forEach(field => field.removeAttribute(
                'aria-invalid'));
        }

        function showFormError(message, field = null) {
            aiFormError.textContent = message;
            aiFormError.classList.remove('d-none');
            if (field) {
                field.setAttribute('aria-invalid', 'true');
                const fieldError = document.createElement('span');
                fieldError.className = 'ai-field-error';
                fieldError.textContent = message;
                field.insertAdjacentElement('afterend', fieldError);
                const configCard = field.closest('.card-ai-config');
                if (configCard) configCard.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                window.setTimeout(() => field.focus({
                    preventScroll: true
                }), 180);
            }
        }

        function validateGenerationForm() {
            document.querySelectorAll('#aiGenForm [aria-invalid="true"]').forEach(field => field.removeAttribute(
                'aria-invalid'));
            if (!courseSelect.value) {
                return {
                    message: 'Vui lòng chọn khóa học để xác định ngân hàng lưu câu hỏi.',
                    field: courseSelect
                };
            }
            if (selectedSourceType() === 'topic' && !topicInput.value.trim()) {
                return {
                    message: 'Vui lòng nhập chủ đề trọng tâm để AI tạo câu hỏi.',
                    field: topicInput
                };
            }
            if (selectedSourceType() === 'course_content' && ['module', 'lesson'].includes(contentScopeSelect.value) && !
                moduleSelect.value) {
                return {
                    message: 'Vui lòng chọn chương hoặc module cần dùng.',
                    field: moduleSelect
                };
            }
            if (selectedSourceType() === 'course_content' && contentScopeSelect.value === 'lesson' && !lessonSelect.value) {
                return {
                    message: 'Vui lòng chọn bài học cần dùng.',
                    field: lessonSelect
                };
            }
            const quantity = Number(document.getElementById('quantity').value);
            if (!Number.isInteger(quantity) || quantity < 1 || quantity > 20) {
                return {
                    message: 'Số lượng câu hỏi phải từ 1 đến 20.',
                    field: document.getElementById('quantity')
                };
            }
            return null;
        }

        function fillSelect(select, options, placeholder) {
            select.innerHTML = `<option value="">${placeholder}</option>`;
            options.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.title;
                select.appendChild(option);
            });
        }

        function currentCourseData() {
            return courseContextOptions[courseSelect.value] || {
                modules: []
            };
        }

        function refreshModuleOptions() {
            const courseData = currentCourseData();
            fillSelect(moduleSelect, courseData.modules || [], '-- Chọn chương --');
            refreshLessonOptions();
        }

        function refreshLessonOptions() {
            const courseData = currentCourseData();
            let lessons = [];

            if (moduleSelect.value) {
                const selectedModule = (courseData.modules || []).find((module) => String(module.id) === moduleSelect
                .value);
                lessons = selectedModule?.lessons || [];
            } else {
                lessons = (courseData.modules || []).flatMap((module) => (module.lessons || []).map((lesson) => ({
                    ...lesson,
                    title: `${module.title} - ${lesson.title}`
                })));
            }

            fillSelect(lessonSelect, lessons, '-- Chọn bài học --');
        }

        function syncSourceUi() {
            const sourceType = selectedSourceType();
            const isCourseContent = sourceType === 'course_content';
            const isTopicOnly = sourceType === 'topic';

            courseContentOptions.classList.toggle('d-none', !isCourseContent);
            topicInput.required = isTopicOnly;
            topicOptionalLabel.textContent = isTopicOnly ? '(bắt buộc)' : '(không bắt buộc)';
            topicHelpText.textContent = isTopicOnly ?
                'Nhập rõ chủ đề để AI tạo câu hỏi, ví dụ: Bootstrap Grid hoặc HTML Form.' :
                'Bỏ trống nếu muốn AI tự chọn ý quan trọng từ phạm vi nội dung đã chọn.';

            syncScopeUi();
        }

        function syncScopeUi() {
            const scope = contentScopeSelect.value;
            const isCourseContent = selectedSourceType() === 'course_content';

            moduleSelectWrap.classList.toggle('d-none', !isCourseContent || !['module', 'lesson'].includes(scope));
            lessonSelectWrap.classList.toggle('d-none', !isCourseContent || scope !== 'lesson');
            moduleSelect.required = isCourseContent && ['module', 'lesson'].includes(scope);
            lessonSelect.required = isCourseContent && scope === 'lesson';

            if (!moduleSelect.required) moduleSelect.value = '';
            if (!lessonSelect.required) lessonSelect.value = '';
            if (scope === 'lesson') refreshLessonOptions();
        }

        courseSelect.addEventListener('change', refreshModuleOptions);
        moduleSelect.addEventListener('change', refreshLessonOptions);
        contentScopeSelect.addEventListener('change', syncScopeUi);
        sourceInputs.forEach((input) => input.addEventListener('change', syncSourceUi));
        questionTypeSelect.addEventListener('change', syncQuestionTypeHelp);
        document.getElementById('aiGenForm').addEventListener('input', clearFormError);
        document.getElementById('aiGenForm').addEventListener('change', clearFormError);

        function syncQuestionTypeHelp() {
            const help = {
                essay: 'AI tạo yêu cầu tự luận, giới hạn từ, điểm tối đa, rubric và hướng dẫn chấm. Giáo viên vẫn là người chấm.',
                code_debug: 'AI tạo mã HTML/CSS có lỗi, yêu cầu sửa, rubric và lời giải tham khảo. JavaScript không được phép.',
            };
            questionTypeHelp.textContent = help[questionTypeSelect.value] ||
                'AI sinh câu hỏi, đáp án và tự kiểm định chất lượng trước khi giáo viên duyệt.';
        }

        refreshModuleOptions();
        syncSourceUi();
        syncQuestionTypeHelp();

        // ==========================================
        // 1. XỬ LÝ SINH CÂU HỎI
        // ==========================================
        document.getElementById('aiGenForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            clearFormError();
            const validationError = validateGenerationForm();
            if (validationError) {
                showFormError(validationError.message, validationError.field);
                return;
            }

            document.getElementById('emptyState').classList.add('d-none');
            document.getElementById('questionPreviewList').classList.add('d-none');
            document.getElementById('loadingState').classList.remove('d-none');

            const btnGenerate = document.getElementById('btnGenerate');
            btnGenerate.disabled = true;
            btnGenerate.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> ĐANG XỬ LÝ...';

            const payload = {
                course_id: courseSelect.value,
                source_type: selectedSourceType(),
                content_scope: contentScopeSelect.value,
                module_id: moduleSelect.value,
                lesson_id: lessonSelect.value,
                topic: topicInput.value,
                difficulty: document.getElementById('difficulty').value,
                question_type: document.getElementById('question_type').value,
                quantity: document.getElementById('quantity').value
            };

            try {
                const response = await fetch(processUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                let data = await response.json();

                if (!response.ok) {
                    const message = data.error || data.message || Object.values(data.errors || {}).flat()[0] ||
                        'Không thể sinh câu hỏi.';
                    showFormError(message);
                    alert("Lỗi: " + message);
                    resetUI();
                    return;
                }

                if (data.queued) {
                    let completed = false;
                    for (let attempt = 0; attempt < 90; attempt++) {
                        const statusResponse = await fetch(data.status_url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const operation = await statusResponse.json();
                        if (operation.status === 'completed') {
                            data = operation.result || {};
                            completed = true;
                            break;
                        }
                        if (operation.status === 'failed') throw new Error(operation.message ||
                            'AI tạo câu hỏi thất bại.');
                        await new Promise(resolve => setTimeout(resolve, 2000));
                    }
                    if (!completed) throw new Error('AI xử lý quá lâu. Vui lòng thử lại sau.');
                }

                generatedQuestions = data.questions ? data.questions : data;

                if (!Array.isArray(generatedQuestions) || generatedQuestions.length === 0) {
                    alert("AI không thể tạo được câu hỏi. Vui lòng thử chủ đề khác!");
                    resetUI();
                    return;
                }

                // Gán ID tạm thời để dễ quản lý DOM
                generatedQuestions.forEach(q => q.tempId = tempIdCounter++);

                renderQuestions();

                document.getElementById('loadingState').classList.add('d-none');
                document.getElementById('questionPreviewList').classList.remove('d-none');

            } catch (error) {
                console.error(error);
                const message = error.message || 'Lỗi kết nối máy chủ AI. Vui lòng thử lại!';
                showFormError(message);
                alert(message);
                resetUI();
            } finally {
                btnGenerate.disabled = false;
                btnGenerate.innerHTML = '<i class="fa-solid fa-microchip me-2"></i> SINH CÂU HỎI';
            }
        });

        // ==========================================
        // 2. HIỂN THỊ CÂU HỎI
        // ==========================================
        function renderQuestions() {
            const container = document.getElementById('questionsContainer');
            container.innerHTML = generatedQuestions.map((q, index) => questionEditorHtml(q, index)).join('');
        }

        function escapeHtml(value) {
            const box = document.createElement('div');
            box.textContent = value ?? '';
            return box.innerHTML;
        }

        function questionEditorHtml(q, index) {
            const typeLabels = @json(\App\Models\Question::typeLabels());
            const quality = q.quality || {
                status: 'needs_review',
                score: 0,
                warnings: ['Chưa kiểm định lại sau khi chỉnh sửa.']
            };
            const warnings = quality.warnings || [];
            const qualityHtml =
                `<div class="ai-quality ${quality.status}"><strong><i class="fa-solid ${quality.status === 'good' ? 'fa-circle-check' : 'fa-triangle-exclamation'}"></i> Chất lượng ${quality.score ?? 0}/100</strong>${warnings.length ? `<ul>${warnings.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul>` : '<span class="ms-2">Không phát hiện vấn đề hoặc câu trùng.</span>'}</div>`;
            let answerHtml = '';

            if (['single_choice', 'multiple_choice'].includes(q.question_type)) {
                const inputType = q.question_type === 'single_choice' ? 'radio' : 'checkbox';
                answerHtml = (q.options || []).map((option, optionIndex) => {
                    const checked = (q.correct_indexes || [q.correct_index]).map(Number).includes(optionIndex);
                    return `<label class="ai-answer-row ${checked ? 'is-correct' : ''}"><input type="${inputType}" name="correct-${q.tempId}" data-correct-index="${optionIndex}" ${checked ? 'checked' : ''}><input type="text" value="${escapeHtml(option)}" data-option-index="${optionIndex}"></label>`;
                }).join('');
            } else if (q.question_type === 'true_false_group') {
                answerHtml = (q.statements || []).map((statement, statementIndex) =>
                    `<div class="ai-answer-row"><input type="text" value="${escapeHtml(statement.text)}" data-statement-text="${statementIndex}"><select class="form-select form-select-sm" style="width:90px" data-statement-value="${statementIndex}"><option value="1" ${statement.is_true ? 'selected' : ''}>Đúng</option><option value="0" ${!statement.is_true ? 'selected' : ''}>Sai</option></select></div>`
                    ).join('');
            } else if (q.question_type === 'fill_blank') {
                answerHtml = (q.blanks || []).map((blank, blankIndex) =>
                    `<div class="ai-answer-row"><strong>Ô ${blankIndex + 1}</strong><input type="text" value="${escapeHtml((blank.accepted || []).join(' | '))}" data-blank-index="${blankIndex}" placeholder="Ngăn cách các cách viết đúng bằng dấu |"></div>`
                    ).join('');
            } else if (q.question_type === 'essay') {
                answerHtml = manualEditorHtml(q, false);
            } else if (q.question_type === 'code_debug') {
                answerHtml = manualEditorHtml(q, true);
            } else {
                answerHtml =
                    `<div class="row g-2"><div class="col-md-4"><label class="ai-editor-label">Đáp án số</label><input class="ai-editor-control" type="number" step="any" value="${q.numeric_answer ?? ''}" data-numeric="numeric_answer"></div><div class="col-md-4"><label class="ai-editor-label">Sai số ±</label><input class="ai-editor-control" type="number" step="any" min="0" value="${q.numeric_tolerance ?? 0}" data-numeric="numeric_tolerance"></div><div class="col-md-4"><label class="ai-editor-label">Đơn vị</label><input class="ai-editor-control" value="${escapeHtml(q.numeric_unit || '')}" data-numeric="numeric_unit"></div></div>`;
            }

            const isManual = ['essay', 'code_debug'].includes(q.question_type);
            const explanationLabel = isManual ? 'Đáp án tham khảo / hướng dẫn chấm' : 'Giải thích đáp án';
            return `<div class="card shadow-sm ai-question-card mb-4" data-temp-id="${q.tempId}"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start mb-3"><div><span class="badge q-badge">Câu ${index + 1}</span><span class="badge bg-light text-dark ms-2">${escapeHtml(typeLabels[q.question_type] || q.question_type)}</span>${isManual ? '<span class="badge bg-warning-subtle text-warning-emphasis ms-2">Giáo viên chấm</span>' : ''}</div><button type="button" class="btn btn-sm border-0 btn-remove-q" title="Bỏ câu"><i class="fa-solid fa-trash-can text-danger"></i></button></div><label class="ai-editor-label">Nội dung câu hỏi</label><textarea class="ai-editor-control mb-3" rows="3" data-question-field="question">${escapeHtml(q.question || '')}</textarea><label class="ai-editor-label">${isManual ? 'Cấu hình chấm thủ công' : 'Đáp án'}</label>${answerHtml}<label class="ai-editor-label mt-3">${explanationLabel}</label><textarea class="ai-editor-control" rows="3" data-question-field="explanation">${escapeHtml(q.explanation || '')}</textarea>${qualityHtml}</div></div>`;
        }

        function manualEditorHtml(q, isCodeDebug) {
            const rubricHtml = (q.rubric || []).map((item, rubricIndex) =>
                `<div class="ai-rubric-row"><input class="ai-editor-control" value="${escapeHtml(item.criterion || '')}" data-rubric-criterion="${rubricIndex}" placeholder="Tên tiêu chí"><input class="ai-editor-control" type="number" min="0.25" max="100" step="0.25" value="${item.max_score ?? ''}" data-rubric-score="${rubricIndex}" aria-label="Điểm tối đa tiêu chí"></div>`
                ).join('');
            const commonFields =
                `<div><label class="ai-editor-label">Điểm tối đa</label><input class="ai-editor-control" type="number" min="0.25" max="100" step="0.25" value="${q.max_score ?? 10}" data-manual-field="max_score"></div>`;
            if (!isCodeDebug) {
                return `<div class="ai-manual-panel"><div class="ai-manual-grid">${commonFields}<div><label class="ai-editor-label">Giới hạn từ</label><input class="ai-editor-control" type="number" min="10" max="5000" value="${q.word_limit ?? 500}" data-manual-field="word_limit"></div><label class="ai-check-control"><input type="checkbox" ${q.allow_attachments ? 'checked' : ''} data-manual-field="allow_attachments"> Cho phép tệp đính kèm</label></div><label class="ai-editor-label">Rubric chấm điểm</label>${rubricHtml}</div>`;
            }
            return `<div class="ai-manual-panel"><div class="ai-manual-grid">${commonFields}<div><label class="ai-editor-label">Yêu cầu giải thích</label><select class="ai-editor-control" data-manual-field="explanation_mode"><option value="required" ${q.explanation_mode === 'required' ? 'selected' : ''}>Bắt buộc</option><option value="optional" ${q.explanation_mode === 'optional' ? 'selected' : ''}>Không bắt buộc</option><option value="disabled" ${q.explanation_mode === 'disabled' ? 'selected' : ''}>Không sử dụng</option></select></div><div><label class="ai-editor-label">Giới hạn từ</label><input class="ai-editor-control" type="number" min="10" max="2000" value="${q.explanation_word_limit ?? 150}" data-manual-field="explanation_word_limit" ${q.explanation_mode === 'disabled' ? 'disabled' : ''}></div></div><label class="ai-editor-label">Mã HTML/CSS có lỗi</label><textarea class="ai-editor-control ai-code-editor mb-3" data-manual-field="starter_code" spellcheck="false">${escapeHtml(q.starter_code || '')}</textarea><label class="ai-editor-label">Rubric chấm điểm</label>${rubricHtml}</div>`;
        }

        document.getElementById('questionsContainer').addEventListener('input', updateQuestionFromEditor);
        document.getElementById('questionsContainer').addEventListener('change', updateQuestionFromEditor);
        document.getElementById('questionsContainer').addEventListener('click', function(event) {
            const remove = event.target.closest('.btn-remove-q');
            if (!remove) return;
            const id = Number(remove.closest('[data-temp-id]').dataset.tempId);
            generatedQuestions = generatedQuestions.filter(question => question.tempId !== id);
            generatedQuestions.length ? renderQuestions() : resetUI();
        });

        function updateQuestionFromEditor(event) {
            const card = event.target.closest('[data-temp-id]');
            if (!card) return;
            const question = generatedQuestions.find(item => item.tempId === Number(card.dataset.tempId));
            if (!question) return;
            const target = event.target;
            if (target.dataset.questionField) question[target.dataset.questionField] = target.value;
            if (target.dataset.optionIndex !== undefined) question.options[Number(target.dataset.optionIndex)] = target
                .value;
            if (target.dataset.correctIndex !== undefined) {
                if (question.question_type === 'single_choice') {
                    question.correct_indexes = [Number(target.dataset.correctIndex)];
                    question.correct_index = Number(target.dataset.correctIndex);
                } else {
                    question.correct_indexes = Array.from(card.querySelectorAll('[data-correct-index]:checked')).map(
                        input => Number(input.dataset.correctIndex));
                }
                card.querySelectorAll('.ai-answer-row').forEach(row => row.classList.toggle('is-correct', Boolean(row
                    .querySelector('[data-correct-index]:checked'))));
            }
            if (target.dataset.statementText !== undefined) question.statements[Number(target.dataset.statementText)].text =
                target.value;
            if (target.dataset.statementValue !== undefined) question.statements[Number(target.dataset.statementValue)]
                .is_true = target.value === '1';
            if (target.dataset.blankIndex !== undefined) question.blanks[Number(target.dataset.blankIndex)].accepted =
                target.value.split('|').map(value => value.trim()).filter(Boolean);
            if (target.dataset.numeric) question[target.dataset.numeric] = target.dataset.numeric === 'numeric_unit' ?
                target.value : Number(target.value);
            if (target.dataset.manualField) {
                const field = target.dataset.manualField;
                question[field] = target.type === 'checkbox' ? target.checked : (target.type === 'number' ? Number(target
                    .value) : target.value);
                if (field === 'explanation_mode') {
                    const limit = card.querySelector('[data-manual-field="explanation_word_limit"]');
                    if (limit) {
                        limit.disabled = target.value === 'disabled';
                        if (target.value === 'disabled') question.explanation_word_limit = 0;
                        else if (!question.explanation_word_limit) {
                            question.explanation_word_limit = 150;
                            limit.value = 150;
                        }
                    }
                }
            }
            if (target.dataset.rubricCriterion !== undefined) question.rubric[Number(target.dataset.rubricCriterion)]
                .criterion = target.value;
            if (target.dataset.rubricScore !== undefined) question.rubric[Number(target.dataset.rubricScore)].max_score =
                Number(target.value);
            question.quality = {
                status: 'needs_review',
                score: 0,
                warnings: ['Nội dung đã được chỉnh sửa; hãy bấm Kiểm tra lại.']
            };
            const qualityBox = card.querySelector('.ai-quality');
            qualityBox.className = 'ai-quality needs_review';
            qualityBox.innerHTML =
                '<strong><i class="fa-solid fa-triangle-exclamation"></i> Chưa kiểm định lại</strong><ul><li>Nội dung đã được chỉnh sửa; hãy bấm Kiểm tra lại.</li></ul>';
        }

        // ==========================================
        // 3. LƯU TẤT CẢ VÀO NGÂN HÀNG
        // ==========================================
        document.getElementById('btnReviewAll').addEventListener('click', async function() {
            if (!generatedQuestions.length) return;
            const button = this;
            button.disabled = true;
            try {
                const response = await fetch(reviewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        course_id: courseSelect.value,
                        questions: generatedQuestions
                    })
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] ||
                    'Không thể kiểm tra.');
                generatedQuestions = data.questions.map(question => ({
                    ...question,
                    tempId: tempIdCounter++
                }));
                renderQuestions();
            } catch (error) {
                alert(error.message);
            } finally {
                button.disabled = false;
            }
        });

        document.getElementById('btnSaveAll').addEventListener('click', () => saveQuestions(false));

        async function saveQuestions(allowDuplicates) {
            if (generatedQuestions.length === 0) return;

            const btnSave = document.getElementById('btnSaveAll');
            btnSave.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Đang lưu...';
            btnSave.disabled = true;

            const payload = {
                course_id: document.getElementById('course_id').value,
                difficulty: document.getElementById('difficulty').value,
                allow_duplicates: allowDuplicates,
                questions: generatedQuestions
            };

            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok) {
                    alert("Thành công! " + data.success);
                    window.location.href = "{{ route('questions.index') }}";
                } else if (data.needs_confirmation) {
                    generatedQuestions = (data.questions || generatedQuestions).map(question => ({
                        ...question,
                        tempId: tempIdCounter++
                    }));
                    renderQuestions();
                    if (confirm(data.message + ' Bạn vẫn muốn lưu các câu này?')) await saveQuestions(true);
                } else {
                    alert("Lỗi khi lưu: " + (data.message || Object.values(data.errors || {}).flat()[0] ||
                        "Vui lòng kiểm tra lại."));
                    btnSave.innerHTML = '<i class="fa-solid fa-cloud-arrow-up me-2"></i> LƯU VÀO NGÂN HÀNG';
                    btnSave.disabled = false;
                }
            } catch (error) {
                console.error(error);
                alert("Lỗi kết nối khi lưu dữ liệu.");
            } finally {
                btnSave.innerHTML = '<i class="fa-solid fa-cloud-arrow-up me-2"></i> LƯU VÀO NGÂN HÀNG';
                btnSave.disabled = false;
            }
        }

        // Reset UI về ban đầu
        function resetUI() {
            document.getElementById('loadingState').classList.add('d-none');
            document.getElementById('questionPreviewList').classList.add('d-none');
            document.getElementById('emptyState').classList.remove('d-none');

            const btnGenerate = document.getElementById('btnGenerate');
            btnGenerate.disabled = false;
            btnGenerate.innerHTML = '<i class="fa-solid fa-microchip me-2"></i> SINH CÂU HỎI';
        }
    </script>
@endsection
