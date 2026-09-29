<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AssignmentResponseService;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RejectionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $driverUser;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['username' => 'admin001']);

        $this->driverUser = User::factory()->create([
            'name' => 'Ramesh Kumar',
            'email' => null,
            'username' => 'driver001',
            'role' => User::ROLE_DRIVER,
        ]);

        $this->driver = Driver::factory()->create(['user_id' => $this->driverUser->id]);
    }

    /**
     * Assign a fresh pending booking to the driver, then let the driver refuse it.
     */
    private function rejectedBooking(string $reason): Booking
    {
        $booking = $this->pendingBooking();

        app(BookingService::class)->assignDriver($booking, $this->driver->id, $this->admin->id);

        app(AssignmentResponseService::class)->reject($booking->fresh()->driverAssignment, $reason);

        return $booking->fresh();
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

    public function test_booking_moves_to_rejected_when_the_driver_refuses_it(): void
    {
        $booking = $this->rejectedBooking('Vehicle is in the workshop');

        $this->assertSame(BookingStatus::REJECTED, $booking->status);
        $this->assertNull($booking->driver_id);
        $this->assertDatabaseHas('driver_assignments', [
            'booking_id' => $booking->id,
            'driver_id' => $this->driver->id,
            'rejection_reason' => 'Vehicle is in the workshop',
        ]);
    }

    public function test_driver_dashboard_lists_the_ride_the_driver_refused(): void
    {
        $booking = $this->rejectedBooking('Vehicle is in the workshop');

        $response = $this->actingAs($this->driverUser)->get(route('driver.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Rides You Rejected');
        $response->assertSee($booking->booking_number);
        $response->assertSee('Vehicle is in the workshop');
    }

    public function test_driver_rejections_page_shows_every_refused_ride_with_its_reason(): void
    {
        $booking = $this->rejectedBooking('I am already on another trip');

        $response = $this->actingAs($this->driverUser)->get(route('driver.rejections.index'));

        $response->assertStatus(200);
        $response->assertSee($booking->booking_number);
        $response->assertSee('I am already on another trip');
    }

    public function test_another_driver_does_not_see_someone_elses_rejection(): void
    {
        $booking = $this->rejectedBooking('Pickup is too far from my city');

        $otherUser = User::factory()->create([
            'email' => null,
            'username' => 'driver002',
            'role' => User::ROLE_DRIVER,
        ]);
        Driver::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($otherUser)->get(route('driver.rejections.index'));

        $response->assertStatus(200);
        $response->assertDontSee($booking->booking_number);
    }

    public function test_admin_dashboard_shows_which_driver_rejected_the_ride(): void
    {
        $booking = $this->rejectedBooking('Family function out of station');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Driver Rejections');
        $response->assertSee($booking->booking_number);
        $response->assertSee($this->driver->name);
        $response->assertSee('Family function out of station');
    }

    public function test_admin_booking_page_shows_the_rejection_details(): void
    {
        $booking = $this->rejectedBooking('Vehicle is not cleaned');

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));

        $response->assertStatus(200);
        $response->assertSee('Rejection Details');
        $response->assertSee($this->driver->name);
        $response->assertSee('Vehicle is not cleaned');
    }

    public function test_admin_booking_list_shows_the_rejection_against_the_booking(): void
    {
        $booking = $this->rejectedBooking('Pickup time does not suit me');

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.index'));

        $response->assertStatus(200);
        $response->assertSee($booking->booking_number);
        $response->assertSee('Pickup time does not suit me');
    }

    public function test_admin_booking_list_labels_a_driver_refusal_as_driver_rejected(): void
    {
        $this->rejectedBooking('Pickup time does not suit me');

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.index'));

        $response->assertStatus(200);
        $response->assertSee(Booking::DRIVER_REJECTED_LABEL);
    }

    public function test_admin_can_filter_the_booking_list_by_driver_rejections(): void
    {
        $driverRejected = $this->rejectedBooking('Vehicle is in the workshop');
        $adminRejected = $this->pendingBooking();

        app(BookingService::class)->rejectBooking($adminRejected, $this->admin->id, 'Customer cancelled the trip');

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.index', [
            'status' => Booking::DRIVER_REJECTED_LABEL,
        ]));

        $response->assertStatus(200);
        $response->assertSee($driverRejected->booking_number);
        $response->assertDontSee($adminRejected->booking_number);
    }

    public function test_driver_activity_overview_counts_the_refused_ride_against_the_driver(): void
    {
        $this->rejectedBooking('Vehicle is in the workshop');

        $response = $this->actingAs($this->admin)->get(route('admin.driver-activity.index'));

        $response->assertStatus(200);
        $response->assertSee('Rides Per Driver');
        $response->assertSee($this->driver->name);
        $response->assertSee('Driver Rejections');
    }

    public function test_driver_activity_overview_sorts_and_filters_without_breaking(): void
    {
        $this->rejectedBooking('Vehicle is in the workshop');

        $response = $this->actingAs($this->admin)->get(route('admin.driver-activity.index', [
            'sort' => 'rejected_count',
            'direction' => 'asc',
            'search' => $this->driver->name,
            'city_id' => $this->driver->current_city_id,
            'status' => $this->driver->status,
        ]));

        $response->assertStatus(200);
        $response->assertSee($this->driver->name);
    }

    public function test_driver_activity_page_splits_completed_rides_from_refusals(): void
    {
        $rejected = $this->rejectedBooking('Pickup is too far from my city');

        $city = City::factory()->create();
        $completed = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => Vehicle::factory()->create()->id,
            'driver_id' => $this->driver->id,
            'status' => BookingStatus::TRIP_COMPLETED->value,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.driver-activity.show', $this->driver));

        $response->assertStatus(200);
        $response->assertSee('Completed Rides');
        $response->assertSee($completed->booking_number);
        $response->assertSee('Rejected Rides');
        $response->assertSee($rejected->booking_number);
        $response->assertSee('Pickup is too far from my city');
    }

    public function test_driver_activity_page_hides_another_drivers_rides(): void
    {
        $booking = $this->rejectedBooking('Pickup is too far from my city');

        $otherUser = User::factory()->create([
            'email' => null,
            'username' => 'driver003',
            'role' => User::ROLE_DRIVER,
        ]);
        $otherDriver = Driver::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.driver-activity.show', $otherDriver));

        $response->assertStatus(200);
        $response->assertDontSee($booking->booking_number);
    }

    public function test_expired_assignment_is_reported_as_no_answer_in_time(): void
    {
        $booking = $this->pendingBooking();

        app(BookingService::class)->assignDriver($booking, $this->driver->id, $this->admin->id);

        $assignment = $booking->fresh()->driverAssignment;
        $assignment->forceFill(['response_deadline' => now()->subMinute()])->save();

        $this->assertSame(1, app(AssignmentResponseService::class)->rejectExpiredAssignments());

        $driverResponse = $this->actingAs($this->driverUser)->get(route('driver.rejections.index'));
        $driverResponse->assertStatus(200);
        $driverResponse->assertSee($booking->booking_number);
        $driverResponse->assertSee('No answer in time');

        $adminResponse = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('No answer in time');
    }
}
