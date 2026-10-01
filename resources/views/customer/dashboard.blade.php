<x-customer-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('My Rides') }}
            </h2>
            <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 transition-colors">
                Book a new ride
            </a>
        </div>
    </x-slot>

    <!-- What his rides add up to. -->
    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-800/60">
            <div class="p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Rides</p>
                <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ $metrics['total_rides'] }}</p>
            </div>
            <div class="p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Upcoming Rides</p>
                <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ $metrics['upcoming_rides'] }}</p>
            </div>
            <div class="p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed Rides</p>
                <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ $metrics['completed_rides'] }}</p>
            </div>
        </div>
    </div>

    <!-- Upcoming rides -->
    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800/60 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Upcoming rides</h3>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $upcomingBookings->count() }}</span>
        </div>

        @if ($upcomingBookings->isEmpty())
            <div class="p-6 text-sm text-gray-500 dark:text-gray-400">
                You have no rides coming up.
                <a href="{{ route('home') }}" class="font-semibold text-primary-600 hover:text-primary-500">Book one now</a>.
            </div>
        @else
            <ul class="divide-y divide-gray-100 dark:divide-gray-800/60">
                @foreach ($upcomingBookings as $ride)
                    <li>
                        <a href="{{ route('customer.bookings.show', $ride) }}" class="flex flex-wrap items-center gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                            <div class="flex-1 min-w-[220px]">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $ride->displayRoute() }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $ride->booking_number }} &middot; {{ $ride->pickup_date?->format('d M Y') }} at {{ $ride->pickup_time }}
                                </p>
                            </div>

                            <div class="min-w-[180px]">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $ride->driver?->name ?? 'Driver not assigned yet' }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $ride->vehicle?->name ?? 'Vehicle not assigned yet' }}
                                </p>
                            </div>

                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold border {{ $ride->status === \App\Enums\BookingStatus::TRIP_STARTED ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50' : 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50' }}">
                                {{ $ride->displayStatus() }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <!-- Past rides -->
    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800/60 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Past rides</h3>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $pastBookings->count() }}</span>
        </div>

        @if ($pastBookings->isEmpty())
            <div class="p-6 text-sm text-gray-500 dark:text-gray-400">
                No past rides yet.
            </div>
        @else
            <ul class="divide-y divide-gray-100 dark:divide-gray-800/60">
                @foreach ($pastBookings as $ride)
                    <li>
                        <a href="{{ route('customer.bookings.show', $ride) }}" class="flex flex-wrap items-center gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                            <div class="flex-1 min-w-[220px]">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $ride->displayRoute() }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $ride->booking_number }} &middot; {{ $ride->pickup_date?->format('d M Y') }} at {{ $ride->pickup_time }}
                                </p>
                            </div>

                            <div class="min-w-[180px]">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $ride->driver?->name ?? '—' }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $ride->vehicle?->name ?? '—' }}
                                </p>
                            </div>

                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold border {{ in_array($ride->status, [\App\Enums\BookingStatus::REJECTED, \App\Enums\BookingStatus::DRIVER_REJECTED, \App\Enums\BookingStatus::CANCELLED]) ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50' : 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                                {{ $ride->displayStatus() }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-customer-layout>