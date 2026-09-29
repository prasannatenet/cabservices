<?php

namespace Tests\Feature;

use App\Enums\DriverType;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverTypeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A valid driver payload, overridden per test.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function driverPayload(array $overrides = []): array
    {
        $driver = Driver::factory()->make();

        return array_merge([
            'name' => 'Test Driver',
            'phone' => '+919876543210',
            'license_number' => 'DL-1234',
            'license_expiry' => now()->addYear()->format('Y-m-d'),
            'current_city_id' => $driver->current_city_id,
            'status' => 'Available',
            'driver_type' => DriverType::Permanent->value,
            'monthly_salary' => 15000,
        ], $overrides);
    }

    public function test_admin_can_create_a_permanent_driver_with_a_monthly_salary(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.drivers.store'), $this->driverPayload([
            'driver_type' => DriverType::Permanent->value,
            'monthly_salary' => 18000.50,
        ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.drivers.index'));

        $driver = Driver::where('license_number', 'DL-1234')->firstOrFail();

        $this->assertSame(DriverType::Permanent, $driver->driver_type);
        $this->assertSame(18000.50, (float) $driver->monthly_salary);
        $this->assertNull($driver->per_day_salary);
    }

    public function test_admin_can_create_a_per_day_driver_with_a_daily_salary(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.drivers.store'), $this->driverPayload([
            'driver_type' => DriverType::PerDay->value,
            'monthly_salary' => null,
            'per_day_salary' => 750,
        ]));

        $response->assertSessionHasNoErrors();

        $driver = Driver::where('license_number', 'DL-1234')->firstOrFail();

        $this->assertSame(DriverType::PerDay, $driver->driver_type);
        $this->assertSame(750.0, (float) $driver->per_day_salary);
        $this->assertNull($driver->monthly_salary);
    }

    public function test_a_permanent_driver_requires_a_monthly_salary(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.drivers.store'), $this->driverPayload([
            'driver_type' => DriverType::Permanent->value,
            'monthly_salary' => null,
        ]))->assertSessionHasErrors('monthly_salary');
    }

    public function test_a_per_day_driver_requires_a_daily_salary(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.drivers.store'), $this->driverPayload([
            'driver_type' => DriverType::PerDay->value,
            'monthly_salary' => null,
            'per_day_salary' => null,
        ]))->assertSessionHasErrors('per_day_salary');
    }

    public function test_the_driver_type_is_required(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.drivers.store'), $this->driverPayload([
            'driver_type' => null,
        ]))->assertSessionHasErrors('driver_type');
    }

    public function test_the_driver_type_must_be_a_known_type(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.drivers.store'), $this->driverPayload([
            'driver_type' => 'Weekly',
        ]))->assertSessionHasErrors('driver_type');
    }

    public function test_a_negative_salary_is_rejected(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.drivers.store'), $this->driverPayload([
            'driver_type' => DriverType::Permanent->value,
            'monthly_salary' => -100,
        ]))->assertSessionHasErrors('monthly_salary');
    }

    public function test_switching_a_permanent_driver_to_per_day_drops_the_monthly_salary(): void
    {
        $admin = User::factory()->create();
        $driver = Driver::factory()->permanent()->create(['monthly_salary' => 20000]);

        $this->actingAs($admin)->put(route('admin.drivers.update', $driver), $this->driverPayload([
            'license_number' => $driver->license_number,
            'driver_type' => DriverType::PerDay->value,
            'monthly_salary' => 20000,
            'per_day_salary' => 900,
        ]))->assertSessionHasNoErrors();

        $driver->refresh();

        $this->assertSame(DriverType::PerDay, $driver->driver_type);
        $this->assertSame(900.0, (float) $driver->per_day_salary);
        $this->assertNull($driver->monthly_salary, 'The stale monthly salary must be cleared.');
    }

    public function test_salary_returns_the_figure_matching_the_driver_type(): void
    {
        $permanent = Driver::factory()->permanent()->create(['monthly_salary' => 15000]);
        $perDay = Driver::factory()->perDay()->create(['per_day_salary' => 800]);

        $this->assertSame(15000.0, $permanent->salary());
        $this->assertSame(800.0, $perDay->salary());

        $this->assertSame('15,000.00 per month', $permanent->formattedSalary());
        $this->assertSame('800.00 per day', $perDay->formattedSalary());
    }

    public function test_the_driver_form_exposes_the_type_and_both_salary_fields(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.drivers.create'))
            ->assertStatus(200)
            ->assertSee('name="driver_type"', false)
            ->assertSee('name="monthly_salary"', false)
            ->assertSee('name="per_day_salary"', false)
            ->assertSee('Monthly Salary')
            ->assertSee('Per Day Salary');
    }

    public function test_the_driver_page_shows_the_type_and_salary(): void
    {
        $admin = User::factory()->create();

        $permanent = Driver::factory()->permanent()->create(['monthly_salary' => 15000]);
        $perDay = Driver::factory()->perDay()->create(['per_day_salary' => 800]);

        $this->actingAs($admin)->get(route('admin.drivers.show', $permanent))
            ->assertStatus(200)
            ->assertSee('Driver Type')
            ->assertSee('15,000.00 per month');

        $this->actingAs($admin)->get(route('admin.drivers.show', $perDay))
            ->assertStatus(200)
            ->assertSee('800.00 per day');
    }
}
