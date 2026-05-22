document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchResources');
    const table = document.querySelector('table');
    const rows = table ? table.querySelectorAll('tbody tr') : [];

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const term = searchInput.value.toLowerCase().trim();
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = !term || text.includes(term) ? '' : 'none';
            });
        });
    }

    document.addEventListener('click', async (e) => {
        const markBtn = e.target.closest('.use-btn, .btn-mark-use');
        if (markBtn) {
            e.preventDefault();
            if (markBtn.disabled) return;
            const name = markBtn.dataset.resource || markBtn.closest('tr')?.querySelector('td:nth-child(2)')?.textContent?.trim() || 'recurso';
            let confirmed;
            if (typeof Confirm !== 'undefined') {
                confirmed = await Confirm.show({
                    title: '¿Marcar en uso?',
                    message: `¿Confirmar uso del recurso "${name}"?`,
                    confirmText: 'Sí, marcar',
                    variant: 'warning'
                });
            } else {
                confirmed = confirm(`¿Marcar en uso el recurso "${name}"?`);
            }
            if (confirmed) {
                if (typeof ToastSystem !== 'undefined') {
                    ToastSystem.success('Recurso marcado', `"${name}" marcado como en uso.`);
                }
                if (markBtn.form) markBtn.form.submit();
            }
            return;
        }

        const releaseBtn = e.target.closest('.btn-release');
        if (releaseBtn) {
            e.preventDefault();
            const name = releaseBtn.dataset.resource || releaseBtn.closest('tr')?.querySelector('td:nth-child(2)')?.textContent?.trim() || 'recurso';
            let confirmed;
            if (typeof Confirm !== 'undefined') {
                confirmed = await Confirm.show({
                    title: '¿Liberar recurso?',
                    message: `¿Liberar el recurso "${name}"?`,
                    confirmText: 'Sí, liberar',
                    variant: 'info',
                    icon: 'bi-arrow-return-left'
                });
            } else {
                confirmed = confirm(`¿Liberar el recurso "${name}"?`);
            }
            if (confirmed) {
                if (typeof ToastSystem !== 'undefined') {
                    ToastSystem.success('Recurso liberado', `"${name}" está ahora disponible.`);
                }
                if (releaseBtn.form) releaseBtn.form.submit();
            }
        }
    });

    const filterSelects = document.querySelectorAll('.filter-recurso');
    filterSelects.forEach(select => {
        select.addEventListener('change', () => {
            const url = new URL(window.location.href);
            if (select.value) {
                url.searchParams.set(select.name, select.value);
            } else {
                url.searchParams.delete(select.name);
            }
            window.location.href = url.toString();
        });
    });
});
