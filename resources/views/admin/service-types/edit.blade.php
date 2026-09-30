<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Service Type') }}: {{ $serviceType->name }}
        </h2>
    </x-slot>

    <div class="admin-card p-6">
            <x-service-type-form
                :action="route('admin.service-types.update', $serviceType)"
                method="PUT"
                :service-type="$serviceType"
                :cities="$cities"
                :associates="$associates"
                :cancel-url="route('admin.service-types.index')"
                submit-label="Update Service"
            />
    </div>
</x-app-layout>
