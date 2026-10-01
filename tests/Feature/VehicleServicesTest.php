<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A vehicle is put on the services it provides, and a vehicle can provide
 * several of them. The admin picks them one at a time from a dropdown, and the
 * picks are listed above it on the form.
 */
class VehicleServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_put_a_vehicle_on_several_services(): void
    {
        $admin = User::factory()->create();
        $outstation = ServiceType::factory()->create(['name' => 'Outstation']);
        $airport = ServiceType::factory()->create(['name' => 'Airport Transfer']);
        $vehicle = Vehicle::factory()->make();

        $response = $this->actingAs($admin)->post(route('admin.vehicles.store'), $this->vehiclePayload($vehicle) + [
            'services_selected' => 1,
            'service_ids' => [$outstation->id, $airport->id],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.vehicles.index'));

        $created = Vehicle::where('registration_number', $vehicle->registration_number)->firstOrFail();

        $this->assertCount(2, $created->services);
        $this->assertEqualsCanonicalizing(
            [$outstation->id, $airport->id],
            $created->services->pluck('id')->all()
        );
        // The other way round: the service knows the vehicles it is offered by.
        $this->assertTrue($outstation->vehicles->contains($created));
    }

    public function test_admin_can_take_a_service_off_a_vehicle(): void
    {
        $admin = User::factory()->create();
        $outstation = ServiceType::factory()->create(['name' => 'Outstation']);
        $airport = ServiceType::factory()->create(['name' => 'Airport Transfer']);
        $vehicle = Vehicle::factory()->create();
        $vehicle->services()->attach([$outstation->id, $airport->id]);

        $response = $this->actingAs($admin)->put(route('admin.vehicles.update', $vehicle), $this->vehiclePayload($vehicle) + [
            'services_selected' => 1,
            'service_ids' => [$outstation->id],
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame([$outstation->id], $vehicle->fresh()->services->pluck('id')->all());
    }

    public function test_the_fleet_form_offers_every_active_service(): void
    {
        $admin = User::factory()->create();
        ServiceType::factory()->create(['name' => 'Outstation', 'status' => 'Active']);
        ServiceType::factory()->create(['name' => 'Retired Service', 'status' => 'Inactive']);
        // A name with an ampersand, which is the kind of thing that breaks an
        // attribute the picker is written into.
        ServiceType::factory()->create(['name' => 'Out & Station', 'status' => 'Active']);

        $response = $this->actingAs($admin)->get(route('admin.vehicles.create'));

        $response->assertStatus(200);
        $response->assertSee('Services Provided');
        $response->assertSee('x-data="servicePicker(', false);
        $response->assertSee('Outstation');
        // The picker is handed the services as JSON inside the attribute. The
        // JSON sits in a quoted string of its own, so a character like "&" is
        // escaped twice over and comes out as a double backslash.
        $response->assertSee('Out \\\\u0026 Station', false);
        $response->assertDontSee('Retired Service');
    }

    public function test_the_edit_form_starts_with_the_services_the_vehicle_is_on(): void
    {
        $admin = User::factory()->create();
        $outstation = ServiceType::factory()->create(['name' => 'Outstation']);
        $vehicle = Vehicle::factory()->create();
        $vehicle->services()->attach($outstation);

        $response = $this->actingAs($admin)->get(route('admin.vehicles.edit', $vehicle));

        $response->assertStatus(200);
        $response->assertSee('Services Provided');
        // The picker is handed the service it already runs, so it opens with it
        // listed as chosen rather than waiting to be picked again.
        $response->assertSee($outstation->name);
        $response->assertSee('name="services_selected"', false);
    }

    public function test_an_associate_can_put_his_own_vehicle_on_a_service(): void
    {
        $city = City::factory()->create();
        $associate = User::factory()->associate()->create();
        $associate->assignedCities()->sync([$city->id]);

        $outstation = ServiceType::factory()->create(['name' => 'Outstation']);
        $vehicle = Vehicle::factory()->make(['city_id' => $city->id]);

        $response = $this->actingAs($associate)->post(route('associate.vehicles.store'), $this->vehiclePayload($vehicle) + [
            'services_selected' => 1,
            'service_ids' => [$outstation->id],
        ]);

        $response->assertSessionHasNoErrors();

        $created = Vehicle::where('registration_number', $vehicle->registration_number)->firstOrFail();

        $this->assertSame([$outstation->id], $created->services->pluck('id')->all());
    }

    /**
     * The vehicle fields every fleet form asks for, whatever else is under test.
     *
     * @return array<string, mixed>
     */
    private function vehiclePayload(Vehicle $vehicle): array
    {
        return [
            'name' => $vehicle->name,
            'model' => $vehicle->model,
            'vehicle_category_id' => $vehicle->vehicle_category_id,
            'registration_number' => $vehicle->registration_number,
            'seating_capacity' => $vehicle->seating_capacity,
            'city_id' => $vehicle->city_id,
            'status' => 'Available',
        ];
    }
}
