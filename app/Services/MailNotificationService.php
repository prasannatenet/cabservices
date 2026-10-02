<?php

namespace App\Services;

use App\Mail\BookingRequestAcknowledgement;
use App\Mail\CustomerLoginDetails;
use App\Mail\DriverAssigned;
use App\Mail\DriverAssignmentNotice;
use App\Mail\NewBookingRequest;
use App\Mail\RideTrackingLinkMail;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the transactional booking mails. Every notification is opt-in through
 * the admin settings screen, and a delivery failure is logged instead of
 * thrown: a customer must still get his booking when SMTP is unreachable.
 *
 * The mails are sent synchronously rather than through the queue, so a customer
 * waiting on a booking confirmation receives it even when no queue worker runs.
 */
class MailNotificationService
{
    /**
     * Emails sent when a customer requests a booking: the operations team is
     * notified and the customer gets an acknowledgement.
     */
    public function notifyBookingRequested(Booking $booking): void
    {
        $booking->loadMissing(['pickupCity', 'dropCity', 'serviceType', 'vehicle']);

        $this->sendToNewBookingRecipients(fn () => Mail::to($this->notificationRecipients())->send(
            new NewBookingRequest($booking)
        ));

        $this->sendToCustomer($booking, fn () => Mail::to($booking->customer_email)->send(
            new BookingRequestAcknowledgement($booking)
        ));
    }

    /**
     * Emails sent when a driver is assigned: the customer learns who is
     * driving and the driver receives his trip details.
     */
    public function notifyDriverAssigned(Booking $booking): void
    {
        $booking->loadMissing(['pickupCity', 'dropCity', 'serviceType', 'vehicle', 'driver']);

        $this->sendToCustomer($booking, fn () => Mail::to($booking->customer_email)->send(
            new DriverAssigned($booking)
        ), 'mail.notify_customer_on_driver_assigned');

        $driver = $booking->driver;

        // The driver notice is opt-in like every other mail: turning mail
        // notifications off stops it too.
        if (! $driver || ! Setting::boolean('mail.enabled', true)) {
            return;
        }

        $recipients = $this->driverRecipients($driver);

        if ($recipients === []) {
            return;
        }

        $this->safely('driver assignment notice', function () use ($recipients, $booking): void {
            Mail::to($recipients)->send(
                new DriverAssignmentNotice($booking)
            );
        });
    }

    /**
     * The live tracking link sent the moment a driver starts a ride: the driver
     * gets it so he can follow his own trip, and the operations team gets it so
     * the running ride can be followed from the desk.
     *
     * Both sides are optional: a ride is followed by whoever has an address to
     * receive the link, and turning mail notifications off stops them both.
     */
    public function notifyRideStarted(Booking $booking): void
    {
        $booking->loadMissing(['pickupCity', 'dropCity', 'serviceType', 'vehicle', 'driver']);

        if (! Setting::boolean('mail.enabled', true)) {
            return;
        }

        $adminRecipients = $this->notificationRecipients();

        if ($adminRecipients !== []) {
            $this->safely('ride tracking link to admin', function () use ($adminRecipients, $booking): void {
                Mail::to($adminRecipients)->send(
                    new RideTrackingLinkMail($booking)
                );
            });
        }

        $driver = $booking->driver;

        if (! $driver) {
            return;
        }

        $driverRecipients = $this->driverRecipients($driver);

        if ($driverRecipients === []) {
            return;
        }

        $this->safely('ride tracking link to driver', function () use ($driverRecipients, $booking): void {
            Mail::to($driverRecipients)->send(
                new RideTrackingLinkMail($booking)
            );
        });
    }

    /**
     * The email that hands a customer his login details. It goes out once, when
     * his account is first created for a confirmed ride, so he can open his own
     * panel and follow the trip.
     */
    public function notifyCustomerLoginDetails(Booking $booking, User $customer, string $plainPassword): void
    {
        $booking->loadMissing(['pickupCity', 'dropCity', 'serviceType', 'vehicle', 'driver']);

        if (! Setting::boolean('mail.enabled', true)) {
            return;
        }

        if (! Setting::boolean('mail.notify_customer_login_details', true)) {
            return;
        }

        if (blank($customer->email)) {
            return;
        }

        $this->safely('customer login details', fn () => Mail::to($customer->email)->send(
            new CustomerLoginDetails($booking, $customer, $plainPassword)
        ));
    }

    /**
     * Addresses that receive the "a customer requested a booking" mail.
     * Falls back to every admin account when the list is left empty.
     *
     * @return list<string>
     */
    public function notificationRecipients(): array
    {
        $configured = Setting::list('mail.booking_notification_recipients');

        if ($configured !== []) {
            return $configured;
        }

        return User::where('role', User::ROLE_ADMIN)
            ->pluck('email')
            ->all();
    }

    /**
     * Addresses of the assigned driver: his own address, and the account he
     * logs in with when it differs.
     *
     * @return list<string>
     */
    protected function driverRecipients(Driver $driver): array
    {
        return array_values(array_unique(array_filter(array_merge(
            [$driver->email],
            [$driver->user?->email]
        ))));
    }

    /**
     * Send to the customer, but only when he gave an address and the
     * notification is enabled.
     */
    protected function sendToCustomer(Booking $booking, callable $send, string $setting = 'mail.notify_customer'): void
    {
        if (! Setting::boolean('mail.enabled', true)) {
            return;
        }

        if (! Setting::boolean($setting, true)) {
            return;
        }

        if (blank($booking->customer_email)) {
            return;
        }

        $this->safely('customer notification', $send);
    }

    /**
     * Send to the operations team, but only when the notification is enabled
     * and there is at least one recipient.
     */
    protected function sendToNewBookingRecipients(callable $send): void
    {
        if (! Setting::boolean('mail.enabled', true) || ! Setting::boolean('mail.notify_on_booking_request', true)) {
            return;
        }

        if ($this->notificationRecipients() === []) {
            return;
        }

        $this->safely('new booking request notification', $send);
    }

    /**
     * Mail must never break a booking, so failures are logged and swallowed.
     */
    protected function safely(string $context, callable $send): void
    {
        try {
            $send();
        } catch (Throwable $exception) {
            Log::error('Mail notification failed: '.$context, [
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
