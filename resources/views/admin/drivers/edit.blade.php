<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Driver') }}: {{ $driver->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <form action="{{ route('admin.drivers.update', $driver) }}" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-2xl">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Full Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $driver->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="phone" :value="__('Phone Number')" />
                                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $driver->phone)" required />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="email" :value="__('Email Address (Optional)')" />
                                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $driver->email)" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="license_number" :value="__('License Number')" />
                                <x-text-input id="license_number" class="block mt-1 w-full" type="text" name="license_number" :value="old('license_number', $driver->license_number)" required />
                                <x-input-error :messages="$errors->get('license_number')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="license_expiry" :value="__('License Expiry Date')" />
                                <x-text-input id="license_expiry" class="block mt-1 w-full" type="date" name="license_expiry" :value="old('license_expiry', $driver->license_expiry)" required />
                                <x-input-error :messages="$errors->get('license_expiry')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="experience_years" :value="__('Experience (Years)')" />
                                <x-text-input id="experience_years" class="block mt-1 w-full" type="number" name="experience_years" :value="old('experience_years', $driver->experience_years)" min="0" />
                                <x-input-error :messages="$errors->get('experience_years')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="current_city_id" :value="__('Current City')" />
                                <select id="current_city_id" name="current_city_id" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" required>
                                    <option value="">Select a city</option>
                                    @foreach($cities as $city)
                                        <option value="{{ $city->id }}" @selected(old('current_city_id', $driver->current_city_id) == $city->id)>{{ $city->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('current_city_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" required>
                                    <option value="Available" @selected(old('status', $driver->status) == 'Available')>Available</option>
                                    <option value="Unavailable" @selected(old('status', $driver->status) == 'Unavailable')>Unavailable</option>
                                    <option value="On Trip" @selected(old('status', $driver->status) == 'On Trip')>On Trip</option>
                                    <option value="On Leave" @selected(old('status', $driver->status) == 'On Leave')>On Leave</option>
                                    <option value="Inactive" @selected(old('status', $driver->status) == 'Inactive')>Inactive</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Login Access (Driver Dashboard) -->
                        <div class="pt-6 border-t border-gray-100 dark:border-gray-800/60 space-y-6">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white font-display">Login Access</h3>
                                @if($driver->user)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">This driver has dashboard access with login id <span class="font-semibold">{{ $driver->user->username }}</span>.</p>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Give the driver a login id and password so they can access their own dashboard.</p>
                                @endif
                            </div>

                            <div>
                                <x-input-label for="login_id" :value="__('Login ID (Username)')" />
                                <x-text-input id="login_id" class="block mt-1 w-full" type="text" name="login_id" :value="old('login_id', $driver->user?->username)" />
                                <p class="text-xs text-gray-500 mt-1">Letters, numbers, dashes and underscores only. Leave blank to keep unchanged.</p>
                                <x-input-error :messages="$errors->get('login_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="login_password" :value="__('Login Password')" />
                                <x-text-input id="login_password" class="block mt-1 w-full" type="password" name="login_password" autocomplete="new-password" />
                                <p class="text-xs text-gray-500 mt-1">
                                    @if($driver->user)
                                        Leave blank to keep the current password.
                                    @else
                                        Required when creating a new login account. Minimum 8 characters.
                                    @endif
                                </p>
                                <x-input-error :messages="$errors->get('login_password')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="profile_photo" :value="__('Profile Photo')" />
                            @if($driver->profile_photo)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/'.$driver->profile_photo) }}" alt="Current Photo" class="h-20 w-20 object-cover rounded">
                                </div>
                            @endif
                            <input id="profile_photo" type="file" name="profile_photo" class="block mt-1 w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-800 dark:file:text-gray-300" />
                            <p class="text-xs text-gray-500 mt-1">Leave blank to keep current photo</p>
                            <x-input-error :messages="$errors->get('profile_photo')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="license_document" :value="__('License Document (PDF/Image)')" />
                            @if($driver->license_document)
                                <div class="mb-2">
                                    <a href="{{ asset('storage/'.$driver->license_document) }}" target="_blank" class="text-indigo-600 hover:underline">View Current Document</a>
                                </div>
                            @endif
                            <input id="license_document" type="file" name="license_document" class="block mt-1 w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-800 dark:file:text-gray-300" />
                            <p class="text-xs text-gray-500 mt-1">Leave blank to keep current document</p>
                            <x-input-error :messages="$errors->get('license_document')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-800/60 mt-6">
                            <a href="{{ route('admin.drivers.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</a>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                                Update Driver
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
