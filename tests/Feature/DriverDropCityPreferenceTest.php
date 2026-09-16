<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverDropCityPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dropdown_hides_drivers_not_willing_to_go_to_drop_city(): void
    {
        $admin = User::factory()->create();
        $pickupCity = City::factory()->create(['name' => 'Jaipur']);
        $dropCity = City::factory()->create(['name' => 'Udaipur']);
        $otherCity = City::factory()->create(['name' => 'Delhi']);
        $service = ServiceType::factory()->create();

        $willingDriver = Driver::factory()->create(['status' => 'Available', 'current_city_id' => $pickupCity->id]);
        $willingDriver->preferredCities()->sync([$dropCity->id]);

        $unwillingDriver = Driver::factory()->create(['status' => 'Available', 'current_city_id' => $pickupCity->id]);
        $unwillingDriver->preferredCities()->sync([$otherCity->id]);

        $noPreferenceDriver = Driver::factory()->create(['status' => 'Available', 'current_city_id' => $pickupCity->id]);

        $booking = Booking::factory()->create([
            'pickup_city_id' => $pickupCity->id,
            'drop_city_id' => $dropCity->id,
            'service_type_id' => $service->id,
            'status' => BookingStatus::PENDING->value,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.bookings.show', $booking));
        $response->assertStatus(200);

        $availableDrivers = $response->viewData('availableDrivers');

        $this->assertTrue($availableDrivers->contains('id', $willingDriver->id));
        $this->assertTrue($availableDrivers->contains('id', $noPreferenceDriver->id));
        $this->assertFalse($availableDrivers->contains('id', $unwillingDriver->id));
    }

    public function test_admin_cannot_assign_driver_not_willing_to_go_to_drop_city(): void
    {
        $admin = User::factory()->create();
        $pickupCity = City::factory()->create();
        $dropCity = City::factory()->create();
        $otherCity = City::factory()->create();
        $service = ServiceType::factory()->create();
        $vehicle = Vehicle::factory()->create(['status' => 'Available']);

        $unwillingDriver = Driver::factory()->create(['status' => 'Available']);
        $unwillingDriver->preferredCities()->sync([$otherCity->id]);

        $booking = Booking::factory()->create([
            'pickup_city_id' => $pickupCity->id,
            'drop_city_id' => $dropCity->id,
            'service_type_id' => $service->id,
            'status' => BookingStatus::PENDING->value,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.bookings.update', $booking), [
            'status' => BookingStatus::CONFIRMED->value,
            'driver_id' => $unwillingDriver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertNotEquals(BookingStatus::CONFIRMED, $booking->fresh()->status);
    }

    public function test_booking_service_rejects_unwilling_driver(): void
    {
        $pickupCity = City::factory()->create();
        $dropCity = City::factory()->create();
        $otherCity = City::factory()->create();
        $service = ServiceType::factory()->create();

        $unwillingDriver = Driver::factory()->create(['status' => 'Available']);
        $unwillingDriver->preferredCities()->sync([$otherCity->id]);

        $booking = Booking::factory()->create([
            'pickup_city_id' => $pickupCity->id,
            'drop_city_id' => $dropCity->id,
            'service_type_id' => $service->id,
            'status' => BookingStatus::PENDING->value,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/not willing to go to/');

        app(BookingService::class)->assignDriver($booking, $unwillingDriver->id, 1);
    }
}
