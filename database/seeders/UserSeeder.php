<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Site admin
        User::updateOrCreate(
            ['email' => 'admin@meitalic.test'],
            [
                'name'               => 'Site Admin',
                'email_verified_at'  => now(),
                'password'           => Hash::make('AdminPass123'),
                'remember_token'     => Str::random(10),
                'is_admin'           => true,
                'is_subscribed'      => false,
                'subscribed_at'      => null,
            ]
        );

        // 2) Regular user
        User::updateOrCreate(
            ['email' => 'user@meitalic.test'],
            [
                'name'               => 'Regular User',
                'email_verified_at'  => now(),
                'password'           => Hash::make('UserPass123'),
                'remember_token'     => Str::random(10),
                'is_admin'           => false,
                'is_subscribed'      => false,
                'subscribed_at'      => null,
            ]
        );

        // 3) A handful of random dev users
        //    `safeEmail()` in the factory will guarantee uniqueness here.
        User::factory()
            ->count(10)
            ->create();
    }
}
