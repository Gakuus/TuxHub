document.addEventListener('DOMContentLoaded', () => {
    const timeoutMinutes = 15;
    const warningMinutes = 2;
    let lastActivity = Date.now();
    let warningShown = false;
    let countdownInterval = null;

    const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click', 'input'];
    const resetActivity = () => {
        lastActivity = Date.now();
        warningShown = false;
        hideWarning();
    };
    events.forEach(e => document.addEventListener(e, resetActivity, { passive: true, capture: true }));

    const timerEl = document.getElementById('inactivityTimer');
    const warningEl = document.getElementById('sessionWarning');
    const countdownEl = document.getElementById('countdown');

    function updateDisplay(timeLeft) {
        if (!timerEl) return;
        const min = Math.floor(timeLeft / 60000);
        const sec = Math.floor((timeLeft % 60000) / 1000);
        timerEl.textContent = `${min}:${sec.toString().padStart(2, '0')}`;
        timerEl.className = timeLeft <= 120000 ? 'session-timer-critical' :
            timeLeft <= 300000 ? 'session-timer-warning' : 'session-timer-normal';
    }

    function showWarning(timeLeft) {
        if (!warningEl || !countdownEl) return;
        warningEl.classList.remove('d-none');
        const min = Math.floor(timeLeft / 60000);
        const sec = Math.floor((timeLeft % 60000) / 1000);
        countdownEl.textContent = `${min}:${sec.toString().padStart(2, '0')}`;
        if (countdownInterval) clearInterval(countdownInterval);
        countdownInterval = setInterval(() => {
            const remaining = Math.max(0, timeLeft - (Date.now() - lastActivity));
            const m = Math.floor(remaining / 60000);
            const s = Math.floor((remaining % 60000) / 1000);
            countdownEl.textContent = `${m}:${s.toString().padStart(2, '0')}`;
            if (remaining <= 0) {
                clearInterval(countdownInterval);
                logoutInactivity();
            }
        }, 1000);
    }

    function hideWarning() {
        if (warningEl) warningEl.classList.add('d-none');
        if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
    }

    function logoutInactivity() {
        if (typeof ToastSystem !== 'undefined') {
            ToastSystem.warning('Sesión expirada', 'Cerrando sesión por inactividad...');
        }
        setTimeout(() => { window.location.href = 'backend/logout.php?timeout=1'; }, 2000);
    }

    setInterval(() => {
        const idle = Date.now() - lastActivity;
        const timeLeft = Math.max(0, (timeoutMinutes * 60000) - idle);

        updateDisplay(timeLeft);

        if (timeLeft <= warningMinutes * 60000 && timeLeft > 0 && !warningShown) {
            warningShown = true;
            showWarning(timeLeft);
            if (typeof ToastSystem !== 'undefined') {
                ToastSystem.warning(
                    'Sesión próxima a expirar',
                    `Su sesión expirará en ${warningMinutes} minutos por inactividad.`,
                    8000
                );
            }
        }

        if (idle >= timeoutMinutes * 60000) {
            hideWarning();
            logoutInactivity();
        }
    }, 1000);

    const extendBtn = document.getElementById('extendSession');
    if (extendBtn) {
        extendBtn.addEventListener('click', async () => {
            try {
                const resp = await fetch('?keep_alive=1');
                if (resp.ok) {
                    resetActivity();
                    hideWarning();
                    if (typeof ToastSystem !== 'undefined') {
                        ToastSystem.success('Sesión extendida', 'Su sesión ha sido renovada.');
                    }
                }
            } catch (err) {
                if (typeof ToastSystem !== 'undefined') {
                    ToastSystem.error('Error', 'No se pudo extender la sesión.');
                }
            }
        });
    }
});
