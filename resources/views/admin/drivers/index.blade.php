<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Manage Drivers') }}
        </h2>
    </x-slot>

        
    <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">All Drivers</h3>
                <a href="{{ route('admin.drivers.create') }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm shadow-primary-500/30">
                    + Add Driver
                </a>
            </div>

            <div class="p-4 sm:p-5 pb-0">
                <x-admin-filter-bar :action="route('admin.drivers.index')">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, phone, license..." class="block w-56 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
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
                        <select name="city_id" class="block w-40 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">All</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" @selected(request('city_id') == $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Associate</label>
                        <select name="associate" class="block w-44 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">All</option>
                            <option value="{{ $adminOwner }}" @selected(request('associate') === $adminOwner)>Admin Created</option>
                            @foreach($associates as $associate)
                                <option value="{{ $associate->id }}" @selected(request('associate') == $associate->id)>{{ $associate->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-admin-filter-bar>
            </div>
            
            <div class="admin-table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left">Driver ID</th>
                            <th scope="col" class="text-left">Name & Contact</th>
                            <th scope="col" class="text-left">License</th>
                            <th scope="col" class="text-left">City</th>
                            <th scope="col" class="text-left">Associate</th>
                            <th scope="col" class="text-left">Login</th>
                            <th scope="col" class="text-left">Status</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        @forelse($drivers as $driver)
                        <tr>
                            <td class="whitespace-nowrap font-semibold text-primary-600 dark:text-primary-400">
                                D-{{ $driver->id }}
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $driver->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $driver->phone }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                {{ $driver->license_number }}
                            </td>
                            <td class="whitespace-nowrap">
                                {{ optional($driver->currentCity)->name ?? 'N/A' }}
                            </td>
                            <td class="whitespace-nowrap">
                                <x-associate-badge :record="$driver" />
                            </td>
                            <td class="whitespace-nowrap">
                                @if($driver->user)
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50">
                                        {{ $driver->user->username }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">&mdash;</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border 
                                    @if($driver->status == 'Available') bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                                    @elseif($driver->status == 'Unavailable') bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/50
                                    @elseif($driver->status == 'Inactive') bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50
                                    @else bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50 @endif">
                                    {{ $driver->status }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-right font-medium">
                                <a href="{{ route('admin.drivers.show', $driver) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">View</a>
                                <a href="{{ route('admin.drivers.edit', $driver) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 ml-3">Edit</a>
                                <a href="{{ route('admin.drivers.leaves', $driver) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 ml-3">Leaves</a>
                                <form action="{{ route('admin.drivers.destroy', $driver) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this driver?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 ml-3">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="admin-table-empty">No drivers found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="admin-card-footer">
                {{ $drivers->links() }}
            </div>
    </div>

</x-app-layout>
