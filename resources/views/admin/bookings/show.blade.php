<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Booking Details: ') }} {{ $booking->booking_number }}
            </h2>
            <a href="{{ route('admin.bookings.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Bookings</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-xl border border-green-100 dark:border-green-900/30">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-xl border border-red-100 dark:border-red-900/30">
                    {{ session('error') }}
                </div>
            @endif

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
                
                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Customer Details -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
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
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $booking->customer_email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Trip Details -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Trip Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Date & Time</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($booking->pickup_date)->format('d M Y') }} at {{ $booking->pickup_time }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Service Type</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ optional($booking->serviceType)->name }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Pickup Location</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $booking->pickup_location }} ({{ optional($booking->pickupCity)->name }})</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Drop Location</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $booking->drop_location }} ({{ optional($booking->dropCity)->name }})</p>
                            </div>
                            @if($booking->vehicle)
                                <div class="md:col-span-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Selected Vehicle</p>
                                    <div class="flex items-center gap-4">
                                        @if($booking->vehicle->images->isNotEmpty())
                                            <img src="{{ asset('storage/' . $booking->vehicle->images->first()->image_path) }}" 
                                                 alt="{{ $booking->vehicle->name }}" 
                                                 class="w-24 h-16 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                        @endif
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $booking->vehicle->name }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $booking->vehicle->model }} &bull; {{ $booking->vehicle->vehicle_type }} &bull; {{ $booking->vehicle->seating_capacity }} seats</p>
                                            @if($booking->vehicle->images->count() > 1)
                                                <p class="text-xs text-gray-400 mt-1">{{ $booking->vehicle->images->count() }} images available</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

                <!-- Right Column: Management Form -->
                <div class="space-y-8">
                    
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Manage Booking</h3>
                        
                        <div class="mb-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Current Status</p>
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                                @if($booking->status == 'PENDING') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                @elseif($booking->status == 'CONFIRMED') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                @elseif($booking->status == 'CANCELLED') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @else bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 @endif">
                                {{ $booking->status }}
                            </span>
                        </div>

                        <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <div class="space-y-4">
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Update Status</label>
                                    <select name="status" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                        @foreach(\App\Enums\BookingStatus::cases() as $status)
                                            <option value="{{ $status->value }}" {{ $booking->status === $status ? 'selected' : '' }}>
                                                {{ $status->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign/Change Vehicle</label>
                                    <select name="vehicle_id" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                        <option value="">-- Select Vehicle --</option>
                                        @foreach($availableVehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}" {{ $booking->vehicle_id == $vehicle->id ? 'selected' : '' }}>
                                                {{ $vehicle->name }} ({{ $vehicle->vehicle_type }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign/Change Driver</label>
                                    <select name="driver_id" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                        <option value="">-- Select Driver --</option>
                                        @foreach($availableDrivers as $driver)
                                            <option value="{{ $driver->id }}" {{ $booking->driver_id == $driver->id ? 'selected' : '' }}>
                                                {{ $driver->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Required to confirm booking.</p>
                                </div>

                                <button type="submit" class="w-full mt-4 bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded-md transition-colors">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>
