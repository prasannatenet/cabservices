<?php

namespace App\Notifications;

use App\Enums\AssignmentResponseStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Tells the dispatcher that the driver answered a newly assigned ride.
 *
 * The booking screen is not necessarily open when the driver answers, so this
 * is pushed as an operating system notification that appears over any other
 * software, even when the browser is minimised or on a background tab.
 *
 * The driver name is passed in rather than read from the booking: a rejected
 * ride has its driver_id cleared so the driver can be given another one, which
 * would otherwise leave the notification without a name.
 */
class DriverRespondedToAssignment extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  AssignmentResponseStatus  $response  How the driver answered the ride.
     * @param  string  $driverName  The driver who answered, captured before a rejection frees him.
     * @param  string|null  $reason  Why he refused the ride, for a rejection only.
     */
    public function __construct(
        protected Booking $booking,
        protected AssignmentResponseStatus $response,
        protected string $driverName,
        protected ?string $reason = null,
    ) {}

    /**
     * The channels the event travels through.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class, 'database'];
    }

    /**
     * The operating system toast shown on the desktop.
     */
    public function toWebPush(object $notifiable): WebPushMessage
    {
        $message = $this->response->isRejected()
            ? $this->rejectionMessage()
            : $this->acceptanceMessage();

        // A refusal has to be acted on, so the toast stays on screen until it is
        // dismissed instead of sliding away like a routine confirmation.
        if ($this->response->isRejected()) {
            $message->requireInteraction();
        }

        return $message
            ->action('View booking', 'view_booking')
            ->data($this->payload($notifiable));
    }

    /**
     * The stored copy of the event, so a notification bell can show it later.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload($notifiable);
    }

    protected function acceptanceMessage(): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Ride confirmed')
            ->body($this->acceptanceBody());
    }

    protected function rejectionMessage(): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Ride rejected')
            ->body($this->rejectionBody());
    }

    protected function acceptanceBody(): string
    {
        return sprintf(
            '%s accepted booking %s (%s → %s).',
            $this->driverName,
            $this->booking->booking_number,
            $this->booking->pickup_location,
            $this->booking->drop_location,
        );
    }

    protected function rejectionBody(): string
    {
        return sprintf(
            '%s refused booking %s (%s → %s). Reason: %s',
            $this->driverName,
            $this->booking->booking_number,
            $this->booking->pickup_location,
            $this->booking->drop_location,
            $this->reason ?? 'not given',
        );
    }

    /**
     * The shared payload. The booking screen differs per panel, so the link
     * sends the dispatcher to the panel he actually works in.
     *
     * @return array<string, mixed>
     */
    protected function payload(object $notifiable): array
    {
        return [
            'title' => $this->response->isRejected() ? 'Ride rejected' : 'Ride confirmed',
            'body' => $this->response->isRejected()
                ? $this->rejectionBody()
                : $this->acceptanceBody(),
            'url' => $this->bookingUrl($notifiable),
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'response' => $this->response->value,
            'driver_name' => $this->driverName,
        ];
    }

    protected function bookingUrl(object $notifiable): string
    {
        $name = $notifiable instanceof User && $notifiable->isAssociate()
            ? 'associate.bookings.show'
            : 'admin.bookings.show';

        return route($name, $this->booking);
    }
}
