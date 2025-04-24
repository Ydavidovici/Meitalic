<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CartService;
use App\Models\Cart;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class CheckoutController extends Controller
{
    public function __construct(protected CartService $cart) {}

    public function create(Request $request)
    {
        $items = $this->cart->all();

        if (!$items || count($items) === 0) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $lineItems = [];

        foreach ($items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => [
                        'name' => $item['product']->name,
                    ],
                    'unit_amount' => intval($item['product']->price * 100),
                ],
                'quantity' => $item['quantity'],
            ];
        }

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items'           => $lineItems,
            'mode'                 => 'payment',
            'success_url'          => route('checkout.success'),
            'cancel_url'           => route('cart.index'),
        ]);

        return redirect($session->url);
    }

    public function success()
    {
        $cart = auth()->check()
            ? Cart::where('user_id', auth()->id())->first()
            : Cart::where('session_id', session()->get('cart_session_id'))->first();

        if ($cart) {
            $cart->cartItems()->delete();
            $cart->update([
                'discount' => 0,
                'promo_code' => null,
                'total' => 0,
            ]);
        }

        return view('checkout.success');
    }
}
