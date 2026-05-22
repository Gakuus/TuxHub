document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.password-toggle');

    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', !isPassword);
                icon.classList.toggle('bi-eye-slash', isPassword);
            }
        });
    }

    const loginForm = document.getElementById('loginForm');
    const cedulaInput = document.getElementById('cedula');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');

    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const cedula = (cedulaInput?.value || '').trim();
            const password = passwordInput?.value || '';
            let valid = true;

            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            cedulaInput?.classList.remove('is-invalid');
            passwordInput?.classList.remove('is-invalid');

            if (!cedula) {
                showError(cedulaInput, 'La cédula es requerida.');
                valid = false;
            } else if (!/^\d+$/.test(cedula)) {
                showError(cedulaInput, 'La cédula solo debe contener números.');
                valid = false;
            }

            if (!password) {
                showError(passwordInput, 'La contraseña es requerida.');
                valid = false;
            }

            if (!valid) return;

            if (submitBtn && submitText) {
                submitBtn.disabled = true;
                submitText.textContent = 'Ingresando...';
            }
            if (typeof Spinner !== 'undefined') {
                Spinner.show(loginForm, { text: 'Verificando credenciales...' });
            }
            loginForm.submit();
        });
    }

    function showError(input, message) {
        if (!input) return;
        input.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        input.parentNode.appendChild(feedback);
    }

    const firstInput = cedulaInput || passwordInput;
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error') && typeof ToastSystem !== 'undefined') {
        ToastSystem.error('Error de inicio de sesión', urlParams.get('error'));
    }
});
