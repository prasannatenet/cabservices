<x-mail-layout>
    <h1 style="margin:0 0 16px;font-size:20px;line-height:28px;">New booking request</h1>

    <p style="margin:0 0 16px;">A new booking was just requested and is waiting for review.</p>

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
            <td style="padding:8px 0;color:#71717a;">Phone</td>
            <td style="padding:8px 0;">{{ $booking->customer_phone }}</td>
        </tr>
        @if ($booking->customer_email)
            <tr>
                <td style="padding:8px 0;color:#71717a;">Email</td>
                <td style="padding:8px 0;">{{ $booking->customer_email }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:8px 0;color:#71717a;">Trip</td>
            <td style="padding:8px 0;">{{ $booking->displayRoute() }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Pickup</td>
            <td style="padding:8px 0;">{{ $booking->pickup_date?->format('d M Y') }} at {{ $booking->pickup_time }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Passengers</td>
            <td style="padding:8px 0;">{{ $booking->passengers }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#71717a;">Service</td>
            <td style="padding:8px 0;">{{ $booking->serviceType?->name ?? '—' }}</td>
        </tr>
    </table>

    <p style="margin:0;">
        <a href="{{ route('admin.bookings.show', $booking) }}" style="display:inline-block;padding:12px 24px;background-color:#18181b;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:bold;">Review booking</a>
    </p>
</x-mail-layout>