class PasswordValidator {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeState();
    }

    setupEventListeners() {
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirm');
        const form = document.getElementById('registerForm');

        if (passwordInput) {
            passwordInput.addEventListener('input', () => {
                this.checkPasswordStrength();
                this.checkPasswordMatch();
            });
        }

        if (confirmInput) {
            confirmInput.addEventListener('input', () => {
                this.checkPasswordMatch();
            });
        }

        if (form) {
            form.addEventListener('submit', (e) => this.validateForm(e));
        }

        // Agregar event listeners para los botones de mostrar/ocultar contraseña
        document.querySelectorAll('.password-toggle').forEach(button => {
            button.addEventListener('click', (e) => {
                const fieldId = e.target.closest('.password-input-group').querySelector('input').id;
                this.togglePassword(fieldId);
            });
        });
    }

    initializeState() {
        this.checkPasswordStrength();
        this.checkPasswordMatch();
    }

    togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.parentNode.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    checkPasswordStrength() {
        const password = document.getElementById('password').value;
        const strengthBar = document.getElementById('passwordStrength');
        const requirements = {
            length: document.getElementById('lengthReq'),
            lowercase: document.getElementById('lowercaseReq'),
            uppercase: document.getElementById('uppercaseReq'),
            number: document.getElementById('numberReq'),
            special: document.getElementById('specialReq')
        };

        // Verificar requisitos
        const hasLength = password.length >= 8;
        const hasLowercase = /[a-z]/.test(password);
        const hasUppercase = /[A-Z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasSpecial = /[\W_]/.test(password);

        // Actualizar visualización de requisitos
        this.updateRequirement(requirements.length, hasLength);
        this.updateRequirement(requirements.lowercase, hasLowercase);
        this.updateRequirement(requirements.uppercase, hasUppercase);
        this.updateRequirement(requirements.number, hasNumber);
        this.updateRequirement(requirements.special, hasSpecial);

        // Calcular fortaleza
        const strength = [hasLength, hasLowercase, hasUppercase, hasNumber, hasSpecial]
            .filter(Boolean).length;

        // Actualizar barra de fortaleza
        this.updateStrengthBar(strengthBar, strength);
    }

    updateRequirement(element, condition) {
        if (condition) {
            element.className = 'requirement-met';
        } else {
            element.className = 'requirement-not-met';
        }
    }

    updateStrengthBar(strengthBar, strength) {
        strengthBar.className = 'password-strength';
        
        if (strength <= 1) {
            strengthBar.classList.add('password-weak');
        } else if (strength <= 2) {
            strengthBar.classList.add('password-medium');
        } else if (strength <= 4) {
            strengthBar.classList.add('password-strong');
        } else {
            strengthBar.classList.add('password-very-strong');
        }
    }

    checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('password_confirm').value;
        const matchText = document.getElementById('passwordMatch');
        
        if (!matchText) return;

        if (confirmPassword === '') {
            matchText.textContent = '';
            matchText.className = 'form-text';
        } else if (password === confirmPassword) {
            matchText.innerHTML = '<i class="bi bi-check-circle-fill"></i> Las contraseñas coinciden';
            matchText.className = 'form-text text-success';
        } else {
            matchText.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> Las contraseñas no coinciden';
            matchText.className = 'form-text text-danger';
        }
    }

    validateForm(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('password_confirm').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            this.showAlert('Las contraseñas no coinciden. Por favor, verifique.', 'danger');
            document.getElementById('password_confirm').focus();
            return false;
        }

        // Verificar fortaleza mínima de contraseña
        const hasLength = password.length >= 8;
        const hasLowercase = /[a-z]/.test(password);
        const hasUppercase = /[A-Z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasSpecial = /[\W_]/.test(password);

        const strength = [hasLength, hasLowercase, hasUppercase, hasNumber, hasSpecial]
            .filter(Boolean).length;

        if (strength < 3) {
            e.preventDefault();
            this.showAlert('La contraseña es demasiado débil. Por favor, cumpla con todos los requisitos.', 'warning');
            document.getElementById('password').focus();
            return false;
        }

        return true;
    }

    showAlert(message, type) {
        // Remover alertas existentes
        const existingAlerts = document.querySelectorAll('.custom-alert');
        existingAlerts.forEach(alert => alert.remove());

        // Crear nueva alerta
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} custom-alert`;
        alert.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-${type === 'danger' ? 'exclamation-triangle' : 'info-circle'}-fill me-2"></i>
                <span>${message}</span>
            </div>
        `;

        // Insertar después del primer h4
        const title = document.querySelector('h4');
        if (title) {
            title.parentNode.insertBefore(alert, title.nextSibling);
        }

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
}

// ==============================
// Clase para el Filtro de Grupos Activos/Inactivos
// ==============================
class GroupFilter {
    constructor() {
        this.filterButtons = document.querySelectorAll('.filter-buttons .btn');
        this.gruposSelect = document.getElementById('gruposSelect');
        this.init();
    }
    
    init() {
        if (!this.gruposSelect || this.filterButtons.length === 0) return;
        
        this.filterButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handleFilterClick(e));
        });
        
        // Inicializar mostrando todos los grupos
        this.filterGroups('all');
    }
    
    handleFilterClick(event) {
        const button = event.currentTarget;
        
        // Remover clase active de todos los botones
        this.filterButtons.forEach(btn => btn.classList.remove('active'));
        // Agregar clase active al botón clickeado
        button.classList.add('active');
        
        const filter = button.getAttribute('data-filter');
        this.filterGroups(filter);
    }
    
    filterGroups(filter) {
        const optgroups = this.gruposSelect.querySelectorAll('optgroup');
        
        switch(filter) {
            case 'all':
                this.showAllGroups(optgroups);
                break;
            case 'active':
                this.showActiveGroups(optgroups);
                break;
            case 'inactive':
                this.showInactiveGroups(optgroups);
                break;
        }
    }
    
    showAllGroups(optgroups) {
        optgroups.forEach(optgroup => {
            optgroup.style.display = '';
            optgroup.querySelectorAll('option').forEach(option => {
                option.style.display = '';
            });
        });
    }
    
    showActiveGroups(optgroups) {
        optgroups.forEach(optgroup => {
            if (optgroup.getAttribute('data-estado') === 'activo') {
                optgroup.style.display = '';
                optgroup.querySelectorAll('option').forEach(option => {
                    option.style.display = '';
                });
            } else {
                optgroup.style.display = 'none';
                optgroup.querySelectorAll('option').forEach(option => {
                    option.style.display = 'none';
                });
            }
        });
    }
    
    showInactiveGroups(optgroups) {
        optgroups.forEach(optgroup => {
            if (optgroup.getAttribute('data-estado') === 'inactivo') {
                optgroup.style.display = '';
                optgroup.querySelectorAll('option').forEach(option => {
                    option.style.display = '';
                });
            } else {
                optgroup.style.display = 'none';
                optgroup.querySelectorAll('option').forEach(option => {
                    option.style.display = 'none';
                });
            }
        });
    }
}

// ==============================
// Clase para Animaciones del Formulario
// ==============================
class FormAnimations {
    constructor() {
        this.init();
    }
    
    init() {
        this.animateFormElements();
    }
    
    animateFormElements() {
        const formElements = document.querySelectorAll('.form-control, .form-select, .btn');
        formElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = `all 0.5s ease ${index * 0.1}s`;
            
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, 100);
        });
    }
}

// ==============================
// Inicialización cuando el DOM esté listo
// ==============================
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar validación de contraseñas
    new PasswordValidator();
    
    // Inicializar filtro de grupos
    new GroupFilter();
    
    // Inicializar animaciones del formulario
    new FormAnimations();
    
    // Agregar validación adicional para grupos inactivos
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const gruposSelect = document.getElementById('gruposSelect');
            const selectedOptions = Array.from(gruposSelect.selectedOptions);
            
            // Verificar si se seleccionó algún grupo inactivo
            const hasInactiveGroup = selectedOptions.some(option => {
                const optgroup = option.closest('optgroup');
                return optgroup && optgroup.getAttribute('data-estado') === 'inactivo';
            });
            
            if (hasInactiveGroup) {
                e.preventDefault();
                alert('No puede asignar usuarios a grupos inactivos. Por favor, seleccione solo grupos activos.');
                return false;
            }
        });
    }
});