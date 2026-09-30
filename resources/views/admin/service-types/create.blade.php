<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add Service Type') }}
        </h2>
    </x-slot>

    <div class="admin-card p-6">
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                Leave the city empty to create a global service that is available in every city. The associate you pick below owns the
                service and manages it from his own panel.
            </p>

            <x-service-type-form
                :action="route('admin.service-types.store')"
                :cities="$cities"
                :associates="$associates"
                :cancel-url="route('admin.service-types.index')"
                submit-label="Save Service"
            />
    </div>
</x-app-layout>
