<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssociateManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_admin_can_create_an_associate_with_cities_and_login(): void
    {
        $admin = $this->admin();
        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $udaipur = City::factory()->create(['name' => 'Udaipur']);

        $response = $this->actingAs($admin)->post(route('admin.associates.store'), [
            'name' => 'City Manager',
            'login_id' => 'city_manager',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'city_ids' => [$jaipur->id, $udaipur->id],
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('admin.associates.index'));

        $associate = User::where('username', 'city_manager')->firstOrFail();
        $this->assertTrue($associate->isAssociate());
        $this->assertEqualsCanonicalizing(
            [$jaipur->id, $udaipur->id],
            $associate->assignedCityIds()
        );
    }

    public function test_associate_needs_at_least_one_city(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.associates.store'), [
            'name' => 'No City',
            'login_id' => 'no_city',
            'password' => 'password123',
            'city_ids' => [],
            'status' => 'Active',
        ]);

        $response->assertSessionHasErrors('city_ids');
        $this->assertDatabaseMissing('users', ['username' => 'no_city']);
    }

    public function test_associate_login_id_must_be_unique(): void
    {
        $city = City::factory()->create();
        User::factory()->associate()->create(['username' => 'taken_id']);

        $response = $this->actingAs($this->admin())->post(route('admin.associates.store'), [
            'name' => 'Duplicate',
            'login_id' => 'taken_id',
            'password' => 'password123',
            'city_ids' => [$city->id],
            'status' => 'Active',
        ]);

        $response->assertSessionHasErrors('login_id');
    }

    public function test_associate_is_redirected_to_his_own_dashboard(): void
    {
        $associate = User::factory()->associate()->create();

        $this->actingAs($associate)->get(route('dashboard'))->assertRedirect(route('associate.dashboard'));
        $this->actingAs($associate)->get(route('associate.dashboard'))->assertOk();
    }

    public function test_associate_can_never_open_the_admin_panel(): void
    {
        $associate = User::factory()->associate()->create();

        $this->actingAs($associate)->get(route('admin.dashboard'))
            ->assertRedirect(route('associate.dashboard'));

        $this->actingAs($associate)->get(route('admin.associates.index'))
            ->assertRedirect(route('associate.dashboard'));
    }

    public function test_inactive_associate_is_logged_out(): void
    {
        $associate = User::factory()->associate()->create(['status' => 'Inactive']);

        $this->actingAs($associate)->get(route('associate.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_can_update_associate_cities(): void
    {
        $admin = $this->admin();
        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $kota = City::factory()->create(['name' => 'Kota']);

        $associate = User::factory()->associate()->create();
        $associate->assignedCities()->sync([$jaipur->id]);

        $response = $this->actingAs($admin)->put(route('admin.associates.update', $associate), [
            'name' => $associate->name,
            'login_id' => $associate->username ?? 'manager_'.uniqid(),
            'password' => '',
            'city_ids' => [$kota->id],
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('admin.associates.index'));
        $this->assertEquals([$kota->id], $associate->fresh()->assignedCityIds());
    }

    public function test_deleting_associate_keeps_his_fleet_visible_to_admin(): void
    {
        $admin = $this->admin();
        $city = City::factory()->create();

        $associate = User::factory()->associate()->create();
        $associate->assignedCities()->sync([$city->id]);
        $vehicle = Vehicle::factory()->create([
            'city_id' => $city->id,
            'created_by' => $associate->id,
        ]);

        $this->actingAs($admin)->delete(route('admin.associates.destroy', $associate))
            ->assertRedirect(route('admin.associates.index'));

        $this->assertDatabaseMissing('users', ['id' => $associate->id]);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'created_by' => null]);
    }
}
