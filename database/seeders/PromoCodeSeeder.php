<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PromoCode;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        PromoCode::factory()->count(10)->create();
    }
}
