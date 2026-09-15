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

class AdminFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicles_index_filters_by_status_city_and_search(): void
    {
        $admin = User::factory()->create();
        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $other = City::factory()->create(['name' => 'Udaipur']);

        Vehicle::factory()->create(['city_id' => $jaipur->id, 'status' => 'Available', 'name' => 'Innova Crysta']);
        Vehicle::factory()->create(['city_id' => $other->id, 'status' => 'Maintenance', 'name' => 'Swift Dzire']);

        $response = $this->actingAs($admin)->get(route('admin.vehicles.index', [
            'status' => 'Available',
            'city_id' => $jaipur->id,
            'search' => 'Innova',
        ]));

        $response->assertOk();
        $response->assertSee('Innova Crysta');
        $response->assertDontSee('Swift Dzire');
    }

    public function test_drivers_index_filters_by_city_and_status(): void
    {
        $admin = User::factory()->create();
        $jaipur = City::factory()->create();
        $other = City::factory()->create();

        Driver::factory()->create(['name' => 'Ramesh Kumar', 'current_city_id' => $jaipur->id, 'status' => 'Available']);
        Driver::factory()->create(['name' => 'Suresh Verma', 'current_city_id' => $other->id, 'status' => 'Inactive']);

        $response = $this->actingAs($admin)->get(route('admin.drivers.index', [
            'city_id' => $jaipur->id,
            'status' => 'Available',
        ]));

        $response->assertOk();
        $response->assertSee('Ramesh Kumar');
        $response->assertDontSee('Suresh Verma');
    }

    public function test_bookings_index_filters_by_status(): void
    {
        $admin = User::factory()->create();
        $city = City::factory()->create();

        $pending = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'status' => BookingStatus::PENDING->value,
        ]);
        $confirmed = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'status' => BookingStatus::CONFIRMED->value,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.bookings.index', [
            'status' => BookingStatus::PENDING->value,
        ]));

        $response->assertOk();
        $response->assertSee($pending->booking_number);
        $response->assertDontSee($confirmed->booking_number);
    }

    public function test_bookings_index_filters_by_pickup_date_range(): void
    {
        $admin = User::factory()->create();
        $city = City::factory()->create();

        $soon = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'pickup_date' => now()->addDays(3)->format('Y-m-d'),
        ]);
        $later = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'pickup_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.bookings.index', [
            'date_from' => now()->format('Y-m-d'),
            'date_to' => now()->addDays(7)->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee($soon->booking_number);
        $response->assertDontSee($later->booking_number);
    }

    public function test_cities_index_filters_by_status(): void
    {
        $admin = User::factory()->create();

        City::factory()->create(['name' => 'Jaipur', 'status' => 'Active']);
        City::factory()->create(['name' => 'Udaipur', 'status' => 'Inactive']);

        $response = $this->actingAs($admin)->get(route('admin.cities.index', ['status' => 'Active']));

        $response->assertOk();
        $response->assertSee('Jaipur');
        $response->assertDontSee('Udaipur');
    }

    public function test_service_types_index_filters_by_status(): void
    {
        $admin = User::factory()->create();

        ServiceType::factory()->create(['name' => 'Airport Transfer', 'status' => 'Active']);
        ServiceType::factory()->create(['name' => 'Corporate Travel', 'status' => 'Inactive']);

        $response = $this->actingAs($admin)->get(route('admin.service-types.index', ['status' => 'Active']));

        $response->assertOk();
        $response->assertSee('Airport Transfer');
        $response->assertDontSee('Corporate Travel');
    }
}
