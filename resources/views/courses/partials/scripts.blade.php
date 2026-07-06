@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        // Fix lỗi Bootstrap 5 chặn focus (không cho gõ chữ) vào các popup bên trong TinyMCE (như popup chèn link)
        document.addEventListener('focusin', (e) => {
            if (e.target.closest(".tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
                e.stopImmediatePropagation();
            }
        });

        const lessonContentBlocks = [
            {
                text: 'Khối ghi nhớ',
                content: '<div class="lesson-callout lesson-callout--note"><strong>Ghi nhớ</strong><p>Nhập ý chính học sinh cần nắm ở đây.</p></div><p></p>'
            },
            {
                text: 'Khối ví dụ',
                content: '<div class="lesson-callout lesson-callout--example"><strong>Ví dụ minh họa</strong><p>Thêm ví dụ cụ thể để học sinh dễ hình dung.</p></div><p></p>'
            },
            {
                text: 'Khối lưu ý',
                content: '<div class="lesson-callout lesson-callout--warning"><strong>Lưu ý</strong><p>Nhập cảnh báo, lỗi thường gặp hoặc điểm cần chú ý.</p></div><p></p>'
            },
            {
                text: 'Bài thực hành',
                content: '<div class="lesson-callout lesson-callout--practice"><strong>Bài thực hành</strong><ol><li>Bước 1: mô tả việc cần làm.</li><li>Bước 2: yêu cầu học sinh thực hiện.</li></ol></div><p></p>'
            },
            {
                text: 'Checklist',
                content: '<ul class="lesson-checklist"><li>Hoàn thành mục thứ nhất.</li><li>Kiểm tra lại kết quả.</li><li>Gửi bài hoặc đánh dấu hoàn thành.</li></ul><p></p>'
            },
            {
                text: 'Câu hỏi tự kiểm tra',
                content: '<div class="lesson-self-check"><h4>Câu hỏi tự kiểm tra</h4><ol><li>Em hiểu nội dung chính của bài này là gì?</li><li>Hãy nêu một ví dụ áp dụng.</li></ol></div><p></p>'
            }
        ];

        const lessonEditorContentStyle = `
            body {
                color: #202634;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                font-size: 15px;
                line-height: 1.75;
                padding: 10px 14px;
            }
            h2, h3, h4 { color: #111827; line-height: 1.35; }
            table { border-collapse: collapse; width: 100%; }
            table td, table th { border: 1px solid #dbe3ef; padding: 10px; }
            pre {
                background: #111827;
                border-radius: 12px;
                color: #e5e7eb;
                overflow-x: auto;
                padding: 14px;
            }
            .lesson-callout,
            .lesson-self-check {
                border: 1px solid #dbeafe;
                border-radius: 16px;
                margin: 16px 0;
                padding: 14px 16px;
            }
            .lesson-callout strong,
            .lesson-self-check h4 {
                display: block;
                font-size: 15px;
                margin: 0 0 6px;
            }
            .lesson-callout p:last-child,
            .lesson-self-check ol:last-child { margin-bottom: 0; }
            .lesson-callout--note { background: #eff6ff; border-color: #bfdbfe; }
            .lesson-callout--example { background: #ecfdf5; border-color: #bbf7d0; }
            .lesson-callout--warning { background: #fff7ed; border-color: #fed7aa; }
            .lesson-callout--practice { background: #f5f3ff; border-color: #ddd6fe; }
            .lesson-checklist {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                list-style: none;
                margin: 16px 0;
                padding: 14px 16px;
            }
            .lesson-checklist li {
                margin: 8px 0;
                padding-left: 28px;
                position: relative;
            }
            .lesson-checklist li::before {
                color: #16a34a;
                content: "✓";
                font-weight: 800;
                left: 0;
                position: absolute;
            }
            .lesson-self-check { background: #f8fafc; border-color: #c7d2fe; }
        `;

        // Khởi tạo trình soạn thảo bài học
        tinymce.init({
            selector: '#addLessonContent, #editLessonContent',
            height: 420,
            min_height: 320,
            menubar: false,
            branding: false,
            promotion: false,
            resize: true,
            plugins: 'lists link image preview searchreplace visualblocks code fullscreen table wordcount autoresize',
            toolbar: 'undo redo | blocks | bold italic underline forecolor backcolor | lessonblocks | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link table image | removeformat | preview fullscreen code',
            toolbar_mode: 'sliding',
            block_formats: 'Đoạn văn=p; Tiêu đề lớn=h2; Tiêu đề vừa=h3; Tiêu đề nhỏ=h4; Trích dẫn=blockquote; Mã lệnh=pre',
            style_formats: [
                { title: 'Đoạn nhấn mạnh', block: 'p', classes: 'lesson-lead-text' },
                { title: 'Ghi nhớ', block: 'div', classes: 'lesson-callout lesson-callout--note', wrapper: true },
                { title: 'Ví dụ minh họa', block: 'div', classes: 'lesson-callout lesson-callout--example', wrapper: true },
                { title: 'Lưu ý', block: 'div', classes: 'lesson-callout lesson-callout--warning', wrapper: true },
                { title: 'Bài thực hành', block: 'div', classes: 'lesson-callout lesson-callout--practice', wrapper: true }
            ],
            content_style: lessonEditorContentStyle,
            paste_data_images: true,
            automatic_uploads: false,
            convert_urls: false,
            extended_valid_elements: 'div[class],section[class],span[class],ul[class],ol[class],li[class],pre[class],code[class],blockquote[class]',
            setup: function(editor) {
                editor.ui.registry.addMenuButton('lessonblocks', {
                    text: 'Khối nội dung',
                    tooltip: 'Chèn nhanh mẫu nội dung bài học',
                    fetch: function(callback) {
                        callback(lessonContentBlocks.map((block) => ({
                            type: 'menuitem',
                            text: block.text,
                            onAction: function() {
                                editor.insertContent(block.content);
                                editor.save();
                            }
                        })));
                    }
                });

                // Đồng bộ dữ liệu từ TinyMCE về textarea gốc để Form có thể gửi đi
                editor.on('change keyup undo redo SetContent', function() {
                    editor.save();
                });
            }
        });
    </script>
    <script>
        let totalLessonsCount = {{ $totalLessons ?? 0 }};
        let currentCompletedCount = {{ $completedCount ?? 0 }};
        const isStudentCourseUser = @json(auth()->user()->role === 'student');
        const canManageCourseContent = @json(auth()->id() === $course->teacher_id || auth()->user()->role === 'admin');
        const currentCourseId = {{ $course->id }};
        const currentCourseTitle = @json($course->title);

        function getYoutubeId(url) {
            const regExp = /^.*(http:\/\/www\.youtube\.com\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }

        let currentLessonIndex = -1;
        const lessons = Array.from(document.querySelectorAll('.lesson-item'));
        let currentLessonId = null;

        function updateNavButtons() {
            document.getElementById('btn-prev').disabled = (currentLessonIndex <= 0);
            document.getElementById('btn-next').disabled = (currentLessonIndex === -1 || currentLessonIndex >= lessons
                .length - 1);

            const btnComplete = document.getElementById('btn-complete');
            if (currentLessonIndex !== -1) {
                btnComplete.classList.toggle('d-none', !isStudentCourseUser);
                btnComplete.classList.replace('btn-secondary', 'btn-success');
                btnComplete.innerHTML = '<i class="fas fa-check-circle me-1"></i> Hoàn thành bài học';
                btnComplete.disabled = false;
            }
        }

        // GIAO DIỆN COMPONENTS
        const lessonArea = document.getElementById('lesson-content-area');
        const assignmentArea = document.getElementById('assignment-content-area');
        const quizArea = document.getElementById('quiz-content-area');
        const videoContainer = document.getElementById('video-container');
        const externalContainer = document.getElementById('external-link-container');
        const navFooter = document.getElementById('nav-footer');
        const iframe = document.getElementById('lesson-video');
        const externalBtn = document.getElementById('external-link-btn');

        function parseDataJson(value, fallback = '') {
            try {
                return JSON.parse(value || JSON.stringify(fallback));
            } catch (e) {
                return fallback;
            }
        }

        function lessonSelector(id) {
            return `.sidebar-scroll .lesson-item[data-id="${id}"]`;
        }

        function contentSelector(type, id) {
            if (type === 'assignment') return `.sidebar-scroll .assignment-item[data-id="${id}"]`;
            if (type === 'quiz') return `.sidebar-scroll .quiz-item[data-id="${id}"]`;
            return lessonSelector(id);
        }

        function getCurrentLessonElement() {
            return currentLessonIndex >= 0 ? lessons[currentLessonIndex] : null;
        }

        function getNextLessonElement() {
            return currentLessonIndex >= 0 && currentLessonIndex < lessons.length - 1
                ? lessons[currentLessonIndex + 1]
                : null;
        }

        function beautifyLessonBody() {
            const body = document.getElementById('lesson-body');
            if (!body) return;

            const linkTitleFromUrl = (href) => {
                try {
                    const url = new URL(href);
                    const host = url.hostname.replace(/^www\./, '');
                    const path = url.pathname.toLowerCase();

                    if (host.includes('docs.google.com') && path.includes('/spreadsheets/')) {
                        return 'Google Sheet bài học';
                    }
                    if (host.includes('docs.google.com') && path.includes('/document/')) {
                        return 'Google Docs bài học';
                    }
                    if (host.includes('docs.google.com') && path.includes('/presentation/')) {
                        return 'Google Slides bài học';
                    }
                    if (host.includes('drive.google.com')) {
                        return 'Thư mục Google Drive';
                    }
                    if (host.includes('youtube.com') || host.includes('youtu.be')) {
                        return 'Video bài học';
                    }

                    return `Tài nguyên từ ${host}`;
                } catch (e) {
                    return 'Tài nguyên bài học';
                }
            };

            const cleanLinkLabel = (text, href) => {
                const withoutUrl = text.replace(href, '').replace(/^\s*(link|liên kết|tài nguyên)\s*[:：-]?\s*/i, '').trim();
                const normalized = withoutUrl.replace(/\s+/g, ' ');

                if (normalized.length >= 3 && normalized.length <= 80) {
                    return normalized;
                }

                return linkTitleFromUrl(href);
            };

            const createResourceLinkCard = (href, label = '') => {
                const wrapper = document.createElement('div');
                wrapper.className = 'lesson-resource-link';

                const icon = document.createElement('span');
                icon.className = 'lesson-resource-link__icon';
                const iconInner = document.createElement('i');
                iconInner.className = 'fas fa-link';
                icon.appendChild(iconInner);

                const content = document.createElement('span');
                content.className = 'lesson-resource-link__content';
                const title = document.createElement('strong');
                title.textContent = label || linkTitleFromUrl(href);
                const anchor = document.createElement('a');
                anchor.href = href;
                anchor.target = '_blank';
                anchor.rel = 'noopener';
                anchor.textContent = href;
                content.appendChild(title);
                content.appendChild(anchor);
                wrapper.appendChild(icon);
                wrapper.appendChild(content);

                return wrapper;
            };

            body.querySelectorAll('a[href^="http"]').forEach((link) => {
                if (link.closest('.lesson-resource-link, .lesson-callout, .attachment-box')) return;
                const href = link.getAttribute('href') || link.textContent.trim();
                if (!href || href.length < 28) return;

                const parent = link.parentElement;
                const parentText = parent?.textContent?.trim() || link.textContent.trim();
                const wrapper = createResourceLinkCard(href, cleanLinkLabel(parentText, href));

                if (parent && parent.childNodes.length <= 2 && parentText.length <= href.length + 90) {
                    parent.replaceWith(wrapper);
                } else {
                    link.replaceWith(wrapper);
                }
            });

            body.querySelectorAll('p').forEach((paragraph) => {
                if (paragraph.closest('.lesson-resource-link, .lesson-callout, .lesson-self-check')) return;
                const text = paragraph.textContent.trim();
                const url = text.match(/https?:\/\/[^\s<>"']+/)?.[0];
                if (url && url.length >= 28) {
                    paragraph.replaceWith(createResourceLinkCard(url, cleanLinkLabel(text, url)));
                    return;
                }

                const looksLikePath = /^[A-Za-z]:\\/.test(text) || /^\/(?:[\w.-]+\/?)+/.test(text);
                if (!looksLikePath || text.length > 180) return;

                const codeBlock = document.createElement('code');
                codeBlock.className = 'lesson-inline-code';
                codeBlock.textContent = text;
                paragraph.replaceWith(codeBlock);
            });
        }

        function setAiLessonContext(lessonEl) {
            const toolbar = document.getElementById('lesson-ai-toolbar');
            if (!lessonEl) {
                if (toolbar) toolbar.classList.remove('active');
                window.SmartLmsAiTutor?.clearLessonContext?.();
                return;
            }

            const context = {
                course_id: currentCourseId,
                course_title: currentCourseTitle,
                lesson_id: lessonEl.getAttribute('data-id'),
                lesson_title: lessonEl.getAttribute('data-title') || 'Bài học',
            };

            if (toolbar) toolbar.classList.add('active');
            window.SmartLmsAiTutor?.setLessonContext?.(context);
        }

        function showReorderToast(message = 'Đã lưu thứ tự nội dung') {
            const toast = document.getElementById('reorder-toast');
            if (!toast) return;
            toast.innerHTML = `<i class="fas fa-check me-1"></i>${message}`;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 1800);
        }

        function postReorder(url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(payload),
            }).then(response => {
                if (!response.ok) throw new Error('Không thể lưu thứ tự.');
                return response.json();
            });
        }

        function initCourseReordering() {
            if (!canManageCourseContent || typeof Sortable === 'undefined') return;

            const moduleList = document.querySelector('.sidebar-scroll #courseAccordion');
            if (moduleList) {
                Sortable.create(moduleList, {
                    handle: '.module-header-wrapper .drag-handle',
                    draggable: '.module-sortable-item',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function() {
                        const moduleIds = Array.from(moduleList.querySelectorAll(':scope > .module-sortable-item'))
                            .map(item => item.dataset.moduleId)
                            .filter(Boolean);
                        postReorder('/modules/reorder', {
                            course_id: currentCourseId,
                            module_ids: moduleIds,
                        }).then(() => showReorderToast('Đã lưu thứ tự chương'));
                    },
                });
            }

            document.querySelectorAll('.sidebar-scroll .lesson-sortable-list').forEach(list => {
                Sortable.create(list, {
                    handle: '.lesson-item-wrapper .drag-handle',
                    draggable: '.lesson-item-wrapper',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function() {
                        const lessonIds = Array.from(list.querySelectorAll(':scope > .lesson-item-wrapper'))
                            .map(item => item.dataset.lessonId)
                            .filter(Boolean);
                        postReorder('/lessons/reorder', {
                            module_id: list.dataset.moduleId,
                            lesson_ids: lessonIds,
                        }).then(() => showReorderToast('Đã lưu thứ tự bài học'));
                    },
                });
            });
        }

        // Helper: ẩn tất cả các khu vực nội dung
        function hideAllAreas() {
            lessonArea.classList.add('d-none');
            videoContainer.classList.add('d-none');
            externalContainer.classList.add('d-none');
            if (iframe) iframe.src = '';

            // ✅ THÊM: Reset attachment container
            const attachCont = document.getElementById('lesson-attachment-container');
            if (attachCont) attachCont.classList.add('d-none');
            if (assignmentArea) {
                assignmentArea.classList.add('d-none');
                assignmentArea.classList.remove('d-flex');
            }
            if (quizArea) {
                quizArea.classList.add('d-none');
                quizArea.classList.remove('d-flex');
            }
            navFooter.classList.add('d-none');
            setAiLessonContext(null);
        }

        // ==========================================
        // 1. CLICK VÀO BÀI HỌC
        // ==========================================
        lessons.forEach((item, index) => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll(
                        '.lesson-item-wrapper, .assignment-item-wrapper, .quiz-item-wrapper')
                    .forEach(li => li.classList.remove('active'));
                this.closest('.lesson-item-wrapper').classList.add('active');

                hideAllAreas();

                lessonArea.classList.remove('d-none');
                navFooter.classList.remove('d-none');

                currentLessonId = this.getAttribute('data-id');
                currentLessonIndex = index;
                updateNavButtons();
                setAiLessonContext(this);

                document.getElementById('lesson-title').innerText = this.getAttribute('data-title');
                const moduleTitleEl = document.getElementById('lesson-module-title');
                if (moduleTitleEl) {
                    const moduleTitle = this.getAttribute('data-module-title') || '';
                    moduleTitleEl.innerText = moduleTitle ? `Module: ${moduleTitle}` : '';
                }
                const durationBox = document.getElementById('lesson-duration-box');
                const durationText = document.getElementById('lesson-duration-text');
                const durationLabel = this.getAttribute('data-duration-label') || '';
                if (durationBox && durationText) {
                    durationText.innerText = durationLabel;
                    durationBox.classList.toggle('d-none', durationLabel.trim() === '');
                }
                document.getElementById('lesson-body').innerHTML = this.getAttribute('data-content') ||
                    '<p class="text-muted fst-italic">Không có nội dung văn bản.</p>';
                beautifyLessonBody();
                const placeholder = document.getElementById('welcome-placeholder');
                if (placeholder) placeholder.style.display = 'none';

                const videoUrl = this.getAttribute('data-video');
                const ytId = videoUrl ? getYoutubeId(videoUrl) : null;

                if (ytId) {
                    iframe.src = `http://www.youtube.com/embed/${ytId}?autoplay=1`;
                    videoContainer.classList.remove('d-none');
                } else if (videoUrl && videoUrl.trim() !== '') {
                    externalBtn.href = videoUrl;
                    externalContainer.classList.remove('d-none');
                }
                lessonArea.scrollIntoView({
                    behavior: 'smooth'
                });

                const attachmentUrl = this.getAttribute('data-attachment');
                const attachmentName = this.getAttribute('data-attachment-name');
                const attachmentContainer = document.getElementById('lesson-attachment-container');
                const attachmentBtn = document.getElementById('lesson-attachment-btn');
                const attachmentViewBtn = document.getElementById('lesson-attachment-view-btn');
                const attachmentNameSpan = document.getElementById('lesson-attachment-name');

                if (attachmentUrl && attachmentUrl.trim() !== '') {
                    attachmentContainer.classList.remove('d-none');
                    attachmentBtn.href = attachmentUrl;
                    if (attachmentViewBtn) attachmentViewBtn.href = attachmentUrl;
                    attachmentNameSpan.innerText = attachmentName;
                } else {
                    attachmentContainer.classList.add('d-none');
                    attachmentBtn.href = '#';
                    if (attachmentViewBtn) attachmentViewBtn.href = '#';
                }

            });
        });

        // ==========================================
        // 2. CLICK VÀO BÀI TẬP
        // ==========================================
        const assignments = Array.from(document.querySelectorAll('.assignment-item'));
        assignments.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll(
                        '.lesson-item-wrapper, .assignment-item-wrapper, .quiz-item-wrapper')
                    .forEach(li => li.classList.remove('active'));
                this.closest('.assignment-item-wrapper').classList.add('active');

                hideAllAreas();

                assignmentArea.classList.remove('d-none');
                assignmentArea.classList.add('d-flex', 'flex-column');


                const id = this.getAttribute('data-id');

                document.getElementById('assignment-title').innerText = this.getAttribute('data-title');

                const instructions = this.getAttribute('data-instructions');
                document.getElementById('assignment-instructions').innerHTML = instructions;

                document.getElementById('assignment-due-date').innerText = this.getAttribute('data-due');

                const rawDue = this.getAttribute('data-raw-due');
                const isOverdue = rawDue ? new Date(rawDue) < new Date() : false;

                const status = this.getAttribute('data-status');
                const assignmentType = this.getAttribute('data-assignment-type') || 'file';
                const needsFile = ['file', 'mixed'].includes(assignmentType);
                const needsEssay = ['essay', 'mixed'].includes(assignmentType);
                const badge = document.getElementById('assignment-badge');
                const grade = this.getAttribute('data-grade');
                const feedback = this.getAttribute('data-feedback');
                const subId = this.getAttribute('data-sub-id');
                const subTime = this.getAttribute('data-sub-time');
                const subFile = this.getAttribute('data-sub-file');
                const textAnswer = parseDataJson(this.getAttribute('data-text-answer'), '');

                const submittedArea = document.getElementById('submitted-info-area');
                const uploadArea = document.getElementById('upload-form-area');
                const submittedFileCard = document.getElementById('submitted-file-card');
                const submittedFileLink = document.getElementById('submitted-file-link');
                const submittedTextAnswerCard = document.getElementById('submitted-text-answer-card');
                const submittedTextAnswerText = document.getElementById('submitted-text-answer-text');
                const fileUploadField = document.getElementById('file-upload-field');
                const fileInput = document.getElementById('assignment-file-input');
                const essayAnswerField = document.getElementById('essay-answer-field');
                const essayAnswerInput = document.getElementById('essay-answer-input');
                const gradingResult = document.getElementById('grading-result');
                const submissionActions = document.getElementById('submission-actions');
                let gradedWarning = document.getElementById('graded-warning');
                const btnCancelEdit = document.getElementById('btn-cancel-edit');
                const btnEditSub = document.getElementById('btn-edit-submission');
                const deleteForm = document.getElementById('delete-submission-form');
                const submitForm = document.getElementById('course-submit-assignment-form');

                if (fileUploadField) fileUploadField.classList.toggle('d-none', !needsFile);
                if (fileInput) {
                    fileInput.required = needsFile && status !== 'submitted';
                    if (!needsFile) fileInput.value = '';
                }
                if (essayAnswerField) essayAnswerField.classList.toggle('d-none', !needsEssay);
                if (essayAnswerInput) {
                    essayAnswerInput.required = needsEssay;
                    essayAnswerInput.value = textAnswer || '';
                }

                if (submittedFileCard) submittedFileCard.classList.toggle('d-none', !subFile);
                if (submittedFileLink && subFile) submittedFileLink.href = subFile;
                if (submittedTextAnswerCard) submittedTextAnswerCard.classList.toggle('d-none', !textAnswer);
                if (submittedTextAnswerText) submittedTextAnswerText.innerText = textAnswer || '';

                let lockedAlert = document.getElementById('overdue-locked-alert');
                if (!lockedAlert && uploadArea) {
                    lockedAlert = document.createElement('div');
                    lockedAlert.id = 'overdue-locked-alert';
                    lockedAlert.className = 'alert alert-danger mb-0 border-0 shadow-sm text-center mt-3';
                    lockedAlert.innerHTML =
                        '<i class="fas fa-lock fa-2x mb-2 text-danger"></i><h6 class="fw-bold text-danger">Đã hết thời gian nộp bài</h6><p class="small mb-0 text-danger">Rất tiếc, bạn đã bỏ lỡ bài tập này hoặc không thể sửa đổi do đã quá hạn.</p>';
                    uploadArea.appendChild(lockedAlert);
                }

                if (status === 'submitted') {
                    badge.className = 'badge rounded-pill px-3 py-2 fs-6 bg-success';
                    badge.innerHTML = '<i class="fas fa-check me-1"></i> Đã nộp';

                    if (submittedArea && uploadArea) {
                        submittedArea.classList.remove('d-none');
                        uploadArea.classList.add('d-none');
                        document.getElementById('submitted-time-text').innerText = subTime;
                        if (deleteForm) deleteForm.action = `/submissions/${subId}/delete`;
                        if (btnCancelEdit) btnCancelEdit.classList.remove('d-none');
                    }

                    if (isOverdue && (!grade || grade === '')) {
                        if (btnEditSub) btnEditSub.classList.add('d-none');
                        if (deleteForm) deleteForm.classList.add('d-none');
                        if (gradedWarning) {
                            gradedWarning.innerHTML =
                                '<i class="fas fa-lock me-1"></i>Đã hết hạn nộp. Bạn không thể sửa hoặc hủy bài nộp nữa.';
                            gradedWarning.classList.remove('d-none', 'text-success');
                            gradedWarning.classList.add('text-danger');
                        }
                    } else if (!grade || grade === '') {
                        if (btnEditSub) btnEditSub.classList.remove('d-none');
                        if (deleteForm) deleteForm.classList.remove('d-none');
                        if (gradedWarning) gradedWarning.classList.add('d-none');
                    }
                } else {
                    if (submittedArea && uploadArea) {
                        submittedArea.classList.add('d-none');
                        uploadArea.classList.remove('d-none');
                        if (btnCancelEdit) btnCancelEdit.classList.add('d-none');
                    }

                    if (isOverdue) {
                        badge.className = 'badge rounded-pill px-3 py-2 fs-6 bg-danger';
                        badge.innerHTML = '<i class="fas fa-times-circle me-1"></i> Quá hạn';
                        if (submitForm) submitForm.classList.add('d-none');
                        if (lockedAlert) lockedAlert.classList.remove('d-none');
                    } else {
                        badge.className = 'badge rounded-pill px-3 py-2 fs-6 bg-warning text-dark';
                        badge.innerHTML = '<i class="fas fa-clock me-1"></i> Chưa nộp';
                        if (submitForm) submitForm.classList.remove('d-none');
                        if (lockedAlert) lockedAlert.classList.add('d-none');
                    }
                }

                if (grade && grade !== '') {
                    if (gradingResult) gradingResult.classList.remove('d-none');
                    document.getElementById('grade-score').innerText = grade;
                    document.getElementById('grade-feedback').innerText = feedback || 'Không có nhận xét';
                    if (submissionActions) submissionActions.classList.add('d-none');
                    if (gradedWarning) {
                        gradedWarning.innerHTML =
                            '<i class="fas fa-lock me-1"></i>Giáo viên đã chấm điểm, bạn không thể sửa hoặc xóa bài.';
                        gradedWarning.classList.remove('d-none', 'text-danger');
                        gradedWarning.classList.add('text-success');
                    }
                } else {
                    if (gradingResult) gradingResult.classList.add('d-none');
                    if (!isOverdue && status === 'submitted' && submissionActions) {
                        submissionActions.classList.remove('d-none');
                    }
                }

                if (submitForm) submitForm.action = `/assignments/${id}/submit`;
                assignmentArea.scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // ==========================================
        // SỰ KIỆN NÚT SỬA BÀI & HỦY SỬA (STUDENT)
        // ==========================================
        const btnEditSubEl = document.getElementById('btn-edit-submission');
        const btnCancelEditEl = document.getElementById('btn-cancel-edit');

        if (btnEditSubEl) {
            btnEditSubEl.addEventListener('click', () => {
                document.getElementById('submitted-info-area').classList.add('d-none');
                document.getElementById('upload-form-area').classList.remove('d-none');
            });
        }
        if (btnCancelEditEl) {
            btnCancelEditEl.addEventListener('click', () => {
                document.getElementById('submitted-info-area').classList.remove('d-none');
                document.getElementById('upload-form-area').classList.add('d-none');
            });
        }

        // ==========================================
        // 3. ĐIỀU HƯỚNG BÀI TRƯỚC / SAU
        // ==========================================
        document.getElementById('btn-prev').addEventListener('click', () => {
            if (currentLessonIndex > 0) lessons[currentLessonIndex - 1].click();
        });

        document.getElementById('btn-next').addEventListener('click', () => {
            if (currentLessonIndex < lessons.length - 1) lessons[currentLessonIndex + 1].click();
        });

        // ==========================================
        // 4. HOÀN THÀNH BÀI HỌC
        // ==========================================
        document.getElementById('btn-complete').addEventListener('click', function() {
            if (!currentLessonId) return;
            axios.post(`/lessons/${currentLessonId}/complete`)
                .then(response => {
                    this.classList.replace('btn-success', 'btn-secondary');
                    this.innerHTML = '<i class="fas fa-check me-1"></i> Đã hoàn thành';
                    this.disabled = true;

                    const icon = document.getElementById('icon-lesson-' + currentLessonId);
                    if (icon && !icon.classList.contains('fa-check-circle')) {
                        icon.className = 'fas fa-check-circle text-success me-2 flex-shrink-0 lesson-icon';
                        const lessonLink = document.querySelector(`.sidebar-scroll .lesson-item[data-id="${currentLessonId}"]`);
                        const statusRow = lessonLink ? lessonLink.querySelector('.sidebar-status-row') : null;
                        if (statusRow) {
                            const firstPill = statusRow.querySelector('.sidebar-status-pill');
                            if (firstPill) {
                                firstPill.className = 'sidebar-status-pill done';
                                firstPill.innerHTML = '<i class="fas fa-check"></i>Đã xong';
                            }
                        }
                        currentCompletedCount++;
                        let newProgress = Math.round((currentCompletedCount / totalLessonsCount) * 100);
                        const progressText = document.getElementById('progress-text');
                        const progressBar = document.getElementById('progress-bar');
                        const sidebarProgressText = document.getElementById('sidebar-progress-text');
                        const sidebarProgressBar = document.getElementById('sidebar-progress-bar');
                        if (progressText) progressText.innerText =
                            `${currentCompletedCount}/${totalLessonsCount} bài (${newProgress}%)`;
                        if (progressBar) progressBar.style.width = newProgress + '%';
                        if (sidebarProgressText) sidebarProgressText.innerText =
                            `Đã học ${currentCompletedCount}/${totalLessonsCount} bài · Tiến độ ${newProgress}%`;
                        if (sidebarProgressBar) sidebarProgressBar.style.width = newProgress + '%';
                    }
                    if (getNextLessonElement()) {
                        setTimeout(() => document.getElementById('btn-next').click(), 1400);
                    }
                });
        });

        // ==========================================
        // 5. MODAL CHẤM ĐIỂM (FETCH API)
        // ==========================================
        document.querySelectorAll('.view-submissions-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Đã xóa e.stopPropagation() để không chặn Bootstrap mở Modal nữa

                const id = this.getAttribute('data-id');
                const tableBody = document.getElementById('submissions-table-body');

                // Hiển thị trạng thái đang tải
                document.getElementById('modal-assignment-name').innerText = 'Đang tải dữ liệu...';
                tableBody.innerHTML =
                    '<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';

                // Dùng fetch gọi API
                fetch(`/assignments/${id}/submissions-list`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Không thể kết nối đến máy chủ.');
                        }
                        return response.json();
                    })
                    .then(data => {
                        document.getElementById('modal-assignment-name').innerText = 'Bài tập: ' + data
                            .assignment_title;
                        tableBody.innerHTML = '';

                        if (!data.submissions || data.submissions.length === 0) {
                            tableBody.innerHTML =
                                '<tr><td colspan="4" class="text-center text-muted py-4">Chưa có học sinh nào nộp bài</td></tr>';
                            return;
                        }

                        data.submissions.forEach(sub => {
                            let statusBadge = sub.submitted_at ?
                                '<span class="badge bg-success">Đã nộp</span>' :
                                '<span class="badge bg-light text-muted border">Chưa nộp</span>';

                            let reviewButton = sub.submission_id ?
                                `<a href="/submissions/${sub.submission_id}/review" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>Xem & chấm
                                </a>` :
                                '<span class="text-muted small">---</span>';

                            tableBody.innerHTML += `
                                <tr>
                                    <td class="px-4">
                                        <div class="fw-bold">${sub.student_name}</div>
                                        <div class="small text-muted">${sub.student_email}</div>
                                    </td>
                                    <td class="px-4">${statusBadge}</td>
                                    <td class="px-4 small text-muted">${sub.submitted_at || '---'}</td>
                                    <td class="px-4">${reviewButton}</td>
                                </tr>
                            `;
                        });
                    })
                    .catch(error => {
                        tableBody.innerHTML =
                            `<tr><td colspan="4" class="text-center text-danger py-5"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>Lỗi: ${error.message}</td></tr>`;
                    });
            });
        });

        // ==========================================
        // 6. GÁN VALUE CHO CÁC MODAL SỬA (GIÁO VIÊN)
        // ==========================================
        document.querySelectorAll('.edit-lesson-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                window.currentEditLessonId = this.getAttribute('data-id');
                document.getElementById('editLessonForm').action =
                    `/lessons/${this.getAttribute('data-id')}`;
                document.getElementById('editLessonTitle').value = this.getAttribute('data-title');

                // Nạp dữ liệu vào TinyMCE thay vì textarea thường
                if (tinymce.get('editLessonContent')) {
                    tinymce.get('editLessonContent').setContent(this.getAttribute('data-content') || '');
                } else {
                    document.getElementById('editLessonContent').value = this.getAttribute('data-content');
                }

                document.getElementById('editLessonVideo').value = this.getAttribute('data-video');
                document.getElementById('editLessonModule').value = this.getAttribute('data-module');
                document.getElementById('editLessonStatus').value = this.getAttribute('data-status') || 'published';
                document.getElementById('editLessonAvailableFrom').value = this.getAttribute('data-available-from') || '';
            });
        });

        document.querySelectorAll('.edit-assignment-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                document.getElementById('editAssignmentForm').action =
                    `/assignments/${this.getAttribute('data-id')}`;
                document.getElementById('editAssignmentLesson').value = this.getAttribute('data-lesson');
                document.getElementById('editAssignmentDue').value = this.getAttribute('data-due');
                document.getElementById('editAssignmentTitle').value = this.getAttribute('data-title');
                document.getElementById('editAssignmentInstructions').value = this.getAttribute(
                    'data-instructions');
            });
        });

        // ==========================================
        // 7. CLICK VÀO BÀI KIỂM TRA (QUIZZES)
        // ==========================================
        const quizzes = Array.from(document.querySelectorAll('.quiz-item'));

        quizzes.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();

                document.querySelectorAll(
                        '.lesson-item-wrapper, .assignment-item-wrapper, .quiz-item-wrapper')
                    .forEach(li => li.classList.remove('active'));
                this.closest('.quiz-item-wrapper').classList.add('active');

                hideAllAreas();

                // Hiện khu vực quiz
                quizArea.classList.remove('d-none');
                quizArea.classList.add('d-flex', 'flex-column');

                const id = this.getAttribute('data-id');
                document.getElementById('quiz-display-title').innerText = this.getAttribute('data-title');
                document.getElementById('quiz-display-duration').innerText = this.getAttribute(
                    'data-duration');

                // Lấy thông tin từ thẻ HTML
                const status = this.getAttribute('data-status');
                const score = this.getAttribute('data-score');
                const attemptId = this.getAttribute('data-attempt-id');

                // Các thành phần UI của học sinh
                const statusText = document.getElementById('quiz-status-text');
                const scoreBox = document.getElementById('quiz-score-box');
                const scoreText = document.getElementById('quiz-score-text');
                const actionArea = document.getElementById('quiz-student-action-area');
                const completedMsg = document.getElementById('quiz-completed-msg');
                const mainIcon = document.getElementById('quiz-main-icon');
                const reviewBtn = document.getElementById('review-quiz-btn');

                if (statusText) {
                    if (status === 'completed') {
                        // Đã làm bài: Hiện điểm, Ẩn form, Đổi icon xanh
                        statusText.innerText = 'Đã hoàn thành';
                        statusText.className = 'mb-0 fw-bold text-success fs-5 mt-2';

                        if (scoreBox) scoreBox.classList.remove('d-none');
                        if (scoreText) scoreText.innerText = score;
                        if (actionArea) actionArea.classList.add('d-none');
                        if (mainIcon) {
                            mainIcon.className = 'fas fa-check-circle fa-5x mb-4 text-success';
                            mainIcon.style.color = '';
                        }

                        // HIỆN KHUNG THÔNG BÁO VÀ GÁN LINK VÀO NÚT
                        if (completedMsg) completedMsg.classList.remove('d-none');
                        if (reviewBtn && attemptId) {
                            reviewBtn.href = `/attempts/${attemptId}/review`;
                        }

                    } else {
                        // Chưa làm bài
                        statusText.innerText = 'Chưa làm';
                        statusText.className = 'mb-0 fw-bold text-warning fs-5 mt-2';

                        if (scoreBox) scoreBox.classList.add('d-none');
                        if (actionArea) actionArea.classList.remove('d-none');
                        if (completedMsg) completedMsg.classList.add('d-none');

                        if (mainIcon) {
                            mainIcon.className = 'fas fa-stopwatch fa-5x mb-4';
                            mainIcon.style.color = '#6f42c1';
                        }
                    }
                }

                const startBtn = document.getElementById('start-quiz-btn');
                const manageBtn = document.getElementById('manage-quiz-btn');

                if (startBtn) startBtn.href = `/quizzes/${id}/attempt`;
                if (manageBtn) manageBtn.href = `/quizzes/${id}`;

                quizArea.scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.course-jump-btn');
            if (!btn) return;

            const type = btn.getAttribute('data-target-type');
            const id = btn.getAttribute('data-target-id');
            if (!type || !id) return;

            const target = document.querySelector(contentSelector(type, id));
            if (target) {
                target.click();
            }
        });

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.lesson-ai-btn');
            if (!btn || !currentLessonId) return;

            window.SmartLmsAiTutor?.openWithPrompt?.(btn.getAttribute('data-ai-prompt'), {
                course_id: currentCourseId,
                course_title: currentCourseTitle,
                lesson_id: currentLessonId,
                lesson_title: document.getElementById('lesson-title')?.innerText || 'Bài học',
                assist_mode: btn.getAttribute('data-ai-assist-mode') || 'lesson_help',
            });
        });

        // ==========================================
        // 8. SỬA CHƯƠNG
        // ==========================================
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.edit-module-btn');
            if (!btn) return;

            e.stopPropagation(); // Không trigger accordion
            document.getElementById('editModuleForm').action = '/modules/' + btn.dataset.id;
            document.getElementById('editModuleTitle').value = btn.dataset.title;
        });

        document.querySelectorAll('[data-course-mode]').forEach(btn => {
            btn.addEventListener('click', function() {
                const mode = this.getAttribute('data-course-mode');
                const wrapper = document.getElementById('course-page-wrapper');
                if (!wrapper) return;

                document.querySelectorAll('[data-course-mode]').forEach(item => item.classList.remove('active'));
                this.classList.add('active');
                wrapper.classList.toggle('preview-student-mode', mode === 'preview');
            });
        });

        initCourseReordering();
    </script>
@endpush
