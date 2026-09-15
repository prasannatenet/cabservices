<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Toyota Innova', 'Suzuki Dzire', 'Honda City', 'Tata Nexon', 'Innova Crysta']),
            'model' => fake()->year(),
            'registration_number' => strtoupper(fake()->bothify('??##??####')),
            'reference_number' => strtoupper(fake()->bothify('V-####')),
            'seating_capacity' => fake()->randomElement([4, 6, 7]),
            'vehicle_category_id' => VehicleCategory::factory(),
            'city_id' => City::factory(),
            'features' => 'AC, Music System, Airbags',
            'status' => 'Available',
        ];
    }
}
