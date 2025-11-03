/**
 * Funcionalidades para el formulario de recursos
 * Maneja la lógica de mostrar/ocultar campos y validaciones
 */

class RecursosForm {
    constructor() {
        this.form = document.getElementById('formRecurso');
        this.tipoSelect = document.getElementById('tipo');
        this.salonField = document.getElementById('salonField');
        this.salonSelect = document.getElementById('salon_id');
        
        this.init();
    }
    
    init() {
        // Establecer estado inicial
        this.toggleSalonField();
        
        // Event listeners
        this.bindEvents();
    }
    
    bindEvents() {
        // Cambio en el tipo de recurso
        if (this.tipoSelect) {
            this.tipoSelect.addEventListener('change', () => this.toggleSalonField());
        }
        
        // Envío del formulario
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.validateForm(e));
        }
    }
    
    /**
     * Muestra u oculta el campo de salón según el tipo de recurso
     */
    toggleSalonField() {
        if (!this.tipoSelect || !this.salonField || !this.salonSelect) return;
        
        const tipo = this.tipoSelect.value;
        
        if (tipo === 'Llave' || tipo === 'Control') {
            this.showSalonField();
        } else {
            this.hideSalonField();
        }
    }
    
    showSalonField() {
        this.salonField.style.display = 'block';
        this.salonSelect.required = true;
        
        // Animación suave
        setTimeout(() => {
            this.salonField.style.opacity = '1';
        }, 10);
    }
    
    hideSalonField() {
        this.salonField.style.display = 'none';
        this.salonSelect.required = false;
        this.salonSelect.value = '';
    }
    
    /**
     * Valida el formulario antes del envío
     */
    validateForm(e) {
        const nombre = document.getElementById('nombre')?.value.trim();
        const tipo = this.tipoSelect?.value;
        const salon_id = this.salonSelect?.value;
        
        let isValid = true;
        let errorMessage = '';
        
        // Validar nombre
        if (!nombre) {
            errorMessage = 'Por favor ingresa un nombre para el recurso';
            document.getElementById('nombre')?.focus();
            isValid = false;
        }
        // Validar tipo
        else if (!tipo) {
            errorMessage = 'Por favor selecciona un tipo de recurso';
            this.tipoSelect?.focus();
            isValid = false;
        }
        // Validar salón para Llaves y Controles
        else if ((tipo === 'Llave' || tipo === 'Control') && !salon_id) {
            errorMessage = `Por favor selecciona un salón para el recurso de tipo ${tipo}`;
            this.salonSelect?.focus();
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            this.showAlert(errorMessage, 'danger');
            return false;
        }
        
        return true;
    }
    
    /**
     * Muestra alertas temporales
     */
    showAlert(message, type = 'danger') {
        // Remover alertas existentes
        this.removeExistingAlerts();
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insertar después del título
        const title = document.querySelector('h4');
        if (title && title.parentNode) {
            title.parentNode.insertBefore(alertDiv, title.nextSibling);
        } else {
            this.form.parentNode.insertBefore(alertDiv, this.form);
        }
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    removeExistingAlerts() {
        const existingAlerts = document.querySelectorAll('.alert-dismissible');
        existingAlerts.forEach(alert => alert.remove());
    }
    
    /**
     * Establece valores por defecto (para edición)
     */
    setEditValues(data) {
        // Esta función puede ser extendida para manejar datos específicos de edición
        console.log('Datos de edición cargados:', data);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.recursosForm = new RecursosForm();
});

// Función global para compatibilidad con código existente
function toggleSalonField() {
    if (window.recursosForm) {
        window.recursosForm.toggleSalonField();
    }
}