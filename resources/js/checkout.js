// resources/js/checkout.js

// ── Core Imports ──
import './bootstrap';
import Alpine from 'alpinejs';

// Expose Alpine globally
window.Alpine = Alpine;

const TAX_RATE = parseFloat(
    document.querySelector('meta[name="tax-rate"]').content
) || 0;


import '../css/pages/checkout/index.css';
import '../css/pages/checkout/success.css';


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
Alpine.data('cartSidebar', cartSidebar);

// ── Checkout Page Component ──
function checkoutPage() {
    return {
        items: [],
        subtotal: 0,
        discount: 0,
        tax: 0,
        total: 0,
        promoCode: '',
        promoError: '',
        orderError: '',
        loading: false,
        form: { shipping_address: '', email: '', phone: '' },

        async init() {
            await this.loadCart();
        },

        async loadCart() {
            const res = await fetch('/cart', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const json = await res.json();
            this.items = json.items.map(i => ({ ...i, price: parseFloat(i.price), quantity: parseInt(i.quantity, 10) }));
            this.subtotal = this.items.reduce((sum, i) => sum + i.price * i.quantity, 0);
            this.discount = parseFloat(json.discount || 0);
            this.tax = +((this.subtotal - this.discount) * TAX_RATE).toFixed(2);
            this.total = +(this.subtotal - this.discount + this.tax).toFixed(2);
        },

        async applyPromo() {
            this.promoError = '';
            const res = await fetch('/checkout/apply-promo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ code: this.promoCode })
            });
            const json = await res.json();
            if (!res.ok) { this.promoError = json.error; return; }
            this.subtotal = parseFloat(json.subtotal);
            this.discount = parseFloat(json.discount);
            this.tax = +((this.subtotal - this.discount) * TAX_RATE).toFixed(2);
            this.total = +(this.subtotal - this.discount + this.tax).toFixed(2);
        },

        async placeOrder() {
            this.orderError = '';
            this.loading = true;
            const res = await fetch('/checkout/place-order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify(this.form)
            });
            const json = await res.json();
            this.loading = false;
            if (!res.ok) { this.orderError = json.error; return; }
            window.location = json.checkout_url;
        }
    };
}

// Register component
Alpine.data('checkoutPage', checkoutPage);

// Initialize Alpine
Alpine.start();
