/**
 * Funcionalidades JavaScript para la página de cargar horario
 * con gestión de filtros activos/inactivos
 */

document.addEventListener('DOMContentLoaded', function() {
    inicializarEventos();
    inicializarValidaciones();
    aplicarEstilosInactivos();
    actualizarContadores();
});

/**
 * Inicializa los event listeners
 */
function inicializarEventos() {
    // Eventos para los filtros
    const filtroGrupos = document.querySelector('select[name="filtro_grupos"]');
    const filtroMaterias = document.querySelector('select[name="filtro_materias"]');
    
    if (filtroGrupos) {
        filtroGrupos.addEventListener('change', function() {
            mostrarCargando('Actualizando grupos...');
            this.form.submit();
        });
    }
    
    if (filtroMaterias) {
        filtroMaterias.addEventListener('change', function() {
            mostrarCargando('Actualizando materias...');
            this.form.submit();
        });
    }
    
    // El evento onchange del select de salón ya está en el HTML
    // pero podemos agregar más funcionalidades aquí si es necesario
    
    // Validación en tiempo real para el nuevo salón
    const nuevoSalonInput = document.querySelector('input[name="nuevo_salon"]');
    if (nuevoSalonInput) {
        nuevoSalonInput.addEventListener('input', validarNuevoSalon);
    }
    
    // Validación de duración
    const duracionInput = document.querySelector('input[name="duracion"]');
    if (duracionInput) {
        duracionInput.addEventListener('change', validarDuracion);
    }
    
    // Confirmación antes de enviar el formulario
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', confirmarEnvio);
    }
    
    // Eventos para cambios en selects principales
    const profesorSelect = document.querySelector('select[name="profesor_id"]');
    const grupoSelect = document.querySelector('select[name="grupo_id"]');
    
    if (profesorSelect) {
        profesorSelect.addEventListener('change', function() {
            mostrarCargando('Cargando grupos del profesor...');
        });
    }
    
    if (grupoSelect) {
        grupoSelect.addEventListener('change', function() {
            mostrarCargando('Cargando horarios del grupo...');
        });
    }
}

/**
 * Muestra u oculta el campo para nuevo salón
 * @param {HTMLSelectElement} select - Elemento select del salón
 */
function mostrarCampoSalon(select) {
    const div = document.getElementById('nuevoSalonDiv');
    const input = div.querySelector('input');
    
    if (select.value === 'nuevo') {
        div.style.display = 'block';
        input.required = true;
        // Limpiar el select original para evitar conflictos
        select.querySelector('option[value=""]').selected = true;
        // Enfocar el input
        setTimeout(() => input.focus(), 100);
    } else {
        div.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

/**
 * Valida el campo de nuevo salón en tiempo real
 */
function validarNuevoSalon() {
    const input = this;
    const valor = input.value.trim();
    const feedback = document.getElementById('feedback-salon') || crearElementoFeedback(input);
    
    if (valor === '') {
        mostrarFeedback(feedback, 'Por favor ingrese un nombre para el salón', 'error');
        return false;
    }
    
    if (valor.length < 2) {
        mostrarFeedback(feedback, 'El nombre del salón debe tener al menos 2 caracteres', 'error');
        return false;
    }
    
    if (valor.length > 50) {
        mostrarFeedback(feedback, 'El nombre del salón no puede exceder los 50 caracteres', 'error');
        return false;
    }
    
    // Validar caracteres permitidos
    const regex = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_]+$/;
    if (!regex.test(valor)) {
        mostrarFeedback(feedback, 'Solo se permiten letras, números, espacios y guiones', 'error');
        return false;
    }
    
    mostrarFeedback(feedback, '✓ Nombre de salón válido', 'success');
    return true;
}

/**
 * Valida que la duración no exceda el máximo disponible
 */
function validarDuracion() {
    const input = this;
    const maxBloques = parseInt(input.getAttribute('max')) || 4;
    const valor = parseInt(input.value);
    const feedback = document.getElementById('feedback-duracion') || crearElementoFeedback(input);
    
    if (isNaN(valor) || valor < 1) {
        mostrarFeedback(feedback, 'La duración debe ser al menos 1 bloque', 'error');
        input.value = 1;
        return false;
    }
    
    if (valor > maxBloques) {
        mostrarFeedback(feedback, `No puede exceder ${maxBloques} bloques consecutivos`, 'error');
        input.value = maxBloques;
        return false;
    }
    
    mostrarFeedback(feedback, `✓ Duración válida (máximo ${maxBloques} bloques)`, 'success');
    return true;
}

/**
 * Aplica estilos a los elementos inactivos en los selects
 */
function aplicarEstilosInactivos() {
    // Aplicar a grupos
    const grupoOptions = document.querySelectorAll('select[name="grupo_id"] option');
    grupoOptions.forEach(option => {
        if (option.textContent.includes('[INACTIVO]')) {
            option.classList.add('option-inactiva');
        }
    });
    
    // Aplicar a materias
    const materiaOptions = document.querySelectorAll('select[name="materia_id"] option');
    materiaOptions.forEach(option => {
        if (option.textContent.includes('[INACTIVA]')) {
            option.classList.add('option-inactiva', 'option-materia-inactiva');
        }
    });
}

/**
 * Actualiza los contadores de elementos visibles
 */
function actualizarContadores() {
    // Contador de grupos
    const grupoSelect = document.querySelector('select[name="grupo_id"]');
    if (grupoSelect) {
        const totalGrupos = grupoSelect.options.length - 1; // Excluir opción por defecto
        const gruposInactivos = Array.from(grupoSelect.options).filter(opt => 
            opt.textContent.includes('[INACTIVO]')
        ).length;
        
        const contadorGrupos = document.getElementById('contador-grupos') || crearContador('grupos');
        contadorGrupos.textContent = `${totalGrupos - gruposInactivos} activos, ${gruposInactivos} inactivos (total: ${totalGrupos})`;
    }
    
    // Contador de materias
    const materiaSelect = document.querySelector('select[name="materia_id"]');
    if (materiaSelect) {
        const totalMaterias = materiaSelect.options.length - 1; // Excluir opción por defecto
        const materiasInactivas = Array.from(materiaSelect.options).filter(opt => 
            opt.textContent.includes('[INACTIVA]')
        ).length;
        
        const contadorMaterias = document.getElementById('contador-materias') || crearContador('materias');
        contadorMaterias.textContent = `${totalMaterias - materiasInactivas} activas, ${materiasInactivas} inactivas (total: ${totalMaterias})`;
    }
}

/**
 * Crea un elemento contador
 */
function crearContador(tipo) {
    const contador = document.createElement('div');
    contador.id = `contador-${tipo}`;
    contador.className = 'contador-elementos';
    
    const select = document.querySelector(`select[name="${tipo}_id"]`);
    if (select) {
        select.parentNode.appendChild(contador);
    }
    
    return contador;
}

/**
 * Crea un elemento de feedback para mostrar mensajes de validación
 * @param {HTMLInputElement} input - Input al que se asociará el feedback
 * @returns {HTMLDivElement} Elemento de feedback creado
 */
function crearElementoFeedback(input) {
    const feedback = document.createElement('div');
    feedback.id = `feedback-${input.name}`;
    feedback.className = 'form-text';
    input.parentNode.appendChild(feedback);
    return feedback;
}

/**
 * Muestra mensajes de feedback
 * @param {HTMLDivElement} element - Elemento donde mostrar el mensaje
 * @param {string} mensaje - Mensaje a mostrar
 * @param {string} tipo - Tipo de mensaje (success, error, warning)
 */
function mostrarFeedback(element, mensaje, tipo) {
    element.textContent = mensaje;
    element.className = 'form-text';
    
    switch (tipo) {
        case 'success':
            element.classList.add('text-success');
            break;
        case 'error':
            element.classList.add('text-danger');
            break;
        case 'warning':
            element.classList.add('text-warning');
            break;
    }
}

/**
 * Confirma el envío del formulario con validaciones específicas
 * @param {Event} event - Evento de submit
 */
function confirmarEnvio(event) {
    const profesor = document.querySelector('select[name="profesor_id"]').value;
    const grupo = document.querySelector('select[name="grupo_id"]');
    const grupoValor = grupo?.value;
    const grupoTexto = grupo?.options[grupo.selectedIndex]?.text;
    const materia = document.querySelector('select[name="materia_id"]');
    const materiaValor = materia?.value;
    const materiaTexto = materia?.options[materia.selectedIndex]?.text;
    const dia = document.querySelector('select[name="dia_id"]')?.value;
    const bloque = document.querySelector('select[name="bloque_id"]')?.value;
    
    // Validación básica de campos requeridos
    if (!profesor || !grupoValor || !materiaValor || !dia || !bloque) {
        return; // La validación HTML5 se encargará de mostrar los mensajes
    }
    
    const duracion = document.querySelector('input[name="duracion"]')?.value || 1;
    let confirmacion = `¿Está seguro de que desea guardar el horario?\n\nDuración: ${duracion} bloque(s)`;
    
    // Verificar si hay elementos inactivos seleccionados
    const advertencias = [];
    
    if (grupoTexto && grupoTexto.includes('[INACTIVO]')) {
        advertencias.push('• El grupo seleccionado está INACTIVO');
    }
    
    if (materiaTexto && materiaTexto.includes('[INACTIVA]')) {
        advertencias.push('• La materia seleccionada está INACTIVA');
    }
    
    if (advertencias.length > 0) {
        confirmacion += '\n\n⚠️ ADVERTENCIAS:\n' + advertencias.join('\n') + '\n\n¿Desea continuar de todas formas?';
    } else {
        confirmacion += '\n\n¿Confirmar guardado?';
    }
    
    if (!confirm(confirmacion)) {
        event.preventDefault();
        mostrarNotificacion('Operación cancelada por el usuario', 'warning');
    }
}

/**
 * Muestra un indicador de carga
 */
function mostrarCargando(mensaje = 'Cargando...') {
    // Podría implementarse un spinner o indicador visual
    console.log(mensaje);
}

/**
 * Inicializa las validaciones del formulario
 */
function inicializarValidaciones() {
    console.log('Sistema de carga de horarios inicializado');
    console.log('Filtros de activos/inactivos cargados correctamente');
    
    // Validar estado inicial del formulario
    const salonSelect = document.getElementById('salon_id');
    if (salonSelect) {
        mostrarCampoSalon(salonSelect);
    }
}

/**
 * Función auxiliar para mostrar notificaciones
 * @param {string} mensaje - Mensaje a mostrar
 * @param {string} tipo - Tipo de notificación (success, error, warning, info)
 */
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Esta función puede extenderse para integrar con librerías de notificación
    const iconos = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    console.log(`${iconos[tipo] || ''} ${mensaje}`);
    
    // Podría integrarse con Toastr o similar:
    // if (typeof toastr !== 'undefined') {
    //     toastr[tipo](mensaje);
    // }
}

// Hacer funciones disponibles globalmente
window.mostrarCampoSalon = mostrarCampoSalon;
window.validarDuracion = validarDuracion;
window.validarNuevoSalon = validarNuevoSalon;