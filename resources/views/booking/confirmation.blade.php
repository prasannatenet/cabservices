<x-public-layout>
    <div class="bg-gray-50 dark:bg-gray-950 py-20 min-h-screen flex items-center justify-center">
        <div class="max-w-3xl w-full mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-800 p-8 md:p-12 text-center animate-slide-up relative overflow-hidden">
                
                <!-- Background decoration -->
                <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 bg-green-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>

                <div class="w-24 h-24 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500 animate-pulse-slow">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>

                <h1 class="font-display text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">Booking Request Received!</h1>
                <p class="text-gray-500 dark:text-gray-400 mb-8">Thank you, {{ $booking->customer_name }}. Your booking request has been successfully submitted and is currently <span class="font-semibold text-amber-500">PENDING</span>.</p>

                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 text-left mb-8 inline-block max-w-lg w-full">
                    <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Booking Number</span>
                        <span class="font-mono font-bold text-lg text-primary-600 dark:text-primary-400">{{ $booking->booking_number }}</span>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Date & Time</span>
                            <div class="text-right">
                                <span class="text-sm font-medium text-gray-900 dark:text-white block">{{ \Carbon\Carbon::parse($booking->pickup_date)->format('d M Y') }} at {{ $booking->pickup_time }} (Pickup)</span>
                                @if($booking->drop_date || $booking->drop_time)
                                    <span class="text-sm font-medium text-gray-900 dark:text-white block mt-1">
                                        @if($booking->drop_date)
                                            {{ \Carbon\Carbon::parse($booking->drop_date)->format('d M Y') }}
                                        @endif
                                        @if($booking->drop_time)
                                            at {{ $booking->drop_time }}
                                        @endif
                                        (Drop)
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Vehicle</span>
                            <div class="text-right">
                                @if(optional($booking->vehicle)->name)
                                    <span class="text-sm font-medium text-gray-900 dark:text-white block">{{ $booking->vehicle->name }}</span>
                                    @if($booking->vehicle->images->isNotEmpty())
                                        <div class="mt-2 relative inline-block">
                                            <img src="{{ asset('storage/' . $booking->vehicle->images->first()->image_path) }}" 
                                                 alt="{{ $booking->vehicle->name }}" 
                                                 class="w-20 h-14 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                        </div>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Assigned soon</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Service</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ optional($booking->serviceType)->name }}</span>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Route</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white text-right max-w-xs">{{ $booking->pickupCity->name }} &rarr; {{ $booking->dropCity->name }}</span>
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">Our team will review your request and confirm your booking shortly. We have sent an email with the details to {{ $booking->customer_email }}.</p>

                <a href="{{ route('home') }}" class="inline-block px-8 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-bold rounded-xl hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors shadow-lg">
                    Return to Home
                </a>
            </div>

        </div>
    </div>
</x-public-layout>
