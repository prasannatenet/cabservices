<?php

namespace Tests\Feature;

use App\Enums\AssignmentResponseStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AssignmentResponseService;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DriverAssignmentResponseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => null,
            'username' => 'driver001',
            'role' => User::ROLE_DRIVER,
        ]);

        $this->driver = Driver::factory()->create(['user_id' => $this->user->id]);
    }

    /**
     * Assign a fresh pending booking to a driver through the real service so
     * the response window is opened exactly as it is in production.
     */
    private function assignRide(?Driver $driver = null): DriverAssignment
    {
        $booking = Booking::factory()->create([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'status' => BookingStatus::PENDING->value,
        ]);

        app(BookingService::class)->assignDriver($booking, ($driver ?? $this->driver)->id, User::factory()->create()->id);

        return $booking->fresh()->driverAssignment;
    }
}
