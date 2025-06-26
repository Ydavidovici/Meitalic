// resources/js/admin-dashboard.js

import Alpine from 'alpinejs'
import './globals.js'
import '../css/pages/admin/dashboard.css'

// ── Alpine Stores ──
Alpine.store('auth', {
    isAuthenticated: window.isAuthenticated
})

Alpine.store('dashboard', {
    devMetricsVisible: false,
    activeModal: null,

    toggleDevMetrics() {
        this.devMetricsVisible = !this.devMetricsVisible
    },

    openModal(name) {
        this.activeModal = name
        window.dispatchEvent(new CustomEvent('open-modal', { detail: name }))
    },

    closeModal(name) {
        if (this.activeModal === name) {
            this.activeModal = null
            window.dispatchEvent(new CustomEvent('close-modal', { detail: name }))
        }
    }
})

// ── Admin Dashboard Component ──
export default function adminDashboard() {
    return {

        serverErrors: window.serverErrors || {},

        init() {
            // if there's an image-size error, open the “create product” modal
            if (this.serverErrors.image) {
                this.openModal('inventory-create')
            }
        },

        devMetricsVisible: Alpine.store('dashboard').devMetricsVisible,
        selectedOrders: [],
        selectedReview: {},
        selectedOrder: null,

        openModal(name) {
            Alpine.store('dashboard').openModal(name)
        },

        closeModal(name) {
            Alpine.store('dashboard').closeModal(name)
        },

        toggleAll(event) {
            const checked = event.target.checked
            const boxes = document.querySelectorAll('#orders-grid tbody input[type="checkbox"]')
            this.selectedOrders = checked
                ? Array.from(boxes).map(el => Number(el.value))
                : []
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
                    if (!res.ok) throw new Error('Update failed')
                    location.reload()
                })
                .catch(err => {
                    console.error(err)
                    alert('Failed to update order status')
                })
        },

        markBulk(status) {
            if (!this.selectedOrders.length) return
            fetch('/admin/orders/bulk-update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ ids: this.selectedOrders, status })
            })
                .then(res => {
                    if (!res.ok) throw new Error('Bulk update failed')
                    location.reload()
                })
                .catch(err => {
                    console.error(err)
                    alert('Failed to bulk update orders')
                })
        },

        async openOrderEdit(id) {
            try {
                const resp = await fetch(`/admin/orders/${id}`, {
                    headers: { Accept: 'application/json' }
                })
                if (!resp.ok) throw new Error('Load failed')

                const order = await resp.json()
                this.selectedOrder = {
                    ...order,
                    customer_name: order.user?.name ?? order.customer_name
                }
                this.openModal('order-edit')
            } catch (err) {
                console.error(err)
                alert('Failed to load order')
            }
        },

        updateOrder() {
            const required = [
                'customer_name',
                'status',
                'shipping_fee',
                'total',
                'shipping_address',
            ]
            if (!ensureFieldsFilled(this.selectedOrder, required)) return

            fetch(`/admin/orders/${this.selectedOrder.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    customer_name: this.selectedOrder.customer_name,
                    status: this.selectedOrder.status,
                    shipping_fee: this.selectedOrder.shipping_fee,
                    total: this.selectedOrder.total,
                    shipping_address: this.selectedOrder.shipping_address,
                    email: this.selectedOrder.email,
                    phone: this.selectedOrder.phone
                })
            })
                .then(res => {
                    if (!res.ok) throw new Error('Save failed')
                    this.closeModal('order-edit')
                    location.reload()
                })
                .catch(err => {
                    console.error(err)
                    alert('Failed to save order changes')
                })
        },

        async openReviewEdit(id) {
            try {
                const resp = await fetch(`/admin/reviews/${id}`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' }
                })
                if (!resp.ok) throw new Error('Load failed')

                this.selectedReview = await resp.json()
                this.openModal('review-edit')
            } catch (err) {
                console.error(err)
                alert('Failed to load review')
            }
        },

        updateReview() {
            const required = ['rating', 'body', 'status']
            if (!ensureFieldsFilled(this.selectedReview, required)) return

            fetch(`/admin/reviews/${this.selectedReview.id}`, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    rating: this.selectedReview.rating,
                    body: this.selectedReview.body,
                    status: this.selectedReview.status
                })
            })
                .then(res => {
                    if (!res.ok) throw new Error('Save failed')
                    this.closeModal('review-edit')
                    location.reload()
                })
                .catch(err => {
                    console.error(err)
                    alert('Failed to save review')
                })
        }
    }
}

Alpine.data('adminDashboard', adminDashboard)
