class HomeManager {
    constructor() {
        this.init();
    }

    init() {
        this.initializeSmoothScroll();
        this.initializeScrollAnimations();
        this.initializeCarousel();
        this.initializeHoverEffects();
        this.initializeAccessibility();
        this.initializeAlerts();
        this.initializeAutoRefresh();
    }

    initializeSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.getBoundingClientRect().top + window.pageYOffset - 80,
                        behavior: 'smooth'
                    });
                    history.pushState(null, null, link.getAttribute('href'));
                }
            });
        });
    }

    initializeScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) entry.target.classList.add('fade-in-up', 'visible');
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        document.querySelectorAll('.feature-card, .alert-card, .news-card').forEach(el => {
            el.classList.add('fade-in-up');
            observer.observe(el);
        });
    }

    initializeCarousel() {
        const carousel = document.getElementById('bannerCarousel');
        if (!carousel) return;

        carousel.addEventListener('mouseenter', () => {
            const bs = bootstrap.Carousel.getInstance(carousel);
            if (bs) carousel.setAttribute('data-bs-interval', '0');
        });
        carousel.addEventListener('mouseleave', () => {
            const bs = bootstrap.Carousel.getInstance(carousel);
            if (bs) carousel.setAttribute('data-bs-interval', '4000');
        });
        this.addCarouselProgress();
    }

    initializeHoverEffects() {
        document.querySelectorAll('.news-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                const img = card.querySelector('img');
                if (img) img.style.transform = 'scale(1.05)';
            });
            card.addEventListener('mouseleave', () => {
                const img = card.querySelector('img');
                if (img) img.style.transform = 'scale(1)';
            });
        });

        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', (e) => { e.target.style.transform = 'translateY(-2px)'; });
            btn.addEventListener('mouseleave', (e) => { e.target.style.transform = 'translateY(0)'; });
        });
    }

    initializeAccessibility() {
        document.querySelectorAll('i[class*="bi-"]').forEach(icon => {
            if (!icon.getAttribute('aria-label')) {
                const name = Array.from(icon.classList).find(c => c.startsWith('bi-'))?.replace('bi-', '').replace('-', ' ');
                if (name) icon.setAttribute('aria-label', name);
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') document.body.classList.add('keyboard-navigation');
        });
        document.addEventListener('mousedown', () => document.body.classList.remove('keyboard-navigation'));
    }

    initializeAlerts() {
        document.querySelectorAll('.alert:not(.alert-danger)').forEach(alerta => {
            setTimeout(() => {
                alerta.style.opacity = '0';
                alerta.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alerta.remove(), 500);
            }, 5000);
        });

        document.querySelectorAll('[data-dismiss="alert"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const alerta = btn.closest('.alert');
                if (alerta) alerta.remove();
            });
        });
    }

    initializeAutoRefresh() {
        const statsContainer = document.querySelector('.stats-container');
        if (!statsContainer) return;

        setInterval(async () => {
            try {
                const res = await fetch(window.location.pathname, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                const html = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newStats = doc.querySelector('.stats-container');
                if (newStats) statsContainer.innerHTML = newStats.innerHTML;
            } catch {
                // Silently fail — stats will update on next interval
            }
        }, 30000);
    }

    addCarouselProgress() {
        const carousel = document.getElementById('bannerCarousel');
        if (!carousel) return;
        const indicators = carousel.querySelector('.carousel-indicators');
        if (!indicators) return;

        indicators.querySelectorAll('button').forEach(btn => {
            const bar = document.createElement('div');
            bar.className = 'carousel-progress';
            bar.style.cssText = 'position:absolute;bottom:0;left:0;height:3px;background:rgba(255,255,255,0.5);width:100%;transform:scaleX(0);transform-origin:left;transition:transform 4s linear';
            btn.style.position = 'relative';
            btn.appendChild(bar);
        });

        carousel.addEventListener('slide.bs.carousel', () => {
            document.querySelectorAll('.carousel-progress').forEach(bar => {
                bar.style.transform = 'scaleX(0)';
                bar.style.transition = 'none';
                void bar.offsetWidth;
                bar.style.transition = 'transform 4s linear';
            });
        });

        const firstBar = indicators.querySelector('button .carousel-progress');
        if (firstBar) firstBar.style.transform = 'scaleX(1)';
    }

    async loadMoreNews() {
        ToastSystem.info('Información', 'Función de carga adicional próximamente.');
    }

    filterAnnouncements(category) {
        document.querySelectorAll('.announcement-card').forEach(card => {
            const cat = card.getAttribute('data-category') || '';
            card.style.display = !category || cat === category ? '' : 'none';
        });
    }
}

const HomeUtils = {
    debounce(func, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    },
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });
    },
    isInViewport(el) {
        const r = el.getBoundingClientRect();
        return r.top >= 0 && r.left >= 0 && r.bottom <= (window.innerHeight || document.documentElement.clientHeight) && r.right <= (window.innerWidth || document.documentElement.clientWidth);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    window.homeManager = new HomeManager();
    window.homeUtils = HomeUtils;
    document.body.classList.add('js-loaded');
});

window.addEventListener('error', (e) => {
    ToastSystem.error('Error', e.error?.message || 'Ocurrió un error en la página.');
});

if ('ontouchstart' in window) {
    document.documentElement.classList.add('touch-device');
} else {
    document.documentElement.classList.add('no-touch-device');
}
