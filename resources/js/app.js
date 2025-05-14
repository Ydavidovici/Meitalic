import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// — read tax rate from meta tag —







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
