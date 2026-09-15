<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New Associate') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden p-6">
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                    Create the associate's login and pick the cities he will manage. Give him the login ID and password &mdash; he logs in at the same login page as you.
                </p>

                <x-associate-form
                    :action="route('admin.associates.store')"
                    :cities="$cities"
                    :cancel-url="route('admin.associates.index')"
                    submit-label="Save Associate"
                />
            </div>
        </div>
    </div>
</x-app-layout>