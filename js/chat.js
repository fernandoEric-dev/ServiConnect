(() => {
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) window.location.reload();
    });
    const chat = document.querySelector('[data-chat-id]');
    if (!chat) return;

    const endpoint = 'backend/controllers/ChatController.php';
    const headers = { Accept: 'application/json' };
    const scroll = chat.querySelector('.chat-scroll');
    const messages = chat.querySelector('.chat-messages');
    const empty = chat.querySelector('.chat-empty');
    const feedback = chat.querySelector('.chat-feedback');
    const peerName = document.getElementById('chatPeer').textContent;
    const id = chat.dataset.chatId;
    const latest = chat.dataset.older === '0';
    let cursor = chat.dataset.cursor;
    let timer;
    let loading = false;
    let refreshAgain = false;
    let stopped = false;

    if (latest) scroll.scrollTop = scroll.scrollHeight;

    function showFeedback(message, error = false) {
        feedback.textContent = message;
        feedback.classList.toggle('chat-error', error);
        feedback.hidden = message === '';
    }

    function addMessage(message) {
        const item = document.createElement('li');
        item.className = message.mine ? 'chat-message chat-mine' : 'chat-message';
        const text = document.createElement('p');
        text.textContent = message.text;
        item.append(text);
        if (message.order) {
            const link = document.createElement('a');
            link.href = 'orcamento.php?id=' + encodeURIComponent(message.order);
            link.textContent = 'Ver proposta';
            item.append(link);
        }
        const meta = document.createElement('div');
        meta.className = 'chat-meta';
        const author = document.createElement('span');
        author.textContent = message.mine ? 'Você' : peerName;
        const time = document.createElement('time');
        time.dateTime = message.datetime;
        time.textContent = message.time;
        meta.append(author, time);
        item.append(meta);
        messages.append(item);
        cursor = message.id;
    }

    function schedule(delay) {
        window.clearTimeout(timer);
        if (id !== '0' && latest && !document.hidden && !stopped) {
            timer = window.setTimeout(refresh, delay);
        }
    }

    async function refresh() {
        if (loading) {
            refreshAgain = true;
            return;
        }
        loading = true;
        let delay = 12000;
        try {
            const query = new URLSearchParams({ id, depois: cursor });
            const response = await fetch(endpoint + '?' + query, { headers, cache: 'no-store' });
            if ([401, 403, 404].includes(response.status)) {
                stopped = true;
                showFeedback('A conversa não está disponível. Atualize a página para continuar.', true);
                return;
            }
            if (!response.ok) throw new Error();
            const result = await response.json();
            const atEnd = scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 64;
            result.messages.forEach(addMessage);
            if (result.messages.length) {
                empty.hidden = true;
                if (atEnd || result.messages.some((message) => message.mine)) scroll.scrollTop = scroll.scrollHeight;
            }
            if (result.more) delay = 0;
        } catch {
            delay = 30000;
        } finally {
            loading = false;
            schedule(refreshAgain ? 0 : delay);
            refreshAgain = false;
        }
    }

    document.addEventListener('visibilitychange', () => schedule(0));
    schedule(12000);

    chat.querySelectorAll('[data-chat-send]').forEach((form) => {
        const button = form.querySelector('button[type=submit]');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (button.disabled) return;
            const data = new FormData(form);
            const proposal = data.get('acao') === 'proposta';
            button.disabled = true;
            showFeedback('Enviando…');
            try {
                const response = await fetch(endpoint, { method: 'POST', headers, body: data });
                const result = await response.json();
                if (!response.ok) {
                    showFeedback(result.message || 'Não foi possível enviar. Tente novamente.', true);
                    return;
                }
                if (id === '0') {
                    window.location.replace('conversas.php?id=' + encodeURIComponent(result.id));
                    return;
                }
                form.reset();
                form.elements.chave_envio.value = result.next_key;
                if (proposal) form.closest('details').open = false;
                showFeedback(proposal ? 'Proposta enviada.' : 'Mensagem enviada.');
                schedule(0);
            } catch {
                showFeedback('Não foi possível confirmar o envio. Tente novamente.', true);
            } finally {
                button.disabled = false;
            }
        });
    });
})();
