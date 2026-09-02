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

// Menú móvil: cerrar al elegir un enlace, al hacer clic afuera o con Escape.
// Sin JS el <details> abre y cierra igual.
document.querySelectorAll('.wp-navbar__disclosure').forEach((disclosure) => {
    const close = () => disclosure.removeAttribute('open');

    disclosure.querySelectorAll('.wp-navbar__panel a').forEach((link) => {
        link.addEventListener('click', close);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });

    document.addEventListener('click', (e) => {
        if (disclosure.hasAttribute('open') && !disclosure.contains(e.target)) close();
    });
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

// Slider de testimonios: auto-scroll suave. Sin JS se desliza a mano.
document.querySelectorAll('[data-wp-slider]').forEach((track) => {
    if (reduceMotion) return;
    const slides = [...track.children];
    if (slides.length < 2) return;
    let i = 0;
    setInterval(() => {
        i = (i + 1) % slides.length;
        track.scrollTo({ left: slides[i].offsetLeft - track.offsetLeft, behavior: 'smooth' });
    }, 7000);
});

// Video con miniatura: al hacer clic, se reemplaza por el iframe embebido.
// Sin JS el enlace abre el video en su plataforma.
document.querySelectorAll('[data-wp-video-embed]').forEach((el) => {
    el.addEventListener('click', (e) => {
        e.preventDefault();
        const frame = document.createElement('iframe');
        frame.src = `${el.dataset.wpVideoEmbed}?autoplay=1`;
        frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
        frame.allowFullscreen = true;
        el.replaceChildren(frame);
        el.classList.add('is-playing');
    });
});

// Lightbox mínimo para la galería.
document.querySelectorAll('[data-wp-lightbox] a.wp-gallery__link').forEach((link) => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const dialog = document.createElement('div');
        dialog.className = 'wp-lightbox';
        dialog.innerHTML = `<img src="${link.getAttribute('href')}" alt="">`;
        dialog.addEventListener('click', () => dialog.remove());
        document.addEventListener('keydown', function esc(ev) {
            if (ev.key === 'Escape') { dialog.remove(); document.removeEventListener('keydown', esc); }
        });
        document.body.appendChild(dialog);
    });
});
