/*
 * Public-site behaviour. Plain JavaScript only: the site's Content-Security-Policy
 * forbids inline/eval scripts, so Alpine expressions cannot run.
 */
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ----- Mobile navigation ----- */
function initMobileNav() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const panel = document.getElementById('mobile-nav');
    if (!toggle || !panel) return;

    const setOpen = (open) => {
        panel.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.querySelector('[data-icon-open]')?.toggleAttribute('hidden', open);
        toggle.querySelector('[data-icon-close]')?.toggleAttribute('hidden', !open);
    };
    toggle.addEventListener('click', () => setOpen(!panel.classList.contains('is-open')));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panel.classList.contains('is-open')) {
            setOpen(false);
            toggle.focus();
        }
    });

    panel.querySelectorAll('[data-sub-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const sub = document.getElementById(button.getAttribute('aria-controls'));
            if (!sub) return;
            const open = !sub.classList.contains('is-open');
            sub.classList.toggle('is-open', open);
            button.setAttribute('aria-expanded', String(open));
        });
    });
}

/* ----- Desktop dropdowns (hover and focus work through CSS; this adds tap/click + Escape) ----- */
function initDropdowns() {
    const items = document.querySelectorAll('.has-dropdown');
    const closeAll = (except) => items.forEach((item) => {
        if (item !== except) {
            item.classList.remove('is-open');
            item.querySelector('[data-dropdown-toggle]')?.setAttribute('aria-expanded', 'false');
        }
    });
    items.forEach((item) => {
        const button = item.querySelector('[data-dropdown-toggle]');
        if (!button) return;
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const open = !item.classList.contains('is-open');
            closeAll(item);
            item.classList.toggle('is-open', open);
            button.setAttribute('aria-expanded', String(open));
        });
    });
    document.addEventListener('click', () => closeAll(null));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeAll(null);
    });
}

/* ----- Hero slider ----- */
function initHero() {
    const hero = document.querySelector('[data-hero]');
    if (!hero) return;
    const slides = Array.from(hero.querySelectorAll('[data-hero-slide]'));
    const dots = Array.from(hero.querySelectorAll('[data-hero-dot]'));
    if (slides.length < 2) return;

    let index = 0;
    let timer = null;
    const show = (next) => {
        index = (next + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            const active = i === index;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
            slide.querySelectorAll('a,button').forEach((el) => el.setAttribute('tabindex', active ? '0' : '-1'));
        });
        dots.forEach((dot, i) => dot.setAttribute('aria-current', String(i === index)));
    };
    const stop = () => { if (timer) { clearInterval(timer); timer = null; } };
    const start = () => { if (!reduceMotion && !timer) timer = setInterval(() => show(index + 1), 6500); };

    dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i); stop(); start(); }));
    hero.addEventListener('mouseenter', stop);
    hero.addEventListener('mouseleave', start);
    hero.addEventListener('focusin', stop);
    hero.addEventListener('focusout', start);
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
    show(0);
    start();
}

/* ----- Gallery: category filter and lightbox ----- */
function initGallery() {
    const root = document.querySelector('[data-gallery]');
    if (!root) return;
    const tiles = Array.from(root.querySelectorAll('[data-tile]'));
    const filters = Array.from(root.querySelectorAll('[data-filter]'));

    filters.forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.filter;
            filters.forEach((other) => other.setAttribute('aria-pressed', String(other === button)));
            tiles.forEach((tile) => { tile.hidden = key !== 'all' && tile.dataset.album !== key; });
        });
    });

    const dialog = document.getElementById('lightbox');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    const image = dialog.querySelector('[data-lightbox-image]');
    const caption = dialog.querySelector('[data-lightbox-caption]');
    let list = [];
    let current = 0;

    const render = () => {
        const item = list[current];
        if (!item) return;
        image.src = item.dataset.full;
        image.alt = item.dataset.alt || '';
        caption.textContent = item.dataset.caption || '';
    };
    const open = (item) => {
        list = [...root.querySelectorAll('[data-lightbox-item]')].filter((el) => !el.hidden);
        current = Math.max(0, list.indexOf(item));
        render();
        dialog.showModal();
    };
    const step = (delta) => { current = (current + delta + list.length) % list.length; render(); };

    root.querySelectorAll('[data-lightbox-item]').forEach((item) => item.addEventListener('click', () => open(item)));
    dialog.querySelector('[data-lightbox-close]')?.addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-lightbox-prev]')?.addEventListener('click', () => step(-1));
    dialog.querySelector('[data-lightbox-next]')?.addEventListener('click', () => step(1));
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') step(-1);
        if (event.key === 'ArrowRight') step(1);
    });
}

/* ----- Back to top ----- */
function initBackToTop() {
    const button = document.querySelector('[data-back-to-top]');
    if (!button) return;
    const update = () => button.classList.toggle('is-visible', window.scrollY > 600);
    window.addEventListener('scroll', update, { passive: true });
    button.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }));
    update();
}

export function initSite() {
    initMobileNav();
    initDropdowns();
    initHero();
    initGallery();
    initBackToTop();
}
