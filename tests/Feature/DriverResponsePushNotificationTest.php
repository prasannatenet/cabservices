<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\DriverRespondedToAssignment;
use App\Services\AssignmentResponseService;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The desktop notification raised when a driver answers an assigned ride.
 */
class DriverResponsePushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $driverUser;

    private Driver $driver;

    private City $home;

    private City $foreign;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->admin = User::factory()->create(['username' => 'admin001']);

        $this->driverUser = User::factory()->create([
            'name' => 'Ramesh Kumar',
            'email' => null,
            'username' => 'driver001',
            'role' => User::ROLE_DRIVER,
        ]);

        $this->driver = Driver::factory()->create([
            'user_id' => $this->driverUser->id,
            'name' => 'Ramesh Kumar',
        ]);

        $this->home = City::factory()->create(['name' => 'Jaipur']);
        $this->foreign = City::factory()->create(['name' => 'Nagpur']);
    }

    public function test_admin_is_notified_when_the_driver_accepts(): void
    {
        $assignment = $this->assignRide();

        app(AssignmentResponseService::class)->accept($assignment);

        Notification::assertSentTo(
            $this->admin,
            DriverRespondedToAssignment::class,
            function (DriverRespondedToAssignment $notification): bool {
                $payload = $notification->toArray($this->admin);

                return $payload['title'] === 'Ride confirmed'
                    && str_contains($payload['body'], 'Ramesh Kumar')
                    && str_contains($payload['body'], $payload['booking_number']);
            }
        );
    }

    public function test_rejection_notification_carries_the_drivers_reason(): void
    {
        $assignment = $this->assignRide();

        app(AssignmentResponseService::class)->reject($assignment, 'My car is in the workshop');

        Notification::assertSentTo(
            $this->admin,
            DriverRespondedToAssignment::class,
            function (DriverRespondedToAssignment $notification): bool {
                $payload = $notification->toArray($this->admin);

                return $payload['title'] === 'Ride rejected'
                    && str_contains($payload['body'], 'My car is in the workshop');
            }
        );
    }

    public function test_expiring_the_window_notifies_the_admin(): void
    {
        $assignment = $this->assignRide();

        $assignment->forceFill(['response_deadline' => now()->subMinute()])->save();

        app(AssignmentResponseService::class)->rejectExpiredAssignments();

        Notification::assertSentTo(
            $this->admin,
            DriverRespondedToAssignment::class,
            function (DriverRespondedToAssignment $notification): bool {
                return $notification->toArray($this->admin)['response'] === 'Auto Rejected';
            }
        );
    }

    public function test_the_associate_who_owns_the_assigned_driver_is_notified(): void
    {
        $associate = User::factory()->associate()->create();
        $associate->assignedCities()->sync([$this->home->id]);

        $assignment = $this->assignRide(driverAssociate: $associate);

        app(AssignmentResponseService::class)->accept($assignment);

        Notification::assertSentTo($associate, DriverRespondedToAssignment::class);
    }

    public function test_an_associate_who_merely_manages_the_pickup_city_is_not_notified(): void
    {
        // Managing the pickup city is not enough: the admin has to assign one of
        // the associate's own drivers, and he has not done so here.
        $associate = User::factory()->associate()->create();
        $associate->assignedCities()->sync([$this->home->id]);

        $assignment = $this->assignRide();

        app(AssignmentResponseService::class)->accept($assignment);

        Notification::assertNotSentTo($associate, DriverRespondedToAssignment::class);
    }

    public function test_associate_managing_another_city_is_not_notified(): void
    {
        $associate = User::factory()->associate()->create();
        $associate->assignedCities()->sync([$this->foreign->id]);

        $assignment = $this->assignRide();

        app(AssignmentResponseService::class)->accept($assignment);

        Notification::assertNotSentTo($associate, DriverRespondedToAssignment::class);
    }

    public function test_driver_is_never_notified(): void
    {
        $assignment = $this->assignRide();

        app(AssignmentResponseService::class)->accept($assignment);

        Notification::assertNotSentTo($this->driverUser, DriverRespondedToAssignment::class);
    }

    public function test_nothing_is_pushed_when_the_master_switch_is_off(): void
    {
        Setting::put('push.enabled', false);

        $assignment = $this->assignRide();

        app(AssignmentResponseService::class)->accept($assignment);

        Notification::assertNothingSent();
    }

    public function test_nothing_is_pushed_when_accepts_are_disabled(): void
    {
        Setting::put('push.notify_on_accept', false);

        $assignment = $this->assignRide();

        app(AssignmentResponseService::class)->accept($assignment);

        Notification::assertNothingSent();
    }

    public function test_rejection_is_still_pushed_when_accepts_are_disabled(): void
    {
        Setting::put('push.notify_on_accept', false);

        $assignment = $this->assignRide();

        app(AssignmentResponseService::class)->reject($assignment, 'Fuel is finished');

        Notification::assertSentTo($this->admin, DriverRespondedToAssignment::class);
    }

    public function test_a_subscription_can_be_stored_and_removed_again(): void
    {
        $payload = [
            'endpoint' => 'https://updates.push.services.mozilla.com/wpush/v2/abc123',
            'keys' => [
                'p256dh' => 'public-key-value',
                'auth' => 'auth-secret-value',
            ],
        ];

        $this->actingAs($this->admin)
            ->postJson(route('notifications.subscription.store'), $payload)
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $this->admin->id,
            'subscribable_type' => $this->admin->getMorphClass(),
            'endpoint' => $payload['endpoint'],
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('notifications.subscription.destroy'), ['endpoint' => $payload['endpoint']])
            ->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', [
            'subscribable_id' => $this->admin->id,
            'endpoint' => $payload['endpoint'],
        ]);
    }

    public function test_an_associate_can_manage_his_own_subscription(): void
    {
        $associate = User::factory()->associate()->create();

        $this->actingAs($associate)
            ->getJson(route('notifications.key'))
            ->assertOk();
    }

    public function test_a_driver_cannot_reach_the_subscription_endpoints(): void
    {
        $this->actingAs($this->driverUser)
            ->getJson(route('notifications.key'))
            ->assertRedirect(route('driver.dashboard'));
    }

    /**
     * Assign a fresh pending booking to the driver through the real service, so
     * the response window is opened exactly as it is in production.
     *
     * Passing an associate makes the driver his, which is what hands the ride
     * over to that associate in turn.
     */
    private function assignRide(?User $driverAssociate = null): DriverAssignment
    {
        // The driver created in setUp is reused, so handing him to an associate
        // is what makes the ride that associate's ride.
        $this->driver->update(['associate_id' => $driverAssociate?->id]);

        $booking = Booking::factory()->create([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'status' => BookingStatus::PENDING->value,
            'pickup_city_id' => $this->home->id,
            'drop_city_id' => $this->home->id,
            'driver_id' => null,
        ]);

        app(BookingService::class)->assignDriver($booking, $this->driver->id, $this->admin->id);

        return $booking->fresh()->driverAssignment;
    }
}
