<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        Product::factory()
            ->count(20)
            ->hasImages(5)
            ->create();
    }
}
