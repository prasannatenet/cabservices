<x-associate-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Fleet Details') }}: V-{{ $vehicle->id }}
            </h2>
            <a href="{{ route('associate.vehicles.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Fleet</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $vehicle->name }}</h3>
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border
                        @if($vehicle->status == 'Available') bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                        @elseif($vehicle->status == 'Maintenance') bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-900/50
                        @else bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50 @endif">
                        {{ $vehicle->status }}
                    </span>
                </div>

                @if($vehicle->images->count() > 0)
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        @foreach($vehicle->images as $img)
                            <img src="{{ asset('storage/'.$img->image_path) }}" alt="{{ $vehicle->name }}" class="h-40 w-full object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">No photos uploaded for this vehicle.</p>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4">Vehicle Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                <p class="text-sm text-gray-500 dark:text-gray-400">Assigned City</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ optional($vehicle->city)->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Operating City</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ optional($vehicle->operatingCity)->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Fuel Type</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->fuel_type ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Has AC</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->has_ac ? 'Yes' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Luggage Capacity</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->luggage_capacity ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Last Service Date</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->last_service_date?->format('d M, Y') ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Pricing &amp; Service</h3>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Price per KM</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->price_per_km !== null ? '₹'.$vehicle->price_per_km : 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Price per Day</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->price_per_day !== null ? '₹'.$vehicle->price_per_day : 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Fixed KM per Day</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $vehicle->fixed_km_per_day ?? 'N/A' }}</p>
                        </div>

                        <div class="pt-4 border-t border-gray-100 dark:border-gray-800/60 flex flex-col gap-3">
                            <a href="{{ route('associate.vehicles.edit', $vehicle) }}" class="w-full text-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                Edit Vehicle
                            </a>
                            <a href="{{ route('associate.vehicles.index') }}" class="w-full text-center px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Back to Fleet
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-associate-layout>