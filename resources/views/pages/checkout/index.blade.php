@extends('layouts.app')
@section('title','Checkout')

@section('content')
    <div x-data="checkoutPage()" x-init="init()" class="container mx-auto py-12 px-4 space-y-8">

        {{-- Cart summary + promo code (unchanged)… --}}

        {{-- Shipping & Contact --}}
        <div class="bg-white rounded shadow p-6 space-y-4" x-show="items.length">
            <h3 class="text-xl font-bold">Shipping & Contact</h3>
            <textarea x-model="form.shipping_address" required
                      class="border rounded p-2 w-full" placeholder="Address"></textarea>
            <input x-model="form.email" type="email" required
                   class="border rounded px-3 py-2 w-full" placeholder="Email">
            <input x-model="form.phone" type="text"
                   class="border rounded px-3 py-2 w-full" placeholder="Phone (optional)">
        </div>

        {{-- Stripe Element --}}
        <div class="bg-white rounded shadow p-6" x-show="items.length">
            <h3 class="text-xl font-bold">Payment</h3>
            <div id="card-element" class="border rounded p-2"></div>
            <p id="card-errors" class="text-red-600 mt-2"></p>
            <button @click="pay()" :disabled="loading"
                    class="mt-4 btn-primary w-full">
                <span x-text="loading ? 'Processing…' : 'Pay $' + total.toFixed(2)"></span>
            </button>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        function checkoutPage() {
            return {
                items:[], subtotal:0, discount:0, tax:0, total:0,
                promoCode:'', promoError:'', orderError:'',
                loading:false,
                form:{shipping_address:'',email:'',phone:''},
                stripe:null, card:null, clientSecret:null,

                async init(){
                    await this.loadCart();

                    // init Stripe Elements
                    this.stripe = Stripe('{{ config('services.stripe.key') }}');
                    let elements = this.stripe.elements();
                    this.card = elements.create('card');
                    this.card.mount('#card-element');
                    this.card.on('change', e=>{
                        document.getElementById('card-errors').textContent = e.error?.message||'';
                    });

                    // fetch PaymentIntent
                    let resp = await fetch("{{ route('checkout.paymentIntent') }}",{
                        method:'POST',headers: {
                            'Content-Type':'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        }
                    });
                    let json = await resp.json();
                    if (!resp.ok) {
                        alert(json.error||'Failed to start payment');
                        return;
                    }
                    this.clientSecret = json.clientSecret;
                },

                async loadCart(){
                    let res  = await fetch('/cart',{headers:{'Accept':'application/json'}});
                    let data = await res.json();
                    this.items    = data.items.map(i=>({ ...i,
                        price:parseFloat(i.price),
                        quantity:parseInt(i.quantity,10)
                    }));
                    this.subtotal = this.items.reduce((s,i)=>s+i.price*i.quantity,0);
                    this.discount = parseFloat(data.discount||0);
                    this.tax      = +((this.subtotal-this.discount)*{{ config('cart.tax_rate',0) }}).toFixed(2);
                    this.total    = +(this.subtotal-this.discount+this.tax).toFixed(2);
                },

                async pay(){
                    this.loading = true;
                    const { paymentIntent, error } = await this.stripe.confirmCardPayment(
                        this.clientSecret, {
                            payment_method: {
                                card: this.card,
                                billing_details: {
                                    name: this.form.name,
                                    email: this.form.email,
                                    phone: this.form.phone
                                }
                            }
                        }
                    );
                    if (error) {
                        document.getElementById('card-errors').textContent = error.message;
                        this.loading = false;
                        return;
                    }
                    // Persist order on your backend
                    let res = await fetch('/checkout/place-order',{
                        method:'POST',
                        headers:{
                            'Content-Type':'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify({
                            ...this.form,
                            payment_intent: paymentIntent.id
                        })
                    });
                    let json = await res.json();
                    this.loading = false;
                    if (!res.ok) {
                        this.orderError = json.error;
                        return;
                    }
                    // success view
                    window.location = "{{ route('checkout.success') }}";
                }
            }
        }
        document.addEventListener('alpine:init',()=> Alpine.data('checkoutPage', checkoutPage));
    </script>
@endsection
