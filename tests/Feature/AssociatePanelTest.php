<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssociatePanelTest extends TestCase
{
    use RefreshDatabase;

    private User $associate;

    private City $home;

    private City $foreign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->home = City::factory()->create(['name' => 'Jaipur']);
        $this->foreign = City::factory()->create(['name' => 'Nagpur']);

        $this->associate = User::factory()->associate()->create();
        $this->associate->assignedCities()->sync([$this->home->id]);
    }

    public function test_associate_sees_his_cities_fleet_and_nothing_else(): void
    {
        $mine = Vehicle::factory()->create(['city_id' => $this->home->id, 'name' => 'HomeInnova']);
        $other = Vehicle::factory()->create(['city_id' => $this->foreign->id, 'name' => 'ForeignSwift']);

        $response = $this->actingAs($this->associate)->get(route('associate.vehicles.index'));

        $response->assertOk();
        $response->assertSee('HomeInnova');
        $response->assertDontSee('ForeignSwift');

        $this->actingAs($this->associate)->get(route('associate.vehicles.show', $mine))->assertOk();
        $this->actingAs($this->associate)->get(route('associate.vehicles.show', $other))->assertForbidden();
    }

    public function test_associate_cannot_create_vehicle_in_unassigned_city(): void
    {
        $category = VehicleCategory::factory()->create();

        $response = $this->actingAs($this->associate)->post(route('associate.vehicles.store'), [
            'name' => 'Sneaky Cab',
            'model' => '2024',
            'vehicle_category_id' => $category->id,
            'registration_number' => 'RJ14SN1234',
            'seating_capacity' => 4,
            'city_id' => $this->foreign->id,
            'status' => 'Available',
        ]);

        $response->assertSessionHasErrors('city_id');
        $this->assertDatabaseMissing('vehicles', ['registration_number' => 'RJ14SN1234']);
    }

    public function test_associate_sees_his_cities_drivers_and_nothing_else(): void
    {
        $mine = Driver::factory()->create(['current_city_id' => $this->home->id, 'name' => 'HomeDriver']);
        $other = Driver::factory()->create(['current_city_id' => $this->foreign->id, 'name' => 'ForeignDriver']);

        $response = $this->actingAs($this->associate)->get(route('associate.drivers.index'));

        $response->assertOk();
        $response->assertSee('HomeDriver');
        $response->assertDontSee('ForeignDriver');

        $this->actingAs($this->associate)->get(route('associate.drivers.show', $other))->assertForbidden();
    }

    public function test_associate_sees_his_cities_services_and_not_global_ones(): void
    {
        $mine = ServiceType::factory()->create(['name' => 'JaipurLocalRide', 'city_id' => $this->home->id]);
        $global = ServiceType::factory()->create(['name' => 'GlobalAirportRide', 'city_id' => null]);

        $response = $this->actingAs($this->associate)->get(route('associate.service-types.index'));

        $response->assertOk();
        $response->assertSee('JaipurLocalRide');
        $response->assertDontSee('GlobalAirportRide');

        // A service of another city is forbidden even on direct URL.
        $this->actingAs($this->associate)->get(route('associate.service-types.edit', $mine))->assertOk();
        $this->actingAs($this->associate)->get(route('associate.service-types.edit', $global))->assertForbidden();
    }

    public function test_associate_sees_bookings_of_his_pickup_city_only(): void
    {
        $mine = Booking::factory()->create([
            'pickup_city_id' => $this->home->id,
            'drop_city_id' => $this->home->id,
        ]);
        $other = Booking::factory()->create([
            'pickup_city_id' => $this->foreign->id,
            'drop_city_id' => $this->foreign->id,
        ]);

        $response = $this->actingAs($this->associate)->get(route('associate.bookings.index'));

        $response->assertOk();
        $response->assertSee($mine->booking_number);
        $response->assertDontSee($other->booking_number);

        $this->actingAs($this->associate)->get(route('associate.bookings.show', $other))->assertForbidden();
    }

    public function test_associate_records_show_up_in_the_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = VehicleCategory::factory()->create();

        $this->actingAs($this->associate)->post(route('associate.vehicles.store'), [
            'name' => 'AssociateCab',
            'model' => '2024',
            'vehicle_category_id' => $category->id,
            'registration_number' => 'RJ14AS0001',
            'seating_capacity' => 7,
            'city_id' => $this->home->id,
            'status' => 'Available',
        ])->assertRedirect(route('associate.vehicles.index'));

        $response = $this->actingAs($admin)->get(route('admin.vehicles.index', ['search' => 'AssociateCab']));

        $response->assertOk();
        $response->assertSee('AssociateCab');
    }
}
