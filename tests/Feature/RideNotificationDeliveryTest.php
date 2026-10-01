<?php

namespace Tests\Feature;

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
 * The ride notification has to reach the bell the moment the driver answers,
 * even when no queue worker is running.
 *
 * The suite-wide queue driver is "sync", which runs a queued notification
 * inline and would hide the bug, so this test forces the "database" driver that
 * production uses. With that driver, a queued notification would sit in the
 * jobs table and never reach the bell; the notification is delivered
 * synchronously, so the bell row is written during the driver's request.
 */
class RideNotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        // Mimic production: the database queue, with no worker consuming it.
        config(['queue.default' => 'database']);

        $this->admin = User::factory()->create(['username' => 'admin001']);

        $driverUser = User::factory()->create([
            'name' => 'Ramesh Kumar',
            'email' => null,
            'username' => 'driver001',
            'role' => User::ROLE_DRIVER,
        ]);

        $this->driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'name' => 'Ramesh Kumar',
        ]);
    }

    public function test_a_rejection_reaches_the_bell_without_a_queue_worker(): void
    {
        $assignment = $this->assignRide();

        app(AssignmentResponseService::class)->reject($assignment, 'My car is in the workshop');

        $this->assertBellHasNotificationFor($this->admin);
    }

    public function test_an_acceptance_reaches_the_bell_without_a_queue_worker(): void
    {
        $assignment = $this->assignRide();

        app(AssignmentResponseService::class)->accept($assignment);

        $this->assertBellHasNotificationFor($this->admin);
    }

    /**
     * The bell persists the database channel rows; a queued notification would
     * leave this table empty because no worker ever runs during the request.
     */
    private function assertBellHasNotificationFor(User $user): void
    {
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => $user->getMorphClass(),
        ]);
    }

    /**
     * Assign a fresh pending booking to the driver through the real service, so
     * the response window is opened exactly as it is in production.
     */
    private function assignRide(): DriverAssignment
    {
        $home = City::factory()->create(['name' => 'Jaipur']);

        $booking = Booking::factory()->create([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'status' => BookingStatus::PENDING->value,
            'pickup_city_id' => $home->id,
            'drop_city_id' => $home->id,
            'driver_id' => null,
        ]);

        app(BookingService::class)->assignDriver($booking, $this->driver->id, $this->admin->id);

        return $booking->fresh()->driverAssignment;
    }
}
