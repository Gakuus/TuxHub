// JavaScript mejorado para la gestión de recursos con efectos visuales llamativos
class RecursosManager {
    constructor() {
        this.currentView = 'table'; // 'table' or 'grid'
        this.init();
    }

    init() {
        this.initializeEventListeners();
        this.initializeViewToggle();
        this.initializeFilters();
        this.initializeFormHandlers();
        this.initializeStats();
        this.cleanURLParameters();
        this.initializeAccessibility();
        this.initializeAnimations();
        this.initializePulseEffects();
        this.initializeSearch();
    }

    // Efectos de pulso para elementos importantes
    initializePulseEffects() {
        // Agregar efecto pulse a botones importantes
        const importantButtons = document.querySelectorAll('.btn-primary-custom, .filter-btn.filter-active');
        importantButtons.forEach(btn => {
            btn.classList.add('pulse');
            
            // Remover pulse después de 3 ciclos
            setTimeout(() => {
                btn.classList.remove('pulse');
            }, 6000);
        });

        // Efecto de confeti al cargar (solo una vez)
        this.showWelcomeEffect();
    }

    showWelcomeEffect() {
        // Efecto visual de bienvenida
        const welcomeMessage = document.createElement('div');
        welcomeMessage.className = 'alert alert-info text-center';
        welcomeMessage.innerHTML = `
            <i class="bi bi-stars me-2"></i>
            <strong>¡Bienvenido a la Gestión de Recursos!</strong>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container');
        container.insertBefore(welcomeMessage, container.firstChild);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (welcomeMessage.parentNode) {
                welcomeMessage.remove();
            }
        }, 5000);
    }

    // Inicializar toggle de vista
    initializeViewToggle() {
        const viewToggle = document.getElementById('viewToggle');
        if (!viewToggle) return;

        const tableBtn = viewToggle.querySelector('[data-view="table"]');
        const gridBtn = viewToggle.querySelector('[data-view="grid"]');

        tableBtn?.addEventListener('click', () => this.switchView('table'));
        gridBtn?.addEventListener('click', () => this.switchView('grid'));

        // Establecer vista activa inicial
        this.setActiveView(this.currentView);
    }

    // Efectos mejorados para cambio de vista
    switchView(view) {
        if (this.currentView === view) return;

        // Efecto de transición
        const mainContent = document.querySelector('.recursos-main');
        mainContent.style.opacity = '0.5';
        mainContent.style.transform = 'scale(0.98)';

        setTimeout(() => {
            this.currentView = view;
            this.setActiveView(view);
            this.toggleViewContainers(view);
            
            // Restaurar opacidad
            mainContent.style.opacity = '1';
            mainContent.style.transform = 'scale(1)';
            
            // Efecto de confeti para cambio de vista
            this.showViewSwitchEffect(view);
            
            // Guardar preferencia en localStorage
            localStorage.setItem('recursos-view-preference', view);
        }, 300);
    }

    showViewSwitchEffect(view) {
        const viewName = view === 'table' ? 'Tabla' : 'Grid';
        this.showNotification(`Cambiado a vista ${viewName}`, 'success');
    }

    setActiveView(view) {
        const viewToggle = document.getElementById('viewToggle');
        if (!viewToggle) return;

        const buttons = viewToggle.querySelectorAll('.view-btn');
        buttons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.view === view) {
                btn.classList.add('active');
            }
        });
    }

    toggleViewContainers(view) {
        const tableContainer = document.querySelector('.table-container');
        const gridContainer = document.querySelector('.resource-grid');

        if (view === 'table') {
            tableContainer?.classList.remove('d-none');
            gridContainer?.classList.add('d-none');
        } else {
            tableContainer?.classList.add('d-none');
            gridContainer?.classList.remove('d-none');
        }
    }

    // Inicializar estadísticas
    initializeStats() {
        this.updateStats();
    }

    updateStats() {
        const recursos = document.querySelectorAll('tbody tr, .resource-card');
        const stats = {
            total: recursos.length,
            disponibles: 0,
            ocupados: 0,
            reservados: 0
        };

        recursos.forEach(recurso => {
            const estadoElement = recurso.querySelector('.status-badge');
            if (estadoElement) {
                const estado = estadoElement.textContent.trim().toLowerCase();
                if (estado.includes('disponible')) stats.disponibles++;
                else if (estado.includes('ocupado')) stats.ocupados++;
                else if (estado.includes('reservado')) stats.reservados++;
            }
        });

        this.renderStats(stats);
    }

    renderStats(stats) {
        const statsContainer = document.getElementById('statsContainer');
        if (!statsContainer) return;

        statsContainer.innerHTML = `
            <div class="stat-card fade-in">
                <div class="stat-value">${stats.total}</div>
                <div class="stat-label">Total Recursos</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-value" style="background: var(--gradient-success); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    ${stats.disponibles}
                </div>
                <div class="stat-label">Disponibles</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-value" style="background: var(--gradient-warning); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    ${stats.ocupados}
                </div>
                <div class="stat-label">Ocupados</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-value" style="background: var(--gradient-info); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    ${stats.reservados}
                </div>
                <div class="stat-label">Reservados</div>
            </div>
        `;
    }

    // Inicializar animaciones
    initializeAnimations() {
        // Animación de entrada para las tarjetas
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        // Observar elementos animables
        const animatedElements = document.querySelectorAll('.resource-card, .stat-card, .table tbody tr');
        animatedElements.forEach(el => {
            observer.observe(el);
        });
    }

    // Resto de métodos mejorados...
    initializeEventListeners() {
        this.enhanceUsageForms();
        this.addDeleteConfirmations();
        this.enhanceFilters();
        this.addLoadingStates();
    }

    // Búsqueda con efectos visuales
    initializeSearch() {
        const searchInput = document.getElementById('searchResources');
        if (!searchInput) return;

        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            
            // Efecto de loading en el input
            searchInput.classList.add('loading');
            
            searchTimeout = setTimeout(() => {
                this.filterResources(e.target.value);
                searchInput.classList.remove('loading');
            }, 500);
        });

        // Efecto focus mejorado
        searchInput.addEventListener('focus', () => {
            searchInput.parentElement.style.boxShadow = '0 0 0 3px rgba(67, 97, 238, 0.1), 0 0 20px rgba(67, 97, 238, 0.3)';
        });

        searchInput.addEventListener('blur', () => {
            searchInput.parentElement.style.boxShadow = '';
        });
    }

    // Filtrado con animaciones
    filterResources(query) {
        const searchTerm = query.toLowerCase();
        const recursos = document.querySelectorAll('tbody tr, .resource-card');

        let visibleCount = 0;
        
        recursos.forEach(recurso => {
            const text = recurso.textContent.toLowerCase();
            const shouldShow = text.includes(searchTerm);
            
            if (shouldShow) {
                recurso.style.display = '';
                visibleCount++;
                // Efecto de aparición
                recurso.classList.add('fade-in');
            } else {
                recurso.style.display = 'none';
            }
        });

        this.updateStats();
        this.updateResultsCounter();
        
        // Efecto de resultado de búsqueda
        if (searchTerm) {
            this.showNotification(`Encontrados ${visibleCount} recursos`, 'info');
        }
    }

    // Mejorar formularios de uso
    enhanceUsageForms() {
        const usageForms = document.querySelectorAll('.usage-form');
        
        usageForms.forEach(form => {
            const selectElements = form.querySelectorAll('select');
            const submitButton = form.querySelector('button[type="submit"]');
            
            // Validar formulario antes de enviar
            form.addEventListener('submit', (e) => {
                if (!this.validateUsageForm(form)) {
                    e.preventDefault();
                    this.showNotification('Por favor completa todos los campos requeridos', 'error');
                } else {
                    this.showLoadingState(form);
                }
            });
            
            // Habilitar/deshabilitar botón según selecciones
            selectElements.forEach(select => {
                select.addEventListener('change', () => {
                    this.updateSubmitButtonState(form, submitButton);
                });
            });
            
            // Estado inicial del botón
            this.updateSubmitButtonState(form, submitButton);
        });
    }

    // Mostrar estado de loading
    showLoadingState(form) {
        const buttons = form.querySelectorAll('button');
        buttons.forEach(button => {
            button.classList.add('loading');
            button.disabled = true;
        });
    }

    // Validar formulario de uso
    validateUsageForm(form) {
        const requiredSelects = form.querySelectorAll('select[required]');
        let isValid = true;
        
        requiredSelects.forEach(select => {
            if (!select.value) {
                isValid = false;
                select.classList.add('is-invalid');
            } else {
                select.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    }

    // Actualizar estado del botón de envío
    updateSubmitButtonState(form, button) {
        const requiredSelects = form.querySelectorAll('select[required]');
        const allFilled = Array.from(requiredSelects).every(select => select.value !== '');
        
        if (allFilled && !button.disabled) {
            button.classList.remove('btn-outline-secondary');
            button.title = button.getAttribute('data-original-title') || '';
        } else if (!allFilled) {
            button.classList.add('btn-outline-secondary');
            button.title = 'Completa los campos requeridos';
        }
    }

    // Agregar confirmaciones para eliminar
    addDeleteConfirmations() {
        const deleteLinks = document.querySelectorAll('a[href*="delete="]');
        
        deleteLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const resourceName = link.closest('tr, .resource-card')?.querySelector('.resource-name, td:nth-child(2)')?.textContent;
                const confirmed = confirm(`¿Estás seguro de eliminar el recurso "${resourceName?.trim()}"?`);
                
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        });
    }

    // Mejorar experiencia de filtros
    enhanceFilters() {
        const filterButtons = document.querySelectorAll('.filter-btn');
        
        filterButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Efecto de ripple
                this.createRippleEffect(e);
                
                // Agregar clase activa temporalmente
                btn.classList.add('filter-active');
                setTimeout(() => {
                    if (!btn.href.includes('tipo=') || !btn.classList.contains('filter-active')) {
                        btn.classList.remove('filter-active');
                    }
                }, 1000);
            });
        });

        // Agregar funcionalidad a los botones de limpiar filtros
        const clearFilterButtons = document.querySelectorAll('.clear-filters');
        
        clearFilterButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearFilters();
            });
        });

        this.updateResultsCounter();
    }

    // Efecto ripple para clicks
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
    }

    // Limpiar todos los filtros
    clearFilters() {
        window.location.href = 'dashboard.php?page=recursos';
    }

    // Actualizar contador de resultados
    updateResultsCounter() {
        const visibleResources = document.querySelectorAll('tbody tr:not([style*="display: none"]), .resource-card:not([style*="display: none"])');
        const resultsCount = visibleResources.length;

        // Agregar o actualizar el contador
        let counter = document.getElementById('results-counter');
        if (!counter) {
            counter = document.createElement('div');
            counter.id = 'results-counter';
            counter.className = 'text-muted mb-3';
            document.querySelector('.recursos-main')?.prepend(counter);
        }
        
        if (resultsCount > 0) {
            counter.innerHTML = `<i class="bi bi-info-circle"></i> Mostrando ${resultsCount} recurso(s)`;
            counter.className = 'alert alert-info mb-3';
        } else {
            counter.innerHTML = `<i class="bi bi-exclamation-triangle"></i> No se encontraron recursos`;
            counter.className = 'alert alert-warning mb-3';
        }
    }

    // Inicializar manejadores de formularios
    initializeFormHandlers() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                this.handleFormSubmit(e, form);
            });
        });
    }

    // Manejar envío de formularios
    handleFormSubmit(e, form) {
        // Agregar estado de loading
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.classList.add('loading');
            submitButton.disabled = true;
        }
    }

    // Limpiar parámetros de la URL
    cleanURLParameters() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success') || urlParams.has('error')) {
            const newUrl = window.location.pathname + '?page=recursos';
            window.history.replaceState({}, document.title, newUrl);
        }
    }

    // Inicializar mejoras de accesibilidad
    initializeAccessibility() {
        // Agregar labels a los íconos
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

        // Mejorar navegación por teclado
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
        });

        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
    }

    // Notificaciones más llamativas
    showNotification(message, type = 'info') {
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
        `;
        
        const icon = this.getNotificationIcon(type);
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi ${icon} me-3" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <strong>${message}</strong>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        container.appendChild(notification);

        // Efecto de entrada
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
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

    // Método para exportar datos
    exportData(format = 'csv') {
        this.showNotification(`Exportando datos en formato ${format.toUpperCase()}...`, 'info');
        // Implementación futura para exportar datos
    }

    // Método para buscar recursos
    searchResources(query) {
        this.filterResources(query);
    }
}

// Utilidades mejoradas
const RecursosUtils = {
    // Debounce para optimizar eventos
    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Formatear fecha
    formatDate: (dateString) => {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(dateString).toLocaleDateString('es-ES', options);
    },

    // Capitalizar texto
    capitalize: (text) => {
        return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase();
    },

    // Generar ID único
    generateId: () => {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }
};

// Agregar estilos para efectos ripple
const rippleStyles = `
.ripple {
    position: absolute;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.7);
    transform: scale(0);
    animation: ripple-animation 0.6s linear;
    pointer-events: none;
}

@keyframes ripple-animation {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

.filter-btn, .btn-custom {
    position: relative;
    overflow: hidden;
}

.slide-up {
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(67, 97, 238, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(67, 97, 238, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(67, 97, 238, 0);
    }
}

.loading {
    position: relative;
    color: transparent !important;
}

.loading::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    top: 50%;
    left: 50%;
    margin-left: -10px;
    margin-top: -10px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
`;

// Inyectar estilos ripple
const styleSheet = document.createElement('style');
styleSheet.textContent = rippleStyles;
document.head.appendChild(styleSheet);

// Inicialización mejorada
document.addEventListener('DOMContentLoaded', () => {
    const recursosManager = new RecursosManager();
    
    // Efecto de carga inicial
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.5s ease';
    
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);

    // Hacer disponible globalmente para debugging
    window.recursosManager = recursosManager;
    window.recursosUtils = RecursosUtils;

    // Cargar preferencia de vista
    const savedView = localStorage.getItem('recursos-view-preference');
    if (savedView) {
        recursosManager.switchView(savedView);
    }

    // Agregar clase para estilos de navegación por teclado
    document.body.classList.add('js-loaded');
});

// Manejar errores globales
window.addEventListener('error', (e) => {
    console.error('Error en la gestión de recursos:', e.error);
});

// Optimizar para dispositivos táctiles
if ('ontouchstart' in window) {
    document.documentElement.classList.add('touch-device');
} else {
    document.documentElement.classList.add('no-touch-device');
}