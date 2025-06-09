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

        $allBrands = array_keys(config('brands'));

        return view('pages.home', compact('featuredProducts', 'allBrands'));
    }
}
