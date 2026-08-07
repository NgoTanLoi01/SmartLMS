    <script>
        (function() {
            var outlineCard = document.querySelector('.course-outline-card');
            var mobileContent = document.getElementById('mobile-sidebar-content');
            var outlineHomeMarker = outlineCard ? document.createComment('course-outline-home') : null;
            var mobileBreakpoint = window.matchMedia('(max-width: 767.98px)');

            if (outlineCard && outlineHomeMarker) outlineCard.before(outlineHomeMarker);

            function syncOutlinePlacement() {
                if (!outlineCard || !outlineHomeMarker || !mobileContent) return;

                if (mobileBreakpoint.matches) {
                    if (outlineCard.parentElement !== mobileContent) mobileContent.appendChild(outlineCard);
                } else if (outlineHomeMarker.parentNode && outlineCard.parentNode !== outlineHomeMarker.parentNode) {
                    outlineHomeMarker.parentNode.insertBefore(outlineCard, outlineHomeMarker.nextSibling);
                }

                if (!mobileBreakpoint.matches && drawer?.classList.contains('open')) closeDrawer();
            }

            syncOutlinePlacement();
            if (mobileBreakpoint.addEventListener) mobileBreakpoint.addEventListener('change', syncOutlinePlacement);
            else mobileBreakpoint.addListener(syncOutlinePlacement);

            document.querySelectorAll('.course-outline-search').forEach(function(input) {
                input.addEventListener('input', function() {
                    var query = this.value.trim().toLocaleLowerCase('vi');
                    var outline = document.querySelector(this.getAttribute('data-outline-target'));
                    if (!outline) return;
                    var visibleCount = 0;

                    outline.querySelectorAll(':scope > .accordion-item').forEach(function(item) {
                        var matches = !query || item.textContent.toLocaleLowerCase('vi').includes(query);
                        item.classList.toggle('d-none', !matches);
                        if (matches) visibleCount++;

                        if (query && matches) {
                            var collapse = item.querySelector(':scope > .module-header-wrapper + .accordion-collapse');
                            var toggle = item.querySelector(':scope > .module-header-wrapper .accordion-button');
                            collapse?.classList.add('show');
                            toggle?.classList.remove('collapsed');
                            toggle?.setAttribute('aria-expanded', 'true');
                        }
                    });
                    outline.querySelector('[data-outline-empty]')?.classList.toggle('d-none', visibleCount > 0);
                });
            });

            var drawer = document.getElementById('mobile-sidebar-drawer');
            var overlay = document.getElementById('mobile-sidebar-overlay');
            var btnOpen = document.getElementById('btn-open-sidebar');
            var btnClose = document.getElementById('btn-close-sidebar');
            var lastFocusedElement = null;

            function openDrawer() {
                lastFocusedElement = document.activeElement;
                drawer.classList.add('open');
                overlay.classList.add('active');
                drawer.setAttribute('aria-hidden', 'false');
                overlay.setAttribute('aria-hidden', 'false');
                btnOpen?.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
                btnClose?.focus();
            }

            function closeDrawer(restoreFocus = true) {
                drawer.classList.remove('open');
                overlay.classList.remove('active');
                drawer.setAttribute('aria-hidden', 'true');
                overlay.setAttribute('aria-hidden', 'true');
                btnOpen?.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
                if (restoreFocus) lastFocusedElement?.focus?.();
            }

            if (btnOpen) btnOpen.addEventListener('click', openDrawer);
            if (btnClose) btnClose.addEventListener('click', closeDrawer);
            if (overlay) overlay.addEventListener('click', closeDrawer);

            document.addEventListener('keydown', function(e) {
                if (!drawer?.classList.contains('open')) return;

                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeDrawer();
                    return;
                }

                if (e.key !== 'Tab') return;
                var focusable = Array.from(drawer.querySelectorAll(
                    'button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )).filter(function(el) { return el.offsetParent !== null; });
                if (!focusable.length) return;
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            });

            if (mobileContent) {
                mobileContent.addEventListener('click', function(e) {
                    var target = e.target.closest('.lesson-item, .assignment-item, .quiz-item');
                    if (!target) return;
                    closeDrawer(false);
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
            const loadingTitle = document.getElementById('ai-plan-loading-title');
            const loadingDetail = document.getElementById('ai-plan-loading-detail');
            const result = document.getElementById('ai-plan-result');
            const summary = document.getElementById('ai-plan-summary');
            const errorBox = document.getElementById('ai-plan-error');
            const generateUrl = @json(route('courses.ai-plan.generate', $course));
            const applyUrl = @json(route('courses.ai-plan.apply', $course));

            const esc = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
            const showError = message => { errorBox.textContent = message; errorBox.classList.remove('d-none'); };
            const sleep = milliseconds => new Promise(resolve => setTimeout(resolve, milliseconds));
            const setLoading = active => {
                loading.classList.toggle('d-none', !active);
                generateBtn.disabled = active;
                formStep.classList.toggle('d-none', active);
                if (active) {
                    loadingTitle.textContent = 'Đang gửi yêu cầu...';
                    loadingDetail.textContent = 'Vui lòng giữ trang này mở trong lúc hệ thống bắt đầu xử lý.';
                }
            };

            const requestErrorMessage = (error, fallback) => {
                const data = error.response?.data || {};
                const validationMessage = Object.values(data.errors || {}).flat()[0];
                if (validationMessage) return validationMessage;
                if (data.message) return data.message;

                const status = error.response?.status;
                if (status === 419) return 'Phiên đăng nhập đã hết hạn. Hãy tải lại trang rồi thử lại.';
                if (status === 429) return 'Bạn thao tác quá nhanh. Hãy chờ khoảng một phút rồi thử lại.';
                if ([502, 503, 504].includes(status)) return 'Máy chủ AI đang bận hoặc phản hồi chậm. Vui lòng thử lại sau ít phút.';
                if (error.code === 'ECONNABORTED') return 'Kết nối đến máy chủ mất quá nhiều thời gian. Tác vụ AI có thể vẫn đang chạy.';
                if (!error.response) return 'Mất kết nối tới máy chủ. Hãy kiểm tra mạng và thử lại.';

                return fallback;
            };

            async function waitForCoursePlan(data) {
                if (!data.queued) return data;

                const interval = Math.max(1000, Number(data.poll_interval_ms) || 2000);
                const timeoutSeconds = Math.max(60, Number(data.poll_timeout_seconds) || 420);
                const maxAttempts = Math.ceil(timeoutSeconds * 1000 / interval);
                const startedAt = Date.now();
                let temporaryFailures = 0;

                for (let attempt = 0; attempt < maxAttempts; attempt++) {
                    const elapsedSeconds = Math.floor((Date.now() - startedAt) / 1000);
                    loadingTitle.textContent = attempt === 0 ? 'Đã xếp hàng, đang chờ AI...' : 'AI đang xây dựng nội dung từng bài...';
                    loadingDetail.textContent = `Đã xử lý ${elapsedSeconds} giây. Hệ thống đang tạo khung toàn khóa rồi viết nội dung thực hành theo từng nhóm bài.`;

                    try {
                        const statusResponse = await axios.get(data.status_url, {
                            headers: { 'Accept': 'application/json' },
                            timeout: 15000
                        });
                        const operation = statusResponse.data || {};
                        temporaryFailures = 0;

                        const progress = operation.progress || {};
                        const completedLessons = Number(progress.completed_lessons) || 0;
                        const totalLessons = Number(progress.total_lessons) || 0;
                        if (operation.status === 'processing' && totalLessons > 0) {
                            loadingTitle.textContent = `Đang viết nội dung bài ${Math.min(completedLessons + 1, totalLessons)}/${totalLessons}...`;
                            loadingDetail.textContent = completedLessons > 0
                                ? `Đã hoàn thành và lưu ${completedLessons}/${totalLessons} bài. Nếu AI phải thử lại, hệ thống tiếp tục từ phần đã lưu.`
                                : 'Khung khóa học đã hoàn thành. AI đang viết nội dung chi tiết cho bài đầu tiên.';
                        }

                        if (operation.status === 'completed') return operation.result || {};
                        if (operation.status === 'failed') {
                            throw new Error(operation.message || 'AI không thể hoàn thành kế hoạch sau khi đã thử lại.');
                        }
                        if (operation.status === 'queued') loadingTitle.textContent = 'Đã xếp hàng, đang chờ AI...';
                    } catch (error) {
                        if (!error.response && error.message && !error.code) throw error;
                        if ([401, 403, 404, 419].includes(error.response?.status)) {
                            throw new Error(requestErrorMessage(error, 'Không thể kiểm tra trạng thái tác vụ AI.'));
                        }
                        temporaryFailures++;
                        if (temporaryFailures >= 4) {
                            throw new Error(requestErrorMessage(error, 'Mất kết nối khi theo dõi tác vụ AI. Vui lòng thử lại.'));
                        }
                        loadingDetail.textContent = 'Kết nối tạm thời gián đoạn, hệ thống đang thử nối lại...';
                    }

                    await sleep(interval);
                }

                throw new Error('AI xử lý quá lâu. Tác vụ có thể vẫn đang chạy; hãy thử lại sau hoặc giảm số buổi.');
            }

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
                    const operationResult = await waitForCoursePlan(response.data);
                    if (!operationResult.plan?.modules?.length) {
                        throw new Error('AI chưa trả về kế hoạch có chương và bài học hợp lệ.');
                    }
                    renderPlan(operationResult.plan);
                } catch (error) {
                    setLoading(false);
                    showError(error.response || error.code
                        ? requestErrorMessage(error, 'Không thể tạo kế hoạch lúc này.')
                        : (error.message || 'Không thể tạo kế hoạch lúc này.'));
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
