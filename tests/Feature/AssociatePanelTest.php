<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The associate panel is driven by ownership, not by geography.
 *
 * An associate sees the vehicles, drivers and services he created himself. Being
 * in a city he manages is not enough: a record the admin created, or one another
 * associate created, stays out of his panel even when it sits in his own city.
 */
class AssociatePanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $associate;

    private City $home;

    private City $foreign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->home = City::factory()->create(['name' => 'Jaipur']);
        $this->foreign = City::factory()->create(['name' => 'Nagpur']);

        $this->associate = User::factory()->associate()->create();
        $this->associate->assignedCities()->sync([$this->home->id]);
    }

    public function test_associate_sees_the_vehicles_he_created_and_nothing_else(): void
    {
        $mine = Vehicle::factory()->create([
            'city_id' => $this->home->id,
            'name' => 'AssociateInnova',
            'associate_id' => $this->associate->id,
        ]);
        // The admin's own vehicle, standing in the very same city the associate
        // manages: it belongs to the admin and must stay out of his panel.
        $adminVehicle = Vehicle::factory()->create([
            'city_id' => $this->home->id,
            'name' => 'AdminSwift',
            'associate_id' => null,
        ]);
        $other = Vehicle::factory()->create([
            'city_id' => $this->foreign->id,
            'name' => 'ForeignSwift',
            'associate_id' => null,
        ]);

        $response = $this->actingAs($this->associate)->get(route('associate.vehicles.index'));

        $response->assertOk();
        $response->assertSee('AssociateInnova');
        $response->assertDontSee('AdminSwift');
        $response->assertDontSee('ForeignSwift');

        $this->actingAs($this->associate)->get(route('associate.vehicles.show', $mine))->assertOk();
        $this->actingAs($this->associate)->get(route('associate.vehicles.show', $adminVehicle))->assertForbidden();
        $this->actingAs($this->associate)->get(route('associate.vehicles.show', $other))->assertForbidden();
    }

    public function test_associate_keeps_a_vehicle_he_created_even_after_it_moves_to_another_city(): void
    {
        $vehicle = Vehicle::factory()->create([
            'city_id' => $this->home->id,
            'name' => 'RelocatedInnova',
            'associate_id' => $this->associate->id,
        ]);

        // The vehicle finished a trip in Nagpur, so it now stands there.
        $vehicle->update(['city_id' => $this->foreign->id]);

        $this->actingAs($this->associate)
            ->get(route('associate.vehicles.index'))
            ->assertOk()
            ->assertSee('RelocatedInnova');
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

    public function test_associate_does_not_see_the_admin_drivers_of_his_city(): void
    {
        $mine = Driver::factory()->create([
            'current_city_id' => $this->home->id,
            'name' => 'AssociateDriver',
            'associate_id' => $this->associate->id,
        ]);
        $adminDriver = Driver::factory()->create([
            'current_city_id' => $this->home->id,
            'name' => 'AdminDriver',
            'associate_id' => null,
        ]);
        $other = Driver::factory()->create([
            'current_city_id' => $this->foreign->id,
            'name' => 'ForeignDriver',
            'associate_id' => null,
        ]);

        $response = $this->actingAs($this->associate)->get(route('associate.drivers.index'));

        $response->assertOk();
        $response->assertSee('AssociateDriver');
        $response->assertDontSee('AdminDriver');
        $response->assertDontSee('ForeignDriver');

        $this->actingAs($this->associate)->get(route('associate.drivers.show', $mine))->assertOk();
        $this->actingAs($this->associate)->get(route('associate.drivers.show', $adminDriver))->assertForbidden();
        $this->actingAs($this->associate)->get(route('associate.drivers.show', $other))->assertForbidden();
    }

    public function test_associate_does_not_see_the_admin_services_of_his_city(): void
    {
        $mine = ServiceType::factory()->create([
            'name' => 'AssociateLocalRide',
            'city_id' => $this->home->id,
            'associate_id' => $this->associate->id,
        ]);
        $adminService = ServiceType::factory()->create([
            'name' => 'AdminAirportRide',
            'city_id' => $this->home->id,
            'associate_id' => null,
        ]);
        $global = ServiceType::factory()->create([
            'name' => 'GlobalAirportRide',
            'city_id' => null,
            'associate_id' => null,
        ]);

        $response = $this->actingAs($this->associate)->get(route('associate.service-types.index'));

        $response->assertOk();
        $response->assertSee('AssociateLocalRide');
        $response->assertDontSee('AdminAirportRide');
        $response->assertDontSee('GlobalAirportRide');

        $this->actingAs($this->associate)->get(route('associate.service-types.edit', $mine))->assertOk();
        $this->actingAs($this->associate)->get(route('associate.service-types.edit', $adminService))->assertForbidden();
        $this->actingAs($this->associate)->get(route('associate.service-types.edit', $global))->assertForbidden();
    }

    public function test_associate_records_show_up_in_the_admin_panel(): void
    {
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

        $response = $this->actingAs($this->admin)->get(route('admin.vehicles.index', ['search' => 'AssociateCab']));

        $response->assertOk();
        $response->assertSee('AssociateCab');
        // The admin can see at a glance that this vehicle belongs to the
        // associate rather than being one of his own.
        $response->assertSee($this->associate->name);
    }
}
