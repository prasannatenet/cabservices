<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Cities') }}
            </h2>
            <a href="{{ route('admin.cities.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                + Add City
            </a>
        </div>
    </x-slot>

    <div class="admin-card">
        <div class="p-4 sm:p-6 text-gray-900 dark:text-gray-100">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <x-admin-filter-bar :action="route('admin.cities.index')">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="City, state, country..." class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                    <select name="status" class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All</option>
                        <option value="Active" @selected(request('status') === 'Active')>Active</option>
                        <option value="Inactive" @selected(request('status') === 'Inactive')>Inactive</option>
                    </select>
                </div>
            </x-admin-filter-bar>
        </div>

        <div class="admin-table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col" class="text-left">Name</th>
                        <th scope="col" class="text-left">State</th>
                        <th scope="col" class="text-left">Country</th>
                        <th scope="col" class="text-left">Status</th>
                        <th scope="col" class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                    @forelse ($cities as $city)
                        <tr>
                            <td class="whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ $city->name }}</td>
                            <td class="whitespace-nowrap">{{ $city->state }}</td>
                            <td class="whitespace-nowrap">{{ $city->country }}</td>
                            <td class="whitespace-nowrap">
                                <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full {{ $city->status === 'Active' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                                    {{ $city->status }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-right font-medium">
                                <a href="{{ route('admin.cities.nearby', $city) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">Nearby Cities</a>
                                <a href="{{ route('admin.cities.edit', $city) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">Edit</a>
                                <form action="{{ route('admin.cities.destroy', $city) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this city?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="admin-table-empty">No cities found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-card-footer">
            {{ $cities->links() }}
        </div>
    </div>
</x-app-layout>
