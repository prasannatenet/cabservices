<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'username' => str_replace('.', '_', fake()->unique()->userName()),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is a driver.
     */
    public function driver(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_DRIVER,
        ]);
    }

    /**
     * Indicate that the user is an associate (a sub-admin for a set of cities).
     */
    public function associate(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ASSOCIATE,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
