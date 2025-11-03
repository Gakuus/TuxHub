// ============================================
// 🕒 SISTEMA DE INACTIVIDAD MEJORADO
// ============================================

class SessionManager {
    constructor() {
        this.timeout = 900000; // 15 minutos en milisegundos
        this.warningTime = 120000; // 2 minutos en milisegundos
        this.checkInterval = 30000; // Revisar cada 30 segundos
        this.lastActivity = Date.now();
        this.warningShown = false;
        this.timer = null;
        this.inactivityTimer = null;
        
        this.init();
    }
    
    init() {
        this.resetTimer();
        this.setupEventListeners();
        this.startSessionChecker();
        this.updateInactivityDisplay();
    }
    
    setupEventListeners() {
        // Eventos que indican actividad del usuario
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        events.forEach(event => {
            document.addEventListener(event, () => this.resetTimer(), true);
        });
        
        // También monitorear inputs de formulario
        document.addEventListener('input', () => this.resetTimer(), true);
    }
    
    resetTimer() {
        this.lastActivity = Date.now();
        this.warningShown = false;
        this.hideWarning();
        this.updateInactivityDisplay();
    }
    
    startSessionChecker() {
        this.timer = setInterval(() => {
            const idleTime = Date.now() - this.lastActivity;
            const timeLeft = this.timeout - idleTime;
            
            this.updateInactivityDisplay(timeLeft);
            
            // Mostrar advertencia cuando queden 2 minutos
            if (timeLeft <= this.warningTime && timeLeft > 0 && !this.warningShown) {
                this.showWarning(timeLeft);
                this.warningShown = true;
            }
            
            // Cerrar sesión cuando se agote el tiempo
            if (idleTime >= this.timeout) {
                this.logoutDueToInactivity();
            }
        }, this.checkInterval);
    }
    
    showWarning(timeLeft) {
        const warningElement = document.getElementById('sessionWarning');
        const countdownElement = document.getElementById('countdown');
        
        if (warningElement && countdownElement) {
            // Convertir a minutos y segundos
            const minutes = Math.floor(timeLeft / 60000);
            const seconds = Math.floor((timeLeft % 60000) / 1000);
            
            countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            warningElement.classList.remove('d-none');
            
            // Actualizar countdown cada segundo
            this.inactivityTimer = setInterval(() => {
                timeLeft -= 1000;
                const min = Math.floor(timeLeft / 60000);
                const sec = Math.floor((timeLeft % 60000) / 1000);
                
                if (timeLeft <= 0) {
                    clearInterval(this.inactivityTimer);
                    this.logoutDueToInactivity();
                } else {
                    countdownElement.textContent = `${min}:${sec.toString().padStart(2, '0')}`;
                }
            }, 1000);
        }
    }
    
    hideWarning() {
        const warningElement = document.getElementById('sessionWarning');
        if (warningElement) {
            warningElement.classList.add('d-none');
        }
        if (this.inactivityTimer) {
            clearInterval(this.inactivityTimer);
        }
    }
    
    updateInactivityDisplay(timeLeft = null) {
        const timerElement = document.getElementById('inactivityTimer');
        if (!timerElement) return;
        
        if (timeLeft === null) {
            timeLeft = this.timeout - (Date.now() - this.lastActivity);
        }
        
        if (timeLeft <= 0) {
            timerElement.textContent = '00:00';
            timerElement.className = 'session-timer-critical';
        } else {
            const minutes = Math.floor(timeLeft / 60000);
            const seconds = Math.floor((timeLeft % 60000) / 1000);
            timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            // Cambiar color según el tiempo restante
            if (timeLeft <= 120000) { // 2 minutos
                timerElement.className = 'session-timer-critical';
            } else if (timeLeft <= 300000) { // 5 minutos
                timerElement.className = 'session-timer-warning';
            } else {
                timerElement.className = 'session-timer-normal';
            }
        }
    }
    
    async extendSession() {
        try {
            const response = await fetch('?keep_alive=1');
            if (response.ok) {
                this.resetTimer();
                this.hideWarning();
                this.showNotification('Sesión extendida correctamente', 'success');
            }
        } catch (error) {
            console.error('Error extendiendo sesión:', error);
            this.showNotification('Error al extender sesión', 'error');
        }
    }
    
    logoutDueToInactivity() {
        this.cleanup();
        this.showNotification('Sesión cerrada por inactividad', 'warning');
        window.location.href = 'backend/logout.php?timeout=1';
    }
    
    showNotification(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        const toastId = 'toast-' + Date.now();
        
        const bgColor = type === 'success' ? 'bg-success' : 
                       type === 'error' ? 'bg-danger' : 
                       type === 'warning' ? 'bg-warning' : 'bg-info';
        
        const icon = type === 'success' ? 'bi-check-circle-fill' : 
                    type === 'error' ? 'bi-exclamation-circle-fill' : 
                    type === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill';
        
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgColor} border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${icon} me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.remove();
            }
        }, 5000);
    }
    
    cleanup() {
        if (this.timer) clearInterval(this.timer);
        if (this.inactivityTimer) clearInterval(this.inactivityTimer);
    }
}

// ============================================
// 🚪 SISTEMA DE LOGOUT MEJORADO
// ============================================

function confirmLogout() {
    const modal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
    modal.show();
}

// ============================================
// 📱 INICIALIZACIÓN
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar gestor de sesión
    const sessionManager = new SessionManager();
    
    // Hacer sessionManager global para poder llamarlo desde HTML
    window.sessionManager = sessionManager;
    
    // Prevenir navegación con cambios sin guardar
    let unsavedChanges = false;
    
    window.addEventListener('beforeunload', function(e) {
        if (unsavedChanges) {
            e.preventDefault();
            e.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
            return e.returnValue;
        }
    });
    
    // Detectar cambios en formularios
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        if (!form.action.includes('logout')) {
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    unsavedChanges = true;
                });
            });
            
            form.addEventListener('submit', function() {
                unsavedChanges = false;
            });
        }
    });
    
    // Mostrar notificación de sesión activa
    setTimeout(() => {
        sessionManager.showNotification('Sesión iniciada correctamente', 'success');
    }, 1000);
});