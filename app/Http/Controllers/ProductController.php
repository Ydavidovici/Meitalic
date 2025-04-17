<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the products for all users.
     */
    public function index(Request $request)
    {
        // Optionally, you can add filtering logic here if needed.
        $products = Product::all();
        return view('pages.products', compact('products'));
    }

    /**
     * Display the specified product for all users.
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);
        return view('pages.product', compact('product'));
    }

    /**
     * Show the form for creating a new product (admin only).
     */
    public function create()
    {
        if (!auth()->user() || !auth()->user()->is_admin) {
            abort(403, 'Unauthorized');
        }
        return view('pages.admin.products.create');
    }

    /**
     * Store a newly created product in storage (admin only).
     */
    public function store(Request $request)
    {
        if (!auth()->user() || !auth()->user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        $validatedData = $request->validate([
            'name'        => 'required|string|max:255',
            'brand'       => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'required|string',
            'price'       => 'required|numeric',
            'image'       => 'nullable|string',
            'inventory'   => 'required|integer',
        ]);

        Product::create($validatedData);

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    /**
     * Show the form for editing the specified product (admin only).
     */
    public function edit($id)
    {
        if (!auth()->user() || !auth()->user()->is_admin) {
            abort(403, 'Unauthorized');
        }
        $product = Product::findOrFail($id);
        return view('pages.admin.products.edit', compact('product'));
    }

    /**
     * Update the specified product in storage (admin only).
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user() || !auth()->user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        $validatedData = $request->validate([
            'name'        => 'required|string|max:255',
            'brand'       => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'required|string',
            'price'       => 'required|numeric',
            'image'       => 'nullable|string',
            'inventory'   => 'required|integer',
        ]);

        $product = Product::findOrFail($id);
        $product->update($validatedData);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage (admin only).
     */
    public function destroy($id)
    {
        if (!auth()->user() || !auth()->user()->is_admin) {
            abort(403, 'Unauthorized');
        }
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }
}
