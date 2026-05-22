class PasswordValidator {
    constructor(inputId, options = {}) {
        this.input = document.querySelector(inputId);
        if (!this.input) return;
        this.strengthBar = options.strengthBar ? document.querySelector(options.strengthBar) : null;
        this.requirements = {};
        if (options.requirements) {
            for (const [key, sel] of Object.entries(options.requirements)) {
                this.requirements[key] = document.querySelector(sel);
            }
        }
        this.input.addEventListener('input', () => this.updateUI(this.calculateStrength(this.input.value)));
    }

    calculateStrength(pass) {
        if (!pass) return 0;
        let score = 0;
        if (pass.length >= 8) score += 25;
        if (pass.length >= 12) score += 10;
        if (/[a-z]/.test(pass)) score += 15;
        if (/[A-Z]/.test(pass)) score += 15;
        if (/\d/.test(pass)) score += 15;
        if (/[\W_]/.test(pass)) score += 20;
        return Math.min(100, score);
    }

    updateUI(strength) {
        if (this.strengthBar) {
            this.strengthBar.value = strength;
            const color = strength < 25 ? '#dc3545' : strength < 50 ? '#fd7e14' : strength < 75 ? '#ffc107' : '#28a745';
            this.strengthBar.style.accentColor = color;
        }
        if (this.requirements.length) {
            const met = this.requirements.length;
            this.requirements.length.el.textContent = this.input.value.length >= 8 ? '✓' : '✗';
        }
        if (this.requirements.lowercase) {
            this.requirements.lowercase.el.textContent = /[a-z]/.test(this.input.value) ? '✓' : '✗';
        }
        if (this.requirements.uppercase) {
            this.requirements.uppercase.el.textContent = /[A-Z]/.test(this.input.value) ? '✓' : '✗';
        }
        if (this.requirements.number) {
            this.requirements.number.el.textContent = /\d/.test(this.input.value) ? '✓' : '✗';
        }
        if (this.requirements.special) {
            this.requirements.special.el.textContent = /[\W_]/.test(this.input.value) ? '✓' : '✗';
        }
    }
}

class GroupFilter {
    constructor(filterBtnSelector, cardSelector) {
        this.buttons = document.querySelectorAll(filterBtnSelector);
        this.cards = document.querySelectorAll(cardSelector);
        if (!this.buttons.length || !this.cards.length) return;
        this.buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const filter = btn.dataset.filter || 'all';
                this.cards.forEach(card => {
                    const group = card.dataset.group || '';
                    card.style.display = filter === 'all' || group === filter ? '' : 'none';
                });
            });
        });
    }
}

class FormAnimations {
    constructor(formSelector) {
        this.form = document.querySelector(formSelector);
        if (!this.form) return;
        this.steps = this.form.querySelectorAll('.form-step');
        this.currentStep = 0;
        if (!this.steps.length) return;
        this.showStep(0);
        this.form.querySelectorAll('.btn-next').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.validateStep(this.currentStep)) {
                    this.showStep(this.currentStep + 1);
                }
            });
        });
        this.form.querySelectorAll('.btn-prev').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.showStep(this.currentStep - 1);
            });
        });
        this.form.addEventListener('submit', (e) => {
            if (!this.validateStep(this.currentStep)) {
                e.preventDefault();
                const firstInvalid = this.steps[this.currentStep].querySelector(':invalid');
                firstInvalid?.focus();
                return;
            }
            if (typeof ToastSystem !== 'undefined') {
                ToastSystem.success('Registro exitoso', 'Sus datos han sido guardados correctamente.');
            }
        });
    }

    showStep(index) {
        this.steps.forEach((step, i) => {
            step.classList.toggle('active', i === index);
            step.style.display = i === index ? 'block' : 'none';
            if (i === index) {
                step.style.animation = 'fadeIn 0.3s ease';
            }
        });
        this.currentStep = index;
    }

    validateStep(index) {
        const step = this.steps[index];
        if (!step) return true;
        const fields = step.querySelectorAll('input, select, textarea');
        let valid = true;
        fields.forEach(f => {
            if (!f.checkValidity()) {
                f.classList.add('is-invalid');
                valid = false;
            } else {
                f.classList.remove('is-invalid');
            }
        });
        return valid;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new PasswordValidator('#password', {
        strengthBar: '#passwordStrength',
        requirements: {
            length: { el: document.querySelector('#lengthReq') },
            lowercase: { el: document.querySelector('#lowercaseReq') },
            uppercase: { el: document.querySelector('#uppercaseReq') },
            number: { el: document.querySelector('#numberReq') },
            special: { el: document.querySelector('#specialReq') }
        }
    });
    new PasswordValidator('#confirm_password', {});
    new GroupFilter('.btn-filter', '.card-item');
    new FormAnimations('#registerForm');

    const pass = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');
    const matchStatus = document.getElementById('passwordMatch');
    if (pass && confirm && matchStatus) {
        const check = () => {
            if (!confirm.value) {
                matchStatus.textContent = '';
            } else if (pass.value === confirm.value) {
                matchStatus.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Coinciden</span>';
            } else {
                matchStatus.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle"></i> No coinciden</span>';
            }
        };
        pass.addEventListener('input', check);
        confirm.addEventListener('input', check);
    }
});
