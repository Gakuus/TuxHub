/**
 * Funcionalidades JavaScript para la página de cargar horario
 */

document.addEventListener('DOMContentLoaded', function() {
    inicializarEventos();
    inicializarValidaciones();
});

/**
 * Inicializa los event listeners
 */
function inicializarEventos() {
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
    
    mostrarFeedback(feedback, 'Nombre válido', 'success');
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
        return false;
    }
    
    if (valor > maxBloques) {
        mostrarFeedback(feedback, `No puede exceder ${maxBloques} bloques consecutivos`, 'error');
        input.value = maxBloques;
        return false;
    }
    
    mostrarFeedback(feedback, `Duración válida (máximo ${maxBloques} bloques)`, 'success');
    return true;
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
 * Confirma el envío del formulario
 * @param {Event} event - Evento de submit
 */
function confirmarEnvio(event) {
    const profesor = document.querySelector('select[name="profesor_id"]').value;
    const grupo = document.querySelector('select[name="grupo_id"]').value;
    const materia = document.querySelector('select[name="materia_id"]').value;
    const dia = document.querySelector('select[name="dia_id"]')?.value;
    const bloque = document.querySelector('select[name="bloque_id"]')?.value;
    
    if (!profesor || !grupo || !materia || !dia || !bloque) {
        // La validación HTML5 se encargará de mostrar los mensajes
        return;
    }
    
    const duracion = document.querySelector('input[name="duracion"]')?.value || 1;
    const confirmacion = `¿Está seguro de que desea guardar el horario?\n\nDuración: ${duracion} bloque(s)`;
    
    if (!confirm(confirmacion)) {
        event.preventDefault();
    }
}

/**
 * Inicializa las validaciones del formulario
 */
function inicializarValidaciones() {
    console.log('Sistema de carga de horarios inicializado');
    
    // Podemos agregar más validaciones aquí según sea necesario
}

/**
 * Función auxiliar para mostrar notificaciones (podría integrarse con Toastr o similar)
 * @param {string} mensaje - Mensaje a mostrar
 * @param {string} tipo - Tipo de notificación (success, error, warning, info)
 */
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Esta función puede extenderse para integrar con librerías de notificación
    console.log(`[${tipo.toUpperCase()}] ${mensaje}`);
}

// Hacer funciones disponibles globalmente
window.mostrarCampoSalon = mostrarCampoSalon;