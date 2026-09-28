<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceType>
 */
class ServiceTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Airport Transfer', 'Local cab', 'Outstation', 'Round Trip', 'Corporate']),
            'description' => fake()->sentence(),
            'status' => 'Active',
            'display_order' => fake()->numberBetween(1, 100),
            'city_id' => null, // null means global service
            'created_by' => null, // null means system-created
            'is_approved' => true,
        ];
    }

    /**
     * Indicate that the service is created by an admin.
     */
    public function createdByAdmin(User $admin): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $admin->id,
            'city_id' => null, // Global service
            'is_approved' => true,
        ]);
    }

    /**
     * Indicate that the service is created by an associate for a specific city.
     */
    public function createdByAssociate(User $associate, City $city): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $associate->id,
            'city_id' => $city->id,
            'is_approved' => true,
        ]);
    }
}
