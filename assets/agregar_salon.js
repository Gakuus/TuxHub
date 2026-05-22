document.addEventListener('DOMContentLoaded', () => {
    const capacidadInput = document.querySelector('input[name="capacidad"]');
    if (!capacidadInput) return;

    capacidadInput.addEventListener('input', () => {
        capacidadInput.value = capacidadInput.value.replace(/[^0-9]/g, '');
        let val = parseInt(capacidadInput.value, 10);
        if (isNaN(val) || val < 1) val = 1;
        if (val > 50) val = 50;
        capacidadInput.value = val;
    });

    capacidadInput.addEventListener('paste', (e) => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
        let val = parseInt(text, 10);
        if (isNaN(val) || val < 1) val = 1;
        if (val > 50) val = 50;
        capacidadInput.value = val;
    });

    const form = capacidadInput.closest('form');
    if (form) {
        form.addEventListener('submit', (e) => {
            const val = parseInt(capacidadInput.value, 10);
            if (isNaN(val) || val < 1) {
                e.preventDefault();
                ToastSystem.warning('Validación', 'La capacidad debe ser al menos 1 persona.');
                capacidadInput.focus();
                return;
            }
            if (val > 50) {
                e.preventDefault();
                ToastSystem.warning('Validación', 'La capacidad no puede ser mayor a 50 personas.');
                capacidadInput.focus();
                return;
            }
        });
    }

    const imagenInput = document.querySelector('input[type="file"][name="imagen"]');
    if (imagenInput) {
        imagenInput.addEventListener('change', () => {
            const file = imagenInput.files[0];
            if (!file) return;
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                ToastSystem.warning('Archivo no válido', 'Selecciona solo imágenes (JPG, PNG, GIF, WebP).');
                imagenInput.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                let preview = document.getElementById('salon-image-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.id = 'salon-image-preview';
                    preview.className = 'mt-2';
                    imagenInput.parentNode.appendChild(preview);
                }
                preview.innerHTML = `<img src="${e.target.result}" alt="Vista previa" class="img-fluid rounded shadow-sm" style="max-height:150px">`;
            };
            reader.readAsDataURL(file);
        });
    }
});
