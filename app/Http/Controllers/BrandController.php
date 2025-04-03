<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        // Retrieve a distinct list of brands from products
        $brands = Product::select('brand')->distinct()->get();
        return view('pages.brands.index', compact('brands'));
    }

    public function show($brand)
    {
        // Optionally, retrieve details for a specific brand (if stored in a separate model, else use static view)
        // For now, we'll assume a static Blade template for the about page for each brand
        // You can also pass products belonging to this brand if needed
        $products = Product::where('brand', $brand)->get();
        return view('pages.brands.show', compact('brand', 'products'));
    }
}
