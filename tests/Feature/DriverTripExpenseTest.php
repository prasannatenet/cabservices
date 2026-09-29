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
 * The driver starts a ride by sending the odometer photo and reading, and logs
 * the petrol / diesel / gas bills he pays for while he drives. Both are visible
 * to the admin on the booking page.
 */
class DriverTripExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $driverUser;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['username' => 'admin050']);

        $this->driverUser = User::factory()->create([
            'name' => 'Ramesh Kumar',
            'email' => null,
            'username' => 'driver050',
            'role' => User::ROLE_DRIVER,
        ]);

        $this->driver = Driver::factory()->create(['user_id' => $this->driverUser->id]);
    }

    /**
     * A confirmed ride handed to this driver, ready to be started.
     */
    private function confirmedBooking(): Booking
    {
        $city = City::factory()->create();

        return Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => Vehicle::factory()->create()->id,
            'driver_id' => $this->driver->id,
            'status' => BookingStatus::CONFIRMED->value,
        ]);
    }

    /**
     * Start the ride the way the driver's form does it.
     */
    private function startTrip(Booking $booking, int $kilometres = 12345): void
    {
        $this->actingAs($this->driverUser)
            ->post(route('driver.trips.start', $booking), [
                'start_odometer_km' => $kilometres,
                'start_odometer_photo' => UploadedFile::fake()->create('odometer.jpg', 20, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors();
    }

    /**
     * A running ride with its odometer already recorded.
     */
    private function runningBooking(int $kilometres = 12345): Booking
    {
        $booking = $this->confirmedBooking();
        $this->startTrip($booking, $kilometres);

        return $booking->fresh();
    }

    /**
     * Log an expense through the driver's form.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function logExpense(Booking $booking, array $overrides = [])
    {
        return $this->actingAs($this->driverUser)
            ->post(route('driver.trips.expenses.store', $booking), array_merge([
                'category' => RideExpenseCategory::Petrol->value,
                'amount' => '1500.50',
                'bill_number' => 'BILL-7781',
                'spent_on' => now()->toDateString(),
                'notes' => 'Filled 20 litres on the way',
                'bill_photo' => UploadedFile::fake()->create('bill.jpg', 30, 'image/jpeg'),
            ], $overrides));
    }

    /**
     * A second driver account, used to prove one driver cannot touch another
     * driver's ride.
     */
    private function otherDriverUser(string $username = 'driver051'): User
    {
        $user = User::factory()->create([
            'email' => null,
            'username' => $username,
            'role' => User::ROLE_DRIVER,
        ]);

        Driver::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_the_driver_starts_the_ride_with_the_odometer_photo_and_reading(): void
    {
        $booking = $this->confirmedBooking();

        $this->startTrip($booking, 145320);

        $booking->refresh();

        $this->assertSame(BookingStatus::TRIP_STARTED, $booking->status);
        $this->assertSame(145320, $booking->start_odometer_km);
        $this->assertNotNull($booking->trip_started_at);
        Storage::disk('public')->assertExists($booking->start_odometer_photo);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'new_status' => BookingStatus::TRIP_STARTED->value,
            'changed_by' => $this->driverUser->id,
        ]);
    }

    public function test_the_ride_cannot_be_started_without_the_odometer_photo_and_reading(): void
    {
        $booking = $this->confirmedBooking();

        $response = $this->actingAs($this->driverUser)->post(route('driver.trips.start', $booking), []);

        $response->assertSessionHasErrors(['start_odometer_km', 'start_odometer_photo']);

        $this->assertSame(BookingStatus::CONFIRMED, $booking->fresh()->status);
        $this->assertNull($booking->fresh()->trip_started_at);
    }

    public function test_a_driver_cannot_open_a_trip_sheet_of_another_driver(): void
    {
        $booking = $this->confirmedBooking();
        $otherUser = $this->otherDriverUser();

        $this->actingAs($otherUser)->get(route('driver.trips.show', $booking))->assertForbidden();

        $this->actingAs($otherUser)
            ->post(route('driver.trips.start', $booking), [
                'start_odometer_km' => 100,
                'start_odometer_photo' => UploadedFile::fake()->create('odometer.jpg', 20, 'image/jpeg'),
            ])
            ->assertForbidden();

        $this->assertSame(BookingStatus::CONFIRMED, $booking->fresh()->status);
    }

    public function test_a_ride_that_is_not_confirmed_cannot_be_started(): void
    {
        $booking = $this->confirmedBooking();
        $booking->update(['status' => BookingStatus::PENDING->value]);

        $response = $this->actingAs($this->driverUser)->post(route('driver.trips.start', $booking), [
            'start_odometer_km' => 100,
            'start_odometer_photo' => UploadedFile::fake()->create('odometer.jpg', 20, 'image/jpeg'),
        ]);

        $response->assertSessionHas('error');

        $this->assertSame(BookingStatus::PENDING, $booking->fresh()->status);
        // The photo the driver sent belongs to a ride that never started.
        Storage::disk('public')->assertDirectoryEmpty('trips/odometer');
    }

    public function test_the_driver_logs_a_fuel_expense_with_its_bill_photo(): void
    {
        $booking = $this->runningBooking();

        $response = $this->logExpense($booking);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $expense = RideExpense::where('booking_id', $booking->id)->firstOrFail();

        $this->assertSame($this->driver->id, $expense->driver_id);
        $this->assertSame(RideExpenseCategory::Petrol, $expense->category);
        $this->assertSame('1500.50', $expense->amount);
        $this->assertSame('BILL-7781', $expense->bill_number);
        Storage::disk('public')->assertExists($expense->bill_photo);
    }

    public function test_an_expense_always_needs_a_bill_photo(): void
    {
        $booking = $this->runningBooking();

        $response = $this->logExpense($booking, ['bill_photo' => null]);

        $response->assertSessionHasErrors('bill_photo');
        $this->assertDatabaseCount('ride_expenses', 0);
    }

    public function test_an_expense_needs_a_known_category(): void
    {
        $booking = $this->runningBooking();

        $response = $this->logExpense($booking, ['category' => 'Diamond']);

        $response->assertSessionHasErrors('category');
        $this->assertDatabaseCount('ride_expenses', 0);
    }

    public function test_expenses_cannot_be_logged_before_the_ride_starts(): void
    {
        $booking = $this->confirmedBooking();

        $response = $this->logExpense($booking);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('ride_expenses', 0);
    }

    public function test_another_driver_cannot_log_an_expense_on_someone_elses_ride(): void
    {
        $booking = $this->runningBooking();
        $otherUser = $this->otherDriverUser();

        $this->actingAs($otherUser)
            ->post(route('driver.trips.expenses.store', $booking), [
                'category' => RideExpenseCategory::Diesel->value,
                'amount' => '900',
                'bill_photo' => UploadedFile::fake()->create('bill.jpg', 30, 'image/jpeg'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('ride_expenses', 0);
    }

    public function test_the_driver_can_remove_his_own_expense_while_the_ride_is_running(): void
    {
        $booking = $this->runningBooking();

        $expense = RideExpense::factory()->create([
            'booking_id' => $booking->id,
            'driver_id' => $this->driver->id,
            'bill_photo' => 'trips/expenses/typed-by-mistake.jpg',
        ]);

        Storage::disk('public')->put($expense->bill_photo, 'bill');

        $response = $this->actingAs($this->driverUser)
            ->delete(route('driver.trips.expenses.destroy', [$booking, $expense]));

        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('ride_expenses', ['id' => $expense->id]);
        Storage::disk('public')->assertMissing('trips/expenses/typed-by-mistake.jpg');
    }

    public function test_an_expense_cannot_be_removed_once_the_trip_is_completed(): void
    {
        $booking = $this->runningBooking();

        $expense = RideExpense::factory()->create([
            'booking_id' => $booking->id,
            'driver_id' => $this->driver->id,
        ]);

        $booking->update(['status' => BookingStatus::TRIP_COMPLETED->value]);

        $response = $this->actingAs($this->driverUser)
            ->delete(route('driver.trips.expenses.destroy', [$booking, $expense]));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ride_expenses', ['id' => $expense->id]);
    }

    public function test_an_expense_of_another_ride_is_not_found(): void
    {
        $booking = $this->runningBooking();

        $otherBooking = Booking::factory()->create([
            'pickup_city_id' => $booking->pickup_city_id,
            'drop_city_id' => $booking->drop_city_id,
            'vehicle_id' => $booking->vehicle_id,
            'driver_id' => $this->driver->id,
            'status' => BookingStatus::TRIP_STARTED->value,
        ]);

        $expense = RideExpense::factory()->create([
            'booking_id' => $otherBooking->id,
            'driver_id' => $this->driver->id,
        ]);

        $this->actingAs($this->driverUser)
            ->delete(route('driver.trips.expenses.destroy', [$booking, $expense]))
            ->assertNotFound();

        $this->assertDatabaseHas('ride_expenses', ['id' => $expense->id]);
    }

    public function test_the_driver_sees_the_odometer_and_his_expenses_on_the_trip_sheet(): void
    {
        $booking = $this->runningBooking(145320);

        RideExpense::factory()->create([
            'booking_id' => $booking->id,
            'driver_id' => $this->driver->id,
            'category' => RideExpenseCategory::Diesel->value,
            'amount' => 750,
            'bill_number' => 'BILL-9001',
        ]);

        $response = $this->actingAs($this->driverUser)->get(route('driver.trips.show', $booking));

        $response->assertOk();
        $response->assertSee('Odometer at Start');
        $response->assertSee('145,320');
        $response->assertSee('Add Expense');
        $response->assertSee('Diesel Money');
        $response->assertSee('BILL-9001');
        $response->assertSee('750.00');
    }

    public function test_a_confirmed_ride_offers_the_driver_the_start_trip_action(): void
    {
        $booking = $this->confirmedBooking();

        $response = $this->actingAs($this->driverUser)->get(route('driver.rides'));

        $response->assertOk();
        $response->assertSee('Start Trip');
        $response->assertSee(route('driver.trips.show', $booking), false);
    }

    public function test_the_dashboard_offers_the_expense_action_for_a_running_ride(): void
    {
        $booking = $this->runningBooking();

        $response = $this->actingAs($this->driverUser)->get(route('driver.dashboard'));

        $response->assertOk();
        $response->assertSee('Trip Sheet');
        $response->assertSee('Add Expense');
        $response->assertSee(route('driver.trips.show', $booking), false);
    }

    public function test_the_admin_sees_the_odometer_and_the_expenses_on_the_booking_page(): void
    {
        $booking = $this->runningBooking(145320);

        RideExpense::factory()->create([
            'booking_id' => $booking->id,
            'driver_id' => $this->driver->id,
            'category' => RideExpenseCategory::Petrol->value,
            'amount' => 1500.50,
            'bill_number' => 'BILL-7781',
            'notes' => 'Filled 20 litres',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));

        $response->assertOk();
        $response->assertSee('Trip Sheet');
        $response->assertSee('145,320');
        $response->assertSee('Trip Expenses');
        $response->assertSee('Petrol Money');
        $response->assertSee('BILL-7781');
        $response->assertSee('1,500.50');
        $response->assertSee('View Bill');
        $response->assertSee($this->driver->name);
    }

    public function test_the_admin_booking_page_says_so_when_the_ride_has_not_started_yet(): void
    {
        $booking = $this->confirmedBooking();

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));

        $response->assertOk();
        $response->assertSee('Trip Sheet');
        $response->assertSee('Not recorded');
        $response->assertSee('The driver has not claimed any expense for this ride.');
    }
}
