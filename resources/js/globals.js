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
    if (!formEl.checkValidity()) {
        formEl.reportValidity()
        return false
    }
    return true
}

// 7) Checkout page component
import '../css/pages/checkout/index.css'
import '../css/pages/checkout/success.css'
import { loadStripe } from '@stripe/stripe-js'

Alpine.data('checkoutPage', () => ({
    stripe:       null,
    elements:     null,
    cardNumber:   null,
    cardExpiry:   null,
    cardCvc:      null,

    step:            1,
    items:           [],
    subtotal:        0,
    discount:        0,
    tax:             0,
    shippingFee:     0,
    total:           0,
    promoCode:       '',
    promoError:      '',
    orderError:      '',
    loading:         false,
    shippingLoading: false,
    shippingError:   '',

    form: {
        name:             '',
        email:            '',
        phone:            '',
        shipping_address: '',
        city:             '',
        state:            '',
        postal_code:      '',
        country:          ''
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
                'name','email','shipping_address','city',
                'state','postal_code','country'
            ]
            if (!ensureFieldsFilled(this.form, required)) return
            this.calculateShipping()
        } else {
            this.step = n
        }
    },

    async loadCart() {
        const res = await fetch('/cart', {
            headers: { Accept: 'application/json' }
        })
        if (!res.ok) return
        const j = await res.json()
        this.items    = j.items.map(i => ({ ...i, price:+i.price, quantity:+i.quantity }))
        this.subtotal = this.items.reduce((s,i) => s + i.price*i.quantity, 0)
        this.discount = +j.discount || 0
        this.shippingFee = 0
        this.recalc()
    },

    recalc() {
        this.tax   = parseFloat(((this.subtotal - this.discount) * TAX_RATE).toFixed(2))
        this.total = parseFloat((this.subtotal - this.discount + this.tax + this.shippingFee).toFixed(2))
    },

    async calculateShipping() {
        this.shippingError   = ''
        this.shippingLoading = true
        try {
            const res = await fetch('/checkout/shipping-cost', {
                method: 'POST',
                headers: {
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .content
                },
                body: JSON.stringify(this.form)
            })
            const j = await res.json()
            if (!res.ok) throw new Error(j.error || 'Shipping calculation failed.')
            this.shippingFee = +j.shipping
            this.recalc()
            this.step = 2
        } catch(err) {
            this.shippingError = err.message
        } finally {
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
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .content
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
        this.stripe   = await loadStripe('pk_test_…')
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
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .content
                },
                body: JSON.stringify({
                    shipping_address: this.form.shipping_address,
                    email:            this.form.email,
                    phone:            this.form.phone,
                    shipping_fee:     this.shippingFee
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
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .content
                },
                body: JSON.stringify({
                    ...this.form,
                    shipping_fee:   this.shippingFee,
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
    }
}))

// 8) Admin dashboard
import adminDashboard from './admin-dashboard.js'
Alpine.data('adminDashboard', adminDashboard)

// 9) Finally, kick off Alpine
Alpine.start()
