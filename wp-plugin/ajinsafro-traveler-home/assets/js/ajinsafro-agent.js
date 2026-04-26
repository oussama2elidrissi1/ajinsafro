(function () {
    'use strict';

    if (typeof window.ajthAgentConfig !== 'object' || window.ajthAgentConfig === null) {
        window.ajthAgentConfig = {};
    }

    function initAgent() {
        var root = document.getElementById('ajth-agent-root');
        if (!root || root.dataset.ajthAgentReady === '1') {
            return !!root;
        }

        root.dataset.ajthAgentReady = '1';

        var config = window.ajthAgentConfig;
        var openButton = root.querySelector('[data-ajth-agent-open]');
        var closeButton = root.querySelector('[data-ajth-agent-close]');
        var panel = root.querySelector('[data-ajth-agent-panel]');
        var messages = root.querySelector('[data-ajth-agent-messages]');
        var typing = root.querySelector('[data-ajth-agent-typing]');
        var form = root.querySelector('[data-ajth-agent-form]');
        var input = form ? form.querySelector('input[name="message"]') : null;
        var quickReplies = root.querySelector('[data-ajth-agent-quick-replies]');
        var labels = config.labels || {};

        function setOpenState(isOpen) {
            if (!panel) {
                return;
            }

            if (isOpen) {
                panel.hidden = false;
                root.classList.add('is-open');
                if (openButton) {
                    openButton.setAttribute('aria-expanded', 'true');
                }
                if (input) {
                    window.setTimeout(function () {
                        input.focus();
                    }, 80);
                }
                return;
            }

            panel.hidden = true;
            root.classList.remove('is-open');
            if (openButton) {
                openButton.setAttribute('aria-expanded', 'false');
            }
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatMessage(message) {
            return escapeHtml(message).replace(/\n/g, '<br>');
        }

        function appendMessage(role, message, actions) {
            var item = document.createElement('div');
            item.className = 'ajth-agent-message ajth-agent-message--' + role;

            var bubble = document.createElement('div');
            bubble.className = 'ajth-agent-message__bubble';
            bubble.innerHTML = formatMessage(message);
            item.appendChild(bubble);

            if (Array.isArray(actions) && actions.length) {
                var actionWrap = document.createElement('div');
                actionWrap.className = 'ajth-agent-message__actions';

                actions.forEach(function (action) {
                    if (!action || !action.url || !action.label) {
                        return;
                    }
                    var link = document.createElement('a');
                    link.className = 'ajth-agent-action';
                    link.href = action.url;
                    link.textContent = action.label;
                    link.target = /^https?:/i.test(action.url) ? '_blank' : '_self';
                    link.rel = 'noopener noreferrer';
                    actionWrap.appendChild(link);
                });

                if (actionWrap.childNodes.length) {
                    item.appendChild(actionWrap);
                }
            }

            messages.appendChild(item);
            messages.scrollTop = messages.scrollHeight;
        }

        function renderQuickReplies(items) {
            if (!quickReplies) {
                return;
            }

            quickReplies.innerHTML = '';
            if (!Array.isArray(items) || !items.length) {
                return;
            }

            items.forEach(function (item) {
                if (!item || !item.label || !item.message) {
                    return;
                }
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'ajth-agent-chip';
                button.textContent = item.label;
                button.addEventListener('click', function () {
                    submitQuestion(item.message);
                });
                quickReplies.appendChild(button);
            });
        }

        function setTyping(isTyping) {
            if (!typing) {
                return;
            }
            typing.hidden = !isTyping;
        }

        function submitQuestion(text) {
            var message = String(text || '').trim();
            if (!message) {
                return;
            }

            appendMessage('user', message);
            renderQuickReplies([]);
            setTyping(true);

            if (input) {
                input.value = '';
            }

            window.fetch(config.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    message: message,
                    nonce: config.nonce
                })
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Request failed');
                    }
                    return response.json();
                })
                .then(function (payload) {
                    setTyping(false);
                    appendMessage('agent', payload.message || 'Je n’ai pas pu répondre pour le moment.', payload.actions || []);
                    renderQuickReplies(payload.quick_replies || config.quickReplies || []);
                })
                .catch(function () {
                    setTyping(false);
                    appendMessage('agent', "Je n’ai pas encore cette information exacte. Vous pouvez contacter l’équipe Ajinsafro pour confirmation.");
                    renderQuickReplies(config.quickReplies || []);
                });
        }

        if (openButton) {
            openButton.setAttribute('aria-expanded', 'false');
            openButton.addEventListener('click', function () {
                setOpenState(true);
            });
        }

        if (closeButton) {
            closeButton.addEventListener('click', function () {
                setOpenState(false);
            });
        }

        root.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-ajth-agent-open]');
            if (trigger) {
                setOpenState(true);
                return;
            }

            var closer = event.target.closest('[data-ajth-agent-close]');
            if (closer) {
                setOpenState(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && root.classList.contains('is-open')) {
                setOpenState(false);
            }
        });

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                submitQuestion(input ? input.value : '');
            });
        }

        appendMessage('agent', config.welcomeMessage || labels.welcomeMessage || 'Bonjour, je suis Ajinsafro Agent.');
        renderQuickReplies(config.quickReplies || []);

        return true;
    }

    function bootAgent() {
        if (initAgent()) {
            return;
        }

        var attempts = 0;
        var maxAttempts = 20;
        var interval = window.setInterval(function () {
            attempts += 1;
            if (initAgent() || attempts >= maxAttempts) {
                window.clearInterval(interval);
            }
        }, 250);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootAgent);
    } else {
        bootAgent();
    }
})();
