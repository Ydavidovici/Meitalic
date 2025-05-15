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

// 4) Filters‐form listener
document.addEventListener('DOMContentLoaded', () => {
    for (let id of ['filters-form','admin-filters-form']) {
        let form = document.getElementById(id)
        if (!form) continue

        form.addEventListener('submit', async e => {
            e.preventDefault()
            let params = new URLSearchParams(new FormData(form))
            let url    = `/admin?${params}`
            let resp   = await fetch(url, { headers:{ 'X-Requested-With':'XMLHttpRequest' } })
            let html   = await resp.text()
            let doc    = new DOMParser().parseFromString(html,'text/html')

            let section = doc.getElementById('admin-product-section')
            if (section) {
                document.getElementById('admin-product-section')
                    .replaceWith(section)
                Alpine.initTree(section)
                history.pushState(null,'',url)
            }
        })
    }
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

Alpine.data('checkoutPage', ()=>({
    // steps: 1 = shipping, 2 = review shipping, 3 = payment
    step:          1,
    items:         [],
    subtotal:      0,
    discount:      0,
    tax:           0,
    shippingFee:   0,
    total:         0,
    shippingLoading:false,
    shippingError: '',
    promoCode:     '',
    promoError:    '',
    orderError:    '',
    loading:       false,
    form: {
        shipping_address: '',
        email:            '',
        phone:            ''
    },

    init() {
        this.loadCart()
    },

    // recalculate tax + total including shipping
    recalc() {
        this.tax   = parseFloat(((this.subtotal - this.discount) * TAX_RATE).toFixed(2))
        this.total = parseFloat((this.subtotal - this.discount + this.tax + this.shippingFee).toFixed(2))
    },

    async loadCart() {
        let res = await fetch('/cart',{ headers:{ Accept:'application/json' }})
        if (!res.ok) return
        let j = await res.json()
        this.items    = j.items.map(i=>({
            ...i,
            price:    parseFloat(i.price),
            quantity: parseInt(i.quantity,10)
        }))
        this.subtotal = this.items.reduce((s,i)=>s + i.price*i.quantity,0)
        this.discount = parseFloat(j.discount||0)
        this.shippingFee = 0
        this.recalc()
    },

    // Step 2: call your shipping-cost endpoint
    async calculateShipping() {
        this.shippingError = ''
        this.shippingLoading = true

        try {
            let res = await fetch('/checkout/shipping-cost', {
                method: 'POST',
                headers: {
                    'Content-Type':   'application/json',
                    'Accept':         'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ shipping_address: this.form.shipping_address })
            })

            let j = await res.json()
            if (!res.ok) {
                this.shippingError = j.error || 'Failed to calculate shipping.'
                return
            }

            this.shippingFee = parseFloat(j.shipping)
            this.recalc()
            this.step = 2
        }
        catch(e) {
            console.error(e)
            this.shippingError = 'Shipping calculation failed.'
        }
        finally {
            this.shippingLoading = false
        }
    },

    async applyPromo() {
        this.promoError = ''
        let res = await fetch('/checkout/apply-promo',{
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'Accept':'application/json',
                'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ code: this.promoCode })
        })
        let j = await res.json()
        if (!res.ok) {
            this.promoError = j.error
            return
        }
        this.subtotal    = parseFloat(j.subtotal)
        this.discount    = parseFloat(j.discount)
        this.recalc()
    },

    // Step 3: place order (including shippingFee)
    async placeOrder() {
        this.orderError = ''
        this.loading    = true

        // include shipping_fee in payload
        let payload = {
            ...this.form,
            shipping_fee: this.shippingFee
        }

        let res = await fetch('/checkout/place-order',{
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'Accept':'application/json',
                'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })

        let j = await res.json()
        this.loading = false

        if (!res.ok) {
            this.orderError = j.error
            return
        }

        // redirect on success (or show success UI)
        window.location = j.checkout_url
    }
}))

// 7) Finally, start Alpine once
Alpine.start()
