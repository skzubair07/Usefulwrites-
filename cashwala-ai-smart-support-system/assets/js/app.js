(function () {
  'use strict';

  if (typeof cwAiSupport === 'undefined') return;

  var root = document.getElementById('cw-ai-support-root');
  var storageKey = 'cw_ai_chat_history_v1';
  var countKey = 'cw_ai_chat_count_v1';

  function byId(id) {
    return document.getElementById(id);
  }

  function post(action, data) {
    var formData = new FormData();
    formData.append('action', action);
    formData.append('nonce', cwAiSupport.nonce);
    Object.keys(data || {}).forEach(function (key) {
      formData.append(key, data[key]);
    });

    return fetch(cwAiSupport.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    }).then(function (res) { return res.json(); });
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function linkify(text) {
    return escapeHtml(text).replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
  }

  function getHistory() {
    try {
      var raw = localStorage.getItem(storageKey);
      return raw ? JSON.parse(raw) : [];
    } catch (e) {
      return [];
    }
  }

  function saveHistory(history) {
    localStorage.setItem(storageKey, JSON.stringify(history.slice(-50)));
  }

  function getMessageCount() {
    var c = parseInt(localStorage.getItem(countKey) || '0', 10);
    return isNaN(c) ? 0 : c;
  }

  function incrementMessageCount() {
    var c = getMessageCount() + 1;
    localStorage.setItem(countKey, String(c));
    return c;
  }

  function addMessage(role, text) {
    var box = byId('cw-ai-chat-messages');
    if (!box) return;

    var row = document.createElement('div');
    row.className = 'cw-ai-msg cw-ai-msg-' + role;
    row.innerHTML = '<div class="cw-ai-bubble">' + linkify(text).replace(/\n/g, '<br>') + '</div>';
    box.appendChild(row);
    box.scrollTop = box.scrollHeight;

    var history = getHistory();
    history.push({ role: role, text: text, time: Date.now() });
    saveHistory(history);
  }

  function renderHistory() {
    var box = byId('cw-ai-chat-messages');
    if (!box) return;
    box.innerHTML = '';

    var history = getHistory();
    if (!history.length) {
      addMessage('bot', 'Hi! I can help you instantly. Ask your question.');
      return;
    }

    history.forEach(function (item) {
      var row = document.createElement('div');
      row.className = 'cw-ai-msg cw-ai-msg-' + item.role;
      row.innerHTML = '<div class="cw-ai-bubble">' + linkify(item.text).replace(/\n/g, '<br>') + '</div>';
      box.appendChild(row);
    });
    box.scrollTop = box.scrollHeight;
  }

  function setupChat() {
    var toggle = byId('cw-ai-chat-toggle');
    var popup = byId('cw-ai-chat-popup');
    var closeBtn = byId('cw-ai-chat-close');
    var sendBtn = byId('cw-ai-chat-send');
    var input = byId('cw-ai-chat-input');
    var openChatBtn = byId('cw-ai-open-chat-btn');

    if (openChatBtn && toggle) {
      openChatBtn.addEventListener('click', function () {
        toggle.click();
      });
    }

    if (!toggle || !popup || !sendBtn || !input) return;

    renderHistory();

    function setOpen(open) {
      popup.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      popup.setAttribute('aria-hidden', open ? 'false' : 'true');
      if (open) input.focus();
    }

    toggle.addEventListener('click', function () {
      var open = !popup.classList.contains('is-open');
      setOpen(open);
    });

    if (closeBtn) {
      closeBtn.addEventListener('click', function () { setOpen(false); });
    }

    function send() {
      if (getMessageCount() >= cwAiSupport.maxMessages) {
        addMessage('bot', cwAiSupport.strings.limitReached);
        return;
      }

      var text = input.value.trim();
      if (!text) {
        addMessage('bot', cwAiSupport.strings.emptyMessage);
        return;
      }
      if (text.length > cwAiSupport.messageMaxChars) {
        addMessage('bot', cwAiSupport.strings.tooLongMessage);
        return;
      }

      addMessage('user', text);
      input.value = '';
      var typingText = cwAiSupport.strings.typing;
      addMessage('bot', typingText);

      post('cw_ai_support_chat', { message: text })
        .then(function (data) {
          var box = byId('cw-ai-chat-messages');
          if (box && box.lastChild) {
            box.removeChild(box.lastChild);
          }

          if (data && data.success && data.data && data.data.answer) {
            addMessage('bot', data.data.answer);
            incrementMessageCount();
            if (data.data.source === 'fallback') {
              post('cw_ai_support_save_unanswered', { question: text });
            }
          } else {
            var fallbackMsg = 'This needs a quick check 👍\nYou can contact us here for instant help.';
            addMessage('bot', fallbackMsg);
            post('cw_ai_support_save_unanswered', { question: text });
          }
        })
        .catch(function () {
          var box = byId('cw-ai-chat-messages');
          if (box && box.lastChild) {
            box.removeChild(box.lastChild);
          }
          addMessage('bot', 'This needs a quick check 👍\nYou can contact us here for instant help.');
          post('cw_ai_support_save_unanswered', { question: text });
        });
    }

    sendBtn.addEventListener('click', send);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
      }
    });
  }

  function setupSearch() {
    var input = byId('cw-ai-search-input');
    var results = byId('cw-ai-search-results');
    var fallbackBtn = byId('cw-ai-open-chat-btn');
    if (!input || !results) return;

    var timer;
    input.addEventListener('input', function () {
      clearTimeout(timer);
      var q = input.value.trim();
      if (q.length < 2) {
        results.innerHTML = '';
        if (fallbackBtn) fallbackBtn.style.display = 'none';
        return;
      }

      timer = setTimeout(function () {
        post('cw_ai_support_search', { query: q }).then(function (data) {
          if (!data || !data.success || !data.data) {
            results.innerHTML = '';
            if (fallbackBtn) fallbackBtn.style.display = 'inline-flex';
            return;
          }

          var items = data.data.results || [];
          if (!items.length) {
            results.innerHTML = '<div class="cw-ai-no-results">No direct match found.</div>';
            if (fallbackBtn) fallbackBtn.style.display = 'inline-flex';
            return;
          }

          var html = items.map(function (item) {
            return '<div class="cw-ai-result-item"><strong>' + escapeHtml(item.question) + '</strong><p>' + escapeHtml(item.answer) + '</p></div>';
          }).join('');

          results.innerHTML = html;
          if (fallbackBtn) fallbackBtn.style.display = 'none';
        });
      }, 250);
    });
  }

  if (root) {
    if (cwAiSupport.enableSearch) setupSearch();
    if (cwAiSupport.enableChatbot) setupChat();
  }
})();
