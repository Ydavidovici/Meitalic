<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        // Fetch recent orders, for example the latest 10 orders
        $recentOrders = Order::latest()->take(10)->get();
        return view('pages.admin.dashboard', compact('recentOrders'));
    }

    public function orders()
    {
        // Show all orders
        $orders = Order::latest()->paginate(20);
        return view('pages.admin.orders', compact('orders'));
    }
}
