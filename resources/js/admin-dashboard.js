// ── Core Imports ──
import './bootstrap';
import './globals.js'
import Alpine from 'alpinejs';

// Expose Alpine globally
window.Alpine = Alpine;

// ── CSS Imports ──
import '../css/pages/admin/dashboard.css';

// ── Alpine Stores ──
Alpine.store('auth', {
    isAuthenticated: window.isAuthenticated
});

Alpine.store('dashboard', {
    devMetricsVisible: false,
    activeModal: null,

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

// ── Admin Dashboard Component ──
export default function adminDashboard() {
    return {
        devMetricsVisible: Alpine.store('dashboard').devMetricsVisible,
        selectedOrders: [],
        selectedReview: {},
        selectedOrder: null,

        openModal(name) {
            Alpine.store('dashboard').openModal(name);
        },

        closeModal(name) {
            Alpine.store('dashboard').closeModal(name);
        },

        singleMark(id, status) {
            fetch(`/admin/orders/${id}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status })
            })
                .then(res => {
                    if (!res.ok) throw new Error('Update failed');
                    location.reload();
                })
                .catch(err => {
                    console.error(err);
                    alert('Failed to update order status');
                });
        },

        markBulk(status) {
            if (!this.selectedOrders.length) return;
            fetch('/admin/orders/bulk-update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ ids: this.selectedOrders, status })
            }).then(() => location.reload());
        },

        async openOrderEdit(id) {
            const resp = await fetch(`/admin/orders/${id}`, {
                headers: { Accept: 'application/json' }
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
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    status: this.selectedOrder.status,
                    total: this.selectedOrder.total,
                    shipping_address: this.selectedOrder.shipping_address,
                    email: this.selectedOrder.email,
                    phone: this.selectedOrder.phone
                })
            })
                .then(res => {
                    if (!res.ok) throw new Error('Update failed');
                    this.closeModal('order-edit');
                    location.reload();
                })
                .catch(err => {
                    console.error(err);
                    alert('Failed to save changes');
                });
        },

        async openReviewEdit(id) {
            const resp = await fetch(`/admin/reviews/${id}`, {
                method:      'GET',
                credentials: 'same-origin',
                headers:     { 'Accept': 'application/json' }
            });
            if (!resp.ok) return alert('Failed to load review');
            this.selectedReview = await resp.json();
            this.openModal('review-edit');
        },

        updateReview() {
            fetch(`/admin/reviews/${this.selectedReview.id}`, {
                method:      'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    rating: this.selectedReview.rating,
                    body:   this.selectedReview.body,
                    status: this.selectedReview.status
                })
            })
                .then(r => {
                    if (!r.ok) throw new Error('Save failed');
                    this.closeModal('review-edit');
                    location.reload();
                })
                .catch(err => {
                    console.error(err);
                    alert('Failed to save review');
                });
        },
    };
}

// Register Alpine component
Alpine.data('adminDashboard', adminDashboard);

// Initialize Alpine
Alpine.start();
