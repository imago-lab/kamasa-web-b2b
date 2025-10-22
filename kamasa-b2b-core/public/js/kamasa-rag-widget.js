(function (window, document) {
    'use strict';

    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }

    const data = window.kamasaWidgetData || {};

    if (!data.apiUrl || !data.nonce) {
        console.warn('kamasaWidgetData no contiene apiUrl o nonce. El widget no se inicializará.');
        return;
    }

    const defaultTexts = {
        assistantName: 'Asesor IA Kamasa',
        bubbleLabel: '¿Ayuda?',
        placeholder: 'Escribe tu pregunta...',
        sendLabel: 'Enviar',
        typing: 'El asesor está escribiendo…',
        error: 'Ocurrió un error. Inténtalo nuevamente.',
        emptyMessage: 'Escribe una pregunta para comenzar.',
        minimizeLabel: 'Minimizar',
        closeLabel: 'Cerrar',
        recommendationsTitle: 'Productos recomendados'
    };

    const texts = Object.assign({}, defaultTexts, data.i18n || {});

    const config = {
        apiUrl: data.apiUrl,
        nonce: data.nonce,
        initialDelay: typeof data.initialDelay === 'number' ? data.initialDelay : 3000,
        scrollThreshold: typeof data.scrollThreshold === 'number' ? data.scrollThreshold : 200,
        texts: texts
    };

    class KamasaChatWidget {
        constructor(settings) {
            this.config = settings;
            this.conversacion = [];
            this.elements = {};
            this.statusMessage = null;
            this.statusTimeout = null;
            this.isDragging = false;
            this.dragOffset = { x: 0, y: 0 };
            this.widgetPosition = { top: null, left: null };
            this.bubbleVisible = false;
        }

        init() {
            this.render();
            this.bindEvents();
            this.setupTriggers();
        }

        render() {
            const bubble = document.createElement('button');
            bubble.id = 'kamasa-chat-bubble';
            bubble.type = 'button';
            bubble.setAttribute('aria-controls', 'kamasa-chat-widget');
            bubble.setAttribute('aria-expanded', 'false');
            bubble.textContent = this.config.texts.bubbleLabel;

            const widget = document.createElement('div');
            widget.id = 'kamasa-chat-widget';
            widget.setAttribute('role', 'dialog');
            widget.setAttribute('aria-modal', 'false');
            widget.setAttribute('aria-live', 'polite');
            widget.innerHTML = `
                <div id="kamasa-chat-header">
                    <h3>${this.config.texts.assistantName}</h3>
                    <div class="kamasa-chat-actions">
                        <button type="button" id="kamasa-chat-minimize" aria-label="${this.config.texts.minimizeLabel}">−</button>
                        <button type="button" id="kamasa-chat-close" aria-label="${this.config.texts.closeLabel}">×</button>
                    </div>
                </div>
                <div id="kamasa-chat-messages" role="log" aria-live="polite"></div>
                <div id="kamasa-chat-input-area">
                    <input type="text" id="kamasa-chat-input" placeholder="${this.config.texts.placeholder}" autocomplete="off" />
                    <button type="button" id="kamasa-chat-send">${this.config.texts.sendLabel}</button>
                </div>
            `;

            document.body.appendChild(bubble);
            document.body.appendChild(widget);

            this.elements = {
                bubble: bubble,
                widget: widget,
                header: widget.querySelector('#kamasa-chat-header'),
                messages: widget.querySelector('#kamasa-chat-messages'),
                input: widget.querySelector('#kamasa-chat-input'),
                send: widget.querySelector('#kamasa-chat-send'),
                minimize: widget.querySelector('#kamasa-chat-minimize'),
                close: widget.querySelector('#kamasa-chat-close')
            };
        }

        bindEvents() {
            this.elements.bubble.addEventListener('click', () => {
                this.showWidget();
            });

            this.elements.close.addEventListener('click', () => {
                this.hideWidget();
            });

            this.elements.minimize.addEventListener('click', () => {
                this.hideWidget();
            });

            this.elements.send.addEventListener('click', () => {
                this.handleSend();
            });

            this.elements.input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    this.handleSend();
                }
            });

            this.elements.header.addEventListener('mousedown', (event) => {
                this.startDrag(event);
            });

            document.addEventListener('mousemove', (event) => {
                this.handleDrag(event);
            });

            document.addEventListener('mouseup', () => {
                this.stopDrag();
            });

            window.addEventListener('resize', () => {
                this.keepWidgetInViewport();
            });
        }

        setupTriggers() {
            if (this.config.initialDelay <= 0) {
                this.showBubble();
            } else {
                window.setTimeout(() => {
                    this.showBubble();
                }, this.config.initialDelay);
            }

            if (this.config.scrollThreshold >= 0) {
                const onScroll = () => {
                    if (window.scrollY > this.config.scrollThreshold) {
                        this.showBubble();
                        window.removeEventListener('scroll', onScroll);
                    }
                };

                window.addEventListener('scroll', onScroll, { passive: true });
            }
        }

        showBubble() {
            if (this.bubbleVisible) {
                return;
            }

            this.bubbleVisible = true;
            this.elements.bubble.style.display = 'flex';
            this.elements.bubble.setAttribute('aria-expanded', 'false');
        }

        hideBubble() {
            this.bubbleVisible = false;
            this.elements.bubble.style.display = 'none';
            this.elements.bubble.setAttribute('aria-expanded', 'true');
        }

        showWidget() {
            this.hideBubble();
            this.elements.widget.classList.add('kamasa-visible');
            this.keepWidgetInViewport();
            this.elements.input.focus({ preventScroll: true });
        }

        hideWidget() {
            this.elements.widget.classList.remove('kamasa-visible');
            this.elements.widget.style.top = '';
            this.elements.widget.style.left = '';
            this.elements.widget.style.bottom = '';
            this.elements.widget.style.right = '';
            this.widgetPosition = { top: null, left: null };
            this.elements.bubble.setAttribute('aria-expanded', 'false');
            this.showBubble();
        }

        handleSend() {
            const pregunta = (this.elements.input.value || '').trim();

            if (!pregunta) {
                this.appendStatus(this.config.texts.emptyMessage, { duration: 2200 });
                return;
            }

            if (this.statusMessage) {
                this.removeStatus();
            }

            this.appendMessage(pregunta, 'user');
            this.conversacion.push({ rol: 'usuario', mensaje: pregunta });
            this.elements.input.value = '';
            this.elements.input.focus({ preventScroll: true });

            this.appendStatus(this.config.texts.typing);
            this.sendPregunta(pregunta);
        }

        appendMessage(text, type = 'bot') {
            const messageEl = document.createElement('div');
            messageEl.className = `kamasa-message kamasa-message--${type}`;
            if (text) {
                messageEl.textContent = text;
            }
            this.elements.messages.appendChild(messageEl);
            this.scrollMessages();
            return messageEl;
        }

        appendStatus(text, options = {}) {
            this.removeStatus();
            this.statusMessage = document.createElement('div');
            this.statusMessage.className = 'kamasa-message kamasa-message--status';
            this.statusMessage.textContent = text;
            this.elements.messages.appendChild(this.statusMessage);
            this.scrollMessages();

            if (this.statusTimeout) {
                window.clearTimeout(this.statusTimeout);
            }

            if (options.duration && typeof options.duration === 'number') {
                this.statusTimeout = window.setTimeout(() => {
                    if (this.statusMessage && this.statusMessage.textContent === text) {
                        this.removeStatus();
                    }
                }, options.duration);
            }
        }

        removeStatus() {
            if (this.statusTimeout) {
                window.clearTimeout(this.statusTimeout);
                this.statusTimeout = null;
            }
            if (this.statusMessage && this.statusMessage.parentNode) {
                this.statusMessage.parentNode.removeChild(this.statusMessage);
            }
            this.statusMessage = null;
        }

        async sendPregunta(pregunta) {
            try {
                const response = await fetch(this.config.apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': this.config.nonce
                    },
                    body: JSON.stringify({
                        pregunta: pregunta,
                        conversacion_previa: this.conversacion
                    })
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                this.removeStatus();

                const respuesta = data && typeof data.respuesta === 'string' ? data.respuesta.trim() : '';
                if (respuesta) {
                    this.appendMessage(respuesta, 'bot');
                    this.conversacion.push({ rol: 'agente', mensaje: respuesta });
                }

                if (data && Array.isArray(data.productos_recomendados) && data.productos_recomendados.length > 0) {
                    const listMessage = this.appendMessage(this.config.texts.recommendationsTitle, 'bot');
                    const list = document.createElement('ul');
                    list.className = 'kamasa-chat-recommendations';
                    data.productos_recomendados.slice(0, 5).forEach((item) => {
                        if (!item) {
                            return;
                        }

                        const listItem = document.createElement('li');
                        listItem.textContent = typeof item === 'string' ? item : String(item);
                        list.appendChild(listItem);
                    });

                    if (list.children.length) {
                        listMessage.appendChild(list);
                    } else {
                        listMessage.parentNode.removeChild(listMessage);
                    }
                }
            } catch (error) {
                console.error('Error al enviar la pregunta:', error);
                this.removeStatus();
                this.appendMessage(this.config.texts.error, 'bot');
            } finally {
                this.scrollMessages();
            }
        }

        scrollMessages() {
            this.elements.messages.scrollTop = this.elements.messages.scrollHeight;
        }

        startDrag(event) {
            this.isDragging = true;
            const rect = this.elements.widget.getBoundingClientRect();
            this.dragOffset.x = event.clientX - rect.left;
            this.dragOffset.y = event.clientY - rect.top;
            this.elements.widget.classList.add('kamasa-dragging');
        }

        handleDrag(event) {
            if (!this.isDragging) {
                return;
            }

            event.preventDefault();

            const newLeft = event.clientX - this.dragOffset.x;
            const newTop = event.clientY - this.dragOffset.y;

            this.widgetPosition = { top: newTop, left: newLeft };

            this.elements.widget.style.top = `${newTop}px`;
            this.elements.widget.style.left = `${newLeft}px`;
            this.elements.widget.style.bottom = 'auto';
            this.elements.widget.style.right = 'auto';
        }

        stopDrag() {
            if (!this.isDragging) {
                return;
            }

            this.isDragging = false;
            this.elements.widget.classList.remove('kamasa-dragging');
            this.keepWidgetInViewport();
        }

        keepWidgetInViewport() {
            if (!this.elements.widget.classList.contains('kamasa-visible')) {
                return;
            }

            const rect = this.elements.widget.getBoundingClientRect();
            const { innerWidth, innerHeight } = window;

            let top = this.widgetPosition.top !== null ? this.widgetPosition.top : rect.top;
            let left = this.widgetPosition.left !== null ? this.widgetPosition.left : rect.left;

            const maxTop = Math.max(20, innerHeight - rect.height - 20);
            const maxLeft = Math.max(20, innerWidth - rect.width - 20);

            if (top < 20) {
                top = 20;
            } else if (top > maxTop) {
                top = maxTop;
            }

            if (left < 20) {
                left = 20;
            } else if (left > maxLeft) {
                left = maxLeft;
            }

            this.elements.widget.style.top = `${top}px`;
            this.elements.widget.style.left = `${left}px`;
            this.elements.widget.style.bottom = 'auto';
            this.elements.widget.style.right = 'auto';

            this.widgetPosition = { top, left };
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        try {
            const widget = new KamasaChatWidget(config);
            widget.init();
        } catch (error) {
            console.error('No se pudo inicializar el widget de Kamasa:', error);
        }
    });
})(window, document);
