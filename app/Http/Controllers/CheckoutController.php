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
use App\Models\Shipment;
use App\Jobs\SchedulePickup;
use Illuminate\Support\Facades\Config;

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
        $threshold = config('shipping.free_threshold', 55);

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

            $rates = $this->shipStationService->getAllRates($from, $to, $parcel);

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
            'postal_code'      => 'required|string',
            'country'          => 'required|string|size:2',
            'city'             => 'nullable|string',
            'state'            => 'nullable|string|size:2',
            'email'            => 'required|email',
            'phone'            => 'nullable|string',
            'payment_intent'   => 'required|string',
            'carrier'          => 'nullable|string',
            'service_code'     => 'nullable|string',
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

        $stripePaymentId = $pi->id;

        // Gather cart & order details
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
            // Create order record
            $order = Order::create([
                'user_id'          => auth()->id(),
                'shipping_address' => $data['shipping_address'],
                'email'            => $data['email'],
                'phone'            => $data['phone'] ?? null,
                'shipping_fee'     => $shipping,
                'total'            => $total,
                'status'           => 'paid',
                'meta'             => json_encode([
                    'payment_intent' => $stripePaymentId,
                    'carrier'        => $data['carrier']      ?? 'ups',
                    'service_code'   => $data['service_code'] ?? 'ups_ground',
                ]),
            ]);

            // Record payment
            app(PaymentController::class)->recordPayment(
                $order,
                $stripePaymentId,
                (int) round($total * 100)
            );

            // Create order items
            foreach ($items as $i) {
                $order->orderItems()->create([
                    'product_id' => $i->product_id,
                    'name'       => $i->product->name,
                    'quantity'   => $i->quantity,
                    'price'      => $i->price,
                    'total'      => $i->price * $i->quantity,
                ]);
            }

            // Handle promo code
            if ($code = $cart->promo_code) {
                PromoCode::where('code', $code)->increment('used_count');
            }
            CartItem::where('cart_id', $cart->id)->delete();
            $cart->update(['promo_code' => null, 'discount' => 0]);

            // Send emails
            Mail::to($order->email)->queue(new OrderConfirmationMailable($order));
            if ($admin = config('mail.admin_address')) {
                Mail::to($admin)->queue(new AdminOrderNotificationMail($order));
            }
            Mail::to($order->email)
                ->later(now()->addDays(7), new ReviewRequestMailable($order));

            // ── Create ShipStation label ──
            $from = config('shipping.shipper_address');
            $to   = [
                'name'        => $order->email,
                'street1'     => $data['shipping_address'],
                'city'        => $data['city']    ?? null,
                'state'       => $data['state']   ?? null,
                'postalCode'  => $data['postal_code'],
                'country'     => strtoupper($data['country']),
                'phone'       => $data['phone']   ?? null,
                'email'       => $data['email'],
                'residential' => true,
            ];
            $pack   = PackagingService::selectPackage($items);
            $parcel = [
                'length' => $pack['dims']['length'],
                'width'  => $pack['dims']['width'],
                'height' => $pack['dims']['height'],
                'weight' => $items->sum(fn($i) => $i->product->weight * $i->quantity),
            ];
            $meta    = json_decode($order->meta, true);
            $carrier = $meta['carrier'];
            $service = $meta['service_code'];

            $orderItems = $order->orderItems->map(fn($i) => [
                'lineItemKey' => Str::uuid(),
                'sku'         => $i->product->sku ?? null,
                'name'        => $i->name,
                'quantity'    => $i->quantity,
                'unitPrice'   => $i->price,
                'weight'      => ['value' => $i->product->weight, 'units' => 'pounds'],
            ])->all();

            // ⇨ CREATE THE SHIPMENT IN SHIPSTATION:
            $shipmentResp = $this->shipStationService->createShipment(
                $from, $to, $parcel,
                $carrier, $service,
                (string)$order->id,
                $orderItems
            );
            $shipStationOrderId = $shipmentResp['orderId'];

            $labelResp = $this->shipStationService->createLabel(
                $from, $to, $parcel,
                $carrier, $service,
                (string)$order->id,
                $orderItems
            );


            Shipment::create([
                'order_id'        => $order->id,
                'label_id'        => $labelResp['labelId'],
                'tracking_number' => $labelResp['trackingNumber'] ?? null,
                'carrier_code'    => $carrier,
                'service_code'    => $service,
                'shipment_cost'   => $labelResp['shipmentCost'] ?? 0,
                'other_cost'      => $labelResp['otherCost']    ?? 0,
                'label_url'       => $labelResp['labelData']    ?? null,
            ]);

            // ── Dispatch end-of-day pickup job ──
            // SchedulePickup::dispatch()
             //   ->delay(now()->endOfDay()->addSeconds(5));
        });

        return $request->expectsJson()
            ? response()->json(['success' => true, 'order_id' => $order->id])
            : redirect()->route('checkout.success');
    }

/**
     * Return all available shipping rates for the given address + cart.
     */
    public function shippingRates(Request $request)
    {

        // 1) Validate & normalize input
        $data = $request->validate([
            'city'        => ['nullable','string'],
            'state'       => ['nullable','string','size:2','regex:/^[A-Z]{2}$/'],
            'postal_code' => ['required','string'],
            'country'     => ['required','string','size:2','regex:/^[A-Z]{2}$/'],
        ]);
        $data['country'] = strtoupper($data['country']);
        if (isset($data['state'])) {
            $data['state'] = strtoupper($data['state']);
        }

        // 2) Ship-from & Ship-to
        $shipper = config('shipping.shipper_address');
        $from = [
            'postalCode' => $shipper['postalCode'],
            'country'    => $shipper['country'],
            'state'      => $shipper['state'] ?? null,
            'city'       => $shipper['city']  ?? null,
        ];
        $to = [
            'postalCode' => $data['postal_code'],
            'country'    => $data['country'],
            'state'      => $data['state'] ?? null,
            'city'       => $data['city']  ?? null,
        ];

        // 3) Load cart, compute subtotal & weight
        $cart     = $this->getCart($request);
        $items    = $cart->cartItems()->with('product')->get();
        $subtotal = $items->sum(fn($i) => $i->price * $i->quantity);
        $weight   = $items->sum(fn($i) => $i->product->weight * $i->quantity);

        // 4) Free-shipping check
        $threshold = config('shipping.free_threshold', 0);
        \Log::info('shippingRates subtotal vs threshold', compact('subtotal','threshold'));
        if ($subtotal >= $threshold) {
            session(['shipping_fee' => 0]);
            return response()->json(['rates' => []]);
        }

        // 5) Packaging decision
        $pack = app(PackagingService::class)->selectPackage($items);
        \Log::info('shippingRates packaging result', $pack);

        $totalFee = 0;
        $allRates = [];

        if ($pack['type'] === 'multi') {
            // split weight across $pack['count'] boxes
            $remaining = $weight;
            for ($i = 0; $i < $pack['count']; $i++) {
                $thisWeight = min($pack['maxWeight'], $remaining);
                $parcel = [
                    'length' => $pack['dims']['length'],
                    'width'  => $pack['dims']['width'],
                    'height' => $pack['dims']['height'],
                    'weight' => $thisWeight,
                ];
                \Log::info("shippingRates parcel #{$i}", $parcel);

                $rates = $this->shipStationService->getAllRates($from, $to, $parcel, 'ups');
                \Log::info("ShipStation rates box #{$i}", ['rates' => $rates]);

                // cheapest cost for this box
                $best = collect($rates)
                    ->map(fn($r)=> $r['shipmentCost'] + $r['otherCost'])
                    ->min();
                $totalFee += $best;
                $allRates = array_merge($allRates, $rates);

                $remaining -= $thisWeight;
            }
        } else {
            // single envelope or box
            $dims   = $pack['dims'];
            $parcel = [
                'length' => $dims['length'],
                'width'  => $dims['width'],
                'height' => $dims['height'],
                'weight' => $weight,
            ];
            \Log::info('shippingRates parcel dimensions', $parcel);

            $rates = $this->shipStationService->getAllRates($from, $to, $parcel, 'ups');
            \Log::info('ShipStation returned rates', ['rates' => $rates]);

            // cheapest cost
            $totalFee = collect($rates)
                ->map(fn($r)=> $r['shipmentCost'] + $r['otherCost'])
                ->min();
            $allRates = $rates;
        }

        // 6) Cache & return
        session(['shipping_fee' => $totalFee]);
        $currency = $data['country'] === 'IL' ? 'ILS' : 'USD';
        return response()->json(['rates' => $allRates,    'currency' => $currency,]);
    }

    public function applyPromo(Request $request)
    {
        // 1) require a code
        $request->validate(['code' => 'required|string']);

        // 2) get the cart & items
        $cart = $this->getCart($request);
        $items = $cart->cartItems()->with('product')->get();

        if ($items->isEmpty()) {
            return response()
                ->json(['error' => 'Your cart is empty.'], 422);
        }

        // 3) find an active, non-expired promo
        $promo = PromoCode::where('code', $request->code)
            ->where('active', true)
            ->first();

        if (! $promo || ($promo->expires_at && now()->greaterThan($promo->expires_at))) {
            return response()
                ->json(['error' => 'That promo code is invalid.'], 422);
        }

        // 4) compute discount
        $subtotal = $items->sum(fn($i)=> $i->price * $i->quantity);
        $discount = $promo->type === 'fixed'
            ? $promo->discount
            : ($subtotal * ($promo->discount / 100));

        // 5) save to cart
        $cart->promo_code = $promo->code;
        $cart->discount   = $discount;
        $cart->save();

        // 6) recalc tax & total
        $tax   = round(($subtotal - $discount) * Config::get('cart.tax_rate', 0), 2);
        $total = round($subtotal - $discount + $tax, 2);

        return response()->json([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax'      => $tax,
            'total'    => $total,
        ]);
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
