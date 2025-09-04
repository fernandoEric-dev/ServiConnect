document.addEventListener('DOMContentLoaded', () => {

    // --- ANIMAÇÃO DE FADE-IN AO ROLAR A PÁGINA ---
    const sections = document.querySelectorAll('.fade-in-section');
    if (sections.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.1 }); // Começa a animar quando 10% da seção está visível
        sections.forEach(section => observer.observe(section));
    }


    // --- NOVA ANIMAÇÃO DE INCLINAÇÃO (TILT) NOS CARDS ---
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left; // Posição X do mouse dentro do card
            const y = e.clientY - rect.top;  // Posição Y do mouse dentro do card

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            // Ajuste os valores (7 e -7) para controlar a intensidade da inclinação
            const rotateX = ((y - centerY) / centerY) * -7;
            const rotateY = ((x - centerX) / centerX) * 7;

            card.style.transition = 'transform 0.1s linear'; // Transição mais rápida no movimento
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.03, 1.03, 1.03)`; // Scale para dar mais destaque
        });

        card.addEventListener('mouseleave', () => {
            card.style.transition = 'transform 0.5s ease-out'; // Transição suave ao sair
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
        });
    });

});