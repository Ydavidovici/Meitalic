<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Newsletter;

class NewsletterSeeder extends Seeder
{
    public function run()
    {
        // Create 10 sample newsletters
        Newsletter::factory()->count(10)->create();
    }
}
