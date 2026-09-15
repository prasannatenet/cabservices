<x-associate-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Driver Details') }}: D-{{ $driver->id }}
            </h2>
            <a href="{{ route('associate.drivers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Drivers</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Personal Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Name</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $driver->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Phone</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $driver->phone }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $driver->email ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Current City</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ optional($driver->currentCity)->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Experience</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $driver->experience_years ?? 'N/A' }} Years</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                                <p class="font-medium">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border
                                        @if($driver->status == 'Available') bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                                        @elseif($driver->status == 'Unavailable') bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/50
                                        @elseif($driver->status == 'Inactive') bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50
                                        @else bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50 @endif">
                                        {{ $driver->status }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">License &amp; Documents</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">License Number</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $driver->license_number }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">License Expiry</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $driver->license_expiry?->format('d M, Y') ?? 'N/A' }}</p>
                            </div>
                            @if($driver->license_document)
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">License Document</p>
                                    <a href="{{ asset('storage/'.$driver->license_document) }}" target="_blank" class="font-medium text-indigo-600 hover:underline">View Document</a>
                                </div>
                            @endif
                            @if($driver->profile_photo)
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Profile Photo</p>
                                    <img src="{{ asset('storage/'.$driver->profile_photo) }}" alt="{{ $driver->name }}" class="h-20 w-20 object-cover rounded mt-1">
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Preferred Cities</h3>
                        @if($driver->preferredCities->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach($driver->preferredCities as $city)
                                    <span class="px-3 py-1 text-xs font-bold rounded-full border bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50">{{ $city->name }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">The driver has not selected any cities yet.</p>
                        @endif
                    </div>
                </div>

                <div class="space-y-8">
                    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">Login Access</h3>
                        @if($driver->user)
                            <p class="font-medium text-green-600 dark:text-green-400">Enabled</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Login ID: <span class="font-semibold text-gray-900 dark:text-white">{{ $driver->user->username }}</span></p>
                        @else
                            <p class="font-medium text-gray-500 dark:text-gray-400">Not created yet</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add a login id and password via Edit Driver to give dashboard access.</p>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-[#161615] shadow-sm rounded-xl border border-gray-100 dark:border-gray-800/60 p-6">
                        <div class="flex flex-col gap-3">
                            <a href="{{ route('associate.drivers.edit', $driver) }}" class="w-full text-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                Edit Driver
                            </a>
                            <a href="{{ route('associate.drivers.leaves', $driver) }}" class="w-full text-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                Manage Leaves
                            </a>
                            <a href="{{ route('associate.drivers.index') }}" class="w-full text-center px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Back to Drivers
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-associate-layout>