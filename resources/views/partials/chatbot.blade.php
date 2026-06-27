<style>
    :root {
        --cb-primary: #2563eb;
        --cb-primary-dark: #1d4ed8;
        --cb-primary-light: #eff6ff;
        --cb-surface: #ffffff;
        --cb-surface-alt: #f8fafc;
        --cb-border: #e2e8f0;
        --cb-text: #0f172a;
        --cb-text-muted: #64748b;
        --cb-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.06);
        --cb-shadow-btn: 0 4px 14px rgba(37, 99, 235, 0.4);
        --cb-radius: 16px;
        --cb-radius-msg: 18px;
    }

    /* ── Toggler ── */
    .cb-toggler {
        position: fixed;
        bottom: 28px;
        right: 28px;
        width: 56px;
        height: 56px;
        background: var(--cb-primary);
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: var(--cb-shadow-btn);
        z-index: 9999;
        border: none;
        transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
    }

    .cb-toggler:hover {
        background: var(--cb-primary-dark);
        transform: scale(1.08);
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
    }

    .cb-toggler:active {
        transform: scale(0.96);
    }

    /* Pulse ring khi chưa mở */
    .cb-toggler::before {
        content: '';
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        border: 2px solid var(--cb-primary);
        opacity: 0;
        animation: cb-pulse 2.5s ease-out infinite;
    }

    .cb-toggler.has-pulse::before {
        opacity: 1;
    }

    @keyframes cb-pulse {
        0% {
            transform: scale(1);
            opacity: 0.6;
        }

        70% {
            transform: scale(1.5);
            opacity: 0;
        }

        100% {
            transform: scale(1.5);
            opacity: 0;
        }
    }

    /* Icon toggle */
    .cb-toggler .icon-open,
    .cb-toggler .icon-close {
        transition: opacity 0.2s, transform 0.2s;
        position: absolute;
    }

    .cb-toggler .icon-close {
        opacity: 0;
        transform: rotate(-90deg);
    }

    .cb-toggler.active .icon-open {
        opacity: 0;
        transform: rotate(90deg);
    }

    .cb-toggler.active .icon-close {
        opacity: 1;
        transform: rotate(0deg);
    }

    /* Badge thông báo */
    .cb-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        width: 18px;
        height: 18px;
        background: #ef4444;
        color: #fff;
        border-radius: 50%;
        font-size: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        display: none;
    }

    /* ── Cửa sổ chat ── */
    .cb-window {
        position: fixed;
        bottom: 96px;
        right: 28px;
        width: 380px;
        height: 560px;
        background: var(--cb-surface);
        border-radius: var(--cb-radius);
        box-shadow: var(--cb-shadow);
        display: flex;
        flex-direction: column;
        z-index: 9998;
        border: 1px solid var(--cb-border);
        overflow: hidden;

        /* Trạng thái đóng */
        opacity: 0;
        pointer-events: none;
        transform: translateY(16px) scale(0.97);
        transform-origin: bottom right;
        transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .cb-window.active {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0) scale(1);
    }

    /* Fullscreen */
    .cb-window.fullscreen {
        width: min(720px, 94vw);
        height: min(860px, 90vh);
        /* bottom: 4vh; */
        right: max(2vw, 12px);
        border-radius: 20px;
    }

    /* ── Header ── */
    .cb-header {
        background: var(--cb-primary);
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
        user-select: none;
    }

    .cb-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .cb-header-info {
        flex: 1;
        min-width: 0;
    }

    .cb-header-name {
        color: #fff;
        font-weight: 600;
        font-size: 0.9rem;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cb-header-status {
        color: rgba(255, 255, 255, 0.75);
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 1px;
    }

    .cb-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #4ade80;
        flex-shrink: 0;
        box-shadow: 0 0 0 2px rgba(74, 222, 128, 0.3);
    }

    .cb-header-actions {
        display: flex;
        gap: 4px;
        align-items: center;
    }

    .cb-icon-btn {
        width: 32px;
        height: 32px;
        border: none;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.15s;
        flex-shrink: 0;
    }

    .cb-icon-btn:hover {
        background: rgba(255, 255, 255, 0.22);
    }

    .cb-context {
        align-items: center;
        background: #eff6ff;
        border-bottom: 1px solid var(--cb-border);
        color: var(--cb-text-muted);
        display: none;
        font-size: 0.75rem;
        gap: 8px;
        padding: 9px 14px;
    }

    .cb-context.active {
        display: flex;
    }

    .cb-context strong {
        color: var(--cb-text);
        font-weight: 700;
    }

    /* ── Body ── */
    .cb-body {
        flex: 1;
        padding: 16px;
        overflow-y: auto;
        background: var(--cb-surface-alt);
        display: flex;
        flex-direction: column;
        gap: 10px;
        scroll-behavior: smooth;
    }

    .cb-body::-webkit-scrollbar {
        width: 4px;
    }

    .cb-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .cb-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    /* ── Ngày giờ ── */
    .cb-date-divider {
        text-align: center;
        font-size: 0.72rem;
        color: var(--cb-text-muted);
        margin: 4px 0;
        letter-spacing: 0.03em;
    }

    /* ── Tin nhắn ── */
    .cb-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;
    }

    .cb-row.user {
        flex-direction: row-reverse;
    }

    .cb-bot-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--cb-primary-light);
        color: var(--cb-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 14px;
        border: 1.5px solid var(--cb-border);
    }

    .cb-msg-wrap {
        display: flex;
        flex-direction: column;
        max-width: 78%;
    }

    .cb-row.user .cb-msg-wrap {
        align-items: flex-end;
    }

    .cb-msg {
        padding: 10px 14px;
        border-radius: var(--cb-radius-msg);
        font-size: 0.875rem;
        line-height: 1.55;
        word-break: break-word;
        overflow-wrap: anywhere;
        position: relative;
        animation: cb-fadein 0.2s ease;
    }

    @keyframes cb-fadein {
        from {
            opacity: 0;
            transform: translateY(6px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .cb-msg.ai {
        background: var(--cb-surface);
        color: var(--cb-text);
        border: 1px solid var(--cb-border);
        border-bottom-left-radius: 4px;
    }

    .cb-msg.user {
        background: var(--cb-primary);
        color: #fff;
        border-bottom-right-radius: 4px;
    }

    .cb-msg-time {
        font-size: 0.68rem;
        color: var(--cb-text-muted);
        margin-top: 3px;
        padding: 0 2px;
    }

    /* ── Typing indicator ── */
    .cb-typing {
        display: none;
        align-items: flex-end;
        gap: 8px;
    }

    .cb-typing.visible {
        display: flex;
    }

    .cb-typing-dots {
        background: var(--cb-surface);
        border: 1px solid var(--cb-border);
        border-radius: var(--cb-radius-msg);
        border-bottom-left-radius: 4px;
        padding: 12px 16px;
        display: flex;
        gap: 5px;
        align-items: center;
    }

    .cb-typing-dots span {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #94a3b8;
        animation: cb-bounce 1.2s ease infinite;
    }

    .cb-typing-dots span:nth-child(2) {
        animation-delay: 0.2s;
    }

    .cb-typing-dots span:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes cb-bounce {

        0%,
        60%,
        100% {
            transform: translateY(0);
        }

        30% {
            transform: translateY(-6px);
        }
    }

    /* ── Input area ── */
    .cb-footer {
        padding: 12px 14px;
        background: var(--cb-surface);
        border-top: 1px solid var(--cb-border);
        display: flex;
        align-items: flex-end;
        gap: 10px;
        flex-shrink: 0;
    }

    .cb-input-wrap {
        flex: 1;
        position: relative;
    }

    .cb-input {
        width: 100%;
        padding: 10px 14px;
        font-size: 0.875rem;
        border: 1.5px solid var(--cb-border);
        border-radius: 12px;
        outline: none;
        resize: none;
        font-family: inherit;
        line-height: 1.5;
        color: var(--cb-text);
        background: var(--cb-surface-alt);
        max-height: 120px;
        overflow-y: auto;
        transition: border-color 0.15s, box-shadow 0.15s;
        box-sizing: border-box;
    }

    .cb-input:focus {
        border-color: var(--cb-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        background: var(--cb-surface);
    }

    .cb-input:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .cb-input::placeholder {
        color: #94a3b8;
    }

    .cb-send-btn {
        width: 42px;
        height: 42px;
        border: none;
        border-radius: 12px;
        background: var(--cb-primary);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
    }

    .cb-send-btn:hover:not(:disabled) {
        background: var(--cb-primary-dark);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.45);
    }

    .cb-send-btn:active:not(:disabled) {
        transform: scale(0.94);
    }

    .cb-send-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* ── Responsive ── */
    @media (max-width: 480px) {
        .cb-toggler {
            bottom: 20px;
            right: 20px;
            width: 52px;
            height: 52px;
        }

        .cb-window {
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100dvh;
            border-radius: 0;
        }

        .cb-window.active {
            transform: translateY(0) scale(1);
        }

        .cb-window.fullscreen {
            width: 100vw;
            height: 100dvh;
            bottom: 0;
            right: 0;
            border-radius: 0;
        }
    }

    @media (min-width: 481px) and (max-width: 768px) {
        .cb-window {
            width: 340px;
        }
    }
</style>

{{-- Nút kích hoạt --}}
<button class="cb-toggler has-pulse" id="cbToggler" aria-label="Mở trợ lý AI" aria-expanded="false">
    <i class="fas fa-robot fa-lg icon-open"></i>
    <i class="fas fa-times icon-close"></i>
    <span class="cb-badge" id="cbBadge">1</span>
</button>

{{-- Cửa sổ chat --}}
<div class="cb-window" id="cbWindow" role="dialog" aria-label="Trợ lý học tập AI" aria-hidden="true">

    {{-- Header --}}
    <div class="cb-header">
        <div class="cb-avatar">
            <i class="fas fa-robot" style="color:#fff; font-size:16px;"></i>
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
            <div class="cb-bot-avatar"><i class="fas fa-robot"></i></div>
            <div class="cb-msg-wrap">
                <div class="cb-msg ai">Chào bạn! 👋 Mình có thể giúp gì cho bài học của bạn hôm nay?</div>
                <span class="cb-msg-time" id="cbWelcomeTime"></span>
            </div>
        </div>
    </div>

    {{-- Typing --}}
    <div class="cb-typing" id="cbTyping" aria-live="polite" aria-label="AI đang trả lời">
        <div style="padding: 0 0 12px 16px; display:flex; align-items:flex-end; gap:8px;">
            <div class="cb-bot-avatar"><i class="fas fa-robot"></i></div>
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
                input.focus();
            }
        };

        sendBtn.addEventListener('click', sendMessage);
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
                <div class="cb-bot-avatar"><i class="fas fa-robot"></i></div>
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
