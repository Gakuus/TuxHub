document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const cedula = document.getElementById('cedula');
    const password = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const overlay = document.getElementById('loadingOverlay');
    const toggleBtn = document.getElementById('togglePassword');

    // --- Password visibility toggle ---
    if (toggleBtn && password) {
        toggleBtn.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggleBtn.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });
    }

    // --- Cédula: solo números ---
    if (cedula) {
        cedula.addEventListener('input', () => {
            cedula.value = cedula.value.replace(/\D/g, '');
        });
    }

    // --- Form validation & submit ---
    if (form) {
        form.addEventListener('submit', (e) => {
            const cedulaVal = (cedula?.value || '').trim();
            const passVal = password?.value || '';
            let valid = true;

            document.querySelectorAll('.input-group.has-error').forEach(el => {
                el.classList.remove('has-error');
            });

            if (!cedulaVal) {
                markError(cedula, 'La cédula es requerida.');
                valid = false;
            } else if (!/^\d{7,8}$/.test(cedulaVal)) {
                markError(cedula, 'La cédula debe tener 8 dígitos numéricos.');
                valid = false;
                cedula.value = '';
            }

            if (!passVal) {
                markError(password, 'La contraseña es requerida.');
                valid = false;
            } else if (passVal.length < 8) {
                markError(password, 'Mínimo 8 caracteres.');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                return;
            }

            // Show loading
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
            if (overlay) {
                overlay.classList.add('active');
            }
        });
    }

    function markError(input, msg) {
        if (!input) return;
        const group = input.closest('.input-group');
        if (group) {
            group.classList.add('has-error');
            const hint = group.querySelector('.input-hint');
            if (hint) hint.textContent = msg;
        }
        input.focus();
    }

    // --- Focus first field ---
    const target = cedula || password;
    if (target) {
        setTimeout(() => target.focus(), 150);
    }
});
