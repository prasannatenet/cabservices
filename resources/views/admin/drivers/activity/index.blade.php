<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Driver Activity') }}
            </h2>
            <a href="{{ route('admin.drivers.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors">&larr; All Drivers</a>
        </div>
    </x-slot>

    @php
        // Column headers double as sort links and keep the current filters.
        $sortUrl = fn (string $column) => route('admin.driver-activity.index', array_merge(request()->query(), [
            'sort' => $column,
            'direction' => $sort === $column && $direction === 'desc' ? 'asc' : 'desc',
        ]));
        $sortArrow = fn (string $column) => $sort === $column ? ($direction === 'asc' ? '&uarr;' : '&darr;') : '';
        $sortClasses = 'inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors';
        $card = 'bg-white dark:bg-[#161615] rounded-2xl border border-gray-100 dark:border-gray-800/60 shadow-sm p-5';
        $label = 'text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider';
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Fleet wide totals -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="{{ $card }}">
                    <p class="{{ $label }}">Drivers</p>
                    <p class="text-2xl font-display font-bold text-gray-900 dark:text-white mt-1">{{ $totals['drivers'] }}</p>
                </div>
                <div class="{{ $card }}">
                    <p class="{{ $label }}">Rides Assigned</p>
                    <p class="text-2xl font-display font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $totals['assigned'] }}</p>
                </div>
                <div class="{{ $card }}">
                    <p class="{{ $label }}">Ongoing</p>
                    <p class="text-2xl font-display font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $totals['ongoing'] }}</p>
                </div>
                <div class="{{ $card }}">
                    <p class="{{ $label }}">Completed</p>
                    <p class="text-2xl font-display font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $totals['completed'] }}</p>
                </div>
                <div class="{{ $card }}">
                    <p class="{{ $label }}">Driver Rejections</p>
                    <p class="text-2xl font-display font-bold text-orange-600 dark:text-orange-400 mt-1">{{ $totals['rejected'] }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-800/60">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Rides Per Driver</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Assigned counts every ride handed to the driver. A refused ride stays counted here because the ride is freed from him again.
                    </p>
                </div>

                <div class="p-6 pb-0">
                    <x-admin-filter-bar :action="route('admin.driver-activity.index')">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, phone, email..." class="block w-56 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">City</label>
                            <select name="city_id" class="block w-40 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">All</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" @selected(request('city_id') == $city->id)>{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Driver Status</label>
                            <select name="status" class="block w-40 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">All</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                        <input type="hidden" name="direction" value="{{ request('direction') }}">
                    </x-admin-filter-bar>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/60">
                        <thead class="bg-gray-50/50 dark:bg-[#0a0a0a]">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left">
                                    <a href="{{ $sortUrl('name') }}" class="{{ $sortClasses }}">Driver {!! $sortArrow('name') !!}</a>
                                </th>
                                <th scope="col" class="px-6 py-4 text-right">
                                    <a href="{{ $sortUrl('assigned_count') }}" class="{{ $sortClasses }}">Assigned {!! $sortArrow('assigned_count') !!}</a>
                                </th>
                                <th scope="col" class="px-6 py-4 text-right">
                                    <a href="{{ $sortUrl('awaiting_count') }}" class="{{ $sortClasses }}">Awaiting {!! $sortArrow('awaiting_count') !!}</a>
                                </th>
                                <th scope="col" class="px-6 py-4 text-right">
                                    <a href="{{ $sortUrl('ongoing_count') }}" class="{{ $sortClasses }}">Ongoing {!! $sortArrow('ongoing_count') !!}</a>
                                </th>
                                <th scope="col" class="px-6 py-4 text-right">
                                    <a href="{{ $sortUrl('completed_count') }}" class="{{ $sortClasses }}">Completed {!! $sortArrow('completed_count') !!}</a>
                                </th>
                                <th scope="col" class="px-6 py-4 text-right">
                                    <a href="{{ $sortUrl('rejected_count') }}" class="{{ $sortClasses }}">Rejected {!! $sortArrow('rejected_count') !!}</a>
                                </th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Details</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-[#161615] divide-y divide-gray-100 dark:divide-gray-800/60">
                            @forelse($drivers as $driver)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-[#0a0a0a]/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $driver->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $driver->phone }} &middot; {{ optional($driver->currentCity)->name ?? 'No city' }}
                                        </div>
                                        <span class="mt-1 inline-flex px-2 py-0.5 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $driver->status }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-blue-600 dark:text-blue-400">{{ $driver->assigned_count }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-yellow-600 dark:text-yellow-400">{{ $driver->awaiting_count }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ $driver->ongoing_count }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $driver->completed_count }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold {{ $driver->rejected_count > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $driver->rejected_count }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold whitespace-nowrap">
                                        <a href="{{ route('admin.driver-activity.show', $driver) }}" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">View rides &rarr;</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400 text-center font-medium">No drivers match these filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-6 border-t border-gray-100 dark:border-gray-800/60">
                    {{ $drivers->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
