(function () {
    'use strict';

    let isOpen = false;
    let isSending = false;

    const container = document.getElementById('chatContainer');
    const messages = document.getElementById('chatMessages');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSendBtn');
    const toggleBtn = document.getElementById('chatToggleBtn');

    function toggle() {
        isOpen = !isOpen;
        container.classList.toggle('open', isOpen);
        toggleBtn.style.display = isOpen ? 'none' : 'flex';
        if (isOpen) {
            input.focus();
            scrollDown();
        }
    }

    function scrollDown() {
        messages.scrollTop = messages.scrollHeight;
    }

    function addMessage(role, text) {
        const div = document.createElement('div');
        div.className = 'chat-msg ' + role;

        if (role === 'assistant') {
            div.innerHTML = renderMarkdown(text);
        } else {
            div.textContent = text;
        }

        messages.appendChild(div);
        scrollDown();
    }

    function renderMarkdown(text) {
        text = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        text = text.replace(/```(\w*)\n([\s\S]*?)```/g, function (_, lang, code) {
            return '<pre><code>' + code.trim() + '</code></pre>';
        });

        text = text.replace(/`([^`]+)`/g, '<code>$1</code>');

        text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

        text = text.replace(/^\* (.+)$/gm, '<li>$1</li>');
        text = text.replace(/(<li>.*<\/li>\n?)+/g, '<ul>$&</ul>');

        text = text.replace(/^(\d+)\. (.+)$/gm, '<li>$2</li>');
        text = text.replace(/(?:^|\n)(<li>.*<\/li>\n?)+/g, function (m) {
            if (!m.includes('<ol>')) {
                return '<ol>' + m + '</ol>';
            }
            return m;
        });

        text = text.replace(/\n{2,}/g, '</p><p>');
        text = text.replace(/\n/g, '<br>');
        text = '<p>' + text + '</p>';

        text = text.replace(/<p><\/p>/g, '');
        text = text.replace(/<p><br><\/p>/g, '');

        return text;
    }

    function showTyping() {
        const div = document.createElement('div');
        div.className = 'chat-typing';
        div.id = 'chatTyping';
        div.innerHTML = '<span></span><span></span><span></span>';
        messages.appendChild(div);
        scrollDown();
    }

    function hideTyping() {
        const el = document.getElementById('chatTyping');
        if (el) el.remove();
    }

    function showError(msg) {
        hideTyping();
        const div = document.createElement('div');
        div.className = 'chat-error';
        div.textContent = msg;
        messages.appendChild(div);
        scrollDown();
        setTimeout(function () {
            if (div.parentNode) div.remove();
        }, 5000);
    }

    function send() {
        const text = input.value.trim();
        if (!text || isSending) return;

        input.value = '';
        addMessage('user', text);
        showTyping();
        isSending = true;
        sendBtn.disabled = true;

        const csrf = document.querySelector('meta[name="csrf-token"]');
        const token = csrf ? csrf.getAttribute('content') : '';

        fetch('backend/chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: text,
                csrf_token: token,
            }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                hideTyping();
                if (data.ok) {
                    addMessage('assistant', data.response);
                } else {
                    showError(data.msg || 'Error de comunicación');
                }
            })
            .catch(function () {
                hideTyping();
                showError('Error de conexión con el servidor');
            })
            .finally(function () {
                isSending = false;
                sendBtn.disabled = false;
                input.focus();
            });
    }

    const closeBtn = document.getElementById('chatCloseBtn');

    toggleBtn.addEventListener('click', toggle);
    if (closeBtn) closeBtn.addEventListener('click', toggle);
    sendBtn.addEventListener('click', send);

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            send();
        }
    });

    document.addEventListener('click', function (e) {
        if (isOpen &&
            !container.contains(e.target) &&
            e.target !== toggleBtn &&
            !toggleBtn.contains(e.target)) {
            toggle();
        }
    });
})();
