    <x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Driver') }}: {{ $driver->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                <x-driver-form
                    :action="route('admin.drivers.update', $driver)"
                    method="PUT"
                    :driver="$driver"
                    :cities="$cities"
                    :cancel-url="route('admin.drivers.index')"
                    submit-label="Update Driver"
                />
            </div>
        </div>
    </div>
</x-app-layout>