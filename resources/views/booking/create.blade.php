<x-public-layout>
    <div class="bg-gray-50 dark:bg-gray-950 py-12 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8 text-center animate-fade-in">
                <h2 class="text-3xl font-display font-bold text-gray-900 dark:text-white">Complete Your Booking</h2>
                <p class="text-gray-500 mt-2">Check the cab and the trip details below, then send us the booking request.</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden animate-slide-up">
                
                <form action="{{ route('booking.store') }}" method="POST"
                      x-data="bookingReview({
                          ratePerDay: {{ json_encode((float) ($priceEstimate['billed_price_per_day'] ?? 0)) }},
                          kmPerDay: {{ json_encode((int) ($vehicle->fixed_km_per_day ?? 0)) }},
                          pickupDate: {{ json_encode(old('pickup_date', $searchParams['pickup_date'] ?? '')) }},
                          dropDate: {{ json_encode(old('drop_date', $searchParams['drop_date'] ?? '')) }}
                      })">
                    @csrf

                    {{-- Everything the search settled on the first page rides along as hidden
                         fields: the customer only ever sees and edits the trip form itself. --}}
                    <input type="hidden" name="vehicle_id" value="{{ $searchParams['vehicle_id'] ?? ($vehicle?->id ?? '') }}">
                    <input type="hidden" name="passengers" value="{{ $searchParams['passengers'] ?? 1 }}">
                    <input type="hidden" name="service_type_id" value="{{ $searchParams['service_type_id'] ?? '' }}">
                    <input type="hidden" name="pickup_city_id" value="{{ $searchParams['pickup_city_id'] ?? '' }}">
                    <input type="hidden" name="drop_city_id" value="{{ $searchParams['drop_city_id'] ?? '' }}">
                    <input type="hidden" name="drop_city" value="{{ old('drop_city', $searchParams['drop_city'] ?? '') }}">
                    <input type="hidden" name="pickup_date" value="{{ $searchParams['pickup_date'] ?? '' }}">
                    <input type="hidden" name="pickup_time" value="{{ $searchParams['pickup_time'] ?? '' }}">
                    <input type="hidden" name="drop_date" value="{{ $searchParams['drop_date'] ?? '' }}">
                    <input type="hidden" name="drop_time" value="{{ $searchParams['drop_time'] ?? '' }}">
                    <input type="hidden" name="vehicle_preference" value="{{ old('vehicle_preference', $searchParams['vehicle_preference'] ?? '') }}">

                    @if($vehicle)
                    <!-- Selected Cab -->
                    <div class="p-8 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                        <h3 class="font-display font-bold text-lg text-gray-900 dark:text-white mb-4">Your Cab</h3>
                        <div class="flex flex-col sm:flex-row gap-6">
                            @if($vehicle->images->isNotEmpty())
                                <img src="{{ asset('storage/'.$vehicle->images->first()->image_path) }}" alt="{{ $vehicle->name }}" class="w-full sm:w-52 h-40 object-cover rounded-2xl border border-gray-200 dark:border-gray-700">
                            @endif
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <p class="font-display font-bold text-xl text-gray-900 dark:text-white">{{ $vehicle->name }}</p>
                                    <span class="px-2.5 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-xs font-bold rounded-lg">{{ $vehicle->vehicle_type }}</span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                                    {{ $vehicle->model }} &bull; {{ $vehicle->seating_capacity }} Seats
                                    @if($vehicle->has_ac) &bull; AC @endif
                                    @if($vehicle->fuel_type) &bull; {{ $vehicle->fuel_type }} @endif
                                    @if($vehicle->luggage_capacity) &bull; {{ $vehicle->luggage_capacity }} Bags @endif
                                </p>

                                @if(trim($vehicle->features ?? '') !== '')
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        @foreach(explode(',', $vehicle->features) as $feature)
                                            @if(trim($feature))
                                                <span class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-600 dark:text-gray-300 text-xs rounded-lg">{{ trim($feature) }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    Based in {{ $vehicle->city?->name ?? 'our fleet' }}
                                    @if($vehicle->price_per_day)
                                        &bull; &#8377;{{ number_format((float) $vehicle->price_per_day, 0) }}/day
                                    @endif
                                    @if($vehicle->price_per_km)
                                        + &#8377;{{ number_format((float) $vehicle->price_per_km, 2) }}/km
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="p-8 border-b border-gray-100 dark:border-gray-800 bg-amber-50 dark:bg-amber-900/20">
                        <p class="text-sm text-amber-700 dark:text-amber-300">
                            No cab was picked with this search.
                            <a href="{{ route('home') }}" class="font-semibold underline">Search again</a> to choose one before requesting the booking.
                        </p>
                    </div>
                    @endif

                <!-- Trip Details: the same form he filled in on the first page, carried over
                     with everything he typed still in it and open for correction. -->
                <div class="p-8 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="font-display font-bold text-lg text-gray-900 dark:text-white mb-4">Trip Details</h3>

                    <datalist id="city-names">
                        @foreach($cities as $city)
                            <option value="{{ $city->name }}">{{ $city->name }}</option>
                        @endforeach
                    </datalist>

                    {{-- The places he tells us now; the cities and the schedule he settled on
                         the search page ride along as the hidden fields above. --}}
                    <p class="mb-5 text-sm text-gray-600 dark:text-gray-300">
                        {{ $cities->firstWhere('id', $searchParams['pickup_city_id'])?->name ?? '' }}
                        &rarr; {{ $searchParams['drop_city'] ?? ($cities->firstWhere('id', $searchParams['drop_city_id'])?->name ?? '') }}
                        &bull; Pickup {{ \Carbon\Carbon::parse($searchParams['pickup_date'])->format('d M Y') }} at {{ $searchParams['pickup_time'] }}
                        @if(!empty($searchParams['drop_date']))
                            &bull; Drop {{ \Carbon\Carbon::parse($searchParams['drop_date'])->format('d M Y') }}@if(!empty($searchParams['drop_time'])) at {{ $searchParams['drop_time'] }}@endif
                        @endif
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Pickup Location</label>
                            <input type="text" name="pickup_location" list="city-names" value="{{ old('pickup_location', $searchParams['pickup_location'] ?? '') }}" required placeholder="e.g. Station Road, Jaipur" class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                            @error('pickup_location') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Pickup Landmark</label>
                            <input type="text" name="pickup_landmark" value="{{ old('pickup_landmark', $searchParams['pickup_landmark'] ?? '') }}" placeholder="e.g. Opposite City Mall gate" class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">Optional - a spot the driver can recognise.</p>
                            @error('pickup_landmark') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Pickup Location Link (Google Maps)</label>
                            <input type="url" name="pickup_location_link" value="{{ old('pickup_location_link', $searchParams['pickup_location_link'] ?? '') }}" placeholder="https://maps.google.com/..." class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">Optional - paste the share link of your pickup point.</p>
                            @error('pickup_location_link') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Drop Location</label>
                            <input type="text" name="drop_location" list="city-names" value="{{ old('drop_location', $searchParams['drop_location'] ?? '') }}" required placeholder="e.g. Hawa Mahal Road, Jaipur" class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                            @error('drop_location') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                        {{-- The schedule and the vehicle preference were settled on the search
                             page and ride along as the hidden fields at the top of the form. --}}
                </div>

                <div class="p-8 border-t border-gray-100 dark:border-gray-800">
                    @if(session('error'))
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-xl border border-red-100 dark:border-red-900/30">
                            {{ session('error') }}
                        </div>
                    @endif

                    <h3 class="font-display font-bold text-lg text-gray-900 dark:text-white mb-6">Contact Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                                <input type="text" name="customer_name" value="{{ old('customer_name') }}" required class="w-full bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all">
                                @error('customer_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Email Address</label>
                                <input type="email" name="customer_email" value="{{ old('customer_email') }}" required class="w-full bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all">
                                @error('customer_email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Phone Number</label>
                                <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" required class="w-full bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all">
                                @error('customer_phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            </div>
                        </div>

                        {{-- Below the form: the cost of the ride, worked out again whenever the
                             dates on the trip form above are changed. --}}
                        <div class="px-8 pt-8 border-t border-gray-200 dark:border-gray-800">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                <h3 class="font-display font-bold text-lg text-gray-900 dark:text-white">Total Amount</h3>
                                @if($priceEstimate)
                                    <p class="text-sm text-gray-500 dark:text-gray-400"><span x-text="days + ' day hire'">{{ $priceEstimate['billed_days'] }} day hire</span></p>
                                @endif
                            </div>

                            @if($priceEstimate)
                                @php
                                    $ratePerDay = (float) $priceEstimate['billed_price_per_day'];
                                    $days = (int) $priceEstimate['billed_days'];
                                    $kmPerDay = (int) ($vehicle->fixed_km_per_day ?? 0);
                                    $totalKm = (int) $priceEstimate['billed_included_km'];
                                @endphp

                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Price Structure</p>

                                <dl class="space-y-3 text-sm">
                                    <div class="pb-3 border-b border-gray-100 dark:border-gray-800">
                                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Rate card</dt>
                                        <dd class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-gray-700 dark:text-gray-300">
                                            <span>
                                                <span class="font-display font-bold text-lg text-primary-600 dark:text-primary-400">&#8377;<span x-text="money(ratePerDay)">{{ number_format($ratePerDay, 2) }}</span></span>
                                                <span class="text-gray-500 dark:text-gray-400">per day</span>
                                            </span>
                                            @if($kmPerDay > 0)
                                                <span>
                                                    <span class="font-display font-bold text-lg text-gray-900 dark:text-white"><span x-text="whole(kmPerDay)">{{ number_format($kmPerDay) }}</span></span>
                                                    <span class="text-gray-500 dark:text-gray-400">km included per day</span>
                                                </span>
                                            @endif
                                            @if($priceEstimate['billed_price_per_km'] > 0)
                                                <span>
                                                    <span class="font-display font-bold text-lg text-gray-900 dark:text-white">&#8377;{{ number_format($priceEstimate['billed_price_per_km'], 2) }}</span>
                                                    <span class="text-gray-500 dark:text-gray-400">per extra km</span>
                                                </span>
                                            @endif
                                        </dd>
                                    </div>

                                    <div class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">
                                            <span x-text="days + ' day(s) hire &#215; &#8377;' + money(ratePerDay) + ' per day'">{{ $days }} day(s) hire &times; &#8377;{{ number_format($ratePerDay, 2) }} per day</span>
                                            @if($kmPerDay > 0)
                                                <span class="block text-xs text-gray-400 dark:text-gray-500">
                                                    <span x-text="days + ' &#215; ' + whole(kmPerDay) + ' km = ' + whole(includedKm) + ' km included in total'">{{ $days }} &times; {{ number_format($kmPerDay) }} km = {{ number_format($totalKm) }} km included in total</span>
                                                </span>
                                            @endif
                                        </dt>
                                        <dd class="font-semibold text-gray-900 dark:text-white whitespace-nowrap" x-text="money(total)">{{ number_format($priceEstimate['base_amount'], 2) }}</dd>
                                    </div>

                                    <div class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">
                                            @if($priceEstimate['billed_price_per_km'] > 0)
                                                Distance beyond <span x-text="whole(includedKm)">{{ number_format($totalKm) }}</span> km included
                                                &mdash; &#8377;{{ number_format($priceEstimate['billed_price_per_km'], 2) }} per km
                                            @else
                                                Extra distance is not charged on this vehicle
                                            @endif
                                        </dt>
                                        <dd class="font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                            @if($priceEstimate['billed_price_per_km'] > 0)
                                                Billed after the trip
                                            @else
                                                &#8377;0.00
                                            @endif
                                        </dd>
                                    </div>

                                    <div class="flex items-center justify-between gap-4 pt-3 mt-1 border-t border-gray-200 dark:border-gray-800">
                                        <dt class="font-bold text-gray-900 dark:text-white">
                                            Estimated Total
                                            <span class="block text-xs font-normal text-gray-400 dark:text-gray-500">
                                                <span x-text="days + ' day(s) at &#8377;' + money(ratePerDay)">{{ $days }} day(s) at &#8377;{{ number_format($ratePerDay, 2) }}</span>
                                            </span>
                                        </dt>
                                        <dd class="font-display font-bold text-2xl text-primary-600 dark:text-primary-400 whitespace-nowrap" x-text="'&#8377;' + money(total)">&#8377;{{ number_format($priceEstimate['total_amount'], 2) }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-4 p-4 rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20">
                                    <div class="flex items-start gap-2 text-amber-700 dark:text-amber-300">
                                        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <p class="text-sm">
                                            <span class="font-semibold">Amount may vary at the end.</span>
                                            The total above covers the whole {{ $days }} day hire. Any distance driven beyond the
                                            included kilometres is billed from the odometer readings once the trip ends.
                                        </p>
                                    </div>
                                </div>
                                </dl>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Pricing for this vehicle is confirmed by our team once your request is reviewed.
                                </p>
                            @endif
                        </div>

                        <div class="px-8 pb-8 mt-8">
                            <button type="submit" class="w-full py-4 bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-700 hover:to-primary-600 text-white font-bold rounded-xl shadow-lg shadow-primary-500/30 transform hover:-translate-y-1 transition-all duration-300">
                                Request Booking
                            </button>
                            <p class="text-center text-xs text-gray-400 mt-4">By submitting, you agree to our Terms and Conditions.</p>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-public-layout>
