<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New Vehicle') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl">
        <div class="admin-card p-6">
            <x-vehicle-form
                :action="route('admin.vehicles.store')"
                route-prefix="admin"
                :cities="$cities"
                :categories="$categories"
                :services="$services"
                :selected-services="[]"
                :associates="$associates"
                :cancel-url="route('admin.vehicles.index')"
                submit-label="Save Vehicle"
            />
        </div>
    </div>
</x-app-layout>
