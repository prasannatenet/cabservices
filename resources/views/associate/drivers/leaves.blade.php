    <x-associate-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Leaves for Driver') }}: {{ $driver->name }}
            </h2>
            <a href="{{ route('associate.drivers.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                Back to Drivers
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Add Leave</h3>
                    <form action="{{ route('associate.drivers.leaves.store', $driver) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        @csrf
                        <div>
                            <x-input-label for="start_date" :value="__('Start Date')" />
                            <x-text-input type="date" name="start_date" id="start_date" class="block mt-1 w-full" :value="old('start_date')" required />
                        </div>
                        <div>
                            <x-input-label for="end_date" :value="__('End Date')" />
                            <x-text-input type="date" name="end_date" id="end_date" class="block mt-1 w-full" :value="old('end_date')" required />
                        </div>
                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div>
                            <x-primary-button>{{ __('Add Leave') }}</x-primary-button>
                        </div>
                        <div class="md:col-span-4">
                            <x-input-label for="reason" :value="__('Reason')" />
                            <x-text-input type="text" name="reason" id="reason" class="block mt-1 w-full" :value="old('reason')" required />
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Leave History</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/60">
                            <thead class="bg-gray-50/50 dark:bg-[#0a0a0a]">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dates</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reason</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-[#161615] divide-y divide-gray-100 dark:divide-gray-800/60">
                                @forelse ($leaves as $leave)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                            {{ $leave->start_date->format('M d, Y') }} - {{ $leave->end_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $leave->reason }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <form action="{{ route('associate.drivers.leaves.update', [$driver, $leave]) }}" method="POST" class="flex items-center gap-2">
                                                @csrf
                                                @method('PUT')
                                                <select name="status" class="text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" onchange="this.form.submit()">
                                                    <option value="Pending" {{ $leave->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="Approved" {{ $leave->status == 'Approved' ? 'selected' : '' }}>Approved</option>
                                                    <option value="Rejected" {{ $leave->status == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <form action="{{ route('associate.drivers.leaves.destroy', [$driver, $leave]) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this leave record?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400 font-medium">No leave records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $leaves->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-associate-layout>