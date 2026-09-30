<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New Driver') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl">
        <div class="admin-card p-6">
            <x-driver-form
                :action="route('admin.drivers.store')"
                :cities="$cities"
                :associates="$associates"
                :cancel-url="route('admin.drivers.index')"
                submit-label="Save Driver"
            />
        </div>
    </div>
</x-app-layout>
