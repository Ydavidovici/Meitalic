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

        \App\Models\User::factory()->regular()->create();
        \App\Models\User::factory()->admin()->create();
    }
}
