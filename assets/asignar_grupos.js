document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('toggleAll');
    const checkboxes = document.querySelectorAll('input[name="grupos[]"]');
    const form = document.querySelector('form');

    if (toggleBtn && checkboxes.length) {
        toggleBtn.addEventListener('click', () => {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => { cb.checked = !allChecked; });
            toggleBtn.textContent = allChecked ? 'Seleccionar todos' : 'Deseleccionar todos';
        });
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            const checked = Array.from(checkboxes).some(cb => cb.checked);
            const profesor = form.querySelector('select[name="profesor_id"]')?.value;
            if (!profesor) {
                e.preventDefault();
                if (typeof ToastSystem !== 'undefined') {
                    ToastSystem.warning('Validación', 'Debe seleccionar un profesor.');
                }
                return;
            }
            if (!checked) {
                if (typeof ToastSystem !== 'undefined') {
                    ToastSystem.warning('Validación', 'Debe seleccionar al menos un grupo.');
                }
                e.preventDefault();
            }
        });
    }
});
