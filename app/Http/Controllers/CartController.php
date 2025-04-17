<?php

namespace App\Http\Controllers;

use App\Services\CartService;

class CartController extends Controller
{
    public function __construct(protected CartService $cart) {}

    public function index()
    {
        return view('cart.index', [
            'items' => $this->cart->all(),
            'total' => $this->cart->total(),
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
}
