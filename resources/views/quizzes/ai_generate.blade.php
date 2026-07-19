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
    </style>

    <div class="container py-5">
        <div class="row g-4">
            {{-- Cột trái: Cấu hình thông số --}}
            <div class="col-lg-4">
                <div class="card shadow border-0 card-ai-config mb-4 sticky-top" style="top: 20px;">
                    <div class="card-header py-3">
                        <h6 class="mb-0 fw-bold text-white d-flex align-items-center">
                            <i class="fa-solid fa-robot me-2"></i> Cấu hình AI
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <form id="aiGenForm">
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Môn học / Khóa học</label>
                                <select class="form-select ai-input" id="course_id" required>
                                    <option value="">-- Chọn khóa học --</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text mt-2 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                                    AI có thể dùng nội dung bài học, tài liệu upload hoặc chủ đề nhập tay.
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
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Độ khó</label>
                                    <select class="form-select ai-input" id="difficulty">
                                        <option value="Dễ">Dễ</option>
                                        <option value="Trung bình" selected>Trung bình</option>
                                        <option value="Khó">Khó</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Số lượng</label>
                                    <input type="number" class="form-control ai-input" id="quantity" value="5"
                                        min="1" max="20">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-ai-generate w-100 py-3 fw-bold shadow-sm"
                                id="btnGenerate">
                                <i class="fa-solid fa-microchip me-2"></i> SINH CÂU HỎI
                            </button>
                        </form>
                    </div>
                </div>

                <div class="alert border-0 rounded-4 p-4 shadow-sm" style="background: rgba(102, 126, 234, 0.05);">
                    <div class="d-flex">
                        <div class="me-3 text-primary h5 mb-0"><i class="fa-solid fa-lightbulb"></i></div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Mẹo nhỏ từ AI</h6>
                            <p class="small text-muted mb-0">
                                Chủ đề càng chi tiết (VD: "Eloquent ORM relationships" thay vì "Laravel"), AI càng đưa ra
                                câu hỏi chính xác.
                            </p>
                        </div>
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
                            <p class="text-muted small mt-2">Chỉ cần cấu hình bên trái, AI sẽ giúp Thầy/Cô soạn đề thi trong
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
                            <h5 class="fw-bold mb-0 text-gradient"><i class="fa-solid fa-list-check me-2"></i>Kết quả sinh ra
                            </h5>
                            <button class="btn btn-primary btn-save-all fw-bold shadow-sm" id="btnSaveAll">
                                <i class="fa-solid fa-cloud-arrow-up me-2"></i> LƯU VÀO NGÂN HÀNG
                            </button>
                        </div>

                        <div id="questionsContainer">
                            {{-- Các câu hỏi sẽ được Append vào đây bằng JS --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Template mẫu cho 1 câu hỏi --}}
    <template id="questionTemplate">
        <div class="card shadow-sm ai-question-card mb-4" data-temp-id="">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge q-badge q-title">Câu 1</span>
                    <button class="btn btn-sm btn-outline-secondary border-0 btn-remove-q" title="Bỏ câu này">
                        <i class="fa-solid fa-trash-can text-danger"></i>
                    </button>
                </div>

                <h6 class="fw-bold mb-4 q-content">[Nội dung câu hỏi]</h6>

                <div class="row g-3 mb-4 options-container">
                    <div class="col-md-6">
                        <div class="option-item opt-a">A. <span class="opt-text">...</span></div>
                    </div>
                    <div class="col-md-6">
                        <div class="option-item opt-b">B. <span class="opt-text">...</span></div>
                    </div>
                    <div class="col-md-6">
                        <div class="option-item opt-c">C. <span class="opt-text">...</span></div>
                    </div>
                    <div class="col-md-6">
                        <div class="option-item opt-d">D. <span class="opt-text">...</span></div>
                    </div>
                </div>

                <div class="explanation-box small d-flex align-items-start">
                    <i class="fa-solid fa-lightbulb text-warning me-2 mt-1"></i>
                    <div>
                        <strong>Giải thích:</strong> <span class="explanation">...</span>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script>
        const processUrl = "{{ route('quizzes.ai_generate.process') }}";
        const saveUrl = "{{ route('quizzes.ai_generate.save') }}";
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

        function selectedSourceType() {
            return document.querySelector('.source-type-input:checked')?.value || 'course_content';
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
                const selectedModule = (courseData.modules || []).find((module) => String(module.id) === moduleSelect.value);
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

        refreshModuleOptions();
        syncSourceUi();

        // ==========================================
        // 1. XỬ LÝ SINH CÂU HỎI
        // ==========================================
        document.getElementById('aiGenForm').addEventListener('submit', async function(e) {
            e.preventDefault();

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
                    alert("Lỗi: " + (data.error || data.message || "Không thể sinh câu hỏi."));
                    resetUI();
                    return;
                }

                if (data.queued) {
                    let completed = false;
                    for (let attempt = 0; attempt < 90; attempt++) {
                        const statusResponse = await fetch(data.status_url, { headers: { 'Accept': 'application/json' } });
                        const operation = await statusResponse.json();
                        if (operation.status === 'completed') {
                            data = operation.result || {};
                            completed = true;
                            break;
                        }
                        if (operation.status === 'failed') throw new Error(operation.message || 'AI tạo câu hỏi thất bại.');
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
                alert("Lỗi kết nối máy chủ AI. Vui lòng thử lại!");
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
            container.innerHTML = '';
            const template = document.getElementById('questionTemplate').content;

            generatedQuestions.forEach((q, index) => {
                const clone = template.cloneNode(true);
                const card = clone.querySelector('.ai-question-card');

                // Set data attribute để dễ xóa
                card.setAttribute('data-temp-id', q.tempId);

                // Điền nội dung
                clone.querySelector('.q-title').innerText = `Câu ${index + 1}`;
                clone.querySelector('.q-content').innerText = q.question;

                const optionsMap = {
                    0: clone.querySelector('.opt-a'),
                    1: clone.querySelector('.opt-b'),
                    2: clone.querySelector('.opt-c'),
                    3: clone.querySelector('.opt-d')
                };

                q.options.forEach((optText, optIndex) => {
                    const optionDiv = optionsMap[optIndex];
                    optionDiv.querySelector('.opt-text').innerText = optText;

                    // Highlight đáp án đúng
                    if (optIndex === q.correct_index) {
                        optionDiv.classList.add('is-correct');
                        optionDiv.innerHTML += ' <i class="fa-solid fa-circle-check float-end"></i>';
                    }
                });

                // Điền giải thích
                clone.querySelector('.explanation').innerText = q.explanation || "Không có giải thích.";

                // Xử lý nút Xóa
                clone.querySelector('.btn-remove-q').addEventListener('click', function() {
                    const cardToRemove = this.closest('.ai-question-card');
                    const idToRemove = parseInt(cardToRemove.getAttribute('data-temp-id'));

                    // Xóa khỏi mảng dữ liệu
                    generatedQuestions = generatedQuestions.filter(q => q.tempId !== idToRemove);

                    // Xóa khỏi DOM
                    cardToRemove.remove();

                    // Cập nhật lại số thứ tự các câu còn lại
                    updateQuestionNumbers();

                    if (generatedQuestions.length === 0) resetUI();
                });

                container.appendChild(clone);
            });
        }

        // Cập nhật lại số thứ tự câu hỏi sau khi xóa
        function updateQuestionNumbers() {
            const cards = document.querySelectorAll('.ai-question-card');
            cards.forEach((card, index) => {
                card.querySelector('.q-title').innerText = `Câu ${index + 1}`;
            });
        }

        // ==========================================
        // 3. LƯU TẤT CẢ VÀO NGÂN HÀNG
        // ==========================================
        document.getElementById('btnSaveAll').addEventListener('click', async function() {
            if (generatedQuestions.length === 0) return;

            const btnSave = this;
            btnSave.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Đang lưu...';
            btnSave.disabled = true;

            const payload = {
                course_id: document.getElementById('course_id').value,
                difficulty: document.getElementById('difficulty').value,
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
                    // Có thể dùng Toast/SweetAlert thay cho Alert thông thường
                    alert("Thành công! " + data.success);
                    window.location.href = "{{ route('questions.index') }}";
                } else {
                    alert("Lỗi khi lưu: " + (data.message || "Vui lòng kiểm tra lại."));
                    btnSave.innerHTML = '<i class="fa-solid fa-cloud-arrow-up me-2"></i> LƯU VÀO NGÂN HÀNG';
                    btnSave.disabled = false;
                }
            } catch (error) {
                console.error(error);
                alert("Lỗi kết nối khi lưu dữ liệu.");
                btnSave.innerHTML = '<i class="fa-solid fa-cloud-arrow-up me-2"></i> LƯU VÀO NGÂN HÀNG';
                btnSave.disabled = false;
            }
        });

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
