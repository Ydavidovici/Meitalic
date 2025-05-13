<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\PromoCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Http\Controllers\PaymentController;
use App\Mail\ReviewRequestMailable;
use App\Mail\OrderConfirmationMailable;
use App\Mail\AdminOrderNotificationMail;

class CheckoutController extends Controller
{
    /**
     * Show the checkout page.
     */
    public function checkout(Request $request)
    {
        $cart     = $this->getCart($request);
        $items    = $cart->cartItems()->with('product')->get();
        $subtotal = $items->sum(fn($i) => $i->total);
        $discount = $cart->discount ?? 0;
        $tax      = round(($subtotal - $discount) * config('cart.tax_rate', 0), 2);
        $total    = round($subtotal - $discount + $tax, 2);

        if ($items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        return view('pages.checkout.index', compact('items', 'subtotal', 'discount', 'tax', 'total'));
    }

    /**
     * AJAX: create a Stripe PaymentIntent and return its client_secret.
     */
    public function paymentIntent(Request $request)
    {
        $cart  = $this->getCart($request);
        $items = $cart->cartItems()->get();

        if ($items->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty'], 422);
        }

        // all values here are in cents
        $subtotal = $items->sum(fn($i) => $i->price * $i->quantity);
        $discount = $cart->discount ?? 0;
        $tax      = round(($subtotal - $discount) * config('cart.tax_rate', 0), 2);

        // total cents to charge
        $amountCents = (int) round($subtotal - $discount + $tax);

        Stripe::setApiKey(config('services.stripe.secret'));
        $intent = PaymentIntent::create([
            'amount'   => $amountCents,
            'currency' => 'usd',
            'metadata' => [
                'cart_id'  => $cart->id,
                'discount' => $discount,
                'user_id'  => auth()->id(),
            ],
        ]);

        return response()->json([
            'clientSecret' => $intent->client_secret,
            'amount'       => $amountCents / 100, // e.g. 90 cents → 0.90 dollars
        ]);
    }

    /**
     * Called **after** client confirms payment.
     * Persist the order, line‑items, clear the cart, send emails.
     */
    public function placeOrder(Request $request)
    {
        $data = $request->validate([
            'shipping_address' => 'required|string',
            'email'            => 'required|email',
            'phone'            => 'nullable|string',
            'payment_intent'   => 'required|string',
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));

        // ── JSON/AJAX flow: confirm an existing PaymentIntent ──
        if ($request->expectsJson()) {
            $pi = PaymentIntent::retrieve($data['payment_intent']);
            if ($pi->status !== 'succeeded') {
                return response()->json(['error' => 'Payment not successful'], 422);
            }
            $stripePaymentId = $data['payment_intent'];
        }
        // ── Non‑AJAX/full‑flow: create a new PaymentIntent ──
        else {
            $cart     = $this->getCart($request);
            $items    = $cart->cartItems()->get();
            $subtotal = $items->sum(fn($i) => $i->price * $i->quantity);
            $discount = $cart->discount ?? 0;
            $tax      = round(($subtotal - $discount) * config('cart.tax_rate', 0), 2);
            $amountCents = intval(($subtotal - $discount + $tax) * 100);

            $intent = PaymentIntent::create([
                'amount'   => $amountCents,
                'currency' => 'usd',
                'metadata' => [
                    'cart_id'  => $cart->id,
                    'discount' => $discount,
                    'user_id'  => auth()->id(),
                ],
            ]);

            $stripePaymentId = $intent->id;
        }

        // ── Gather/re‑use cart, items & totals ──
        $cart     ??= $this->getCart($request);
        $items    ??= $cart->cartItems()->with('product')->get();
        $subtotal ??= $items->sum(fn($i) => $i->price * $i->quantity);
        $discount ??= $cart->discount ?? 0;
        $tax      ??= round(($subtotal - $discount) * config('cart.tax_rate', 0), 2);
        $total     = round($subtotal - $discount + $tax, 2);
        $code      = $cart->promo_code;
        $amount    = $request->expectsJson()
            ? intval(($subtotal - $discount + $tax) * 100)
            : $amountCents;

        $order = null;

        DB::transaction(function() use (
            $data, $cart, $items, $subtotal,
            $discount, $tax, $total, $code, $amount,
            $stripePaymentId,
            & $order
        ) {
            // 1) Create the order into that outer variable
            $order = Order::create([
                'user_id'          => auth()->id(),
                'shipping_address' => $data['shipping_address'],
                'email'            => $data['email'],
                'phone'            => $data['phone'] ?? null,
                'total'            => $total,
                'status'           => 'paid',
                'meta'             => json_encode(['payment_intent' => $data['payment_intent']]),
            ]);

            // 2) Record the payment
            app(PaymentController::class)->recordPayment(
                $order,
                $stripePaymentId,
                $amount
            );

            // 3) Persist line items
            foreach ($items as $i) {
                $order->orderItems()->create([
                    'product_id' => $i->product_id,
                    'name'       => $i->product->name,
                    'quantity'   => $i->quantity,
                    'price'      => $i->price,
                    'total'      => $i->price * $i->quantity,
                ]);
            }

            // 4) Promo usage
            if ($code) {
                PromoCode::where('code', $code)->increment('used_count');
            }

            // 5) Clear the cart
            CartItem::where('cart_id', $cart->id)->delete();
            $cart->update(['promo_code' => null, 'discount' => 0, 'total' => 0]);

            // 6) Emails
            Mail::to($order->email)
                ->queue(new OrderConfirmationMailable($order));
            if ($admin = config('mail.admin_address')) {
                Mail::to($admin)
                    ->queue(new AdminOrderNotificationMail($order));
            }
            Mail::to($order->email)
                ->later(now()->addDays(7), new ReviewRequestMailable($order));
        });

        // Return JSON for AJAX, or a plain 200 for form‐post
        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'order_id' => $order->id,
            ]);
        }

        return response('', 200);
    }

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

        if ($promo->type === 'fixed') {
            $discount = min($promo->discount, $subtotal);
        } else {
            $discount = round($subtotal * $promo->discount / 100, 2);
        }

        $cart->update([
            'promo_code' => $promo->code,
            'discount'   => $discount,
        ]);

        // calculate tax & total
        ['tax' => $tax, 'total' => $total] = $this->calculateTotals($subtotal, $discount);

        return response()->json(compact('subtotal', 'discount', 'tax', 'total'));
    }

    /**
     * Compute tax and total based on config('cart.tax_rate').
     *
     * @param  float|int  $subtotal  amount before discount
     * @param  float|int  $discount  discount amount
     * @return array{tax: float, total: float}
     */
    private function calculateTotals($subtotal, $discount): array
    {
        $tax   = round(($subtotal - $discount) * config('cart.tax_rate', 0), 2);
        $total = round($subtotal - $discount + $tax, 2);

        return ['tax' => $tax, 'total' => $total];
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
            return Cart::firstOrCreate(['user_id' => auth()->id()]);
        }

        $sessionId = $request->session()->get('cart_session_id', Str::uuid());
        $request->session()->put('cart_session_id', $sessionId);

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }
}
