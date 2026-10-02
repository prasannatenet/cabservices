<?php

namespace Tests\Feature;

use App\Enums\AssignmentResponseStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AssignmentResponseService;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A ride the dispatcher takes off one driver and gives to another must stop
 * counting against the first driver, whether he had answered it or not.
 */
class DriverReplacedOnRideTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Driver $firstDriver;

    private Driver $secondDriver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['username' => 'admin001']);
        $this->firstDriver = Driver::factory()->create();
        $this->secondDriver = Driver::factory()->create();
    }

    /**
     * A pending booking with a vehicle, ready to be handed to a driver.
     */
    private function pendingBooking(): Booking
    {
        $city = City::factory()->create();

        return Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => Vehicle::factory()->create()->id,
            'status' => BookingStatus::PENDING->value,
        ]);
    }

    /**
     * Put a driver on a ride through the real service, so the assignment and
     * its response window are created exactly as they are in production.
     */
    private function assignRide(Booking $booking, Driver $driver): DriverAssignment
    {
        app(BookingService::class)->assignDriver($booking, $driver->id, $this->admin->id);

        return $booking->fresh()->driverAssignment;
    }

    /**
     * A pending booking with the first driver already on it, ready to be swapped
     * for the second one.
     */
    private function rideAwaitingSecondDriver(): Booking
    {
        $booking = $this->pendingBooking();

        $this->assignRide($booking, $this->firstDriver);

        return $booking->fresh();
    }

    public function test_a_ride_taken_off_an_unanswered_driver_is_not_counted_for_him(): void
    {
        $booking = $this->rideAwaitingSecondDriver();

        $this->assignRide($booking, $this->secondDriver);

        $this->assertSame(
            0,
            $this->firstDriver->driverAssignments()->counted()->count(),
            'A ride the driver never answered and was taken off must not be counted for him.'
        );

        $this->assertSame(1, $this->firstDriver->driverAssignments()->superseded()->count());
        $this->assertSame(1, $this->secondDriver->driverAssignments()->counted()->count());
    }

    public function test_a_ride_taken_off_a_driver_who_had_accepted_it_is_not_counted_for_him(): void
    {
        $booking = $this->rideAwaitingSecondDriver();

        app(AssignmentResponseService::class)->accept($booking->driverAssignment);

        $this->assignRide($booking->fresh(), $this->secondDriver);

        $this->assertSame(
            0,
            $this->firstDriver->driverAssignments()->counted()->count(),
            'A ride the driver accepted and was then taken off must not be counted for him either.'
        );

        $this->assertTrue($this->firstDriver->driverAssignments()->sole()->wasSuperseded());
    }

    public function test_the_swapped_ride_stays_with_the_new_driver(): void
    {
        $booking = $this->rideAwaitingSecondDriver();

        $this->assignRide($booking, $this->secondDriver);

        $booking->refresh();
        $this->assertSame(BookingStatus::DRIVER_ASSIGNED, $booking->status);
        $this->assertSame($this->secondDriver->id, $booking->driver_id);
        $this->assertSame(AssignmentResponseStatus::Pending, $booking->driverAssignment->response_status);
    }

    public function test_the_replaced_driver_can_no_longer_answer_the_ride(): void
    {
        $booking = $this->rideAwaitingSecondDriver();
        $superseded = $booking->driverAssignment;

        $this->assignRide($booking, $this->secondDriver);

        // A late answer to a ride he is not on is refused rather than accepted.
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This ride has been given to another driver.');

        app(AssignmentResponseService::class)->accept($superseded);
    }

    public function test_the_replaced_driver_cannot_reject_the_ride_either(): void
    {
        $booking = $this->rideAwaitingSecondDriver();
        $superseded = $booking->driverAssignment;

        $this->assignRide($booking, $this->secondDriver);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This ride has been given to another driver.');

        app(AssignmentResponseService::class)->reject($superseded, 'Vehicle is in the workshop');
    }

    public function test_a_replaced_assignment_is_not_auto_rejected_when_its_window_closes(): void
    {
        $booking = $this->rideAwaitingSecondDriver();

        $this->assignRide($booking, $this->secondDriver);

        // The window closing on a ride the first driver no longer has must not
        // take the whole ride down with it.
        $this->assertSame(0, app(AssignmentResponseService::class)->rejectExpiredAssignments());

        $booking->refresh();

        $this->assertSame(BookingStatus::DRIVER_ASSIGNED, $booking->status);
        $this->assertSame($this->secondDriver->id, $booking->driver_id);
    }

    public function test_a_replaced_ride_is_not_reported_as_a_rejection(): void
    {
        $booking = $this->rideAwaitingSecondDriver();

        $this->assignRide($booking, $this->secondDriver);

        // He never turned the ride down, so nothing may say that he did.
        $this->assertSame(0, $this->firstDriver->rejectedAssignments()->count());
        $this->assertNull($this->firstDriver->driverAssignments()->sole()->rejection_reason);
    }

    public function test_a_ride_the_driver_refused_before_the_swap_still_counts_as_a_rejection(): void
    {
        $booking = $this->rideAwaitingSecondDriver();

        app(AssignmentResponseService::class)->reject(
            $booking->driverAssignment,
            'Pickup is too far from my city'
        );

        $this->assignRide($booking->fresh(), $this->secondDriver);

        // The refusal happened before the swap and is a real thing he did, so it
        // stays on his record rather than being quietly written off.
        $this->assertSame(1, $this->firstDriver->rejectedAssignments()->count());
        $this->assertTrue($this->firstDriver->driverAssignments()->sole()->wasRejectedByDriver());
    }

    public function test_the_activity_report_does_not_count_a_ride_the_driver_was_replaced_on(): void
    {
        $this->assignRide($this->rideAwaitingSecondDriver(), $this->secondDriver);

        $response = $this->actingAs($this->admin)->get(route('admin.driver-activity.show', $this->firstDriver));

        $response->assertStatus(200);
        $response->assertViewHas('summary', fn (array $summary): bool => $summary['assigned'] === 0);
    }

    public function test_the_driver_no_longer_sees_the_ride_on_his_pending_list(): void
    {
        $user = User::factory()->create([
            'email' => null,
            'username' => 'driver001',
            'role' => User::ROLE_DRIVER,
        ]);

        $driver = Driver::factory()->create(['user_id' => $user->id]);
        $booking = $this->pendingBooking();

        $this->assignRide($booking, $driver);
        $this->assignRide($booking->fresh(), $this->secondDriver);

        $this->actingAs($user)
            ->get(route('driver.assignments.index'))
            ->assertOk()
            ->assertDontSee($booking->booking_number);
    }

    public function test_an_expired_assignment_of_a_driver_who_is_still_on_the_ride_is_still_auto_rejected(): void
    {
        $booking = $this->rideAwaitingSecondDriver();

        // The window closing on the driver the ride actually has still releases it.
        $booking->driverAssignment->forceFill(['response_deadline' => now()->subMinute()])->save();

        $this->assertSame(1, app(AssignmentResponseService::class)->rejectExpiredAssignments());
        $this->assertSame(BookingStatus::DRIVER_REJECTED, $booking->fresh()->status);
    }
}
