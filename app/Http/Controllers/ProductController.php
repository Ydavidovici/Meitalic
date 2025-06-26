<?php
// app/Http/Controllers/ProductController.php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->firstOrFail();

        // fetch all image paths for this product
        $images = $this->getProductImages($product->id);

        return view('pages.product', compact('product','images'));
    }

    // Admin: show "create" form via dashboard
    public function create(Request $request)
    {
        $this->authorizeAdmin();
        // Delegates to AdminController@index which renders the dashboard with modals
        return app(AdminController::class)->index($request);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        Log::debug('▶︎ store() hit', [
            'has_file'   => $request->hasFile('image'),
            'file_valid' => $request->hasFile('image')
                ? $request->file('image')->isValid()
                : null,
            'all_input'  => $request->except('image'), // avoid dumping the whole file object
        ]);
        try {
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
            'images'     => 'nullable|array',
            'images.*'   => 'file|mimes:jpg,jpeg,png,gif,webp|max:30720',
            'is_featured' => 'sometimes|boolean',
        ]);
        } catch (ValidationException $e) {
            // If this was an AJAX/Fetch request, return JSON, else redirect back.
            if ($request->expectsJson()) {
                return response()->json([
                    'errors' => $e->validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            throw $e; // leaves your old redirect+error-bag behavior intact
        }

        // slug & sku
        $data['slug']        = Str::slug($data['name']);
        $data['sku']         = $data['slug'].'-'.Str::upper(Str::random(6));
        $data['is_featured'] = $request->has('is_featured');

        // 1) create the product (without images)
        $product = Product::create(Arr::except($data, ['images']));

        // 2) store each uploaded image
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('products', 'public');
                $product->images()->create(['path' => $path]);
            }
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Product created successfully.');
    }

    // Admin: update existing product
    public function update(Request $request, Product $product)
    {
        $this->authorizeAdmin();

        // same validation rules as store
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
            'is_featured' => 'sometimes|boolean',
            'images'      => 'nullable|array',
            'images.*'    => 'file|mimes:jpg,jpeg,png,gif,webp|max:30720',
        ]);

        // update slug if name changed
        if ($data['name'] !== $product->name) {
            $data['slug'] = Str::slug($data['name']);
        }
        $data['is_featured'] = $request->has('is_featured');

        if ($toRemove = $request->input('remove_images')) {
            foreach (json_decode($toRemove, true) as $imgId) {
                ProductImage::where('id', $imgId)
                    ->where('product_id', $product->id)
                    ->delete();
            }
        }

        // update product record (without images)
        $product->update(Arr::except($data, ['images']));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('products', 'public');
                $product->images()->create(['path' => $path]);
            }
        }

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

    protected function getProductImages(int $productId)
    {
        return ProductImage::where('product_id', $productId)
            ->pluck('path');
    }

    // Ensure only admins can hit these endpoints
    protected function authorizeAdmin()
    {
        if (! auth()->user()?->is_admin) {
            abort(403);
        }
    }
}
