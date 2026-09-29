<?php

namespace Database\Factories;

use App\Enums\RideExpenseCategory;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\RideExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RideExpense>
 */
class RideExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'driver_id' => Driver::factory(),
            'category' => RideExpenseCategory::Petrol->value,
            'amount' => $this->faker->randomFloat(2, 200, 5000),
            'bill_number' => strtoupper($this->faker->bothify('BILL-####')),
            'bill_photo' => 'trips/expenses/'.$this->faker->uuid().'.jpg',
            'notes' => $this->faker->sentence(),
            'spent_on' => $this->faker->date(),
        ];
    }
}
