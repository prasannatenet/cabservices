<x-public-layout>
    <div class="bg-gray-50 dark:bg-gray-950 py-12 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Search Summary -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 mb-8 shadow-sm border border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4 animate-fade-in">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Your Search</p>
                    <h2 class="text-xl font-display font-bold text-gray-900 dark:text-white">
                        {{ $pickupCity->name }} &rarr; {{ $dropCity->name }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        {{ \Carbon\Carbon::parse($searchParams['pickup_date'])->format('d M Y') }} at {{ $searchParams['pickup_time'] }}
                        @if(!empty($searchParams['drop_date']) || !empty($searchParams['drop_time']))
                            to
                            @if(!empty($searchParams['drop_date']))
                                {{ \Carbon\Carbon::parse($searchParams['drop_date'])->format('d M Y') }}
                            @endif
                            @if(!empty($searchParams['drop_time']))
                                at {{ $searchParams['drop_time'] }}
                            @endif
                        @endif
                        &bull; {{ $searchParams['passengers'] }} Passenger(s) &bull; {{ $serviceType->name }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('home') }}" class="px-6 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors font-medium text-sm">
                        Modify Search
                    </a>
                </div>
            </div>

            @php
                $hasPreference = !empty($searchParams['vehicle_preference']);
                $preferredVehicles = collect();
                $otherVehicles = collect();
                
                if ($hasPreference) {
                    foreach ($vehicles as $vehicle) {
                        $isPreferred = (
                            stripos($vehicle->name, $searchParams['vehicle_preference']) !== false ||
                            stripos($vehicle->model, $searchParams['vehicle_preference']) !== false ||
                            stripos($vehicle->vehicle_type, $searchParams['vehicle_preference']) !== false
                        );
                        if ($isPreferred) {
                            $preferredVehicles->push($vehicle);
                        } else {
                            $otherVehicles->push($vehicle);
                        }
                    }
                } else {
                    $otherVehicles = $vehicles;
                }
            @endphp

            @if($vehicles->isEmpty())
                <div class="bg-white dark:bg-gray-900 rounded-3xl p-12 text-center shadow-sm border border-gray-100 dark:border-gray-800 animate-slide-up">
                    <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No Vehicles Available</h4>
                    <p class="text-gray-500 max-w-md mx-auto">We couldn't find any vehicles matching your criteria for the selected date and time. Please try adjusting your search parameters.</p>
                </div>
            @else
                @php
                    $hasNearbyVehicles = $vehicles->contains('is_from_nearby_city', true);
                @endphp
                
                @if($hasNearbyVehicles)
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 mb-6 text-center animate-fade-in">
                        <div class="flex items-center justify-center gap-2 text-amber-700 dark:text-amber-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                            <span class="font-medium">No vehicles available in {{ $pickupCity->name }}</span>
                        </div>
                        <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">Check the nearby cities for available vehicles below.</p>
                    </div>
                @endif
                
                @if($hasPreference && $preferredVehicles->isNotEmpty())
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Preferred Matches <span class="text-primary-600 text-lg">({{ $preferredVehicles->count() }})</span></h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                        @foreach($preferredVehicles as $index => $vehicle)
                            @include('booking.partials.vehicle-card', ['vehicle' => $vehicle, 'isPreferred' => true, 'index' => $index, 'searchParams' => $searchParams])
                        @endforeach
                    </div>
                @endif
                
                @if($otherVehicles->isNotEmpty())
                    @if($hasPreference && $preferredVehicles->isNotEmpty())
                        <div class="mb-6 flex items-center justify-between border-t border-gray-200 dark:border-gray-800 pt-8">
                            <h3 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Suggested Alternatives <span class="text-gray-500 text-lg">({{ $otherVehicles->count() }})</span></h3>
                        </div>
                    @elseif($hasPreference && $preferredVehicles->isEmpty())
                        <div class="mb-6 flex items-center justify-between">
                            <h3 class="text-2xl font-display font-bold text-gray-900 dark:text-white">No Exact Matches - Suggested Alternatives <span class="text-gray-500 text-lg">({{ $otherVehicles->count() }})</span></h3>
                        </div>
                    @else
                        <div class="mb-6 flex items-center justify-between">
                            <h3 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Available Vehicles <span class="text-primary-600 text-lg">({{ $otherVehicles->count() }})</span></h3>
                        </div>
                    @endif
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach($otherVehicles as $index => $vehicle)
                            @include('booking.partials.vehicle-card', ['vehicle' => $vehicle, 'isPreferred' => false, 'index' => $index, 'searchParams' => $searchParams])
                        @endforeach
                    </div>
                @endif

            @endif
        </div>
    </div>
</x-public-layout>
