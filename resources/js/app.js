import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// — Auth store —
Alpine.store('auth', {
    isAuthenticated: window.isAuthenticated
});

// — Dashboard store —
Alpine.store('dashboard', {
    devMetricsVisible: false,
    activeModal:      null,

    toggleDevMetrics() {
        this.devMetricsVisible = ! this.devMetricsVisible;
    },
    openModal(name) {
        this.activeModal = name;
        window.dispatchEvent(new CustomEvent('open-modal',{ detail: name }));
    },
    closeModal(name) {
        if (this.activeModal === name) {
            this.activeModal = null;
            window.dispatchEvent(new CustomEvent('close-modal',{ detail: name }));
        }
    }
});

// — Admin component —
function adminDashboard() {
    return {
        devMetricsVisible: Alpine.store('dashboard').devMetricsVisible,
        openModal(name)    { Alpine.store('dashboard').openModal(name) },
        closeModal(name)   { Alpine.store('dashboard').closeModal(name) },

        // for bulk actions
        selectedOrders: [],

        // for the order‑edit modal
        selectedOrder: null,

        // quick status‑patch
        singleMark(id, status) {
            return fetch(`/admin/orders/${id}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status })
            });
        },

        // bulk status‑patch
        markBulk(status) {
            if (! this.selectedOrders.length) return;
            fetch('/admin/orders/bulk-update', {
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ ids:this.selectedOrders, status })
            }).then(() => location.reload());
        },

        // ← new: fetch an order and open modal
        async openOrderEdit(id) {
            const resp = await fetch(`/admin/orders/${id}`, {
                headers: { 'Accept':'application/json' }
            });
            if (!resp.ok) return alert('Failed to load order');
            this.selectedOrder = await resp.json();
            this.openModal('order-edit');
        },

        updateOrder() {
            fetch(`/admin/orders/${this.selectedOrder.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .content
                },
                body: JSON.stringify({
                    status:           this.selectedOrder.status,
                    total:            this.selectedOrder.total,
                    shipping_address: this.selectedOrder.shipping_address,
                    email:            this.selectedOrder.email,
                    phone:            this.selectedOrder.phone,
                })
            })
                .then(() => location.reload());
        }
    }
}
Alpine.data('adminDashboard', adminDashboard);
Alpine.start();

// — global form validator + submitter —
window.validateAndSubmit = function(formEl) {
    const required = formEl.querySelectorAll('[required]');
    for (const field of required) {
        if (! String(field.value).trim()) {
            const label = formEl.querySelector(`label[for="${field.id}"]`)?.innerText || field.name;
            alert(`${label} is required.`);
            field.focus();
            return;
        }
    }
    formEl.submit();
};

document.addEventListener('DOMContentLoaded', () => {
    ['filters-form','admin-filters-form']
        .forEach(formId => {
            const form = document.getElementById(formId);
            if (! form) return;

            form.addEventListener('submit', async e => {
                e.preventDefault();
                const params   = new URLSearchParams(new FormData(form));
                const url      = `/admin?${params.toString()}`;
                const resp     = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' } });
                const html     = await resp.text();
                const doc      = new DOMParser().parseFromString(html, 'text/html');
                const gridId   = 'admin-product-grid';
                const newGrid  = doc.getElementById(gridId);
                if (newGrid) {
                    document.getElementById(gridId).replaceWith(newGrid);
                    window.Alpine.initTree(newGrid);
                    history.pushState(null, '', url);
                }
            });
        });
});
