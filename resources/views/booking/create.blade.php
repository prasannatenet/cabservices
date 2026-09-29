<x-public-layout>
    <div class="bg-gray-50 dark:bg-gray-950 py-12 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8 text-center animate-fade-in">
                <h2 class="text-3xl font-display font-bold text-gray-900 dark:text-white">Complete Your Booking</h2>
                <p class="text-gray-500 mt-2">Almost there! Please provide your contact details to submit the booking request.</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden animate-slide-up">
                
                <div class="p-8 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <h3 class="font-display font-bold text-lg text-gray-900 dark:text-white mb-4">Trip Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Pickup Date & Time</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($searchParams['pickup_date'])->format('d M Y') }} at {{ $searchParams['pickup_time'] }}</p>
                        </div>
                        @if(!empty($searchParams['drop_date']) || !empty($searchParams['drop_time']))
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Drop Date & Time</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                @if(!empty($searchParams['drop_date']))
                                    {{ \Carbon\Carbon::parse($searchParams['drop_date'])->format('d M Y') }}
                                @endif
                                @if(!empty($searchParams['drop_time']))
                                    at {{ $searchParams['drop_time'] }}
                                @endif
                            </p>
                        </div>
                        @endif
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Passengers</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $searchParams['passengers'] }} Person(s)</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-500 mb-1">Route</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $searchParams['pickup_location'] }} &rarr; {{ $searchParams['drop_location'] }}</p>
                        </div>
                    </div>
                </div>

                @if($vehicle)
                <div class="p-8 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <h3 class="font-display font-bold text-lg text-gray-900 dark:text-white mb-4">Selected Vehicle</h3>
                    <div class="flex items-center gap-4">
                        @if($vehicle->images->isNotEmpty())
                            <img src="{{ asset('storage/'.$vehicle->images->first()->image_path) }}" alt="{{ $vehicle->name }}" class="w-24 h-20 object-cover rounded-xl border border-gray-200 dark:border-gray-700">
                        @endif
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->name }}</p>
                            <p class="text-sm text-gray-500">{{ $vehicle->vehicle_type }} &middot; {{ $vehicle->seating_capacity }} Seats @if($vehicle->has_ac) &middot; AC @endif</p>
                        </div>
                    </div>

                    @if($priceEstimate)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-800">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <h4 class="font-display font-bold text-base text-gray-900 dark:text-white">Price Structure</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $priceEstimate['billed_days'] }} day hire
                            </p>
                        </div>

                        @php
                            $ratePerDay = (float) $priceEstimate['billed_price_per_day'];
                            $days = (int) $priceEstimate['billed_days'];
                            $kmPerDay = (int) ($vehicle->fixed_km_per_day ?? 0);
                            $totalKm = (int) $priceEstimate['billed_included_km'];
                        @endphp

                        <dl class="space-y-3 text-sm">
                            {{-- The rate card itself: what one day costs and what one day includes. --}}
                            <div class="pb-3 border-b border-gray-100 dark:border-gray-800">
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Rate card</dt>
                                <dd class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-gray-700 dark:text-gray-300">
                                    <span>
                                        <span class="font-display font-bold text-lg text-primary-600 dark:text-primary-400">&#8377;{{ number_format($ratePerDay, 2) }}</span>
                                        <span class="text-gray-500 dark:text-gray-400">per day</span>
                                    </span>
                                    @if($kmPerDay > 0)
                                        <span>
                                            <span class="font-display font-bold text-lg text-gray-900 dark:text-white">{{ number_format($kmPerDay) }}</span>
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

                            {{-- What this hire comes to: the rate card multiplied by the days. --}}
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-gray-500 dark:text-gray-400">
                                    {{ $days }} day(s) hire &times; &#8377;{{ number_format($ratePerDay, 2) }} per day
                                    @if($kmPerDay > 0)
                                        <span class="block text-xs text-gray-400 dark:text-gray-500">
                                            {{ $days }} &times; {{ number_format($kmPerDay) }} km = {{ number_format($totalKm) }} km included in total
                                        </span>
                                    @endif
                                </dt>
                                <dd class="font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ number_format($priceEstimate['base_amount'], 2) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-gray-500 dark:text-gray-400">
                                    @if($priceEstimate['billed_price_per_km'] > 0)
                                        Distance beyond {{ number_format($totalKm) }} km included
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
                                        {{ $days }} day(s) at &#8377;{{ number_format($ratePerDay, 2) }}
                                    </span>
                                </dt>
                                <dd class="font-display font-bold text-lg text-primary-600 dark:text-primary-400 whitespace-nowrap">
                                    &#8377;{{ number_format($priceEstimate['total_amount'], 2) }}
                                </dd>
                            </div>
                        </dl>

                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            The total above covers the whole {{ $days }} day hire. The final bill is confirmed from
                            the odometer readings once the trip ends, adding only the distance driven beyond the
                            included kilometres.
                        </p>
                    </div>
                    @else
                    <p class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-800 text-sm text-gray-500 dark:text-gray-400">
                        Pricing for this vehicle is confirmed by our team once your request is reviewed.
                    </p>
                    @endif
                </div>
                @endif

                <div class="p-8">
                    @if(session('error'))
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-xl border border-red-100 dark:border-red-900/30">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('booking.store') }}" method="POST">
                        @csrf
                        @foreach($searchParams as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        
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

                            <div class="md:col-span-2 mt-6">
                                <button type="submit" class="w-full py-4 bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-700 hover:to-primary-600 text-white font-bold rounded-xl shadow-lg shadow-primary-500/30 transform hover:-translate-y-1 transition-all duration-300">
                                    Confirm Booking Request
                                </button>
                                <p class="text-center text-xs text-gray-400 mt-4">By submitting, you agree to our Terms and Conditions.</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-public-layout>
