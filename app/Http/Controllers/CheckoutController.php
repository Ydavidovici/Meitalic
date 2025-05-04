<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

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
     * Display the checkout page (cart summary + shipping form).
     */
    public function checkout(Request $request)
    {
        $cart    = $this->getCart($request);
        $items   = $cart->cartItems()->with('product')->get();
        $subtotal = $cart->cartItems()->sum('total');
        $discount = $cart->discount ?? 0;
        $tax      = round(($subtotal - $discount) * config('cart.tax_rate', 0), 2);
        $total    = $cart->total;

        if ($items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        return view('pages.checkout.index', compact('items','subtotal','discount','tax','total'));
    }

    /**
     * AJAX endpoint: apply a promo code to the current DB cart.
     */
    public function applyPromo(Request $request)
    {
        $cart    = $this->getCart($request);
        $items   = $cart->cartItems()->get();
        $subtotal = $items->sum(fn($i) => $i->price * $i->quantity);

        if ($items->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty.'], 422);
        }

        $code  = strtoupper(trim($request->input('code','')));
        $promo = PromoCode::where('code', $code)
            ->where('active', true)
            ->first();

        if (! $promo) {
            return response()->json(['error'=>'That promo code is invalid.'], 422);
        }
        if ($promo->expires_at && now()->gt($promo->expires_at)) {
            return response()->json(['error'=>'That promo has expired.'], 422);
        }
        if ($promo->max_uses && $promo->used_count >= $promo->max_uses) {
            return response()->json(['error'=>'That promo is fully redeemed.'], 422);
        }

        $discount = $promo->type === 'percent'
            ? round($subtotal * ($promo->discount/100), 2)
            : min($promo->discount, $subtotal);

        $tax   = round(($subtotal - $discount) * config('cart.tax_rate', 0), 2);
        $total = round($subtotal - $discount + $tax, 2);

        // store on Cart
        $cart->promo_code = $promo->code;
        $cart->discount   = $discount;
        $cart->total      = $subtotal - $discount;
        $cart->save();

        return response()->json(compact('subtotal','discount','tax','total'));
    }

    /**
     * Place the order: validate (including promo), create Stripe session,
     * persist Order + Items, clear cart, send emails.
     */
    public function placeOrder(Request $request)
    {
        $data = $request->validate([
            'shipping_address'=>'required|string',
            'email'           =>'required|email',
            'phone'           =>'nullable|string',
        ]);

        $cart    = $this->getCart($request);
        $items   = $cart->cartItems()->with('product')->get();
        $subtotal = $items->sum(fn($i) => $i->price * $i->quantity);
        $discount = $cart->discount  ?? 0;
        $code     = $cart->promo_code;

        if ($items->isEmpty()) {
            return response()->json(['error'=>'Your cart is empty.'], 422);
        }

        $tax   = round(($subtotal - $discount) * config('cart.tax_rate', 0), 2);
        $total = round($subtotal - $discount + $tax, 2);

        // Create Stripe session
        Stripe::setApiKey(config('services.stripe.secret'));
        $lineItems = $items->map(fn($i) => [
            'price_data' => [
                'currency'     => 'usd',
                'product_data' => ['name' => $i->product->name],
                'unit_amount'  => intval($i->price * 100),
            ],
            'quantity'   => $i->quantity,
        ])->toArray();

        if ($discount > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => ['name' => "Promo: {$code}"],
                    'unit_amount'  => intval(-1 * $discount * 100),
                ],
                'quantity' => 1,
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

        // Persist Order
        $order = Order::create([
            'user_id'          => auth()->id(),
            'shipping_address' => $data['shipping_address'],
            'email'            => $data['email'],
            'phone'            => $data['phone'],
            'total'            => $total,
            'status'           => 'pending',
            'meta'             => json_encode(['stripe_session'=>$session->id]),
        ]);

        // Persist Items
        foreach ($items as $i) {
            $order->orderItems()->create([
                'product_id' => $i->product_id,
                'name'       => $i->product->name,
                'quantity'   => $i->quantity,
                'price'      => $i->price,
                'total'      => $i->price * $i->quantity,
            ]);
        }

        // Increment promo usage
        if ($code) {
            PromoCode::where('code', $code)->increment('used_count');
        }

        // Clear cart
        CartItem::where('cart_id', $cart->id)->delete();
        $cart->update(['promo_code'=>null,'discount'=>0,'total'=>0]);

        // Emails
        Mail::to($order->email)->queue(new Receipt($order));
        if ($admin = config('mail.admin_address')) {
            Mail::to($admin)->queue(new OrderPlaced($order));
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
    public function success()
    {
        return view('pages.checkout.success');
    }

    /**
     * Mirror of CartController::getCart()
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
