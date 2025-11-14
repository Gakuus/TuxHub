// JavaScript para la gestión de salones - Versión Mejorada
document.addEventListener('DOMContentLoaded', function() {
    inicializarInteracciones();
    inicializarAnimaciones();
    inicializarTemas();
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
                    // Agregar animación de shake
                    field.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => field.style.animation = '', 500);
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                mostrarNotificacion('Por favor completa todos los campos requeridos', 'error');
                return false;
            }
            
            // Mostrar loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Procesando...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
            
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
                
                // Efecto visual al hacer click
                this.style.transform = 'scale(0.95)';
                setTimeout(() => this.style.transform = 'scale(1)', 150);
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
                entry.target.style.transition = 'all 0.6s ease-out';
            }
        });
    }, observerOptions);

    // Aplicar observador a elementos que puedan aparecer después
    const animatedElements = document.querySelectorAll('.salon-card, .header-section, .group-selector');
    animatedElements.forEach(el => {
        observer.observe(el);
    });
}

function inicializarTemas() {
    // Detectar preferencia de tema del sistema
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Podrías agregar aquí lógica para cambiar entre temas claro/oscuro
    if (prefersDark) {
        document.body.classList.add('dark-mode');
    }
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear notificación toast
    const toastContainer = document.getElementById('toast-container') || crearContenedorToast();
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${tipo} alert-dismissible fade show`;
    toast.style.animation = 'slideInRight 0.3s ease-out';
    toast.innerHTML = `
        <i class="bi bi-${obtenerIconoTipo(tipo)} me-2"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
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
                
                // Animación de cambio de estado
                statusBadge.style.animation = 'pulse 0.6s ease-in-out';
                setTimeout(() => statusBadge.style.animation = '', 600);
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

// Agregar estilos CSS para animaciones adicionales
const additionalStyles = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .spinner {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);