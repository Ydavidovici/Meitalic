<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        // grab 5 random featured products
        $featuredProducts = Product::featured()
            ->inRandomOrder()
            ->take(5)
            ->get();

        return view('pages.home', compact('featuredProducts'));
    }
}
