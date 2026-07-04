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

        // Khởi tạo trình soạn thảo
        tinymce.init({
            selector: '#addLessonContent, #editLessonContent', // Gắn vào 2 thẻ textarea
            height: 250,
            menubar: false,
            plugins: 'lists link image preview searchreplace visualblocks code fullscreen table code wordcount',
            toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link table | removeformat | code',
            setup: function(editor) {
                // Đồng bộ dữ liệu từ TinyMCE về textarea gốc để Form có thể gửi đi
                editor.on('change', function() {
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
