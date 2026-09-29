<x-driver-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('My Ride History') }}
            </h2>
            <a href="{{ route('driver.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
        <!-- What his finished rides added up to. The price of a ride is the
             operator's business, so a per day driver is shown his own
             earnings from his daily rate instead of the ride total. -->
        <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-800/60 border-b border-gray-100 dark:border-gray-800/60">
            <div class="p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rides Completed</p>
                <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ number_format($totals['rides']) }}</p>
            </div>
            <div class="p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Distance Covered</p>
                <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ number_format($totals['total_km']) }} <span class="text-base">km</span></p>
            </div>
            <div class="p-6">
                @if($driver->isPaidPerDay())
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">My Total Earnings</p>
                    <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ number_format($totals['earnings'], 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ number_format($driver->per_day_salary, 2) }} per day &times; {{ number_format($totals['days']) }} day(s)
                    </p>
                @else
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Driver Type</p>
                    <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ $driver->driver_type?->value ?? 'Permanent' }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $driver->formattedSalary() ?? 'Salary not set' }}</p>
                @endif
            </div>
        </div>

        <div class="p-6 pb-0">
            <form action="{{ route('driver.rides') }}" method="GET" class="mb-6 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                    <select name="status" class="block w-44 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition-colors">Apply Filters</button>
                    <a href="{{ route('driver.rides') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Reset</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/60">
                <thead class="bg-gray-50/50 dark:bg-[#0a0a0a]">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Booking</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Route</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date &amp; Time</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Passengers</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Vehicle</th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Km</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trip Sheet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/40">
                    @forelse($rides as $ride)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary-600 dark:text-primary-400">{{ $ride->booking_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                {{ optional($ride->pickupCity)->name ?? $ride->pickup_location }} &rarr; {{ optional($ride->dropCity)->name ?? $ride->drop_location }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                {{ \Illuminate\Support\Carbon::parse($ride->pickup_date)->format('d M, Y') }} at {{ \Illuminate\Support\Carbon::parse($ride->pickup_time)->format('h:i A') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $ride->passengers }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                {{ $ride->vehicle?->name ?? $ride->vehicle_reference ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">
                                @if($ride->tripDistanceKm() !== null)
                                    {{ number_format($ride->tripDistanceKm()) }} km
                                @else
                                    <span class="text-gray-400 dark:text-gray-500" title="The odometer readings of this ride are not both in yet">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border
                                    @if($ride->status == \App\Enums\BookingStatus::TRIP_COMPLETED) bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                                    @elseif(in_array($ride->status, [\App\Enums\BookingStatus::TRIP_STARTED, \App\Enums\BookingStatus::DRIVER_ASSIGNED, \App\Enums\BookingStatus::CONFIRMED])) bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50
                                    @elseif(in_array($ride->status, [\App\Enums\BookingStatus::REJECTED, \App\Enums\BookingStatus::CANCELLED])) bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50
                                    @else bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/50 @endif">
                                    {{ $ride->status->value }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($ride->canStartTrip())
                                    <a href="{{ route('driver.trips.show', $ride) }}" class="inline-block px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 transition-colors">
                                        Start Trip
                                    </a>
                                @elseif($ride->canLogExpenses())
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('driver.trips.show', $ride) }}" class="inline-block px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 transition-colors">
                                            Add Expense
                                        </a>
                                        <a href="{{ route('driver.trips.show', $ride) }}#end-trip" class="inline-block px-3 py-1.5 rounded-lg text-xs font-bold text-green-700 bg-green-100 hover:bg-green-200 dark:text-green-400 dark:bg-green-900/30 transition-colors">
                                            End Trip
                                        </a>
                                    </div>
                                @elseif($ride->hasTripStarted())
                                    <a href="{{ route('driver.trips.show', $ride) }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                        Trip Sheet
                                    </a>
                                    @php $earnings = $driver->earningsFor($ride); @endphp
                                    @if($earnings)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $earnings['days'] }} day(s) &times; {{ number_format($earnings['rate'], 2) }}
                                        </p>
                                        <p class="mt-0.5 text-xs font-bold text-green-600 dark:text-green-400">{{ number_format($earnings['total'], 2) }}</p>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center font-medium">No rides found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100 dark:border-gray-800/60">
            {{ $rides->links() }}
        </div>
    </div>
</x-driver-layout>