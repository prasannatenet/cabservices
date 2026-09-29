<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ $driver->name }}
                <span class="block text-sm font-sans font-medium text-gray-500 dark:text-gray-400 mt-1">Ride activity</span>
            </h2>
            <div class="flex items-center gap-4 text-sm font-medium">
                <a href="{{ route('admin.drivers.show', $driver) }}" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">Driver profile &rarr;</a>
                <a href="{{ route('admin.driver-activity.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">&larr; All drivers</a>
            </div>
        </div>
    </x-slot>

    @php
    $card = 'bg-white dark:bg-[#161615] rounded-2xl border border-gray-100 dark:border-gray-800/60 shadow-sm p-5';
    $label = 'text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider';
    $panel = 'bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden';
    $head = 'text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider';
    $cell = 'whitespace-nowrap text-sm text-gray-600 dark:text-gray-300';
    $link = 'text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors';
    @endphp


    <!-- Driver identity -->
    <div class="{{ $card }}">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                <span class="inline-flex px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $driver->status }}</span>
                <span class="text-sm text-gray-600 dark:text-gray-300">{{ $driver->phone }}</span>
                @if($driver->email)
                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $driver->email }}</span>
                @endif
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ optional($driver->currentCity)->name ?? 'No city set' }}</span>
            </div>
    </div>

    <!-- Counts for this driver -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="{{ $card }}">
                <p class="{{ $label }}">Assigned</p>
                <p class="text-2xl font-display font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $summary['assigned'] }}</p>
            </div>
            <div class="{{ $card }}">
                <p class="{{ $label }}">Awaiting Reply</p>
                <p class="text-2xl font-display font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $summary['awaiting'] }}</p>
            </div>
            <div class="{{ $card }}">
                <p class="{{ $label }}">Ongoing</p>
                <p class="text-2xl font-display font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $summary['ongoing'] }}</p>
            </div>
            <div class="{{ $card }}">
                <p class="{{ $label }}">Completed</p>
                <p class="text-2xl font-display font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $summary['completed'] }}</p>
            </div>
            <div class="{{ $card }}">
                <p class="{{ $label }}">Rejected</p>
                <p class="text-2xl font-display font-bold text-orange-600 dark:text-orange-400 mt-1">{{ $summary['rejected'] }}</p>
            </div>
    </div>

    <!-- Completed rides -->
    <div class="{{ $panel }}">
            <div class="admin-card-header">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Completed Rides</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Rides he drove through to {{ \App\Enums\BookingStatus::TRIP_COMPLETED->value }}.</p>
            </div>

            @include('admin.drivers.activity.partials.rides-table', [
                'rides' => $completed,
                'empty' => 'This driver has not completed a ride yet.',
            ])
    </div>

    <!-- Ongoing rides -->
    <div class="{{ $panel }}">
            <div class="admin-card-header">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Ongoing Rides</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Rides still with him &mdash; assigned, confirmed or started.</p>
            </div>

            @include('admin.drivers.activity.partials.rides-table', [
                'rides' => $ongoing,
                'empty' => 'No ride is currently with this driver.',
            ])
    </div>

    <!-- Refused rides -->
    <div class="{{ $panel }}">
            <div class="admin-card-header">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Rejected Rides</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Rides he turned down, or never answered inside the 6 hour window. The ride is freed for another driver,
                    so this is where those refusals stay visible along with the reason he gave.
                </p>
            </div>

            <div class="admin-table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col" class="{{ $head }}">Booking</th>
                            <th scope="col" class="{{ $head }}">Route</th>
                            <th scope="col" class="{{ $head }}">Date &amp; Time</th>
                            <th scope="col" class="{{ $head }}">Reason Given</th>
                            <th scope="col" class="{{ $head }}">Rejected On</th>
                            <th scope="col" class="text-right {{ $head }}">Ride</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/40">
                        @forelse($rejected as $assignment)
                            @php $booking = $assignment->booking; @endphp
                            <tr>
                                <td class="whitespace-nowrap font-semibold text-primary-600 dark:text-primary-400">{{ $booking?->booking_number }}</td>
                                <td class="{{ $cell }}">
                                    {{ optional($booking?->pickupCity)->name ?? $booking?->pickup_location }}
                                    &rarr;
                                    {{ optional($booking?->dropCity)->name ?? $booking?->drop_location }}
                                </td>
                                <td class="{{ $cell }}">
                                    {{ $booking?->pickup_date ? \Illuminate\Support\Carbon::parse($booking->pickup_date)->format('d M, Y') : 'N/A' }}
                                    <span class="block text-xs text-gray-500">{{ $booking?->pickup_time }}</span>
                                </td>
                                <td class="text-sm">
                                    <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full border bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-900/20 dark:text-orange-400 dark:border-orange-900/50">
                                        {{ $assignment->rejectionLabel() }}
                                    </span>
                                    <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $assignment->rejection_reason }}</p>
                                </td>
                                <td class="{{ $cell }}">
                                    {{ $assignment->responded_at?->format('d M, Y') }}
                                    <span class="block text-xs text-gray-500">{{ $assignment->responded_at?->format('h:i A') }}</span>
                                </td>
                                <td class="whitespace-nowrap text-right font-semibold">
                                    @if($booking)
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="{{ $link }}">View &rarr;</a>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="admin-table-empty">This driver has not rejected any ride.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-card-footer">
                {{ $rejected->links() }}
            </div>
    </div>
</x-app-layout>
