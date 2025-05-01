<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Public listing
    public function index(Request $request)
    {
        $products = Product::all();
        return view('pages.products', compact('products'));
    }

    // Public detail
    public function show($slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        return view('pages.product', compact('product'));
    }

    // Admin: show "create" form
    public function create()
    {
        $this->authorize('admin-only');
        return view('pages.admin.products.create');
    }

    // Admin: store new
    public function store(Request $request)
    {
        $this->authorize('admin-only');

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'brand'       => 'required|string|max:255',
            'category'    => 'required|string',
            'description' => 'required|string',
            'price'       => 'required|numeric',
            'image'       => 'nullable|string',
            'inventory'   => 'required|integer',
            'sku'         => 'required|string|max:255|unique:products,sku',
        ]);

        Product::create($validated);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    // Admin: show "edit" form
    public function edit(Product $product)
    {
        $this->authorize('admin-only');
        return view('pages.admin.products.edit', compact('product'));
    }

    // Admin: apply update
    public function update(Request $request, Product $product)
    {
        $this->authorize('admin-only');

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'brand'       => 'required|string|max:255',
            'category'    => 'required|string',
            'description' => 'required|string',
            'price'       => 'required|numeric',
            'image'       => 'nullable|string',
            'inventory'   => 'required|integer',
            'sku'         => 'required|string|max:255|unique:products,sku,' . $product->id,
        ]);

        $product->update($validated);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    // Admin: delete
    public function destroy(Product $product)
    {
        $this->authorize('admin-only');
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    // Simple gate for admin-only
    protected function authorize(string $ability)
    {
        if (! auth()->user()?->is_admin) {
            abort(403);
        }
    }
}
