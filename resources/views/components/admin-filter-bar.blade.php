@props(['action'])

<form action="{{ $action }}" method="GET" {{ $attributes->merge(['class' => 'mb-6 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50']) }}>
    <div class="flex flex-wrap items-end gap-3">
        {{ $slot }}
        <div class="flex items-center gap-2 ms-auto">
            <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Apply Filters
            </button>
            <a href="{{ $action }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                Reset
            </a>
        </div>
    </div>
</form>
