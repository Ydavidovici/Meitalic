<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'is_admin'          => false,
            'is_subscribed'     => false,
            'subscribed_at'     => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attrs) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Seed a “site admin” user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attrs) => [
            'name'               => 'Site Admin',
            'email'              => 'admin@meitalic.test',
            'password'           => Hash::make('AdminPass123'),
            'is_admin'           => true,
            'email_verified_at'  => now(),
        ]);
    }

    /**
     * Seed a “regular” user with a known email+password.
     */
    public function regular(): static
    {
        return $this->state(fn (array $attrs) => [
            'name'               => 'Regular User',
            'email'              => 'user@meitalic.test',
            'password'           => Hash::make('UserPass123'),
            'is_admin'           => false,
            'email_verified_at'  => now(),
        ]);
    }
}
