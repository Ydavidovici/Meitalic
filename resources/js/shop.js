// resources/js/shop.js

// ── Core Imports ──
import './bootstrap';
import Alpine from 'alpinejs';

// Expose Alpine globally
window.Alpine = Alpine;

// ── Alpine Stores ──
// Cart store & sidebar
Alpine.store('cart', {
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; }
});

// ── Cart Sidebar Component ──
function cartSidebar() {
    return {
        loading: true,
        items: [],
        subtotal: 0,
        discount: 0,
        tax: 0,
        total: 0,

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            const res = await fetch('/cart', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) { this.loading = false; return; }
            const data = await res.json();
            this.items = data.items.map(i => ({
                ...i,
                price: parseFloat(i.price),
                quantity: parseInt(i.quantity, 10)
            }));
            this.subtotal = this.items.reduce((sum, i) => sum + i.price * i.quantity, 0);
            this.discount = parseFloat(data.discount || 0);
            this.tax = +((this.subtotal - this.discount) * TAX_RATE).toFixed(2);
            this.total = +(this.subtotal - this.discount + this.tax).toFixed(2);
            this.loading = false;
        },

        async remove(itemId) {
            await fetch(`/cart/remove/${itemId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            await this.load();
        }
    };
}
// Register component
Alpine.data('cartSidebar', cartSidebar);

// Initialize Alpine
Alpine.start();
