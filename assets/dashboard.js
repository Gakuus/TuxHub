/* ============================================
   🚀 DASHBOARD JS - OPTIMIZADO Y SEGURO
   ============================================ */

class DashboardManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadUserPreferences();
        this.setupSessionTimer();
        this.setupSidebar();
    }

    /* =======================
       🌙 MODO OSCURO / CLARO
       ======================= */
    setupThemeToggle() {
        const body = document.body;
        const themeToggle = document.getElementById('themeToggle');
        const themeLabel = document.getElementById('themeLabel');

        if (!themeToggle) return;

        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const isDark = body.classList.contains('dark-mode');
            themeLabel.textContent = isDark ? "Modo claro" : "Modo oscuro";
            this.savePreference('theme', isDark ? 'dark' : 'light');
        });
    }

    /* =======================
       🌐 CAMBIO DE IDIOMA
       ======================= */
    setupLanguageToggle() {
        const langToggle = document.getElementById('languageToggle');
        const langLabel = document.getElementById('langLabel');
        const langIcon = document.getElementById('langIcon');
        const welcomeText = document.getElementById('welcomeText');

        if (!langToggle) return;

        const setLanguage = (lang) => {
            const translations = {
                'en': {
                    welcome: 'Welcome',
                    label: 'English',
                    flag: 'https://flagcdn.com/w20/gb.png'
                },
                'es': {
                    welcome: 'Bienvenido',
                    label: 'Español',
                    flag: 'https://flagcdn.com/w20/es.png'
                }
            };

            const t = translations[lang];
            langLabel.textContent = t.label;
            langIcon.src = t.flag;
            langIcon.alt = t.label;
            
            const userName = welcomeText.querySelector('.fw-bold')?.textContent || '';
            const userRole = welcomeText.textContent.match(/\(([^)]+)\)/)?.[1] || '';
            welcomeText.innerHTML = `${t.welcome}, <span class="fw-bold">${userName}</span> (${userRole})`;

            this.savePreference('lang', lang);
        };

        langToggle.addEventListener('click', () => {
            const current = this.getPreference('lang') === 'en' ? 'es' : 'en';
            setLanguage(current);
        });
    }

    /* =======================
       📱 TOGGLE SIDEBAR MÓVIL
       ======================= */
    setupSidebar() {
        const sidebar = document.getElementById('sidebarMenu');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('toggleSidebar');

        if (!sidebar || !toggleBtn) return;

        const toggleSidebar = () => {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        };

        const hideSidebar = () => {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        };

        toggleBtn.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', hideSidebar);

        // Cerrar sidebar al hacer clic en un enlace (móvil)
        sidebar.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && e.target.closest('.nav-link')) {
                hideSidebar();
            }
        });

        // Cerrar sidebar con Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                hideSidebar();
            }
        });
    }

    /* =======================
       ⏰ CONTROL DE SESIÓN
       ======================= */
    setupSessionTimer() {
        const timeout = 15 * 60 * 1000; // 15 minutos
        let timer;

        const resetTimer = () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                this.showSessionExpiredAlert();
            }, timeout);
        };

        const events = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
        events.forEach(event => {
            document.addEventListener(event, resetTimer, { passive: true });
        });

        resetTimer();
    }

    showSessionExpiredAlert() {
        // Crear modal de expiración de sesión
        const modal = document.createElement('div');
        modal.className = 'modal fade show d-block';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Sesión Expirada</h5>
                    </div>
                    <div class="modal-body">
                        <p>Tu sesión ha expirado por inactividad. Serás redirigido al login.</p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        setTimeout(() => {
            window.location.href = "backend/logout.php";
        }, 3000);
    }

    /* =======================
       💾 GESTIÓN DE PREFERENCIAS
       ======================= */
    savePreference(key, value) {
        try {
            localStorage.setItem(`dashboard_${key}`, value);
        } catch (e) {
            console.warn('No se pudo guardar la preferencia:', e);
        }
    }

    getPreference(key) {
        try {
            return localStorage.getItem(`dashboard_${key}`);
        } catch (e) {
            console.warn('No se pudo leer la preferencia:', e);
            return null;
        }
    }

    loadUserPreferences() {
        // Tema
        const savedTheme = this.getPreference('theme');
        const body = document.body;
        const themeLabel = document.getElementById('themeLabel');
        
        if (savedTheme === 'dark') {
            body.classList.add('dark-mode');
            if (themeLabel) themeLabel.textContent = "Modo claro";
        } else if (themeLabel) {
            themeLabel.textContent = "Modo oscuro";
        }

        // Idioma
        const savedLang = this.getPreference('lang') || 'es';
        const langLabel = document.getElementById('langLabel');
        const langIcon = document.getElementById('langIcon');
        
        if (langLabel && langIcon) {
            if (savedLang === 'en') {
                langLabel.textContent = 'English';
                langIcon.src = "https://flagcdn.com/w20/gb.png";
            } else {
                langLabel.textContent = 'Español';
                langIcon.src = "https://flagcdn.com/w20/es.png";
            }
        }
    }

    /* =======================
       🎯 EVENT LISTENERS
       ======================= */
    setupEventListeners() {
        this.setupThemeToggle();
        this.setupLanguageToggle();
        
        // Prevenir clics múltiples rápidos
        this.setupClickDebouncing();
        
        // Manejar errores de recursos
        this.setupErrorHandling();
    }

    setupClickDebouncing() {
        let lastClick = 0;
        document.addEventListener('click', (e) => {
            const now = Date.now();
            if (now - lastClick < 300) {
                e.preventDefault();
                e.stopPropagation();
            }
            lastClick = now;
        }, true);
    }

    setupErrorHandling() {
        // Manejar errores de imágenes
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG') {
                e.target.style.display = 'none';
                console.warn('Error cargando imagen:', e.target.src);
            }
        }, true);

        // Manejar errores globales
        window.addEventListener('error', (e) => {
            console.error('Error global:', e.error);
        });

        // Manejar promesas rechazadas
        window.addEventListener('unhandledrejection', (e) => {
            console.error('Promesa rechazada:', e.reason);
            e.preventDefault();
        });
    }

    /* =======================
       🔧 UTILIDADES
       ======================= */
    static sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    static debounce(func, wait) {
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
}

/* =======================
   🚀 INICIALIZACIÓN
   ======================= */
document.addEventListener('DOMContentLoaded', () => {
    // Verificar que estamos en un entorno seguro
    if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
        console.warn('Dashboard cargado en conexión no segura');
    }

    // Inicializar dashboard
    new DashboardManager();

    // Mostrar estado cargado
    document.body.classList.add('loaded');
});

// Service Worker para cache (opcional)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}