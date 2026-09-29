<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Completed Rides') }}
            </h2>
            <a href="{{ route('admin.bookings.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; All Bookings</a>
        </div>
    </x-slot>


    <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Finished Rides</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Every ride that finished, with the distance it covered and the amount billed from the rate card of its vehicle.</p>
            </div>

            <!-- What the rides in this list came to -->
            <div class="grid grid-cols-1 md:grid-cols-3 border-b border-gray-100 dark:border-gray-800/60">
                <div class="p-6 border-b md:border-b-0 md:border-r border-gray-100 dark:border-gray-800/60">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Trip Price</p>
                    <p class="mt-1 text-3xl font-display font-bold text-gray-900 dark:text-white">{{ number_format($totals['total_trip_price'], 2) }}</p>
                </div>
                <div class="p-6 border-b md:border-b-0 md:border-r border-gray-100 dark:border-gray-800/60">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rides Completed</p>
                    <p class="mt-1 text-3xl font-display font-bold text-gray-900 dark:text-white">{{ number_format($totals['rides']) }}</p>
                </div>
                <div class="p-6">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Distance</p>
                    <p class="mt-1 text-3xl font-display font-bold text-gray-900 dark:text-white">{{ number_format($totals['total_trip_km']) }} <span class="text-lg">km</span></p>
                </div>
            </div>
            <div class="p-4 sm:p-5 pb-0">
                <x-admin-filter-bar :action="route('admin.bookings.completed')">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Booking no, customer, phone..." class="block w-56 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Pickup City</label>
                        <select name="pickup_city_id" class="block w-40 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">All</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" @selected(request('pickup_city_id') == $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Pickup From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="block w-40 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Pickup To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="block w-40 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                </x-admin-filter-bar>
            </div>

            <div class="admin-table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left">Booking ID</th>
                            <th scope="col" class="text-left">Customer</th>
                            <th scope="col" class="text-left">Route &amp; Time</th>
                            <th scope="col" class="text-left">Driver</th>
                            <th scope="col" class="text-left">Vehicle</th>
                            <th scope="col" class="text-right">Total Km</th>
                            <th scope="col" class="text-right">Amount</th>
                            <th scope="col" class="text-left">Completed</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        @forelse($rides as $ride)
                        <tr>
                            <td class="whitespace-nowrap font-semibold text-primary-600 dark:text-primary-400">
                                #{{ $ride->booking_number }}
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $ride->customer_name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $ride->customer_phone }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium">{{ $ride->pickupCity?->name ?? '—' }}</span>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                    <span class="font-medium">{{ $ride->dropCity?->name ?? '—' }}</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">{{ \Carbon\Carbon::parse($ride->pickup_date)->format('d M, Y') }} at {{ $ride->pickup_time }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                {{ $ride->driver?->name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap">
                                {{ $ride->vehicle?->name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap text-right font-semibold text-gray-900 dark:text-white">
                                @if($ride->tripDistanceKm() !== null)
                                    {{ number_format($ride->tripDistanceKm()) }} km
                                @else
                                    <span class="text-gray-400 dark:text-gray-500" title="The trip was closed without odometer readings">&mdash;</span>
                                @endif
                            </td>
                            @php $fare = $fares[$ride->id] ?? null; @endphp
                            <td class="whitespace-nowrap text-right text-gray-900 dark:text-white">
                                @if($fare)
                                    <div class="font-bold">{{ number_format($fare['total_amount'], 2) }}</div>
                                    <div class="text-xs font-normal text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $fare['billed_days'] }} day(s) &times; {{ number_format($fare['billed_price_per_day'], 2) }} = {{ number_format($fare['base_amount'], 2) }}
                                    </div>
                                    <div class="text-xs font-normal text-gray-500 dark:text-gray-400 mt-1">
                                        @if($fare['extra_km'] > 0)
                                            {{ number_format($fare['extra_km']) }} extra km &times; {{ number_format($fare['billed_price_per_km'], 2) }} = {{ number_format($fare['extra_km_amount'], 2) }}
                                        @else
                                            No extra km &mdash; inside the {{ number_format($fare['billed_included_km']) }} km included
                                        @endif
                                    </div>
                                    @unless($fare['billed'])
                                        <div class="text-xs font-normal text-amber-600 dark:text-amber-400 mt-1">
                                            Worked out from the rate card of {{ $ride->vehicle?->name ?? 'the vehicle' }} today &mdash; not billed when the trip was closed
                                        </div>
                                    @endunless
                                @else
                                    <span class="text-gray-400 dark:text-gray-500" title="No rate card, or the trip was closed without odometer readings">Not billed</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="font-medium">{{ $ride->trip_ended_at?->format('d M, Y') ?? '—' }}</div>
                                @if($ride->trip_ended_at)
                                    <div class="text-xs text-gray-500 mt-1">{{ $ride->trip_ended_at->format('h:i A') }}</div>
                                @else
                                    <div class="text-xs text-gray-500 mt-1">Closed by admin</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right font-semibold">
                                <a href="{{ route('admin.bookings.show', $ride) }}" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">Manage &rarr;</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="admin-table-empty">No completed rides found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-card-footer">
                {{ $rides->links() }}
            </div>
    </div>

</x-app-layout>
