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

                    {{-- Only this associate's own drivers and vehicles are offered:
                         the ride is his, so the admin's resources are not his to
                         reassign, and they are never listed here. --}}
                    @php $availableDrivers = $availableDrivers ?? collect(); @endphp

                    @if(session('success'))
                        <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-sm">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm">{{ session('error') }}</div>
                    @endif

                    {{-- A finished trip is a permanent record, so the form is replaced
                         by a notice rather than shown in a disabled state. --}}
                    @if($booking->isLocked())
                        <div class="rounded-lg border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-900/20 p-4">
                            <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Trip completed &mdash; read only</p>
                            <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-400">
                                This trip has ended, so its driver, vehicle and status can no longer be changed.
                            </p>
                            @if($booking->trip_ended_at)
                                <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-500">Ended {{ $booking->trip_ended_at->format('d M, Y \a\t h:i A') }}</p>
                            @endif
                        </div>
                    @else
                    <form action="{{ route('associate.bookings.update', $booking) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Update Status</label>
                                <select name="status" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                    @foreach(\App\Enums\BookingStatus::cases() as $status)
                                        <option value="{{ $status->value }}" {{ $booking->status === $status ? 'selected' : '' }}>{{ $status->name }}</option>
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
                                <p class="text-xs text-gray-500 mt-1">{{ $availableVehicles->count() }} of your vehicle(s) available.</p>
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
                                <p class="text-xs text-gray-500 mt-1">{{ $availableDrivers->count() }} of your driver(s) available{{ $booking->dropCity ? ' willing to go to '.$booking->dropCity->name : '' }}.</p>
                            </div>

                            <button type="submit" class="w-full mt-4 bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded-md transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-associate-layout>