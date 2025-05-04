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

        // 3) Recent orders with status, amount & order‑number filters
        $orderQ = Order::query();

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $orderQ->where('status', $status);
            }
        }
        if (! is_null($min = $request->query('min_amount'))) {
            $orderQ->where('total', '>=', $min);
        }
        if (! is_null($max = $request->query('max_amount'))) {
            $orderQ->where('total', '<=', $max);
        }
        if ($num = $request->query('order_number')) {
            $orderQ->where('id', $num);
        }

        $recentOrders = $orderQ
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($request->only([
                'status',
                'min_amount',
                'max_amount',
                'order_number',
            ]));

        $allStatuses = ['all','pending','shipped','delivered','unfulfilled'];


        // 4) Inventory alerts
        $threshold  = config('inventory.low_stock_threshold', 5);
        $lowStock   = Product::where('inventory', '<=', $threshold)->get();
        $outOfStock = Product::where('inventory', 0)->get();

        // 5) Product performance
        $topSellers = Product::select('products.*')
            ->selectRaw('(SELECT COALESCE(SUM(quantity),0) FROM order_items WHERE order_items.product_id = products.id) AS sold')
            ->orderByDesc('sold')
            ->limit(5)
            ->get();

        $topRevenue = Product::select('products.*')
            ->selectRaw('(SELECT COALESCE(SUM(quantity * price),0) FROM order_items WHERE order_items.product_id = products.id) AS revenue')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $slowMovers = Product::select('products.*')
            ->selectRaw('(SELECT COALESCE(SUM(quantity),0) FROM order_items WHERE order_items.product_id = products.id) AS sold')
            ->orderBy('sold')
            ->limit(5)
            ->get();

        // 6) Customer insights
        $newCustomersToday = User::whereDate('created_at', today())->count();
        $topCustomers = User::select('users.*')
            ->selectRaw('(SELECT COALESCE(SUM(total),0) FROM orders WHERE orders.user_id = users.id) AS lifetime_spend')
            ->orderByDesc('lifetime_spend')
            ->limit(5)
            ->get();

        // 7) Marketing & promotions
        $activeCoupons   = PromoCode::where('active', true)->get();
        $expiringCoupons = PromoCode::whereBetween('expires_at', [now(), now()->addDays(7)])->get();

        // 8) Analytics HTML (stubbed out)
        $analyticsHtml = '';

        // 9) Filterable product list
        $q = Product::query();

        // 9.a) free‑text search via the “q” field
        if ($term = $request->query('q')) {
            $q->search($term);
        }

        // 9.b) exact filters
        if ($b = $request->query('brand')) {
            $q->where('brand', $b);
        }
        if ($c = $request->query('category')) {
            $q->where('category', $c);
        }

        // sorting
        $allowed = ['inventory', 'updated_at', 'name'];
        $sort    = in_array($request->query('sort'), $allowed)
            ? $request->query('sort')
            : 'updated_at';
        $dir     = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        // paginate & preserve filters
        $products = $q->orderBy($sort, $dir)
            ->paginate(20)
            ->appends($request->only(['q', 'brand', 'category', 'sort', 'dir']));

        $allBrands     = Product::select('brand')->distinct()->orderBy('brand')->pluck('brand');
        $allCategories = Product::select('category')->distinct()->orderBy('category')->pluck('category');

        if ($request->ajax()) {
            return view('partials.admin.product-grid', compact('products', 'allBrands', 'allCategories'));
        }

        return view('pages.admin.dashboard', compact(
            'kpis',
            'counts',
            'recentOrders',
            'allStatuses',
            'lowStock',
            'outOfStock',
            'topSellers',
            'topRevenue',
            'slowMovers',
            'newCustomersToday',
            'topCustomers',
            'activeCoupons',
            'expiringCoupons',
            'analyticsHtml',
            'products',
            'allBrands',
            'allCategories'
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

        return back()->with(
            'product_success',
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

    /**
     * Update a single order’s status via AJAX.
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        abort_if(! $request->user()?->is_admin, 403);

        $data = $request->validate([
            'status' => 'required|in:pending,shipped,delivered,unfulfilled',
        ]);

        $order->update(['status' => $data['status']]);

        // Return a simple JSON success response
        return response()->json(['success' => true]);
    }

    /**
     * Bulk‐update statuses for multiple orders via AJAX.
     */
    public function bulkUpdateOrderStatus(Request $request)
    {
        abort_if(! $request->user()?->is_admin, 403);

        $data = $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'integer|exists:orders,id',
            'status' => 'required|in:pending,shipped,delivered,unfulfilled',
        ]);

        Order::whereIn('id', $data['ids'])
            ->update(['status' => $data['status']]);

        return response()->json(['success' => true]);
    }

    public function show(Order $order)
    {
        abort_if(! auth()->user()?->is_admin, 403);

        $order->load('user','items');

        // you can shape this array any way you like—here’s a simple pass‑through
        return response()->json($order);
    }

    public function update(Request $request, Order $order)
    {
        abort_if(! auth()->user()?->is_admin, 403);

        $data = $request->validate([
            'status'           => 'required|in:pending,shipped,delivered,unfulfilled',
            'total'            => 'required|numeric|min:0',
            'shipping_address' => 'required|string',
            'email'            => 'nullable|email',
            'phone'            => 'nullable|string',
        ]);

        $order->update($data);

        return response()->json(['success' => true]);
    }
}
