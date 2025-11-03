// script.js - CORREGIDO: Límite de cédula a 8 caracteres exactos
class LoginForm {
    constructor() {
        this.init();
    }

    init() {
        this.setupPasswordToggle();
        this.setupFormSubmission();
        this.setupCedulaValidation();
        this.setupAutoFocus();
        this.setupErrorHandling();
        this.setupResponsiveChecks();
    }

    setupPasswordToggle() {
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (togglePassword && passwordInput && eyeIcon) {
            togglePassword.addEventListener('click', () => {
                const isCurrentlyPassword = passwordInput.type === 'password';
                
                passwordInput.type = isCurrentlyPassword ? 'text' : 'password';
                
                if (isCurrentlyPassword) {
                    eyeIcon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
                    togglePassword.setAttribute('aria-label', 'Ocultar contraseña');
                } else {
                    eyeIcon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
                    togglePassword.setAttribute('aria-label', 'Mostrar contraseña');
                }
                
                passwordInput.focus();
            });

            // Soporte para teclado
            togglePassword.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    togglePassword.click();
                }
            });
        }
    }

    setupFormSubmission() {
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');

        if (loginForm && submitBtn && submitText) {
            loginForm.addEventListener('submit', (e) => {
                if (!this.validateForm()) {
                    e.preventDefault();
                    return;
                }

                // Mostrar estado de carga
                submitBtn.disabled = true;
                submitBtn.classList.add('btn-loading');
                submitText.textContent = 'Verificando...';
                
                // Timeout de seguridad
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        this.resetSubmitButton();
                        alert('El servidor está tardando en responder. Intente nuevamente.');
                    }
                }, 10000);
            });
        }
    }

    // VALIDACIÓN CORREGIDA: Cédula exactamente 8 caracteres
    validateForm() {
        const cedula = document.getElementById('cedula')?.value.trim();
        const password = document.getElementById('password')?.value;
        const recaptcha = document.querySelector('.g-recaptcha-response')?.value;

        // Validación básica
        if (!cedula || !password) {
            this.showTemporaryMessage('Por favor complete todos los campos', 'warning');
            return false;
        }

        // Validación de cédula - EXACTAMENTE 8 DÍGITOS
        if (!/^\d+$/.test(cedula)) {
            this.showTemporaryMessage('La cédula solo puede contener números', 'warning');
            return false;
        }

        // CORRECCIÓN: Exactamente 8 caracteres, no 6-8
        if (cedula.length !== 8) {
            this.showTemporaryMessage('La cédula debe tener exactamente 8 dígitos', 'warning');
            return false;
        }

        // Validación de reCAPTCHA
        if (!recaptcha) {
            this.showTemporaryMessage('Por favor complete el reCAPTCHA', 'warning');
            return false;
        }

        return true;
    }

    setupCedulaValidation() {
        const cedulaInput = document.getElementById('cedula');
        
        if (cedulaInput) {
            // Solo números y máximo 8 caracteres
            cedulaInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/[^0-9]/g, '');
                
                // Limitar a 8 caracteres
                if (value.length > 8) {
                    value = value.substring(0, 8);
                }
                
                e.target.value = value;
                
                // Validación visual
                this.updateCedulaValidationState(e.target);
            });

            // Prevenir pegado de texto no numérico
            cedulaInput.addEventListener('paste', (e) => {
                setTimeout(() => {
                    let value = e.target.value.replace(/[^0-9]/g, '');
                    if (value.length > 8) {
                        value = value.substring(0, 8);
                    }
                    e.target.value = value;
                    this.updateCedulaValidationState(e.target);
                }, 0);
            });

            // Validación al perder el foco
            cedulaInput.addEventListener('blur', (e) => {
                this.updateCedulaValidationState(e.target);
            });
        }
    }

    updateCedulaValidationState(input) {
        const value = input.value;
        
        if (value.length === 0) {
            input.classList.remove('is-valid', 'is-invalid');
        } else if (/^\d{8}$/.test(value)) { // CORRECCIÓN: Exactamente 8 dígitos
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        }
    }

    setupAutoFocus() {
        const cedulaInput = document.getElementById('cedula');
        
        if (cedulaInput && !cedulaInput.value) {
            setTimeout(() => {
                if (document.visibilityState === 'visible') {
                    cedulaInput.focus();
                }
            }, 300);
        }
    }

    setupErrorHandling() {
        // Auto-cierre de alertas después de 5 segundos
        const autoCloseAlerts = setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('show')) {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }
            });
        }, 5000);

        // Limpiar timeout si el usuario interactúa
        document.addEventListener('mouseenter', () => {
            clearTimeout(autoCloseAlerts);
        }, { once: true });
    }

    setupResponsiveChecks() {
        // Ajustar reCAPTCHA en redimensionamiento
        window.addEventListener('resize', this.debounce(() => {
            this.adjustRecaptcha();
        }, 250));

        // Ajuste inicial
        this.adjustRecaptcha();
    }

    adjustRecaptcha() {
        const recaptchaElement = document.querySelector('.g-recaptcha');
        if (!recaptchaElement) return;

        const containerWidth = recaptchaElement.parentElement.offsetWidth;
        
        if (containerWidth < 300) {
            recaptchaElement.style.transform = 'scale(0.8)';
        } else if (containerWidth < 400) {
            recaptchaElement.style.transform = 'scale(0.9)';
        } else {
            recaptchaElement.style.transform = 'scale(1)';
        }
    }

    // Utilidades
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

    showTemporaryMessage(message, type = 'info') {
        const alertClass = {
            'warning': 'alert-warning',
            'danger': 'alert-danger',
            'success': 'alert-success',
            'info': 'alert-info'
        }[type] || 'alert-info';

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert ${alertClass} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const form = document.getElementById('loginForm');
        form.parentNode.insertBefore(alertDiv, form);

        // Auto-remover después de 4 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                bsAlert.close();
            }
        }, 4000);
    }

    resetSubmitButton() {
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        
        if (submitBtn && submitText) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-loading');
            submitText.textContent = 'Iniciar Sesión';
        }
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new LoginForm();
});