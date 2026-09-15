<tbody class="bg-white dark:bg-[#161615] divide-y divide-gray-100 dark:divide-gray-800/60">
                            @forelse($vehicles as $vehicle)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-[#0a0a0a]/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary-600 dark:text-primary-400">
                                    V-{{ $vehicle->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $vehicle->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $vehicle->model }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ optional($vehicle->category)->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ $vehicle->registration_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ optional($vehicle->city)->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border
                                        @if($vehicle->status == 'Available') bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                                        @elseif($vehicle->status == 'Maintenance') bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-900/50
                                        @else bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50 @endif">
                                        {{ $vehicle->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('associate.vehicles.show', $vehicle) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">View</a>
                                    <a href="{{ route('associate.vehicles.edit', $vehicle) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 ml-3">Edit</a>
                                    <form action="{{ route('associate.vehicles.destroy', $vehicle) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this vehicle?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 ml-3">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center font-medium">No vehicles found in your cities.</td>
                            </tr>
                            @endforelse
                        </tbody>