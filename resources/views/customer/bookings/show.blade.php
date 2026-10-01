<x-customer-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <div>
                <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ __('Ride details') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $booking->booking_number }}</p>
            </div>
            <a href="{{ route('customer.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Trip -->
        <div class="lg:col-span-2 bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800/60 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Trip</h3>
                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold border {{ in_array($booking->status, [\App\Enums\BookingStatus::REJECTED, \App\Enums\BookingStatus::DRIVER_REJECTED, \App\Enums\BookingStatus::CANCELLED]) ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50' : (in_array($booking->status, [\App\Enums\BookingStatus::TRIP_COMPLETED]) ? 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' : 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50') }}">
                    {{ $booking->displayStatus() }}
                </span>
            </div>

            <div class="p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">From</dt>
                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $booking->pickupCity?->name ?? $booking->pickup_location }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">To</dt>
                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $booking->displayDropCity() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Pickup date</dt>
                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $booking->pickup_date?->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Pickup time</dt>
                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $booking->pickup_time }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Pickup address</dt>
                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $booking->pickup_location }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Drop address</dt>
                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $booking->drop_location }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Passengers</dt>
                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $booking->passengers }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Service</dt>
                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $booking->serviceType?->name ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Driver and vehicle -->
        <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800/60">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Your driver</h3>
            </div>

            <div class="p-6 space-y-4 text-sm">
                @if ($booking->driver)
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Name</p>
                        <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $booking->driver->name }}</p>
                    </div>
                    @if ($booking->driver->phone)
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Phone</p>
                            <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $booking->driver->phone }}</p>
                        </div>
                    @endif
                @else
                    <p class="text-gray-500 dark:text-gray-400">Your driver will appear here once one is assigned.</p>
                @endif

                <div class="pt-4 border-t border-gray-100 dark:border-gray-800/60">
                    <p class="text-gray-500 dark:text-gray-400">Vehicle</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">
                        {{ $booking->vehicle?->name ?? '—' }}{{ $booking->vehicle?->registration_number ? ' ('.$booking->vehicle->registration_number.')' : '' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Ride updates -->
    <div class="mt-6 bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800/60">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Ride updates</h3>
        </div>

        @if ($booking->statusHistory->isEmpty())
            <div class="p-6 text-sm text-gray-500 dark:text-gray-400">No updates yet.</div>
        @else
            <ul class="divide-y divide-gray-100 dark:divide-gray-800/60">
                @foreach ($booking->statusHistory->sortByDesc('created_at') as $update)
                    <li class="px-6 py-4 flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $update->new_status }}</p>
                            @if ($update->remarks)
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $update->remarks }}</p>
                            @endif
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $update->created_at?->format('d M Y, h:i A') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-customer-layout>