<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'India',
            'latitude' => fake()->latitude(34, 8),
            'longitude' => fake()->longitude(97, 68),
            'status' => 'Active',
        ];
    }
}
