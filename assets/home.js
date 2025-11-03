// JavaScript para la página de inicio
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
    }

    // Smooth scroll para enlaces internos
    initializeSmoothScroll() {
        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                const targetId = link.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    const offsetTop = targetElement.getBoundingClientRect().top + window.pageYOffset - 80;
                    
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                    
                    // Actualizar URL sin recargar la página
                    history.pushState(null, null, targetId);
                }
            });
        });
    }

    // Animaciones al hacer scroll
    initializeScrollAnimations() {
        const animatedElements = document.querySelectorAll('.feature-card, .alert-card, .news-card');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up', 'visible');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        animatedElements.forEach(element => {
            element.classList.add('fade-in-up');
            observer.observe(element);
        });
    }

    // Mejoras para el carousel
    initializeCarousel() {
        const carousel = document.getElementById('bannerCarousel');
        if (!carousel) return;

        // Pausar carousel al hover
        carousel.addEventListener('mouseenter', () => {
            const bsCarousel = bootstrap.Carousel.getInstance(carousel);
            if (bsCarousel) {
                carousel.setAttribute('data-bs-interval', '0');
            }
        });

        carousel.addEventListener('mouseleave', () => {
            const bsCarousel = bootstrap.Carousel.getInstance(carousel);
            if (bsCarousel) {
                carousel.setAttribute('data-bs-interval', '4000');
            }
        });

        // Agregar indicadores de progreso
        this.addCarouselProgress();
    }

    // Efectos hover mejorados
    initializeHoverEffects() {
        // Efecto para tarjetas de noticias
        const newsCards = document.querySelectorAll('.news-card');
        newsCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                const img = card.querySelector('img');
                if (img) {
                    img.style.transform = 'scale(1.05)';
                }
            });
            
            card.addEventListener('mouseleave', () => {
                const img = card.querySelector('img');
                if (img) {
                    img.style.transform = 'scale(1)';
                }
            });
        });

        // Efecto para botones
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', (e) => {
                e.target.style.transform = 'translateY(-2px)';
            });
            
            button.addEventListener('mouseleave', (e) => {
                e.target.style.transform = 'translateY(0)';
            });
        });
    }

    // Mejoras de accesibilidad
    initializeAccessibility() {
        // Agregar labels a los íconos
        const icons = document.querySelectorAll('i[class*="bi-"]');
        icons.forEach(icon => {
            if (!icon.getAttribute('aria-label')) {
                const className = Array.from(icon.classList)
                    .find(cls => cls.startsWith('bi-'))
                    ?.replace('bi-', '')
                    .replace('-', ' ');
                
                if (className) {
                    icon.setAttribute('aria-label', className);
                }
            }
        });

        // Mejorar focus para teclado
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
        });

        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
    }

    // Indicadores de progreso para el carousel
    addCarouselProgress() {
        const carousel = document.getElementById('bannerCarousel');
        if (!carousel) return;

        const indicators = carousel.querySelector('.carousel-indicators');
        if (!indicators) return;

        // Agregar barras de progreso a los indicadores
        const indicatorButtons = indicators.querySelectorAll('button');
        indicatorButtons.forEach(button => {
            const progressBar = document.createElement('div');
            progressBar.className = 'carousel-progress';
            progressBar.style.cssText = `
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                background: rgba(255,255,255,0.5);
                width: 100%;
                transform: scaleX(0);
                transform-origin: left;
                transition: transform 4s linear;
            `;
            
            button.style.position = 'relative';
            button.appendChild(progressBar);
        });

        // Controlar la animación de progreso
        carousel.addEventListener('slide.bs.carousel', (e) => {
            this.resetProgressBars();
            
            if (e.direction === 'left') {
                const nextIndex = e.to;
                const nextButton = indicatorButtons[nextIndex];
                if (nextButton) {
                    const progressBar = nextButton.querySelector('.carousel-progress');
                    if (progressBar) {
                        progressBar.style.transform = 'scaleX(1)';
                    }
                }
            }
        });

        // Iniciar progreso para el primer slide
        const firstButton = indicatorButtons[0];
        if (firstButton) {
            const progressBar = firstButton.querySelector('.carousel-progress');
            if (progressBar) {
                progressBar.style.transform = 'scaleX(1)';
            }
        }
    }

    resetProgressBars() {
        const progressBars = document.querySelectorAll('.carousel-progress');
        progressBars.forEach(bar => {
            bar.style.transform = 'scaleX(0)';
            bar.style.transition = 'none';
            
            // Forzar reflow
            void bar.offsetWidth;
            
            bar.style.transition = 'transform 4s linear';
        });
    }

    // Método para cargar más noticias (para futura implementación)
    loadMoreNews() {
        // Implementación para cargar más noticias vía AJAX
        console.log('Cargando más noticias...');
    }

    // Método para filtrar avisos (para futura implementación)
    filterAnnouncements(category) {
        // Implementación para filtrar avisos
        console.log('Filtrando avisos por categoría:', category);
    }
}

// Utilidades adicionales
const HomeUtils = {
    // Debounce para optimizar eventos
    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Formatear fechas
    formatDate: (dateString) => {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(dateString).toLocaleDateString('es-ES', options);
    },

    // Verificar si un elemento está en viewport
    isInViewport: (element) => {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    const homeManager = new HomeManager();
    
    // Hacer disponible globalmente para debugging
    window.homeManager = homeManager;
    window.homeUtils = HomeUtils;

    // Agregar clase para estilos de navegación por teclado
    document.body.classList.add('js-loaded');
});

// Manejar errores globales
window.addEventListener('error', (e) => {
    console.error('Error en la página de inicio:', e.error);
});

// Optimizar para dispositivos táctiles
if ('ontouchstart' in window) {
    document.documentElement.classList.add('touch-device');
} else {
    document.documentElement.classList.add('no-touch-device');
}