<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A finished ride is billed from the rate card of the vehicle that drove it: the
 * price of a day covers its fixed kilometres and every kilometre above that is
 * charged at the per kilometre rate.
 *
 * The Tata Nexon of the fleet is on 5,500 for a day of 500 km and 13 for every
 * kilometre above that, so a ride of 412 km is billed 5,500 and a ride of 620 km
 * is billed 5,500 + 120 x 13 = 7,060. Both the driver on his trip sheet and the
 * admin on the booking screen see the amount and the figures it came out of.
 */
class DriverTripFareTest extends TestCase
{
    use RefreshDatabase;

    private const START_KM = 145320;

    private const PRICE_PER_DAY = '5500.00';

    private const FIXED_KM_PER_DAY = 500;

    private const PRICE_PER_KM = '13.00';

    private User $admin;

    private User $driverUser;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['username' => 'admin070']);

        $this->driverUser = User::factory()->create([
            'name' => 'Ramesh Kumar',
            'email' => null,
            'username' => 'driver070',
            'role' => User::ROLE_DRIVER,
        ]);

        $this->driver = Driver::factory()->create(['user_id' => $this->driverUser->id]);
    }

    /**
     * A confirmed ride of this driver. Its vehicle carries the rate card of the
     * Tata Nexon unless other rates are given for it.
     *
     * @param  array<string, mixed>  $bookingAttributes
     * @param  array<string, mixed>  $vehicleAttributes
     */
    private function confirmedBooking(array $bookingAttributes = [], array $vehicleAttributes = []): Booking
    {
        $city = City::factory()->create();

        $vehicle = Vehicle::factory()->create(array_merge([
            'name' => 'Tata Nexon',
            'city_id' => $city->id,
            'price_per_day' => self::PRICE_PER_DAY,
            'fixed_km_per_day' => self::FIXED_KM_PER_DAY,
            'price_per_km' => self::PRICE_PER_KM,
        ], $vehicleAttributes));

        return Booking::factory()->create(array_merge([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $this->driver->id,
            'status' => BookingStatus::CONFIRMED->value,
        ], $bookingAttributes));
    }

    /**
     * Start the ride the way the driver's form does it.
     */
    private function startTrip(Booking $booking): void
    {
        $this->actingAs($this->driverUser)
            ->post(route('driver.trips.start', $booking), [
                'start_odometer_km' => self::START_KM,
                'start_odometer_photo' => UploadedFile::fake()->create('odometer.jpg', 20, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors();
    }

    /**
     * Drive a ride of the given distance and close it the way the driver does.
     */
    private function driveAndEndTrip(Booking $booking, int $kilometres): Booking
    {
        $this->startTrip($booking);

        $this->actingAs($this->driverUser)
            ->post(route('driver.trips.end', $booking), [
                'end_odometer_km' => self::START_KM + $kilometres,
                'end_odometer_photo' => UploadedFile::fake()->create('odometer-end.jpg', 20, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors();

        return $booking->fresh();
    }

    public function test_a_ride_inside_the_included_kilometres_is_billed_the_price_of_the_day(): void
    {
        $booking = $this->driveAndEndTrip($this->confirmedBooking(), 412);

        $this->assertTrue($booking->hasTripFare());
        $this->assertSame(1, $booking->billed_days);
        $this->assertSame(500, $booking->billed_included_km);
        $this->assertSame(0, $booking->extra_km);
        $this->assertSame(self::PRICE_PER_DAY, $booking->base_amount);
        $this->assertSame('0.00', $booking->extra_km_amount);
        $this->assertSame(self::PRICE_PER_DAY, $booking->total_amount);
    }

    public function test_every_kilometre_above_the_included_ones_is_charged_at_the_extra_rate(): void
    {
        $booking = $this->driveAndEndTrip($this->confirmedBooking(), 620);

        $this->assertSame(500, $booking->billed_included_km);
        $this->assertSame(120, $booking->extra_km);
        $this->assertSame('1560.00', $booking->extra_km_amount);
        $this->assertSame('7060.00', $booking->total_amount);
    }

    public function test_a_ride_of_exactly_the_included_kilometres_is_not_charged_extra(): void
    {
        $booking = $this->driveAndEndTrip($this->confirmedBooking(), self::FIXED_KM_PER_DAY);

        $this->assertSame(0, $booking->extra_km);
        $this->assertSame(self::PRICE_PER_DAY, $booking->total_amount);
    }

    public function test_a_single_kilometre_above_the_included_ones_is_charged(): void
    {
        $booking = $this->driveAndEndTrip($this->confirmedBooking(), self::FIXED_KM_PER_DAY + 1);

        $this->assertSame(1, $booking->extra_km);
        $this->assertSame('5513.00', $booking->total_amount);
    }

    public function test_every_day_of_a_multi_day_hire_is_charged_with_its_own_included_kilometres(): void
    {
        $booking = $this->driveAndEndTrip(
            $this->confirmedBooking(['pickup_date' => '2026-10-01', 'drop_date' => '2026-10-03']),
            1400
        );

        $this->assertSame(3, $booking->billed_days);
        $this->assertSame(1500, $booking->billed_included_km);
        $this->assertSame('16500.00', $booking->base_amount);
        $this->assertSame(0, $booking->extra_km);
        $this->assertSame('16500.00', $booking->total_amount);
    }

    public function test_a_multi_day_hire_charges_the_kilometres_above_the_kilometres_of_all_its_days(): void
    {
        $booking = $this->driveAndEndTrip(
            $this->confirmedBooking(['pickup_date' => '2026-10-01', 'drop_date' => '2026-10-03']),
            1700
        );

        $this->assertSame(3, $booking->billed_days);
        $this->assertSame(1500, $booking->billed_included_km);
        $this->assertSame(200, $booking->extra_km);
        $this->assertSame('19100.00', $booking->total_amount);
    }

    public function test_a_hire_without_a_drop_date_is_billed_for_a_single_day(): void
    {
        $booking = $this->driveAndEndTrip($this->confirmedBooking(['pickup_date' => '2026-10-01']), 620);

        $this->assertSame(1, $booking->billed_days);
        $this->assertSame('7060.00', $booking->total_amount);
    }

    public function test_the_rate_card_is_copied_into_the_bill_so_a_later_rate_change_does_not_change_it(): void
    {
        $booking = $this->driveAndEndTrip($this->confirmedBooking(), 620);

        Vehicle::find($booking->vehicle_id)->update([
            'price_per_day' => '6000.00',
            'fixed_km_per_day' => 400,
            'price_per_km' => '15.00',
        ]);

        $booking->refresh();

        $this->assertSame(self::PRICE_PER_DAY, $booking->billed_price_per_day);
        $this->assertSame(self::PRICE_PER_KM, $booking->billed_price_per_km);
        $this->assertSame(500, $booking->billed_included_km);
        $this->assertSame('7060.00', $booking->total_amount);
    }

    public function test_a_vehicle_quoted_per_kilometre_only_is_billed_per_kilometre(): void
    {
        $booking = $this->driveAndEndTrip(
            $this->confirmedBooking(vehicleAttributes: ['price_per_day' => null, 'fixed_km_per_day' => null]),
            412
        );

        $this->assertSame(0, $booking->billed_included_km);
        $this->assertSame(412, $booking->extra_km);
        $this->assertSame('0.00', $booking->base_amount);
        $this->assertSame('5356.00', $booking->total_amount);
    }

    public function test_a_vehicle_without_a_rate_card_is_not_billed(): void
    {
        $booking = $this->driveAndEndTrip(
            $this->confirmedBooking(vehicleAttributes: [
                'price_per_day' => null,
                'fixed_km_per_day' => null,
                'price_per_km' => null,
            ]),
            620
        );

        $this->assertFalse($booking->hasTripFare());
        $this->assertNull($booking->total_amount);
        $this->assertNull($booking->tripFare());
    }

    public function test_a_ride_the_admin_closes_without_readings_is_not_billed(): void
    {
        $booking = $this->confirmedBooking();

        app(BookingService::class)->completeTrip($booking, $this->admin->id);

        $booking->refresh();

        $this->assertSame(BookingStatus::TRIP_COMPLETED, $booking->status);
        $this->assertFalse($booking->hasTripFare());
        $this->assertNull($booking->total_amount);
    }

    public function test_the_history_records_how_the_amount_was_worked_out(): void
    {
        $booking = $this->driveAndEndTrip($this->confirmedBooking(), 620);

        $remarks = (string) $booking->statusHistory()->latest('id')->value('remarks');

        $this->assertStringContainsString('total distance: 620 km', $remarks);
        $this->assertStringContainsString('Amount billed: 7,060.00', $remarks);
        $this->assertStringContainsString('1 day(s) at 5,500.00 covering 500 km', $remarks);
        $this->assertStringContainsString('plus 120 extra km at 13.00', $remarks);
    }

    public function test_ending_the_ride_reports_the_amount_to_the_driver(): void
    {
        $booking = $this->confirmedBooking();
        $this->startTrip($booking);

        $this->actingAs($this->driverUser)
            ->post(route('driver.trips.end', $booking), [
                'end_odometer_km' => self::START_KM + 620,
                'end_odometer_photo' => UploadedFile::fake()->create('odometer-end.jpg', 20, 'image/jpeg'),
            ])
            ->assertSessionHas('success', 'Trip completed. Total distance: 620 km, amount billed: 7,060.00.');
    }

    public function test_the_trip_sheet_shows_the_rate_card_before_the_ride_is_closed(): void
    {
        $booking = $this->confirmedBooking();
        $this->startTrip($booking);

        $response = $this->actingAs($this->driverUser)->get(route('driver.trips.show', $booking));

        $response->assertOk();
        $response->assertSee('What This Ride Will Cost');
        $response->assertSee('5,500.00 per day');
        $response->assertSee('covering the first 500 km');
        $response->assertSee('13.00 for every kilometre above that');
        $response->assertDontSee('Total Amount');
    }

    public function test_the_driver_sees_the_amount_and_the_figures_it_came_out_of(): void
    {
        $booking = $this->driveAndEndTrip($this->confirmedBooking(), 620);

        $response = $this->actingAs($this->driverUser)->get(route('driver.trips.show', $booking));

        $response->assertOk();
        $response->assertSee('Total Amount');
        $response->assertSee('7,060.00');
        $response->assertSee('1 day(s)');
        $response->assertSee('covering 500 km');
        $response->assertSee('120 extra km');
    }

    public function test_the_rides_list_shows_the_amount_of_a_finished_ride(): void
    {
        $this->driveAndEndTrip($this->confirmedBooking(), 620);

        $response = $this->actingAs($this->driverUser)->get(route('driver.rides'));

        $response->assertOk();
        $response->assertSee('7,060.00');
    }

    public function test_the_admin_sees_the_amount_and_the_figures_it_came_out_of(): void
    {
        $booking = $this->driveAndEndTrip($this->confirmedBooking(), 620);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));

        $response->assertOk();
        $response->assertSee('Amount Billed');
        $response->assertSee('7,060.00');
        $response->assertSee('1 day(s)');
        $response->assertSee('first 500 km included');
        $response->assertSee('120 extra km');
        $response->assertSee('Total Amount 7,060.00');
    }

    public function test_the_trip_sheets_say_so_when_the_vehicle_has_no_rate_card(): void
    {
        $booking = $this->driveAndEndTrip(
            $this->confirmedBooking(vehicleAttributes: [
                'price_per_day' => null,
                'fixed_km_per_day' => null,
                'price_per_km' => null,
            ]),
            620
        );

        $this->actingAs($this->admin)
            ->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('No rate card was filled in for Tata Nexon');

        $this->actingAs($this->driverUser)
            ->get(route('driver.trips.show', $booking))
            ->assertOk()
            ->assertSee('No rate card was filled in for Tata Nexon');
    }
}
