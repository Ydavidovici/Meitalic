<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\PromoCode;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function index(Request $request)
    {
        abort_if(! $request->user()?->is_admin, 403);

        // 1) KPIs
        $kpis = [
            'orders_today'    => Order::whereDate('created_at', today())->count(),
            'orders_week'     => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'orders_month'    => Order::whereMonth('created_at', now()->month)->count(),
            'revenue_today'   => Order::whereDate('created_at', today())->sum('total'),
            'revenue_week'    => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('total'),
            'revenue_month'   => Order::whereMonth('created_at', now()->month)->sum('total'),
            'avg_order_value' => Order::whereDate('created_at', today())->avg('total'),
        ];

        // 2) Pending / Unfulfilled counts
        $counts = [
            'pending'     => Order::where('status', 'pending')->count(),
            'unfulfilled' => Order::where('status', 'unfulfilled')->count(),
        ];

        // 3) Recent orders
        $recentOrders = Order::latest()->take(10)->get();

        // 4) Inventory alerts
        $threshold  = config('inventory.low_stock_threshold', 5);
        $lowStock   = Product::where('inventory', '<=', $threshold)->get();
        $outOfStock = Product::where('inventory', 0)->get();

        // 5) Product performance
        $topSellers = Product::select('products.*')
            ->selectRaw('(SELECT COALESCE(SUM(quantity),0) FROM order_items WHERE order_items.product_id=products.id) AS sold')
            ->orderByDesc('sold')
            ->limit(5)
            ->get();

        $topRevenue = Product::select('products.*')
            ->selectRaw('(SELECT COALESCE(SUM(quantity*price),0) FROM order_items WHERE order_items.product_id=products.id) AS revenue')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $slowMovers = Product::select('products.*')
            ->selectRaw('(SELECT COALESCE(SUM(quantity),0) FROM order_items WHERE order_items.product_id=products.id) AS sold')
            ->orderBy('sold')
            ->limit(5)
            ->get();

        // 6) Customer insights
        $newCustomersToday = User::whereDate('created_at', today())->count();
        $topCustomers = User::select('users.*')
            ->selectRaw('(SELECT COALESCE(SUM(total),0) FROM orders WHERE orders.user_id=users.id) AS lifetime_spend')
            ->orderByDesc('lifetime_spend')
            ->limit(5)
            ->get();

        // 7) Marketing & promotions
        $activeCoupons   = PromoCode::where('active', true)->get();
        $expiringCoupons = PromoCode::whereBetween('expires_at', [now(), now()->addDays(7)])->get();

        // 8) Analytics HTML (stubbed out)
        $analyticsHtml = '';

        // — 9) Filterable product list —
        $q = Product::query();
        if ($b = $request->query('brand')) {
            $q->where('brand', $b);
        }
        if ($c = $request->query('category')) {
            $q->where('category', $c);
        }
        $allowed = ['inventory','updated_at','name'];
        $sort    = in_array($request->query('sort'), $allowed)
            ? $request->query('sort')
            : 'updated_at';
        $dir     = $request->query('dir') === 'asc' ? 'asc' : 'desc';
        $products = $q->orderBy($sort, $dir)
            ->paginate(20);

        $allBrands     = Product::select('brand')->distinct()->orderBy('brand')->pluck('brand');
        $allCategories = Product::select('category')->distinct()->orderBy('category')->pluck('category');

        if ($request->ajax()) {
            return view('partials.admin.product-grid', compact('products', 'allBrands', 'allCategories'));
        }

        return view('pages.admin.dashboard', compact(
            'kpis','counts','recentOrders',
            'lowStock','outOfStock','topSellers','topRevenue','slowMovers',
            'newCustomersToday','topCustomers',
            'activeCoupons','expiringCoupons','analyticsHtml',
            'products', 'allBrands', 'allCategories'
        ));
    }

    /**
     * Adjust a product’s inventory by a delta (positive or negative).
     */
    public function adjustInventory(Request $request, Product $product)
    {
        abort_if(! $request->user()?->is_admin, 403);

        $data = $request->validate([
            'delta' => 'required|integer',
        ]);

        $product->increment('inventory', $data['delta']);

        return back()->with('product_success',
            "Adjusted “{$product->name}” by {$data['delta']} units."
        );
    }

    /**
     * Paginated list of all orders.
     */
    public function orders(Request $request)
    {
        abort_if(! $request->user()?->is_admin, 403);

        $orders = Order::latest()->paginate(20);

        return view('pages.admin.orders', compact('orders'));
    }
}
