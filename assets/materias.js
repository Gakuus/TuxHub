/**
 * Funcionalidades JavaScript para la gestión de materias
 */

document.addEventListener('DOMContentLoaded', function() {
    inicializarGestionMaterias();
});

function inicializarGestionMaterias() {
    // Inicializar tooltips de Bootstrap si están disponibles
    inicializarTooltips();
    
    // Configurar validación del formulario
    configurarValidacionFormulario();
    
    // Configurar interacciones de la tabla
    configurarInteraccionesTabla();
    
    // Configurar animaciones
    configurarAnimaciones();
    
    // Mostrar información de debug en desarrollo
    manejarDebugInfo();
}

function inicializarTooltips() {
    // Inicializar tooltips si Bootstrap está disponible
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

function configurarValidacionFormulario() {
    const formulario = document.querySelector('.materia-form');
    const inputMateria = document.querySelector('.materia-input');
    const botonSubmit = document.querySelector('.submit-btn');
    
    if (!formulario) return;
    
    // Validación en tiempo real del input
    if (inputMateria) {
        inputMateria.addEventListener('input', function(e) {
            const valor = e.target.value;
            const maxCaracteres = 24;
            
            // Contador de caracteres
            const contador = valor.length;
            const contadorElemento = document.getElementById('contador-caracteres') || crearContadorCaracteres(inputMateria);
            
            contadorElemento.textContent = `${contador}/${maxCaracteres} caracteres`;
            
            if (contador > maxCaracteres) {
                contadorElemento.style.color = '#dc3545';
                inputMateria.style.borderColor = '#dc3545';
            } else {
                contadorElemento.style.color = '#6c757d';
                inputMateria.style.borderColor = contador > 0 ? '#28a745' : '#e9ecef';
            }
        });
        
        // Efecto de foco
        inputMateria.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        inputMateria.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    }
    
    // Validación del formulario al enviar
    formulario.addEventListener('submit', function(e) {
        const nombreMateria = inputMateria?.value.trim();
        
        if (!nombreMateria) {
            e.preventDefault();
            mostrarMensajeError('⚠️ Debe ingresar el nombre de la materia.');
            inputMateria?.focus();
            return;
        }
        
        if (nombreMateria.length > 24) {
            e.preventDefault();
            mostrarMensajeError('⚠️ El nombre no puede superar los 24 caracteres.');
            inputMateria?.focus();
            return;
        }
        
        // Mostrar loading en el botón
        if (botonSubmit) {
            const textoOriginal = botonSubmit.innerHTML;
            botonSubmit.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
            botonSubmit.disabled = true;
            
            // Restaurar botón después de 3 segundos (por si hay error)
            setTimeout(() => {
                botonSubmit.innerHTML = textoOriginal;
                botonSubmit.disabled = false;
            }, 3000);
        }
    });
}

function crearContadorCaracteres(inputElement) {
    const contador = document.createElement('div');
    contador.id = 'contador-caracteres';
    contador.className = 'contador-caracteres';
    contador.style.fontSize = '0.75rem';
    contador.style.marginTop = '0.25rem';
    contador.style.color = '#6c757d';
    
    inputElement.parentNode.appendChild(contador);
    return contador;
}

function configurarInteraccionesTabla() {
    const filasMaterias = document.querySelectorAll('.materia-row');
    const botonesAccion = document.querySelectorAll('.desactivar-btn, .activar-btn');
    
    // Efectos hover en filas
    filasMaterias.forEach(fila => {
        fila.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s ease';
        });
    });
    
    // Confirmación mejorada para acciones
    botonesAccion.forEach(boton => {
        boton.addEventListener('click', function(e) {
            const esDesactivar = this.classList.contains('desactivar-btn');
            const nombreMateria = this.closest('.materia-row').querySelector('.materia-nombre').textContent;
            const mensaje = esDesactivar ? 
                `¿Desactivar "${nombreMateria}"?\n\nNo aparecerá en los listados normales.` :
                `¿Reactivar "${nombreMateria}"?\n\nVolverá a aparecer en los listados normales.`;
            
            if (!confirm(mensaje)) {
                e.preventDefault();
            } else {
                // Mostrar feedback visual
                this.innerHTML = esDesactivar ? 
                    '<i class="bi bi-hourglass-split"></i> Procesando...' :
                    '<i class="bi bi-hourglass-split"></i> Activando...';
                this.disabled = true;
            }
        });
    });
}

function configurarAnimaciones() {
    // Animación para las tarjetas de estadísticas
    const statsCards = document.querySelectorAll('.stats-card');
    
    statsCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animate__animated', 'animate__fadeInUp');
    });
    
    // Animación para las filas de la tabla
    const filasTabla = document.querySelectorAll('.materia-row');
    
    filasTabla.forEach((fila, index) => {
        fila.style.animationDelay = `${index * 0.05}s`;
        fila.classList.add('animate__animated', 'animate__fadeIn');
    });
}

function manejarDebugInfo() {
    const debugInfo = document.querySelector('.debug-info');
    
    // Mostrar/ocultar debug info con Ctrl+D
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'd') {
            e.preventDefault();
            if (debugInfo) {
                debugInfo.classList.toggle('d-none');
            }
        }
    });
    
    // Auto-ocultar mensajes de alerta después de 5 segundos
    const alertas = document.querySelectorAll('.alert:not(.alert-danger)');
    alertas.forEach(alerta => {
        setTimeout(() => {
            if (alerta.parentNode) {
                alerta.style.opacity = '0';
                alerta.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    if (alerta.parentNode) {
                        alerta.remove();
                    }
                }, 500);
            }
        }, 5000);
    });
}

function mostrarMensajeError(mensaje) {
    // Crear o actualizar mensaje de error
    let mensajeError = document.querySelector('.mensaje-error-temporal');
    
    if (!mensajeError) {
        mensajeError = document.createElement('div');
        mensajeError.className = 'alert alert-danger mensaje-error-temporal';
        mensajeError.style.position = 'fixed';
        mensajeError.style.top = '20px';
        mensajeError.style.right = '20px';
        mensajeError.style.zIndex = '9999';
        mensajeError.style.minWidth = '300px';
        document.body.appendChild(mensajeError);
    }
    
    mensajeError.textContent = mensaje;
    mensajeError.style.display = 'block';
    
    // Auto-ocultar después de 3 segundos
    setTimeout(() => {
        mensajeError.style.display = 'none';
    }, 3000);
}

// Funciones utilitarias para gestión de materias
const MateriasUtils = {
    /**
     * Validar nombre de materia
     */
    validarNombre: function(nombre) {
        if (!nombre || nombre.trim().length === 0) {
            return { valido: false, error: 'El nombre no puede estar vacío' };
        }
        
        if (nombre.length > 24) {
            return { valido: false, error: 'El nombre no puede superar los 24 caracteres' };
        }
        
        // Validar caracteres permitidos (opcional)
        const regex = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_()&]+$/;
        if (!regex.test(nombre)) {
            return { valido: false, error: 'El nombre contiene caracteres no permitidos' };
        }
        
        return { valido: true, error: null };
    },
    
    /**
     * Formatear nombre de materia
     */
    formatearNombre: function(nombre) {
        return nombre.trim()
            .replace(/\s+/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    },
    
    /**
     * Obtener estadísticas actualizadas (simulación)
     */
    obtenerEstadisticas: function() {
        // En una implementación real, esto haría una petición AJAX
        console.log('Obteniendo estadísticas actualizadas...');
    }
};

// Exportar para uso global (si es necesario)
window.MateriasUtils = MateriasUtils;