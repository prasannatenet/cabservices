<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Fleet Details:') }} V-{{ $vehicle->id }}
            </h2>
            <a href="{{ route('admin.vehicles.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Fleet</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Vehicle Photos -->
                    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Vehicle Photos</h3>
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
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Vehicle Information</h3>
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
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Documents</h3>

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
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Pricing &amp; Service</h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Price per KM</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->price_per_km !== null ? '₹'.$vehicle->price_per_km : 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Price per Day</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->price_per_day !== null ? '₹'.$vehicle->price_per_day : 'N/A' }}
                                    @if($vehicle->fixed_km_per_day)
                                        <span class="text-sm text-gray-500 dark:text-gray-400">(Fixed {{ $vehicle->fixed_km_per_day }} KM/day)</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Fixed KM per Day</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->fixed_km_per_day ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Last Service Date</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->last_service_date?->format('d M, Y') ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                        <div class="flex flex-col gap-3">
                            <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="w-full text-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                Edit Vehicle
                            </a>
                            <a href="{{ route('admin.vehicles.index') }}" class="w-full text-center px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Back to Fleet
                            </a>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>