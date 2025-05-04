<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;

class AccountController extends Controller
{
    /**
     * Show the user dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1) At-a-glance metrics
        $totalOrders = Order::where('user_id', $user->id)->count();
        $yearlySpend = Order::where('user_id', $user->id)
            ->whereYear('created_at', now()->year)
            ->sum('total');
        $storeCredit = 0;

        // 2) Recent & upcoming orders
        $recentOrders = Order::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // 3) Full order history
        $allOrders = Order::where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        // 4) Recommendations & promotions
        $recommendations = Product::where('active', true)
            ->take(8)
            ->get();

        $activePromos = PromoCode::where('active', true)->get();

        return view('pages.dashboard.index', compact(
            'totalOrders',
            'yearlySpend',
            'storeCredit',
            'recentOrders',
            'allOrders',
            'recommendations',
            'activePromos'
        ));
    }

    public function show($id)
    {
        // Only allow the owner to fetch it:
        $order = Order::with('orderItems.product')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'id'               => $order->id,
            'user_id'          => $order->user_id,
            'shipping_address' => $order->shipping_address,
            'email'            => $order->email,
            'phone'            => $order->phone,
            'total'            => (float) $order->total,
            'status'           => $order->status,
            // if you have arbitrary JSON on meta:
            'meta'             => $order->meta ? json_decode($order->meta, true) : null,
            // ISO8601 strings, null‑safe via optional()
            'created_at'       => optional($order->created_at)->toIso8601String(),
            'updated_at'       => optional($order->updated_at)->toIso8601String(),
            'items'            => $order->orderItems->map(function($i) {
                return [
                    'id'       => $i->id,
                    'name'     => $i->product->name,
                    'quantity' => $i->quantity,
                    'price'    => (float) $i->price,
                ];
            })->values(),
        ]);
    }


    public function cancel(Order $order)
    {
        $order->update(['status' => 'canceled']);
        return response()->json(['status' => 'canceled']);
    }

    public function return(Order $order)
    {
        $order->update(['status' => 'returned']);
        return response()->json(['status' => 'returned']);
    }

    /**
     * Show current cart.
     */
    public function cart(Request $request)
    {
        $cart = session('cart', []);
        return view('pages.dashboard.cart', compact('cart'));
    }

    /**
     * (Optional) Dump all orders as JSON.
     */
    public function ordersJson(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('orderItems.product', 'payment')
            ->latest()
            ->get();

        return response()->json($orders);
    }
}
