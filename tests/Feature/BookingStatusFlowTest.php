<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DriverStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AssignmentResponseService;
use App\Services\BookingService;
use App\Services\BookingStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A booking's status follows what happens to the ride, never a choice from a
 * list: a driver is put on it, he accepts or refuses it, he opens it with an
 * odometer reading and closes it at the drop point.
 *
 * These tests walk that chain end to end and check the two things that make it
 * safe: the impossible jumps are refused, and the driver and vehicle standing on
 * a ride are released exactly when that ride stops needing them.
 */
class BookingStatusFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private City $city;

    private Driver $driver;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->city = City::factory()->create();

        $this->vehicle = Vehicle::factory()->create([
            'city_id' => $this->city->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);

        $this->driver = Driver::factory()->create([
            'current_city_id' => $this->city->id,
            'status' => DriverStatus::AVAILABLE->value,
        ]);
    }

    public function test_a_ride_runs_from_a_request_to_a_finished_trip_without_a_status_being_picked(): void
    {
        $booking = $this->pendingBooking();

        $this->assertSame(BookingStatus::PENDING, $booking->status);

        // The admin assigns a driver, which is what moves the ride on.
        app(BookingService::class)->assignDriver($booking, $this->driver->id, $this->admin->id);
        $this->assertSame(BookingStatus::DRIVER_ASSIGNED, $booking->fresh()->status);

        // The driver accepts from his own dashboard.
        app(AssignmentResponseService::class)->accept($booking->fresh()->driverAssignment);
        $this->assertSame(BookingStatus::CONFIRMED, $booking->fresh()->status);

        // He opens the trip with the odometer reading.
        app(BookingService::class)->startTrip($booking->fresh(), 145_320, 'trips/start.jpg', $this->admin->id);
        $this->assertSame(BookingStatus::TRIP_STARTED, $booking->fresh()->status);

        // And closes it at the drop point.
        app(BookingService::class)->endTrip($booking->fresh(), 145_940, 'trips/end.jpg', $this->admin->id);
        $this->assertSame(BookingStatus::TRIP_COMPLETED, $booking->fresh()->status);
    }

    public function test_the_driver_and_vehicle_follow_the_ride_and_are_released_at_the_end(): void
    {
        $booking = $this->pendingBooking();
        $service = app(BookingService::class);

        $service->assignDriver($booking, $this->driver->id, $this->admin->id);

        $this->assertSame(DriverStatus::ASSIGNED->value, $this->driver->fresh()->status);
        $this->assertSame(VehicleStatus::ASSIGNED->value, $this->vehicle->fresh()->status);

        // The trip opens once the driver has accepted the ride.
        $service->startTrip($this->confirmed($booking), 145_320, 'trips/start.jpg', $this->admin->id);

        $this->assertSame(DriverStatus::ON_TRIP->value, $this->driver->fresh()->status);
        $this->assertSame(VehicleStatus::BOOKED->value, $this->vehicle->fresh()->status);

        $service->endTrip($booking->fresh(), 145_940, 'trips/end.jpg', $this->admin->id);

        // The ride is over, so both stand free again in the drop city.
        $this->assertSame(DriverStatus::AVAILABLE->value, $this->driver->fresh()->status);
        $this->assertSame(VehicleStatus::AVAILABLE->value, $this->vehicle->fresh()->status);
    }

    public function test_a_driver_refusing_a_ride_moves_it_to_driver_rejected_and_frees_him(): void
    {
        $booking = $this->pendingBooking();

        app(BookingService::class)->assignDriver($booking, $this->driver->id, $this->admin->id);
        app(AssignmentResponseService::class)->reject(
            $booking->fresh()->driverAssignment,
            'My vehicle is in the workshop'
        );

        $booking->refresh();

        $this->assertSame(BookingStatus::DRIVER_REJECTED, $booking->status);
        $this->assertNull($booking->driver_id);

        // The driver is not taking the ride, so he is free for another one.
        $this->assertSame(DriverStatus::AVAILABLE->value, $this->driver->fresh()->status);
        $this->assertSame(VehicleStatus::AVAILABLE->value, $this->vehicle->fresh()->status);
    }

    public function test_a_ride_whose_window_expires_moves_to_driver_rejected(): void
    {
        $booking = $this->pendingBooking();

        app(BookingService::class)->assignDriver($booking, $this->driver->id, $this->admin->id);

        $assignment = $booking->fresh()->driverAssignment;
        $assignment->forceFill(['response_deadline' => now()->subMinute()])->save();

        $this->assertSame(1, app(AssignmentResponseService::class)->rejectExpiredAssignments());

        $this->assertSame(BookingStatus::DRIVER_REJECTED, $booking->fresh()->status);
        $this->assertSame(DriverStatus::AVAILABLE->value, $this->driver->fresh()->status);
    }

    public function test_a_ride_cannot_be_completed_before_it_was_driven(): void
    {
        $booking = $this->pendingBooking();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/cannot go from Pending to Trip Completed/');

        app(BookingStatusService::class)->transition($booking, BookingStatus::TRIP_COMPLETED);
    }

    public function test_a_running_ride_cannot_be_sent_back_to_confirmed(): void
    {
        $booking = $this->pendingBooking();

        $service = app(BookingService::class);
        $service->assignDriver($booking, $this->driver->id, $this->admin->id);
        $service->startTrip($this->confirmed($booking), 145_320, 'trips/start.jpg', $this->admin->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/cannot go from Trip Started to Confirmed/');

        app(BookingStatusService::class)->transition($booking->fresh(), BookingStatus::CONFIRMED);
    }

    public function test_a_finished_ride_can_move_nowhere_at_all(): void
    {
        $booking = $this->pendingBooking();

        $service = app(BookingService::class);
        $service->assignDriver($booking, $this->driver->id, $this->admin->id);
        $service->startTrip($this->confirmed($booking), 145_320, 'trips/start.jpg', $this->admin->id);
        $service->endTrip($booking->fresh(), 145_940, 'trips/end.jpg', $this->admin->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/has ended on Trip Completed/');

        app(BookingStatusService::class)->transition($booking->fresh(), BookingStatus::CANCELLED);
    }

    public function test_reassigning_a_ride_releases_the_previous_driver(): void
    {
        $booking = $this->pendingBooking();

        $firstDriver = $this->driver;
        $secondDriver = Driver::factory()->create([
            'current_city_id' => $this->city->id,
            'status' => DriverStatus::AVAILABLE->value,
        ]);

        $service = app(BookingService::class);

        $service->assignDriver($booking, $firstDriver->id, $this->admin->id);
        $service->assignDriver($booking->fresh(), $secondDriver->id, $this->admin->id);

        $this->assertSame(DriverStatus::AVAILABLE->value, $firstDriver->fresh()->status);
        $this->assertSame(DriverStatus::ASSIGNED->value, $secondDriver->fresh()->status);
        $this->assertSame($secondDriver->id, $booking->fresh()->driver_id);
    }

    public function test_a_driver_the_admin_put_on_leave_is_not_made_available_by_a_release(): void
    {
        $booking = $this->pendingBooking();

        app(BookingService::class)->assignDriver($booking, $this->driver->id, $this->admin->id);

        // The admin takes the driver off the road for a while mid-assignment.
        $this->driver->update(['status' => DriverStatus::ON_LEAVE->value]);

        app(BookingService::class)->cancelBooking($booking->fresh());

        // A status the booking flow never set is left exactly as the admin set it.
        $this->assertSame(DriverStatus::ON_LEAVE->value, $this->driver->fresh()->status);
    }

    public function test_every_status_change_is_recorded_with_a_reason(): void
    {
        $booking = $this->pendingBooking();

        app(BookingService::class)->assignDriver($booking, $this->driver->id, $this->admin->id);
        app(AssignmentResponseService::class)->accept($booking->fresh()->driverAssignment);

        $history = $booking->fresh()->statusHistory()->get();

        $this->assertSame(
            ['Driver Assigned', 'Confirmed'],
            $history->pluck('new_status')->all(),
        );

        $this->assertSame('Driver accepted the assignment.', $history->last()->remarks);
    }

    public function test_cancelling_a_ride_releases_the_driver_and_the_vehicle(): void
    {
        $booking = $this->pendingBooking();

        $service = app(BookingService::class);
        $service->assignDriver($booking, $this->driver->id, $this->admin->id);
        $service->cancelBooking($booking->fresh());

        $this->assertSame(BookingStatus::CANCELLED, $booking->fresh()->status);
        $this->assertSame(DriverStatus::AVAILABLE->value, $this->driver->fresh()->status);
        $this->assertSame(VehicleStatus::AVAILABLE->value, $this->vehicle->fresh()->status);
    }

    public function test_a_driver_still_covering_another_ride_is_not_released(): void
    {
        $first = $this->pendingBooking();
        $second = $this->pendingBooking();

        $service = app(BookingService::class);
        $service->assignDriver($first, $this->driver->id, $this->admin->id);
        $service->assignDriver($second, $this->driver->id, $this->admin->id);

        $service->cancelBooking($first->fresh());

        // One ride is still holding him, so he is not free yet.
        $this->assertSame(DriverStatus::ASSIGNED->value, $this->driver->fresh()->status);
    }

    /**
     * A new customer request waiting for a driver, with the vehicle already on
     * it, which is what the booking form produces.
     */
    private function pendingBooking(): Booking
    {
        return Booking::factory()->create([
            'pickup_city_id' => $this->city->id,
            'drop_city_id' => $this->city->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => BookingStatus::PENDING->value,
        ]);
    }

    /**
     * The booking with its driver having accepted, i.e. the Confirmed ride a
     * driver is then allowed to open with his odometer reading.
     */
    private function confirmed(Booking $booking): Booking
    {
        app(AssignmentResponseService::class)->accept($booking->fresh()->driverAssignment);

        return $booking->fresh();
    }
}
