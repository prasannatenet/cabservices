<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Manage Fleet') }}
        </h2>
    </x-slot>

        
    <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">All Vehicles</h3>
                <a href="{{ route('admin.vehicles.create') }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm shadow-primary-500/30">
                    + Add Vehicle
                </a>
            </div>

            <div class="p-4 sm:p-5 pb-0">
                <x-admin-filter-bar :action="route('admin.vehicles.index')">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, model, registration..." class="block w-56 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
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
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Category</label>
                        <select name="category_id" class="block w-40 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">All</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-admin-filter-bar>
            </div>
            
            <div class="admin-table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left">Vehicle ID</th>
                            <th scope="col" class="text-left">Name & Model</th>
                            <th scope="col" class="text-left">Category</th>
                            <th scope="col" class="text-left">Registration Number</th>
                            <th scope="col" class="text-left">City</th>
                            <th scope="col" class="text-left">Status</th>
                            <th scope="col" class="text-left">Documents</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        @forelse($vehicles as $vehicle)
                        <tr>
                            <td class="whitespace-nowrap font-semibold text-primary-600 dark:text-primary-400">
                                V-{{ $vehicle->id }}
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $vehicle->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $vehicle->model }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                {{ optional($vehicle->category)->name ?? 'N/A' }}
                            </td>
                            <td class="whitespace-nowrap">
                                {{ $vehicle->registration_number }}
                            </td>
                            <td class="whitespace-nowrap">
                                {{ optional($vehicle->city)->name ?? 'N/A' }}
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border 
                                    @if($vehicle->status == 'Available') bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                                    @elseif($vehicle->status == 'Maintenance') bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-900/50
                                    @else bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50 @endif">
                                    {{ $vehicle->status }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex flex-col gap-1 items-start">
                                    <x-document-expiry-badge
                                        compact
                                        label="RC"
                                        :status="$vehicle->rcExpiryStatus()"
                                        :days-remaining="$vehicle->rcDaysRemaining()"
                                    />
                                    <x-document-expiry-badge
                                        compact
                                        label="Insurance"
                                        :status="$vehicle->insuranceExpiryStatus()"
                                        :days-remaining="$vehicle->insuranceDaysRemaining()"
                                    />
                                </div>
                            </td>
                            <td class="whitespace-nowrap text-right font-medium">
                                <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">View</a>
                                <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 ml-3">Edit</a>
                                <form action="{{ route('admin.vehicles.destroy', $vehicle) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this vehicle?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 ml-3">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="admin-table-empty">No vehicles found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="admin-card-footer">
                {{ $vehicles->links() }}
            </div>
    </div>

</x-app-layout>
