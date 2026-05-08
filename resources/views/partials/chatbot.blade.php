<style>
    /* Nút kích hoạt Chatbot */
    .chatbot-toggler {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background-color: #0d6efd;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
        z-index: 9999;
        transition: all 0.3s ease;
    }

    .chatbot-toggler:hover {
        transform: scale(1.05);
        background-color: #0b5ed7;
    }

    /* Khung Chat */
    .chatbot-window {
        position: fixed;
        bottom: 100px;
        right: 30px;
        width: 350px;
        height: 450px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        z-index: 9998;
        opacity: 0;
        pointer-events: none;
        transform: translateY(20px);
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
        overflow: hidden;
    }

    .chatbot-window.active {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0);
    }

    .chat-header {
        background: #0d6efd;
        color: white;
        padding: 15px;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-body {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .chat-msg {
        max-width: 80%;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 0.9rem;
        line-height: 1.4;
    }

    .chat-msg.user {
        background: #0d6efd;
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 2px;
    }

    .chat-msg.ai {
        background: white;
        color: #333;
        border: 1px solid #dee2e6;
        align-self: flex-start;
        border-bottom-left-radius: 2px;
    }

    .chat-input-area {
        padding: 15px;
        background: white;
        border-top: 1px solid #dee2e6;
        display: flex;
        gap: 10px;
    }

    .typing-indicator {
        font-size: 0.8rem;
        color: #6c757d;
        font-style: italic;
        display: none;
        align-self: flex-start;
        padding: 0 5px;
    }
</style>

<div class="chatbot-toggler" id="chatbotToggler" title="Trợ lý AI">
    <i class="fas fa-robot fa-lg"></i>
</div>

<div class="chatbot-window" id="chatbotWindow">
    <div class="chat-header">
        <span><i class="fas fa-robot me-2"></i> Trợ lý học tập AI</span>
        <button type="button" class="btn-close btn-close-white" style="font-size: 0.8rem;" id="closeChat"></button>
    </div>

    <div class="chat-body" id="chatBody">
        <div class="chat-msg ai">Chào bạn! Mình có thể giúp gì cho bài học của bạn hôm nay?</div>
    </div>
    <div class="typing-indicator" id="typingIndicator">AI đang nhập...</div>

    <div class="chat-input-area">
        <input type="text" id="chatInput" class="form-control rounded-pill" placeholder="Nhập câu hỏi...">
        <button id="sendBtn" class="btn btn-primary rounded-circle" style="width: 40px; height: 40px; padding: 0;">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')
            .getAttribute('content');

        const toggler = document.getElementById('chatbotToggler');
        const windowEl = document.getElementById('chatbotWindow');
        const closeBtn = document.getElementById('closeChat');
        const sendBtn = document.getElementById('sendBtn');
        const chatInput = document.getElementById('chatInput');
        const chatBody = document.getElementById('chatBody');
        const typingIndicator = document.getElementById('typingIndicator');

        // BỘ NHỚ LƯU TRỮ LỊCH SỬ CHAT
        let chatHistory = [];

        toggler.addEventListener('click', () => windowEl.classList.toggle('active'));
        closeBtn.addEventListener('click', () => windowEl.classList.remove('active'));

        const scrollToBottom = () => {
            chatBody.scrollTop = chatBody.scrollHeight;
        };

        const appendMessage = (text, sender) => {
            const msgDiv = document.createElement('div');
            msgDiv.className = `chat-msg ${sender}`;

            let formattedText = text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');

            msgDiv.innerHTML = formattedText;
            chatBody.appendChild(msgDiv);
            scrollToBottom();
        };

        const sendMessage = async () => {
            const message = chatInput.value.trim();
            if (!message) return;

            chatInput.value = '';
            chatInput.disabled = true;
            sendBtn.disabled = true;

            appendMessage(message, 'user');

            // 1. Lưu câu hỏi của học sinh vào bộ nhớ
            chatHistory.push({
                role: 'user',
                content: message
            });

            typingIndicator.style.display = 'block';
            scrollToBottom();

            try {
                // 2. Gửi TOÀN BỘ LỊCH SỬ lên server thay vì chỉ 1 tin nhắn
                const response = await axios.post('{{ route('chatbot.send') }}', {
                    messages: chatHistory
                });

                typingIndicator.style.display = 'none';
                const aiReply = response.data.reply;

                appendMessage(aiReply, 'ai');

                // 3. Lưu câu trả lời của AI vào bộ nhớ
                chatHistory.push({
                    role: 'assistant',
                    content: aiReply
                });

            } catch (error) {
                console.error(error);
                typingIndicator.style.display = 'none';
                appendMessage('Đã xảy ra lỗi khi gọi AI. Vui lòng thử lại.', 'ai');

                // Nếu lỗi, xóa câu hỏi vừa rồi khỏi bộ nhớ để tránh kẹt
                chatHistory.pop();
            } finally {
                chatInput.disabled = false;
                sendBtn.disabled = false;
                chatInput.focus();
            }
        };

        sendBtn.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });
    });
</script>
