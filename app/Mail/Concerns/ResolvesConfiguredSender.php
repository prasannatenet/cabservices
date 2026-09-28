<?php

namespace App\Mail\Concerns;

use App\Models\Setting;
use Illuminate\Mail\Mailables\Address;

/**
 * Resolves the sender of a mail from the admin settings screen, falling back
 * to the address configured in the environment.
 */
trait ResolvesConfiguredSender
{
    protected function configuredSender(): Address
    {
        return new Address(
            Setting::string('mail.from_address', (string) config('mail.from.address')),
            Setting::string('mail.from_name', (string) config('mail.from.name')),
        );
    }
}
