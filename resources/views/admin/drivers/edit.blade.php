<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Driver') }}: {{ $driver->name }}
        </h2>
    </x-slot>

    <div class="admin-card p-6">
            <x-driver-form
                :action="route('admin.drivers.update', $driver)"
                method="PUT"
                :driver="$driver"
                :cities="$cities"
                :cancel-url="route('admin.drivers.index')"
                submit-label="Update Driver"
            />
    </div>
</x-app-layout>
