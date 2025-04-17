<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Webkul\Order\Repositories\OrderRepository;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin.auth');
    }

    public function index(OrderRepository $orderRepository)
    {
        $recentOrders = $orderRepository->orderBy('created_at', 'desc')->take(10)->get();
        return view('pages.admin.dashboard', compact('recentOrders'));
    }

    public function orders(OrderRepository $orderRepository)
    {
        $orders = $orderRepository->orderBy('created_at', 'desc')->paginate(20);
        return view('pages.admin.orders', compact('orders'));
    }
}
