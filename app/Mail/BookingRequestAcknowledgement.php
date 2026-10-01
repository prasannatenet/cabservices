<?php

namespace App\Mail;

use App\Mail\Concerns\ResolvesConfiguredSender;
use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Emailed to the customer to acknowledge the booking request, with the trip
 * details they submitted.
 *
 * Sent synchronously in the request that creates the booking, so the customer
 * is emailed even when no queue worker is running.
 */
class BookingRequestAcknowledgement extends Mailable
{
    use ResolvesConfiguredSender;

    /**
     * Create a new message instance.
     */
    public function __construct(public Booking $booking)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your booking request '.$this->booking->booking_number,
            from: $this->configuredSender(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.bookings.acknowledgement',
        );
    }
}
