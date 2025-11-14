// JavaScript optimizado para gestión de recursos - Diseño institucional
class RecursosManager {
    constructor() {
        this.searchTimeout = null;
        this.init();
    }

    init() {
        this.initializeSearch();
        this.initializeFilters();
        this.enhanceUsageForms();
        this.addDeleteConfirmations();
        this.updateResultsCounter();
        this.initializeAnimations();
        this.cleanURLParameters();
        this.initializeExport();
    }

    // Búsqueda optimizada
    initializeSearch() {
        const searchInput = document.getElementById('searchResources');
        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.filterResources(e.target.value);
            }, 300);
        });
    }

    filterResources(query) {
        const searchTerm = query.toLowerCase().trim();
        const recursos = document.querySelectorAll('tbody tr');
        let visibleCount = 0;

        recursos.forEach(recurso => {
            const text = recurso.textContent.toLowerCase();
            const shouldShow = !searchTerm || text.includes(searchTerm);
            
            recurso.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCount++;
        });

        this.updateResultsCounter(visibleCount);
    }

    // Filtros mejorados
    initializeFilters() {
        document.querySelectorAll('.clear-filters').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = 'dashboard.php?page=recursos';
            });
        });
    }

    // Formularios de uso mejorados - VERSIÓN CORREGIDA
    enhanceUsageForms() {
        console.log('Mejorando formularios de uso...');
        
        document.querySelectorAll('.usage-form').forEach((form, index) => {
            console.log(`Procesando formulario ${index + 1}`, form);
            
            const tipo = form.dataset.tipo;
            const grupoSelect = form.querySelector('select[name="grupo_id"]');
            const salonSelect = form.querySelector('select[name="salon_id"]');
            const useButtons = form.querySelectorAll('.use-btn');
            
            console.log(`Formulario ${index + 1}: tipo=${tipo}, grupoSelect=${!!grupoSelect}, salonSelect=${!!salonSelect}, useButtons=${useButtons.length}`);
            
            // Agregar event listeners a todos los selects
            if (grupoSelect) {
                grupoSelect.addEventListener('change', () => {
                    console.log('Cambio en grupoSelect');
                    this.validateFormButtons(form, tipo);
                });
            }
            
            if (salonSelect) {
                salonSelect.addEventListener('change', () => {
                    console.log('Cambio en salonSelect');
                    this.validateFormButtons(form, tipo);
                });
            }
            
            // Validación inicial inmediata
            setTimeout(() => {
                console.log(`Validación inicial formulario ${index + 1}`);
                this.validateFormButtons(form, tipo);
            }, 50);
        });
    }

    // Validación corregida de botones
    validateFormButtons(form, tipo) {
        console.log('Validando botones para tipo:', tipo);
        
        const useButtons = form.querySelectorAll('.use-btn');
        const grupoSelect = form.querySelector('select[name="grupo_id"]');
        const salonSelect = form.querySelector('select[name="salon_id"]');
        const salonFixed = form.querySelector('.salon-fixed');
        
        console.log('Elementos encontrados:', {
            useButtons: useButtons.length,
            grupoSelect: !!grupoSelect,
            salonSelect: !!salonSelect,
            salonFixed: !!salonFixed
        });

        let isValid = true;
        
        // Validar grupo (siempre requerido)
        if (grupoSelect && !grupoSelect.value) {
            console.log('Grupo no seleccionado');
            isValid = false;
        }
        
        // Validar salón (requerido excepto para alargues cuando no hay salón fijo)
        if (tipo !== 'Alargue') {
            // Para llaves y controles, se requiere salón
            if (salonSelect && !salonSelect.value && !salonFixed) {
                console.log('Salón requerido para tipo:', tipo);
                isValid = false;
            }
        } else {
            // Para alargues, el salón es opcional
            console.log('Alargue - salón opcional');
        }
        
        console.log('Validación resultado:', isValid);
        
        // Aplicar a todos los botones de uso en este formulario
        useButtons.forEach((button, index) => {
            if (button) {
                const wasDisabled = button.disabled;
                button.disabled = !isValid;
                console.log(`Botón ${index + 1}: ${wasDisabled ? 'deshabilitado' : 'habilitado'} -> ${button.disabled ? 'deshabilitado' : 'habilitado'}`);
                
                // Agregar estilo visual para indicar estado
                if (button.disabled) {
                    button.classList.add('btn-disabled');
                    button.classList.remove('btn-warning-custom');
                    button.classList.add('btn-outline-secondary');
                } else {
                    button.classList.remove('btn-disabled');
                    button.classList.remove('btn-outline-secondary');
                    button.classList.add('btn-warning-custom');
                }
            }
        });
        
        return isValid;
    }

    // Confirmaciones de eliminación
    addDeleteConfirmations() {
        document.querySelectorAll('a[href*="delete="]').forEach(link => {
            link.addEventListener('click', (e) => {
                const resourceName = this.getResourceName(link);
                const confirmed = confirm(`¿Está seguro de eliminar el recurso "${resourceName}"?\nEsta acción no se puede deshacer.`);
                
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        });
    }

    getResourceName(element) {
        const row = element.closest('tr');
        return row?.querySelector('td:nth-child(2)')?.textContent?.trim() || 'recurso';
    }

    // Contador de resultados
    updateResultsCounter(count = null) {
        if (count === null) {
            count = document.querySelectorAll('tbody tr:not([style*="none"])').length;
        }

        const total = document.querySelectorAll('tbody tr').length;
        const counter = document.getElementById('results-counter');
        
        if (counter) {
            if (count === 0) {
                counter.className = 'alert alert-warning mb-3';
                counter.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>No se encontraron recursos que coincidan con la búsqueda';
            } else if (count < total) {
                counter.className = 'alert alert-info mb-3';
                counter.innerHTML = `<i class="bi bi-funnel me-2"></i>Mostrando ${count} de ${total} recursos`;
            } else {
                counter.className = 'alert alert-light mb-3';
                counter.innerHTML = `<i class="bi bi-info-circle me-2"></i>Total: ${count} recursos`;
            }
        }
    }

    // Animaciones
    initializeAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.stat-card, .table tbody tr').forEach(el => {
            observer.observe(el);
        });
    }

    // Exportar datos
    initializeExport() {
        const exportBtn = document.querySelector('[onclick*="exportData"]');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                this.exportData('csv');
            });
        }
    }

    exportData(format = 'csv') {
        this.showNotification(`Función de exportación en desarrollo (${format})`, 'info');
        // Implementación futura
    }

    showNotification(message, type = 'info') {
        // Puedes implementar notificaciones toast aquí si lo deseas
        console.log(`${type.toUpperCase()}: ${message}`);
    }

    cleanURLParameters() {
        const url = new URL(window.location);
        if (url.searchParams.has('success') || url.searchParams.has('error')) {
            setTimeout(() => {
                url.searchParams.delete('success');
                url.searchParams.delete('error');
                window.history.replaceState({}, '', url);
            }, 3000);
        }
    }
}

// Inicialización mejorada - VERSIÓN CORREGIDA
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== INICIALIZANDO RECURSOS MANAGER ===');
    
    try {
        window.recursosManager = new RecursosManager();
        console.log('✅ RecursosManager inicializado correctamente');
        
        // Validación inicial forzada después de un breve delay
        setTimeout(function() {
            console.log('🔄 Ejecutando validación inicial de todos los formularios...');
            const forms = document.querySelectorAll('.usage-form');
            console.log(`Encontrados ${forms.length} formularios para validar`);
            
            forms.forEach((form, index) => {
                const tipo = form.dataset.tipo;
                console.log(`📋 Validando formulario ${index + 1} (tipo: ${tipo})`);
                window.recursosManager.validateFormButtons(form, tipo);
            });
        }, 200);
        
    } catch (error) {
        console.error('❌ Error inicializando RecursosManager:', error);
    }
    
    // Event listeners directos como respaldo
    document.addEventListener('change', function(e) {
        if (e.target.matches('select[name="grupo_id"], select[name="salon_id"]')) {
            const form = e.target.closest('.usage-form');
            if (form) {
                const tipo = form.dataset.tipo;
                console.log('🔄 Cambio detectado en select, validando formulario...');
                setTimeout(() => {
                    window.recursosManager?.validateFormButtons(form, tipo);
                }, 10);
            }
        }
    });
});

// Manejo de errores global
window.addEventListener('error', (e) => {
    console.error('🔥 Error en la aplicación:', e.error);
});