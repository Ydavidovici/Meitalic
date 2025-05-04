<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PromoCode;
use App\Models\Payment;
use App\Mail\OrderPlaced;
use App\Mail\Receipt;

class CheckoutController extends Controller
{
    /**
     * Display the checkout page (cart summary + shipping form).
     */
    public function checkout(Request $request)
    {
        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error','Your cart is empty.');
        }

        // compute subtotal here if you like, or let frontend do it
        return view('checkout.index', compact('cart'));
    }

    /**
     * AJAX endpoint: apply a promo code to the current cart/session.
     */
    public function applyPromo(Request $request)
    {
        $code = strtoupper(trim($request->input('code','')));
        if (! $code) {
            return response()->json(['subtotal'=>0,'discount'=>0,'tax'=>0,'total'=>0]);
        }

        $cart = session('cart', []);
        if (empty($cart)) {
            return response()->json(['error'=>'Your cart is empty.'], 422);
        }

        // calculate subtotal
        $subtotal = collect($cart)->sum(fn($i)=> $i['price'] * $i['quantity']);

        // lookup promo
        $promo = PromoCode::where('code',$code)->where('active',true)->first();
        if (! $promo) {
            return response()->json(['error'=>'That promo code is invalid.'], 422);
        }
        if ($promo->expires_at && now()->gt($promo->expires_at)) {
            return response()->json(['error'=>'That promo has expired.'], 422);
        }
        if ($promo->max_uses && $promo->used_count >= $promo->max_uses) {
            return response()->json(['error'=>'That promo is fully redeemed.'], 422);
        }

        // compute discount
        if ($promo->type === 'percent') {
            $discount = round($subtotal * ($promo->discount/100),2);
        } else {
            $discount = min($promo->discount, $subtotal);
        }

        $tax   = round(($subtotal - $discount) * config('cart.tax_rate',0),2);
        $total = round($subtotal - $discount + $tax,2);

        // store applied promo in session
        session(['promo'=>['code'=>$code,'discount'=>$discount]]);

        return response()->json(compact('subtotal','discount','tax','total'));
    }

    /**
     * Place the order: validate (including promo), create Stripe session, persist Order + Items.
     */
    public function placeOrder(Request $request)
    {
        $data = $request->validate([
            'shipping_address'=>'required|string',
            'email'           =>'required|email',
            'phone'           =>'nullable|string',
        ]);

        $cart = session('cart', []);
        if (empty($cart)) {
            return response()->json(['error'=>'Your cart is empty.'], 422);
        }

        // recalculate subtotal
        $subtotal = collect($cart)->sum(fn($i)=> $i['price'] * $i['quantity']);

        // pull promo from session if set
        $promoSess = session('promo', null);
        $discount  = $promoSess['discount'] ?? 0;
        $code      = $promoSess['code']     ?? null;

        // if they passed a promo code manually, re-validate it here
        if ($code) {
            $promo = PromoCode::where('code',$code)->where('active',true)->first();
            if (! $promo
                || ($promo->expires_at && now()->gt($promo->expires_at))
                || ($promo->max_uses && $promo->used_count >= $promo->max_uses)
            ) {
                return response()->json(['error'=>'Promo code failed, please re‑enter or clear it.'], 422);
            }
        }

        $tax   = round(($subtotal - $discount) * config('cart.tax_rate',0),2);
        $total = round($subtotal - $discount + $tax,2);

        // --- Create Stripe Checkout Session ---
        Stripe::setApiKey(config('services.stripe.secret'));
        $lineItems = [];
        foreach ($cart as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => ['name'=>$item['name']],
                    'unit_amount'  => intval($item['price'] * 100),
                ],
                'quantity'   => $item['quantity'],
            ];
        }
        // add negative line item for discount if any
        if ($discount > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => ['name'=>"Promo: {$code}"],
                    'unit_amount'  => intval(-1 * $discount * 100),
                ],
                'quantity'   => 1,
            ];
        }

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items'           => $lineItems,
            'mode'                 => 'payment',
            'success_url'          => route('checkout.success').'/?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'           => route('checkout.index'),
            'metadata'             => [
                'shipping_address'=> $data['shipping_address'],
                'email'           => $data['email'],
                'phone'           => $data['phone'],
                'promo_code'      => $code,
            ],
        ]);

        // persist an Order with status “pending”
        $order = Order::create(array_merge($data, [
            'total'            => $total,
            'status'           => 'pending',
            'user_id'          => auth()->id(),
            'shipping_address' => $data['shipping_address'],
            'email'            => $data['email'],
            'phone'            => $data['phone'],
            'meta'             => json_encode(['stripe_session'=>$session->id]),
        ]));

        // persist items
        foreach ($cart as $item) {
            $order->orderItems()->create([
                'product_id' => $item['product_id'],
                'name'       => $item['name'],
                'quantity'   => $item['quantity'],
                'price'      => $item['price'],
                'total'      => $item['price'] * $item['quantity'],
            ]);
        }

        // increment promo use‐count
        if (!empty($promo)) {
            $promo->increment('used_count');
        }

        // clear session cart & promo
        session()->forget(['cart','promo']);

        // 1) send **receipt** to customer
        Mail::to($order->email)
            ->queue(new Receipt($order));

        // 2) send **new‑order notification** to admin
        if ($admin = config('mail.admin_address')) {
            Mail::to($admin)
                ->queue(new OrderPlaced($order));
        }

        return response()->json([
            'checkout_session_id' => $session->id,
            'checkout_url'        => $session->url,
            'order_id'            => $order->id,
        ]);
    }

    /**
     * Thank you / success page after Stripe redirect.
     */
    public function success(Request $request)
    {
        return view('checkout.success');
    }
}
