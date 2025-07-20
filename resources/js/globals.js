// resources/js/globals.js

// 1) Alpine + bootstrap
import './bootstrap'
import Alpine from 'alpinejs'
window.Alpine = Alpine

// 2) Auth store
Alpine.store('auth', {
    isAuthenticated: window.isAuthenticated
})

// 3) Tax constant
const TAX_RATE = parseFloat(
    document.querySelector('meta[name="tax-rate"]').content
) || 0
window.TAX_RATE = TAX_RATE

// ————————————————————
// 1) CART STORE
Alpine.store('cart', {
    isOpen: false,
    open()   { this.isOpen = true },
    close()  { this.isOpen = false },
    toggle() { this.isOpen = ! this.isOpen },
})

// ————————————————————
// 2) CART SIDEBAR COMPONENT
Alpine.data('cartSidebar', () => ({
    loading:  false,
    items:    [],
    subtotal: 0,
    discount: 0,
    tax:      0,
    total:    0,

    init() {
        // whenever cart opens, fetch fresh data
        this.$watch('$store.cart.isOpen', open => {
            if (open) this.load()
        })
    },

    async load() {
        this.loading = true
        try {
            const res  = await fetch('/cart', { headers: { Accept: 'application/json' }})
            if (!res.ok) throw new Error(res.statusText)
            const data = await res.json()

            this.items = data.items.map(i => ({
                ...i,
                price:    parseFloat(i.price),
                quantity: parseInt(i.quantity, 10),
            }))

            this.subtotal = this.items.reduce((sum, i) => sum + i.price * i.quantity, 0)
            this.discount = parseFloat(data.discount || 0)
            this.tax      = parseFloat(
                ((this.subtotal - this.discount) * window.TAX_RATE).toFixed(2)
            )
            this.total    = parseFloat(
                (this.subtotal - this.discount + this.tax).toFixed(2)
            )
        }
        catch (e) {
            console.error('Cart load failed:', e)
        }
        finally {
            this.loading = false
        }
    },

    async remove(id) {
        try {
            const res = await fetch(`/cart/remove/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            if (!res.ok) throw new Error(res.statusText)
            this.load()
        }
        catch(e) {
            console.error('Remove failed:', e)
        }
    }
}))

// 6) Admin filters (no changes here)
document.addEventListener('DOMContentLoaded', () => {
    const adminForm = document.getElementById('admin-filters-form')
    if (!adminForm) return

    adminForm.addEventListener('submit', async e => {
        e.preventDefault()
        const params = new URLSearchParams(new FormData(adminForm)).toString()
        const url    = `${adminForm.action}?${params}`

        const resp = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        if (!resp.ok) {
            console.error('Admin filter fetch failed', resp)
            return
        }

        const html   = await resp.text()
        const doc    = new DOMParser().parseFromString(html, 'text/html')
        const newGrid = doc.getElementById('admin-product-grid')
        const newPag  = doc.querySelector('.product-grid__pagination')

        if (newGrid && newPag) {
            document.getElementById('admin-product-grid').replaceWith(newGrid)
            document
                .querySelector('.product-grid__pagination')
                .replaceWith(newPag)
            Alpine.initTree(newGrid)
            history.pushState(null, '', url)
        }
    })
})

// ——————————————
// GLOBAL VALIDATORS

/**
 * Ensure each named field on formObj is non-empty.
 * @param {Object} formObj - key→value map, e.g. Alpine this.form
 * @param {string[]} fields - array of keys to validate
 * @returns {boolean} - true if all filled, false (and alert) otherwise
 */
window.ensureFieldsFilled = function(formObj, fields) {
    for (let name of fields) {
        const val = formObj[name]
        if (val == null || !val.toString().trim()) {
            alert(`Please enter your ${name.replace('_',' ')}`)
            return false
        }
    }
    return true
}

/**
 * Use native HTML5 constraint validation on a <form> element.
 * @param {HTMLFormElement} formEl - the <form> to check
 * @returns {boolean} - true if valid, false (and show bubbles) otherwise
 */
window.ensureFormValid = function(formEl) {
    console.log('[validateAndSubmit] checking HTML5 validity for form:', formEl.id);
    if (!formEl.checkValidity()) {
        console.warn('[validateAndSubmit] HTML5 validation failed for', formEl.id);
        formEl.reportValidity()
        return false
    }
    console.log('[validateAndSubmit] HTML5 validation passed for', formEl.id);
    return true
}

window.validateAndSubmit = async function(formEl) {
    // 1) HTML5 validation
    if (!formEl.checkValidity()) {
        formEl.reportValidity();
        return;
    }

    // 2) Clear old inline errors
    formEl.querySelectorAll('.inline-error').forEach(el => el.remove());

    // 3) Build the FormData (includes your file inputs,
    //    the CSRF token and the hidden _method field)
    const fd = new FormData(formEl);

    try {
        // 4) Force the request to POST so files get parsed
        const res = await fetch(formEl.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept':      'application/json',
            },
            body: fd
        });

        // 5) If Laravel returns validation errors, show them inline
        if (res.status === 422) {
            const { errors } = await res.json();
            console.log('Validation errors:', errors);
            for (let [field, msgs] of Object.entries(errors)) {
                // for array fields like images[], you may need a more
                // sophisticated selector (e.g. `[name="images[]"]`)
                const input = formEl.querySelector(`[name="${field}"]`);
                if (!input) continue;
                const p = document.createElement('p');
                p.classList.add('inline-error','text-red-600','mt-1');
                p.textContent = msgs[0];
                input.insertAdjacentElement('afterend', p);
            }
            return;
        }

        if (!res.ok) throw new Error('Server error');

        // 6) Close the modal and reload on success
        const modalName = formEl.getAttribute('data-modal-name') || 'inventory-create';
        window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));
        location.reload();
    }
    catch (err) {
        console.error('Submission failed:', err);
        alert('Submission failed; see console for details.');
    }
}


// 7) Checkout page component
import '../css/pages/checkout/index.css'
import '../css/pages/checkout/success.css'
import { loadStripe } from '@stripe/stripe-js'

const STRIPE_KEY = import.meta.env.VITE_STRIPE_KEY

Alpine.data('checkoutPage', () => ({
    stripe: null,
    elements: null,
    cardNumber: null,
    cardExpiry: null,
    cardCvc: null,

    step: 1,
    items: [],
    subtotal: 0,
    discount: 0,
    tax: 0,
    shippingFee: 0,
    total: 0,
    promoCode: '',
    promoError: '',
    orderError: '',
    loading: false,
    shippingLoading: false,
    shippingError: '',

    rates: [],
    selectedRate: null,

    form: {
        name: '',
        email: '',
        phone: '',
        shipping_address: '',
        city: '',
        state: '',
        postal_code: '',
        country: ''
    },

    init() {
        this.loadCart()
        this.$watch('step', s => {
            if (s === 3) this.initStripe()
        })
    },

    goToStep(n) {
        if (n === 2) {
            const required = [
                'name', 'email', 'shipping_address', 'city',
                'state', 'postal_code', 'country'
            ];
            if (!ensureFieldsFilled(this.form, required)) return;

            // Uppercase & validate codes
            this.form.country = this.form.country.trim().toUpperCase();
            if (!/^[A-Z]{2}$/.test(this.form.country)) {
                alert('Country must be a 2-letter code, e.g. "US".');
                return;
            }
            this.form.state = this.form.state.trim().toUpperCase();
            if (!/^[A-Z]{2}$/.test(this.form.state)) {
                alert('State must be a 2-letter code, e.g. "NY".');
                return;
            }

            this.fetchRates();
        } else {
            this.step = n;
        }
    },

    async loadCart() {
        this.loading = true
        try {
            const res = await fetch('/cart', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            if (!res.ok) throw new Error(res.statusText)
            const j = await res.json()

            this.items    = j.items.map(i => ({ ...i, price: +i.price, quantity: +i.quantity }))
            this.subtotal = this.items.reduce((s, i) => s + i.price * i.quantity, 0)
            this.discount = +j.discount || 0
            this.tax      = parseFloat(((this.subtotal - this.discount) * window.TAX_RATE).toFixed(2))
            this.total    = parseFloat((this.subtotal - this.discount + this.tax).toFixed(2))

            console.log('[loadCart] items:', this.items)
            console.log('[loadCart] subtotal:', this.subtotal)
            console.log('[loadCart] discount:', this.discount)
            console.log('[loadCart] tax:', this.tax)
            console.log('[loadCart] total:', this.total)

        } catch (e) {
            console.error('Cart load failed:', e)
        } finally {
            this.loading = false
        }
    },

    recalc() {
        this.tax   = parseFloat(((this.subtotal - this.discount) * TAX_RATE).toFixed(2))
        this.total = parseFloat((this.subtotal - this.discount + this.tax + this.shippingFee).toFixed(2))

        console.log('[recalc] subtotal, discount, tax, shippingFee, total →',
            this.subtotal, this.discount, this.tax, this.shippingFee, this.total
        )
    },

    async fetchRates() {
        console.log('[fetchRates] cart items before request →', this.items)
        console.log('[fetchRates] payload(form) →', this.form)

        this.shippingError   = ''
        this.shippingLoading = true

        try {
            const res = await fetch('/checkout/shipping-rates', {
                method:      'POST',
                credentials: 'same-origin',
                headers:     {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(this.form),
            })

            console.log('[fetchRates] HTTP status →', res.status)
            const raw = await res.text()
            console.log('[fetchRates] raw response →', raw)

            const j = JSON.parse(raw)
            if (!res.ok) throw new Error(j.error || 'Failed to fetch shipping options.')

            console.log('[fetchRates] parsed rates array →', j.rates)

            // --- NEW: handle free-shipping (empty array) ---
            if (!j.rates.length) {
                this.shippingFee = 0
                this.recalc()
                this.step = 2
                return
            }

            // existing “pick cheapest” logic
            const best = j.rates.reduce((cheap, r) => {
                const cost      = r.shipmentCost + r.otherCost
                const cheapCost = cheap.shipmentCost + cheap.otherCost
                return cost < cheapCost ? r : cheap
            })

            console.log('[fetchRates] best rate →', best)
            this.shippingFee = best.shipmentCost + best.otherCost
            console.log('[fetchRates] shippingFee set →', this.shippingFee)

            this.recalc()
            this.step = 2

        } catch (err) {
            console.error('[fetchRates] caught error →', err)
            this.shippingError = err.message
        } finally {
            console.log('[fetchRates] done')
            this.shippingLoading = false
        }
    },

    async applyPromo() {
        this.promoError = ''
        if (!this.promoCode.trim()) {
            this.promoError = 'Please enter a promo code.'
            return
        }
        const res = await fetch('/checkout/apply-promo', {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'Accept':'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ code:this.promoCode })
        })
        const j = await res.json()
        if (!res.ok) {
            this.promoError = j.error
            return
        }
        this.subtotal = +j.subtotal
        this.discount = +j.discount
        this.recalc()
    },

    async initStripe() {
        if (this.stripe) return
        // use the env key instead of hard-coding
        this.stripe   = await loadStripe(STRIPE_KEY)
        this.elements = this.stripe.elements()
        const style   = { base:{ fontSize:'16px', color:'#333' } }
        this.cardNumber = this.elements.create('cardNumber', { style })
        this.cardExpiry = this.elements.create('cardExpiry', { style })
        this.cardCvc    = this.elements.create('cardCvc',    { style })
        this.cardNumber.mount('#card-number')
        this.cardExpiry.mount('#card-expiry')
        this.cardCvc.mount('#card-cvc')
    },

    async pay() {
        this.orderError = ''
        this.loading    = true

        try {
            const intentRes = await fetch('/checkout/payment-intent', {
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    shipping_address: this.form.shipping_address,
                    email:            this.form.email,
                    phone:            this.form.phone,
                    shipping_fee:     this.shippingFee,
                    service_code:     this.selectedRate?.serviceCode
                })
            })
            const intentJson = await intentRes.json()
            if (!intentRes.ok) throw new Error(intentJson.error || 'Payment init failed.')

            const { error, paymentIntent } = await this.stripe.confirmCardPayment(
                intentJson.clientSecret,
                { payment_method: { card: this.cardNumber } }
            )
            if (error) throw error

            const orderRes = await fetch('/checkout/place-order', {
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    ...this.form,
                    shipping_fee:   this.shippingFee,
                    service_code:   this.selectedRate?.serviceCode,
                    payment_intent: paymentIntent.id
                })
            })
            const orderJson = await orderRes.json()
            if (!orderRes.ok) throw new Error(orderJson.error || 'Order failed.')
            window.location = orderJson.checkout_url || '/checkout/success'
        }
        catch(err) {
            this.orderError = err.message
        }
        finally {
            this.loading = false
        }
    },

    selectRate(rate) {
        this.selectedRate = rate
        this.shippingFee  = rate.shipmentCost + rate.otherCost
        this.recalc()
    }
}))

// 8) Admin dashboard
import adminDashboard from './admin-dashboard.js'
Alpine.data('adminDashboard', adminDashboard)

// 9) Finally, kick off Alpine
Alpine.start()
