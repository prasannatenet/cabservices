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
 * What the finished rides are worth, seen from the admin dashboard and from the
 * completed rides page under the Bookings tab, plus the distance every finished
 * ride added to the driver's own history.
 *
 * The Tata Nexon of the fleet is on 5,500 for a day of 500 km and 13 for every
 * kilometre above that, so a 620 km ride is billed 7,060.00 and a 412 km ride
 * 5,500.00.
 */
class AdminCompletedRidesTest extends TestCase
{
    use RefreshDatabase;

    private const START_KM = 145320;

    private User $admin;

    private User $driverUser;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create();

        $this->driverUser = User::factory()->create([
            'name' => 'Ramesh Kumar',
            'username' => 'driver080',
            'role' => User::ROLE_DRIVER,
        ]);

        $this->driver = Driver::factory()->create([
            'user_id' => $this->driverUser->id,
            'name' => 'Ramesh Kumar',
        ]);
    }

    /**
     * A confirmed ride of this driver, on a vehicle carrying the rate card of
     * the Tata Nexon unless other rates are given for it.
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
            'price_per_day' => '5500.00',
            'fixed_km_per_day' => 500,
            'price_per_km' => '13.00',
        ], $vehicleAttributes));

        return Booking::factory()->create(array_merge([
            'booking_number' => 'BKG-'.fake()->unique()->numerify('####'),
            'customer_name' => 'Anita Sharma',
            'customer_phone' => '9876500001',
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $this->driver->id,
            'status' => BookingStatus::CONFIRMED->value,
        ], $bookingAttributes));
    }

    /**
     * Drive a ride of the given distance and close it the way the driver does,
     * so its amount is worked out from the readings he took.
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

    public function test_the_admin_dashboard_adds_up_the_price_of_every_finished_ride(): void
    {
        $this->driveAndEndTrip($this->confirmedBooking(), 620);
        $this->driveAndEndTrip($this->confirmedBooking(), 412);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('metrics', function (array $metrics): bool {
            return $metrics['total_trip_price'] === 12560.0
                && $metrics['completed_rides'] === 2
                && $metrics['total_trip_km'] === 1032;
        });
        // 7,060.00 for the 620 km ride and 5,500.00 for the 412 km one.
        $response->assertSee('12,560.00');
        $response->assertSee('1,032');
    }

    public function test_the_dashboard_trip_price_leaves_out_rides_that_are_not_finished(): void
    {
        $this->driveAndEndTrip($this->confirmedBooking(), 620);
        $this->confirmedBooking();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('metrics', function (array $metrics): bool {
            return $metrics['total_trip_price'] === 7060.0
                && $metrics['completed_rides'] === 1
                && $metrics['total_trip_km'] === 620;
        });
    }

    public function test_the_completed_rides_page_lists_every_finished_ride_with_its_distance_and_amount(): void
    {
        $this->driveAndEndTrip($this->confirmedBooking(), 620);
        $this->confirmedBooking(['customer_name' => 'Vikram Rao']);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.completed'));

        $response->assertOk();
        $response->assertSee('Anita Sharma');
        $response->assertSee('Ramesh Kumar');
        $response->assertSee('Tata Nexon');
        $response->assertSee('620 km');
        $response->assertSee('7,060.00');
        $response->assertSee('120 extra km');
        // A ride that has not finished yet is not on this page.
        $response->assertDontSee('Vikram Rao');
    }

    public function test_the_completed_rides_page_adds_up_only_the_rides_left_by_the_filters(): void
    {
        $this->driveAndEndTrip($this->confirmedBooking(), 620);
        $this->driveAndEndTrip($this->confirmedBooking(['customer_name' => 'Vikram Rao']), 412);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.completed', ['search' => 'Anita']));

        $response->assertOk();
        $response->assertSee('Anita Sharma');
        $response->assertDontSee('Vikram Rao');
        $response->assertViewHas('totals', function (array $totals): bool {
            return $totals['rides'] === 1
                && $totals['total_trip_price'] === 7060.0
                && $totals['total_trip_km'] === 620;
        });
    }

    public function test_a_ride_closed_without_odometer_readings_is_listed_without_an_amount(): void
    {
        $this->confirmedBooking([
            'customer_name' => 'Vikram Rao',
            'status' => BookingStatus::TRIP_COMPLETED->value,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.completed'));

        $response->assertOk();
        $response->assertSee('Vikram Rao');
        $response->assertSee('Not billed');
        $response->assertSee('Closed by admin');
        $response->assertViewHas('totals', function (array $totals): bool {
            return $totals['rides'] === 1
                && $totals['total_trip_price'] === 0.0
                && $totals['total_trip_km'] === 0;
        });
    }

    public function test_the_bookings_tab_offers_the_completed_rides_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.bookings.index'));

        $response->assertOk();
        $response->assertSee('Completed Rides');
        $response->assertSee(route('admin.bookings.completed'), false);
    }

    public function test_a_driver_is_sent_away_from_the_completed_rides_page(): void
    {
        $response = $this->actingAs($this->driverUser)->get(route('admin.bookings.completed'));

        $response->assertRedirect(route('driver.dashboard'));
    }

    public function test_the_driver_history_shows_the_total_km_of_every_finished_ride(): void
    {
        $this->driveAndEndTrip($this->confirmedBooking(['booking_number' => 'BKG-0001']), 620);
        $this->driveAndEndTrip($this->confirmedBooking(['booking_number' => 'BKG-0002']), 412);
        $this->confirmedBooking(['booking_number' => 'BKG-0003']);

        $response = $this->actingAs($this->driverUser)->get(route('driver.rides'));

        $response->assertOk();
        $response->assertSee('620 km');
        $response->assertSee('412 km');
        // A ride that has not finished is on his history, but it has no distance yet.
        $response->assertSee('BKG-0003');
        $response->assertViewHas('totals', function (array $totals): bool {
            return $totals['rides'] === 2
                && $totals['total_km'] === 1032
                && $totals['total_amount'] === 12560.0;
        });
    }

    public function test_a_ride_closed_before_its_vehicle_had_a_rate_card_is_priced_from_the_rate_card_today(): void
    {
        // A ride finished long ago: the distance is on record, but the trip was
        // closed when the vehicle carried no rate card, so no bill was stored.
        $this->confirmedBooking([
            'customer_name' => 'Vikram Rao',
            'status' => BookingStatus::TRIP_COMPLETED->value,
            'start_odometer_km' => self::START_KM,
            'end_odometer_km' => self::START_KM + 620,
            'trip_started_at' => now()->subDays(3),
            'trip_ended_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.completed'));

        $response->assertOk();
        // 1 day x 5,500.00 = 5,500.00 and 120 extra km x 13.00 = 1,560.00.
        $response->assertSee('7,060.00');
        $response->assertSee('1 day(s) &times; 5,500.00 = 5,500.00', false);
        $response->assertSee('120 extra km &times; 13.00 = 1,560.00', false);
        $response->assertSee('not billed when the trip was closed');
        $response->assertViewHas('totals', function (array $totals): bool {
            return $totals['total_trip_price'] === 7060.0
                && $totals['total_trip_km'] === 620;
        });
    }

    public function test_the_dashboard_counts_the_rides_that_were_never_billed_at_the_same_price(): void
    {
        $this->driveAndEndTrip($this->confirmedBooking(), 620);
        $this->confirmedBooking([
            'status' => BookingStatus::TRIP_COMPLETED->value,
            'start_odometer_km' => self::START_KM,
            'end_odometer_km' => self::START_KM + 412,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        // 7,060.00 for the billed ride and 5,500.00 for the one worked out now.
        $response->assertViewHas('metrics', function (array $metrics): bool {
            return $metrics['total_trip_price'] === 12560.0
                && $metrics['completed_rides'] === 2;
        });
        $response->assertSee('12,560.00');
    }

    public function test_the_completed_rides_page_shows_the_figures_a_billed_amount_came_out_of(): void
    {
        $this->driveAndEndTrip($this->confirmedBooking(), 620);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.completed'));

        $response->assertOk();
        $response->assertSee('7,060.00');
        $response->assertSee('1 day(s) &times; 5,500.00 = 5,500.00', false);
        $response->assertSee('120 extra km &times; 13.00 = 1,560.00', false);
        // It really was billed when the trip was closed, so it is not marked as one.
        $response->assertDontSee('not billed when the trip was closed');
    }

    public function test_a_ride_stayed_inside_its_included_kilometres_is_said_so(): void
    {
        $this->driveAndEndTrip($this->confirmedBooking(), 412);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.completed'));

        $response->assertOk();
        $response->assertSee('5,500.00');
        $response->assertSee('No extra km &mdash; inside the 500 km included', false);
    }

    public function test_a_ride_with_no_distance_and_no_rate_card_has_no_price(): void
    {
        $this->confirmedBooking([
            'status' => BookingStatus::TRIP_COMPLETED->value,
        ], [
            'price_per_day' => null,
            'fixed_km_per_day' => null,
            'price_per_km' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.completed'));

        $response->assertOk();
        $response->assertSee('Not billed');
        $response->assertViewHas('totals', fn (array $totals): bool => $totals['total_trip_price'] === 0.0);
    }
}
