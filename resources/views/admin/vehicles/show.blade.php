<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Fleet Details:') }} V-{{ $vehicle->id }}
            </h2>
            <a href="{{ route('admin.vehicles.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Fleet
            </a>
        </div>
    </x-slot>


    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-8">

                <!-- Vehicle Photos -->
                <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Vehicle Photos
                    </h3>
                    @if($vehicle->images->count() > 0)
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($vehicle->images as $img)
                                <img src="{{ asset('storage/'.$img->image_path) }}" alt="{{ $vehicle->name }}" class="h-40 w-full object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No photos uploaded for this vehicle.</p>
                    @endif
                </div>

                <!-- Vehicle Information -->
                <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4a1 1 0 00-1-1H4a1 1 0 00-1 1v4h1m9-9h3l2 2m-2-2v2l2 2m-2-2h-2m-2 2h-2m2 2h-2m0-2h-2m2-2h-2m2 2h2m-2 0h2m-6 6h6m-6 0h6m0 0h6m-3 0h3"></path></svg>
                        Vehicle Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Vehicle Name</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Model</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->model }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Category</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ optional($vehicle->category)->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Registration Number</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->registration_number }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Seating Capacity</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->seating_capacity }} Seats</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Fuel Type</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->fuel_type ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Air Conditioned</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->has_ac ? 'Yes' : 'No' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Luggage Capacity</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->luggage_capacity ?? 'N/A' }} Bags</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Assigned City</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ optional($vehicle->city)->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Operating City</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ optional($vehicle->operatingCity)->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                            <p class="font-medium">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border
                                    @if($vehicle->status == 'Available') bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                                    @elseif($vehicle->status == 'Maintenance') bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-900/50
                                    @else bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50 @endif">
                                    {{ $vehicle->status }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Features</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->features ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Documents -->
                <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Documents
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <x-document-expiry-badge
                            label="Registration Certificate (RC)"
                            :status="$vehicle->rcExpiryStatus()"
                            :days-remaining="$vehicle->rcDaysRemaining()"
                            :expiry-date="$vehicle->rc_expiry_date"
                            :issue-date="$vehicle->rc_issue_date"
                        />
                        <x-document-expiry-badge
                            label="Insurance"
                            :status="$vehicle->insuranceExpiryStatus()"
                            :days-remaining="$vehicle->insuranceDaysRemaining()"
                            :expiry-date="$vehicle->insurance_expiry_date"
                            :issue-date="$vehicle->insurance_issue_date"
                        />
                    </div>

                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Insurance Photos</h4>
                    @if(count($vehicle->insurance_photo ?? []) > 0)
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            @foreach($vehicle->insurance_photo_urls as $url)
                                <a href="{{ $url }}" target="_blank">
                                    <img src="{{ $url }}" alt="Insurance Photo" class="h-40 w-full object-cover rounded-lg border border-gray-200 dark:border-gray-700 hover:opacity-90 transition-opacity">
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">No insurance photos uploaded.</p>
                    @endif

                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">RC Photos</h4>
                    @if(count($vehicle->rc_photo ?? []) > 0)
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($vehicle->rc_photo_urls as $url)
                                <a href="{{ $url }}" target="_blank">
                                    <img src="{{ $url }}" alt="RC Photo" class="h-40 w-full object-cover rounded-lg border border-gray-200 dark:border-gray-700 hover:opacity-90 transition-opacity">
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No RC photos uploaded.</p>
                    @endif
                </div>
            </div>



            <!-- Right Column: Pricing & Service -->
            <div class="space-y-8">

                <!-- Pricing & Service -->
                <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Pricing &amp; Service
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Price per Day</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                @if($vehicle->price_per_day !== null)
                                    <span class="font-display font-bold text-lg text-primary-600 dark:text-primary-400">&#8377;{{ number_format((float) $vehicle->price_per_day, 2) }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">per day</span>
                                @else
                                    <span class="text-gray-400">Not set</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Price per KM</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                @if($vehicle->price_per_km !== null)
                                    <span class="font-display font-bold text-lg text-primary-600 dark:text-primary-400">&#8377;{{ number_format((float) $vehicle->price_per_km, 2) }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">per km, beyond the included distance</span>
                                @else
                                    <span class="text-gray-400">Not set</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Fixed KM per Day</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                @if($vehicle->fixed_km_per_day)
                                    <span class="font-display font-bold text-lg text-gray-900 dark:text-white">{{ number_format((int) $vehicle->fixed_km_per_day) }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">km included per day</span>
                                @else
                                    <span class="text-gray-400">Not set</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Last Service Date</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->last_service_date?->format('d M, Y') ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <p class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                        A hire is billed for the day rate, which covers the fixed kilometres above. Every kilometre
                        driven beyond them is charged at the per km rate.
                    </p>
                </div>

                <!-- Actions -->
                <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                    <div class="flex flex-col gap-3">
                        <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Edit Vehicle
                        </a>
                        <a href="{{ route('admin.vehicles.index') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Back to Fleet
                        </a>
                    </div>
                </div>
            </div>

    </div>

</x-app-layout>
