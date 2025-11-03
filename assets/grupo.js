// Funciones para la gestión de grupos
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips si se usan
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-ocultar mensajes después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (!alert.classList.contains('alert-permanent')) {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }
    });

    // Validación del formulario antes de enviar
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const nombreInput = this.querySelector('input[name="nombre_grupo"]');
            const turnoSelect = this.querySelector('select[name="turno"]');
            
            if (nombreInput && turnoSelect) {
                const nombre = nombreInput.value.trim();
                const turno = turnoSelect.value;
                
                if (nombre.length === 0) {
                    e.preventDefault();
                    showToast('⚠️ Debe ingresar el nombre del grupo', 'warning');
                    nombreInput.focus();
                    return;
                }
                
                if (nombre.length > 24) {
                    e.preventDefault();
                    showToast('⚠️ El nombre no puede superar 24 caracteres', 'warning');
                    nombreInput.focus();
                    return;
                }
                
                if (!turno) {
                    e.preventDefault();
                    showToast('⚠️ Debe seleccionar un turno', 'warning');
                    turnoSelect.focus();
                    return;
                }
            }
        });
    }

    // Función para mostrar toasts (notificaciones)
    window.showToast = function(message, type = 'info') {
        // Crear elemento toast si no existe
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 300px;
            `;
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `alert alert-${type}`;
        toast.style.cssText = `
            margin-bottom: 10px;
            animation: slideIn 0.3s ease;
        `;
        toast.innerHTML = message;

        toastContainer.appendChild(toast);

        // Auto-remover después de 4 segundos
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };

    // Función para confirmar acciones de activar/desactivar
    window.confirmAction = function(action, grupoNombre) {
        const messages = {
            'desactivar': `¿Desactivar el grupo "${grupoNombre}"?\n\nNo aparecerá en los listados normales.`,
            'activar': `¿Reactivar el grupo "${grupoNombre}"?\n\nVolverá a aparecer en los listados normales.`
        };
        
        return confirm(messages[action] || `¿Confirmar acción para "${grupoNombre}"?`);
    };

    // Función para filtrar la tabla (si se implementa filtro en el cliente)
    window.filterTable = function(searchTerm) {
        const table = document.querySelector('.grupos-table');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm.toLowerCase()) ? '' : 'none';
        });
    };

    // Agregar estilos para animaciones
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
});

// Función para exportar datos de grupos
window.exportGruposData = function(format = 'csv') {
    // Implementar lógica de exportación según el formato
    console.log(`Exportando datos de grupos en formato: ${format}`);
    // Aquí se puede implementar la lógica para exportar a CSV, Excel, etc.
};