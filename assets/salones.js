document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.time-slot-item').forEach(item => {
        item.addEventListener('click', (e) => {
            if (e.target.type !== 'checkbox') {
                const cb = item.querySelector('input[type="checkbox"]');
                if (cb) {
                    cb.checked = !cb.checked;
                }
            }
            item.classList.toggle('selected', item.querySelector('input[type="checkbox"]')?.checked);
        });
    });

    document.addEventListener('click', async (e) => {
        const trigger = e.target.closest('[data-confirm]');
        if (!trigger) return;
        e.preventDefault();
        const message = trigger.dataset.confirm || '¿Está seguro?';
        let confirmed;
        if (typeof Confirm !== 'undefined') {
            confirmed = await Confirm.show({ message });
        } else {
            confirmed = confirm(message);
        }
        if (confirmed) {
            if (trigger.tagName === 'A') {
                window.location.href = trigger.href;
            } else if (trigger.tagName === 'BUTTON' && trigger.form) {
                trigger.form.submit();
            }
        }
    });

    document.querySelectorAll('.admin-panel-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const panel = document.querySelector(btn.dataset.target || '.admin-panel');
            if (panel) {
                panel.classList.toggle('expanded');
                if (panel.classList.contains('expanded')) {
                    panel.style.maxHeight = panel.scrollHeight + 'px';
                } else {
                    panel.style.maxHeight = '0';
                }
            }
        });
    });

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            const checkboxes = form.querySelectorAll('input[name="bloques[]"]');
            if (checkboxes.length > 0) {
                const checked = Array.from(checkboxes).some(cb => cb.checked);
                if (!checked) {
                    e.preventDefault();
                    if (typeof ToastSystem !== 'undefined') {
                        ToastSystem.warning('Validación', 'Debe seleccionar al menos un horario.');
                    }
                }
            }
        });
    });
});
