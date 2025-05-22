// ── resources/js/globals.js ──

// 1) Alpine + bootstrap, once
import './bootstrap'
import Alpine from 'alpinejs'
window.Alpine = Alpine

// 2) Global stores & constants
Alpine.store('auth', {
    isAuthenticated: window.isAuthenticated
})

const TAX_RATE = parseFloat(
    document.querySelector('meta[name="tax-rate"]').content
) || 0
window.TAX_RATE = TAX_RATE

// 3) Form validator
window.validateAndSubmit = formEl => {
    for (let field of formEl.querySelectorAll('[required]')) {
        if (!String(field.value).trim()) {
            let label = formEl.querySelector(`label[for="${field.id}"]`)?.innerText
                || field.name
            alert(`${label} is required.`)
            field.focus()
            return
        }
    }
    formEl.submit()
}

document.addEventListener('DOMContentLoaded', () => {
    const adminForm = document.getElementById('admin-filters-form')
    if (!adminForm) return

    adminForm.addEventListener('submit', async e => {
        e.preventDefault()

        // build URL
        const params = new URLSearchParams(new FormData(adminForm)).toString()
        const url    = `${adminForm.action}?${params}`

        // fetch only the partial
        const resp = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        if (!resp.ok) {
            console.error('Admin filter fetch failed', resp)
            return
        }

        const html = await resp.text()
        const doc  = new DOMParser().parseFromString(html, 'text/html')

        // swap grid + pagination
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



// 5) Cart store & sidebar component (unchanged)
Alpine.store('cart', {
    open:   false,
    toggle() { this.open = !this.open },
    close()  { this.open = false }
})

Alpine.data('cartSidebar', () => ({
    loading:  false,
    items:    [],
    subtotal: 0,
    discount: 0,
    tax:      0,
    total:    0,

    init() {
        this.$watch('$store.cart.open', open => {
            if (open) this.load()
        })
    },

    async load() {
        this.loading = true
        try {
            let res  = await fetch('/cart',{ headers:{ Accept:'application/json' }})
            if (!res.ok) throw new Error(res.statusText)
            let data = await res.json()

            this.items    = data.items.map(i=>({
                ...i,
                price:    parseFloat(i.price),
                quantity: parseInt(i.quantity,10)
            }))
            this.subtotal = this.items.reduce((s,i)=>s + i.price*i.quantity,0)
            this.discount = parseFloat(data.discount||0)
            this.tax      = parseFloat(((this.subtotal - this.discount)*TAX_RATE).toFixed(2))
            this.total    = parseFloat((this.subtotal - this.discount + this.tax).toFixed(2))
        }
        catch(e) {
            console.error('Cart load failed',e)
        }
        finally {
            this.loading = false
        }
    },

    async remove(id) {
        await fetch(`/cart/remove/${id}`,{
            method:'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        this.load()
    }
}))

// 6) Checkout‐page component (updated for shipping step)
import '../css/pages/checkout/index.css'
import '../css/pages/checkout/success.css'

import { loadStripe } from '@stripe/stripe-js'

Alpine.data('checkoutPage', () => ({
    // ── Stripe / Elements refs ─────────────────────────────
    stripe:       null,
    elements:     null,
    cardNumber:   null,
    cardExpiry:   null,
    cardCvc:      null,
    clientSecret: null,

    // ── State ───────────────────────────────────────────────
    step:            1,      // 1=Info, 2=Review, 3=Payment
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
        country:          '',
    },

    // ── Lifecycle ──────────────────────────────────────────
    init() {
        this.loadCart()
        // when we flip to the payment step, set up Stripe Elements
        this.$watch('step', s => {
            if (s === 3) this.initStripe()
        })
    },

    // ── Step navigation ────────────────────────────────────
    goToStep(n) {
        if (n === 2) {
            // validate step 1 fields
            for (let f of ['name','email','shipping_address','city','state','postal_code','country']) {
                if (! this.form[f]?.trim()) {
                    alert(`Please enter your ${f.replace('_',' ')}`)
                    return
                }
            }
            this.calculateShipping()
        } else {
            this.step = n
        }
    },

    // ── Load cart & compute totals ─────────────────────────
    async loadCart() {
        const res = await fetch('/cart', { headers:{ Accept:'application/json' }})
        if (!res.ok) return
        const j = await res.json()
        this.items      = j.items.map(i=>({ ...i, price:+i.price, quantity:+i.quantity }))
        this.subtotal   = this.items.reduce((s,i)=>s + i.price*i.quantity, 0)
        this.discount   = +j.discount||0
        this.shippingFee= 0
        this.recalc()
    },

    recalc() {
        this.tax   = parseFloat(((this.subtotal - this.discount) * TAX_RATE).toFixed(2))
        this.total = parseFloat((this.subtotal - this.discount + this.tax + this.shippingFee).toFixed(2))
    },

    // ── Step 2: calculate shipping ─────────────────────────
    async calculateShipping() {
        this.shippingError   = ''
        this.shippingLoading = true
        try {
            const res = await fetch('/checkout/shipping-cost', {
                method: 'POST',
                headers: {
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
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

    // ── Step 2: apply promo ─────────────────────────────────
    async applyPromo() {
        this.promoError = ''
        const res = await fetch('/checkout/apply-promo', {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'Accept':'application/json',
                'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
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

    // ── Step 3: initialize Stripe Elements ─────────────────
    async initStripe() {
        if (this.stripe) return
        // ← loadStripe returns a Stripe instance bound to the browser
        this.stripe   = await loadStripe('pk_test_51RHTymPJuM73wkUGVIqqlmMGFBBPHMPNJB9MDqTEZF90F8X5oi0zBfRKr4dFg3RzTx35qn1b8NS4LHYzWNMORuWG00O20z85ko')
        this.elements = this.stripe.elements()

        const style = { base:{ fontSize:'16px', color:'#333' } }
        this.cardNumber = this.elements.create('cardNumber', { style })
        this.cardExpiry = this.elements.create('cardExpiry', { style })
        this.cardCvc    = this.elements.create('cardCvc',    { style })

        this.cardNumber.mount('#card-number')
        this.cardExpiry.mount('#card-expiry')
        this.cardCvc.mount('#card-cvc')
    },

    // ── Step 3: perform the payment ────────────────────────
    async pay() {
        this.orderError = ''
        this.loading    = true
        try {
            // 1) get a clientSecret from your Laravel controller
            let intentRes = await fetch('/checkout/payment-intent', {
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
                    shipping_fee:     this.shippingFee
                })
            })
            let intentJson = await intentRes.json()
            if (!intentRes.ok) throw new Error(intentJson.error || 'Payment initialization failed.')
            const clientSecret = intentJson.clientSecret

            // 2) confirm with Stripe.js & your CardElement
            const { error, paymentIntent } = await this.stripe.confirmCardPayment(clientSecret, {
                payment_method: { card: this.cardNumber }
            })
            if (error) throw error

            // 3) on success, record the order server‐side
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
                    payment_intent: paymentIntent.id                 })
            })
            const orderJson = await orderRes.json()
            if (!orderRes.ok) throw new Error(orderJson.error || 'Order placement failed.')
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

Alpine.start()


