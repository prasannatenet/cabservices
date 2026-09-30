<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RideTrackingLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Booking $booking,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Live Tracking for Ride '.$this->booking->booking_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->getHtmlContent(),
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function getHtmlContent(): string
    {
        $trackingUrl = url('/admin/track/ride/'.$this->booking->tracking_id);

        return <<<HTML
        <div>
            <h2>Ride {$this->booking->booking_number} Started</h2>
            <p>The driver has started the trip.</p>
            <p>You can track their live location here:</p>
            <p><a href="{$trackingUrl}" style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Track Ride</a></p>
            <p>Or copy this link: <br>{$trackingUrl}</p>
        </div>
        HTML;
    }
}
