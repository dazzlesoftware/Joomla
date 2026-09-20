document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.featured-showcase').forEach(section => {
        section.addEventListener('slid.bs.carousel', event => {
            section.querySelectorAll('[data-bs-slide-to]').forEach(button => {
                const active = Number(button.dataset.bsSlideTo) === event.to;
                button.classList.toggle('active', active);
                button.setAttribute('aria-current', String(active));
            });
        });
    });
    document.querySelectorAll('[data-featured-pause]').forEach(button => {
        const section = button.closest('.carousel');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const setPaused = paused => {
            const carousel = window.bootstrap.Carousel.getOrCreateInstance(section);
            paused ? carousel.pause() : carousel.cycle();
            button.setAttribute('aria-pressed', String(paused));
            button.textContent = paused ? 'Resume slideshow' : 'Pause slideshow';
        };
        button.addEventListener('click', () => setPaused(button.getAttribute('aria-pressed') !== 'true'));
        section.addEventListener('focusin', () => setPaused(true));
        section.addEventListener('mouseenter', () => window.bootstrap.Carousel.getOrCreateInstance(section).pause());
        section.addEventListener('mouseleave', () => {
            if (button.getAttribute('aria-pressed') !== 'true') window.bootstrap.Carousel.getOrCreateInstance(section).cycle();
        });
        setPaused(reducedMotion);
    });
});
