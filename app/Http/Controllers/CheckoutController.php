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
use App\Services\UPSService;
use App\Mail\ReviewRequestMailable;
use App\Mail\OrderConfirmationMailable;
use App\Mail\AdminOrderNotificationMail;

class CheckoutController extends Controller
{
    protected UPSService $ups;

    public function __construct(UPSService $ups)
    {
        $this->ups = $ups;
    }

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
        $shipping = session('shipping_fee', 0);
        $total    = round($subtotal - $discount + $tax + $shipping, 2);

        if ($items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        return view('pages.checkout.index', compact(
            'items','subtotal','discount','tax','shipping','total'
        ));
    }

    /**
     * AJAX: calculate UPS shipping for this address + cart.
     */
    public function calculateShipping(Request $request)
    {
        $data = $request->validate([
            'shipping_address' => 'required|string',
        ]);

        $cart  = $this->getCart($request);
        $items = $cart->cartItems()->with('product')->get();
        $subtotal = $items->sum(fn($i) => $i->price * $i->quantity);

        if ($subtotal >= config('shipping.free_threshold')) {
            $fee = 0;
        } else {
            // total weight
            $weight = $items->sum(fn($i) => $i->product->weight * $i->quantity);

            // bounding dims + total volume
            $maxLen = $maxWid = $maxHei = 0;
            $totalVol = 0;
            foreach ($items as $i) {
                $p = $i->product;
                $maxLen = max($maxLen, $p->length);
                $maxWid = max($maxWid, $p->width);
                $maxHei = max($maxHei, $p->height);
                $totalVol += ($p->length * $p->width * $p->height) * $i->quantity;
            }

            // pick smallest box
            $boxes = config('shipping.boxes');
            usort($boxes, fn($a,$b) =>
                ($a['length'] * $a['width'] * $a['height'])
                <=> ($b['length'] * $b['width'] * $b['height'])
            );

            $selected = null;
            foreach ($boxes as $b) {
                $vol = $b['length'] * $b['width'] * $b['height'];
                if (
                    $b['length'] >= $maxLen
                    && $b['width']  >= $maxWid
                    && $b['height'] >= $maxHei
                    && $vol >= $totalVol
                ) {
                    $selected = $b;
                    break;
                }
            }
            if (! $selected) {
                // fallback to largest
                $selected = end($boxes);
            }

            // call UPS
            $shipTo = ['AddressLine' => $data['shipping_address']];
            $fee = $this->ups->getRate(
                $shipTo,
                $weight,
                ['length' => $selected['length'], 'width' => $selected['width'], 'height' => $selected['height']]
            );
        }

        session(['shipping_fee' => $fee]);

        return response()->json(['shipping' => $fee]);
    }

    /**
     * AJAX: create a Stripe PaymentIntent including shipping.
     */
    public function paymentIntent(Request $request)
    {
        $cart     = $this->getCart($request);
        $items    = $cart->cartItems()->get();
        if ($items->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty'], 422);
        }

        $subtotal      = $items->sum(fn($i) => $i->price * $i->quantity);
        $discount      = $cart->discount ?? 0;
        $tax           = round(($subtotal - $discount) * config('cart.tax_rate', 0), 2);
        $shippingFee   = session('shipping_fee', 0);
        $amountCents   = (int) round($subtotal - $discount + $tax + $shippingFee);

        Stripe::setApiKey(config('services.stripe.secret'));
        $intent = PaymentIntent::create([
            'amount'   => $amountCents,
            'currency' => 'usd',
            'metadata' => [
                'cart_id'      => $cart->id,
                'discount'     => $discount,
                'shipping_fee' => $shippingFee,
                'user_id'      => auth()->id(),
            ],
        ]);

        return response()->json([
            'clientSecret' => $intent->client_secret,
            'amount'       => $amountCents / 100,
        ]);
    }

    /**
     * After payment succeeds: record order + line‑items, including shipping_fee.
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

        // confirm existing PaymentIntent
        if ($request->expectsJson()) {
            $pi = PaymentIntent::retrieve($data['payment_intent']);
            if ($pi->status !== 'succeeded') {
                return response()->json(['error' => 'Payment not successful'], 422);
            }
            $stripePaymentId = $data['payment_intent'];
        } else {
            // unlikely in AJAX flow, but stubbed for completeness
            $stripePaymentId = $data['payment_intent'];
        }

        // re‑gather
        $cart     = $this->getCart($request);
        $items    = $cart->cartItems()->with('product')->get();
        $subtotal = $items->sum(fn($i) => $i->price * $i->quantity);
        $discount = $cart->discount ?? 0;
        $tax      = round(($subtotal - $discount) * config('cart.tax_rate', 0), 2);
        $shipping = session('shipping_fee', 0);
        $total    = round($subtotal - $discount + $tax + $shipping, 2);

        DB::transaction(function() use (
            $data, $cart, $items, $subtotal,
            $discount, $tax, $shipping, $total,
            $stripePaymentId,
            & $order
        ) {
            // 1) create order
            $order = Order::create([
                'user_id'          => auth()->id(),
                'shipping_address' => $data['shipping_address'],
                'email'            => $data['email'],
                'phone'            => $data['phone'] ?? null,
                'shipping_fee'     => $shipping,
                'total'            => $total,
                'status'           => 'paid',
                'meta'             => json_encode(['payment_intent' => $stripePaymentId]),
            ]);

            // 2) payment record
            app(PaymentController::class)->recordPayment(
                $order,
                $stripePaymentId,
                intval($total)
            );

            // 3) line‐items
            foreach ($items as $i) {
                $order->orderItems()->create([
                    'product_id' => $i->product_id,
                    'name'       => $i->product->name,
                    'quantity'   => $i->quantity,
                    'price'      => $i->price,
                    'total'      => $i->price * $i->quantity,
                ]);
            }

            // 4) promo usage
            if ($code = $cart->promo_code) {
                PromoCode::where('code', $code)->increment('used_count');
            }

            // 5) clear cart
            CartItem::where('cart_id', $cart->id)->delete();
            $cart->update(['promo_code' => null, 'discount' => 0]);

            // 6) emails
            Mail::to($order->email)->queue(new OrderConfirmationMailable($order));
            if ($admin = config('mail.admin_address')) {
                Mail::to($admin)->queue(new AdminOrderNotificationMail($order));
            }
            Mail::to($order->email)
                ->later(now()->addDays(7), new ReviewRequestMailable($order));
        });

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'order_id' => $order->id]);
        }
        return redirect()->route('checkout.success');
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
