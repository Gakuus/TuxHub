/**
 * Funcionalidades JavaScript para la página de asignación de grupos
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar funcionalidades
    initGroupSelectionCounter();
    initSelectAllToggle();
    initFilterGroups();
    initEstadoCounters();
});

/**
 * Inicializa el contador de grupos seleccionados
 */
function initGroupSelectionCounter() {
    const gruposContainer = document.getElementById('grupos-container');
    if (!gruposContainer) return;
    
    // Crear elemento para mostrar el contador
    const labelElement = document.querySelector('label[for^="grupo_"]').closest('.mb-3').querySelector('.form-label');
    const countSpan = document.createElement('span');
    countSpan.id = 'selected-count';
    countSpan.textContent = '0';
    labelElement.appendChild(countSpan);
    
    // Actualizar contador
    function updateCounter() {
        const selectedCount = document.querySelectorAll('input[name="grupos[]"]:checked').length;
        countSpan.textContent = selectedCount;
        
        // Cambiar color si no hay selección
        if (selectedCount === 0) {
            countSpan.style.backgroundColor = 'var(--danger-color)';
        } else {
            countSpan.style.backgroundColor = 'var(--success-color)';
        }
        
        // Actualizar contadores de estado
        updateEstadoCounters();
    }
    
    // Event listeners para checkboxes
    const checkboxes = document.querySelectorAll('input[name="grupos[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCounter);
    });
    
    // Contador inicial
    updateCounter();
}

/**
 * Inicializa los contadores de estado (activos/inactivos)
 */
function initEstadoCounters() {
    updateEstadoCounters();
}

/**
 * Actualiza los contadores de grupos por estado
 */
function updateEstadoCounters() {
    const grupos = document.querySelectorAll('.grupo-item');
    let activosCount = 0;
    let inactivosCount = 0;
    let activosSeleccionados = 0;
    let inactivosSeleccionados = 0;
    
    grupos.forEach(grupo => {
        const estado = grupo.getAttribute('data-estado');
        const checkbox = grupo.querySelector('input[type="checkbox"]');
        const estaSeleccionado = checkbox.checked;
        
        if (estado === 'activo') {
            activosCount++;
            if (estaSeleccionado) activosSeleccionados++;
        } else {
            inactivosCount++;
            if (estaSeleccionado) inactivosSeleccionados++;
        }
    });
    
    // Actualizar contadores en la UI
    const contadorActivos = document.getElementById('contador-activos');
    const contadorInactivos = document.getElementById('contador-inactivos');
    const contadorTotal = document.getElementById('contador-total');
    
    if (contadorActivos) {
        contadorActivos.textContent = `${activosSeleccionados}/${activosCount} activos`;
        contadorActivos.className = `badge ${activosSeleccionados > 0 ? 'bg-success' : 'bg-secondary'}`;
    }
    
    if (contadorInactivos) {
        contadorInactivos.textContent = `${inactivosSeleccionados}/${inactivosCount} inactivos`;
        contadorInactivos.className = `badge ${inactivosSeleccionados > 0 ? 'bg-warning' : 'bg-secondary'}`;
    }
    
    if (contadorTotal) {
        const totalSeleccionados = activosSeleccionados + inactivosSeleccionados;
        contadorTotal.textContent = `${totalSeleccionados} seleccionados`;
        contadorTotal.className = `badge ${totalSeleccionados > 0 ? 'bg-primary' : 'bg-secondary'}`;
    }
}

/**
 * Inicializa el botón para seleccionar/deseleccionar todos los grupos
 */
function initSelectAllToggle() {
    const gruposContainer = document.getElementById('grupos-container');
    if (!gruposContainer) return;
    
    // Crear botón de selección/deselección
    const labelElement = document.querySelector('label[for^="grupo_"]').closest('.mb-3').querySelector('.form-label');
    const toggleButton = document.createElement('button');
    toggleButton.type = 'button';
    toggleButton.className = 'btn btn-sm btn-outline-secondary ms-2';
    toggleButton.id = 'toggle-all-btn';
    toggleButton.textContent = 'Seleccionar todos';
    labelElement.appendChild(toggleButton);
    
    // Funcionalidad del botón
    toggleButton.addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('input[name="grupos[]"]');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = !allChecked;
        });
        
        // Disparar evento change para actualizar contador
        checkboxes.forEach(checkbox => {
            checkbox.dispatchEvent(new Event('change'));
        });
        
        // Actualizar texto del botón
        toggleButton.textContent = allChecked ? 'Seleccionar todos' : 'Deseleccionar todos';
    });
}

/**
 * Inicializa el filtrado de grupos por búsqueda
 */
function initFilterGroups() {
    const gruposContainer = document.getElementById('grupos-container');
    if (!gruposContainer) return;
    
    // Crear campo de búsqueda
    const searchDiv = document.createElement('div');
    searchDiv.className = 'mb-3';
    searchDiv.innerHTML = `
        <label class="form-label fw-bold">Buscar grupo:</label>
        <input type="text" id="group-search" class="form-control" placeholder="Escribe para filtrar grupos...">
    `;
    
    gruposContainer.parentNode.insertBefore(searchDiv, gruposContainer);
    
    // Funcionalidad de búsqueda
    const searchInput = document.getElementById('group-search');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const groupItems = gruposContainer.querySelectorAll('.grupo-item');
        
        groupItems.forEach(item => {
            const label = item.querySelector('.form-check-label');
            const text = label.textContent.toLowerCase();
            
            if (text.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
        
        // Actualizar contadores después de filtrar
        updateEstadoCounters();
    });
}

/**
 * Muestra notificación temporal
 */
function showNotification(message, type = 'success') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 1050;
        min-width: 300px;
    `;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Agregar al documento
    document.body.appendChild(notification);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

/**
 * Valida el formulario antes de enviar
 */
function validateForm() {
    const profesorSelect = document.querySelector('select[name="profesor_id"]');
    const gruposCheckboxes = document.querySelectorAll('input[name="grupos[]"]:checked');
    
    if (!profesorSelect.value) {
        showNotification('Por favor, selecciona un profesor.', 'danger');
        return false;
    }
    
    if (gruposCheckboxes.length === 0) {
        const confirmacion = confirm('No has seleccionado ningún grupo. ¿Estás seguro de que quieres continuar?');
        if (!confirmacion) {
            return false;
        }
    }
    
    return true;
}

// Agregar validación al formulario
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    }
});

/**
 * Filtra grupos por estado (para uso futuro si se implementa filtrado en el cliente)
 */
function filterByEstado(estado) {
    const grupos = document.querySelectorAll('.grupo-item');
    
    grupos.forEach(grupo => {
        const grupoEstado = grupo.getAttribute('data-estado');
        const mostrar = estado === 'todos' || grupoEstado === estado;
        
        grupo.style.display = mostrar ? 'block' : 'none';
    });
    
    updateEstadoCounters();
}