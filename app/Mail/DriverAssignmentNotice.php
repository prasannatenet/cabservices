<?php

namespace App\Mail;

use App\Mail\Concerns\ResolvesConfiguredSender;
use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Emailed to the driver with the details of the trip assigned to him.
 *
 * Sent synchronously so it does not depend on a queue worker.
 */
class DriverAssignmentNotice extends Mailable
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
            subject: 'New trip assigned to you '.$this->booking->booking_number,
            from: $this->configuredSender(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.bookings.driver-notice',
        );
    }
}
