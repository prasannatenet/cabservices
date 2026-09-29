<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    {{-- The layout already gives the content its padding
         (see layouts/app.blade.php), so this view adds none of its own. --}}
    <div class="space-y-6">
        <!-- Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-[#161615] overflow-hidden shadow-sm hover:shadow-lg rounded-2xl p-5 border border-gray-100 dark:border-gray-800/60 transform hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/10 text-blue-600 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <div class="ml-3 min-w-0">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Bookings</p>
                        <p class="text-2xl font-display font-bold text-gray-900 dark:text-white mt-0.5">{{ $metrics['total_bookings'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#161615] overflow-hidden shadow-sm hover:shadow-lg rounded-2xl p-5 border border-gray-100 dark:border-gray-800/60 transform hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/10 text-yellow-600 dark:text-yellow-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="ml-3 min-w-0">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Bookings</p>
                        <p class="text-2xl font-display font-bold text-gray-900 dark:text-white mt-0.5">{{ $metrics['pending_bookings'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#161615] overflow-hidden shadow-sm hover:shadow-lg rounded-2xl p-5 border border-gray-100 dark:border-gray-800/60 transform hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/10 text-green-600 dark:text-green-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    </div>
                    <div class="ml-3 min-w-0">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Vehicles</p>
                        <p class="text-2xl font-display font-bold text-gray-900 dark:text-white mt-0.5">{{ $metrics['active_vehicles'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#161615] overflow-hidden shadow-sm hover:shadow-lg rounded-2xl p-5 border border-gray-100 dark:border-gray-800/60 transform hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/10 text-purple-600 dark:text-purple-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div class="ml-3 min-w-0">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Available Drivers</p>
                        <p class="text-2xl font-display font-bold text-gray-900 dark:text-white mt-0.5">{{ $metrics['available_drivers'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#161615] overflow-hidden shadow-sm hover:shadow-lg rounded-2xl p-5 border border-gray-100 dark:border-gray-800/60 transform hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/10 text-red-600 dark:text-red-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                    </div>
                    <div class="ml-3 min-w-0">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Driver Rejections</p>
                        <p class="text-2xl font-display font-bold text-gray-900 dark:text-white mt-0.5">{{ $metrics['driver_rejections'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- What the finished rides came to: the total price billed for them and
             the ground they covered, with a way into the full list. -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white font-display">Completed Rides</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">What every closed trip was billed, from the rate card of the vehicle that drove it.</p>
                </div>
                <a href="{{ route('admin.bookings.completed') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors whitespace-nowrap">View all completed rides &rarr;</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-100 dark:divide-gray-800/60">
                <div class="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Trip Price</p>
                    <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ number_format($metrics['total_trip_price'], 2) }}</p>
                </div>
                <div class="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rides Completed</p>
                    <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ number_format($metrics['completed_rides']) }}</p>
                </div>
                <div class="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Distance Covered</p>
                    <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ number_format($metrics['total_trip_km']) }} <span class="text-base">km</span></p>
                </div>
            </div>
        </div>

        <!-- Rejected Rides: which driver refused which ride, and why -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white font-display">Rejected Rides</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Rides a driver refused (or never answered in 6 hours), with his reason.</p>
                </div>
                <a href="{{ route('admin.bookings.index', ['status' => \App\Enums\BookingStatus::REJECTED->value]) }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors whitespace-nowrap">View all rejected &rarr;</a>
            </div>
            <div class="admin-table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left">Booking</th>
                            <th scope="col" class="text-left">Rejected By</th>
                            <th scope="col" class="text-left">Rejection Reason</th>
                            <th scope="col" class="text-left">When</th>
                            <th scope="col" class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        @forelse($recentRejections as $assignment)
                        <tr>
                            <td class="whitespace-nowrap">
                                <div class="text-sm font-semibold text-primary-600 dark:text-primary-400">#{{ $assignment->booking?->booking_number }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $assignment->booking?->customer_name }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $assignment->driver?->name ?? 'Driver removed' }}</div>
                                <span class="mt-1 inline-flex px-2 py-0.5 text-xs leading-5 font-bold rounded-full border
                                    @if($assignment->wasAutoRejected()) bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/50
                                    @else bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50 @endif">
                                    {{ $assignment->rejectionLabel() }}
                                </span>
                            </td>
                            <td class="text-sm text-gray-700 dark:text-gray-300">{{ $assignment->rejection_reason }}</td>
                            <td class="whitespace-nowrap">
                                <div class="font-medium">{{ $assignment->responded_at?->format('d M, Y') }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $assignment->responded_at?->format('h:i A') }}</div>
                            </td>
                            <td class="whitespace-nowrap text-right text-sm font-semibold">
                                @if($assignment->booking)
                                <a href="{{ route('admin.bookings.show', $assignment->booking) }}" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">Manage &rarr;</a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="admin-table-empty">No ride has been rejected by a driver yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="text-base font-bold text-gray-900 dark:text-white font-display">Recent Booking Requests</h3>
                <a href="{{ route('admin.bookings.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors whitespace-nowrap">View All &rarr;</a>
            </div>
            <div class="admin-table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left">Booking ID</th>
                            <th scope="col" class="text-left">Customer</th>
                            <th scope="col" class="text-left">Route</th>
                            <th scope="col" class="text-left">Pickup</th>
                            <th scope="col" class="text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        @forelse($recentBookings as $booking)
                        <tr>
                            <td class="whitespace-nowrap font-semibold text-primary-600 dark:text-primary-400">
                                #{{ $booking->booking_number }}
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $booking->customer_name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $booking->customer_phone }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ optional($booking->pickupCity)->name }}</span>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                    <span class="font-medium">{{ optional($booking->dropCity)->name }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="font-medium">{{ \Carbon\Carbon::parse($booking->pickup_date)->format('d M, Y') }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $booking->pickup_time }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-bold rounded-full border
                                    @if($booking->status === \App\Enums\BookingStatus::TRIP_COMPLETED) bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-900/50
                                    @elseif($booking->isRejectedByDriver()) bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-900/20 dark:text-orange-400 dark:border-orange-900/50
                                    @elseif($booking->status === \App\Enums\BookingStatus::CANCELLED || $booking->status === \App\Enums\BookingStatus::REJECTED) bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50
                                    @else bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/50 @endif">
                                    {{ $booking->displayStatus() }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="admin-table-empty">No bookings found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>