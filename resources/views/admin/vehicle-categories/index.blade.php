<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Vehicle Categories') }}
            </h2>
            <a href="{{ route('admin.vehicle-categories.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                + Add Category
            </a>
        </div>
    </x-slot>

    <div class="admin-card">
        <div class="p-4 sm:p-6">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif
        </div>

        <div class="admin-table-scroll">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-left">Image</th>
                                <th scope="col" class="text-left">Name</th>
                                <th scope="col" class="text-left">Capacity</th>
                                <th scope="col" class="text-left">Status</th>
                                <th scope="col" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                            @forelse ($categories as $category)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        @if($category->image)
                                            <img src="{{ asset('storage/'.$category->image) }}" alt="{{ $category->name }}" class="h-10 w-10 object-cover rounded">
                                        @else
                                            <span class="text-gray-400">No image</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap">{{ $category->name }}</td>
                                    <td class="whitespace-nowrap">{{ $category->default_seating_capacity }}</td>
                                    <td class="whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $category->status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $category->status }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap text-right font-medium">
                                        <a href="{{ route('admin.vehicle-categories.edit', $category) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">Edit</a>
                                        <form action="{{ route('admin.vehicle-categories.destroy', $category) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="admin-table-empty">No categories found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
        <div class="admin-card-footer">
            {{ $categories->links() }}
        </div>
    </div>
</x-app-layout>
