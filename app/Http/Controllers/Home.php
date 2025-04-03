<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::take(5)->get();
        return view('pages.home', compact('featuredProducts'));
    }
}
