document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }

    // Theme toggle (both header button and sidebar footer button)
    function toggleTheme(btn) {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        document.querySelectorAll('#themeToggle, #headerThemeToggle').forEach(b => {
            const icon = b.querySelector('i');
            if (icon) {
                icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            }
        });
    }

    document.querySelectorAll('#themeToggle, #headerThemeToggle').forEach(btn => {
        if (btn) btn.addEventListener('click', () => toggleTheme(btn));
    });

    // Sync theme icon on load
    if (document.body.classList.contains('dark-mode')) {
        document.querySelectorAll('#themeToggle i, #headerThemeToggle i').forEach(icon => {
            if (icon) icon.className = 'bi bi-sun-fill';
        });
    }

    // Sidebar toggle (mobile)
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

    // Active nav link highlighting
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'home';
    const currentTipo = new URLSearchParams(window.location.search).get('tipo') || '';
    document.querySelectorAll('.nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (!href) return;
        const url = new URL(href, window.location.origin);
        const linkPage = url.searchParams.get('page');
        const linkTipo = url.searchParams.get('tipo');

        if (linkPage === currentPage) {
            if (linkTipo) {
                if (linkTipo === currentTipo) link.classList.add('active');
            } else {
                link.classList.add('active');
            }
        }
    });

    // Session timer
    const sessionMeta = document.querySelector('meta[name="session-timeout"]');
    if (sessionMeta) {
        const timeoutMinutes = parseInt(sessionMeta.getAttribute('content'), 10) || 15;
        let lastActivity = Date.now();
        const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart'];
        const resetActivity = () => { lastActivity = Date.now(); };
        events.forEach(e => document.addEventListener(e, resetActivity, { passive: true }));

        const checkInterval = setInterval(() => {
            const elapsed = (Date.now() - lastActivity) / 60000;
            const remaining = timeoutMinutes - elapsed;

            if (remaining <= 1 && remaining > 0) {
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

    // Close alerts
    document.querySelectorAll('.alert .btn-close').forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.alert')?.remove();
        });
    });
});
