<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $cart) {}

    public function index()
    {
        return view('cart.index', [
            'items'     => $this->cart->all(),
            'total'     => $this->cart->total(),
            'discount'  => $this->cart->getDiscount(),
            'promoCode' => $this->cart->promoCode(),
        ]);
    }

    public function add(Request $request)
    {
        $this->cart->add($request->product_id, $request->quantity ?? 1);
        return redirect()->back()->with('success', 'Added to cart!');
    }

    public function remove($id)
    {
        $this->cart->remove($id);
        return back();
    }

    public function update(Request $request, $id)
    {
        $this->cart->update($id, $request->quantity);
        return back()->with('success', 'Cart updated.');
    }

    public function applyPromo(Request $request)
    {
        try {
            $this->cart->applyPromoCode($request->code);
            return back()->with('success', 'Promo code applied!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
