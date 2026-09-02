/*
 * Realce progresivo. Todo el contenido es visible sin JavaScript.
 * Se respeta prefers-reduced-motion.
 */
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

document.documentElement.classList.add('js-ready');

// Contador de stats
document.querySelectorAll('[data-wp-count] .wp-stat__value').forEach((el) => {
    const target = parseFloat(el.dataset.value ?? '0');
    const suffix = el.dataset.suffix ?? '';
    if (Number.isNaN(target)) return;

    if (reduceMotion) {
        el.textContent = `${target}${suffix}`;
        return;
    }

    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            io.disconnect();
            const start = performance.now();
            const dur = 1100;
            const tick = (now) => {
                const p = Math.min(1, (now - start) / dur);
                const eased = 1 - Math.pow(1 - p, 3);
                const value = target % 1 === 0 ? Math.round(target * eased) : (target * eased).toFixed(1);
                el.textContent = `${value}${suffix}`;
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });
    }, { threshold: 0.4 });

    io.observe(el);
});

// Carrusel del hero
document.querySelectorAll('[data-wp-carousel]').forEach((root) => {
    const slides = [...root.querySelectorAll('.wp-hero__slide')];
    if (slides.length < 2) return;

    let index = 0;
    const show = (next) => {
        slides[index].classList.remove('is-active');
        index = (next + slides.length) % slides.length;
        slides[index].classList.add('is-active');
    };

    if (!reduceMotion) {
        setInterval(() => show(index + 1), 6000);
    }
});
