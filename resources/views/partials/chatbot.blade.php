@vite('resources/css/pages/chatbot.css')

{{-- Nút kích hoạt --}}
<button class="cb-toggler has-pulse" id="cbToggler" aria-label="Mở trợ lý AI" aria-expanded="false">
    <span class="cb-mascot icon-open" aria-hidden="true">
        <img src="{{ asset('chatbot-mascot-v2.png') }}" alt="">
        <span class="cb-mascot__blink"></span>
    </span>
    <i class="fas fa-times icon-close"></i>
    <span class="cb-badge" id="cbBadge">1</span>
</button>

{{-- Cửa sổ chat --}}
<div class="cb-window" id="cbWindow" role="dialog" aria-label="Trợ lý học tập AI" aria-hidden="true">

    {{-- Header --}}
    <div class="cb-header">
        <div class="cb-avatar">
            <span class="cb-mascot" aria-hidden="true">
                <img src="{{ asset('chatbot-mascot-v2.png') }}" alt="">
                <span class="cb-mascot__blink"></span>
            </span>
        </div>
        <div class="cb-header-info">
            <div class="cb-header-name">Trợ lý học tập AI</div>
            <div class="cb-header-status">
                <span class="cb-status-dot"></span>
                Đang hoạt động
            </div>
        </div>
        <div class="cb-header-actions">
            <button class="cb-icon-btn" id="cbExpand" title="Phóng to" aria-label="Phóng to">
                <i class="fas fa-expand" style="font-size:13px;"></i>
            </button>
            <button class="cb-icon-btn" id="cbClose" title="Đóng" aria-label="Đóng chat">
                <i class="fas fa-times" style="font-size:14px;"></i>
            </button>
        </div>
    </div>

    <div class="cb-context" id="cbContext">
        <i class="fas fa-book-open text-primary"></i>
        <span>Đang bám theo: <strong id="cbContextTitle">Bài học</strong></span>
    </div>

    {{-- Messages --}}
    <div class="cb-body" id="cbBody">
        <div class="cb-date-divider" id="cbDateDivider"></div>
        <div class="cb-row ai">
            <div class="cb-bot-avatar"><img src="{{ asset('chatbot-mascot-v2.png') }}" alt=""></div>
            <div class="cb-msg-wrap">
                <div class="cb-msg ai">
                    @if (auth()->user()->isTeacher())
                        Chào thầy/cô! 👋 Mình có thể tra lịch dạy và các việc cần xử lý hôm nay.
                    @elseif (auth()->user()->isStudent())
                        Chào bạn! 👋 Mình có thể tra lịch học, bài tập và hỗ trợ nội dung bài học.
                    @else
                        Chào bạn! 👋 Mình có thể hỗ trợ thông tin trong SmartLMS.
                    @endif
                </div>
                <span class="cb-msg-time" id="cbWelcomeTime"></span>
            </div>
        </div>
        <div class="cb-quick-actions" aria-label="Câu hỏi gợi ý">
            @if (auth()->user()->isTeacher())
                <button type="button" class="cb-quick-action" data-prompt="Lịch dạy hôm nay của tôi">Lịch dạy hôm nay</button>
                <button type="button" class="cb-quick-action" data-prompt="Bài nào đang chờ tôi chấm?">Bài chờ chấm</button>
            @elseif (auth()->user()->isStudent())
                <button type="button" class="cb-quick-action" data-prompt="Lịch học hôm nay của tôi">Lịch học hôm nay</button>
                <button type="button" class="cb-quick-action" data-prompt="Tôi còn bài tập nào chưa nộp?">Bài tập chưa nộp</button>
            @endif
            <button type="button" class="cb-quick-action" data-prompt="Thông báo chưa đọc của tôi">Thông báo của tôi</button>
        </div>
    </div>

    {{-- Typing --}}
    <div class="cb-typing" id="cbTyping" aria-live="polite" aria-label="AI đang trả lời">
        <div style="padding: 0 0 12px 16px; display:flex; align-items:flex-end; gap:8px;">
            <div class="cb-bot-avatar"><img src="{{ asset('chatbot-mascot-v2.png') }}" alt=""></div>
            <div class="cb-typing-dots">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="cb-footer">
        <div class="cb-input-wrap">
            <textarea id="cbInput" class="cb-input" placeholder="Nhập câu hỏi của bạn..." rows="1"
                aria-label="Nhập tin nhắn"></textarea>
        </div>
        <button class="cb-send-btn" id="cbSend" aria-label="Gửi tin nhắn">
            <i class="fas fa-paper-plane" style="font-size:15px;"></i>
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')
            .getAttribute('content');

        const toggler = document.getElementById('cbToggler');
        const windowEl = document.getElementById('cbWindow');
        const closeBtn = document.getElementById('cbClose');
        const expandBtn = document.getElementById('cbExpand');
        const sendBtn = document.getElementById('cbSend');
        const input = document.getElementById('cbInput');
        const body = document.getElementById('cbBody');
        const typing = document.getElementById('cbTyping');
        const badge = document.getElementById('cbBadge');
        const contextBar = document.getElementById('cbContext');
        const contextTitle = document.getElementById('cbContextTitle');

        let chatHistory = [];
        let isFullscreen = false;
        let currentLessonContext = null;

        // Hiển thị thời gian khởi tạo
        const now = new Date();
        document.getElementById('cbDateDivider').textContent = now.toLocaleDateString('vi-VN', {
            weekday: 'long',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
        document.getElementById('cbWelcomeTime').textContent = formatTime(now);

        // ── Toggle mở / đóng ──
        toggler.addEventListener('click', () => {
            const isActive = windowEl.classList.toggle('active');
            toggler.classList.toggle('active', isActive);
            toggler.classList.remove('has-pulse');
            toggler.setAttribute('aria-expanded', isActive);
            windowEl.setAttribute('aria-hidden', !isActive);
            badge.style.display = 'none';
            if (isActive) {
                scrollToBottom();
                setTimeout(() => input.focus(), 280);
            }
        });

        closeBtn.addEventListener('click', () => {
            windowEl.classList.remove('active');
            toggler.classList.remove('active');
            toggler.setAttribute('aria-expanded', 'false');
            windowEl.setAttribute('aria-hidden', 'true');
        });

        // ── Phóng to / thu nhỏ ──
        expandBtn.addEventListener('click', () => {
            isFullscreen = !isFullscreen;
            windowEl.classList.toggle('fullscreen', isFullscreen);
            const icon = expandBtn.querySelector('i');
            icon.className = isFullscreen ? 'fas fa-compress' : 'fas fa-expand';
            icon.style.fontSize = '13px';
            expandBtn.setAttribute('title', isFullscreen ? 'Thu nhỏ' : 'Phóng to');
            expandBtn.setAttribute('aria-label', isFullscreen ? 'Thu nhỏ' : 'Phóng to');
            scrollToBottom();
        });

        // ── Auto-resize textarea ──
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        });

        // ── Hiệu ứng ripple khi bấm nút gửi ──
        function spawnRipple(btn, evt) {
            const rect = btn.getBoundingClientRect();
            const ripple = document.createElement('span');
            const size = Math.max(rect.width, rect.height);
            const x = (evt && evt.clientX ? evt.clientX - rect.left : rect.width / 2) - size / 2;
            const y = (evt && evt.clientY ? evt.clientY - rect.top : rect.height / 2) - size / 2;
            ripple.className = 'cb-ripple';
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            btn.appendChild(ripple);
            ripple.addEventListener('animationend', () => ripple.remove());
        }

        sendBtn.addEventListener('click', (evt) => spawnRipple(sendBtn, evt));

        // ── Gửi tin nhắn ──
        const sendMessage = async (presetMessage = null, presetContext = null) => {
            const message = (presetMessage ?? input.value).trim();
            if (!message) return;

            if (!presetMessage) {
                input.value = '';
            }
            input.style.height = 'auto';
            input.disabled = true;
            sendBtn.disabled = true;
            document.querySelectorAll('.cb-quick-action').forEach(button => button.disabled = true);

            appendMessage(message, 'user');
            chatHistory.push({
                role: 'user',
                content: message
            });

            typing.classList.add('visible');
            scrollToBottom();

            try {
                const res = await axios.post('{{ route('chatbot.send') }}', {
                    messages: chatHistory,
                    lesson_context: presetContext || currentLessonContext
                });
                typing.classList.remove('visible');

                const reply = res.data.reply;
                appendMessage(reply, 'ai');
                chatHistory.push({
                    role: 'assistant',
                    content: reply
                });

            } catch (err) {
                console.error(err);
                typing.classList.remove('visible');
                appendMessage('Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.', 'ai');
                chatHistory.pop();
            } finally {
                input.disabled = false;
                sendBtn.disabled = false;
                document.querySelectorAll('.cb-quick-action').forEach(button => button.disabled = false);
                input.focus();
            }
        };

        sendBtn.addEventListener('click', sendMessage);
        document.querySelectorAll('.cb-quick-action').forEach(button => {
            button.addEventListener('click', () => sendMessage(button.dataset.prompt));
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        window.SmartLmsAiTutor = {
            setLessonContext(context) {
                currentLessonContext = context && context.lesson_id ? context : null;
                updateContextBar();
            },
            clearLessonContext() {
                currentLessonContext = null;
                updateContextBar();
            },
            openWithPrompt(message, context = null) {
                if (context && context.lesson_id) {
                    currentLessonContext = context;
                    updateContextBar();
                }

                openChatWindow();
                sendMessage(message, context || currentLessonContext);
            }
        };

        // ── Helpers ──
        function appendMessage(text, sender) {
            const row = document.createElement('div');
            row.className = 'cb-row ' + sender;

            const html = text
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');

            const timeStr = formatTime(new Date());

            if (sender === 'ai') {
                row.innerHTML = `
                <div class="cb-bot-avatar"><img src="{{ asset('chatbot-mascot-v2.png') }}" alt=""></div>
                <div class="cb-msg-wrap">
                    <div class="cb-msg ai">${html}</div>
                    <span class="cb-msg-time">${timeStr}</span>
                </div>`;
            } else {
                row.innerHTML = `
                <div class="cb-msg-wrap">
                    <div class="cb-msg user">${html}</div>
                    <span class="cb-msg-time">${timeStr}</span>
                </div>`;
            }

            body.appendChild(row);
            scrollToBottom();
        }

        function scrollToBottom() {
            requestAnimationFrame(() => {
                body.scrollTop = body.scrollHeight;
            });
        }

        function openChatWindow() {
            const isActive = windowEl.classList.contains('active');
            if (!isActive) {
                windowEl.classList.add('active');
                toggler.classList.add('active');
                toggler.classList.remove('has-pulse');
                toggler.setAttribute('aria-expanded', 'true');
                windowEl.setAttribute('aria-hidden', 'false');
                badge.style.display = 'none';
            }

            scrollToBottom();
            setTimeout(() => input.focus(), 120);
        }

        function updateContextBar() {
            if (!contextBar || !contextTitle) return;

            if (!currentLessonContext || !currentLessonContext.lesson_title) {
                contextBar.classList.remove('active');
                contextTitle.textContent = 'Bài học';
                return;
            }

            contextTitle.textContent = currentLessonContext.lesson_title;
            contextBar.classList.add('active');
        }

        function formatTime(date) {
            return date.toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    });
</script>
