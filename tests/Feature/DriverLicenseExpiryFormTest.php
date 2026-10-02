<?php

namespace Tests\Feature;

use App\Enums\DriverType;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The licence expiry date is a date column, so the model hands it to the form as a
 * date object. A date input only accepts the plain Y-m-d value, so the edit form has
 * to format it, otherwise the browser shows an empty box and the admin has to retype
 * the expiry date on every save.
 */
class DriverLicenseExpiryFormTest extends TestCase
{
    use RefreshDatabase;

    private function driverPayload(Driver $driver, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Driver',
            'phone' => '+919876543210',
            'license_number' => $driver->license_number,
            'license_expiry' => now()->addYear()->format('Y-m-d'),
            'current_city_id' => $driver->current_city_id,
            'status' => 'Available',
            'driver_type' => DriverType::Permanent->value,
            'monthly_salary' => 15000,
        ], $overrides);
    }

    public function test_the_edit_form_fills_the_licence_expiry_input_with_a_date_the_browser_accepts(): void
    {
        $admin = User::factory()->create();
        $driver = Driver::factory()->create(['license_expiry' => '2031-04-15']);

        $this->actingAs($admin)->get(route('admin.drivers.edit', $driver))
            ->assertStatus(200)
            ->assertSee('name="license_expiry"', false)
            ->assertSee('value="2031-04-15"', false);
    }

    public function test_the_edit_form_does_not_put_a_datetime_into_the_date_input(): void
    {
        $admin = User::factory()->create();
        $driver = Driver::factory()->create(['license_expiry' => '2031-04-15']);

        // The raw cast value is "2031-04-15 00:00:00", which a date input rejects.
        $this->actingAs($admin)->get(route('admin.drivers.edit', $driver))
            ->assertStatus(200)
            ->assertDontSee('value="2031-04-15 00:00:00"', false);
    }

    public function test_an_admin_can_resave_a_driver_without_retyping_the_licence_expiry(): void
    {
        $admin = User::factory()->create();
        $driver = Driver::factory()->create(['license_expiry' => '2031-04-15']);

        $this->actingAs($admin)->put(route('admin.drivers.update', $driver), $this->driverPayload($driver, [
            'license_expiry' => '2031-04-15',
            'name' => 'Renamed Driver',
        ]))->assertSessionHasNoErrors();

        $driver->refresh();

        $this->assertSame('2031-04-15', $driver->license_expiry->format('Y-m-d'));
        $this->assertSame('Renamed Driver', $driver->name);
    }

    public function test_an_admin_can_change_the_licence_expiry_from_the_edit_form(): void
    {
        $admin = User::factory()->create();
        $driver = Driver::factory()->create(['license_expiry' => '2031-04-15']);

        $this->actingAs($admin)->put(route('admin.drivers.update', $driver), $this->driverPayload($driver, [
            'license_expiry' => '2032-09-30',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('2032-09-30', $driver->fresh()->license_expiry->format('Y-m-d'));
    }
}
