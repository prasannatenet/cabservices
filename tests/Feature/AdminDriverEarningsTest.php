<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the admin is shown of a driver's earnings on his activity page.
 *
 * This is the driver's own pay, worked out from his per day rate, not the price
 * the customer was billed: the two come off different rate cards. The figure has
 * to be the same one the driver is shown on his own dashboard, and it must not
 * appear at all for a driver who is not paid per day, because there is then no
 * rate of his own to add up.
 */
class AdminDriverEarningsTest extends TestCase
{
    use RefreshDatabase;

    private const PER_DAY_SALARY = 800.00;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_the_activity_page_adds_up_what_a_per_day_driver_has_earned(): void
    {
        $driver = $this->perDayDriver();

        // One single-day ride and one two-day ride: 1 day + 2 days at 800.
        $this->completedRide($driver, '2026-10-01', '2026-10-01');
        $this->completedRide($driver, '2026-10-05', '2026-10-06');

        $response = $this->actingAs($this->admin)->get(route('admin.driver-activity.show', $driver));

        $response->assertOk();
        // 1 day + 2 days at 800.00 a day = 2,400.00.
        $response->assertSee('2,400.00');
        $response->assertSee('Across 3 billed days');
        $response->assertSee('at 800.00 a day.');
        $response->assertViewHas('earnings', fn (?array $earnings): bool => $earnings !== null
            && $earnings['total'] === 2400.00
            && $earnings['days'] === 3
            && $earnings['rate'] === self::PER_DAY_SALARY);
    }

    public function test_a_driver_who_is_not_paid_per_day_is_shown_no_earnings_at_all(): void
    {
        $driver = Driver::factory()->permanent()->create();

        $this->completedRide($driver, '2026-10-01', '2026-10-03');

        $response = $this->actingAs($this->admin)->get(route('admin.driver-activity.show', $driver));

        $response->assertOk();
        // Nothing of his own can be worked out, so the card is left out rather
        // than showing 0.00, which would read as "he earned nothing".
        $response->assertViewHas('earnings', fn (?array $earnings): bool => $earnings === null);
        $response->assertDontSee('Total Earnings');
    }

    public function test_a_per_day_driver_with_no_daily_rate_is_shown_no_earnings(): void
    {
        $driver = Driver::factory()->perDay()->create(['per_day_salary' => null]);

        $this->completedRide($driver, '2026-10-01', '2026-10-01');

        $response = $this->actingAs($this->admin)->get(route('admin.driver-activity.show', $driver));

        $response->assertOk();
        $response->assertViewHas('earnings', fn (?array $earnings): bool => $earnings === null);
        $response->assertDontSee('Total Earnings');
    }

    public function test_another_drivers_rides_are_not_added_to_his_earnings(): void
    {
        $driver = $this->perDayDriver();
        $other = $this->perDayDriver();

        $this->completedRide($driver, '2026-10-01', '2026-10-01');
        $this->completedRide($other, '2026-10-01', '2026-10-01');

        $response = $this->actingAs($this->admin)->get(route('admin.driver-activity.show', $driver));

        $response->assertOk();
        // Only his own ride, so 1 day at 800.00 rather than 1,600.00.
        $response->assertViewHas('earnings', fn (?array $earnings): bool => $earnings !== null
            && $earnings['total'] === 800.00
            && $earnings['days'] === 1);
    }

    public function test_a_ride_that_has_not_finished_earns_nothing_yet(): void
    {
        $driver = $this->perDayDriver();

        $city = City::factory()->create();

        Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => Vehicle::factory()->create(['city_id' => $city->id])->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::TRIP_STARTED->value,
            'pickup_date' => '2026-10-01',
            'drop_date' => '2026-10-04',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.driver-activity.show', $driver));

        $response->assertOk();
        // A ride still running has earned nothing yet, so the total is 0.00 over
        // 0 days rather than a bill for a hire that has not finished.
        $response->assertViewHas('earnings', fn (?array $earnings): bool => $earnings !== null
            && $earnings['total'] === 0.0
            && $earnings['days'] === 0);
    }

    private function perDayDriver(): Driver
    {
        return Driver::factory()->perDay()->create([
            'per_day_salary' => self::PER_DAY_SALARY,
        ]);
    }

    /**
     * A finished ride of this driver covering the given hire dates.
     */
    private function completedRide(Driver $driver, string $pickup, string $drop): Booking
    {
        $city = City::factory()->create();

        return Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => Vehicle::factory()->create(['city_id' => $city->id])->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::TRIP_COMPLETED->value,
            'pickup_date' => $pickup,
            'drop_date' => $drop,
        ]);
    }
}
