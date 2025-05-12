<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\PromoCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Mail\Receipt;
use App\Mail\OrderPlaced;

class CheckoutController extends Controller
{
    /**
     * Show the checkout page.
     */
    public function checkout(Request $request)
    {
        $cart     = $this->getCart($request);
        $items    = $cart->cartItems()->with('product')->get();
        $subtotal = $items->sum(fn($i)=> $i->total);
        $discount = $cart->discount ?? 0;
        $tax      = round(($subtotal - $discount) * config('cart.tax_rate',0),2);
        $total    = round($subtotal - $discount + $tax,2);

        if ($items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error','Your cart is empty.');
        }

        return view('pages.checkout.index', compact('items','subtotal','discount','tax','total'));
    }

    /**
     * AJAX: create a Stripe PaymentIntent and return its client_secret.
     */
    public function paymentIntent(Request $request)
    {
        $cart     = $this->getCart($request);
        $items    = $cart->cartItems()->get();
        if ($items->isEmpty()) {
            return response()->json(['error'=>'Your cart is empty'], 422);
        }

        $subtotal = $items->sum(fn($i)=> $i->price * $i->quantity);
        $discount = $cart->discount ?? 0;
        $tax      = round(($subtotal - $discount) * config('cart.tax_rate',0),2);
        $amount   = intval(($subtotal - $discount + $tax) * 100); // cents

        Stripe::setApiKey(config('services.stripe.secret'));
        $intent = PaymentIntent::create([
            'amount'   => $amount,
            'currency' => 'usd',
            'metadata' => [
                'cart_id'    => $cart->id,
                'discount'   => $discount,
                'user_id'    => auth()->id(),
            ],
        ]);

        return response()->json([
            'clientSecret' => $intent->client_secret,
            'amount'       => $amount/100,
        ]);
    }

    /**
     * Called **after** client confirms payment.
     * Persist the order, line‑items, clear the cart, send emails.
     */
    public function placeOrder(Request $request)
    {
        $data = $request->validate([
            'shipping_address'=>'required|string',
            'email'           =>'required|email',
            'phone'           =>'nullable|string',
            'payment_intent'  =>'required|string',
        ]);

        // Verify with Stripe
        Stripe::setApiKey(config('services.stripe.secret'));
        $pi = PaymentIntent::retrieve($data['payment_intent']);
        if ($pi->status !== 'succeeded') {
            return response()->json(['error'=>'Payment not successful'], 422);
        }

        // Now everything else almost same as before:
        $cart     = $this->getCart($request);
        $items    = $cart->cartItems()->with('product')->get();
        $subtotal = $items->sum(fn($i)=> $i->price * $i->quantity);
        $discount = $cart->discount ?? 0;
        $tax      = round(($subtotal - $discount) * config('cart.tax_rate',0),2);
        $total    = round($subtotal - $discount + $tax,2);
        $code     = $cart->promo_code;

        // Persist Order
        $order = Order::create([
            'user_id'          => auth()->id(),
            'shipping_address' => $data['shipping_address'],
            'email'            => $data['email'],
            'phone'            => $data['phone'],
            'total'            => $total,
            'status'           => 'paid',
            'meta'             => json_encode(['payment_intent'=>$data['payment_intent']]),
        ]);

        // Persist each item
        foreach ($items as $i) {
            $order->orderItems()->create([
                'product_id' => $i->product_id,
                'name'       => $i->product->name,
                'quantity'   => $i->quantity,
                'price'      => $i->price,
                'total'      => $i->price * $i->quantity,
            ]);
        }

        // Promo use count
        if ($code) {
            PromoCode::where('code',$code)->increment('used_count');
        }

        // Clear cart
        CartItem::where('cart_id',$cart->id)->delete();
        $cart->update(['promo_code'=>null,'discount'=>0,'total'=>0]);

        // Emails
        Mail::to($order->email)->queue(new Receipt($order));
        if ($admin = config('mail.admin_address')) {
            Mail::to($admin)->queue(new OrderPlaced($order));
        }

        return response()->json(['success'=>true,'order_id'=>$order->id]);
    }

    /**
     * AJAX: apply a promo code to the cart and return new totals.
     */
    public function applyPromo(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string',
        ]);

        $cart  = $this->getCart($request);
        $items = $cart->cartItems()->get();

        if ($items->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty.'], 422);
        }

        // look up the promo
        $promo = PromoCode::where('code', $data['code'])
            ->where('active', true)
            ->first();

        if (! $promo
            || ($promo->expires_at && $promo->expires_at->isPast())
            || ($promo->max_uses && $promo->used_count >= $promo->max_uses)
        ) {
            return response()->json(['error' => 'That promo code is invalid.'], 422);
        }

        $subtotal = $items->sum(fn($i) => $i->price * $i->quantity);

        // compute discount
        if ($promo->type === 'fixed') {
            $discount = min($promo->discount, $subtotal);
        } else { // percent
            $discount = round($subtotal * $promo->discount / 100, 2);
        }

        // persist onto cart
        $cart->update([
            'promo_code' => $promo->code,
            'discount'   => $discount,
        ]);

        // now recalc tax + total
        ['tax' => $tax, 'total' => $total] = $this->calculateTotals($subtotal, $discount);

        return response()->json(compact('subtotal', 'discount', 'tax', 'total'));
    }

    /**
     * helper: from subtotal & discount, build tax + total
     */
    private function calculateTotals(float $subtotal, float $discount): array
    {
        $taxRate = config('cart.tax_rate', 0);
        $tax     = round(($subtotal - $discount) * $taxRate, 2);
        $total   = round($subtotal - $discount + $tax, 2);

        return compact('tax', 'total');
    }

    /**
     * Thank‑you page.
     */
    public function success()
    {
        return view('pages.checkout.success');
    }

    /**
     * Duplicate of CartController::getCart()
     */
    private function getCart(Request $request): Cart
    {
        if (auth()->check()) {
            return Cart::firstOrCreate(['user_id'=>auth()->id()]);
        }
        $sessionId = $request->session()->get('cart_session_id', Str::uuid());
        $request->session()->put('cart_session_id',$sessionId);
        return Cart::firstOrCreate(['session_id'=>$sessionId]);
    }
}
