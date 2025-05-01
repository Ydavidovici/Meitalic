<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::query();

        if ($line = $request->query('line')) {
            $q->where('brand', $line);
        }
        if ($cat = $request->query('category')) {
            $q->where('category', $cat);
        }

        $allowed = ['price','name','updated_at'];
        $sort    = in_array($request->query('sort'), $allowed)
            ? $request->query('sort')
            : 'name';
        $dir     = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $products = $q->orderBy($sort, $dir)
            ->paginate(20);

        if ($request->ajax()) {
            return view('partials.product-grid', compact('products'));
        }

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
        $this->authorizeAdmin();
        return view('admin.dashboard');
    }

    // Admin: store new
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'brand'       => 'required|string|max:255',
            'category'    => 'required|string',
            'description' => 'required|string',
            'price'       => 'required|numeric|min:0',
            'inventory'   => 'required|integer|min:0',
            'image'       => 'nullable|image|max:2048',
        ]);

        // auto-slug & SKU
        $data['slug'] = Str::slug($data['name']);
        // unique SKU: slug + 6-char random
        $data['sku']  = $data['slug'] . '-' . Str::upper(Str::random(6));

        // handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request
                ->file('image')
                ->store('products','public');
        }

        Product::create($data);

        return redirect()
            ->route('admin.dashboard')
            ->with('success','Product created successfully.');
    }

    // Admin: show "edit" form
    public function edit(Product $product)
    {
        $this->authorizeAdmin();
        return view('pages.admin.products.edit', compact('product'));
    }

    // Admin: apply update
    public function update(Request $request, Product $product)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'brand'       => 'required|string|max:255',
            'category'    => 'required|string',
            'description' => 'required|string',
            'price'       => 'required|numeric|min:0',
            'inventory'   => 'required|integer|min:0',
            'image'       => 'nullable|image|max:2048',
        ]);

        // if name changed, regenerate slug (optional)
        if ($data['name'] !== $product->name) {
            $data['slug'] = Str::slug($data['name']);
            // keep existing SKU or regenerate?
            // $data['sku'] = $data['slug'].'-'.Str::upper(Str::random(6));
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request
                ->file('image')
                ->store('products','public');
        }

        $product->update($data);

        return redirect()
            ->route('admin.dashboard')
            ->with('success','Product updated successfully.');
    }

    // Admin: delete
    public function destroy(Product $product)
    {
        $this->authorizeAdmin();
        $product->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('success','Product deleted successfully.');
    }

    protected function authorizeAdmin()
    {
        if (! auth()->user()?->is_admin) {
            abort(403);
        }
    }
}
