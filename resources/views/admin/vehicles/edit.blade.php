<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Vehicle') }}: V-{{ $vehicle->id }}
        </h2>
    </x-slot>

    <div class="admin-card p-6">
            <x-vehicle-form
                :action="route('admin.vehicles.update', $vehicle)"
                method="PUT"
                route-prefix="admin"
                :vehicle="$vehicle"
                :cities="$cities"
                :categories="$categories"
                :associates="$associates"
                :cancel-url="route('admin.vehicles.index')"
                submit-label="Update Vehicle"
            />
    </div>
</x-app-layout>
