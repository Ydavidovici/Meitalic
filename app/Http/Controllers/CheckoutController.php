<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
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
use App\Services\ShipStationService;
use App\Services\PackagingService;
use App\Mail\ReviewRequestMailable;
use App\Mail\OrderConfirmationMailable;
use App\Mail\AdminOrderNotificationMail;

class CheckoutController extends Controller
{
    protected ShipStationService $shipStationService;

    public function __construct(ShipStationService $shipStationService)
    {
        $this->shipStationService = $shipStationService;
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
            'items', 'subtotal', 'discount', 'tax', 'shipping', 'total'
        ));
    }

    /**
     * Calculate shipping cost via ShipStation and cache in session.
     */
    public function calculateShipping(Request $request)
    {
        $data = $request->validate([
            'shipping_address' => 'required|string',
            'city'             => 'nullable|string',
            'state'            => 'nullable|string',
            'postal_code'      => 'nullable|string',
            'country'          => 'nullable|string',
        ]);

        $cart     = $this->getCart($request);
        $items    = $cart->cartItems()->with('product')->get();
        $subtotal = $items->sum(fn($i) => $i->price * $i->quantity);

        // free shipping threshold
        $threshold = config('shipping.free_threshold', 0);

        if ($subtotal >= $threshold) {
            $fee = 0;
        } else {
            $weight  = $items->sum(fn($i) => $i->product->weight * $i->quantity);
            $package = PackagingService::selectPackage($items);
            $dims    = $package['dims'];

            $from = [
                'postalCode' => config('shipping.shipper_address.postalCode'),
                'country'    => config('shipping.shipper_address.countryCode'),
                'state'      => config('shipping.shipper_address.stateProvinceCode') ?? null,
                'city'       => config('shipping.shipper_address.city') ?? null,
            ];

            $to = [
                'postalCode' => $data['postal_code'],
                'country'    => $data['country'],
                'state'      => $data['state'] ?? null,
                'city'       => $data['city'] ?? null,
            ];

            $parcel = [
                'length' => $dims['length'],
                'width'  => $dims['width'],
                'height' => $dims['height'],
                'weight' => $weight,
            ];

            $rates = $this->shipStationService->getRates($from, $to, $parcel);

            // pick the cheapest rate
            $fee = collect($rates)
                ->min(fn($r) => $r['shipRate'] ?? PHP_INT_MAX)['shipRate'] ?? 0;
        }

        session(['shipping_fee' => $fee]);

        return response()->json(['shipping' => $fee]);
    }

    /**
     * AJAX: create a Stripe PaymentIntent including shipping.
     */
    public function paymentIntent(Request $request)
    {
        $cart  = $this->getCart($request);
        $items = $cart->cartItems()->get();

        if ($items->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty'], 422);
        }

        $subtotal    = $items->sum(fn($i) => $i->price * $i->quantity);
        $discount    = $cart->discount ?? 0;
        $tax         = round(($subtotal - $discount) * config('cart.tax_rate', 0), 2);
        $shippingFee = session('shipping_fee', 0);

        // amount in cents
        $amountCents = (int) round(
            ($subtotal - $discount + $tax + $shippingFee) * 100
        );

        Stripe::setApiKey(config('services.stripe.secret'));

        Log::channel('api')->info('Stripe ▶️ PaymentIntent.create', [
            'amount_cents' => $amountCents,
            'currency'     => 'usd',
            'cart_id'      => $cart->id,
            'user_id'      => auth()->id(),
            'timestamp'    => now()->toIso8601String(),
        ]);

        $intent = PaymentIntent::create([
            'amount'   => $amountCents,
            'currency' => 'usd',
            'payment_method_types' => ['card'],
            'metadata' => [
                'cart_id'      => $cart->id,
                'discount'     => $discount,
                'shipping_fee' => $shippingFee,
                'user_id'      => auth()->id(),
            ],
        ]);

        Log::channel('api')->info('Stripe ◀️ PaymentIntent.create', [
            'id'           => $intent->id,
            'status'       => $intent->status,
            'client_secret'=> $intent->client_secret,
            'timestamp'    => now()->toIso8601String(),
        ]);

        $displayAmount = ($subtotal - $discount + $tax + $shippingFee) / 100;

        return response()->json([
            'clientSecret' => $intent->client_secret,
            'amount'       => $displayAmount,
        ]);
    }

    /**
     * After payment succeeds: record the order (with shipping_fee) + payment.
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

        Log::channel('api')->info('Stripe ▶️ PaymentIntent.retrieve', [
            'payment_intent_id' => $data['payment_intent'],
            'timestamp'         => now()->toIso8601String(),
        ]);

        $pi = PaymentIntent::retrieve($data['payment_intent']);

        Log::channel('api')->info('Stripe ◀️ PaymentIntent.retrieve', [
            'id'        => $pi->id ?? null,
            'status'    => $pi->status,
            'timestamp' => now()->toIso8601String(),
        ]);

        if ($pi->status !== 'succeeded') {
            return response()->json(['error' => 'Payment not successful'], 422);
        }

        $stripePaymentId = $pi->id ?? $data['payment_intent'];

        // re-gather
        $cart     = $this->getCart($request);
        $items    = $cart->cartItems()->with('product')->get();
        $subtotal = $items->sum(fn($i) => $i->price * $i->quantity);
        $discount = $cart->discount ?? 0;
        $tax      = round(($subtotal - $discount) * config('cart.tax_rate', 0), 2);
        $shipping = session('shipping_fee', 0);
        $total    = round($subtotal - $discount + $tax + $shipping, 2);

        DB::transaction(function() use (
            $data, $cart, $items,
            $subtotal, $discount, $tax,
            $shipping, $total, $stripePaymentId,
            & $order
        ) {
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

            app(PaymentController::class)->recordPayment(
                $order,
                $stripePaymentId,
                (int) round($total * 100)
            );

            foreach ($items as $i) {
                $order->orderItems()->create([
                    'product_id' => $i->product_id,
                    'name'       => $i->product->name,
                    'quantity'   => $i->quantity,
                    'price'      => $i->price,
                    'total'      => $i->price * $i->quantity,
                ]);
            }

            if ($code = $cart->promo_code) {
                PromoCode::where('code', $code)->increment('used_count');
            }
            CartItem::where('cart_id', $cart->id)->delete();
            $cart->update(['promo_code' => null, 'discount' => 0]);

            Mail::to($order->email)->queue(new OrderConfirmationMailable($order));
            if ($admin = config('mail.admin_address')) {
                Mail::to($admin)->queue(new AdminOrderNotificationMail($order));
            }
            Mail::to($order->email)
                ->later(now()->addDays(7), new ReviewRequestMailable($order));
        });

        return $request->expectsJson()
            ? response()->json(['success' => true, 'order_id' => $order->id])
            : redirect()->route('checkout.success');
    }

    /**
     * Thank-you page.
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
