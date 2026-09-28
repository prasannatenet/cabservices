<x-driver-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('New Ride Requests') }}
            </h2>
            <a href="{{ route('driver.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800/60">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Rides waiting for your response</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                You have <span class="font-semibold text-gray-700 dark:text-gray-200">6 hours</span> to accept or reject each ride.
                If you do not respond in time, the ride is automatically rejected and offered to another driver.
            </p>
        </div>

        <div class="p-6 space-y-6">
            @forelse($assignments as $assignment)
                @php
                    $booking = $assignment->booking;
                    $minutesLeft = $assignment->minutesRemaining();
                    $isUrgent = $minutesLeft <= 60;
                @endphp

                <div class="rounded-xl border p-5 {{ $isUrgent ? 'border-red-300 bg-red-50/50 dark:border-red-900/60 dark:bg-red-900/10' : 'border-gray-200 dark:border-gray-700' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-base font-bold text-gray-900 dark:text-white">{{ $booking?->booking_number }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                {{ optional($booking?->pickupCity)->name ?? $booking?->pickup_location }}
                                &rarr;
                                {{ optional($booking?->dropCity)->name ?? $booking?->drop_location }}
                            </p>
                        </div>

                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border whitespace-nowrap
                            {{ $isUrgent
                                ? 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/40 dark:text-red-300 dark:border-red-800'
                                : 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800' }}">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ intdiv($minutesLeft, 60) }}h {{ $minutesLeft % 60 }}m left
                        </span>
                    </div>


                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Pickup</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $booking?->pickup_date?->format('d M, Y') }} at {{ $booking?->pickup_time }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Passengers</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $booking?->passengers }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Vehicle</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $booking?->vehicle?->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Service</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $booking?->serviceType?->name ?? 'N/A' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 pt-5 border-t border-gray-200/70 dark:border-gray-700/70 grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                        <form action="{{ route('driver.assignments.accept', $assignment) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2.5 rounded-lg text-sm font-bold text-white bg-green-600 hover:bg-green-700 transition-colors">
                                Accept Ride
                            </button>
                        </form>

                        <form action="{{ route('driver.assignments.reject', $assignment) }}" method="POST" class="space-y-2">
                            @csrf
                            <label for="rejection_reason_{{ $assignment->id }}" class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                                Reason for rejecting (required)
                            </label>
                            <input type="text" id="rejection_reason_{{ $assignment->id }}" name="rejection_reason" required minlength="5" maxlength="1000"
                                placeholder="e.g. I am not going to that city today"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('rejection_reason')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="w-full px-4 py-2.5 rounded-lg text-sm font-bold text-white bg-red-600 hover:bg-red-700 transition-colors">
                                Reject Ride
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center font-medium py-10">
                    No rides are waiting for your response right now.
                </p>
            @endforelse
        </div>
    </div>
</x-driver-layout>