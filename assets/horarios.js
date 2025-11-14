// JavaScript optimizado para gestión de horarios con efectos visuales
class HorariosManager {
    constructor() {
        this.currentGrupoId = null;
        this.currentTurno = '';
        this.isInitialized = false;
        this.observer = null;
        this.init();
    }

    init() {
        if (this.isInitialized) return;
        
        this.initializeEventListeners();
        this.initializeAnimations();
        this.initializeRippleEffects();
        this.initializeRealTimeUpdates();
        this.initializeAccessibility();
        this.showWelcomeNotification();
        this.isInitialized = true;
        
        console.log('✅ HorariosManager inicializado correctamente');
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

    // Inicializar event listeners optimizados
    initializeEventListeners() {
        this.enhanceFormSubmissions();
        this.addClassCardInteractions();
        this.addKeyboardNavigation();
        this.initializeExportFunctionality();
        this.addPerformanceOptimizations();
    }

    // Mejorar envío de formularios
    enhanceFormSubmissions() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                this.handleFormSubmit(e, form);
            });
        });

        // Efectos en selects con delegación de eventos
        document.addEventListener('change', (e) => {
            if (e.target.matches('select')) {
                this.createRippleEffect(e);
                this.showLoadingState();
            }
        });

        document.addEventListener('focus', (e) => {
            if (e.target.matches('select')) {
                e.target.parentElement.classList.add('focused');
            }
        }, true);

        document.addEventListener('blur', (e) => {
            if (e.target.matches('select')) {
                e.target.parentElement.classList.remove('focused');
            }
        }, true);
    }

    // Manejar envío de formularios
    handleFormSubmit(e, form) {
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitButton) {
            this.showLoadingState(submitButton);
        }
    }

    // Mostrar estado de loading optimizado
    showLoadingState(element = null) {
        if (element) {
            const originalText = element.innerHTML;
            element.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Procesando...';
            element.disabled = true;
            
            setTimeout(() => {
                element.innerHTML = originalText;
                element.disabled = false;
            }, 1500);
        }
    }

    // Interacciones con tarjetas de clase optimizadas
    addClassCardInteractions() {
        // Usar delegación de eventos para mejor rendimiento
        document.addEventListener('click', (e) => {
            const card = e.target.closest('.class-card');
            if (card) {
                this.showClassDetails(card);
            }
        });

        document.addEventListener('mouseenter', (e) => {
            const card = e.target.closest('.class-card');
            if (card) {
                this.animateCardHover(card, true);
            }
        }, true);

        document.addEventListener('mouseleave', (e) => {
            const card = e.target.closest('.class-card');
            if (card) {
                this.animateCardHover(card, false);
            }
        }, true);
    }

    // Mostrar detalles de la clase mejorado
    showClassDetails(card) {
        const materia = card.dataset.materia || card.querySelector('h6').textContent;
        const profesor = card.dataset.profesor || card.querySelector('.class-details small:nth-child(1)').textContent;
        const salon = card.dataset.salon || card.querySelector('.class-details small:nth-child(2)').textContent;
        
        const detailsHtml = `
            <div class="class-details-modal">
                <h5 class="mb-3">${this.escapeHtml(materia)}</h5>
                <div class="details-list">
                    <p><i class="bi bi-person me-2"></i> <strong>Profesor:</strong> ${this.escapeHtml(profesor)}</p>
                    <p><i class="bi bi-geo-alt me-2"></i> <strong>Salón:</strong> ${this.escapeHtml(salon)}</p>
                    <p><i class="bi bi-clock me-2"></i> <strong>Duración:</strong> 45 minutos</p>
                </div>
            </div>
        `;

        this.showNotification(detailsHtml, 'info', 4000);
    }

    // Animación hover de tarjetas optimizada
    animateCardHover(card, isHovering) {
        if (isHovering) {
            card.style.transform = 'translateY(-3px)';
            card.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
        } else {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = '';
        }
    }

    // Inicializar animaciones con Intersection Observer
    initializeAnimations() {
        const observerOptions = {
            threshold: 0.05,
            rootMargin: '10px'
        };

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-visible');
                    this.observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observar elementos animables
        const animatedElements = document.querySelectorAll('.schedule-row, .selection-panel, .selected-group-indicator');
        animatedElements.forEach(el => {
            this.observer.observe(el);
        });
    }

    // Efectos ripple optimizados
    initializeRippleEffects() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn, .class-card, .form-select')) {
                this.createRippleEffect(e);
            }
        });
    }

    // Crear efecto ripple mejorado
    createRippleEffect(event) {
        const element = event.currentTarget;
        if (!element) return;

        const rect = element.getBoundingClientRect();
        const diameter = Math.max(rect.width, rect.height);
        const radius = diameter / 2;

        const circle = document.createElement('span');
        circle.className = 'ripple-effect';
        circle.style.cssText = `
            width: ${diameter}px;
            height: ${diameter}px;
            left: ${event.clientX - rect.left - radius}px;
            top: ${event.clientY - rect.top - radius}px;
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        `;

        // Limpiar efectos anteriores
        const existingRipples = element.querySelectorAll('.ripple-effect');
        existingRipples.forEach(ripple => ripple.remove());

        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(circle);

        // Auto-remover después de la animación
        setTimeout(() => circle.remove(), 600);
    }

    // Navegación por teclado mejorada
    addKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            // Detectar navegación por teclado
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
            
            // Atajos de teclado
            if (e.ctrlKey || e.metaKey) {
                switch(e.key.toLowerCase()) {
                    case 'e':
                        e.preventDefault();
                        this.exportHorario();
                        break;
                    case 'r':
                        e.preventDefault();
                        this.refreshHorario();
                        break;
                    case 'f':
                        e.preventDefault();
                        document.querySelector('select[name="grupo_id"]')?.focus();
                        break;
                }
            }
        });

        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
    }

    // Optimizaciones de rendimiento
    addPerformanceOptimizations() {
        // Debounce para eventos de resize
        window.addEventListener('resize', this.debounce(() => {
            this.handleResize();
        }, 250));

        // Preload de recursos críticos
        this.preloadCriticalResources();
    }

    handleResize() {
        // Optimizar para diferentes tamaños de pantalla
        if (window.innerWidth < 768) {
            document.body.classList.add('mobile-view');
        } else {
            document.body.classList.remove('mobile-view');
        }
    }

    preloadCriticalResources() {
        // Preload de íconos y recursos importantes
        const criticalResources = [
            '/Agora/Agora/css/horarios.css'
        ];
        
        criticalResources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.href = resource;
            link.as = 'style';
            document.head.appendChild(link);
        });
    }

    // Funcionalidad de exportación mejorada
    initializeExportFunctionality() {
        window.horariosManager = this;
    }

    // Exportar horario a CSV
    async exportHorario() {
        try {
            const grupoNombre = document.querySelector('.selected-group-indicator h5')?.textContent?.replace('Grupo: ', '').trim() || 'Horario';
            
            this.showNotification(`📊 Preparando exportación de ${grupoNombre}...`, 'info');
            
            // Simular proceso de exportación con feedback real
            await this.simulateExportProcess();
            
            this.showNotification(`✅ Horario de ${grupoNombre} exportado correctamente`, 'success');
            
            // Efecto visual de confirmación
            this.animateExportSuccess();
            
        } catch (error) {
            this.showNotification('❌ Error al exportar el horario', 'error');
            console.error('Error en exportación:', error);
        }
    }

    async simulateExportProcess() {
        return new Promise((resolve) => {
            setTimeout(() => {
                // En una implementación real, aquí se generaría el archivo CSV/PDF
                const table = document.getElementById('horarioTable');
                if (table) {
                    this.generateCSV(table);
                }
                resolve();
            }, 1500);
        });
    }

    generateCSV(table) {
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/(\s\s)/gm, ' ').trim();
                row.push(`"${text}"`);
            }
            
            csv.push(row.join(','));
        }
        
        // Descargar archivo
        this.downloadFile(csv.join('\n'), 'horario.csv', 'text/csv');
    }

    downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    animateExportSuccess() {
        const table = document.getElementById('horarioTable');
        if (table) {
            table.style.transition = 'all 0.3s ease';
            table.style.transform = 'scale(0.98)';
            
            setTimeout(() => {
                table.style.transform = 'scale(1)';
            }, 300);
        }
    }

    // Refrescar horario
    refreshHorario() {
        this.showNotification('🔄 Actualizando horario...', 'info');
        
        setTimeout(() => {
            location.reload();
        }, 1000);
    }

    // Actualizaciones en tiempo real optimizadas
    initializeRealTimeUpdates() {
        // Verificar actualizaciones cada 2 minutos
        setInterval(() => {
            this.checkForUpdates();
        }, 120000);
    }

    checkForUpdates() {
        // En una implementación real, aquí harías una petición al servidor
        if (Math.random() > 0.8) { // Simular actualización ocasional
            this.showNotification('🔄 Hay actualizaciones disponibles en el horario', 'info', 3000);
        }
    }

    // Accesibilidad mejorada
    initializeAccessibility() {
        // Mejorar semántica de íconos
        this.enhanceIconsAccessibility();
        
        // Soporte para alto contraste
        this.enhanceContrastSupport();
        
        // Navegación por teclado mejorada
        this.enhanceKeyboardNavigation();
    }

    enhanceIconsAccessibility() {
        const icons = document.querySelectorAll('i[class*="bi-"]:not([aria-label])');
        icons.forEach(icon => {
            const iconName = Array.from(icon.classList)
                .find(cls => cls.startsWith('bi-'))
                ?.replace('bi-', '')
                .replace(/-/g, ' ');
            
            if (iconName) {
                icon.setAttribute('aria-label', iconName);
                icon.setAttribute('role', 'img');
            }
        });
    }

    enhanceContrastSupport() {
        // Detectar preferencia de alto contraste
        if (window.matchMedia('(prefers-contrast: high)').matches) {
            document.documentElement.classList.add('high-contrast');
        }

        // Escuchar cambios en la preferencia
        window.matchMedia('(prefers-contrast: high)').addEventListener('change', (e) => {
            if (e.matches) {
                document.documentElement.classList.add('high-contrast');
            } else {
                document.documentElement.classList.remove('high-contrast');
            }
        });
    }

    enhanceKeyboardNavigation() {
        // Mejorar focus indicators
        document.addEventListener('focusin', (e) => {
            if (e.target.matches('button, select, .class-card')) {
                e.target.classList.add('keyboard-focus');
            }
        });

        document.addEventListener('focusout', (e) => {
            e.target.classList.remove('keyboard-focus');
        });
    }

    // Sistema de notificaciones mejorado
    showNotification(message, type = 'info', duration = 5000) {
        const container = this.getNotificationsContainer();
        const notification = this.createNotificationElement(message, type);
        
        container.appendChild(notification);
        
        // Animación de entrada
        requestAnimationFrame(() => {
            notification.classList.add('notification-show');
        });

        // Auto-eliminar
        const removeNotification = () => {
            notification.classList.remove('notification-show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        };

        if (duration > 0) {
            setTimeout(removeNotification, duration);
        }

        return { element: notification, remove: removeNotification };
    }

    getNotificationsContainer() {
        let container = document.getElementById('notifications-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notifications-container';
            container.className = 'notifications-container';
            document.body.appendChild(container);
        }
        return container;
    }

    createNotificationElement(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'polite');
        
        const icon = this.getNotificationIcon(type);
        notification.innerHTML = `
            <div class="notification-content">
                <i class="bi ${icon} notification-icon"></i>
                <div class="notification-message">${message}</div>
                <button class="notification-close" aria-label="Cerrar notificación">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `;

        // Cerrar al hacer click
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('notification-show');
            setTimeout(() => notification.remove(), 300);
        });

        return notification;
    }

    getNotificationIcon(type) {
        const icons = {
            'success': 'bi-check-circle-fill',
            'error': 'bi-exclamation-triangle-fill',
            'warning': 'bi-exclamation-triangle-fill',
            'info': 'bi-info-circle-fill'
        };
        return icons[type] || 'bi-info-circle-fill';
    }

    // Utilidades
    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    debounce(func, wait) {
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

    // Cleanup para evitar memory leaks
    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
        
        // Limpiar event listeners
        document.body.classList.remove('keyboard-navigation', 'mobile-view');
        this.isInitialized = false;
        
        console.log('🧹 HorariosManager limpiado correctamente');
    }
}

// Inicialización optimizada
document.addEventListener('DOMContentLoaded', () => {
    // Cargar progresivamente
    setTimeout(() => {
        const horariosManager = new HorariosManager();
        
        // Transición suave de carga
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.4s ease';
        
        requestAnimationFrame(() => {
            document.body.style.opacity = '1';
        });

        // Hacer disponible globalmente
        window.horariosManager = horariosManager;
    }, 100);
});

// Manejo de errores global mejorado
window.addEventListener('error', (e) => {
    console.error('Error en el módulo de horarios:', e.error);
    
    // Mostrar error amigable al usuario
    if (window.horariosManager) {
        window.horariosManager.showNotification(
            '⚠️ Ocurrió un error inesperado. Por favor, recarga la página.',
            'error',
            8000
        );
    }
});

// Optimización para dispositivos táctiles
if ('ontouchstart' in window) {
    document.documentElement.classList.add('touch-device');
} else {
    document.documentElement.classList.add('no-touch-device');
}

// Soporte para PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // Aquí podrías registrar un service worker
        console.log('🚀 PWA ready for service worker registration');
    });
}

// Prevención de recarga accidental
window.addEventListener('beforeunload', (e) => {
    if (document.querySelector('form').classList.contains('dirty')) {
        e.preventDefault();
        e.returnValue = '';
    }
});

console.log('📚 Módulo de horarios cargado correctamente');

// Agregar estas funciones a la clase HorariosManager

// Filtrar materias por estado (nueva funcionalidad)
filtrarMateriasPorEstado(estado) {
    const url = new URL(window.location.href);
    url.searchParams.set('estado', estado);
    window.location.href = url.toString();
}

// Mostrar información del filtro actual
mostrarInfoFiltro() {
    const urlParams = new URLSearchParams(window.location.search);
    const filtroEstado = urlParams.get('estado') || 'activas';
    
    let mensaje = '';
    switch(filtroEstado) {
        case 'activas':
            mensaje = 'Mostrando solo materias activas';
            break;
        case 'inactivas':
            mensaje = 'Mostrando solo materias inactivas';
            break;
        case 'todas':
            mensaje = 'Mostrando todas las materias (activas e inactivas)';
            break;
    }
    
    return mensaje;
}

// Resaltar materias inactivas
resaltarMateriasInactivas() {
    const materiasInactivas = document.querySelectorAll('.class-card[data-activa="false"]');
    materiasInactivas.forEach(card => {
        card.style.border = '2px dashed #ffc107';
    });
}

// En el método initializeEventListeners, agregar:
initializeEventListeners() {
    this.enhanceFormSubmissions();
    this.addClassCardInteractions();
    this.addKeyboardNavigation();
    this.initializeExportFunctionality();
    this.addPerformanceOptimizations();
    this.initializeFilterFunctionality(); // Nueva línea
}

// Nuevo método para inicializar funcionalidad de filtros
initializeFilterFunctionality() {
    // Mostrar información del filtro actual al cargar
    setTimeout(() => {
        const infoFiltro = this.mostrarInfoFiltro();
        if (infoFiltro) {
            this.showNotification(infoFiltro, 'info', 3000);
        }
    }, 1500);

    // Resaltar materias inactivas si el filtro es "todas"
    const urlParams = new URLSearchParams(window.location.search);
    const filtroEstado = urlParams.get('estado') || 'activas';
    
    if (filtroEstado === 'todas') {
        this.resaltarMateriasInactivas();
    }

    // Agregar tooltips a las materias inactivas
    this.initializeInactiveMateriasTooltips();
}

// Inicializar tooltips para materias inactivas
initializeInactiveMateriasTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// En el método showClassDetails, agregar información de estado
showClassDetails(card) {
    const materia = card.dataset.materia || card.querySelector('h6').textContent;
    const profesor = card.dataset.profesor || card.querySelector('.class-details small:nth-child(1)').textContent;
    const salon = card.dataset.salon || card.querySelector('.class-details small:nth-child(2)').textContent;
    const esActiva = card.dataset.activa === 'true';
    
    const estadoTexto = esActiva ? 
        '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Materia Activa</span>' :
        '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Materia Inactiva</span>';
    
    const detailsHtml = `
        <div class="class-details-modal">
            <h5 class="mb-3">${this.escapeHtml(materia)}</h5>
            <div class="details-list">
                <p><i class="bi bi-person me-2"></i> <strong>Profesor:</strong> ${this.escapeHtml(profesor)}</p>
                <p><i class="bi bi-geo-alt me-2"></i> <strong>Salón:</strong> ${this.escapeHtml(salon)}</p>
                <p><i class="bi bi-clock me-2"></i> <strong>Duración:</strong> 45 minutos</p>
                <p><i class="bi bi-info-circle me-2"></i> <strong>Estado:</strong> ${estadoTexto}</p>
            </div>
        </div>
    `;

    this.showNotification(detailsHtml, esActiva ? 'info' : 'warning', 5000);
}


// Agregar estas funciones a la clase HorariosManager

// Mostrar información de los filtros actuales
mostrarInfoFiltros() {
    const urlParams = new URLSearchParams(window.location.search);
    const filtroGrupos = urlParams.get('estado_grupos') || 'activos';
    const filtroMaterias = urlParams.get('estado_materias') || 'activas';
    
    let mensaje = '';
    
    switch(filtroGrupos) {
        case 'activos':
            mensaje += 'Mostrando grupos activos';
            break;
        case 'inactivos':
            mensaje += 'Mostrando grupos inactivos';
            break;
        case 'todos':
            mensaje += 'Mostrando todos los grupos';
            break;
    }
    
    switch(filtroMaterias) {
        case 'activas':
            mensaje += ' y materias activas';
            break;
        case 'inactivas':
            mensaje += ' y materias inactivas';
            break;
        case 'todas':
            mensaje += ' y todas las materias';
            break;
    }
    
    return mensaje;
}

// En el método initializeFilterFunctionality, actualizar:
initializeFilterFunctionality() {
    // Mostrar información de los filtros actuales al cargar
    setTimeout(() => {
        const infoFiltros = this.mostrarInfoFiltros();
        if (infoFiltros) {
            this.showNotification(infoFiltros, 'info', 3000);
        }
    }, 1500);

    // Resaltar elementos inactivos según los filtros
    this.resaltarElementosInactivos();
}

// Resaltar elementos inactivos
resaltarElementosInactivos() {
    const urlParams = new URLSearchParams(window.location.search);
    const filtroGrupos = urlParams.get('estado_grupos') || 'activos';
    const filtroMaterias = urlParams.get('estado_materias') || 'activas';
    
    // Resaltar grupo inactivo si está seleccionado
    const grupoIndicator = document.querySelector('.selected-group-indicator');
    if (grupoIndicator && grupoIndicator.classList.contains('grupo-inactivo')) {
        this.mostrarAdvertenciaGrupoInactivo();
    }
    
    // Resaltar materias inactivas si el filtro es "todas"
    if (filtroMaterias === 'todas') {
        this.resaltarMateriasInactivas();
    }
}

// Mostrar advertencia para grupo inactivo
mostrarAdvertenciaGrupoInactivo() {
    const warningHtml = `
        <div class="alert alert-warning alert-dismissible fade show mt-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Grupo Inactivo:</strong> Este grupo está marcado como inactivo. 
            Es posible que algunas funcionalidades estén limitadas.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const grupoIndicator = document.querySelector('.selected-group-indicator');
    if (grupoIndicator) {
        grupoIndicator.insertAdjacentHTML('afterend', warningHtml);
    }
}