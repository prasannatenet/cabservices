<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Edit Associate') }}: {{ $associate->name }}
        </h2>
    </x-slot>

    <div class="admin-card p-6">
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                Changing the cities here takes effect immediately: he gains access to the newly ticked cities and loses the unticked ones.
                The fleet, drivers and services you untick stay in the admin panel.
            </p>

            <x-associate-form
                :action="route('admin.associates.update', $associate)"
                method="PUT"
                :associate="$associate"
                :cities="$cities"
                :cancel-url="route('admin.associates.index')"
                submit-label="Update Associate"
            />
    </div>
</x-app-layout>
