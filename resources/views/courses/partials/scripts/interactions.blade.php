    <script>
        let totalLessonsCount = {{ $totalLessons ?? 0 }};
        let currentCompletedCount = {{ $completedCount ?? 0 }};
        const isStudentCourseUser = @json(auth()->user()->role === 'student');
        const canManageCourseContent = @json(auth()->id() === $course->teacher_id || auth()->user()->role === 'admin');
        const currentCourseId = {{ $course->id }};
        const currentCourseTitle = @json($course->title);
        const courseMaterialCards = @json($courseMaterialCards ?? []);

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
                btnComplete.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Hoàn thành bài học';
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
        const lessonMaterialContainer = document.getElementById('lesson-material-container');
        const lessonMaterialList = document.getElementById('lesson-material-list');
        const lessonMaterialCount = document.getElementById('lesson-material-count');

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

        function renderLessonMaterials(lessonId = null) {
            if (!lessonMaterialContainer || !lessonMaterialList || !lessonMaterialCount) return;

            const normalizedLessonId = lessonId ? String(lessonId) : null;
            const materials = courseMaterialCards.filter((material) => {
                if (material.unlock_lesson_id && String(material.unlock_lesson_id) !== normalizedLessonId) {
                    return false;
                }

                if (!normalizedLessonId) {
                    return material.lesson_id === null || material.lesson_id === undefined || material.lesson_id === '';
                }

                return material.lesson_id === null ||
                    material.lesson_id === undefined ||
                    material.lesson_id === '' ||
                    String(material.lesson_id) === normalizedLessonId;
            });

            lessonMaterialList.innerHTML = '';
            lessonMaterialCount.textContent = `${materials.length} mục`;
            lessonMaterialContainer.classList.toggle('d-none', materials.length === 0);

            materials.forEach((material) => {
                const item = document.createElement('a');
                item.className = 'lesson-material-card';
                item.href = material.url || '#';
                item.target = material.target || '_self';
                if (item.target === '_blank') item.rel = 'noopener';
                if (material.source_type !== 'link') item.dataset.noPageTransition = '';

                const icon = document.createElement('span');
                icon.className = 'lesson-material-icon';
                const iconInner = document.createElement('i');
                iconInner.className = `fa-solid ${material.icon || 'fa-file-lines'}`;
                icon.appendChild(iconInner);

                const content = document.createElement('span');
                content.className = 'min-w-0 flex-grow-1';
                const title = document.createElement('span');
                title.className = 'lesson-material-title';
                title.textContent = material.title || 'Học liệu';
                const meta = document.createElement('span');
                meta.className = 'lesson-material-meta';
                meta.textContent = [
                    material.type_label,
                    material.size,
                    material.class_name ? `Lớp ${material.class_name}` : null,
                    material.lock_label
                ].filter(Boolean).join(' · ');
                content.appendChild(title);
                content.appendChild(meta);

                const action = document.createElement('span');
                action.className = 'btn btn-sm btn-light border fw-bold';
                action.textContent = material.source_type === 'link' ? 'Mở' : 'Tải';

                item.appendChild(icon);
                item.appendChild(content);
                item.appendChild(action);
                lessonMaterialList.appendChild(item);
            });
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
                iconInner.className = 'fa-solid fa-link';
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
            toast.innerHTML = `<i class="fa-solid fa-check me-1"></i>${message}`;
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

            // Reset attachment container
            const attachCont = document.getElementById('lesson-attachment-container');
            if (attachCont) attachCont.classList.add('d-none');
            if (lessonMaterialContainer) lessonMaterialContainer.classList.add('d-none');
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

                renderLessonMaterials(currentLessonId);

            });
        });

        renderLessonMaterials(null);

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
                        '<i class="fa-solid fa-lock fa-2x mb-2 text-danger"></i><h6 class="fw-bold text-danger">Đã hết thời gian nộp bài</h6><p class="small mb-0 text-danger">Rất tiếc, bạn đã bỏ lỡ bài tập này hoặc không thể sửa đổi do đã quá hạn.</p>';
                    uploadArea.appendChild(lockedAlert);
                }

                if (status === 'submitted') {
                    badge.className = 'badge rounded-pill px-3 py-2 fs-6 bg-success';
                    badge.innerHTML = '<i class="fa-solid fa-check me-1"></i> Đã nộp';

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
                                '<i class="fa-solid fa-lock me-1"></i>Đã hết hạn nộp. Bạn không thể sửa hoặc hủy bài nộp nữa.';
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
                        badge.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i> Quá hạn';
                        if (submitForm) submitForm.classList.add('d-none');
                        if (lockedAlert) lockedAlert.classList.remove('d-none');
                    } else {
                        badge.className = 'badge rounded-pill px-3 py-2 fs-6 bg-warning text-dark';
                        badge.innerHTML = '<i class="fa-solid fa-clock me-1"></i> Chưa nộp';
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
                            '<i class="fa-solid fa-lock me-1"></i>Giáo viên đã chấm điểm, bạn không thể sửa hoặc xóa bài.';
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
                    this.innerHTML = '<i class="fa-solid fa-check me-1"></i> Đã hoàn thành';
                    this.disabled = true;

                    const icon = document.getElementById('icon-lesson-' + currentLessonId);
                    if (icon && !icon.classList.contains('fa-circle-check')) {
                        icon.className = 'fa-solid fa-circle-check text-success me-2 flex-shrink-0 lesson-icon';
                        const lessonLink = document.querySelector(`.sidebar-scroll .lesson-item[data-id="${currentLessonId}"]`);
                        const statusRow = lessonLink ? lessonLink.querySelector('.sidebar-status-row') : null;
                        if (statusRow) {
                            const firstPill = statusRow.querySelector('.sidebar-status-pill');
                            if (firstPill) {
                                firstPill.className = 'sidebar-status-pill done';
                                firstPill.innerHTML = '<i class="fa-solid fa-check"></i>Đã xong';
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
                                    <i class="fa-solid fa-eye me-1"></i>Xem & chấm
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
                            `<tr><td colspan="4" class="text-center text-danger py-5"><i class="fa-solid fa-triangle-exclamation fa-2x mb-2"></i><br>Lỗi: ${error.message}</td></tr>`;
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
                            mainIcon.className = 'fa-solid fa-circle-check fa-5x mb-4 text-success';
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
                            mainIcon.className = 'fa-solid fa-stopwatch fa-5x mb-4';
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
