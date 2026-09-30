<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Booking Details: ') }} {{ $booking->booking_number }}
            </h2>
            <a href="{{ route('admin.bookings.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Bookings</a>
        </div>
    </x-slot>

        
    @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-xl border border-green-100 dark:border-green-900/30">
                {{ session('success') }}
            </div>
    @endif

    @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-xl border border-red-100 dark:border-red-900/30">
                {{ session('error') }}
            </div>
    @endif

    @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-xl border border-red-100 dark:border-red-900/30">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Customer Details -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Customer Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Name</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $booking->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Phone</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $booking->customer_phone ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">WhatsApp</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $booking->customer_whatsapp ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $booking->customer_email ?: '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Trip Details -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Trip Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pickup Date &amp; Time</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($booking->pickup_date)->format('d M Y') }} at {{ $booking->pickup_time }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Drop Date &amp; Time</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                @if($booking->drop_date)
                                    {{ \Carbon\Carbon::parse($booking->drop_date)->format('d M Y') }} at {{ $booking->drop_time ?: '—' }}
                                @else
                                    <span class="text-gray-400">Same-day hire</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Service Type</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ optional($booking->serviceType)->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Passengers</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $booking->passengers ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Current Status</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $booking->displayStatus() }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Handled By</p>
                            <div class="mt-0.5"><x-associate-badge :record="$booking" /></div>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pickup Location</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $booking->pickup_location }} ({{ optional($booking->pickupCity)->name }})</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Drop Location</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $booking->drop_location }} ({{ optional($booking->dropCity)->name }})</p>
                        </div>
                        @if($booking->vehicle)
                            <div class="md:col-span-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Selected Vehicle</p>
                                    <a href="{{ route('admin.vehicles.show', $booking->vehicle) }}" class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 whitespace-nowrap">View vehicle &rarr;</a>
                                </div>
                                <div class="mt-2 flex items-center gap-4">
                                    @if($booking->vehicle->images->isNotEmpty())
                                        <img src="{{ asset('storage/' . $booking->vehicle->images->first()->image_path) }}"
                                             alt="{{ $booking->vehicle->name }}"
                                             class="w-24 h-16 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $booking->vehicle->name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $booking->vehicle->model }} &bull; {{ $booking->vehicle->vehicle_type }} &bull; {{ $booking->vehicle->seating_capacity }} seats</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $booking->vehicle->registration_number }}
                                            @if($booking->vehicle->city)
                                                &bull; {{ $booking->vehicle->city->name }}
                                            @endif
                                        </p>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-bold rounded-full border
                                                @if($booking->vehicle->status === 'Available') bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                                                @else bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50 @endif">
                                                {{ $booking->vehicle->status }}
                                            </span>
                                            <x-associate-badge :record="$booking->vehicle" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="md:col-span-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Selected Vehicle</p>
                                <p class="font-medium text-gray-400 dark:text-gray-500">No vehicle assigned to this ride yet.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Assigned Driver: who is actually driving this ride -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Assigned Driver</h3>
                        @if($booking->driver)
                            <a href="{{ route('admin.drivers.show', $booking->driver) }}" class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 whitespace-nowrap">View driver &rarr;</a>
                        @endif
                    </div>

                    @if($booking->driver)
                        <div class="flex items-start gap-4">
                            @if($booking->driver->profile_photo)
                                <img src="{{ asset('storage/' . $booking->driver->profile_photo) }}"
                                     alt="{{ $booking->driver->name }}"
                                     class="w-16 h-16 object-cover rounded-full border border-gray-200 dark:border-gray-700">
                            @else
                                <div class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xl font-bold text-gray-600 dark:text-gray-300">
                                    {{ strtoupper(substr($booking->driver->name, 0, 1)) }}
                                </div>
                            @endif

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $booking->driver->name }}</p>
                                    <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-bold rounded-full border
                                        @if($booking->driver->status === 'Available') bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                                        @elseif($booking->driver->status === 'On Trip') bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50
                                        @else bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50 @endif">
                                        {{ $booking->driver->status }}
                                    </span>
                                    <x-associate-badge :record="$booking->driver" />
                                </div>

                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">Phone</p>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $booking->driver->phone ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">WhatsApp</p>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $booking->driver->whatsapp ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">Licence Number</p>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $booking->driver->license_number ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">Licence Expiry</p>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $booking->driver->license_expiry?->format('d M Y') ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">Current City</p>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ optional($booking->driver->currentCity)->name ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">Employment</p>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $booking->driver->formattedSalary() ?: '—' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- A refused ride has no driver left on it: the booking's
                             driver is cleared so he can take another ride. --}}
                        <p class="text-sm text-gray-500 dark:text-gray-400">No driver is currently assigned to this ride.</p>
                        @php $refusedDriver = $booking->driverAssignments->last(); @endphp
                        @if($refusedDriver?->driver)
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $refusedDriver->driver->name }} ({{ $refusedDriver->driver->phone }}) was the last driver offered this ride.
                            </p>
                        @endif
                    @endif
                </div>

                <!-- Money: everything this one ride came to, in figures -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Money On This Ride</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        {{-- What the customer pays. --}}
                        <div class="rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Billed To Customer</p>
                            @if($money['has_figure'])
                                <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ number_format($money['total_amount'], 2) }}</p>
                                @unless($money['charged'])
                                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Estimated from the vehicle's current rate card &mdash; this ride was never billed.</p>
                                @endunless
                            @else
                                <p class="mt-1 text-sm font-semibold text-gray-400 dark:text-gray-500">Not priced</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">No rate card on the vehicle, or no distance to price it over.</p>
                            @endif
                        </div>

                        {{-- What the driver earned for driving this ride. --}}
                        <div class="rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Driver Earnings</p>
                            @if($money['driver_earnings'])
                                <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ number_format($money['driver_pay'], 2) }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $money['driver_earnings']['days'] }} day(s) &times; {{ number_format($money['driver_earnings']['rate'], 2) }} per day
                                </p>
                            @else
                                <p class="mt-1 text-sm font-semibold text-gray-400 dark:text-gray-500">No per-ride pay</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $booking->driver ? 'Paid a fixed '.$booking->driver->formattedSalary().' salary, so there is no per-ride cost to subtract.' : 'No driver is assigned to this ride.' }}
                                </p>
                            @endif
                        </div>

                        {{-- What the driver spent on it out of his own pocket. --}}
                        <div class="rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Driver Expenses</p>
                            <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ number_format($money['expenses'], 2) }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $booking->rideExpenses->count() }} claim(s) &mdash; fuel, tolls and the like
                            </p>
                        </div>

                        {{-- What is left once the driver and his expenses are paid. --}}
                        <div class="rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Left For The Operator</p>
                            @if($money['leftover'] !== null)
                                <p class="mt-1 text-2xl font-display font-bold {{ $money['leftover'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ number_format($money['leftover'], 2) }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Billed, less the driver's pay and his claims.</p>
                            @else
                                <p class="mt-1 text-sm font-semibold text-gray-400 dark:text-gray-500">Not available</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Only worked out for a driver paid per day.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Trip Sheet: the odometer proof the driver sends when he starts and when he ends the ride -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Trip Sheet &mdash; Odometer Readings</h3>
                        @if($booking->tripDistanceKm() !== null || $booking->hasTripFare())
                            <div class="text-right">
                                @if($booking->tripDistanceKm() !== null)
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">Total Distance {{ number_format($booking->tripDistanceKm()) }} km</p>
                                @endif
                                @if($booking->hasTripFare())
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">Total Amount {{ number_format((float) $booking->total_amount, 2) }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Odometer at Start</p>
                            @if($booking->start_odometer_km !== null)
                                <p class="font-medium text-gray-900 dark:text-white">{{ number_format($booking->start_odometer_km) }} km</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Trip started {{ $booking->trip_started_at?->format('d M, Y \a\t h:i A') }}
                                </p>
                            @else
                                <p class="font-medium text-gray-500 dark:text-gray-400">Not recorded &mdash; the driver has not started the ride yet.</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Odometer at End</p>
                            @if($booking->end_odometer_km !== null)
                                <p class="font-medium text-gray-900 dark:text-white">{{ number_format($booking->end_odometer_km) }} km</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Trip ended {{ $booking->trip_ended_at?->format('d M, Y \a\t h:i A') }}
                                </p>
                            @else
                                <p class="font-medium text-gray-500 dark:text-gray-400">Not recorded &mdash; the ride has not ended yet.</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Distance</p>
                            @if($booking->tripDistanceKm() !== null)
                                <p class="font-medium text-gray-900 dark:text-white">{{ number_format($booking->tripDistanceKm()) }} km</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Closing reading minus starting reading.</p>
                            @else
                                <p class="font-medium text-gray-500 dark:text-gray-400">Worked out once both readings are in.</p>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Photo at Start</p>
                            @if($booking->startOdometerPhotoUrl())
                                <a href="{{ $booking->startOdometerPhotoUrl() }}" target="_blank" class="inline-block">
                                    <img src="{{ $booking->startOdometerPhotoUrl() }}" alt="Odometer at start"
                                        class="w-48 h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                </a>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">No odometer photo uploaded.</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Photo at End</p>
                            @if($booking->endOdometerPhotoUrl())
                                <a href="{{ $booking->endOdometerPhotoUrl() }}" target="_blank" class="inline-block">
                                    <img src="{{ $booking->endOdometerPhotoUrl() }}" alt="Odometer at end"
                                        class="w-48 h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                </a>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">No odometer photo uploaded.</p>
                            @endif
                        </div>
                    </div>

                    <!-- The bill: the rate card of the vehicle applied to the distance the ride covered -->
                    @php $fare = $booking->tripFare(); @endphp
                    <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Amount Billed</h4>
                            @if($fare)
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($fare['total_amount'], 2) }}</p>
                            @endif
                        </div>

                        @if($fare)
                            <dl class="mt-3 space-y-2 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">
                                        {{ $fare['billed_days'] }} day(s) &times; {{ number_format($fare['billed_price_per_day'], 2) }} per day
                                        &mdash; first {{ number_format($fare['billed_included_km']) }} km included
                                    </dt>
                                    <dd class="font-semibold text-gray-900 dark:text-white">{{ number_format($fare['base_amount'], 2) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">
                                        @if($fare['extra_km'] > 0)
                                            {{ number_format($fare['extra_km']) }} extra km &times; {{ number_format($fare['billed_price_per_km'], 2) }} per km
                                        @else
                                            No extra km &mdash; the ride stayed inside the included kilometres
                                        @endif
                                    </dt>
                                    <dd class="font-semibold text-gray-900 dark:text-white">{{ number_format($fare['extra_km_amount'], 2) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4 pt-2 border-t border-gray-100 dark:border-gray-700">
                                    <dt class="font-bold text-gray-900 dark:text-white">Total</dt>
                                    <dd class="font-bold text-gray-900 dark:text-white">{{ number_format($fare['total_amount'], 2) }}</dd>
                                </div>
                            </dl>
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                Worked out from {{ number_format($booking->tripDistanceKm()) }} km at the rate card of {{ $booking->vehicle?->name ?? 'the vehicle' }}. The rate card was copied into this bill when the trip was closed, so a later change to the rates does not change it.
                            </p>
                        @else
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                @if($booking->vehicle && ((float) $booking->vehicle->price_per_day > 0 || (float) $booking->vehicle->price_per_km > 0))
                                    The amount is worked out from the closing odometer reading. {{ $booking->vehicle->name }} is on
                                    @if((float) $booking->vehicle->price_per_day > 0)
                                        {{ number_format((float) $booking->vehicle->price_per_day, 2) }} per day
                                        @if((int) $booking->vehicle->fixed_km_per_day > 0)
                                            (first {{ number_format((int) $booking->vehicle->fixed_km_per_day) }} km included)
                                        @endif
                                    @endif
                                    @if((float) $booking->vehicle->price_per_km > 0)
                                        {{ (float) $booking->vehicle->price_per_day > 0 ? ', then ' : '' }}{{ number_format((float) $booking->vehicle->price_per_km, 2) }} per extra km
                                    @endif
                                    .
                                @else
                                    No rate card was filled in for {{ $booking->vehicle?->name ?? 'the vehicle' }}, so no amount could be worked out for this ride.
                                @endif
                            </p>
                        @endif
                    </div>
                    @if($booking->startOdometerPhotoUrl() || $booking->endOdometerPhotoUrl())
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Click a photo to open it full size.</p>
                    @endif
                </div>

                <!-- Ride expenses: petrol / diesel / gas money the driver paid for, each with its bill photo -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Trip Expenses</h3>
                        @if($booking->rideExpenses->isNotEmpty())
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $booking->rideExpenses->count() }} bill(s) &bull; Total {{ number_format($booking->expenseTotal(), 2) }}
                            </p>
                        @endif
                    </div>

                    @if($booking->rideExpenses->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">The driver has not claimed any expense for this ride.</p>
                    @else
                        <div class="admin-table-scroll">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th class="text-left">Date</th>
                                        <th class="text-left">Spent On</th>
                                        <th class="text-left">Amount</th>
                                        <th class="text-left">Bill No.</th>
                                        <th class="text-left">Bill Photo</th>
                                        <th class="text-left">Logged By</th>
                                        <th class="text-left">Note</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                                    @foreach($booking->rideExpenses->sortByDesc('spent_on') as $expense)
                                        <tr>
                                            <td class="whitespace-nowrap">{{ $expense->spent_on?->format('d M, Y') }}</td>
                                            <td class="whitespace-nowrap font-semibold text-gray-900 dark:text-white">{{ $expense->category->label() }}</td>
                                            <td class="whitespace-nowrap font-bold text-gray-900 dark:text-white">{{ number_format((float) $expense->amount, 2) }}</td>
                                            <td class="whitespace-nowrap">{{ $expense->bill_number ?: '—' }}</td>
                                            <td class="whitespace-nowrap">
                                                @if($expense->billPhotoUrl())
                                                    <a href="{{ $expense->billPhotoUrl() }}" target="_blank" class="text-sm font-semibold text-primary-600 hover:underline dark:text-primary-400">View Bill</a>
                                                @else
                                                    <span class="text-sm text-gray-400">Not uploaded</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap">{{ $expense->driver?->name ?? 'Driver removed' }}</td>
                                            <td class="text-sm text-gray-600 dark:text-gray-300">{{ $expense->notes ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                @php $rejections = $booking->driverAssignments->whereNotNull('rejection_reason')->sortByDesc('responded_at'); @endphp
                @if($rejections->isNotEmpty() || $booking->status === \App\Enums\BookingStatus::REJECTED)
                    <!-- Rejection details: who turned the ride down, and why -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-red-100 dark:border-red-900/40 overflow-hidden p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">
                            {{ $booking->isRejectedByDriver() ? 'Driver Rejection Details' : 'Rejection Details' }}
                        </h3>

                        @if($booking->isRejectedByDriver())
                            <p class="mb-4 p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 text-sm">
                                This ride is marked <strong>Driver Rejected</strong> because {{ $rejections->first()->driver?->name ?? 'the assigned driver' }}
                                {{ $rejections->first()->wasAutoRejected() ? 'never answered within the 6 hour window' : 'turned it down' }}.
                                The customer has not been notified &mdash; assign another driver below to put it back in play.
                            </p>
                        @elseif($booking->isRejectedByAdmin())
                            <p class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm">
                                This ride is <strong>Rejected</strong> by the admin.
                                @if($booking->rejection_reason)
                                    Reason: {{ $booking->rejection_reason }}
                                @endif
                            </p>
                        @endif

                        @if($rejections->isNotEmpty())
                        <ul class="space-y-4">
                            @foreach($rejections as $rejection)
                                <li class="p-4 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ $rejection->driver?->name ?? 'Driver removed' }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                Rejected on {{ $rejection->responded_at?->format('d M, Y') }} at {{ $rejection->responded_at?->format('h:i A') }}
                                            </p>
                                        </div>
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full
                                            @if($rejection->wasAutoRejected()) bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                            @else bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 @endif">
                                            {{ $rejection->rejectionLabel() }}
                                        </span>
                                    </div>
                                    <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">{{ $rejection->rejection_reason }}</p>
                                </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                @endif

            </div>

            <!-- Right Column: Management Form -->
            <div class="space-y-8">
                
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Manage Booking</h3>
                    
                    <div class="mb-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Current Status</p>
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                            @if($booking->status === \App\Enums\BookingStatus::TRIP_COMPLETED) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400
                            @elseif($booking->isRejectedByDriver()) bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400
                            @elseif($booking->status === \App\Enums\BookingStatus::CANCELLED || $booking->status === \App\Enums\BookingStatus::REJECTED) bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                            @elseif($booking->status === \App\Enums\BookingStatus::PENDING) bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                            @else bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 @endif">
                            {{ $booking->displayStatus() }}
                        </span>
                    </div>

                    {{-- A finished trip is a permanent record, so the whole form is
                         replaced by a notice instead of merely disabling it. --}}
                    @if($booking->isLocked())
                        <div class="rounded-lg border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-900/20 p-4">
                            <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Trip completed &mdash; read only</p>
                            <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-400">
                                This trip has ended, so its driver, vehicle, status and fare can no longer be changed by anyone.
                            </p>
                            @if($booking->trip_ended_at)
                                <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-500">Ended {{ $booking->trip_ended_at->format('d M, Y \a\t h:i A') }}</p>
                            @endif
                        </div>
                    @else
                    <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="space-y-4">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Update Status</label>
                                <select name="status" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                    @foreach(\App\Enums\BookingStatus::cases() as $status)
                                        <option value="{{ $status->value }}" {{ $booking->status === $status ? 'selected' : '' }}>
                                            {{ $status->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                    {{-- An associate's fleet and drivers stay out of these two
                         dropdowns and live behind the checkbox below, so a ride is
                         only ever handed to an associate on purpose. --}}
                    @php
                        $associateIsInPlay = $booking->associate !== null
                            || old('associate_driver_id')
                            || old('associate_vehicle_id');
                    @endphp

                    <div x-data="{ showAssociate: {{ ($pickupCityHasAssociate && $associateIsInPlay) ? 'true' : 'false' }} }">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Assign/Change Vehicle
                                <span class="text-xs font-normal text-gray-500">({{ optional($booking->pickupCity)->name ?? 'pickup city' }})</span>
                            </label>
                            <select name="vehicle_id" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="">-- Select Vehicle --</option>
                                @foreach($availableVehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ $booking->vehicle_id == $vehicle->id && $booking->associate === null ? 'selected' : '' }}>
                                        {{ $vehicle->name }} ({{ $vehicle->vehicle_type }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">{{ $availableVehicles->count() }} vehicle(s) available in {{ optional($booking->pickupCity)->name ?? 'the pickup city' }}.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Assign/Change Driver
                                <span class="text-xs font-normal text-gray-500">({{ optional($booking->pickupCity)->name ?? 'pickup city' }})</span>
                            </label>
                            <select name="driver_id" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="">-- Select Driver --</option>
                                @foreach($availableDrivers as $driver)
                                    <option value="{{ $driver->id }}" {{ $booking->driver_id == $driver->id && $booking->associate === null ? 'selected' : '' }}>
                                        {{ $driver->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if($booking->dropCity)
                                <p class="text-xs text-gray-500 mt-1">{{ $availableDrivers->count() }} own driver(s) in {{ optional($booking->pickupCity)->name ?? 'the pickup city' }} willing to go to {{ $booking->dropCity->name }}.</p>
                                @if($availableDrivers->isEmpty())
                                    <p class="text-xs text-amber-600 mt-1 font-medium">No own drivers in {{ optional($booking->pickupCity)->name ?? 'the pickup city' }} are available for this ride.</p>
                                @endif
                            @else
                                <p class="text-xs text-gray-500 mt-1">Required to confirm booking.</p>
                            @endif
                        </div>
                        {{-- Only offered when this city actually has an associate
                             with a vehicle or driver waiting in it. --}}
                        @if($pickupCityHasAssociate)
                            <div class="rounded-lg border border-purple-200 dark:border-purple-900/50 bg-purple-50/50 dark:bg-purple-900/10 p-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        x-model="showAssociate"
                                        class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-purple-600 focus:ring-purple-500"
                                    >
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                        Include {{ optional($booking->pickupCity)->name ?? 'this city' }} associate's fleet &amp; drivers
                                    </span>
                                </label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-6">
                                    Choosing from these lists hands the ride over to that associate.
                                </p>

                                <div x-show="showAssociate" x-cloak class="mt-3 space-y-3">
                                    @if($associateVehicles->isNotEmpty())
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Associate Vehicle</label>
                                            <select name="associate_vehicle_id" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
                                                <option value="">-- Select Associate Vehicle --</option>
                                                @foreach($associateVehicles as $vehicle)
                                                    <option value="{{ $vehicle->id }}" {{ $booking->vehicle_id == $vehicle->id && $booking->associate !== null ? 'selected' : '' }}>
                                                        {{ $vehicle->name }} ({{ $vehicle->vehicle_type }}) &mdash; {{ $vehicle->ownerLabel() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif

                                    @if($associateDrivers->isNotEmpty())
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Associate Driver</label>
                                            <select name="associate_driver_id" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
                                                <option value="">-- Select Associate Driver --</option>
                                                @foreach($associateDrivers as $driver)
                                                    <option value="{{ $driver->id }}" {{ $booking->driver_id == $driver->id && $booking->associate !== null ? 'selected' : '' }}>
                                                        {{ $driver->name }} &mdash; {{ $driver->ownerLabel() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p class="text-xs text-gray-500 mt-1">{{ $associateDrivers->count() }} associate driver(s) in {{ optional($booking->pickupCity)->name ?? 'the pickup city' }}.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                            <button type="submit" class="w-full mt-4 bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded-md transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                    @endif
                </div>

            </div>

    </div>

</x-app-layout>
