<?php

namespace App\Mail;

use App\Mail\Concerns\ResolvesConfiguredSender;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Gives a newly created customer his login details, so he can open his own
 * panel and follow the ride that was just confirmed for him.
 *
 * Sent synchronously so it does not depend on a queue worker.
 */
class CustomerLoginDetails extends Mailable
{
    use ResolvesConfiguredSender;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Booking $booking,
        public User $customer,
        public string $plainPassword,
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your login details for booking '.$this->booking->booking_number,
            from: $this->configuredSender(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.bookings.customer-login-details',
        );
    }
}
