<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    protected function getCart(Request $request)
    {
        if (auth()->check()) {
            return Cart::firstOrCreate(['user_id' => auth()->id()]);
        }

        $sessionId = $request->session()->get('cart_session_id', Str::uuid());
        $request->session()->put('cart_session_id', $sessionId);

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    public function index(Request $request)
    {
        $cart = $this->getCart($request);
        $items = $cart->cartItems()->with('product')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'items'    => $items,
                'subtotal' => $cart->cartItems()->sum('total'),
                'discount' => $cart->discount,
                'tax'      => round(($cart->cartItems()->sum('total') - $cart->discount) * config('cart.tax_rate',0), 2),
                'total'    => $cart->total,
            ]);
        }

        // existing Blade view
        return view('cart.index', [
            'items'     => $items,
            'total'     => $cart->total,
            'discount'  => $cart->discount ?? 0,
            'promoCode' => $cart->promo_code,
        ]);
    }


    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->quantity ?? 1;

        $cart = $this->getCart($request);

        CartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $product->id],
            ['quantity' => $quantity, 'price' => $product->price, 'total' => $product->price * $quantity]
        );

        $this->calculateTotals($cart);

        return redirect()->back()->with('success', 'Added to cart!');
    }

    public function remove(Request $request, $id)
    {
        $cart = $this->getCart($request);
        CartItem::where('cart_id', $cart->id)->where('id', $id)->delete();

        $this->calculateTotals($cart);

        return back()->with('success', 'Item removed.');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);

        $cartItem = CartItem::findOrFail($id);
        $cartItem->quantity = $request->quantity;
        $cartItem->total = $cartItem->price * $request->quantity;
        $cartItem->save();

        $this->calculateTotals($cartItem->cart);

        return back()->with('success', 'cart updated.');
    }

    public function applyPromo(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $cart = $this->getCart($request);

        $promo = PromoCode::where('code', $request->code)->where('active', true)->first();

        if (!$promo || ($promo->expires_at && now()->greaterThan($promo->expires_at))) {
            return back()->with('error', 'Invalid or expired promo code.');
        }

        $cart->promo_code = $promo->code;
        $cart->discount = $promo->type === 'fixed'
            ? $promo->discount
            : ($cart->total * ($promo->discount / 100));

        $cart->total -= $cart->discount;
        $cart->save();

        return back()->with('success', 'Promo code applied!');
    }

    protected function calculateTotals(Cart $cart)
    {
        $cart->total = $cart->cartItems()->sum('total');

        if ($cart->promo_code) {
            $promo = PromoCode::where('code', $cart->promo_code)->first();
            if ($promo) {
                $cart->discount = $promo->type === 'fixed'
                    ? $promo->discount
                    : ($cart->total * ($promo->discount / 100));

                $cart->total -= $cart->discount;
            }
        }

        $cart->save();
    }

}
