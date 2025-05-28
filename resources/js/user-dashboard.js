// resources/js/user-dashboard.js

// Alpine + bootstrap
import './bootstrap';
import Alpine from 'alpinejs';
import '../css/pages/dashboard/index.css';
window.Alpine = Alpine;

// Alpine component for the user dashboard
Alpine.data('userDashboard', () => ({
    // —— Profile Modal State ——
    profileForm: { name: '', email: '' },

    // —— Review Modal State ——
    isReviewModalOpen: false,
    modalTitle: '',
    modalAction: '',
    modalData: {},

    // —— Order Details Modal State ——
    isOrderModalOpen: false,
    selectedOrder: null,

    init() {
        // Pre‑populate profileForm from server‐rendered data
        if (window.profileData) {
            this.profileForm = { ...window.profileData };
        }
    },

    // —— Profile Modal Methods ——
    openProfileModal() {
        // refresh with latest data
        this.profileForm = { ...window.profileData };
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'profile-edit' }));
    },

    // Example saveProfile method with validation
    async saveProfile() {
        // Ensure name and email filled
        if (!ensureFieldsFilled(this.profileForm, ['name', 'email'])) return;

        try {
            const res = await fetch('/dashboard/profile', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(this.profileForm)
            });
            if (!res.ok) throw new Error('Profile update failed');
            location.reload();
        } catch (e) {
            console.error(e);
            alert(e.message);
        }
    },

    // —— Review Modal Methods ——
    openReviewModal({ orderId, itemId, productId, rating = 1, body = '' }) {
        this.modalTitle = itemId
            ? `Review Item #${itemId} (Order #${orderId})`
            : `Review Order #${orderId}`;
        this.modalAction = '/dashboard/reviews';
        this.modalData = { orderId, itemId, productId, rating, body };
        this.isReviewModalOpen = true;
    },

    async submitReview() {
        // Ensure rating and body filled
        if (!ensureFieldsFilled(this.modalData, ['rating', 'body'])) return;

        try {
            const res = await fetch(this.modalAction, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(this.modalData)
            });
            if (!res.ok) throw new Error('Review submission failed');
            location.reload();
        } catch (e) {
            console.error(e);
            alert(e.message);
        }
    },

    closeReviewModal() {
        this.isReviewModalOpen = false;
    },

    // —— Inline Order Details Modal ——
    async openOrderModal(orderId) {
        try {
            let res = await fetch(`/order/${orderId}`, {
                headers: { Accept: 'application/json' }
            });
            if (!res.ok) throw new Error('Fetch failed');
            this.selectedOrder = await res.json();
            this.isOrderModalOpen = true;
        } catch (e) {
            console.error(e);
            alert('Unable to load order details');
        }
    },

    closeOrderModal() {
        this.isOrderModalOpen = false;
    },

    // —— Cancel or Return actions ——
    async cancelOrder(orderId) {
        if (!confirm('Are you sure you want to cancel this order?')) return;
        let res = await fetch(`/order/${orderId}/cancel`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        if (res.ok) location.reload();
        else alert('Cancel failed');
    },

    async returnOrder(orderId) {
        if (!confirm('Request a return for this order?')) return;
        let res = await fetch(`/order/${orderId}/return`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        if (res.ok) location.reload();
        else alert('Return request failed');
    }
}));

// Register Alpine component
Alpine.data('userDashboard', userDashboard);
