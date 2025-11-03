// JavaScript para la gestión de salones
document.addEventListener('DOMContentLoaded', function() {
    inicializarInteracciones();
    inicializarAnimaciones();
});

function inicializarInteracciones() {
    // Mejorar la experiencia de usuario con los checkboxes de horarios
    const timeSlotItems = document.querySelectorAll('.time-slot-item');
    
    timeSlotItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
            }
            
            if (this.querySelector('input[type="checkbox"]').checked) {
                this.classList.add('selected');
            } else {
                this.classList.remove('selected');
            }
        });
    });

    // Validación de formularios antes de enviar
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Validar checkboxes de horarios si existen
            const checkboxes = this.querySelectorAll('input[name="bloques[]"]');
            if (checkboxes.length > 0) {
                const checked = Array.from(checkboxes).some(cb => cb.checked);
                if (!checked) {
                    e.preventDefault();
                    mostrarNotificacion('Debes seleccionar al menos un horario', 'error');
                    return false;
                }
            }
            
            // Validar campos requeridos
            const requiredFields = this.querySelectorAll('[required]');
            let valid = true;
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                mostrarNotificacion('Por favor completa todos los campos requeridos', 'error');
                return false;
            }
            
            return true;
        });
    });

    // Mejorar la experiencia de los checkboxes de recursos
    const checkboxItems = document.querySelectorAll('.checkbox-item');
    checkboxItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
            }
        });
    });

    // Efectos hover mejorados para las tarjetas
    const salonCards = document.querySelectorAll('.salon-card');
    salonCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
}

function inicializarAnimaciones() {
    // Animación suave al cargar las tarjetas
    const cards = document.querySelectorAll('.salon-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Observador de intersección para animaciones al hacer scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Aplicar observador a elementos que puedan aparecer después
    const animatedElements = document.querySelectorAll('.salon-card, .header-section, .group-selector');
    animatedElements.forEach(el => {
        observer.observe(el);
    });
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear notificación toast
    const toastContainer = document.getElementById('toast-container') || crearContenedorToast();
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${tipo} alert-dismissible fade show`;
    toast.innerHTML = `
        <i class="bi bi-${obtenerIconoTipo(tipo)} me-2"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
}

function crearContenedorToast() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

function obtenerIconoTipo(tipo) {
    const iconos = {
        'success': 'check-circle-fill',
        'error': 'exclamation-triangle-fill',
        'warning': 'exclamation-triangle-fill',
        'info': 'info-circle-fill'
    };
    return iconos[tipo] || 'info-circle-fill';
}

// Funciones utilitarias para el manejo de salones
const SalonManager = {
    // Marcar salón en uso
    marcarEnUso: function(salonId, grupoId, bloques) {
        // Validación adicional antes de enviar
        if (!bloques || bloques.length === 0) {
            mostrarNotificacion('Selecciona al menos un horario', 'error');
            return false;
        }
        
        mostrarNotificacion('Marcando salón en uso...', 'info');
        return true;
    },
    
    // Liberar salón
    liberarSalon: function(salonId) {
        mostrarNotificacion('Liberando salón...', 'info');
        return true;
    },
    
    // Actualizar estado visual del salón
    actualizarEstadoSalon: function(salonId, nuevoEstado) {
        const salonCard = document.querySelector(`[data-salon-id="${salonId}"]`);
        if (salonCard) {
            const statusBadge = salonCard.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = `status-badge ${nuevoEstado === 'disponible' ? 'status-available' : 'status-occupied'}`;
                statusBadge.innerHTML = `
                    <i class="bi bi-${nuevoEstado === 'disponible' ? 'check-circle' : 'x-circle'} me-1"></i>
                    ${nuevoEstado.charAt(0).toUpperCase() + nuevoEstado.slice(1)}
                `;
            }
        }
    }
};

// Exportar funciones para uso global
window.SalonManager = SalonManager;
window.mostrarNotificacion = mostrarNotificacion;

// Manejar errores globales
window.addEventListener('error', function(e) {
    console.error('Error global:', e.error);
    mostrarNotificacion('Ha ocurrido un error inesperado', 'error');
});