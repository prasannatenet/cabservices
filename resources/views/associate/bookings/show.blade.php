<x-associate-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Booking Details') }}: {{ $booking->booking_number }}
            </h2>
            <a href="{{ route('associate.bookings.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Bookings</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-xl border border-red-100 dark:border-red-900/30">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Customer Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Name</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $booking->customer_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Phone</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $booking->customer_phone }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $booking->customer_email ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">WhatsApp</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $booking->customer_whatsapp ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Trip Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Pickup City</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ optional($booking->pickupCity)->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Pickup Location</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $booking->pickup_location }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Drop City</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ optional($booking->dropCity)->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Drop Location</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $booking->drop_location }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Pickup</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($booking->pickup_date)->format('d M, Y') }} at {{ $booking->pickup_time }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Passengers</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $booking->passengers }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Service Type</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ optional($booking->serviceType)->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Current Status</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $booking->status->value }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- NEXT --}}
                </div>
            </div>
        </div>
    </div>
</x-associate-layout>