<x-driver-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    Trip Sheet &mdash; {{ $booking->booking_number }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Odometer proof and ride expenses. Everything on this page is visible to the admin.
                </p>
            </div>
            <a href="{{ route('driver.rides') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to My Rides</a>
        </div>
    </x-slot>

    <div class="space-y-8">

        @if($errors->any())
            <div class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Ride summary -->
        <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Route</p>
                    <p class="mt-1 font-bold text-gray-900 dark:text-white">
                        {{ $booking->displayRoute() }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Pickup {{ \Illuminate\Support\Carbon::parse($booking->pickup_date)->format('d M, Y') }} at {{ \Illuminate\Support\Carbon::parse($booking->pickup_time)->format('h:i A') }}
                        &bull; {{ $booking->passengers }} passenger(s)
                    </p>
                </div>
                <span class="px-3 py-1 text-xs leading-5 font-bold rounded-full border
                    @if($booking->status === \App\Enums\BookingStatus::TRIP_COMPLETED) bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                    @elseif(in_array($booking->status, [\App\Enums\BookingStatus::TRIP_STARTED, \App\Enums\BookingStatus::DRIVER_ASSIGNED, \App\Enums\BookingStatus::CONFIRMED])) bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50
                    @elseif(in_array($booking->status, [\App\Enums\BookingStatus::REJECTED, \App\Enums\BookingStatus::DRIVER_REJECTED, \App\Enums\BookingStatus::CANCELLED])) bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50
                    @else bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/50 @endif">
                    {{ $booking->status->value }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-100 dark:border-gray-800/60">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Vehicle</p>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $booking->vehicle?->name ?? $booking->vehicle_reference ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</p>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $booking->customer_name }} &bull; {{ $booking->customer_phone }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Service</p>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ optional($booking->serviceType)->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        @if($booking->canLogExpenses())
            {{-- While the ride is running the driver's browser shares his position
                 with the operations team, who follow the trip on the live map. --}}
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Live Location</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            While this ride runs, your location is shared with the operations team so they can follow the trip on the map.
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-2 px-3 py-1 text-xs font-bold rounded-full border border-green-200 bg-green-50 text-green-700 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-400">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        Sharing
                    </span>
                </div>
                <p id="location-status" class="text-xs text-gray-500 dark:text-gray-400 mt-3">Starting location sharing&hellip;</p>
            </div>

            <script>
                (function () {
                    const statusEl = document.getElementById('location-status');
                    const endpoint = @json(route('driver.location.update', $booking));
                    const csrfToken = @json(csrf_token());

                    if (! navigator.geolocation) {
                        statusEl.textContent = 'This device cannot share its location.';
                        return;
                    }

                    const sendPosition = (position) => {
                        fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                            }),
                        })
                            .then((response) => {
                                statusEl.textContent = response.ok
                                    ? 'Location sharing is on. Last sent ' + new Date().toLocaleTimeString() + '.'
                                    : 'Could not send your location.';
                            })
                            .catch(() => {
                                statusEl.textContent = 'Could not send your location.';
                            });
                    };

                    navigator.geolocation.watchPosition(sendPosition, () => {
                        statusEl.textContent = 'Allow location access in your browser to share this ride with the admin.';
                    }, { enableHighAccuracy: true, maximumAge: 5000, timeout: 30000 });
                })();
            </script>
        @endif

        <!-- Odometer at the start of the ride -->
        <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Odometer at Start</h3>

            @if($booking->canStartTrip())
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Before you move the vehicle, write down the reading on the meter and upload a clear photo of it.
                    This is the record the admin checks the ride against.
                </p>

                <form action="{{ route('driver.trips.start', $booking) }}" method="POST" enctype="multipart/form-data"
                    class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                    @csrf
                    <div>
                        <label for="start_odometer_km" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Odometer Reading (KM) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="start_odometer_km" id="start_odometer_km" min="0" step="1" required
                            value="{{ old('start_odometer_km') }}"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">For example: 145320</p>
                    </div>
                    <div>
                        <label for="start_odometer_photo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Odometer Photo <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="start_odometer_photo" id="start_odometer_photo" accept="image/*" required
                            class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 dark:file:bg-primary-900/30 dark:file:text-primary-300">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">JPG or PNG, up to 4 MB.</p>
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="px-6 py-3 rounded-xl text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 shadow-sm transition-colors">
                            Start Trip
                        </button>
                    </div>
                </form>
            @elseif($booking->hasTripStarted())
                <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reading</p>
                            <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ number_format($booking->start_odometer_km) }} km</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trip Started</p>
                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $booking->trip_started_at?->format('d M, Y \a\t h:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expenses Logged</p>
                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $expenses->count() }} &bull; {{ number_format($expenseTotal, 2) }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Odometer Photo</p>
                        @if($booking->startOdometerPhotoUrl())
                            <a href="{{ $booking->startOdometerPhotoUrl() }}" target="_blank" class="block">
                                <img src="{{ $booking->startOdometerPhotoUrl() }}" alt="Odometer at start"
                                    class="w-full h-32 object-cover rounded-xl border border-gray-200 dark:border-gray-700">
                            </a>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No photo uploaded.</p>
                        @endif
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Once this ride is confirmed you can start it here by uploading the odometer photo and writing the KM reading.
                </p>
            @endif
        </div>

        <!-- Odometer at the end of the ride: the total distance of the ride is the closing reading minus the starting reading -->
        <div id="end-trip" class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-800/60 flex flex-wrap justify-between items-center gap-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Odometer at End</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        At the drop point write the reading on the meter and photograph it. The total distance of this ride is the closing reading minus the starting reading.
                    </p>
                </div>
                @if($booking->tripDistanceKm() !== null)
                    <div class="text-right">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Distance</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white font-display">{{ number_format($booking->tripDistanceKm()) }} km</p>
                        </div>
                    </div>
                @endif
            </div>

            @if($booking->canEndTrip())
                <form action="{{ route('driver.trips.end', $booking) }}" method="POST" enctype="multipart/form-data"
                    class="p-6 bg-gray-50/70 dark:bg-[#0a0a0a] grid grid-cols-1 md:grid-cols-2 gap-5">
                    @csrf
                    <div>
                        <label for="end_odometer_km" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Closing Reading (KM) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="end_odometer_km" id="end_odometer_km" min="{{ $booking->start_odometer_km }}" step="1" required
                            value="{{ old('end_odometer_km') }}"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Cannot be less than the {{ number_format($booking->start_odometer_km) }} km you entered when you started.
                        </p>
                    </div>
                    <div>
                        <label for="end_odometer_photo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Odometer Photo <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="end_odometer_photo" id="end_odometer_photo" accept="image/*" required
                            class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 dark:file:bg-primary-900/30 dark:file:text-primary-300">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">JPG or PNG, up to 4 MB.</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            Ending the ride marks it Trip Completed, so no more expenses can be added afterwards.
                        </p>
                        <button type="submit" class="px-6 py-3 rounded-xl text-sm font-bold text-white bg-green-600 hover:bg-green-700 shadow-sm transition-colors">
                            End Trip
                        </button>
                    </div>
                </form>
            @elseif($booking->hasTripEnded())
                <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-6" style="margin: 25px">
                    <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Closing Reading</p>
                            <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ number_format($booking->end_odometer_km) }} km</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Distance</p>
                            <p class="mt-1 text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($booking->tripDistanceKm()) }} km</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trip Ended</p>
                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $booking->trip_ended_at?->format('d M, Y \a\t h:i A') }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Odometer Photo</p>
                        @if($booking->endOdometerPhotoUrl())
                            <a href="{{ $booking->endOdometerPhotoUrl() }}" target="_blank" class="block">
                                <img src="{{ $booking->endOdometerPhotoUrl() }}" alt="Odometer at end"
                                    class="w-full h-32 object-cover rounded-xl border border-gray-200 dark:border-gray-700">
                            </a>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No photo uploaded.</p>
                        @endif
                    </div>
                </div>

                {{-- What the driver himself earns for this ride, worked out from his
                     own per day rate. The price the customer is charged is the
                     operator's business and is not shown here. --}}
                @php $earnings = $driver->earningsFor($booking); @endphp
                @if($earnings)
                    <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-800/60" style="margin: 25px">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">My Earnings for This Ride</p>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400 font-display">{{ number_format($earnings['total'], 2) }}</p>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $earnings['days'] }} day(s) &times; {{ number_format($earnings['rate'], 2) }} per day
                            </p>
                        </div>
                    </div>
                @elseif($driver->isPaidPerDay())
                    <p class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-800/60 text-sm text-gray-500 dark:text-gray-400">
                        No per day salary has been set on your profile yet, so your earnings for this ride cannot be worked out.
                    </p>
                @endif
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    @if($booking->status === \App\Enums\BookingStatus::TRIP_COMPLETED)
                        The admin closed this ride, so no closing reading was recorded for it.
                    @else
                        Once the ride is running you can close it here with the closing reading and photo.
                    @endif
                </p>
            @endif
        </div>

        <!-- Ride expenses -->
        <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-800/60 flex flex-wrap justify-between items-center gap-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Ride Expenses</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Money you paid for this ride &mdash; petrol, diesel, gas money or anything else. Add every bill with its photo.
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Expenses</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white font-display">{{ number_format($expenseTotal, 2) }}</p>
                </div>
            </div>

            @if($booking->canLogExpenses())
                <div class="p-6 bg-gray-50/70 dark:bg-[#0a0a0a] border-b border-gray-100 dark:border-gray-800/60">
                    <form action="{{ route('driver.trips.expenses.store', $booking) }}" method="POST" enctype="multipart/form-data"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        @csrf
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Spent On <span class="text-red-500">*</span>
                            </label>
                            <select name="category" id="category" required
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @foreach($categories as $category)
                                    <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Amount <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="amount" id="amount" min="1" step="0.01" required value="{{ old('amount') }}"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label for="spent_on" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                            <input type="date" name="spent_on" id="spent_on" max="{{ now()->toDateString() }}"
                                value="{{ old('spent_on', now()->toDateString()) }}"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label for="bill_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bill Number</label>
                            <input type="text" name="bill_number" id="bill_number" maxlength="100" value="{{ old('bill_number') }}"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label for="bill_photo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Bill Photo <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="bill_photo" id="bill_photo" accept="image/*" required
                                class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 dark:file:bg-primary-900/30 dark:file:text-primary-300">
                        </div>
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Note</label>
                            <input type="text" name="notes" id="notes" maxlength="1000" value="{{ old('notes') }}" placeholder="Filled 20 litres at Jaipur"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div class="md:col-span-2 lg:col-span-3">
                            <button type="submit" class="px-6 py-3 rounded-xl text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 shadow-sm transition-colors">
                                Add Expense
                            </button>
                        </div>
                    </form>
                </div>
            @endif
            @if(! $booking->canLogExpenses() && ! $booking->hasTripStarted())
                <div class="px-6 py-4 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-900/40 text-sm text-amber-800 dark:text-amber-300">
                    Start the trip first &mdash; expenses can be added while the ride is running.
                </div>
            @elseif(! $booking->canLogExpenses())
                <div class="px-6 py-4 bg-gray-50 dark:bg-[#0a0a0a] border-b border-gray-100 dark:border-gray-800/60 text-sm text-gray-500 dark:text-gray-400">
                    This trip is closed, so the expenses below are locked as the admin's record.
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/60">
                    <thead class="bg-gray-50/50 dark:bg-[#0a0a0a]">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Spent On</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bill No.</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bill Photo</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Note</th>
                            @if($booking->canLogExpenses())
                                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/40">
                        @forelse($expenses as $expense)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $expense->spent_on?->format('d M, Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">{{ $expense->category->label() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">{{ number_format((float) $expense->amount, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $expense->bill_number ?: '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($expense->billPhotoUrl())
                                        <a href="{{ $expense->billPhotoUrl() }}" target="_blank" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">View Bill</a>
                                    @else
                                        <span class="text-sm text-gray-400">Not uploaded</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $expense->notes ?: '—' }}</td>
                                @if($booking->canLogExpenses())
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <form action="{{ route('driver.trips.expenses.destroy', [$booking, $expense]) }}" method="POST"
                                            onsubmit="return confirm('Remove this expense?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700 dark:text-red-400">Remove</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $booking->canLogExpenses() ? 7 : 6 }}" class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400 text-center font-medium">
                                    No expense added for this ride yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-driver-layout>
