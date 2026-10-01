<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Mail\NewBookingRequest;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;


class OutOfNetworkBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_ride_to_a_place_we_do_not_run_in_is_served_from_the_pickup_city(): void
    {
        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $vehicle = Vehicle::factory()->create([
            'city_id' => $jaipur->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);

        $booking = $this->bookTo('Kishangarh', $jaipur, $vehicle);

        // The ride is run from Jaipur, but it is a ride to Kishangarh.
        $this->assertSame($jaipur->id, $booking->pickup_city_id);
        $this->assertSame($jaipur->id, $booking->drop_city_id);
        $this->assertSame('Kishangarh', $booking->drop_city_label);

        // And that is what the screens read, rather than "Jaipur to Jaipur".
        $this->assertSame('Kishangarh', $booking->displayDropCity());
        $this->assertSame('Jaipur → Kishangarh', $booking->displayRoute());
        $this->assertTrue($booking->isOutOfNetwork());
    }

    public function test_a_ride_to_one_of_our_cities_is_served_from_that_city(): void
    {
        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $udaipur = City::factory()->create(['name' => 'Udaipur']);
        $vehicle = Vehicle::factory()->create([
            'city_id' => $jaipur->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);

        $booking = $this->bookTo('Udaipur', $jaipur, $vehicle);

        $this->assertSame($udaipur->id, $booking->drop_city_id);
        $this->assertSame('Udaipur', $booking->displayDropCity());
        $this->assertFalse($booking->isOutOfNetwork());
    }

    public function test_a_ride_may_not_start_outside_the_cities_we_run_in(): void
    {
        // More than one city, so there is no single obvious pickup to fall back
        // on and the unknown pickup city has to be refused.
        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $udaipur = City::factory()->create(['name' => 'Udaipur']);

        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::AVAILABLE->value]);

        $this->expectException(\Exception::class);

        $this->bookTo('Kishangarh', null, $vehicle, pickupCityId: 9999);
    }

    public function test_the_driver_guard_names_the_city_the_ride_really_goes_to(): void
    {
        Mail::fake();

        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $udaipur = City::factory()->create(['name' => 'Udaipur']);
        $vehicle = Vehicle::factory()->create([
            'city_id' => $jaipur->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);

        $booking = $this->bookTo('Kishangarh', $jaipur, $vehicle);

        // Willing to go to Udaipur, which is not where this ride ends.
        $driver = Driver::factory()->create([
            'status' => 'Available',
            'current_city_id' => $jaipur->id,
        ]);
        $driver->preferredCities()->sync([$udaipur->id]);

        $message = null;

        try {
            app(BookingService::class)->assignDriver($booking, $driver->id, 1);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        // The refusal names the destination the customer asked for, not the
        // city the cab happens to be standing in.
        $this->assertNotNull($message, 'The driver should have been refused this ride.');
        $this->assertStringContainsString('Kishangarh', $message);
        $this->assertStringNotContainsString('Udaipur', $message);
    }

    public function test_the_admin_and_driver_screens_show_the_written_destination(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $driverUser = User::factory()->create([
            'name' => 'Test Driver',
            'email' => null,
            'username' => 'driver001',
            'role' => User::ROLE_DRIVER,
        ]);

        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $vehicle = Vehicle::factory()->create([
            'city_id' => $jaipur->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);

        $booking = $this->bookTo('Kishangarh', $jaipur, $vehicle);

        // A driver only sees his own rides, so the booking is put on his profile.
        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'current_city_id' => $jaipur->id,
            'status' => 'Available',
        ]);
        $booking->forceFill(['driver_id' => $driver->id, 'status' => BookingStatus::CONFIRMED->value])->save();

        $adminView = $this->actingAs($admin)->get(route('admin.bookings.show', $booking));
        $adminView->assertStatus(200);
        $adminView->assertSee('Kishangarh');

        $driverView = $this->actingAs($driverUser)->get(route('driver.rides'));
        $driverView->assertStatus(200);
        $driverView->assertSee('Kishangarh');
    }

    public function test_no_screen_labels_a_long_haul_ride_as_outside_our_network(): void
    {
        Mail::fake();

        $admin = User::factory()->create();

        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $vehicle = Vehicle::factory()->create([
            'city_id' => $jaipur->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);

        $booking = $this->bookTo('Kishangarh', $jaipur, $vehicle);

        // The ride is still recognised as a long haul in the data, it is simply
        // never shown as one: the phrase belongs to no screen at all.
        $this->assertTrue($booking->isOutOfNetwork());

        $this->actingAs($admin)->get(route('admin.bookings.show', $booking))
            ->assertDontSee('Outside our network');

        $this->actingAs($admin)->get(route('admin.bookings.index'))
            ->assertDontSee('Outside our network');

        $this->get(route('booking.confirmation', $booking->booking_number))
            ->assertDontSee('Outside our network');
    }

    public function test_the_new_booking_mail_names_the_written_destination(): void
    {
        Mail::fake();

        Setting::put('mail.booking_notification_recipients', 'ops@example.com');

        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $vehicle = Vehicle::factory()->create([
            'city_id' => $jaipur->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);

        $this->bookTo('Kishangarh', $jaipur, $vehicle);

        Mail::assertSent(NewBookingRequest::class, function (NewBookingRequest $mail) {
            $rendered = $mail->render();

            return str_contains($rendered, 'Kishangarh')
                && str_contains($rendered, 'Jaipur')
                && ! str_contains($rendered, 'outside our network');
        });
    }

    /**
     * File a booking request to the given written destination, the way a
     * customer does from the search page.
     */
    private function bookTo(string $destination, ?City $pickupCity, Vehicle $vehicle, ?int $pickupCityId = null): Booking
    {
        $serviceType = ServiceType::factory()->create();

        return app(BookingService::class)->createBookingRequest([
            'customer_name' => 'Asha Sharma',
            'customer_phone' => '9876543210',
            'customer_email' => 'asha@example.com',
            'pickup_city_id' => $pickupCityId ?? $pickupCity?->id,
            'pickup_location' => 'Station Road, '.($pickupCity?->name ?? 'Nowhere'),
            'drop_city' => $destination,
            'drop_location' => 'Hawa Mahal Road',
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '10:00',
            'passengers' => 1,
            'service_type_id' => $serviceType->id,
            'vehicle_id' => $vehicle->id,
        ]);
    }
}
