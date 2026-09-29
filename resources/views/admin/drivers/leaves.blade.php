<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Leaves for Driver') }}: {{ $driver->name }}
            </h2>
            <a href="{{ route('admin.drivers.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                Back to Drivers
            </a>
        </div>
    </x-slot>

        
    @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
    @endif

    <div class="admin-card mb-6">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-medium mb-4">Add Leave</h3>
                <form action="{{ route('admin.drivers.leaves.store', $driver) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    @csrf
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                        <input type="date" name="start_date" id="start_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                        <input type="date" name="end_date" id="end_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason</label>
                        <input type="text" name="reason" id="reason" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <button type="submit" class="w-full px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Add Leave</button>
                    </div>
                </form>
                @if($errors->any())
                    <div class="mt-2 text-red-500 text-sm">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Leave History</h3>
        </div>

        <div class="admin-table-scroll">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-left">Dates</th>
                                <th scope="col" class="text-left">Reason</th>
                                <th scope="col" class="text-left">Status</th>
                                <th scope="col" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                            @forelse ($leaves as $leave)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        {{ $leave->start_date->format('M d, Y') }} - {{ $leave->end_date->format('M d, Y') }}
                                    </td>
                                    <td class="text-sm">{{ $leave->reason }}</td>
                                    <td class="whitespace-nowrap">
                                        <form action="{{ route('admin.drivers.leaves.update', [$driver, $leave]) }}" method="POST" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="status" class="text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" onchange="this.form.submit()">
                                                <option value="Pending" {{ $leave->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="Approved" {{ $leave->status == 'Approved' ? 'selected' : '' }}>Approved</option>
                                                <option value="Rejected" {{ $leave->status == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="whitespace-nowrap text-right font-medium">
                                        <form action="{{ route('admin.drivers.leaves.destroy', [$driver, $leave]) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this leave record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="admin-table-empty">No leave records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

        <div class="admin-card-footer">
            {{ $leaves->links() }}
        </div>
    </div>
</x-app-layout>
