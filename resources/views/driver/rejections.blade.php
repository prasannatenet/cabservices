<x-driver-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('My Rejected Rides') }}
            </h2>
            <a href="{{ route('driver.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800/60">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Rides you turned down</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Each ride you rejected stays here with the reason you wrote, so you always know what you told the admin.
                Rides you never answered inside the 6 hour window are marked as <span class="font-semibold text-gray-700 dark:text-gray-200">No answer in time</span>.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800/60">
                <thead class="bg-gray-50/50 dark:bg-[#0a0a0a]">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Booking</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Route</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date &amp; Time</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Vehicle</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reason You Gave</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rejected On</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/40">
                    @forelse($assignments as $assignment)
                        @php $booking = $assignment->booking; @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary-600 dark:text-primary-400">{{ $booking?->booking_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                {{ $booking?->displayRoute() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                {{ \Illuminate\Support\Carbon::parse($booking?->pickup_date)->format('d M, Y') }} at {{ \Illuminate\Support\Carbon::parse($booking?->pickup_time)->format('h:i A') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                {{ $booking?->vehicle?->name ?? $booking?->vehicle_reference ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full border bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50">
                                    {{ $assignment->rejectionLabel() }}
                                </span>
                                <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $assignment->rejection_reason }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                {{ $assignment->responded_at?->format('d M, Y') }}<span class="block text-xs text-gray-500">{{ $assignment->responded_at?->format('h:i A') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center font-medium">You have not rejected any ride yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100 dark:border-gray-800/60">
            {{ $assignments->links() }}
        </div>
    </div>
</x-driver-layout>