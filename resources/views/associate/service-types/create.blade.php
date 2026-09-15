<x-associate-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add Service') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                    A service you create belongs to one of your cities and is only visible and editable by you.
                </p>

                <x-service-type-form
                    :action="route('associate.service-types.store')"
                    :cities="$cities"
                    :cancel-url="route('associate.service-types.index')"
                    submit-label="Save Service"
                    :city-required="true"
                />
            </div>
        </div>
    </div>
</x-associate-layout>