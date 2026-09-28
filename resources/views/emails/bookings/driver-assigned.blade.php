<x-mail-layout>
    <h1 style="margin:0 0 16px;font-size:20px;line-height:28px;">Your driver is on the way</h1>

    <p style="margin:0 0 16px;">Hi {{ $booking->customer_name }},</p>

    <p style="margin:0 0 16px;">
        A driver has been assigned to your booking and the trip is being confirmed.
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
            <td style="padding:8px 0;">{{ $booking->pickupCity?->name }} &rarr; {{ $booking->dropCity?->name }}</td>
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

    <p style="margin:0;color:#71717a;font-size:14px;">
        The driver will contact you on {{ $booking->customer_phone }} before pickup.
    </p>
</x-mail-layout>