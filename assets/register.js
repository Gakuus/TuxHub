class PasswordValidator {
    constructor(inputId, options = {}) {
        this.input = document.querySelector(inputId);
        if (!this.input) return;

        this.strengthFill = options.strengthFill
            ? document.querySelector(options.strengthFill)
            : null;
        this.requirements = {};
        if (options.requirements) {
            for (const [key, sel] of Object.entries(options.requirements)) {
                const el = document.querySelector(sel);
                if (el) this.requirements[key] = el;
            }
        }

        this.input.addEventListener('input', () => {
            const val = this.input.value;
            const score = this.calculateStrength(val);
            this.updateStrength(score);
            this.updateRequirements(val);
        });
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

    updateStrength(score) {
        if (!this.strengthFill) return;
        this.strengthFill.style.width = score + '%';
        this.strengthFill.className = 'strength-fill';
        if (score === 0) {
            this.strengthFill.style.width = '0';
        } else if (score < 25) {
            this.strengthFill.classList.add('weak');
        } else if (score < 50) {
            this.strengthFill.classList.add('medium');
        } else if (score < 75) {
            this.strengthFill.classList.add('strong');
        } else {
            this.strengthFill.classList.add('very-strong');
        }
    }

    updateRequirements(val) {
        const checks = {
            length: val.length >= 8,
            lowercase: /[a-z]/.test(val),
            uppercase: /[A-Z]/.test(val),
            number: /\d/.test(val),
            special: /[\W_]/.test(val),
        };
        for (const [key, el] of Object.entries(this.requirements)) {
            if (!el) continue;
            if (checks[key]) {
                el.classList.add('met');
                el.querySelector('i').className = 'bi bi-check-circle-fill';
            } else {
                el.classList.remove('met');
                el.querySelector('i').className = 'bi bi-x-circle';
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    if (!form) return;

    const steps = form.querySelectorAll('.step-content');
    const circles = [
        document.getElementById('circle1'),
        document.getElementById('circle2'),
        document.getElementById('circle3'),
    ];
    const labels = [
        document.getElementById('label1'),
        document.getElementById('label2'),
        document.getElementById('label3'),
    ];
    const lines = [
        document.getElementById('line1'),
        document.getElementById('line2'),
    ];
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    let currentStep = 0;

    function showStep(index) {
        steps.forEach((s, i) => {
            s.classList.toggle('active', i === index);
        });
        circles.forEach((c, i) => {
            if (!c) return;
            c.classList.toggle('active', i === index);
            c.classList.toggle('completed', i < index);
        });
        labels.forEach((l, i) => {
            if (l) l.classList.toggle('active', i === index);
        });
        lines.forEach((l, i) => {
            if (l) l.classList.toggle('active', i < index);
        });

        prevBtn.disabled = index === 0;
        nextBtn.classList.toggle('d-none', index === steps.length - 1);
        submitBtn.classList.toggle('d-none', index !== steps.length - 1);
        currentStep = index;
    }

    function validateStep(index) {
        const step = steps[index];
        if (!step) return true;
        const fields = step.querySelectorAll('[required]');
        let valid = true;
        fields.forEach((f) => {
            if (!f.checkValidity() || (f.type === 'checkbox' && !f.checked)) {
                f.classList.add('has-error');
                valid = false;
            } else {
                f.classList.remove('has-error');
            }
        });
        return valid;
    }

    nextBtn.addEventListener('click', () => {
        if (validateStep(currentStep)) {
            if (currentStep < steps.length - 1) {
                showStep(currentStep + 1);
            }
        } else {
            const firstInvalid = steps[currentStep].querySelector('.has-error');
            if (firstInvalid) firstInvalid.focus();
        }
    });

    prevBtn.addEventListener('click', () => {
        if (currentStep > 0) showStep(currentStep - 1);
    });

    form.addEventListener('submit', (e) => {
        if (!validateStep(currentStep)) {
            e.preventDefault();
            const firstInvalid = steps[currentStep].querySelector('.has-error');
            if (firstInvalid) firstInvalid.focus();
            return;
        }
        if (typeof ToastSystem !== 'undefined') {
            ToastSystem.success('Registrando...', 'Procesando solicitud.');
        }
    });

    // Init password validators
    new PasswordValidator('#password', {
        strengthFill: '#strengthFill',
        requirements: {
            length: '#requirements [data-req="length"]',
            lowercase: '#requirements [data-req="lowercase"]',
            uppercase: '#requirements [data-req="uppercase"]',
            number: '#requirements [data-req="number"]',
            special: '#requirements [data-req="special"]',
        },
    });

    // Password toggle
    document.querySelectorAll('.password-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.for;
            const input = document.getElementById(targetId);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    // Password match indicator
    const pass = document.getElementById('password');
    const confirm = document.getElementById('password_confirm');
    const matchStatus = document.getElementById('passwordMatch');
    if (pass && confirm && matchStatus) {
        const check = () => {
            if (!confirm.value) {
                matchStatus.textContent = '';
                matchStatus.className = 'password-match';
            } else if (pass.value === confirm.value) {
                matchStatus.innerHTML = '<i class="bi bi-check-circle-fill"></i> Las contraseñas coinciden';
                matchStatus.className = 'password-match match';
            } else {
                matchStatus.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> Las contraseñas no coinciden';
                matchStatus.className = 'password-match no-match';
            }
        };
        pass.addEventListener('input', check);
        confirm.addEventListener('input', check);
    }

    // Group filter
    const filterBtns = document.querySelectorAll('.filter-group .btn-filter');
    const gruposSelect = document.getElementById('gruposSelect');
    if (filterBtns.length && gruposSelect) {
        filterBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                filterBtns.forEach((b) => b.classList.remove('active'));
                btn.classList.add('active');
                const filter = btn.dataset.filter || 'all';
                Array.from(gruposSelect.options).forEach((opt) => {
                    if (filter === 'all') {
                        opt.style.display = '';
                    } else if (filter === 'active') {
                        opt.style.display = opt.dataset.estado === 'activo' ? '' : 'none';
                    } else {
                        opt.style.display = opt.dataset.estado === 'inactivo' ? '' : 'none';
                    }
                });
            });
        });
    }

    // Cédula only numbers
    const cedula = document.getElementById('cedula');
    if (cedula) {
        cedula.addEventListener('input', () => {
            cedula.value = cedula.value.replace(/\D/g, '');
        });
    }
});
