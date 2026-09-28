<?php

namespace App\Mail;

use App\Mail\Concerns\ResolvesConfiguredSender;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent from the settings screen to verify the mail configuration actually
 * delivers. It is synchronous on purpose: the admin needs the real result
 * of the attempt, so a failed SMTP connection surfaces immediately.
 */
class SettingsTestMail extends Mailable
{
    use Queueable, ResolvesConfiguredSender, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public string $recipientName)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test mail from '.config('app.name'),
            from: $this->configuredSender(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.settings.test',
        );
    }
}
