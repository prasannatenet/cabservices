<?php

namespace Database\Factories;

use App\Models\VehicleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleCategory>
 */
class VehicleCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Hatchback', 'Sedan', 'SUV', 'MUV', 'Luxury']),
            'description' => fake()->sentence(),
            'default_seating_capacity' => fake()->randomElement([4, 5, 6, 7]),
            'status' => 'active',
        ];
    }
}
