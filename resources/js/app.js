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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSite);
} else {
    initSite();
}
