<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DriverStatus;
use App\Enums\VehicleStatus;
use App\Mail\RideTrackingLinkMail;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AssignmentResponseService;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * When a driver starts a ride, the live tracking link goes out to him and to
 * the operations team, and the running ride can be followed from the admin
 * panel: the booking screen and the dashboard both link to the live map.
 */
class RideTrackingTest extends TestCase
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

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->city = City::factory()->create();

        $this->vehicle = Vehicle::factory()->create([
            'city_id' => $this->city->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);

        $this->driver = Driver::factory()->create([
            'current_city_id' => $this->city->id,
            'status' => DriverStatus::AVAILABLE->value,
            'email' => 'driver@example.com',
        ]);
    }

    public function test_starting_a_ride_emails_the_tracking_link_to_the_driver_and_the_admin(): void
    {
        Mail::fake();
        Setting::put('mail.booking_notification_recipients', 'ops@example.com');

        $booking = $this->startableBooking();

        app(BookingService::class)->startTrip($booking, 145_320, 'trips/start.jpg', $this->admin->id);

        $booking->refresh();

        $this->assertNotNull($booking->tracking_id);

        Mail::assertSentTimes(RideTrackingLinkMail::class, 2);
        Mail::assertSent(RideTrackingLinkMail::class, fn (RideTrackingLinkMail $mail): bool => $mail->hasTo('driver@example.com'));
        Mail::assertSent(RideTrackingLinkMail::class, fn (RideTrackingLinkMail $mail): bool => $mail->hasTo('ops@example.com'));
    }

    public function test_the_tracking_link_is_not_emailed_when_mail_is_disabled(): void
    {
        Mail::fake();
        Setting::put('mail.enabled', false);

        $booking = $this->startableBooking();

        app(BookingService::class)->startTrip($booking, 145_320, 'trips/start.jpg', $this->admin->id);

        Mail::assertNothingSent();
    }

    public function test_a_mail_failure_does_not_stop_the_ride_from_starting(): void
    {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP is down'));

        $booking = $this->startableBooking();

        app(BookingService::class)->startTrip($booking, 145_320, 'trips/start.jpg', $this->admin->id);

        $booking->refresh();

        $this->assertSame(BookingStatus::TRIP_STARTED, $booking->status);
        $this->assertNotNull($booking->tracking_id);
    }

    public function test_the_admin_booking_screen_links_to_the_live_map_while_the_ride_runs(): void
    {
        $booking = $this->runningBooking();

        $this->actingAs($this->admin)
            ->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee(route('tracking.show', $booking->tracking_id), false);
    }

    public function test_the_admin_booking_screen_has_no_tracking_link_before_the_ride_starts(): void
    {
        $booking = $this->startableBooking();

        $this->actingAs($this->admin)
            ->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertDontSee('/admin/track/ride/', false);
    }

    public function test_the_dashboard_links_to_the_live_map_while_the_ride_runs(): void
    {
        $booking = $this->runningBooking();

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('tracking.show', $booking->tracking_id), false);
    }

    public function test_the_dashboard_has_no_tracking_link_before_the_ride_starts(): void
    {
        $booking = $this->startableBooking();

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('/admin/track/ride/', false);
    }

    public function test_the_live_map_page_loads_for_a_started_ride(): void
    {
        $booking = $this->runningBooking();

        $this->get(route('tracking.show', $booking->tracking_id))
            ->assertOk()
            ->assertSee($booking->booking_number);
    }

    public function test_the_assigned_driver_can_push_the_live_location(): void
    {
        $driverUser = User::factory()->create(['role' => User::ROLE_DRIVER]);
        $this->driver->update(['user_id' => $driverUser->id]);

        $booking = $this->runningBooking();

        $this->actingAs($driverUser)
            ->postJson(route('driver.location.update', $booking), [
                'latitude' => 26.9124,
                'longitude' => 75.7873,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $booking->refresh();

        $this->assertEquals(26.9124, (float) $booking->current_latitude);
        $this->assertEquals(75.7873, (float) $booking->current_longitude);
    }

    public function test_another_driver_cannot_move_the_marker_of_a_ride(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_DRIVER]);
        Driver::factory()->create(['user_id' => $otherUser->id]);

        $booking = $this->runningBooking();

        $this->actingAs($otherUser)
            ->postJson(route('driver.location.update', $booking), [
                'latitude' => 26.9124,
                'longitude' => 75.7873,
            ])
            ->assertForbidden();
    }

    public function test_the_live_location_api_serves_the_drivers_position(): void
    {
        $booking = $this->runningBooking();

        $booking->update([
            'current_latitude' => 26.9124,
            'current_longitude' => 75.7873,
        ]);

        $response = $this->getJson(route('tracking.location', $booking->tracking_id))->assertOk();

        $this->assertEquals(26.9124, (float) $response->json('latitude'));
        $this->assertEquals(75.7873, (float) $response->json('longitude'));
    }

    /**
     * A confirmed ride with its driver on it, which is the state a driver is
     * allowed to open with his odometer reading.
     */
    private function startableBooking(): Booking
    {
        $booking = Booking::factory()->create([
            'pickup_city_id' => $this->city->id,
            'drop_city_id' => $this->city->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => BookingStatus::PENDING->value,
        ]);

        app(BookingService::class)->assignDriver($booking, $this->driver->id, $this->admin->id);
        app(AssignmentResponseService::class)->accept($booking->fresh()->driverAssignment);

        return $booking->fresh();
    }

    /**
     * A ride already under way, with its live tracking link generated.
     */
    private function runningBooking(): Booking
    {
        $booking = $this->startableBooking();

        app(BookingService::class)->startTrip($booking, 145_320, 'trips/start.jpg', $this->admin->id);

        return $booking->fresh();
    }
}
