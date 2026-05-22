document.addEventListener('DOMContentLoaded', () => {
    const salonSelect = document.querySelector('select[name="salon_id"]');
    const nuevoSalonDiv = document.getElementById('nuevoSalonDiv');
    const nuevoSalonInput = document.querySelector('input[name="nuevo_salon"]');
    const form = document.querySelector('form');

    if (salonSelect && nuevoSalonDiv) {
        salonSelect.addEventListener('change', () => {
            if (salonSelect.value === 'nuevo') {
                nuevoSalonDiv.style.display = 'block';
                nuevoSalonInput.required = true;
                setTimeout(() => nuevoSalonInput?.focus(), 100);
            } else {
                nuevoSalonDiv.style.display = 'none';
                nuevoSalonInput.required = false;
                nuevoSalonInput.value = '';
            }
        });
    }

    if (nuevoSalonInput) {
        nuevoSalonInput.addEventListener('input', () => {
            const val = nuevoSalonInput.value.trim();
            if (val.length > 0 && val.length < 2) {
                nuevoSalonInput.setCustomValidity('El nombre debe tener al menos 2 caracteres.');
            } else if (val.length > 50) {
                nuevoSalonInput.setCustomValidity('El nombre no puede exceder 50 caracteres.');
            } else if (!/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_]+$/.test(val)) {
                nuevoSalonInput.setCustomValidity('Solo letras, números, espacios y guiones.');
            } else {
                nuevoSalonInput.setCustomValidity('');
            }
            nuevoSalonInput.reportValidity();
        });
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            const profesor = form.querySelector('select[name="profesor_id"]')?.value;
            const grupo = form.querySelector('select[name="grupo_id"]')?.value;
            const materia = form.querySelector('select[name="materia_id"]')?.value;
            const dia = form.querySelector('select[name="dia_id"]')?.value;
            const bloque = form.querySelector('select[name="bloque_id"]')?.value;

            if (!profesor || !grupo || !materia || !dia || !bloque) {
                if (typeof ToastSystem !== 'undefined') {
                    ToastSystem.warning('Validación', 'Complete todos los campos requeridos.');
                }
                return;
            }

            if (typeof ToastSystem !== 'undefined') {
                ToastSystem.info('Guardando', 'Procesando horario...');
            }
        });
    }
});
