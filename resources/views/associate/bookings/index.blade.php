<x-associate-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Bookings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-800/60">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Bookings Starting in My Cities</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">A booking appears here as soon as a customer books a pickup in one of your cities.</p>
                </div>

                <div class="p-6 pb-0">
                    <x-admin-filter-bar :action="route('associate.bookings.index')">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Booking no, customer, phone..." class="block w-56 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                            <select name="status" class="block w-40 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">All</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
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

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/60">
                        <thead class="bg-gray-50/50 dark:bg-[#0a0a0a]">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Booking</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Route</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-[#161615] divide-y divide-gray-100 dark:divide-gray-800/60">
                            @forelse($bookings as $booking)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-[#0a0a0a]/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary-600 dark:text-primary-400">
                                    #{{ $booking->booking_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $booking->customer_name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $booking->customer_phone }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-medium">{{ optional($booking->pickupCity)->name }}</span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                        <span class="font-medium">{{ optional($booking->dropCity)->name }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">{{ \Carbon\Carbon::parse($booking->pickup_date)->format('d M, Y') }} at {{ $booking->pickup_time }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border
                                        @if($booking->status->value === 'Pending') bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-900/50
                                        @elseif($booking->status->value === 'Confirmed') bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                                        @elseif($booking->status->value === 'Cancelled') bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50
                                        @elseif($booking->status->value === 'Trip Completed') bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-900/50
                                        @else bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50 @endif">
                                        {{ $booking->status->value }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold">
                                    <a href="{{ route('associate.bookings.show', $booking) }}" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">Manage &rarr;</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center font-medium">No bookings found for your cities.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-100 dark:border-gray-800/60">
                    {{ $bookings->links() }}
                </div>
            </div>

        </div>
    </div>
</x-associate-layout>