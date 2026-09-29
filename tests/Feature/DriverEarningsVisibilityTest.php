<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The price of a ride is the operator's business and is never shown to the
 * driver. A driver paid per day is shown what a trip earns him from his own
 * daily rate, worked out from the number of days the trip covers.
 */
class DriverEarningsVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const START_KM = 145320;

    private const PER_DAY_SALARY = 800.00;

    private User $driverUser;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->driverUser = User::factory()->create([
            'name' => 'Ramesh Kumar',
            'email' => null,
            'username' => 'driver080',
            'role' => User::ROLE_DRIVER,
        ]);

        $this->driver = Driver::factory()->perDay()->create([
            'user_id' => $this->driverUser->id,
            'per_day_salary' => self::PER_DAY_SALARY,
        ]);
    }

    /**
     * A confirmed ride of this driver, carrying a rate card that is never shown
     * to the driver.
     *
     * @param  array<string, mixed>  $bookingAttributes
     */
    private function confirmedBooking(array $bookingAttributes = []): Booking
    {
        $city = City::factory()->create();

        $vehicle = Vehicle::factory()->create([
            'name' => 'Tata Nexon',
            'city_id' => $city->id,
            'price_per_day' => 5500,
            'fixed_km_per_day' => 500,
            'price_per_km' => 13,
        ]);

        return Booking::factory()->create(array_merge([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $this->driver->id,
            'status' => BookingStatus::CONFIRMED->value,
        ], $bookingAttributes));
    }

    /**
     * Drive a ride of the given distance and close it the way the driver does.
     */
    private function driveAndEndTrip(Booking $booking, int $kilometres): Booking
    {
        $this->actingAs($this->driverUser)
            ->post(route('driver.trips.start', $booking), [
                'start_odometer_km' => self::START_KM,
                'start_odometer_photo' => UploadedFile::fake()->create('odometer.jpg', 20, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->driverUser)
            ->post(route('driver.trips.end', $booking), [
                'end_odometer_km' => self::START_KM + $kilometres,
                'end_odometer_photo' => UploadedFile::fake()->create('odometer-end.jpg', 20, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors();

        return $booking->fresh();
    }

    public function test_earnings_are_worked_out_from_the_daily_rate_and_the_days_of_the_trip(): void
    {
        // Pickup 1 October, drop 3 October: three days of the hire.
        $booking = $this->confirmedBooking([
            'pickup_date' => '2026-10-01',
            'drop_date' => '2026-10-03',
        ]);

        $earnings = $this->driver->earningsFor($booking);

        $this->assertSame(3, $earnings['days']);
        $this->assertSame(self::PER_DAY_SALARY, $earnings['rate']);
        $this->assertSame(2400.00, $earnings['total']);
    }

    public function test_a_same_day_ride_earnings_one_day(): void
    {
        $earnings = $this->driver->earningsFor($this->confirmedBooking([
            'pickup_date' => '2026-10-01',
            'drop_date' => '2026-10-01',
        ]));

        $this->assertSame(1, $earnings['days']);
        $this->assertSame(self::PER_DAY_SALARY, $earnings['total']);
    }

    public function test_a_permanent_driver_has_no_per_ride_earnings(): void
    {
        $permanent = Driver::factory()->permanent()->create(['monthly_salary' => 15000]);

        $this->assertFalse($permanent->isPaidPerDay());
        $this->assertNull($permanent->earningsFor($this->confirmedBooking()));
    }

    public function test_earnings_add_up_across_rides(): void
    {
        $one = $this->confirmedBooking(['pickup_date' => '2026-10-01', 'drop_date' => '2026-10-01']);
        $two = $this->confirmedBooking(['pickup_date' => '2026-10-05', 'drop_date' => '2026-10-06']);

        // 1 day + 2 days at 800 a day.
        $this->assertSame(2400.00, $this->driver->earningsAcross([$one, $two]));
    }

    public function test_the_trip_sheet_shows_a_per_day_driver_his_earnings_and_not_the_price(): void
    {
        $booking = $this->driveAndEndTrip(
            $this->confirmedBooking(['pickup_date' => '2026-10-01', 'drop_date' => '2026-10-03']),
            1400
        );

        $response = $this->actingAs($this->driverUser)->get(route('driver.trips.show', $booking));

        $response->assertOk();
        $response->assertSee('My Earnings for This Ride');
        $response->assertSee('2,400.00');
        $response->assertSee('3 day(s)');

        // The rate card and the price of the ride stay hidden.
        $response->assertDontSee('What This Ride Will Cost');
        $response->assertDontSee('Total Amount');
        $response->assertDontSee('5,500.00 per day');
        $response->assertDontSee('16,500.00');
    }

    public function test_the_rides_list_shows_total_earnings_for_a_per_day_driver(): void
    {
        $this->driveAndEndTrip(
            $this->confirmedBooking(['pickup_date' => '2026-10-01', 'drop_date' => '2026-10-02']),
            700
        );

        $response = $this->actingAs($this->driverUser)->get(route('driver.rides'));

        $response->assertOk();
        $response->assertSee('My Total Earnings');
        $response->assertSee('1,600.00');
        $response->assertDontSee('Total Billed');
    }

    public function test_a_per_day_driver_without_a_salary_is_told_earnings_cannot_be_worked_out(): void
    {
        $booking = $this->driveAndEndTrip($this->confirmedBooking(), 620);

        // The admin has not set a daily rate on the driver.
        $this->driver->update(['per_day_salary' => null]);

        $this->assertNull($this->driver->fresh()->earningsFor($booking));

        $this->actingAs($this->driverUser);

        // The driver is reached through the cached user -> driver relation, so
        // it is dropped to pick up the salary change just made.
        $this->driverUser->unsetRelation('driver');

        $this->get(route('driver.trips.show', $booking))
            ->assertOk()
            ->assertSee('No per day salary has been set on your profile yet');
    }

    public function test_the_profile_shows_the_driver_his_own_type_and_rate(): void
    {
        $this->actingAs($this->driverUser)->get(route('driver.profile.edit'))
            ->assertOk()
            ->assertSee('Driver Type')
            ->assertSee('Per Day')
            ->assertSee('800.00 per day')
            ->assertDontSee('5,500.00');
    }
}
