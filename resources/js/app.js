import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// — read tax rate from meta tag —
const TAX_RATE = parseFloat(
    document.querySelector('meta[name="tax-rate"]').content
) || 0;

// — Auth store (unchanged) —
Alpine.store('auth', {
    isAuthenticated: window.isAuthenticated
});

// — Cart sidebar store & component —
Alpine.store('cart', {
    open: false,
    toggle() { this.open = ! this.open },
    close()  { this.open = false }
});

function cartSidebar() {
    return {
        loading:  true,
        items:    [],
        subtotal: 0,
        discount: 0,
        tax:      0,
        total:    0,

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            let res = await fetch('/cart', { headers:{ 'Accept':'application/json' } });
            if (!res.ok) {
                this.loading = false;
                return;
            }
            let data = await res.json();

            // 1) coerce string → number
            this.items = data.items.map(i => ({
                ...i,
                price:    parseFloat(i.price),
                quantity: parseInt(i.quantity, 10)
            }));

            // 2) recompute
            this.subtotal = this.items.reduce(
                (sum, i) => sum + i.price * i.quantity,
                0
            );
            this.discount = parseFloat(data.discount || 0);
            this.tax      = +((this.subtotal - this.discount) * TAX_RATE).toFixed(2);
            this.total    = +(this.subtotal - this.discount + this.tax).toFixed(2);

            this.loading = false;
        },

        async remove(itemId) {
            await fetch(`/cart/remove/${itemId}`, {
                method: 'DELETE',
                headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            await this.load();
        }
    }
}
Alpine.data('cartSidebar', cartSidebar);

// — Shared “dashboard” store for modals (unchanged) —
Alpine.store('dashboard', {
    devMetricsVisible: false,
    activeModal:       null,
    toggleDevMetrics() { this.devMetricsVisible = ! this.devMetricsVisible },
    openModal(name)    { this.activeModal = name; window.dispatchEvent(new CustomEvent('open-modal',{detail:name})) },
    closeModal(name)   { if (this.activeModal === name) { this.activeModal = null; window.dispatchEvent(new CustomEvent('close-modal',{detail:name})); } }
});

// — Admin dashboard component (unchanged) —
function adminDashboard() {
    return {
        devMetricsVisible: Alpine.store('dashboard').devMetricsVisible,
        openModal(name)   { Alpine.store('dashboard').openModal(name) },
        closeModal(name)  { Alpine.store('dashboard').closeModal(name) },
        selectedOrders: [],
        selectedOrder:  null,

        singleMark(id,status) {
            return fetch(`/admin/orders/${id}/status`, {
                method:'PATCH',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({status})
            });
        },

        markBulk(status) {
            if (!this.selectedOrders.length) return;
            fetch('/admin/orders/bulk-update', {
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ids:this.selectedOrders,status})
            }).then(() => location.reload());
        },

        async openOrderEdit(id) {
            const resp = await fetch(`/admin/orders/${id}`,{headers:{'Accept':'application/json'}});
            if (!resp.ok) return alert('Failed to load order');
            this.selectedOrder = await resp.json();
            this.openModal('order-edit');
        },

        updateOrder() {
            fetch(`/admin/orders/${this.selectedOrder.id}`,{
                method:'PATCH',
                headers:{
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
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

// — User dashboard component (unchanged) —
function userDashboard() {
    return {
        dateRange: 'all',
        status:    '',
        modalOpen: false,
        selectedOrder: null,

        async openOrder(id) {
            try {
                let res = await fetch(`/order/${id}`, {headers:{'Accept':'application/json'}});
                if (!res.ok) throw new Error(res.statusText);
                this.selectedOrder = await res.json();
                this.modalOpen     = true;
            } catch(e) {
                alert('Failed to load order: ' + e.message);
            }
        },

        closeModal() {
            this.modalOpen     = false;
            this.selectedOrder = null;
        },

        async cancelOrder(id) {
            let res = await fetch(`/order/${id}/cancel`, {
                method:'PATCH',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            if (!res.ok) return alert('Cancel failed');
            this.selectedOrder.status = 'canceled';
        },

        async returnOrder(id) {
            let res = await fetch(`/order/${id}/return`, {
                method:'PATCH',
                headers:{
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

// — Global form validator + AJAX filters for admin products (unchanged) —
window.validateAndSubmit = formEl => {
    const required = formEl.querySelectorAll('[required]');
    for (const field of required) {
        if (!String(field.value).trim()) {
            const label = formEl.querySelector(`label[for="${field.id}"]`)?.innerText || field.name;
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
            const resp   = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
            const html   = await resp.text();
            const doc    = new DOMParser().parseFromString(html,'text/html');
            const grid   = doc.getElementById('admin-product-grid');
            if (grid) {
                document.getElementById('admin-product-grid').replaceWith(grid);
                window.Alpine.initTree(grid);
                history.pushState(null,'',url);
            }
        });
    });
});

// — Checkout page component (with JS‐side math) —
function checkoutPage() {
    return {
        items:    [],
        subtotal: 0,
        discount: 0,
        tax:      0,
        total:    0,
        promoCode: '',
        promoError:'',
        orderError:'',
        loading:   false,
        form:      { shipping_address:'', email:'', phone:'' },

        async init() {
            await this.loadCart();
        },

        async loadCart() {
            let res  = await fetch('/cart',{headers:{'Accept':'application/json'}});
            if (!res.ok) return;
            let json = await res.json();

            // coerce + compute
            this.items    = json.items.map(i => ({
                ...i,
                price:    parseFloat(i.price),
                quantity: parseInt(i.quantity,10)
            }));
            this.subtotal = this.items.reduce((sum,i) => sum + i.price*i.quantity, 0);
            this.discount = parseFloat(json.discount || 0);
            this.tax      = +((this.subtotal - this.discount) * TAX_RATE).toFixed(2);
            this.total    = +(this.subtotal - this.discount + this.tax).toFixed(2);
        },

        async remove(id) {
            await fetch(`/cart/remove/${id}`,{
                method:'DELETE',
                headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            await this.loadCart();
        },

        async applyPromo() {
            this.promoError = '';
            let res = await fetch('/checkout/apply-promo',{
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ code:this.promoCode })
            });
            let json = await res.json();
            if (!res.ok) {
                this.promoError = json.error;
                return;
            }
            // use returned subtotal/discount then recalc tax + total
            this.subtotal = parseFloat(json.subtotal);
            this.discount = parseFloat(json.discount);
            this.tax      = +((this.subtotal - this.discount) * TAX_RATE).toFixed(2);
            this.total    = +(this.subtotal - this.discount + this.tax).toFixed(2);
        },

        async placeOrder() {
            this.orderError = '';
            this.loading    = true;
            let res = await fetch('/checkout/place-order',{
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(this.form)
            });
            let json = await res.json();
            this.loading = false;

            if (!res.ok) {
                this.orderError = json.error;
                return;
            }
            window.location = json.checkout_url;
        }
    }
}
Alpine.data('checkoutPage', checkoutPage);

Alpine.start();
