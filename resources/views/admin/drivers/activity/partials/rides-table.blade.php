{{-- Shared ride table for the driver activity report: completed and ongoing rides look alike.
     Expects $rides (paginator of bookings) and $empty (message shown when there are none). --}}
@php
    $head = 'px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider';
    $cell = 'px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300';
    $link = 'text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors';
@endphp
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/60">
        <thead class="bg-gray-50/50 dark:bg-[#0a0a0a]">
            <tr>
                <th scope="col" class="{{ $head }}">Booking</th>
                <th scope="col" class="{{ $head }}">Customer</th>
                <th scope="col" class="{{ $head }}">Route</th>
                <th scope="col" class="{{ $head }}">Date &amp; Time</th>
                <th scope="col" class="{{ $head }}">Vehicle</th>
                <th scope="col" class="{{ $head }}">Status</th>
                <th scope="col" class="px-6 py-4 text-right {{ $head }}">Ride</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/40">
            @forelse($rides as $booking)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-[#0a0a0a]/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary-600 dark:text-primary-400">#{{ $booking->booking_number }}</td>
                    <td class="{{ $cell }}">
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $booking->customer_name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $booking->customer_phone }}</div>
                    </td>
                    <td class="{{ $cell }}">
                        {{ optional($booking->pickupCity)->name ?? $booking->pickup_location }}
                        &rarr;
                        {{ optional($booking->dropCity)->name ?? $booking->drop_location }}
                    </td>
                    <td class="{{ $cell }}">
                        {{ \Illuminate\Support\Carbon::parse($booking->pickup_date)->format('d M, Y') }}
                        <span class="block text-xs text-gray-500">{{ $booking->pickup_time }}</span>
                    </td>
                    <td class="{{ $cell }}">{{ $booking->vehicle?->name ?? $booking->vehicle_reference ?? 'N/A' }}</td>
                    <td class="{{ $cell }}">{{ $booking->displayStatus() }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold">
                        <a href="{{ route('admin.bookings.show', $booking) }}" class="{{ $link }}">View &rarr;</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400 text-center font-medium">{{ $empty }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-4 border-t border-gray-100 dark:border-gray-800/60">
    {{ $rides->links() }}
</div>
