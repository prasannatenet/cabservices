<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\City;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_search_available_cabs()
    {
        $city = City::factory()->create();
        $service = ServiceType::factory()->create();

        $vehicle = Vehicle::factory()->create([
            'city_id' => $city->id,
            'status' => VehicleStatus::AVAILABLE->value,
            'seating_capacity' => 4,
        ]);

        $response = $this->post(route('booking.search'), [
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'pickup_location' => 'Airport',
            'drop_location' => 'Hotel',
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '10:00',
            'passengers' => 2,
            'service_type_id' => $service->id,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('booking.results');
        $response->assertViewHas('vehicles');
    }

    public function test_booking_create_page_renders_with_search_params()
    {
        $city = City::factory()->create();
        $service = ServiceType::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'city_id' => $city->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);

        $response = $this->get(route('booking.create', [
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'pickup_location' => 'Airport',
            'drop_location' => 'Hotel',
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '10:00',
            'passengers' => 2,
            'service_type_id' => $service->id,
            'vehicle_id' => $vehicle->id,
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('booking.create');
        $response->assertViewHas('searchParams');
        $response->assertViewHas('vehicle', fn ($viewVehicle) => $viewVehicle->is($vehicle));
        $response->assertSee('Complete Your Booking');
        $response->assertSee($vehicle->name);
    }
}
