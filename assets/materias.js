document.addEventListener('DOMContentLoaded', () => {
    inicializarGestionMaterias();
});

function inicializarGestionMaterias() {
    inicializarTooltips();
    configurarValidacionFormulario();
    configurarInteraccionesTabla();
    LiveSearch.init('.search-input', '.materias-table');
}

function inicializarTooltips() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    }
}

function configurarValidacionFormulario() {
    const formulario = document.querySelector('.materia-form');
    const inputMateria = document.querySelector('.materia-input');
    const botonSubmit = document.querySelector('.submit-btn');
    if (!formulario) return;

    if (inputMateria) {
        inputMateria.addEventListener('input', () => {
            const contador = document.getElementById('contador-caracteres') || crearContadorCaracteres(inputMateria);
            const len = inputMateria.value.length;
            contador.textContent = `${len}/24 caracteres`;
            contador.style.color = len > 24 ? '#dc3545' : '#6c757d';
            inputMateria.style.borderColor = len > 24 ? '#dc3545' : len > 0 ? '#28a745' : '#e9ecef';
        });

        inputMateria.addEventListener('focus', () => inputMateria.parentElement.classList.add('focused'));
        inputMateria.addEventListener('blur', () => inputMateria.parentElement.classList.remove('focused'));
    }

    formulario.addEventListener('submit', async (e) => {
        const nombre = inputMateria?.value.trim();
        if (!nombre) {
            e.preventDefault();
            ToastSystem.warning('Validación', 'Debe ingresar el nombre de la materia.');
            inputMateria?.focus();
            return;
        }
        if (nombre.length > 24) {
            e.preventDefault();
            ToastSystem.warning('Validación', 'El nombre no puede superar los 24 caracteres.');
            inputMateria?.focus();
            return;
        }
        if (botonSubmit) {
            botonSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...';
            botonSubmit.disabled = true;
        }
    });
}

function crearContadorCaracteres(input) {
    const el = document.createElement('div');
    el.id = 'contador-caracteres';
    el.className = 'contador-caracteres';
    el.style.cssText = 'font-size:0.75rem;margin-top:0.25rem;color:#6c757d';
    input.parentNode.appendChild(el);
    return el;
}

function configurarInteraccionesTabla() {
    document.querySelectorAll('.desactivar-btn, .activar-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const esDesactivar = btn.classList.contains('desactivar-btn');
            const row = btn.closest('.materia-row');
            const nombre = row?.querySelector('.materia-nombre')?.textContent || 'esta materia';
            const accion = esDesactivar ? 'desactivar' : 'reactivar';
            const titulo = esDesactivar ? `Desactivar "${nombre}"` : `Reactivar "${nombre}"`;
            const mensaje = esDesactivar
                ? 'No aparecerá en los listados normales.'
                : 'Volverá a aparecer en los listados normales.';

            const confirmed = await Confirm.show({
                title: titulo,
                message: mensaje,
                confirmText: esDesactivar ? 'Desactivar' : 'Reactivar',
                variant: esDesactivar ? 'danger' : 'success',
                icon: esDesactivar ? 'bi-toggle-off' : 'bi-toggle-on'
            });

            if (confirmed) {
                const form = btn.closest('form');
                if (form) {
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...';
                    btn.disabled = true;
                    form.submit();
                } else {
                    const link = btn.closest('a');
                    if (link) window.location.href = link.href;
                }
            }
        });
    });
}

const MateriasUtils = {
    validarNombre(nombre) {
        if (!nombre || !nombre.trim()) return { valido: false, error: 'El nombre no puede estar vacío' };
        if (nombre.length > 24) return { valido: false, error: 'El nombre no puede superar los 24 caracteres' };
        if (!/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_()&]+$/.test(nombre)) {
            return { valido: false, error: 'El nombre contiene caracteres no permitidos' };
        }
        return { valido: true, error: null };
    },
    formatearNombre(nombre) {
        return nombre.trim().replace(/\s+/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
};

window.MateriasUtils = MateriasUtils;
