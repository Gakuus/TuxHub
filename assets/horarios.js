// JavaScript mejorado para gestión de horarios con efectos visuales
class HorariosManager {
    constructor() {
        this.currentGrupoId = null;
        this.currentTurno = '';
        this.init();
    }

    init() {
        this.initializeEventListeners();
        this.initializeAnimations();
        this.initializeRippleEffects();
        this.initializeRealTimeUpdates();
        this.initializeAccessibility();
        this.showWelcomeNotification();
    }

    // Efectos de bienvenida
    showWelcomeNotification() {
        if (!sessionStorage.getItem('horarios-welcome-shown')) {
            setTimeout(() => {
                this.showNotification('¡Bienvenido al módulo de horarios! Selecciona un grupo para comenzar.', 'info');
                sessionStorage.setItem('horarios-welcome-shown', 'true');
            }, 1000);
        }
    }

    // Inicializar event listeners
    initializeEventListeners() {
        this.enhanceFormSubmissions();
        this.addClassCardInteractions();
        this.addKeyboardNavigation();
        this.initializeExportFunctionality();
    }

    // Mejorar envío de formularios
    enhanceFormSubmissions() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                this.handleFormSubmit(e, form);
            });
        });

        // Efectos en selects
        const selects = document.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('change', (e) => {
                this.createRippleEffect(e);
                this.showLoadingState();
            });

            select.addEventListener('focus', () => {
                select.parentElement.classList.add('focused');
            });

            select.addEventListener('blur', () => {
                select.parentElement.classList.remove('focused');
            });
        });
    }

    // Manejar envío de formularios
    handleFormSubmit(e, form) {
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitButton) {
            this.showLoadingState(submitButton);
        }
    }

    // Mostrar estado de loading
    showLoadingState(element = null) {
        if (element) {
            element.classList.add('loading');
            element.disabled = true;
            
            setTimeout(() => {
                element.classList.remove('loading');
                element.disabled = false;
            }, 2000);
        } else {
            // Loading global
            const mainContent = document.querySelector('.container-fluid');
            mainContent.style.opacity = '0.7';
            mainContent.style.transition = 'opacity 0.3s ease';
            
            setTimeout(() => {
                mainContent.style.opacity = '1';
            }, 1000);
        }
    }

    // Interacciones con tarjetas de clase
    addClassCardInteractions() {
        const classCards = document.querySelectorAll('.class-card');
        
        classCards.forEach(card => {
            card.addEventListener('click', (e) => {
                this.showClassDetails(e.currentTarget);
            });

            card.addEventListener('mouseenter', (e) => {
                this.animateCardHover(e.currentTarget, true);
            });

            card.addEventListener('mouseleave', (e) => {
                this.animateCardHover(e.currentTarget, false);
            });
        });
    }

    // Mostrar detalles de la clase
    showClassDetails(card) {
        const materia = card.querySelector('h6').textContent;
        const profesor = card.querySelector('.class-details small:nth-child(1)').textContent;
        const salon = card.querySelector('.class-details small:nth-child(2)').textContent;
        
        const detailsHtml = `
            <div class="class-details-modal">
                <h5>${materia}</h5>
                <div class="details-list">
                    <p><i class="bi bi-person"></i> <strong>Profesor:</strong> ${profesor}</p>
                    <p><i class="bi bi-geo-alt"></i> <strong>Salón:</strong> ${salon}</p>
                    <p><i class="bi bi-clock"></i> <strong>Horario:</strong> Completo</p>
                </div>
            </div>
        `;

        this.showNotification(detailsHtml, 'info', 5000);
    }

    // Animación hover de tarjetas
    animateCardHover(card, isHovering) {
        if (isHovering) {
            card.style.transform = 'translateY(-5px) scale(1.02)';
            card.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.2)';
        } else {
            card.style.transform = 'translateY(0) scale(1)';
            card.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
        }
    }

    // Inicializar animaciones
    initializeAnimations() {
        // Observer para animaciones al hacer scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                    
                    // Efecto escalonado para filas de horario
                    if (entry.target.classList.contains('schedule-row')) {
                        const delay = Array.from(entry.target.parentNode.children).indexOf(entry.target) * 100;
                        entry.target.style.animationDelay = `${delay}ms`;
                    }
                }
            });
        }, observerOptions);

        // Observar elementos animables
        const animatedElements = document.querySelectorAll('.schedule-row, .selection-panel, .selected-group-indicator');
        animatedElements.forEach(el => {
            observer.observe(el);
        });

        // Efecto de aparición gradual
        this.staggerAnimation();
    }

    // Animación escalonada
    staggerAnimation() {
        const elements = document.querySelectorAll('.animate-fade-in');
        elements.forEach((el, index) => {
            el.style.animationDelay = `${index * 0.1}s`;
        });
    }

    // Efectos ripple
    initializeRippleEffects() {
        const rippleElements = document.querySelectorAll('.btn, .class-card, .form-select');
        
        rippleElements.forEach(element => {
            element.addEventListener('click', (e) => {
                this.createRippleEffect(e);
            });
        });
    }

    // Crear efecto ripple
    createRippleEffect(event) {
        const btn = event.currentTarget;
        const circle = document.createElement('span');
        const diameter = Math.max(btn.clientWidth, btn.clientHeight);
        const radius = diameter / 2;

        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${event.clientX - btn.getBoundingClientRect().left - radius}px`;
        circle.style.top = `${event.clientY - btn.getBoundingClientRect().top - radius}px`;
        circle.classList.add('ripple');

        const ripple = btn.getElementsByClassName('ripple')[0];
        if (ripple) {
            ripple.remove();
        }

        btn.appendChild(circle);

        // Auto-remover después de la animación
        setTimeout(() => {
            circle.remove();
        }, 600);
    }

    // Navegación por teclado
    addKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
            
            // Navegación rápida con teclado
            if (e.ctrlKey) {
                switch(e.key) {
                    case 'e':
                        e.preventDefault();
                        this.exportHorario();
                        break;
                    case 'r':
                        e.preventDefault();
                        this.refreshHorario();
                        break;
                }
            }
        });

        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
    }

    // Funcionalidad de exportación
    initializeExportFunctionality() {
        window.exportHorario = this.exportHorario.bind(this);
    }

    // Exportar horario
    exportHorario() {
        const grupoNombre = document.querySelector('.selected-group-indicator h5')?.textContent || 'Horario';
        
        this.showNotification(`Preparando exportación de ${grupoNombre}...`, 'info');
        
        // Simular proceso de exportación
        setTimeout(() => {
            this.showNotification(`✅ Horario de ${grupoNombre} exportado correctamente`, 'success');
            
            // Efecto visual de exportación
            const table = document.getElementById('horarioTable');
            if (table) {
                table.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    table.style.transform = 'scale(1)';
                }, 300);
            }
        }, 1500);
    }

    // Refrescar horario
    refreshHorario() {
        this.showNotification('🔄 Actualizando horario...', 'info');
        
        // Simular refresh
        setTimeout(() => {
            this.showNotification('✅ Horario actualizado', 'success');
        }, 1000);
    }

    // Actualizaciones en tiempo real
    initializeRealTimeUpdates() {
        // Simular actualizaciones cada 30 segundos
        setInterval(() => {
            this.checkForUpdates();
        }, 30000);
    }

    // Verificar actualizaciones
    checkForUpdates() {
        // En una implementación real, aquí harías una petición al servidor
        console.log('Verificando actualizaciones de horario...');
    }

    // Accesibilidad
    initializeAccessibility() {
        // Agregar labels a íconos
        const icons = document.querySelectorAll('i[class*="bi-"]');
        icons.forEach(icon => {
            if (!icon.getAttribute('aria-label')) {
                const className = Array.from(icon.classList)
                    .find(cls => cls.startsWith('bi-'))
                    ?.replace('bi-', '')
                    .replace(/-/g, ' ');
                
                if (className) {
                    icon.setAttribute('aria-label', className);
                }
            }
        });

        // Mejorar contraste para modo alto contraste
        if (window.matchMedia('(prefers-contrast: high)').matches) {
            document.documentElement.style.setProperty('--primary-color', '#0044cc');
            document.documentElement.style.setProperty('--secondary-color', '#002266');
        }
    }

    // Notificaciones mejoradas
    showNotification(message, type = 'info', duration = 5000) {
        let container = document.getElementById('notifications-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notifications-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }

        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show slide-up`;
        notification.style.cssText = `
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: none;
            border-radius: 16px;
            backdrop-filter: blur(10px);
            margin-bottom: 10px;
        `;
        
        const icon = this.getNotificationIcon(type);
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi ${icon} me-3" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    ${message}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        `;

        container.appendChild(notification);

        // Efecto de entrada
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        // Auto-eliminar después del tiempo especificado
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }
        }, duration);
    }

    getNotificationIcon(type) {
        const icons = {
            'success': 'bi-check-circle-fill text-success',
            'error': 'bi-exclamation-triangle-fill text-danger',
            'warning': 'bi-exclamation-triangle-fill text-warning',
            'info': 'bi-info-circle-fill text-primary'
        };
        return icons[type] || 'bi-info-circle-fill text-primary';
    }

    // Utilidades
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    const horariosManager = new HorariosManager();
    
    // Efecto de carga inicial suave
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.5s ease';
    
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);

    // Hacer disponible globalmente
    window.horariosManager = horariosManager;
});

// Manejo de errores globales
window.addEventListener('error', (e) => {
    console.error('Error en el módulo de horarios:', e.error);
});

// Optimización para dispositivos táctiles
if ('ontouchstart' in window) {
    document.documentElement.classList.add('touch-device');
} else {
    document.documentElement.classList.add('no-touch-device');
}

// Soporte para PWA (Progressive Web App)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // Registrar service worker para caché
    });
}