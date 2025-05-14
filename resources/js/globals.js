// resources/js/globals.js

// ── Core Alpine bootstrapping ──
import './bootstrap';
import Alpine from 'alpinejs';
window.Alpine = Alpine;

// ── Auth store (used by your nav to show Login/Logout) ──
Alpine.store('auth', {
    isAuthenticated: window.isAuthenticated
});

// ── (Your existing form validator) ──
window.validateAndSubmit = formEl => {
    const required = formEl.querySelectorAll('[required]');
    for (const field of required) {
        if (!String(field.value).trim()) {
            const label =
                formEl.querySelector(`label[for="${field.id}"]`)?.innerText ||
                field.name;
            alert(`${label} is required.`);
            field.focus();
            return;
        }
    }
    formEl.submit();
};

document.addEventListener('DOMContentLoaded', () => {
    ['filters-form','admin-filters-form'].forEach(id => {
        let form = document.getElementById(id);
        if (!form) return;

        form.addEventListener('submit', async e => {
            e.preventDefault();

            const params = new URLSearchParams(new FormData(form));
            const url    = `/admin?${params}`;
            const resp   = await fetch(url, {
                headers: { 'X-Requested-With':'XMLHttpRequest' }
            });
            const html = await resp.text();
            const doc  = new DOMParser().parseFromString(html, 'text/html');

            // ← swap the entire section (grid + its modals)
            const section = doc.getElementById('admin-product-section');
            if (section) {
                const old = document.getElementById('admin-product-section');
                old.replaceWith(section);

                // re‑init Alpine on the new bits
                window.Alpine.initTree(section);

                // push the new URL
                history.pushState(null, '', url);
            }
        });
    });
});

// ── Kick off Alpine so stores/directives work immediately ──
Alpine.start();
