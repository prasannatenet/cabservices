<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_number' => 'BKG-'.strtoupper($this->faker->unique()->bothify('????####')),
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'pickup_location' => $this->faker->streetAddress(),
            'drop_location' => $this->faker->streetAddress(),
            'pickup_date' => $this->faker->date(),
            'pickup_time' => $this->faker->time('H:i'),
            'passengers' => $this->faker->numberBetween(1, 4),
            'status' => BookingStatus::PENDING->value,
        ];
    }
}
