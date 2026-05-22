/* ============================================
   🔔 SISTEMA UNIFICADO DE TOASTS
   ============================================ */

const ToastSystem = {
    container: null,

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            this.container.setAttribute('aria-live', 'polite');
            this.container.setAttribute('aria-label', 'Notificaciones');
            document.body.appendChild(this.container);
        }
    },

    show({ type = 'info', title = '', message = '', duration = 4000 } = {}) {
        this.init();

        const icons = {
            success: '<i class="bi bi-check-lg"></i>',
            error: '<i class="bi bi-x-lg"></i>',
            warning: '<i class="bi bi-exclamation-lg"></i>',
            info: '<i class="bi bi-info-lg"></i>'
        };

        const el = document.createElement('div');
        el.className = `toast-notification toast-${type}`;
        el.setAttribute('role', 'alert');
        el.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-body">
                ${title ? `<div class="toast-title">${this._esc(title)}</div>` : ''}
                <div class="toast-message">${this._esc(message)}</div>
            </div>
            <button class="toast-close" aria-label="Cerrar">&times;</button>
            <div class="toast-progress"></div>
        `;

        el.querySelector('.toast-close').addEventListener('click', () => this._remove(el));
        this.container.appendChild(el);

        if (duration > 0) {
            setTimeout(() => this._remove(el), duration);
        }

        return el;
    },

    success(title, message, duration) {
        return this.show({ type: 'success', title, message, duration });
    },
    error(title, message, duration) {
        return this.show({ type: 'error', title, message, duration });
    },
    warning(title, message, duration) {
        return this.show({ type: 'warning', title, message, duration });
    },
    info(title, message, duration) {
        return this.show({ type: 'info', title, message, duration });
    },

    _remove(el) {
        if (el.classList.contains('removing')) return;
        el.classList.add('removing');
        el.addEventListener('animationend', () => el.remove());
    },

    _esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
};

/* ============================================
   ⏳ LOADING SPINNER HELPER
   ============================================ */

const Spinner = {
    show(container, { size = 'lg', text = '' } = {}) {
        const el = document.createElement('div');
        el.className = 'overlay-spinner';
        el.innerHTML = `
            <div style="text-align:center">
                <div class="spinner spinner-${size}"></div>
                ${text ? `<div style="margin-top:0.5rem;font-size:0.875rem;color:var(--text-muted)">${text}</div>` : ''}
            </div>
        `;
        const parent = typeof container === 'string' ? document.querySelector(container) : container;
        if (parent) {
            parent.style.position = 'relative';
            parent.appendChild(el);
        }
        return el;
    },

    hide(spinnerEl) {
        if (spinnerEl) spinnerEl.remove();
    }
};

/* ============================================
   ✅ MODAL DE CONFIRMACIÓN
   ============================================ */

const Confirm = {
    async show({ title = '¿Estás seguro?', message = '', confirmText = 'Eliminar', cancelText = 'Cancelar', variant = 'danger', icon = 'bi-trash3-fill' } = {}) {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'confirm-overlay';

            overlay.innerHTML = `
                <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
                    <div class="confirm-icon ${variant}"><i class="bi ${icon}"></i></div>
                    <div class="confirm-title" id="confirm-title">${this._esc(title)}</div>
                    <div class="confirm-message">${this._esc(message)}</div>
                    <div class="confirm-actions">
                        <button class="btn btn-cancel">${cancelText}</button>
                        <button class="btn btn-${variant}" id="confirm-accept-btn">${confirmText}</button>
                    </div>
                </div>
            `;

            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    this._close(overlay);
                    resolve(false);
                }
            });

            overlay.querySelector('.btn-cancel').addEventListener('click', () => {
                this._close(overlay);
                resolve(false);
            });

            overlay.querySelector(`#confirm-accept-btn`).addEventListener('click', () => {
                this._close(overlay);
                resolve(true);
            });

            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Escape') {
                    this._close(overlay);
                    resolve(false);
                    document.removeEventListener('keydown', handler);
                }
            });

            document.body.appendChild(overlay);
            overlay.querySelector('.btn-danger')?.focus();
        });
    },

    _close(overlay) {
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 0.2s ease';
        setTimeout(() => overlay.remove(), 200);
    },

    _esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
};

/* ============================================
   🔍 BÚSQUEDA EN VIVO
   ============================================ */

const LiveSearch = {
    init(inputSelector, tableSelector, { minChars = 1, caseSensitive = false, highlight = false } = {}) {
        const input = document.querySelector(inputSelector);
        const table = document.querySelector(tableSelector);
        if (!input || !table) return;

        const rows = table.querySelectorAll('tbody tr');
        const clearBtn = input.parentElement.querySelector('.search-clear');

        const filter = () => {
            const term = input.value.trim();
            clearBtn.classList.toggle('visible', term.length > 0);

            rows.forEach(row => {
                const text = row.textContent;
                const match = caseSensitive ? text.includes(term) : text.toLowerCase().includes(term.toLowerCase());
                row.style.display = (term.length < minChars || match) ? '' : 'none';
            });
        };

        input.addEventListener('input', filter);

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                input.value = '';
                filter();
            input.focus();
        });
    }
};

/* ============================================
   🛡️ CONFIRM - EVENT DELEGATION
   ============================================ */

document.addEventListener('click', async (e) => {
    const trigger = e.target.closest('[data-confirm]');
    if (!trigger) return;

    const message = trigger.getAttribute('data-confirm');
    const confirmed = await Confirm.show({ message });

    if (!confirmed) {
        e.preventDefault();
        return;
    }

    if (trigger.tagName === 'A') {
        e.preventDefault();
        window.location.href = trigger.href;
    }
    // Button inside a form — let it submit naturally
});
