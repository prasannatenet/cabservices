@props(['action'])

<form action="{{ $action }}" method="GET" {{ $attributes->merge(['class' => 'p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-800/60 bg-gray-50 dark:bg-[#0f0f0f]/40']) }}>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 items-end">
        {{ $slot }}
    </div>
    <div class="mt-4 flex flex-wrap items-center gap-2">
        <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition-colors">
            Apply Filters
        </button>
        <a href="{{ $action }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
            Reset
        </a>
    </div>
</form>
