<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Manage Bookings') }}
        </h2>
    </x-slot>

        
    <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">All Bookings</h3>
                <a href="{{ route('admin.bookings.completed') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors whitespace-nowrap">Completed Rides &rarr;</a>
            </div>

            <div class="p-4 sm:p-5 pb-0">
                <x-admin-filter-bar :action="route('admin.bookings.index')">
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
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Handled By</label>
                        <select name="associate" class="block w-44 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">All</option>
                            <option value="{{ $adminOwner }}" @selected(request('associate') === $adminOwner)>Admin Created</option>
                            @foreach($associates as $associate)
                                <option value="{{ $associate->id }}" @selected(request('associate') == $associate->id)>{{ $associate->name }}</option>
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
                            <th scope="col" class="text-left">Route & Time</th>
                            <th scope="col" class="text-left">Handled By</th>
                            <th scope="col" class="text-left">Status</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        @forelse($bookings as $booking)
                        <tr>
                            <td class="whitespace-nowrap font-semibold text-primary-600 dark:text-primary-400">
                                #{{ $booking->booking_number }}
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $booking->customer_name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $booking->customer_phone }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium">{{ optional($booking->pickupCity)->name }}</span>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                    <span class="font-medium">{{ $booking->displayDropCity() }}</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">{{ \Carbon\Carbon::parse($booking->pickup_date)->format('d M, Y') }} at {{ $booking->pickup_time }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                <x-associate-badge :record="$booking" />
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border
                                    {{-- A driver refusal is a status of its own now, so it gets its own colour
                                         rather than borrowing the plain Rejected one. --}}
                                    @if($booking->isRejectedByDriver()) bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-900/20 dark:text-orange-400 dark:border-orange-900/50
                                    @elseif($booking->status === \App\Enums\BookingStatus::PENDING) bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-900/50
                                    @elseif($booking->status === \App\Enums\BookingStatus::CONFIRMED) bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                                    @elseif($booking->status === \App\Enums\BookingStatus::TRIP_COMPLETED) bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-900/50
                                    @elseif($booking->status === \App\Enums\BookingStatus::CANCELLED || $booking->status === \App\Enums\BookingStatus::REJECTED) bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50
                                    @else bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50 @endif">
                                    {{ $booking->displayStatus() }}
                                </span>
                                @if($booking->isRejectedByDriver() && $booking->driverAssignment)
                                    <div class="mt-2 max-w-xs text-xs text-gray-500 dark:text-gray-400">
                                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $booking->driverAssignment->driver?->name ?? 'Driver removed' }}</span>
                                        &middot; {{ $booking->driverAssignment->rejectionLabel() }}:
                                        {{ Str::limit($booking->driverAssignment->rejection_reason, 70) }}
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right font-semibold">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">Manage &rarr;</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="admin-table-empty">No bookings found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="admin-card-footer">
                {{ $bookings->links() }}
            </div>
    </div>

</x-app-layout>
