window.confirmAction = async function (action, groupName) {
    const actionText = action === 'desactivar' ? 'Desactivar' : 'Activar';
    const message = `¿${actionText} el grupo "${groupName}"?`;
    if (typeof Confirm !== 'undefined') {
        return await Confirm.show({ title: 'Confirmar acción', message, confirmText: actionText });
    }
    return confirm(message);
};

document.addEventListener('DOMContentLoaded', () => {
    const turnoFilter = document.getElementById('filterTurno');
    if (turnoFilter) {
        turnoFilter.addEventListener('change', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('turno', turnoFilter.value);
            window.location.href = url.toString();
        });
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            new bootstrap.Tooltip(el);
        }
    });
});
