<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Create a predefined admin account
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@meitalic.com',
            'password' => Hash::make('adminpass'),
            'is_admin' => true,
        ]);
    }
}
