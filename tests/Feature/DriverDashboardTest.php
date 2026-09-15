<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a user account with the driver role linked to a driver profile.
     *
     * @return array{0: User, 1: Driver}
     */
    private function createDriverAccount(array $driverAttributes = []): array
    {
        $user = User::factory()->create([
            'name' => 'Test Driver',
            'email' => null,
            'username' => 'driver001',
            'role' => User::ROLE_DRIVER,
        ]);

        $driver = Driver::factory()->create($driverAttributes + ['user_id' => $user->id]);

        return [$user, $driver];
    }

    public function test_driver_can_login_with_login_id(): void
    {
        [$user] = $this->createDriverAccount();

        $response = $this->post('/login', [
            'email' => 'driver001',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_still_login_with_email(): void
    {
        $admin = User::factory()->create(['username' => 'admin']);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_dashboard_redirects_driver_to_driver_dashboard(): void
    {
        [$user] = $this->createDriverAccount();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('driver.dashboard', absolute: false));
    }

    public function test_dashboard_redirects_admin_to_admin_dashboard(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_driver_can_view_dashboard(): void
    {
        [$user] = $this->createDriverAccount();

        $response = $this->actingAs($user)->get(route('driver.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('My Availability');
    }

    public function test_driver_can_toggle_availability(): void
    {
        [$user, $driver] = $this->createDriverAccount(['status' => 'Available']);

        $response = $this->actingAs($user)->post(route('driver.availability.toggle'));

        $response->assertRedirect();
        $this->assertDatabaseHas('drivers', ['id' => $driver->id, 'status' => 'Unavailable']);

        $this->actingAs($user)->post(route('driver.availability.toggle'));
        $this->assertDatabaseHas('drivers', ['id' => $driver->id, 'status' => 'Available']);
    }

    public function test_driver_cannot_toggle_availability_when_admin_manages_status(): void
    {
        [$user, $driver] = $this->createDriverAccount(['status' => 'Assigned']);

        $response = $this->actingAs($user)->post(route('driver.availability.toggle'));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('drivers', ['id' => $driver->id, 'status' => 'Assigned']);
    }

    public function test_driver_can_view_ride_history(): void
    {
        [$user, $driver] = $this->createDriverAccount();

        $city = City::factory()->create();
        $service = ServiceType::factory()->create();

        $booking = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'service_type_id' => $service->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::TRIP_COMPLETED->value,
        ]);

        $response = $this->actingAs($user)->get(route('driver.rides'));

        $response->assertStatus(200);
        $response->assertSee($booking->booking_number);
    }

    public function test_driver_cannot_see_other_drivers_rides(): void
    {
        [$user] = $this->createDriverAccount();

        $otherDriver = Driver::factory()->create();

        $city = City::factory()->create();
        $service = ServiceType::factory()->create();

        $booking = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'service_type_id' => $service->id,
            'driver_id' => $otherDriver->id,
        ]);

        $response = $this->actingAs($user)->get(route('driver.rides'));

        $response->assertStatus(200);
        $response->assertDontSee($booking->booking_number);
    }

    public function test_driver_can_manage_preferred_cities(): void
    {
        [$user, $driver] = $this->createDriverAccount();

        $selectedCities = City::factory()->count(2)->create();
        $ignoredCity = City::factory()->create();

        $response = $this->actingAs($user)->post(route('driver.cities.sync'), [
            'city_ids' => $selectedCities->pluck('id')->all(),
        ]);

        $response->assertRedirect(route('driver.cities', absolute: false));
        $response->assertSessionHasNoErrors();

        foreach ($selectedCities as $city) {
            $this->assertDatabaseHas('driver_city', ['driver_id' => $driver->id, 'city_id' => $city->id]);
        }
        $this->assertDatabaseMissing('driver_city', ['driver_id' => $driver->id, 'city_id' => $ignoredCity->id]);

        $viewResponse = $this->actingAs($user)->get(route('driver.cities'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee($selectedCities->first()->name);
    }

    public function test_driver_cannot_select_unknown_cities(): void
    {
        [$user] = $this->createDriverAccount();

        $response = $this->actingAs($user)->post(route('driver.cities.sync'), [
            'city_ids' => [9999],
        ]);

        $response->assertSessionHasErrors('city_ids.0');
    }

    public function test_driver_cannot_access_admin_routes(): void
    {
        [$user] = $this->createDriverAccount();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertRedirect(route('driver.dashboard', absolute: false));
    }

    public function test_admin_cannot_access_driver_routes(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('driver.dashboard'));

        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_admin_can_create_driver_with_login_account(): void
    {
        $city = City::factory()->create();

        $response = $this->actingAs(User::factory()->create())->post(route('admin.drivers.store'), [
            'name' => 'Ramesh Kumar',
            'phone' => '9876543210',
            'license_number' => 'DL-12345678',
            'license_expiry' => now()->addYears(3)->toDateString(),
            'current_city_id' => $city->id,
            'status' => 'Available',
            'login_id' => 'ramesh123',
            'login_password' => 'secret1234',
        ]);

        $response->assertRedirect(route('admin.drivers.index', absolute: false));
        $response->assertSessionHasNoErrors();

        $driver = Driver::where('license_number', 'DL-12345678')->first();

        $this->assertDatabaseHas('users', [
            'username' => 'ramesh123',
            'role' => User::ROLE_DRIVER,
        ]);

        $user = $driver->user;
        $this->assertNotNull($user);
        $this->assertSame('ramesh123', $user->username);

        // The driver can now login with the credentials given by the admin.
        $this->post('/logout');
        $loginResponse = $this->post('/login', [
            'email' => 'ramesh123',
            'password' => 'secret1234',
        ]);

        $loginResponse->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_update_driver_login_credentials(): void
    {
        [$admin, $driver] = $this->createDriverAccount();

        $response = $this->actingAs(User::factory()->create())->put(route('admin.drivers.update', $driver), [
            'name' => $driver->name,
            'phone' => $driver->phone,
            'license_number' => $driver->license_number,
            'license_expiry' => $driver->license_expiry,
            'current_city_id' => $driver->current_city_id,
            'status' => 'Available',
            'login_id' => 'driver002',
            'login_password' => 'newsecret123',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'id' => $driver->user->id,
            'username' => 'driver002',
        ]);
    }
}
