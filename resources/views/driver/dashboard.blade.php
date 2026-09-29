<x-driver-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                Welcome, {{ $driver->name }}
            </h2>
            <span class="px-3 py-1 text-xs font-bold rounded-full border
                @if($driver->status == 'Available') bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                @elseif($driver->status == 'Unavailable') bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/50
                @elseif($driver->status == 'Inactive') bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50
                @else bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50 @endif">
                {{ $driver->status }}
            </span>
        </div>
    </x-slot>

    <div class="space-y-8">

        <!-- Pending assignment alerts -->
        @forelse($pendingAssignments as $pending)
            @php $minutesLeft = $pending->minutesRemaining(); @endphp
            <a href="{{ route('driver.assignments.index') }}" class="block bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-800 rounded-2xl p-5 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-bold text-amber-900 dark:text-amber-200">
                            New ride waiting for your response &mdash; {{ $pending->booking?->booking_number }}
                        </p>
                        <p class="text-sm text-amber-800 dark:text-amber-300 mt-0.5">
                            {{ optional($pending->booking?->pickupCity)->name }} &rarr; {{ optional($pending->booking?->dropCity)->name }}
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-100 whitespace-nowrap">
                        {{ intdiv($minutesLeft, 60) }}h {{ $minutesLeft % 60 }}m left to respond
                    </span>
                </div>
            </a>
        @endforeach

        <!-- Availability -->
        <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">My Availability</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        @if($driver->canManageAvailability())
                            Set yourself as available or unavailable. This is instantly visible to the admin.
                        @else
                            Your availability is currently managed by the admin (Status: {{ $driver->status }}).
                        @endif
                    </p>
                </div>

                @if($driver->canManageAvailability())
                    <form action="{{ route('driver.availability.toggle') }}" method="POST"
                        onsubmit="return confirm('Change your availability to {{ $driver->status == 'Available' ? 'Unavailable' : 'Available' }}?');">
                        @csrf
                        <button type="submit" class="px-6 py-3 rounded-xl text-sm font-bold text-white transition-colors shadow-sm
                            @if($driver->status == 'Available') bg-amber-500 hover:bg-amber-600 shadow-amber-500/30
                            @else bg-green-600 hover:bg-green-700 shadow-green-500/30 @endif">
                            @if($driver->status == 'Available')
                                Go Unavailable
                            @else
                                Go Available
                            @endif
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Rides</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white font-display">{{ $metrics['total_rides'] }}</p>
            </div>
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completed</p>
                <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400 font-display">{{ $metrics['completed_rides'] }}</p>
            </div>
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Upcoming</p>
                <p class="mt-2 text-3xl font-bold text-primary-600 dark:text-primary-400 font-display">{{ $metrics['upcoming_rides'] }}</p>
            </div>
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">My Cities</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white font-display">{{ $metrics['preferred_cities'] }}</p>
            </div>
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rejected</p>
                <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400 font-display">{{ $metrics['rejected_rides'] }}</p>
            </div>
        </div>

        <!-- Upcoming Trips -->
        <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-800/60 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Upcoming Trips</h3>
                <a href="{{ route('driver.rides') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">View all rides &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/60">
                    <thead class="bg-gray-50/50 dark:bg-[#0a0a0a]">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Booking</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Route</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pickup</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trip Sheet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/40">
                        @forelse($upcomingBookings as $booking)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary-600 dark:text-primary-400">{{ $booking->booking_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ optional($booking->pickupCity)->name }} &rarr; {{ optional($booking->dropCity)->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ \Illuminate\Support\Carbon::parse($booking->pickup_date)->format('d M, Y') }} at {{ \Illuminate\Support\Carbon::parse($booking->pickup_time)->format('h:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700 dark:text-gray-300">{{ $booking->status->value }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($booking->canStartTrip())
                                        <a href="{{ route('driver.trips.show', $booking) }}" class="inline-block px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 transition-colors">
                                            Start Trip
                                        </a>
                                    @elseif($booking->canLogExpenses())
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('driver.trips.show', $booking) }}" class="inline-block px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 transition-colors">
                                                Add Expense
                                            </a>
                                            <a href="{{ route('driver.trips.show', $booking) }}#end-trip" class="inline-block px-3 py-1.5 rounded-lg text-xs font-bold text-green-700 bg-green-100 hover:bg-green-200 dark:text-green-400 dark:bg-green-900/30 transition-colors">
                                                End Trip
                                            </a>
                                        </div>
                                    @elseif($booking->hasTripStarted())
                                        <a href="{{ route('driver.trips.show', $booking) }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                            Trip Sheet
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">Waiting</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center font-medium">No upcoming trips assigned yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rides You Rejected -->
        <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-800/60 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Rides You Rejected</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Every ride you refused, with the reason you sent to the admin.</p>
                </div>
                <a href="{{ route('driver.rejections.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">View all &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/60">
                    <thead class="bg-gray-50/50 dark:bg-[#0a0a0a]">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Booking</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Route</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pickup</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reason You Gave</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/40">
                        @forelse($rejectedAssignments as $assignment)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary-600 dark:text-primary-400">{{ $assignment->booking?->booking_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ optional($assignment->booking?->pickupCity)->name }} &rarr; {{ optional($assignment->booking?->dropCity)->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ \Illuminate\Support\Carbon::parse($assignment->booking?->pickup_date)->format('d M, Y') }} at {{ \Illuminate\Support\Carbon::parse($assignment->booking?->pickup_time)->format('h:i A') }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full border bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50">
                                        {{ $assignment->rejectionLabel() }}
                                    </span>
                                    <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $assignment->rejection_reason }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $assignment->responded_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center font-medium">You have not rejected any ride yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('driver.rides') }}" class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 p-6 hover:border-primary-300 dark:hover:border-primary-800 transition-colors">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Ride History</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">See all rides assigned to you.</p>
            </a>
            <a href="{{ route('driver.cities') }}" class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 p-6 hover:border-primary-300 dark:hover:border-primary-800 transition-colors">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">My Cities</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage the cities you want to go to.</p>
            </a>
        </div>
    </div>
</x-driver-layout>