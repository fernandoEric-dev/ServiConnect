document.addEventListener('DOMContentLoaded', () => {

    // --- CÓDIGO CORRIGIDO PARA HEADER QUE ENCOLHE (EVITA "FLICKERING") ---
    const header = document.querySelector('header');
    if (header) { // Verifica se o header existe
        let lastScrollTop = 0;
        const scrollThreshold = 80; // Mantive seu valor de 80px

        window.addEventListener('scroll', function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            // Condição para rolar PARA BAIXO
            if (scrollTop > lastScrollTop && scrollTop > scrollThreshold) {
                // Adiciona a classe apenas se não a tiver
                header.classList.add('compacto');
            } 
            // Condição para rolar PARA CIMA
            else if (scrollTop < lastScrollTop && scrollTop < scrollThreshold) {
                // Remove a classe apenas se a tiver
                header.classList.remove('compacto');
            }

            // Atualiza a última posição do scroll
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        });
    }


    // --- ANIMAÇÃO DE FADE-IN AO ROLAR A PÁGINA (Seu código original, sem alterações) ---
    const sections = document.querySelectorAll('.fade-in-section');
    if (sections.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.1 });
        sections.forEach(section => observer.observe(section));
    }


    // --- ANIMAÇÃO DE INCLINAÇÃO (TILT) NOS CARDS (Seu código original, sem alterações) ---
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = ((y - centerY) / centerY) * -7;
            const rotateY = ((x - centerX) / centerX) * 7;
            card.style.transition = 'transform 0.1s linear';
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.03, 1.03, 1.03)`;
        });
        card.addEventListener('mouseleave', () => {
            card.style.transition = 'transform 0.5s ease-out';
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
        });
    });


    // --- CÓDIGO PARA O MENU HAMBURGER (Seu código original, sem alterações) ---
    const primaryNav = document.getElementById('primary-navigation');
    const navToggle = document.querySelector('.mobile-nav-toggle');
    navToggle.addEventListener('click', () => {
        const visibility = primaryNav.getAttribute('data-visible');
        if (visibility === "false" || visibility === null) {
            primaryNav.setAttribute('data-visible', true);
            navToggle.setAttribute('aria-expanded', true);
        } else {
            primaryNav.setAttribute('data-visible', false);
            navToggle.setAttribute('aria-expanded', false);
        }
    });
});