<?php

namespace Tests\Feature;

use App\Mail\BookingRequestAcknowledgement;
use App\Mail\DriverAssigned;
use App\Mail\DriverAssignmentNotice;
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
use RuntimeException;
use Tests\TestCase;

class BookingMailNotificationTest extends TestCase
{
    use RefreshDatabase;

    private City $city;

    private ServiceType $service;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->city = City::factory()->create(['name' => 'Jaipur']);
        $this->service = ServiceType::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['status' => 'Available']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bookingData(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Ramesh Customer',
            'customer_phone' => '+919876543210',
            'customer_email' => 'customer@example.com',
            'pickup_city_id' => $this->city->id,
            'pickup_location' => 'Jaipur Airport',
            'drop_city_id' => $this->city->id,
            'drop_location' => 'Hotel Trident',
            'pickup_date' => now()->addDay()->format('Y-m-d'),
            'pickup_time' => '10:00',
            'passengers' => 2,
            'service_type_id' => $this->service->id,
            'vehicle_id' => $this->vehicle->id,
        ], $overrides);
    }

    public function test_booking_request_notifies_admin_and_customer(): void
    {
        Mail::fake();
        Setting::put('mail.booking_notification_recipients', 'ops@example.com');

        app(BookingService::class)->createBookingRequest($this->bookingData());

        Mail::assertQueued(NewBookingRequest::class, fn (NewBookingRequest $mail): bool => $mail->hasTo('ops@example.com'));
        Mail::assertQueued(BookingRequestAcknowledgement::class, fn (BookingRequestAcknowledgement $mail): bool => $mail->hasTo('customer@example.com'));
    }

    public function test_booking_request_falls_back_to_admin_accounts_when_no_recipient_configured(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['email' => 'fallback-admin@example.com']);

        app(BookingService::class)->createBookingRequest($this->bookingData());

        Mail::assertQueued(NewBookingRequest::class, fn (NewBookingRequest $mail): bool => $mail->hasTo($admin->email));
    }

    public function test_customer_is_not_emailed_when_no_email_was_given(): void
    {
        Mail::fake();
        Setting::put('mail.booking_notification_recipients', 'ops@example.com');

        app(BookingService::class)->createBookingRequest($this->bookingData(['customer_email' => null]));

        Mail::assertQueued(NewBookingRequest::class);
        Mail::assertNotQueued(BookingRequestAcknowledgement::class);
    }

    public function test_assigning_a_driver_emails_the_customer_and_the_driver(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $driver = Driver::factory()->create(['email' => 'driver@example.com', 'status' => 'Available']);

        $booking = Booking::factory()->create([
            'customer_email' => 'customer@example.com',
            'pickup_city_id' => $this->city->id,
            'drop_city_id' => $this->city->id,
            'service_type_id' => $this->service->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        app(BookingService::class)->assignDriver($booking, $driver->id, $admin->id);

        Mail::assertQueued(DriverAssigned::class, fn (DriverAssigned $mail): bool => $mail->hasTo('customer@example.com'));
        Mail::assertQueued(DriverAssignmentNotice::class, fn (DriverAssignmentNotice $mail): bool => $mail->hasTo('driver@example.com'));
    }

    public function test_confirming_a_booking_emails_the_customer_and_the_driver(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $driver = Driver::factory()->create(['email' => 'driver@example.com', 'status' => 'Available']);

        $booking = Booking::factory()->create([
            'customer_email' => 'customer@example.com',
            'pickup_city_id' => $this->city->id,
            'drop_city_id' => $this->city->id,
            'service_type_id' => $this->service->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $this->actingAs($admin);

        app(BookingService::class)->confirmBooking($booking, $driver->id);

        Mail::assertQueued(DriverAssigned::class, fn (DriverAssigned $mail): bool => $mail->hasTo('customer@example.com'));
        Mail::assertQueued(DriverAssignmentNotice::class, fn (DriverAssignmentNotice $mail): bool => $mail->hasTo('driver@example.com'));
    }

    public function test_no_mail_is_sent_when_notifications_are_disabled(): void
    {
        Mail::fake();
        Setting::put('mail.enabled', false);
        Setting::put('mail.booking_notification_recipients', 'ops@example.com');

        app(BookingService::class)->createBookingRequest($this->bookingData());

        Mail::assertNothingQueued();
    }

    public function test_booking_still_succeeds_when_mail_delivery_fails(): void
    {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP is down'));

        $booking = app(BookingService::class)->createBookingRequest($this->bookingData());

        $this->assertDatabaseHas('bookings', ['booking_number' => $booking->booking_number]);
    }
}
