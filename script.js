document.addEventListener('DOMContentLoaded', () => {

    // --- CÓDIGO NOVO PARA HEADER QUE ENCOLHE ---
    const header = document.querySelector('header');
    if (header) { // Verifica se o header existe
        window.onscroll = function() {
            // Se o scroll passar de 80 pixels, adiciona a classe, senão, remove.
            if (document.body.scrollTop > 80 || document.documentElement.scrollTop > 80) {
                header.classList.add('compacto');
            } else {
                header.classList.remove('compacto');
            }
        };
    }


    // --- ANIMAÇÃO DE FADE-IN AO ROLAR A PÁGINA (Seu código original) ---
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


    // --- ANIMAÇÃO DE INCLINAÇÃO (TILT) NOS CARDS (Seu código original) ---
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

    // --- CÓDIGO PARA O MENU HAMBURGER (Seu código original) ---
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