document.addEventListener('DOMContentLoaded', () => {

    // --- NOVA FUNÇÃO DEBOUNCE ---
    // Esta função recebe outra função e um tempo de espera (delay)
    // e retorna uma nova versão da função que só executa após o tempo de espera.
    function debounce(func, delay = 100) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        };
    }

    // --- CÓDIGO DO HEADER MODIFICADO PARA USAR DEBOUNCE ---
    const header = document.querySelector('header');
    if (header) {
        const scrollThreshold = 80;

        // 1. Criamos uma função separada para a lógica de rolagem
        const handleScroll = () => {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > scrollThreshold) {
                header.classList.add('compacto');
            } else {
                header.classList.remove('compacto');
            }
        };

        // 2. Usamos a função debounce para criar uma versão otimizada
        const debouncedScrollHandler = debounce(handleScroll, 10); // A verificação ocorrerá 10ms após o fim da rolagem

        // 3. Adicionamos o listener com a nova função otimizada
        window.addEventListener('scroll', debouncedScrollHandler);
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