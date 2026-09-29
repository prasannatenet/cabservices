<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Associates') }}
            </h2>
            <a href="{{ route('admin.associates.create') }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition-colors">
                + Add Associate
            </a>
        </div>
    </x-slot>


    <div class="mb-6 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                An associate is a sub-admin for the cities you assign to him. He manages the fleet, drivers, services and bookings of those cities
                in his own portal &mdash; and everything he creates stays visible here in your admin panel.
            </p>
    </div>

    <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">All Associates</h3>
            </div>

            <div class="p-4 sm:p-5 pb-0">
                <x-admin-filter-bar :action="route('admin.associates.index')">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, login id, email..." class="block w-56 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select name="status" class="block w-36 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">All</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">City</label>
                        <select name="city_id" class="block w-44 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">All</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" @selected(request('city_id') == $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-admin-filter-bar>
            </div>

            <div class="admin-table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left">Associate</th>
                            <th scope="col" class="text-left">Login ID</th>
                            <th scope="col" class="text-left">Cities</th>
                            <th scope="col" class="text-left">Status</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        @forelse($associates as $associate)
                        <tr>
                            <td class="whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $associate->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $associate->email ?? 'No email' }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50">
                                    {{ $associate->username }}
                                </span>
                            </td>
                            <td class="text-sm text-gray-600 dark:text-gray-300">
                                @if($associate->assignedCities->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($associate->assignedCities as $city)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full border bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50">{{ $city->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">No cities assigned</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border
                                    @if($associate->status === 'Active') bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                                    @else bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50 @endif">
                                    {{ $associate->status }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-right font-medium">
                                <a href="{{ route('admin.associates.edit', $associate) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Edit</a>
                                <form action="{{ route('admin.associates.destroy', $associate) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this associate? His fleet, drivers and services will stay in your admin panel.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 ml-3">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="admin-table-empty">No associates found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-card-footer">
                {{ $associates->links() }}
            </div>
    </div>

</x-app-layout>
