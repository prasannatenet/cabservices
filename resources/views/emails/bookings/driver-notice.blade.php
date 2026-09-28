<x-mail-layout>
    <h1 style="margin:0 0 16px;font-size:20px;line-height:28px;">New trip assigned to you</h1>

    <p style="margin:0 0 16px;">Hi {{ $booking->driver?->name ?? 'Driver' }},</p>

    <p style="margin:0 0 16px;">
        You have been assigned to the booking below. Please reach the pickup point on time.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;font-size:14px;border-collapse:collapse;">
        <tr>
            <td style="padding:8px 0;width:160px;color:#71717a;">Booking number</td>
            <td style="padding:8px 0;font-weight:bold;">{{ $booking->booking_number }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Customer</td>
            <td style="padding:8px 0;">{{ $booking->customer_name }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Customer phone</td>
            <td style="padding:8px 0;">{{ $booking->customer_phone }}</td>
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
        <tr>
            <td style="padding:8px 0;color:#71717a;">Drop address</td>
            <td style="padding:8px 0;">{{ $booking->drop_location }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Vehicle</td>
            <td style="padding:8px 0;">{{ $booking->vehicle?->name ?? '—' }}{{ $booking->vehicle?->registration_number ? ' ('.$booking->vehicle->registration_number.')' : '' }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Passengers</td>
            <td style="padding:8px 0;">{{ $booking->passengers }}</td>
        </tr>
    </table>
</x-mail-layout>