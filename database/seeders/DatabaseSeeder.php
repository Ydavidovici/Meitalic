<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {

        $this->call([
            UserSeeder::class,
            ProductSeeder::class,
            ProductImageSeeder::class,
            OrderSeeder::class,
            OrderItemSeeder::class,
            PaymentSeeder::class,
            ConsultationSeeder::class,
            PromoCodeSeeder::class,
            CartSeeder::class,
            CartItemSeeder::class,
            ReviewSeeder::class,
            NewsletterSeeder::class,
        ]);
    }
}
