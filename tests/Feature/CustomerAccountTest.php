<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DriverStatus;
use App\Enums\VehicleStatus;
use App\Mail\CustomerLoginDetails;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AssignmentResponseService;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * What happens to the customer when his ride is confirmed: he is given an
 * account, that account is linked to the ride, and his login details are emailed
 * to him once and once only.
 */
class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private City $city;

    private Vehicle $vehicle;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Every confirmation mails something, so the mailer is faked throughout
        // and each test looks at the mailables it actually cares about.
        Mail::fake();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->city = City::factory()->create();

        $this->vehicle = Vehicle::factory()->create([
            'city_id' => $this->city->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);
    }

    public function test_accepting_a_ride_creates_a_customer_account_and_links_it_to_the_ride(): void
    {
        $booking = $this->acceptRide($this->pendingBooking());

        $customer = User::where('email', 'customer@example.com')->firstOrFail();

        $this->assertTrue($customer->isCustomer());
        $this->assertTrue($customer->isActive());
        $this->assertNotNull($customer->email_verified_at);
        $this->assertTrue(Hash::isHashed($customer->password));
        $this->assertSame($customer->id, $booking->fresh()->customer_user_id);
        $this->assertSame($customer->id, $booking->fresh()->customerUser->id);
    }

    public function test_the_login_details_are_emailed_with_the_password_that_opens_the_account(): void
    {
        $this->acceptRide($this->pendingBooking());

        $plainPassword = null;

        Mail::assertSent(CustomerLoginDetails::class, function (CustomerLoginDetails $mail) use (&$plainPassword): bool {
            $plainPassword = $mail->plainPassword;

            return $mail->hasTo('customer@example.com')
                && $mail->booking->booking_number !== '';
        });

        $this->assertIsString($plainPassword);
        $this->assertNotSame('', $plainPassword);

        // The password he was sent is the one his account actually accepts.
        $customer = User::where('email', 'customer@example.com')->firstOrFail();
        $this->assertTrue(Hash::check($plainPassword, $customer->password));
    }

    public function test_a_second_ride_from_the_same_address_reuses_the_account_and_mails_nothing_new(): void
    {
        $first = $this->acceptRide($this->pendingBooking());
        $second = $this->acceptRide($this->pendingBooking());

        Mail::assertSent(CustomerLoginDetails::class, 1);

        $this->assertSame(1, User::where('email', 'customer@example.com')->count());
        $this->assertSame($first->fresh()->customer_user_id, $second->fresh()->customer_user_id);
    }

    public function test_a_ride_booked_without_an_email_gets_no_account(): void
    {
        $booking = $this->acceptRide($this->pendingBooking(['customer_email' => null]));

        $this->assertNull($booking->fresh()->customer_user_id);
        $this->assertDatabaseMissing('users', ['email' => 'customer@example.com']);
        Mail::assertNotSent(CustomerLoginDetails::class);
    }

    public function test_an_address_that_belongs_to_staff_is_never_turned_into_a_customer(): void
    {
        $staff = User::factory()->create(['email' => 'dispatcher@example.com']);

        $booking = $this->acceptRide($this->pendingBooking(['customer_email' => 'dispatcher@example.com']));

        $this->assertNull($booking->fresh()->customer_user_id);
        $this->assertSame(1, User::where('email', 'dispatcher@example.com')->count());
        $this->assertSame(User::ROLE_ADMIN, $staff->fresh()->role);
        Mail::assertNotSent(CustomerLoginDetails::class);
    }

    public function test_the_account_is_still_created_when_mail_notifications_are_switched_off(): void
    {
        Setting::put('mail.enabled', false);

        $booking = $this->acceptRide($this->pendingBooking());

        $customer = User::where('email', 'customer@example.com')->firstOrFail();

        $this->assertTrue($customer->isCustomer());
        $this->assertSame($customer->id, $booking->fresh()->customer_user_id);
        Mail::assertNotSent(CustomerLoginDetails::class);
    }

    public function test_the_account_is_still_created_when_the_login_details_notification_is_switched_off(): void
    {
        Setting::put('mail.notify_customer_login_details', false);

        $this->acceptRide($this->pendingBooking());

        $this->assertTrue(
            User::where('email', 'customer@example.com')->firstOrFail()->isCustomer()
        );
        Mail::assertNotSent(CustomerLoginDetails::class);
    }

    /**
     * A new request with the vehicle already on it, which is what the booking
     * form produces.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function pendingBooking(array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'customer_name' => 'Ramesh Customer',
            'customer_email' => 'customer@example.com',
            'pickup_city_id' => $this->city->id,
            'drop_city_id' => $this->city->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => BookingStatus::PENDING->value,
        ], $overrides));
    }

    /**
     * The real path: a driver is put on the ride and accepts it, which confirms
     * the booking and so gives the customer his account.
     */
    private function acceptRide(Booking $booking): Booking
    {
        // A fresh driver for each ride, so a second booking in the same test is
        // never refused because the first one still has a driver on it.
        $driver = Driver::factory()->create([
            'current_city_id' => $this->city->id,
            'status' => DriverStatus::AVAILABLE->value,
        ]);

        app(BookingService::class)->assignDriver($booking, $driver->id, $this->admin->id);
        app(AssignmentResponseService::class)->accept($booking->fresh()->driverAssignment);

        return $booking->fresh();
    }
}
