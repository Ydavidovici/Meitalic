<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        // 1) grab 4 random featured products
        $featuredProducts = Product::featured()
            ->inRandomOrder()
            ->take(4)
            ->get();

        // 2) get all distinct brands for the “Shop by Brand” section
        $allBrands = Product::select('brand')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand');

        return view('pages.home', compact('featuredProducts', 'allBrands'));
    }
}
