<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RejectionSource;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A finished ride is the record of what actually happened: the odometer
 * readings, the bill worked out from them, the driver who drove it. Once it has
 * ended nobody may change any of it, so the figures cannot be quietly rewritten
 * after the fact. The same is true of a cancelled one: nothing follows it either.
 */
class CompletedRideIsLockedTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $associate;

    private City $city;

    private Driver $ownDriver;

    private Vehicle $ownVehicle;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->associate = User::factory()->associate()->create();
        $this->city = City::factory()->create(['name' => 'Udaipur']);

        $this->ownVehicle = Vehicle::factory()->create([
            'city_id' => $this->city->id,
            'associate_id' => null,
        ]);
        $this->ownDriver = Driver::factory()->create([
            'current_city_id' => $this->city->id,
            'associate_id' => null,
        ]);

        $this->associate->assignedCities()->sync([$this->city->id]);
    }

    /**
     * A ride the admin closed off: finished, with a bill worked out of the two
     * odometer readings.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function completedRide(array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'pickup_city_id' => $this->city->id,
            'drop_city_id' => $this->city->id,
            'vehicle_id' => $this->ownVehicle->id,
            'driver_id' => $this->ownDriver->id,
            'associate_id' => null,
            'status' => BookingStatus::TRIP_COMPLETED->value,
            'start_odometer_km' => 145320,
            'end_odometer_km' => 145732,
            'trip_started_at' => now()->subHours(6),
            'trip_ended_at' => now()->subHour(),
            'total_amount' => 4500.00,
            'billed_days' => 1,
            'billed_included_km' => 100,
            'extra_km' => 12,
        ], $overrides));
    }

    public function test_the_admin_cannot_reassign_the_driver_of_a_finished_trip(): void
    {
        $ride = $this->completedRide();

        $this->actingAs($this->admin)->put(route('admin.bookings.update', $ride), [
            'status' => BookingStatus::DRIVER_ASSIGNED->value,
            'driver_id' => Driver::factory()->create([
                'current_city_id' => $this->city->id,
                'associate_id' => null,
            ])->id,
        ])->assertSessionHas('error');

        $ride->refresh();

        // Reassigning would have dragged the finished trip back into play.
        $this->assertEquals(BookingStatus::TRIP_COMPLETED, $ride->status);
        $this->assertSame($this->ownDriver->id, (int) $ride->driver_id);
    }

    public function test_the_admin_cannot_cancel_a_finished_trip(): void
    {
        $ride = $this->completedRide();

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.cancel', $ride))
            ->assertSessionHas('error');

        $this->assertSame(BookingStatus::TRIP_COMPLETED, $ride->fresh()->status);
    }

    public function test_the_admin_cannot_change_the_status_of_a_finished_trip(): void
    {
        $ride = $this->completedRide();

        $this->actingAs($this->admin)->put(route('admin.bookings.update', $ride), [
            'status' => BookingStatus::CANCELLED->value,
        ])->assertSessionHas('error');

        $this->assertEquals(BookingStatus::TRIP_COMPLETED, $ride->fresh()->status);
    }

    public function test_the_admin_cannot_swap_the_vehicle_of_a_finished_trip(): void
    {
        $ride = $this->completedRide();

        $this->actingAs($this->admin)->put(route('admin.bookings.update', $ride), [
            'status' => BookingStatus::TRIP_COMPLETED->value,
            'vehicle_id' => Vehicle::factory()->create(['city_id' => $this->city->id])->id,
        ])->assertSessionHas('error');

        $this->assertSame($this->ownVehicle->id, (int) $ride->fresh()->vehicle_id);
    }

    public function test_the_assign_form_is_replaced_by_a_read_only_notice(): void
    {
        $ride = $this->completedRide();

        $this->actingAs($this->admin)
            ->get(route('admin.bookings.show', $ride))
            ->assertOk()
            ->assertSee('read only')
            ->assertDontSee('Save Vehicle &amp; Driver', false);
    }

    public function test_the_service_refuses_to_reopen_a_finished_trip(): void
    {
        $ride = $this->completedRide();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('can no longer be changed');

        app(BookingService::class)->assignDriver(
            $ride,
            Driver::factory()->create(['current_city_id' => $this->city->id])->id,
            $this->admin->id
        );
    }

    public function test_the_service_refuses_to_cancel_a_finished_trip(): void
    {
        $ride = $this->completedRide();

        $this->expectException(\Exception::class);

        app(BookingService::class)->cancelBooking($ride);
    }

    public function test_the_associate_cannot_change_a_finished_trip_either(): void
    {
        $ride = $this->completedRide(['associate_id' => $this->associate->id]);

        $this->actingAs($this->associate)
            ->post(route('associate.bookings.cancel', $ride))
            ->assertSessionHas('error');

        $this->assertEquals(BookingStatus::TRIP_COMPLETED, $ride->fresh()->status);

        $this->actingAs($this->associate)
            ->get(route('associate.bookings.show', $ride))
            ->assertOk()
            ->assertSee('read only')
            ->assertDontSee('Save Vehicle &amp; Driver', false);
    }

    public function test_a_rejected_ride_is_not_locked_so_the_admin_can_reassign_it(): void
    {
        // A refused ride is reopened by giving it another driver, so it has to
        // stay editable even though it will never run.
        $ride = $this->completedRide([
            'status' => BookingStatus::REJECTED->value,
            'trip_ended_at' => null,
            'trip_started_at' => null,
        ]);

        $this->assertFalse($ride->isLocked());

        // The ride already carries this driver, so the ride is reassigned to a
        // second one to show a refused ride can be put back in play.
        $this->actingAs($this->admin)->put(route('admin.bookings.update', $ride), [
            'driver_id' => Driver::factory()->create([
                'current_city_id' => $this->city->id,
                'associate_id' => null,
            ])->id,
        ])->assertSessionHasNoErrors();

        // Assigning a driver to a rejected ride puts it back in play.
        $this->assertSame(BookingStatus::DRIVER_ASSIGNED, $ride->fresh()->status);
    }

    public function test_a_driver_rejected_ride_is_reopened_by_assigning_another_driver(): void
    {
        $ride = $this->completedRide([
            'status' => BookingStatus::DRIVER_REJECTED->value,
            'rejection_source' => RejectionSource::Driver,
            'rejection_reason' => 'My vehicle is in the workshop',
            'trip_ended_at' => null,
            'trip_started_at' => null,
        ]);

        $this->assertFalse($ride->isLocked());

        $this->actingAs($this->admin)->put(route('admin.bookings.update', $ride), [
            'driver_id' => Driver::factory()->create([
                'current_city_id' => $this->city->id,
                'associate_id' => null,
            ])->id,
        ])->assertSessionHasNoErrors();

        $ride->refresh();

        $this->assertSame(BookingStatus::DRIVER_ASSIGNED, $ride->status);

        // The old refusal no longer describes the ride, so it is cleared rather
        // than left contradicting the status it was sitting next to.
        $this->assertNull($ride->rejection_reason);
        $this->assertNull($ride->rejection_source);
    }

    public function test_a_running_ride_is_still_editable(): void
    {
        $ride = Booking::factory()->create([
            'pickup_city_id' => $this->city->id,
            'drop_city_id' => $this->city->id,
            'vehicle_id' => $this->ownVehicle->id,
            'status' => BookingStatus::TRIP_STARTED->value,
        ]);

        $this->assertFalse($ride->isLocked());

        $this->actingAs($this->admin)
            ->get(route('admin.bookings.show', $ride))
            ->assertOk()
            ->assertSee('Save Vehicle &amp; Driver', false);
    }

    public function test_a_cancelled_ride_is_frozen(): void
    {
        $ride = $this->completedRide([
            'status' => BookingStatus::CANCELLED->value,
            'trip_ended_at' => null,
            'trip_started_at' => null,
        ]);

        // Nothing follows a cancelled ride either, so it is closed for good.
        $this->assertTrue($ride->isLocked());

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.cancel', $ride))
            ->assertSessionHas('error');

        $this->assertSame(BookingStatus::CANCELLED, $ride->fresh()->status);
    }
}
