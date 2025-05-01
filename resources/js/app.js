import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// — Auth store (unchanged) —
Alpine.store('auth', {
    isAuthenticated: window.isAuthenticated
});

// — Dashboard store (unchanged) —
Alpine.store('dashboard', {
    devMetricsVisible: false,
    activeModal:      null,

    toggleDevMetrics() {
        this.devMetricsVisible = !this.devMetricsVisible;
    },
    openModal(name) {
        this.activeModal = name;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: name }));
    },
    closeModal(name) {
        if (this.activeModal === name) {
            this.activeModal = null;
            window.dispatchEvent(new CustomEvent('close-modal', { detail: name }));
        }
    }
});

// — Admin Alpine component (unchanged) —
function adminDashboard() {
    return {
        devMetricsVisible: Alpine.store('dashboard').devMetricsVisible,
        openModal(name)    { Alpine.store('dashboard').openModal(name) },
        closeModal(name)   { Alpine.store('dashboard').closeModal(name) },
        openKpi(name)      { this.openModal(name) },
        closeKpi(name)     { this.closeModal(name) },
        selectedOrders: [],
        dateFilter:   'all',
        statusFilter: [],

        matchesDate(range) {
            return this.dateFilter === 'all' || range === this.dateFilter;
        },
        matchesStatus(status) {
            return this.statusFilter.length === 0 || this.statusFilter.includes(status);
        },
        toggleAll(e) {
            const checked = e.target.checked;
            this.selectedOrders = checked
                ? Array.from(document.querySelectorAll('tbody input[type="checkbox"]'))
                    .map(el => el.value)
                : [];
        },
        singleMark(id, status) {
            fetch(`/admin/orders/${id}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status })
            }).then(() => location.reload());
        },
        markBulk(status) {
            if (!this.selectedOrders.length) return;
            fetch('/admin/orders/bulk-update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    ids: this.selectedOrders,
                    status
                })
            }).then(() => location.reload());
        }
    };
}

Alpine.data('adminDashboard', adminDashboard);
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    ['filters-form','admin-filters-form'].forEach(formId => {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', async e => {
            e.preventDefault();

            const params = new URLSearchParams(new FormData(form));
            const url    = formId === 'filters-form'
                ? `/products?${params}`
                : `/admin?${params}`;

            const resp = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await resp.text();

            // parse the returned fragment
            const parser = new DOMParser();
            const doc    = parser.parseFromString(html, 'text/html');
            const newGrid = doc.getElementById(formId === 'filters-form'
                ? 'product-grid'
                : 'admin-product-grid');

            if (newGrid) {
                // replace the old with the new
                const oldGrid = document.getElementById(newGrid.id);
                oldGrid.replaceWith(newGrid);

                // re-hydrate Alpine on the freshly‐injected nodes
                window.Alpine.initTree(newGrid);

                // update the URL
                history.pushState(null, '', url);
            }
        });
    });
});

