<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;

class AccountController extends Controller
{
    /**
     * Show the user dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1) At‑a‑glance metrics
        $totalOrders = Order::where('user_id', $user->id)->count();
        $yearlySpend = Order::where('user_id', $user->id)
            ->whereYear('created_at', now()->year)
            ->sum('total');
        $storeCredit = 0; // if you have a store credit system

        // 2) Recent & upcoming orders (just list most recent 5)
        $recentOrders = Order::where('user_id', $user->id)
            ->with('orderItems.review')
            ->latest()
            ->take(5)
            ->get();

        // 3) Full order history (paginated)
        $allOrders = Order::where('user_id', $user->id)
            ->with('orderItems.review')
            ->latest()
            ->paginate(10);

        // 4) Recommendations
        $recommendations = Product::where('active', true)
            ->inRandomOrder()
            ->take(8)
            ->get();


        return view('pages.dashboard.index', [
            'totalOrders'    => $totalOrders,
            'yearlySpend'    => $yearlySpend,
            'storeCredit'    => $storeCredit,
            'recentOrders'   => $recentOrders,
            'allOrders'      => $allOrders,
            'recommendations'=> $recommendations,
            'profileData'     => $user->only(['name','email']),
            'user'           => $user,

        ]);
    }

    /**
     * Return one order as JSON for the “view order” modal.
     */
    public function show(Order $order)
    {
        $this->authorize('view', $order);  // or ensure user owns it

        $order->load('orderItems.product');

        return response()->json([
            'id'               => $order->id,
            'status'           => $order->status,
            'shipping_address' => $order->shipping_address,
            'email'            => $order->email,
            'phone'            => $order->phone,
            'total'            => (float) $order->total,
            'created_at'       => $order->created_at->toIso8601String(),
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


    /**
     * Show a simple cart summary page (optional).
     */
    public function cart(Request $request)
    {
        $cart = session('cart', []);
        return view('pages.dashboard.cart', compact('cart'));
    }

    /**
     * (Optional) JSON endpoint listing all orders.
     */
    public function orders(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('orderItems.product')
            ->latest()
            ->paginate(10);

        return view('pages.dashboard.orders', compact('orders'));
    }

    /**
     * Show all reviews this user has left.
     */
    public function reviews(Request $request)
    {
        $reviews = $request->user()
            ->reviews()                        // make sure User model has reviews()
            ->with(['product','orderItem.order'])
            ->latest()
            ->paginate(10);

        return view('pages.dashboard.reviews.index', compact('reviews'));
    }

}
