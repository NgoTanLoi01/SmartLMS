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

    /* Khung Chat Mặc định */
    .chatbot-window {
        position: fixed;
        bottom: 100px;
        right: 30px;
        width: 380px;
        /* Mở rộng xíu so với 350px cũ */
        height: 550px;
        /* Cao hơn chút để dễ đọc */
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        z-index: 9998;
        opacity: 0;
        pointer-events: none;
        transform: translateY(20px);
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        border: 1px solid #dee2e6;
        overflow: hidden;
    }

    .chatbot-window.active {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0);
    }

    /* CHẾ ĐỘ FULL MÀN HÌNH */
    .chatbot-window.fullscreen {
        width: 700px;
        height: 900px;
        bottom: 5vh;
        right: 5vw;
        border-radius: 16px;
    }

    .chat-header {
        background: #0d6efd;
        color: white;
        padding: 15px 20px;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Nhóm nút điều khiển header */
    .chat-controls {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .chat-controls i {
        cursor: pointer;
        opacity: 0.8;
        transition: opacity 0.2s;
    }

    .chat-controls i:hover {
        opacity: 1;
    }

    .chat-body {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    /* Tối ưu Scrollbar cho đẹp */
    .chat-body::-webkit-scrollbar {
        width: 6px;
    }

    .chat-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .chat-body::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 10px;
    }

    .chat-msg {
        max-width: 85%;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 0.95rem;
        line-height: 1.5;
        word-wrap: break-word;
        /* Chống tràn chữ */
        overflow-wrap: anywhere;
    }

    /* Khi fullscreen, tin nhắn có thể chiếm diện tích rộng hơn */
    .chatbot-window.fullscreen .chat-msg {
        max-width: 75%;
        font-size: 1rem;
        /* Chữ to hơn một chút khi full màn hình */
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
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
    }

    .chat-input-area {
        padding: 15px;
        background: white;
        border-top: 1px solid #dee2e6;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .chat-input-area input {
        padding: 12px 20px;
    }

    .typing-indicator {
        font-size: 0.85rem;
        color: #6c757d;
        font-style: italic;
        display: none;
        align-self: flex-start;
        padding: 0 20px 10px 20px;
        background: #f8f9fa;
    }
</style>

<div class="chatbot-toggler" id="chatbotToggler" title="Trợ lý AI">
    <i class="fas fa-robot fa-lg"></i>
</div>

<div class="chatbot-window" id="chatbotWindow">
    <div class="chat-header">
        <span><i class="fas fa-robot me-2"></i> Trợ lý học tập AI</span>
        <div class="chat-controls">
            <i class="fas fa-expand" id="expandChat" title="Phóng to"></i>
            <i class="fas fa-times" id="closeChat" title="Đóng" style="font-size: 1.2rem;"></i>
        </div>
    </div>

    <div class="chat-body" id="chatBody">
        <div class="chat-msg ai">Chào bạn! Mình có thể giúp gì cho bài học của bạn hôm nay?</div>
    </div>
    <div class="typing-indicator" id="typingIndicator">AI đang suy nghĩ...</div>

    <div class="chat-input-area">
        <input type="text" id="chatInput" class="form-control rounded-pill" placeholder="Hỏi AI bất cứ điều gì...">
        <button id="sendBtn" class="btn btn-primary rounded-circle"
            style="width: 45px; height: 45px; padding: 0; display: flex; align-items: center; justify-content: center;">
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
        const expandBtn = document.getElementById('expandChat');
        const sendBtn = document.getElementById('sendBtn');
        const chatInput = document.getElementById('chatInput');
        const chatBody = document.getElementById('chatBody');
        const typingIndicator = document.getElementById('typingIndicator');

        let chatHistory = [];

        // Đóng / Mở Chat
        toggler.addEventListener('click', () => windowEl.classList.toggle('active'));
        closeBtn.addEventListener('click', () => windowEl.classList.remove('active'));

        // Phóng to / Thu nhỏ
        expandBtn.addEventListener('click', () => {
            windowEl.classList.toggle('fullscreen');

            // Đổi icon tương ứng
            if (windowEl.classList.contains('fullscreen')) {
                expandBtn.classList.remove('fa-expand');
                expandBtn.classList.add('fa-compress');
                expandBtn.title = "Thu nhỏ";
            } else {
                expandBtn.classList.remove('fa-compress');
                expandBtn.classList.add('fa-expand');
                expandBtn.title = "Phóng to";
            }
            scrollToBottom();
        });

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

            chatHistory.push({
                role: 'user',
                content: message
            });

            typingIndicator.style.display = 'block';
            scrollToBottom();

            try {
                const response = await axios.post('{{ route('chatbot.send') }}', {
                    messages: chatHistory
                });

                typingIndicator.style.display = 'none';
                const aiReply = response.data.reply;

                appendMessage(aiReply, 'ai');

                chatHistory.push({
                    role: 'assistant',
                    content: aiReply
                });

            } catch (error) {
                console.error(error);
                typingIndicator.style.display = 'none';
                appendMessage('Đã xảy ra lỗi khi gọi AI. Vui lòng thử lại.', 'ai');
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
