<x-associate-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Service') }}: {{ $serviceType->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                <x-service-type-form
                    :action="route('associate.service-types.update', $serviceType)"
                    method="PUT"
                    :service-type="$serviceType"
                    :cities="$cities"
                    :cancel-url="route('associate.service-types.index')"
                    submit-label="Update Service"
                    :city-required="true"
                />
            </div>
        </div>
    </div>
</x-associate-layout>