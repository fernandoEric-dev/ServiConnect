document.addEventListener('DOMContentLoaded', () => {
    const panels = Array.from(document.querySelectorAll('[data-panel]'));
    function showPanel() {
        if (!panels.length) return;
        const requested = window.location.hash.slice(1);
        const selected = panels.some(panel => panel.id === requested) ? requested : (document.body.dataset.defaultPanel || panels[0].id);
        panels.forEach(panel => { panel.hidden = panel.id !== selected; });
        document.querySelectorAll('.app-nav a').forEach(link => {
            const active = selected === 'perfil' ? link.hash === '#perfil'
                : selected === 'busca' ? link.getAttribute('href') === 'busca.php'
                : link.getAttribute('href') === 'orcamentos.php';
            if (active) link.setAttribute('aria-current','page'); else link.removeAttribute('aria-current');
        });
        document.dispatchEvent(new CustomEvent('app:panel', {detail:selected}));
    }
    window.addEventListener('hashchange', showPanel); showPanel();
});
