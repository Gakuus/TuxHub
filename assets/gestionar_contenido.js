document.addEventListener('DOMContentLoaded', () => {
    inicializarContadores();
    inicializarValidaciones();
    inicializarFileInputs();
});

function inicializarContadores() {
    const tituloInput = document.querySelector('input[name="titulo"]');
    const tituloCounter = document.getElementById('titulo-counter');
    if (tituloInput && tituloCounter) {
        const update = () => actualizarEstadoContador(tituloCounter, tituloInput.value.length, 24);
        tituloCounter.textContent = tituloInput.value.length;
        tituloInput.addEventListener('input', update);
        update();
    }

    const contenidoInput = document.querySelector('textarea[name="contenido"]') || document.querySelector('textarea[name="mensaje"]');
    const contenidoCounter = document.getElementById('contenido-counter') || document.getElementById('mensaje-counter');
    if (contenidoInput && contenidoCounter) {
        const update = () => actualizarEstadoContador(contenidoCounter, contenidoInput.value.length, 255);
        contenidoCounter.textContent = contenidoInput.value.length;
        contenidoInput.addEventListener('input', update);
        update();
    }
}

function actualizarEstadoContador(el, len, max) {
    el.classList.remove('text-success', 'text-warning', 'text-danger', 'text-muted');
    if (len === 0) el.classList.add('text-muted');
    else if (len <= max * 0.8) el.classList.add('text-success');
    else if (len <= max * 0.95) el.classList.add('text-warning');
    else el.classList.add('text-danger');
    el.textContent = `${len}/${max}`;
}

function inicializarFileInputs() {
    const fileInput = document.querySelector('input[type="file"][name="imagen_file"]');
    if (!fileInput) return;
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) return;

        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 5 * 1024 * 1024;

        if (!validTypes.includes(file.type)) {
            ToastSystem.warning('Archivo no válido', 'Selecciona solo imágenes (JPG, PNG, GIF, WebP).');
            fileInput.value = '';
            return;
        }
        if (file.size > maxSize) {
            ToastSystem.warning('Archivo demasiado grande', 'La imagen no debe superar los 5MB.');
            fileInput.value = '';
            return;
        }

        mostrarVistaPrevia(file);
        ToastSystem.success('Imagen válida', 'Puedes guardar el formulario.');
    });
}

function mostrarVistaPrevia(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        let container = document.getElementById('image-preview-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'image-preview-container';
            container.className = 'mb-3';
            const label = document.createElement('p');
            label.textContent = 'Vista previa:';
            container.appendChild(label);
            const inputFile = document.querySelector('input[name="imagen_file"]');
            inputFile.parentNode.insertBefore(container, inputFile.nextSibling);
        } else {
            const old = container.querySelector('img');
            if (old) old.remove();
        }
        const img = document.createElement('img');
        img.src = e.target.result;
        img.alt = 'Vista previa';
        img.className = 'img-fluid rounded shadow-sm mt-2';
        img.style.maxHeight = '150px';
        container.appendChild(img);
    };
    reader.readAsDataURL(file);
}

function inicializarValidaciones() {
    const formulario = document.querySelector('form');
    if (!formulario) return;

    formulario.addEventListener('submit', (e) => {
        let valido = true;

        const titulo = document.querySelector('input[name="titulo"]');
        if (titulo) {
            if (!titulo.value.trim()) {
                marcarInvalido(titulo, 'El título es obligatorio');
                valido = false;
            } else if (titulo.value.length > 24) {
                marcarInvalido(titulo, 'El título no puede tener más de 24 caracteres');
                valido = false;
            } else {
                marcarValido(titulo);
            }
        }

        const contenido = document.querySelector('textarea[name="contenido"]') || document.querySelector('textarea[name="mensaje"]');
        if (contenido) {
            if (!contenido.value.trim()) {
                marcarInvalido(contenido, 'Este campo es obligatorio');
                valido = false;
            } else if (contenido.value.length > 255) {
                marcarInvalido(contenido, 'No puede tener más de 255 caracteres');
                valido = false;
            } else {
                marcarValido(contenido);
            }
        }

        if (!valido) {
            e.preventDefault();
            ToastSystem.warning('Corrige los errores', 'Revisa los campos marcados en rojo.');
        }
    });
}

function marcarInvalido(campo, mensaje) {
    campo.classList.add('is-invalid');
    campo.classList.remove('is-valid');
    const old = campo.parentNode.querySelector('.invalid-feedback');
    if (old) old.remove();
    const fb = document.createElement('div');
    fb.className = 'invalid-feedback';
    fb.textContent = mensaje;
    campo.parentNode.appendChild(fb);
}

function marcarValido(campo) {
    campo.classList.add('is-valid');
    campo.classList.remove('is-invalid');
    const old = campo.parentNode.querySelector('.invalid-feedback');
    if (old) old.remove();
}

document.addEventListener('click', async (e) => {
    const dangerLink = e.target.closest('a.btn-danger');
    if (!dangerLink) return;
    e.preventDefault();

    const confirmed = await Confirm.show({
        title: 'Confirmar eliminación',
        message: '¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.',
        confirmText: 'Eliminar',
        variant: 'danger',
        icon: 'bi-trash3-fill'
    });

    if (confirmed) {
        window.location.href = dangerLink.href;
    }
});
