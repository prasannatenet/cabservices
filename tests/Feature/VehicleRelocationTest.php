<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleRelocationTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $availability;

    private BookingService $bookingService;

    private City $jaipur;

    private City $udaipur;

    private ServiceType $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availability = new AvailabilityService;
        $this->bookingService = new BookingService;
        $this->jaipur = City::factory()->create(['name' => 'Jaipur']);
        $this->udaipur = City::factory()->create(['name' => 'Udaipur']);
        $this->service = ServiceType::factory()->create();
    }

    public function test_vehicle_becomes_available_at_drop_city_after_trip(): void
    {
        $vehicle = Vehicle::factory()->create(['city_id' => $this->jaipur->id]);

        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'pickup_city_id' => $this->jaipur->id,
            'drop_city_id' => $this->udaipur->id,
            'pickup_date' => now()->subDays(3)->format('Y-m-d'),
            'pickup_time' => '08:00',
            'drop_date' => now()->subDay()->format('Y-m-d'),
            'drop_time' => '18:00',
            'status' => BookingStatus::TRIP_COMPLETED->value,
        ]);

        $inUdaipur = $this->availability->searchAvailableVehicles(
            $this->udaipur->id,
            now()->addDay()->format('Y-m-d'),
            '10:00',
            2,
            $this->service->id
        );

        $inJaipur = $this->availability->searchAvailableVehicles(
            $this->jaipur->id,
            now()->addDay()->format('Y-m-d'),
            '10:00',
            2,
            $this->service->id
        );

        $this->assertTrue($inUdaipur->contains('id', $vehicle->id), 'Vehicle should be available in Udaipur after the trip ended there.');
        $this->assertFalse($inJaipur->contains('id', $vehicle->id), 'Vehicle should no longer be available in Jaipur.');
    }

    public function test_vehicle_on_active_trip_is_unavailable_anywhere(): void
    {
        $vehicle = Vehicle::factory()->create(['city_id' => $this->jaipur->id]);

        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'pickup_city_id' => $this->jaipur->id,
            'drop_city_id' => $this->udaipur->id,
            'pickup_date' => now()->format('Y-m-d'),
            'pickup_time' => '08:00',
            'drop_date' => now()->addDays(2)->format('Y-m-d'),
            'drop_time' => '18:00',
            'status' => BookingStatus::CONFIRMED->value,
        ]);

        $inUdaipur = $this->availability->searchAvailableVehicles(
            $this->udaipur->id,
            now()->addDay()->format('Y-m-d'),
            '10:00',
            2,
            $this->service->id
        );

        $inJaipur = $this->availability->searchAvailableVehicles(
            $this->jaipur->id,
            now()->addDay()->format('Y-m-d'),
            '10:00',
            2,
            $this->service->id
        );

        $this->assertFalse($inUdaipur->contains('id', $vehicle->id), 'Vehicle on an active trip must not be available mid-trip.');
        $this->assertFalse($inJaipur->contains('id', $vehicle->id), 'Vehicle on an active trip must not be available in its base city.');
    }

    public function test_busy_window_uses_actual_drop_date_instead_of_buffer(): void
    {
        $vehicle = Vehicle::factory()->create(['city_id' => $this->jaipur->id]);

        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'pickup_city_id' => $this->jaipur->id,
            'drop_city_id' => $this->udaipur->id,
            'pickup_date' => now()->format('Y-m-d'),
            'pickup_time' => '10:00',
            'drop_date' => now()->addDays(2)->format('Y-m-d'),
            'drop_time' => '15:00',
            'status' => BookingStatus::CONFIRMED->value,
        ]);

        // The old 12h buffer would consider the vehicle free on day +1;
        // the actual drop date keeps it busy until day +2.
        $search = $this->availability->searchAvailableVehicles(
            $this->jaipur->id,
            now()->addDay()->format('Y-m-d'),
            '10:00',
            2,
            $this->service->id
        );

        $this->assertFalse($search->contains('id', $vehicle->id), 'Vehicle must stay busy until the actual drop date.');
    }

    public function test_vehicle_without_trips_stays_in_base_city(): void
    {
        $vehicle = Vehicle::factory()->create(['city_id' => $this->jaipur->id]);

        $inJaipur = $this->availability->searchAvailableVehicles(
            $this->jaipur->id,
            now()->addDay()->format('Y-m-d'),
            '10:00',
            2,
            $this->service->id
        );

        $inUdaipur = $this->availability->searchAvailableVehicles(
            $this->udaipur->id,
            now()->addDay()->format('Y-m-d'),
            '10:00',
            2,
            $this->service->id
        );

        $this->assertTrue($inJaipur->contains('id', $vehicle->id));
        $this->assertFalse($inUdaipur->contains('id', $vehicle->id));
    }

    public function test_completing_trip_relocates_vehicle_and_driver_to_drop_city(): void
    {
        $admin = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['city_id' => $this->jaipur->id]);
        $driver = Driver::factory()->create(['current_city_id' => $this->jaipur->id]);

        $booking = Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'pickup_city_id' => $this->jaipur->id,
            'drop_city_id' => $this->udaipur->id,
            'pickup_date' => now()->format('Y-m-d'),
            'pickup_time' => '08:00',
            'drop_date' => now()->addDay()->format('Y-m-d'),
            'drop_time' => '18:00',
            'status' => BookingStatus::TRIP_STARTED->value,
        ]);

        $this->bookingService->completeTrip($booking, $admin->id);

        $booking->refresh();

        $this->assertSame(BookingStatus::TRIP_COMPLETED, $booking->status);
        $this->assertSame($this->udaipur->id, $vehicle->fresh()->city_id);
        $this->assertSame($this->udaipur->id, $driver->fresh()->current_city_id);
    }
}
