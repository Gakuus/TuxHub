const horariosManager = {
    exportHorario() {
        const params = new URLSearchParams(window.location.search);
        params.set('page', 'recursos');
        params.delete('page');
        window.location.href = 'backend/exportar_horario.php?' + params.toString();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.class-card');
    const materiaColors = [];

    cards.forEach(card => {
        const materia = card.dataset.materia || card.querySelector('h6')?.textContent || '';
        let hash = 0;
        for (let i = 0; i < materia.length; i++) {
            hash = ((hash << 5) - hash) + materia.charCodeAt(i);
            hash |= 0;
        }
        const colorIndex = Math.abs(hash) % 10 + 1;
        card.classList.add(`materia-${colorIndex}`);
        materiaColors.push(colorIndex);

        card.addEventListener('click', () => {
            const details = card.querySelector('.class-details');
            if (details) {
                const isVisible = details.style.display !== 'none';
                details.style.display = isVisible ? 'none' : 'block';
                details.style.animation = isVisible ? '' : 'fadeIn 0.2s ease';
            }
        });

        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-4px)';
            card.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
            card.style.boxShadow = '';
        });
    });

    document.querySelectorAll('.btn-view-details').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const card = btn.closest('.class-card');
            if (!card) return;
            const materia = card.dataset.materia || '';
            const profesor = card.dataset.profesor || '';
            const salon = card.dataset.salon || '';
            const html = `
                <p><strong>Materia:</strong> ${materia}</p>
                <p><strong>Profesor:</strong> ${profesor}</p>
                <p><strong>Salón:</strong> ${salon}</p>
            `;
            if (typeof ToastSystem !== 'undefined') {
                ToastSystem.info('Detalles de clase', html, 5000);
            }
        });
    });
});
