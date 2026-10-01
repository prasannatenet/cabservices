<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DriverStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AssignmentResponseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A booking moves on its own: assigning a driver puts it on Driver Assigned,
 * and the driver accepting his assignment confirms it. Nothing in the admin
 * form sets a status by hand any more.
 */
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

    public function test_assigning_a_driver_puts_the_booking_on_driver_assigned(): void
    {
        [$admin, $booking, $driver] = $this->pendingBookingWithFleet();

        $this->actingAs($admin)->put(route('admin.bookings.update', $booking), [
            'driver_id' => $driver->id,
        ])->assertSessionHasNoErrors();

        // Assigning is what moves the status, not choosing one from a list.
        $this->assertSame(BookingStatus::DRIVER_ASSIGNED, $booking->fresh()->status);
        $this->assertDatabaseHas('driver_assignments', [
            'booking_id' => $booking->id,
            'driver_id' => $driver->id,
        ]);

        // The driver now holds the ride, so he is no longer free for another.
        $this->assertSame(DriverStatus::ASSIGNED->value, $driver->fresh()->status);
    }

    public function test_the_driver_confirming_his_assignment_confirms_the_booking(): void
    {
        [$admin, $booking, $driver] = $this->pendingBookingWithFleet();

        $this->actingAs($admin)->put(route('admin.bookings.update', $booking), [
            'driver_id' => $driver->id,
        ])->assertSessionHasNoErrors();

        // The driver accepts from his own dashboard, which confirms the ride.
        app(AssignmentResponseService::class)->accept($booking->fresh()->driverAssignment);

        $this->assertSame(BookingStatus::CONFIRMED, $booking->fresh()->status);
    }

    public function test_the_admin_cannot_set_a_status_by_hand(): void
    {
        $admin = User::factory()->create();

        $booking = Booking::factory()->create([
            'pickup_city_id' => City::factory()->create()->id,
            'drop_city_id' => City::factory()->create()->id,
            'status' => BookingStatus::PENDING->value,
        ]);

        // A hand-crafted post trying to jump straight to a finished ride is
        // ignored, because the endpoint only accepts a driver and a vehicle.
        $this->actingAs($admin)->put(route('admin.bookings.update', $booking), [
            'status' => BookingStatus::TRIP_COMPLETED->value,
        ])->assertSessionHasNoErrors();

        $this->assertSame(BookingStatus::PENDING, $booking->fresh()->status);
    }

    /**
     * A pending booking with an available driver and vehicle standing in its
     * pickup city, which is the only place the admin may pick them from.
     *
     * @return array{0: User, 1: Booking, 2: Driver}
     */
    private function pendingBookingWithFleet(): array
    {
        $admin = User::factory()->create();

        $city = City::factory()->create();

        $vehicle = Vehicle::factory()->create([
            'status' => 'Available',
            'city_id' => $city->id,
        ]);

        $driver = Driver::factory()->create([
            'status' => 'Available',
            'current_city_id' => $city->id,
        ]);

        $booking = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'service_type_id' => ServiceType::factory()->create()->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::PENDING->value,
        ]);

        return [$admin, $booking, $driver];
    }
}
