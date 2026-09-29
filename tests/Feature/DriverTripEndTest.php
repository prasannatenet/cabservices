<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RideExpenseCategory;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\RideExpense;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * At the drop point the driver closes the ride with the closing odometer photo
 * and reading. The total distance of the ride is the closing reading minus the
 * reading taken at the start, and both readings plus both photos are visible to
 * the admin on the booking screen.
 */
class DriverTripEndTest extends TestCase
{
    use RefreshDatabase;

    private const START_KM = 145320;

    private const END_KM = 145732;

    private User $admin;

    private User $driverUser;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['username' => 'admin060']);

        $this->driverUser = User::factory()->create([
            'name' => 'Ramesh Kumar',
            'email' => null,
            'username' => 'driver060',
            'role' => User::ROLE_DRIVER,
        ]);

        $this->driver = Driver::factory()->create(['user_id' => $this->driverUser->id]);
    }

    /**
     * A confirmed ride handed to this driver, ready to be started.
     */
    private function confirmedBooking(?City $dropCity = null): Booking
    {
        $city = City::factory()->create();

        return Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => ($dropCity ?? $city)->id,
            'vehicle_id' => Vehicle::factory()->create(['city_id' => $city->id])->id,
            'driver_id' => $this->driver->id,
            'status' => BookingStatus::CONFIRMED->value,
        ]);
    }

    /**
     * Start the ride the way the driver's form does it.
     */
    private function startTrip(Booking $booking, int $kilometres = self::START_KM): void
    {
        $this->actingAs($this->driverUser)
            ->post(route('driver.trips.start', $booking), [
                'start_odometer_km' => $kilometres,
                'start_odometer_photo' => UploadedFile::fake()->create('odometer.jpg', 20, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors();
    }

    /**
     * A running ride with its starting odometer already recorded.
     */
    private function runningBooking(int $kilometres = self::START_KM): Booking
    {
        $booking = $this->confirmedBooking();
        $this->startTrip($booking, $kilometres);

        return $booking->fresh();
    }

    /**
     * Close the ride the way the driver's form does it.
     */
    private function endTrip(Booking $booking, int $kilometres = self::END_KM)
    {
        return $this->actingAs($this->driverUser)
            ->post(route('driver.trips.end', $booking), [
                'end_odometer_km' => $kilometres,
                'end_odometer_photo' => UploadedFile::fake()->create('odometer-end.jpg', 20, 'image/jpeg'),
            ]);
    }

    /**
     * A second driver account, used to prove one driver cannot close another
     * driver's ride.
     */
    private function otherDriverUser(string $username = 'driver061'): User
    {
        $user = User::factory()->create([
            'email' => null,
            'username' => $username,
            'role' => User::ROLE_DRIVER,
        ]);

        Driver::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_the_driver_ends_the_ride_with_the_closing_odometer_photo_and_reading(): void
    {
        $booking = $this->runningBooking();

        $response = $this->endTrip($booking);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', 'Trip completed. Total distance: 412 km.');
        $response->assertRedirect(route('driver.trips.show', $booking));

        $booking->refresh();

        $this->assertSame(BookingStatus::TRIP_COMPLETED, $booking->status);
        $this->assertSame(self::END_KM, $booking->end_odometer_km);
        $this->assertNotNull($booking->trip_ended_at);
        Storage::disk('public')->assertExists($booking->end_odometer_photo);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'old_status' => BookingStatus::TRIP_STARTED->value,
            'new_status' => BookingStatus::TRIP_COMPLETED->value,
            'changed_by' => $this->driverUser->id,
        ]);
    }

    public function test_the_total_distance_is_the_closing_reading_minus_the_starting_reading(): void
    {
        $booking = $this->runningBooking();

        // While the ride is running there is only one reading, so no total yet.
        $this->assertNull($booking->tripDistanceKm());

        $this->endTrip($booking)->assertSessionHasNoErrors();

        $this->assertSame(self::END_KM - self::START_KM, $booking->fresh()->tripDistanceKm());
    }

    public function test_a_ride_that_covers_no_distance_has_a_total_of_zero(): void
    {
        $booking = $this->runningBooking(self::START_KM);

        $this->endTrip($booking, self::START_KM)->assertSessionHasNoErrors();

        $this->assertSame(0, $booking->fresh()->tripDistanceKm());
    }

    public function test_the_driver_sees_the_closing_reading_and_the_total_distance_on_the_trip_sheet(): void
    {
        $booking = $this->runningBooking();

        $this->endTrip($booking);

        $response = $this->actingAs($this->driverUser)->get(route('driver.trips.show', $booking));

        $response->assertOk();
        $response->assertSee('Odometer at End');
        $response->assertSee('145,732');
        $response->assertSee('Total Distance');
        $response->assertSee('412 km');
    }

    public function test_the_admin_sees_both_readings_and_the_total_distance(): void
    {
        $booking = $this->runningBooking();

        $this->endTrip($booking);
        $booking->refresh();

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));

        $response->assertOk();
        $response->assertSee('Trip Sheet');
        $response->assertSee('Odometer at Start');
        $response->assertSee('Odometer at End');
        $response->assertSee('145,320');
        $response->assertSee('145,732');
        $response->assertSee('Total Distance 412 km');
        $response->assertSee('Photo at End');
        $response->assertSee($booking->endOdometerPhotoUrl(), false);
    }

    public function test_the_closing_reading_cannot_be_lower_than_the_starting_reading(): void
    {
        $booking = $this->runningBooking();

        $response = $this->endTrip($booking, self::START_KM - 500);

        $response->assertSessionHasErrors('end_odometer_km');

        $booking->refresh();

        $this->assertSame(BookingStatus::TRIP_STARTED, $booking->status);
        $this->assertNull($booking->end_odometer_km);
        $this->assertNull($booking->trip_ended_at);
        // Only the photo from the start of the ride is on disk.
        $this->assertCount(1, Storage::disk('public')->files('trips/odometer'));
    }

    public function test_the_closing_odometer_photo_is_required(): void
    {
        $booking = $this->runningBooking();

        $response = $this->actingAs($this->driverUser)->post(route('driver.trips.end', $booking), [
            'end_odometer_km' => self::END_KM,
        ]);

        $response->assertSessionHasErrors('end_odometer_photo');

        $booking->refresh();

        $this->assertSame(BookingStatus::TRIP_STARTED, $booking->status);
        $this->assertNull($booking->end_odometer_km);
    }

    public function test_a_ride_that_has_not_started_cannot_be_ended(): void
    {
        $booking = $this->confirmedBooking();

        $response = $this->endTrip($booking);

        $response->assertSessionHas('error');

        $booking->refresh();

        $this->assertSame(BookingStatus::CONFIRMED, $booking->status);
        $this->assertNull($booking->end_odometer_km);
        // The photo sent for a ride that never started is thrown away.
        Storage::disk('public')->assertDirectoryEmpty('trips/odometer');
    }

    public function test_a_ride_that_has_already_ended_cannot_be_ended_again(): void
    {
        $booking = $this->runningBooking();

        $this->endTrip($booking);

        $this->endTrip($booking, self::END_KM + 100)->assertSessionHas('error');

        $this->assertSame(self::END_KM, $booking->fresh()->end_odometer_km);
    }

    public function test_a_driver_cannot_end_a_ride_of_another_driver(): void
    {
        $booking = $this->runningBooking();

        $this->actingAs($this->otherDriverUser())
            ->post(route('driver.trips.end', $booking), [
                'end_odometer_km' => self::END_KM,
                'end_odometer_photo' => UploadedFile::fake()->create('odometer-end.jpg', 20, 'image/jpeg'),
            ])
            ->assertForbidden();

        $booking->refresh();

        $this->assertSame(BookingStatus::TRIP_STARTED, $booking->status);
        $this->assertNull($booking->end_odometer_km);
    }

    public function test_the_vehicle_and_driver_move_to_the_drop_city_when_the_driver_ends_the_ride(): void
    {
        $dropCity = City::factory()->create();
        $booking = $this->confirmedBooking($dropCity);
        $this->driver->update(['current_city_id' => $booking->pickup_city_id]);

        $this->startTrip($booking);
        $this->endTrip($booking)->assertSessionHasNoErrors();

        $this->assertSame($dropCity->id, Vehicle::find($booking->vehicle_id)->city_id);
        $this->assertSame($dropCity->id, $this->driver->fresh()->current_city_id);
    }

    public function test_an_expense_cannot_be_added_once_the_ride_is_ended(): void
    {
        $booking = $this->runningBooking();

        $this->endTrip($booking);

        $response = $this->actingAs($this->driverUser)->post(route('driver.trips.expenses.store', $booking), [
            'category' => RideExpenseCategory::Petrol->value,
            'amount' => '1200.00',
            'bill_number' => 'BILL-5566',
            'spent_on' => now()->toDateString(),
            'bill_photo' => UploadedFile::fake()->create('bill.jpg', 30, 'image/jpeg'),
        ]);

        $response->assertSessionHas('error');

        $this->assertSame(0, $booking->rideExpenses()->count());
    }

    public function test_a_bill_cannot_be_removed_once_the_ride_is_ended(): void
    {
        $booking = $this->runningBooking();

        $expense = RideExpense::factory()->create([
            'booking_id' => $booking->id,
            'driver_id' => $this->driver->id,
        ]);

        $this->endTrip($booking);

        $response = $this->actingAs($this->driverUser)
            ->delete(route('driver.trips.expenses.destroy', [$booking, $expense]));

        $response->assertSessionHas('error');

        $this->assertDatabaseHas('ride_expenses', ['id' => $expense->id]);
    }

    public function test_the_rides_page_offers_the_end_trip_action_while_the_ride_is_running(): void
    {
        $this->runningBooking();

        $response = $this->actingAs($this->driverUser)->get(route('driver.rides'));

        $response->assertOk();
        $response->assertSee('Add Expense');
        $response->assertSee('End Trip');
    }
}
