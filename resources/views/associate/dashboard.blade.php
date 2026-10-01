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

            {{-- The banner states the rule rather than repeating the numbers the
                 cards below already show: what belongs to the associate, and what
                 the cities are still used for. --}}
            <div class="mb-8 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#161615]">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">What you manage</p>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                            The fleet, drivers and services <span class="font-semibold text-gray-900 dark:text-white">you created</span>,
                            plus the rides the admin <span class="font-semibold text-gray-900 dark:text-white">assigns to you</span> by putting one of them on a ride.
                        </p>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#0a0a0a] px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-300">
                            {{ $owned['vehicles'] }} {{ \Illuminate\Support\Str::plural('vehicle', $owned['vehicles']) }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#0a0a0a] px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-300">
                            {{ $owned['drivers'] }} {{ \Illuminate\Support\Str::plural('driver', $owned['drivers']) }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#0a0a0a] px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-300">
                            {{ $owned['services'] }} {{ \Illuminate\Support\Str::plural('service', $owned['services']) }}
                        </span>
                    </div>
                </div>

                {{-- Cities no longer hand an associate work, so they are shown as
                     what they are: the places he is allowed to add records. --}}
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800/60">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ empty($managedCityNames) ? 'No cities yet' : 'Cities where you can add vehicles, drivers and services' }}
                    </p>
                    @if(empty($managedCityNames))
                        <p class="mt-1 text-sm text-amber-700 dark:text-amber-400 font-medium">
                            You cannot add anything until the admin assigns you a city. Please contact the administrator.
                        </p>
                    @else
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ implode(', ', $managedCityNames) }}</p>
                    @endif
                </div>
            </div>

            @unless($hasAnything)
                <div class="mb-8 p-4 rounded-xl border border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900/50 dark:bg-blue-900/20 dark:text-blue-300 text-sm font-medium">
                    Nothing has been assigned to you yet. You will see a ride here as soon as the admin puts one of your drivers or vehicles on it.
                </div>
            @endunless

            {{-- Every card links into the list behind it: a count you cannot act
                 on is just decoration. The wording follows ownership, not the
                 cities an associate happens to manage. --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5 mb-8">
                @php
                    $cards = [
                        [
                            'label' => 'Rides Assigned',
                            'value' => $metrics['total_bookings'],
                            'href' => route('associate.bookings.index'),
                            'from' => 'from-blue-50', 'to' => 'to-blue-100', 'darkFrom' => 'dark:from-blue-900/20', 'darkTo' => 'dark:to-blue-800/10', 'icon' => 'text-blue-600 dark:text-blue-400',
                            'path' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        ],
                        [
                            'label' => 'Needs Action',
                            'value' => $metrics['pending_bookings'],
                            'href' => route('associate.bookings.index', ['status' => 'Pending']),
                            'from' => 'from-yellow-50', 'to' => 'to-yellow-100', 'darkFrom' => 'dark:from-yellow-900/20', 'darkTo' => 'dark:to-yellow-800/10', 'icon' => 'text-yellow-600 dark:text-yellow-400',
                            'path' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                        ],
                        [
                            'label' => 'Ongoing Now',
                            'value' => $metrics['ongoing_bookings'],
                            'href' => route('associate.bookings.index'),
                            'from' => 'from-cyan-50', 'to' => 'to-cyan-100', 'darkFrom' => 'dark:from-cyan-900/20', 'darkTo' => 'dark:to-cyan-800/10', 'icon' => 'text-cyan-600 dark:text-cyan-400',
                            'path' => 'M13 10V3L4 14h7v7l9-11h-7z',
                        ],
                        [
                            'label' => 'My Vehicles',
                            'value' => $metrics['active_vehicles'],
                            'href' => route('associate.vehicles.index'),
                            'from' => 'from-green-50', 'to' => 'to-green-100', 'darkFrom' => 'dark:from-green-900/20', 'darkTo' => 'dark:to-green-800/10', 'icon' => 'text-green-600 dark:text-green-400',
                            'path' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                        ],
                        [
                            'label' => 'My Drivers',
                            'value' => $metrics['available_drivers'],
                            'href' => route('associate.drivers.index'),
                            'from' => 'from-purple-50', 'to' => 'to-purple-100', 'darkFrom' => 'dark:from-purple-900/20', 'darkTo' => 'dark:to-purple-800/10', 'icon' => 'text-purple-600 dark:text-purple-400',
                            'path' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        ],
                        [
                            'label' => 'My Services',
                            'value' => $metrics['services'],
                            'href' => route('associate.service-types.index'),
                            'from' => 'from-pink-50', 'to' => 'to-pink-100', 'darkFrom' => 'dark:from-pink-900/20', 'darkTo' => 'dark:to-pink-800/10', 'icon' => 'text-pink-600 dark:text-pink-400',
                            'path' => 'M13 10V3L4 14h7v7l9-11h-7z',
                        ],
                    ];
                @endphp

                @foreach($cards as $card)
                    <a href="{{ $card['href'] }}"
                       class="group bg-white dark:bg-[#161615] overflow-hidden shadow-sm hover:shadow-lg rounded-2xl p-5 border border-gray-100 dark:border-gray-800/60 transform hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-[#0a0a0a]">
                        <div class="flex items-start justify-between gap-2">
                            <div class="p-3 rounded-xl bg-gradient-to-br {{ $card['from'] }} {{ $card['to'] }} {{ $card['darkFrom'] }} {{ $card['darkTo'] }} {{ $card['icon'] }}">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['path'] }}"></path></svg>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-primary-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                        <p class="mt-4 text-3xl font-display font-bold text-gray-900 dark:text-white">{{ $card['value'] }}</p>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                    </a>
                @endforeach
            </div>

            <!-- Recent Bookings -->
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-800/60 flex justify-between items-center gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Recent Rides Assigned To You</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">A ride appears here once the admin assigns one of your own drivers or vehicles to it.</p>
                    </div>
                    <a href="{{ route('associate.bookings.index') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 whitespace-nowrap">View all &rarr;</a>
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
                                        <span class="font-medium">{{ $booking->displayDropCity() }}</span>
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
                                <td colspan="5" class="px-6 py-10 text-center">
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No rides assigned to you yet</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        The admin hands you a ride by putting one of your own drivers or vehicles on it.
                                    </p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- Desktop notifications -->
        <div class="bg-white dark:bg-[#161615] overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60" x-data="desktopNotificationCard()">
            <div class="p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Desktop notifications</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Be told on this computer when a driver accepts or refuses a ride assigned to you. Your browser has to allow it, so each device subscribes on its own.
                </p>

                <p x-cloak x-show="message" :class="failed ? 'text-red-500' : 'text-green-600'" class="text-xs mb-4" x-text="message"></p>

                <div class="flex flex-wrap gap-3">
                    <button type="button" x-on:click="enable"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-800 hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        Enable notifications
                    </button>
                    <button type="button" x-on:click="disable"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                        Turn off
                    </button>
                </div>

                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    A notification cannot appear while the browser is closed completely.
                </p>
            </div>
        </div>

    </div>

</x-associate-layout>
