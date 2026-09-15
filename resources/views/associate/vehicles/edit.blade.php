<x-associate-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Vehicle') }}: V-{{ $vehicle->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                <div class="mb-6 flex justify-between items-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ optional($vehicle->city)->name }} &middot; {{ $vehicle->registration_number }}
                    </p>
                    <a href="{{ route('associate.vehicles.show', $vehicle) }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">View details</a>
                </div>

                <x-vehicle-form
                    :action="route('associate.vehicles.update', $vehicle)"
                    method="PUT"
                    route-prefix="associate"
                    :vehicle="$vehicle"
                    :cities="$cities"
                    :categories="$categories"
                    :cancel-url="route('associate.vehicles.index')"
                    submit-label="Update Vehicle"
                />
            </div>
        </div>
    </div>
</x-associate-layout>