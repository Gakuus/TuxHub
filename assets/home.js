document.addEventListener('DOMContentLoaded', () => {
    // Dismiss alerts
    document.querySelectorAll('.alert-card').forEach(card => {
        const dismiss = card.querySelector('[data-dismiss="alert"]');
        if (dismiss) {
            dismiss.addEventListener('click', () => {
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'translateY(-8px)';
                setTimeout(() => card.remove(), 300);
            });
        }
    });

    // Auto-dismiss success alerts after 5s
    document.querySelectorAll('.alert-glass.alert-success').forEach(alerta => {
        setTimeout(() => {
            alerta.style.transition = 'opacity 0.5s ease';
            alerta.style.opacity = '0';
            setTimeout(() => alerta.remove(), 500);
        }, 5000);
    });

    // Stat cards entrance animation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.stat-card').forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.4s ease ${i * 0.08}s, transform 0.4s ease ${i * 0.08}s`;
        observer.observe(card);
    });
});
