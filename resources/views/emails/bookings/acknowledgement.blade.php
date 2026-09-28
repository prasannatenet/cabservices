<x-mail-layout>
    <h1 style="margin:0 0 16px;font-size:20px;line-height:28px;">We received your request</h1>

    <p style="margin:0 0 16px;">Hi {{ $booking->customer_name }},</p>

    <p style="margin:0 0 16px;">
        Thank you for booking with {{ config('app.name') }}. We have received your request and our team
        will review it and confirm shortly.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;font-size:14px;border-collapse:collapse;">
        <tr>
            <td style="padding:8px 0;width:160px;color:#71717a;">Booking number</td>
            <td style="padding:8px 0;font-weight:bold;">{{ $booking->booking_number }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Trip</td>
            <td style="padding:8px 0;">{{ $booking->pickupCity?->name }} &rarr; {{ $booking->dropCity?->name }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Pickup</td>
            <td style="padding:8px 0;">{{ $booking->pickup_date?->format('d M Y') }} at {{ $booking->pickup_time }}</td>
        </tr>
        @if ($booking->drop_date)
            <tr>
                <td style="padding:8px 0;color:#71717a;">Drop</td>
                <td style="padding:8px 0;">{{ $booking->drop_date->format('d M Y') }} at {{ $booking->drop_time }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:8px 0;color:#71717a;">Passengers</td>
            <td style="padding:8px 0;">{{ $booking->passengers }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Service</td>
            <td style="padding:8px 0;">{{ $booking->serviceType?->name ?? '—' }}</td>
        </tr>
    </table>

    <p style="margin:0;color:#71717a;font-size:14px;">
        Need help? Reply to this email or call us on {{ $booking->customer_phone }}.
    </p>
</x-mail-layout>