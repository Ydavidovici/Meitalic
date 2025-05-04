import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// — Auth store (unchanged) —
Alpine.store('auth', {
    isAuthenticated: window.isAuthenticated
});

Alpine.store('cart', {
    open: false,
    toggle() {
        this.open = !this.open;
    },
    close() {
        this.open = false;
    }
});

function cartSidebar() {
    return {
        loading: true,
        items: [],
        subtotal: 0,

        async load() {
            this.loading = true;
            let res = await fetch('/cart', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) { this.loading = false; return; }
            let data = await res.json();
            this.items = data.items;        // [{ id, quantity, price, product: {...} }, …]
            this.subtotal = data.raw_total; // set in your controller below
            this.loading = false;
        },

        async remove(itemId) {
            await fetch(`/cart/remove/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            this.load(); // refresh
        }
    }
}
Alpine.data('cartSidebar', cartSidebar);


// — Shared “dashboard” store for Jetstream <x-modal> events (unchanged) —
Alpine.store('dashboard', {
    devMetricsVisible: false,
    activeModal:       null,

    toggleDevMetrics() {
        this.devMetricsVisible = ! this.devMetricsVisible;
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

// — Admin component (unchanged) —
function adminDashboard() {
    return {
        devMetricsVisible: Alpine.store('dashboard').devMetricsVisible,
        openModal(name)    { Alpine.store('dashboard').openModal(name) },
        closeModal(name)   { Alpine.store('dashboard').closeModal(name) },

        selectedOrders: [],   // bulk IDs
        selectedOrder:  null, // for admin “Edit Order” modal

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

        markBulk(status) {
            if (! this.selectedOrders.length) return;
            fetch('/admin/orders/bulk-update', {
                method: 'POST',
                headers: {
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ ids:this.selectedOrders, status })
            }).then(() => location.reload());
        },

        async openOrderEdit(id) {
            const resp = await fetch(`/admin/orders/${id}`, { headers:{ 'Accept':'application/json' } });
            if (!resp.ok) return alert('Failed to load order');
            this.selectedOrder = await resp.json();
            this.openModal('order-edit');
        },

        updateOrder() {
            fetch(`/admin/orders/${this.selectedOrder.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    status:           this.selectedOrder.status,
                    total:            this.selectedOrder.total,
                    shipping_address: this.selectedOrder.shipping_address,
                    email:            this.selectedOrder.email,
                    phone:            this.selectedOrder.phone,
                })
            })
                .then(res => {
                    if (!res.ok) throw new Error('Update failed');
                    this.closeModal('order-edit');
                    location.reload();
                })
                .catch(e => {
                    console.error(e);
                    alert('Failed to save changes');
                });
        }
    }
}
Alpine.data('adminDashboard', adminDashboard);


// — User Dashboard component —
function userDashboard() {
    return {
        // **NEW**: define these here so x-model + x-show can work
        dateRange:    'all',
        status:       '',

        modalOpen:     false,
        selectedOrder: null,

        async openOrder(id) {
            try {
                let res = await fetch(`/order/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error(res.statusText);
                this.selectedOrder = await res.json();
                this.modalOpen     = true;
            } catch (e) {
                alert('Failed to load order: ' + e.message);
            }
        },

        closeModal() {
            this.modalOpen     = false;
            this.selectedOrder = null;
        },

        async cancelOrder(id) {
            let res = await fetch(`/order/${id}/cancel`, {
                method: 'PATCH',
                headers: {
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            if (!res.ok) return alert('Cancel failed');
            this.selectedOrder.status = 'canceled';
        },

        async returnOrder(id) {
            let res = await fetch(`/order/${id}/return`, {
                method: 'PATCH',
                headers: {
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            if (!res.ok) return alert('Return failed');
            this.selectedOrder.status = 'returned';
        }
    }
}
Alpine.data('userDashboard', userDashboard);

Alpine.start();


// — global form validator + AJAX filters for admin products (unchanged) —
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
    ['filters-form','admin-filters-form'].forEach(formId => {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const params = new URLSearchParams(new FormData(form));
            const url    = `/admin?${params}`;
            const resp   = await fetch(url, { headers:{ 'X-Requested-With':'XMLHttpRequest' } });
            const html   = await resp.text();
            const doc    = new DOMParser().parseFromString(html,'text/html');
            const newGrid = doc.getElementById('admin-product-grid');
            if (newGrid) {
                document.getElementById('admin-product-grid').replaceWith(newGrid);
                window.Alpine.initTree(newGrid);
                history.pushState(null,'',url);
            }
        });
    });
});
