<x-driver-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('My Cities') }}
            </h2>
            <a href="{{ route('driver.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800/60">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Select the cities you want to go to</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">These are all the cities added by the admin. Tick the ones you are willing to drive in and save.</p>
        </div>

        <form action="{{ route('driver.cities.sync') }}" method="POST" class="p-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @forelse($cities as $city)
                    <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-colors
                        {{ in_array($city->id, $selectedCityIds) ? 'border-primary-400 bg-primary-50/60 dark:border-primary-800 dark:bg-primary-900/10' : 'border-gray-200 hover:border-gray-300 dark:border-gray-800/60 dark:hover:border-gray-700' }}">
                        <input type="checkbox" name="city_ids[]" value="{{ $city->id }}"
                            class="mt-0.5 rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-primary-600 shadow-sm focus:ring-primary-500"
                            @checked(in_array($city->id, $selectedCityIds))>
                        <span>
                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $city->name }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $city->state }}, {{ $city->country }}</span>
                        </span>
                    </label>
                @empty
                    <p class="col-span-full text-sm text-gray-500 dark:text-gray-400 text-center font-medium py-8">No cities available yet. The admin has not added any cities.</p>
                @endforelse
            </div>

            @if($cities->isNotEmpty())
                <div class="flex items-center justify-end gap-4 pt-6 mt-6 border-t border-gray-100 dark:border-gray-800/60">
                    <a href="{{ route('driver.cities') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</a>
                    <button type="submit" class="px-5 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                        Save My Cities
                    </button>
                </div>
            @endif
        </form>
    </div>
</x-driver-layout>