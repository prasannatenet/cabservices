@php
    $managedCityNames = $cities->pluck('name')->all();
@endphp

<x-associate-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Associate Dashboard') }}
        </h2>
    </x-slot>

    <div class="space-y-8">

            @if(empty($managedCityNames))
                <div class="mb-8 p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-400 text-sm font-medium">
                    No cities are assigned to you yet. Please contact the administrator.
                </div>
            @else
                <div class="mb-8 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">You manage</p>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ implode(', ', $managedCityNames) }}</p>
                </div>
            @endif

            <!-- Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <div class="bg-white dark:bg-[#161615] overflow-hidden shadow-sm hover:shadow-lg rounded-2xl p-6 border border-gray-100 dark:border-gray-800/60 transform hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center">
                        <div class="p-4 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/10 text-blue-600 dark:text-blue-400">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Bookings</p>
                            <p class="text-3xl font-display font-bold text-gray-900 dark:text-white mt-1">{{ $metrics['total_bookings'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#161615] overflow-hidden shadow-sm hover:shadow-lg rounded-2xl p-6 border border-gray-100 dark:border-gray-800/60 transform hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center">
                        <div class="p-4 rounded-xl bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/10 text-yellow-600 dark:text-yellow-400">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Bookings</p>
                            <p class="text-3xl font-display font-bold text-gray-900 dark:text-white mt-1">{{ $metrics['pending_bookings'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#161615] overflow-hidden shadow-sm hover:shadow-lg rounded-2xl p-6 border border-gray-100 dark:border-gray-800/60 transform hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center">
                        <div class="p-4 rounded-xl bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/10 text-green-600 dark:text-green-400">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Vehicles</p>
                            <p class="text-3xl font-display font-bold text-gray-900 dark:text-white mt-1">{{ $metrics['active_vehicles'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#161615] overflow-hidden shadow-sm hover:shadow-lg rounded-2xl p-6 border border-gray-100 dark:border-gray-800/60 transform hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center">
                        <div class="p-4 rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/10 text-purple-600 dark:text-purple-400">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Available Drivers</p>
                            <p class="text-3xl font-display font-bold text-gray-900 dark:text-white mt-1">{{ $metrics['available_drivers'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#161615] overflow-hidden shadow-sm hover:shadow-lg rounded-2xl p-6 border border-gray-100 dark:border-gray-800/60 transform hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center">
                        <div class="p-4 rounded-xl bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/20 dark:to-pink-800/10 text-pink-600 dark:text-pink-400">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">My Services</p>
                            <p class="text-3xl font-display font-bold text-gray-900 dark:text-white mt-1">{{ $metrics['services'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-800/60 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Recent Bookings in My Cities</h3>
                    <a href="{{ route('associate.bookings.index') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">View all &rarr;</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/60">
                        <thead class="bg-gray-50/50 dark:bg-[#0a0a0a]">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Booking</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Route</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pickup</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-[#161615] divide-y divide-gray-100 dark:divide-gray-800/60">
                            @forelse($recentBookings as $booking)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-[#0a0a0a]/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary-600 dark:text-primary-400">
                                    <a href="{{ route('associate.bookings.show', $booking) }}">#{{ $booking->booking_number }}</a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $booking->customer_name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $booking->customer_phone }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ optional($booking->pickupCity)->name }}</span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                        <span class="font-medium">{{ optional($booking->dropCity)->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    <div class="font-medium">{{ \Carbon\Carbon::parse($booking->pickup_date)->format('d M, Y') }}</div>
                                    <div class="text-xs text-gray-500 mt-1">{{ $booking->pickup_time }}</div>
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
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center font-medium">No bookings found for your cities.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-associate-layout>
