<x-associate-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New Vehicle') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                    A vehicle can only be based in one of your cities. Fields marked as required must be filled.
                </p>

                <x-vehicle-form
                    :action="route('associate.vehicles.store')"
                    route-prefix="associate"
                    :cities="$cities"
                    :categories="$categories"
                    :cancel-url="route('associate.vehicles.index')"
                    submit-label="Save Vehicle"
                />
            </div>
        </div>
    </div>
</x-associate-layout>