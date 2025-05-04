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
        $storeCredit = 0; // no store_credit column

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
        // drop is_featured—just pick active products
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

    /**
     * Show full order history (alternate route if needed).
     */
    public function orders(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('pages.dashboard.orders', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $html = view('pages.dashboard.partials.order-modal', [
            'order' => $order->load('orderItems.product','payment'),
        ])->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Show current cart.
     */
    public function cart(Request $request)
    {
        $cart = session('cart', []);
        return view('pages.dashboard.cart', compact('cart'));
    }

    public function ordersJson(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('orderItems.product','payment')
            ->latest()
            ->get();

        return response()->json($orders);
    }

    /**
     * Cancel an order (user action).
     */
    public function cancel(Request $request, Order $order)
    {
        $this->authorize('update', $order);
        $order->update(['status' => 'canceled']);
        return response()->json($order);
    }

    /**
     * Return an order (user action).
     */
    public function return(Request $request, Order $order)
    {
        $this->authorize('update', $order);
        $order->update(['status' => 'returned']);
        return response()->json($order);
    }
}
