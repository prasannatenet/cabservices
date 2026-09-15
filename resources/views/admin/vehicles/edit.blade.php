    <x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Vehicle') }}: V-{{ $vehicle->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                <x-vehicle-form
                    :action="route('admin.vehicles.update', $vehicle)"
                    method="PUT"
                    route-prefix="admin"
                    :vehicle="$vehicle"
                    :cities="$cities"
                    :categories="$categories"
                    :cancel-url="route('admin.vehicles.index')"
                    submit-label="Update Vehicle"
                />
            </div>
        </div>
    </div>
</x-app-layout>