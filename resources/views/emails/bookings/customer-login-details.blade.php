<x-mail-layout>
    <h1 style="margin:0 0 16px;font-size:20px;line-height:28px;">Your ride is confirmed</h1>

    <p style="margin:0 0 16px;">Hi {{ $customer->name }},</p>

    <p style="margin:0 0 16px;">
        Your ride is confirmed. We have set up an account for you so you can follow it &mdash; and every ride you book after it &mdash; from your own dashboard.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;font-size:14px;border-collapse:collapse;">
        <tr>
            <td style="padding:8px 0;width:160px;color:#71717a;">Email</td>
            <td style="padding:8px 0;font-weight:bold;">{{ $customer->email }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Password</td>
            <td style="padding:8px 0;font-weight:bold;font-family:monospace;font-size:16px;letter-spacing:1px;">{{ $plainPassword }}</td>
        </tr>
    </table>

    <p style="margin:0 0 20px;">
        <a href="{{ route('login') }}" style="display:inline-block;padding:12px 24px;background-color:#111827;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:bold;">Log in to your dashboard</a>
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;font-size:14px;border-collapse:collapse;">
        <tr>
            <td style="padding:8px 0;width:160px;color:#71717a;">Booking number</td>
            <td style="padding:8px 0;font-weight:bold;">{{ $booking->booking_number }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Driver</td>
            <td style="padding:8px 0;font-weight:bold;">{{ $booking->driver?->name ?? 'To be confirmed' }}</td>
        </tr>
        @if ($booking->driver?->phone)
            <tr>
                <td style="padding:8px 0;color:#71717a;">Driver phone</td>
                <td style="padding:8px 0;">{{ $booking->driver->phone }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:8px 0;color:#71717a;">Vehicle</td>
            <td style="padding:8px 0;">{{ $booking->vehicle?->name ?? '—' }}{{ $booking->vehicle?->registration_number ? ' ('.$booking->vehicle->registration_number.')' : '' }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Trip</td>
            <td style="padding:8px 0;">{{ $booking->displayRoute() }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Pickup</td>
            <td style="padding:8px 0;">{{ $booking->pickup_date?->format('d M Y') }} at {{ $booking->pickup_time }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Pickup address</td>
            <td style="padding:8px 0;">{{ $booking->pickup_location }}</td>
        </tr>
    </table>

    <p style="margin:0 0 8px;color:#71717a;font-size:14px;">
        Log in with your email and the password above. You can choose a password of your own afterwards from the Forgot Password link on the login page.
    </p>

    <p style="margin:0;color:#71717a;font-size:14px;">
        If you did not book this ride, please ignore this email.
    </p>
</x-mail-layout>