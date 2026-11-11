import './bootstrap';
import { initSite } from './site';

document.addEventListener('submit', (event) => {
    const form = event.target;
    const message = form instanceof HTMLFormElement ? form.dataset.confirm : null;
    if (message && !window.confirm(message)) event.preventDefault();
});

document.addEventListener('click', (event) => {
    const button = event.target instanceof Element ? event.target.closest('[data-print]') : null;
    if (button) window.print();
});

function initHeaderSearch() {
    const search = document.querySelector('[data-header-search]');
    if (!search) return;

    const panel = search.querySelector('.header-search__panel');
    const input = search.querySelector('[data-search-input]');
    if (!panel || !input) return;

    const setOpen = (open) => {
        search.classList.toggle('is-open', open);
        panel.hidden = !open;
        search.querySelectorAll('[data-search-toggle]').forEach((toggle) => {
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close website search' : 'Open website search');
        });
        if (open) window.requestAnimationFrame(() => input.focus());
    };

    document.querySelectorAll('[data-search-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => setOpen(!search.classList.contains('is-open')));
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false);
        }
    });

    document.addEventListener('click', (event) => {
        if (!search.contains(event.target)) setOpen(false);
    });
}

function initLiveSearch() {
    document.querySelectorAll('[data-live-search]').forEach((form) => {
        const input = form.querySelector('input[type="search"]');
        const target = form.querySelector('[data-live-search-results]');
        if (!input || !target) return;

        let timer;
        let requestId = 0;

        const hide = () => {
            target.hidden = true;
            target.replaceChildren();
            input.removeAttribute('aria-activedescendant');
        };

        const render = (html) => {
            const parsed = new DOMParser().parseFromString(html, 'text/html');
            const source = parsed.querySelector(form.dataset.liveSearch === 'portal' ? '.portal-search-results' : '.search-results');
            target.replaceChildren();
            if (!source) {
                hide();
                return;
            }

            [...source.querySelectorAll('a')].slice(0, 8).forEach((link, index) => {
                const suggestion = document.createElement('a');
                suggestion.href = link.href;
                suggestion.className = 'live-search-suggestion';
                suggestion.id = `live-search-option-${index}`;
                suggestion.setAttribute('role', 'option');
                suggestion.innerHTML = link.innerHTML;
                target.append(suggestion);
            });
            target.hidden = target.childElementCount === 0;
        };

        const load = async () => {
            const query = input.value.trim();
            if (query.length < 2) {
                hide();
                return;
            }
            const currentRequest = ++requestId;
            target.hidden = false;
            target.innerHTML = '<div class="live-search-loading">Searching…</div>';
            try {
                const response = await fetch(`${form.action}?q=${encodeURIComponent(query)}`, {
                    headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (currentRequest !== requestId || !response.ok) return;
                render(await response.text());
            } catch {
                if (currentRequest === requestId) hide();
            }
        };

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(load, 220);
        });

        input.addEventListener('keydown', (event) => {
            const options = [...target.querySelectorAll('[role="option"]')];
            if (!options.length) return;
            const active = options.indexOf(document.activeElement);
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                options[(active + (event.key === 'ArrowDown' ? 1 : options.length - 1)) % options.length].focus();
            } else if (event.key === 'Escape') {
                hide();
            }
        });

        form.addEventListener('submit', () => { requestId++; });
        document.addEventListener('click', (event) => {
            if (!form.contains(event.target)) hide();
        });
    });
}

function initRealtimeSubmitSearch() {
    document.querySelectorAll('[data-realtime-submit-search]').forEach((form) => {
        const input = form.querySelector('input[type="search"]');
        if (!input) return;

        let timer;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                form.setAttribute('aria-busy', 'true');
                if (typeof form.requestSubmit === 'function') form.requestSubmit();
                else form.submit();
            }, 280);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { initSite(); initHeaderSearch(); initLiveSearch(); initRealtimeSubmitSearch(); });
} else {
    initSite();
    initHeaderSearch();
    initLiveSearch();
    initRealtimeSubmitSearch();
}
