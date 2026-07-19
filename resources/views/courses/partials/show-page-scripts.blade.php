    <script>
        (function() {
            var desktopAccordion = document.querySelector('.sidebar-scroll .accordion');
            var mobileContent = document.getElementById('mobile-sidebar-content');
            if (desktopAccordion && mobileContent) {
                var clone = desktopAccordion.cloneNode(true);
                clone.id = 'courseAccordionMobile';
                clone.querySelectorAll('[data-bs-parent]').forEach(function(el) {
                    el.setAttribute('data-bs-parent', '#courseAccordionMobile');
                });
                mobileContent.appendChild(clone);
            }

            var drawer = document.getElementById('mobile-sidebar-drawer');
            var overlay = document.getElementById('mobile-sidebar-overlay');
            var btnOpen = document.getElementById('btn-open-sidebar');
            var btnClose = document.getElementById('btn-close-sidebar');

            function openDrawer() {
                drawer.classList.add('open');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeDrawer() {
                drawer.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (btnOpen) btnOpen.addEventListener('click', openDrawer);
            if (btnClose) btnClose.addEventListener('click', closeDrawer);
            if (overlay) overlay.addEventListener('click', closeDrawer);

            if (mobileContent) {
                mobileContent.addEventListener('click', function(e) {
                    var target = e.target.closest('.lesson-item, .assignment-item, .quiz-item');
                    if (!target) return;
                    e.preventDefault();
                    var type = target.classList.contains('assignment-item') ? 'assignment' :
                        (target.classList.contains('quiz-item') ? 'quiz' : 'lesson');
                    var id = target.getAttribute('data-id');
                    var selector = type === 'assignment' ?
                        `.sidebar-scroll .assignment-item[data-id="${id}"]` :
                        (type === 'quiz' ?
                            `.sidebar-scroll .quiz-item[data-id="${id}"]` :
                            `.sidebar-scroll .lesson-item[data-id="${id}"]`);
                    var desktopTarget = document.querySelector(selector);
                    if (desktopTarget) desktopTarget.click();
                    closeDrawer();
                });
            }
        })();

        (function() {
            var startButton = document.getElementById('start-presentation-btn');
            var exitButton = document.getElementById('exit-presentation-btn');
            var fontUpButton = document.getElementById('presentation-font-up');
            var fontDownButton = document.getElementById('presentation-font-down');
            var fontScale = 1;

            if (!startButton) return;

            function updateFontScale(delta) {
                fontScale = Math.max(.75, Math.min(1.5, fontScale + delta));
                document.body.style.setProperty('--presentation-font-scale', fontScale.toFixed(2));
            }

            function startPresentation() {
                document.body.classList.add('course-presentation-mode');
                document.body.style.setProperty('--presentation-font-scale', fontScale.toFixed(2));
                window.scrollTo({ top: 0, behavior: 'smooth' });

                if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen().catch(function() {
                        // Chế độ trình chiếu vẫn hoạt động khi trình duyệt từ chối fullscreen.
                    });
                }
            }

            function exitPresentation(leaveFullscreen) {
                document.body.classList.remove('course-presentation-mode');
                document.body.style.removeProperty('--presentation-font-scale');

                if (leaveFullscreen !== false && document.fullscreenElement && document.exitFullscreen) {
                    document.exitFullscreen().catch(function() {});
                }
            }

            startButton.addEventListener('click', startPresentation);
            if (new URLSearchParams(window.location.search).get('presentation') === '1') {
                startPresentation();
            }
            if (exitButton) exitButton.addEventListener('click', function() { exitPresentation(true); });
            if (fontUpButton) fontUpButton.addEventListener('click', function() { updateFontScale(.1); });
            if (fontDownButton) fontDownButton.addEventListener('click', function() { updateFontScale(-.1); });

            document.addEventListener('keydown', function(event) {
                if (!document.body.classList.contains('course-presentation-mode')) return;
                if (event.key === 'Escape') exitPresentation(false);
                if (event.key === '+' || event.key === '=') updateFontScale(.1);
                if (event.key === '-') updateFontScale(-.1);
            });

            document.addEventListener('fullscreenchange', function() {
                if (!document.fullscreenElement && document.body.classList.contains('course-presentation-mode')) {
                    exitPresentation(false);
                }
            });
        })();

        @if ($isCourseManager)
        (function() {
            const form = document.getElementById('ai-course-plan-form');
            if (!form) return;
            const generateBtn = document.getElementById('ai-plan-generate-btn');
            const applyBtn = document.getElementById('ai-plan-apply-btn');
            const backBtn = document.getElementById('ai-plan-back-btn');
            const formStep = document.getElementById('ai-plan-form-step');
            const reviewStep = document.getElementById('ai-plan-review-step');
            const loading = document.getElementById('ai-plan-loading');
            const result = document.getElementById('ai-plan-result');
            const summary = document.getElementById('ai-plan-summary');
            const errorBox = document.getElementById('ai-plan-error');
            const generateUrl = @json(route('courses.ai-plan.generate', $course));
            const applyUrl = @json(route('courses.ai-plan.apply', $course));

            const esc = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
            const showError = message => { errorBox.textContent = message; errorBox.classList.remove('d-none'); };
            const setLoading = active => {
                loading.classList.toggle('d-none', !active);
                generateBtn.disabled = active;
                formStep.classList.toggle('d-none', active);
            };

            function renderPlan(plan) {
                summary.textContent = plan.summary || 'Bản nháp chương trình đã được tạo.';
                result.innerHTML = (plan.modules || []).map((module, moduleIndex) => `
                    <section class="ai-plan-module" data-module>
                        <div class="ai-plan-module-head">
                            <span class="badge bg-primary">Chương ${moduleIndex + 1}</span>
                            <input class="form-control ai-plan-module-title" value="${esc(module.title)}" aria-label="Tên chương">
                            <button type="button" class="ai-plan-remove" data-remove-module title="Xóa chương"><i class="fa-solid fa-trash"></i></button>
                        </div>
                        <div data-lessons>${(module.lessons || []).map((lesson, lessonIndex) => `
                            <article class="ai-plan-lesson" data-lesson>
                                <div class="ai-plan-lesson-head">
                                    <span class="badge bg-light text-dark">Buổi ${lessonIndex + 1}</span>
                                    <input class="form-control fw-semibold" value="${esc(lesson.title)}" aria-label="Tên bài học">
                                    <button type="button" class="ai-plan-remove" data-remove-lesson title="Xóa bài"><i class="fa-solid fa-times"></i></button>
                                </div>
                                <div class="ai-plan-lesson-content" contenteditable="true">${lesson.content || ''}</div>
                            </article>`).join('')}</div>
                    </section>`).join('');
                formStep.classList.add('d-none');
                loading.classList.add('d-none');
                reviewStep.classList.remove('d-none');
                generateBtn.classList.add('d-none');
                applyBtn.classList.remove('d-none');
                backBtn.classList.remove('d-none');
            }

            function collectPlan() {
                return { modules: [...result.querySelectorAll('[data-module]')].map(module => ({
                    title: module.querySelector('.ai-plan-module-title').value.trim(),
                    lessons: [...module.querySelectorAll('[data-lesson]')].map(lesson => ({
                        title: lesson.querySelector('input').value.trim(),
                        content: lesson.querySelector('.ai-plan-lesson-content').innerHTML.trim()
                    }))
                })).filter(module => module.title && module.lessons.length) };
            }

            generateBtn.addEventListener('click', async () => {
                if (!form.reportValidity()) return;
                errorBox.classList.add('d-none');
                setLoading(true);
                try {
                    const payload = Object.fromEntries(new FormData(form).entries());
                    payload.session_count = Number(payload.session_count);
                    payload.minutes_per_session = Number(payload.minutes_per_session);
                    const response = await axios.post(generateUrl, payload);
                    renderPlan(response.data.plan);
                } catch (error) {
                    setLoading(false);
                    showError(error.response?.data?.message || 'Không thể tạo kế hoạch lúc này.');
                }
            });

            backBtn.addEventListener('click', () => {
                reviewStep.classList.add('d-none');
                formStep.classList.remove('d-none');
                generateBtn.classList.remove('d-none');
                applyBtn.classList.add('d-none');
                backBtn.classList.add('d-none');
            });

            result.addEventListener('click', event => {
                const removeLesson = event.target.closest('[data-remove-lesson]');
                const removeModule = event.target.closest('[data-remove-module]');
                if (removeLesson) removeLesson.closest('[data-lesson]').remove();
                if (removeModule) removeModule.closest('[data-module]').remove();
            });

            applyBtn.addEventListener('click', async () => {
                const plan = collectPlan();
                if (!plan.modules.length) return showError('Kế hoạch cần ít nhất một chương có bài học.');
                errorBox.classList.add('d-none');
                applyBtn.disabled = true;
                applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang áp dụng...';
                try {
                    const response = await axios.post(applyUrl, plan);
                    window.location.href = response.data.redirect_url;
                } catch (error) {
                    applyBtn.disabled = false;
                    applyBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Áp dụng vào khóa học';
                    showError(error.response?.data?.message || 'Không thể áp dụng kế hoạch.');
                }
            });
        })();
        @endif
    </script>
