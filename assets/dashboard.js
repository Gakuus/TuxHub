document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = themeToggle?.querySelector('i');

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        if (themeIcon) {
            themeIcon.classList.remove('bi-moon-fill');
            themeIcon.classList.add('bi-sun-fill');
        }
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            if (themeIcon) {
                themeIcon.classList.toggle('bi-moon-fill', !isDark);
                themeIcon.classList.toggle('bi-sun-fill', isDark);
            }
        });
    }

    const sidebar = document.getElementById('sidebarMenu');
    const toggleBtn = document.getElementById('toggleSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar?.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        });
    }

    document.querySelectorAll('.nav-link').forEach(link => {
        const page = new URLSearchParams(window.location.search).get('page') || '';
        if (link.getAttribute('href')?.includes(page)) {
            link.classList.add('active');
        }
    });

    const sessionMeta = document.querySelector('meta[name="session-timeout"]');
    if (sessionMeta) {
        const timeoutMinutes = parseInt(sessionMeta.getAttribute('content'), 10) || 15;
        const warningMinutes = Math.max(1, timeoutMinutes - 1);
        let lastActivity = Date.now();
        const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart'];
        const resetActivity = () => { lastActivity = Date.now(); };
        events.forEach(e => document.addEventListener(e, resetActivity, { passive: true }));

        const checkInterval = setInterval(() => {
            const elapsed = (Date.now() - lastActivity) / 60000;
            const remaining = timeoutMinutes - elapsed;

            if (remaining <= 1 && remaining > 0 && elapsed > warningMinutes) {
                if (typeof ToastSystem !== 'undefined') {
                    ToastSystem.warning(
                        'Sesión próxima a expirar',
                        `Su sesión expirará en ${Math.ceil(remaining * 60)} segundos por inactividad.`,
                        10000
                    );
                }
            }

            if (elapsed >= timeoutMinutes) {
                clearInterval(checkInterval);
                if (typeof ToastSystem !== 'undefined') {
                    ToastSystem.error('Sesión expirada', 'Será redirigido al login.');
                }
                setTimeout(() => { window.location.href = 'backend/logout.php?timeout=1'; }, 3000);
            }
        }, 30000);
    }

    document.querySelectorAll('.alert .btn-close').forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.alert')?.remove();
        });
    });
});
