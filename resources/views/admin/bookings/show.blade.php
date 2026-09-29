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
                            <p class="font-medium text-gray-900 dark:text-white">{{ $booking->customer_phone }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $booking->customer_email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Trip Details -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Trip Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Date & Time</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($booking->pickup_date)->format('d M Y') }} at {{ $booking->pickup_time }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Service Type</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ optional($booking->serviceType)->name }}</p>
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
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Selected Vehicle</p>
                                <div class="flex items-center gap-4">
                                    @if($booking->vehicle->images->isNotEmpty())
                                        <img src="{{ asset('storage/' . $booking->vehicle->images->first()->image_path) }}" 
                                             alt="{{ $booking->vehicle->name }}" 
                                             class="w-24 h-16 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                    @endif
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $booking->vehicle->name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $booking->vehicle->model }} &bull; {{ $booking->vehicle->vehicle_type }} &bull; {{ $booking->vehicle->seating_capacity }} seats</p>
                                        @if($booking->vehicle->images->count() > 1)
                                            <p class="text-xs text-gray-400 mt-1">{{ $booking->vehicle->images->count() }} images available</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
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

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign/Change Vehicle</label>
                                <select name="vehicle_id" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                    <option value="">-- Select Vehicle --</option>
                                    @foreach($availableVehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ $booking->vehicle_id == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->name }} ({{ $vehicle->vehicle_type }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign/Change Driver</label>
                                <select name="driver_id" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                    <option value="">-- Select Driver --</option>
                                    @foreach($availableDrivers as $driver)
                                        <option value="{{ $driver->id }}" {{ $booking->driver_id == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($booking->dropCity)
                                    <p class="text-xs text-gray-500 mt-1">Showing only {{ $availableDrivers->count() }} driver(s) willing to go to {{ $booking->dropCity->name }}. Drivers who did not select this city are hidden.</p>
                                    @if($availableDrivers->isEmpty())
                                        <p class="text-xs text-amber-600 mt-1 font-medium">No drivers have selected {{ $booking->dropCity->name }} as a preferred city yet.</p>
                                    @endif
                                @else
                                    <p class="text-xs text-gray-500 mt-1">Required to confirm booking.</p>
                                @endif
                            </div>

                            <button type="submit" class="w-full mt-4 bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded-md transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

            </div>

    </div>

</x-app-layout>
