<?php
// app/Http/Controllers/ProductController.php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // Public listing
    public function index(Request $request)
    {
        $q = Product::query();

        // — 1. free-text search —
        if ($search = $request->query('search')) {
            $q->search($search);
        }

        // — 2. exact filters —
        if ($brand = $request->query('brand')) {
            $q->where('brand', $brand);
        }
        if ($category = $request->query('category')) {
            $q->where('category', $category);
        }
        if ($line = $request->query('line')) {
            $q->where('line', $line);
        }

        // — 2.b) featured filter —
        if (! is_null($request->query('featured'))) {
            $val = (int) $request->query('featured');
            if (in_array($val, [0, 1], true)) {
                $q->where('is_featured', $val === 1);
            }
        }

        // — 3. sorting —
        $allowed = ['price', 'name', 'updated_at'];
        $sort    = in_array($request->query('sort'), $allowed)
            ? $request->query('sort')
            : 'name';
        $dir     = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        // — 4. dropdown data —
        $allBrands     = Product::select('brand')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand');
        $allCategories = Product::select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        $allLines      = Product::whereNotNull('line')
            ->distinct()
            ->orderBy('line')
            ->pluck('line');

        // — 5. paginate & preserve query —
        $products = $q->orderBy($sort, $dir)
            ->paginate(20)
            ->appends(
                $request->only([
                    'search',
                    'brand',
                    'category',
                    'line',
                    'featured',
                    'sort',
                    'dir',
                ])
            );

        if ($request->ajax()) {
            return view('partials.product-grid', compact('products'));
        }

        return view('pages.products', compact(
            'products',
            'allBrands',
            'allCategories',
            'allLines'
        ));
    }

    // Public detail
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        return view('pages.product', compact('product'));
    }

    // Admin: show "create" form via dashboard
    public function create(Request $request)
    {
        $this->authorizeAdmin();
        // Delegates to AdminController@index which renders the dashboard with modals
        return app(AdminController::class)->index($request);
    }

    // Admin: store new product
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'brand'       => 'required|string|max:255',
            'category'    => 'required|string',
            'line'        => 'nullable|string',
            'description' => 'required|string',
            'weight'      => 'required|numeric|min:0',
            'length'      => 'required|numeric|min:0',
            'width'       => 'required|numeric|min:0',
            'height'      => 'required|numeric|min:0',
            'price'       => 'required|numeric|min:0',
            'inventory'   => 'required|integer|min:0',
            'image'       => 'nullable|image|max:2048',
            'is_featured' => 'sometimes|boolean',
        ]);

        // auto-slug & SKU
        $data['slug']        = Str::slug($data['name']);
        $data['sku']         = $data['slug'].'-'.Str::upper(Str::random(6));
        $data['is_featured'] = $request->has('is_featured');

        if ($file = $request->file('image')) {
            $data['image'] = $file->store('products', 'public');
        }

        Product::create($data);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Product created successfully.');
    }

    // Admin: update existing product
    public function update(Request $request, Product $product)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'brand'       => 'required|string|max:255',
            'category'    => 'required|string',
            'line'        => 'nullable|string',
            'description' => 'required|string',
            'weight'      => 'required|numeric|min:0',
            'length'      => 'required|numeric|min:0',
            'width'       => 'required|numeric|min:0',
            'height'      => 'required|numeric|min:0',
            'price'       => 'required|numeric|min:0',
            'inventory'   => 'required|integer|min:0',
            'image'       => 'nullable|image|max:2048',
            'is_featured' => 'sometimes|boolean',
        ]);

        if ($data['name'] !== $product->name) {
            $data['slug'] = Str::slug($data['name']);
        }
        $data['is_featured'] = $request->has('is_featured');

        if ($file = $request->file('image')) {
            $data['image'] = $file->store('products', 'public');
        }

        $product->update($data);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Product updated successfully.');
    }

    // Admin: delete a product
    public function destroy(Product $product)
    {
        $this->authorizeAdmin();
        $product->cartItems()->delete();
        $product->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Product deleted successfully.');
    }

    // Ensure only admins can hit these endpoints
    protected function authorizeAdmin()
    {
        if (! auth()->user()?->is_admin) {
            abort(403);
        }
    }
}
