    <x-associate-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Driver') }}: {{ $driver->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                <div class="mb-6 flex justify-between items-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ optional($driver->currentCity)->name }} &middot; {{ $driver->license_number }}
                    </p>
                    <a href="{{ route('associate.drivers.show', $driver) }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">View details</a>
                </div>

                <x-driver-form
                    :action="route('associate.drivers.update', $driver)"
                    method="PUT"
                    :driver="$driver"
                    :cities="$cities"
                    :cancel-url="route('associate.drivers.index')"
                    submit-label="Update Driver"
                />
            </div>
        </div>
    </div>
</x-associate-layout>