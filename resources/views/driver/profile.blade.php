<x-driver-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('My Profile') }}
            </h2>
            <a href="{{ route('driver.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="space-y-6">

        <!-- Identity summary -->
        <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
            <div class="p-6 flex flex-col sm:flex-row sm:items-center gap-5">
                @if ($driver->profile_photo)
                    <img src="{{ asset('storage/'.$driver->profile_photo) }}" alt="{{ $driver->name }}" class="h-24 w-24 rounded-2xl object-cover border border-gray-200 dark:border-gray-700">
                @else
                    <div class="h-24 w-24 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-3xl font-bold text-gray-400">
                        {{ substr($driver->name, 0, 1) }}
                    </div>
                @endif

                <div class="min-w-0">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white font-display">{{ $driver->name }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $driver->phone }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border
                            @if($driver->status == 'Available') bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50
                            @elseif($driver->status == 'Unavailable') bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/50
                            @elseif($driver->status == 'Inactive') bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50
                            @else bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50 @endif">
                            {{ $driver->status }}
                        </span>
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-medium rounded-full border border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300">
                            {{ optional($driver->currentCity)->name ?? 'No city assigned' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>


        <!-- Editable details -->
        <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-800/60">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Contact &amp; Address</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Keep your contact details up to date so dispatch can always reach you.</p>
            </div>

            <form action="{{ route('driver.profile.update') }}" method="POST" enctype="multipart/form-data" class="p-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="name" :value="__('Full Name')" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $driver->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="phone" :value="__('Phone Number')" />
                        <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $driver->phone)" required />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="whatsapp" :value="__('WhatsApp Number')" />
                        <x-text-input id="whatsapp" class="block mt-1 w-full" type="text" name="whatsapp" :value="old('whatsapp', $driver->whatsapp)" />
                        <x-input-error :messages="$errors->get('whatsapp')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="alternate_phone" :value="__('Alternate Mobile Number')" />
                        <x-text-input id="alternate_phone" class="block mt-1 w-full" type="text" name="alternate_phone" :value="old('alternate_phone', $driver->alternate_phone)" />
                        <p class="text-xs text-gray-500 mt-1">A second number you can always be reached on.</p>
                        <x-input-error :messages="$errors->get('alternate_phone')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email Address')" />
                        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $driver->email)" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="experience_years" :value="__('Experience (Years)')" />
                        <x-text-input id="experience_years" class="block mt-1 w-full" type="number" name="experience_years" :value="old('experience_years', $driver->experience_years)" min="0" />
                        <x-input-error :messages="$errors->get('experience_years')" class="mt-2" />
                    </div>
                </div>


                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
                    <div>
                        <x-input-label for="permanent_address" :value="__('Permanent Address')" />
                        <textarea id="permanent_address" name="permanent_address" rows="3" placeholder="House / Street / Area, City, State, PIN" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm">{{ old('permanent_address', $driver->permanent_address) }}</textarea>
                        <x-input-error :messages="$errors->get('permanent_address')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="current_address" :value="__('Current Address')" />
                        <textarea id="current_address" name="current_address" rows="3" placeholder="Where you currently stay" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm">{{ old('current_address', $driver->current_address) }}</textarea>
                        <x-input-error :messages="$errors->get('current_address')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6">
                    <x-input-label for="profile_photo" :value="__('Profile Photo')" />
                    @if ($driver->profile_photo)
                        <div class="mb-2">
                            <img src="{{ asset('storage/'.$driver->profile_photo) }}" alt="Current Photo" class="h-20 w-20 object-cover rounded border border-gray-200 dark:border-gray-700">
                        </div>
                    @endif
                    <input id="profile_photo" type="file" name="profile_photo" accept="image/*" class="block mt-1 w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:text-gray-300 dark:file:bg-gray-800 dark:file:text-gray-300" />
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep your current photo. Max size: 2MB</p>
                    <x-input-error :messages="$errors->get('profile_photo')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-4 pt-6 mt-6 border-t border-gray-100 dark:border-gray-800/60">
                    <a href="{{ route('driver.dashboard') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</a>
                    <button type="submit" class="px-5 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                        Save Profile
                    </button>
                </div>
            </form>
        </div>


        <!-- Read-only identity documents (managed by the admin) -->
        <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-800/60 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white font-display">Aadhaar &amp; Licence</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Verified by the admin. These details are read-only for you.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Locked
                </span>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aadhaar Number</p>
                    <p class="font-semibold text-gray-900 dark:text-white tracking-wide mt-0.5">
                        {{ $driver->formatted_aadhaar_number ?? 'Not recorded' }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aadhaar Photo</p>
                    @if ($driver->aadhaar_photo)
                        <a href="{{ $driver->aadhaar_photo_url }}" target="_blank" class="inline-block mt-1">
                            <img src="{{ $driver->aadhaar_photo_url }}" alt="Aadhaar Photo" class="h-28 w-44 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                        </a>
                    @else
                        <p class="font-medium text-gray-500 dark:text-gray-400 mt-0.5">Not uploaded</p>
                    @endif
                </div>

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Licence Number</p>
                    <p class="font-semibold text-gray-900 dark:text-white mt-0.5">{{ $driver->license_number }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Licence Expiry</p>
                    <p class="font-semibold text-gray-900 dark:text-white mt-0.5">
                        {{ $driver->license_expiry?->format('d M, Y') ?? 'N/A' }}
                    </p>
                </div>
            </div>

            <p class="px-6 pb-6 text-xs text-gray-500 dark:text-gray-400">
                Need a correction in these documents? Please contact the admin.
            </p>
        </div>
    </div>
</x-driver-layout>