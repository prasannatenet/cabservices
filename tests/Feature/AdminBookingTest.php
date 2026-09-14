<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_bookings()
    {
        $admin = User::factory()->create();

        $city = City::factory()->create();
        $service = ServiceType::factory()->create();

        $booking = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'service_type_id' => $service->id,
            'status' => BookingStatus::PENDING->value,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.bookings.index'));
        $response->assertStatus(200);
        $response->assertSee($booking->booking_number);
    }

    public function test_admin_can_assign_driver_and_confirm()
    {
        $admin = User::factory()->create();

        $city = City::factory()->create();
        $service = ServiceType::factory()->create();

        $vehicle = Vehicle::factory()->create(['status' => 'Available']);
        $driver = Driver::factory()->create(['status' => 'Available']);

        $booking = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'service_type_id' => $service->id,
            'status' => BookingStatus::PENDING->value,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.bookings.update', $booking), [
            'status' => BookingStatus::CONFIRMED->value,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(BookingStatus::CONFIRMED, $booking->fresh()->status);
        $this->assertDatabaseHas('driver_assignments', [
            'booking_id' => $booking->id,
            'driver_id' => $driver->id,
        ]);
    }
}
