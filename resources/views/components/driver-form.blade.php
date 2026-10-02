@props([
    'action',
    'method' => 'POST',
    'driver' => null,
    'cities',
    'cancelUrl',
    'submitLabel' => 'Save Driver',
    // Only the admin passes this. A driver an associate creates is his by
    // definition, so his own form has no owner to pick.
    'associates' => null,
    'adminOwner' => 'none',
])

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-2xl">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    {{-- Keeps the salary input for the unselected driver type from flashing
         on screen before Alpine applies x-show. --}}
    <style>[x-cloak] { display: none !important; }</style>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-input-label for="name" :value="__('Full Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $driver?->name)" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone Number')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $driver?->phone)" required />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="alternate_phone" :value="__('Alternate Mobile Number (Optional)')" />
            <x-text-input id="alternate_phone" class="block mt-1 w-full" type="text" name="alternate_phone" :value="old('alternate_phone', $driver?->alternate_phone)" />
            <p class="text-xs text-gray-500 mt-1">A second number the driver can always be reached on.</p>
            <x-input-error :messages="$errors->get('alternate_phone')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email Address (Optional)')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $driver?->email)" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="license_number" :value="__('License Number')" />
            <x-text-input id="license_number" class="block mt-1 w-full" type="text" name="license_number" :value="old('license_number', $driver?->license_number)" required />
            <x-input-error :messages="$errors->get('license_number')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="license_expiry" :value="__('License Expiry Date')" />
            <x-text-input id="license_expiry" class="block mt-1 w-full" type="date" name="license_expiry" :value="old('license_expiry', $driver?->license_expiry?->format('Y-m-d'))" required />
            <x-input-error :messages="$errors->get('license_expiry')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="experience_years" :value="__('Experience (Years)')" />
            <x-text-input id="experience_years" class="block mt-1 w-full" type="number" name="experience_years" :value="old('experience_years', $driver?->experience_years)" min="0" />
            <x-input-error :messages="$errors->get('experience_years')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="current_city_id" :value="__('Current City')" />
            <select id="current_city_id" name="current_city_id" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" required>
                <option value="">Select a city</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}" @selected(old('current_city_id', $driver?->current_city_id) == $city->id)>{{ $city->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('current_city_id')" class="mt-2" />
        </div>

        @if($associates !== null)
            <div>
                <x-input-label for="associate_id" :value="__('Associate (Owner)')" />
                <select id="associate_id" name="associate_id" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm">
                    <option value="{{ $adminOwner }}" @selected(old('associate_id', $driver?->associate_id ?? $adminOwner) == $adminOwner)>Admin Created</option>
                    @foreach($associates as $associate)
                        <option value="{{ $associate->id }}" @selected(old('associate_id', $driver?->associate_id) == $associate->id)>{{ $associate->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Whose driver this is. The city he is based in does not decide this.</p>
                <x-input-error :messages="$errors->get('associate_id')" class="mt-2" />
            </div>
        @endif

        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select id="status" name="status" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" required>
                <option value="Available" @selected(old('status', $driver?->status) === 'Available')>Available</option>
                <option value="Unavailable" @selected(old('status', $driver?->status) === 'Unavailable')>Unavailable</option>
                <option value="On Trip" @selected(old('status', $driver?->status) === 'On Trip')>On Trip</option>
                <option value="On Leave" @selected(old('status', $driver?->status) === 'On Leave')>On Leave</option>
                <option value="Inactive" @selected(old('status', $driver?->status) === 'Inactive')>Inactive</option>
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>
    </div>

    <!-- Employment (Driver Type & Salary) -->
    <div class="pt-6 border-t border-gray-100 dark:border-gray-800/60 space-y-6" x-data="{ driverType: @js(old('driver_type', $driver?->driver_type?->value ?? \App\Enums\DriverType::Permanent->value)) }">
        <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white font-display">Employment</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Is the driver on a permanent monthly salary, or paid per day he works?</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <x-input-label for="driver_type" :value="__('Driver Type')" />
                <select id="driver_type" name="driver_type" x-model="driverType" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" required>
                    @foreach(\App\Enums\DriverType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->value }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('driver_type')" class="mt-2" />
            </div>

            <div x-cloak x-show="driverType === @js(\App\Enums\DriverType::Permanent->value)">
                <x-input-label for="monthly_salary" :value="__('Monthly Salary')" />
                <x-text-input id="monthly_salary" class="block mt-1 w-full" type="number" name="monthly_salary" min="0" step="0.01" placeholder="15000" :value="old('monthly_salary', $driver?->monthly_salary)" />
                <p class="text-xs text-gray-500 mt-1">Fixed amount paid every month.</p>
                <x-input-error :messages="$errors->get('monthly_salary')" class="mt-2" />
            </div>

            <div x-cloak x-show="driverType === @js(\App\Enums\DriverType::PerDay->value)">
                <x-input-label for="per_day_salary" :value="__('Per Day Salary')" />
                <x-text-input id="per_day_salary" class="block mt-1 w-full" type="number" name="per_day_salary" min="0" step="0.01" placeholder="800" :value="old('per_day_salary', $driver?->per_day_salary)" />
                <p class="text-xs text-gray-500 mt-1">Fixed amount paid for each day he works.</p>
                <x-input-error :messages="$errors->get('per_day_salary')" class="mt-2" />
            </div>
        </div>
    </div>

    <!-- Aadhaar Details (Identity Proof) -->
    <div class="pt-6 border-t border-gray-100 dark:border-gray-800/60 space-y-6">
        <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white font-display">Aadhaar Details</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Identity proof of the driver, collected at onboarding.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <x-input-label for="aadhaar_number" :value="__('Aadhaar Number')" />
                <x-text-input id="aadhaar_number" class="block mt-1 w-full" type="text" name="aadhaar_number" inputmode="numeric" maxlength="14" placeholder="1234 5678 9012" :value="old('aadhaar_number', $driver?->formatted_aadhaar_number)" />
                <p class="text-xs text-gray-500 mt-1">12 digit Aadhaar number. Must be unique across drivers.</p>
                <x-input-error :messages="$errors->get('aadhaar_number')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="aadhaar_photo" :value="__('Aadhaar Photo')" />
                @if($driver?->aadhaar_photo)
                    <div class="mb-2">
                        <img src="{{ $driver->aadhaar_photo_url }}" alt="Current Aadhaar Photo" class="h-20 w-32 object-cover rounded border border-gray-200 dark:border-gray-700">
                    </div>
                @endif
                <input id="aadhaar_photo" type="file" name="aadhaar_photo" accept="image/*" class="block mt-1 w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-300 dark:file:bg-gray-800 dark:file:text-gray-300" />
                @if($driver)
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep current photo</p>
                @endif
                <p class="text-xs text-gray-500 mt-1">Max size: 2MB</p>
                <x-input-error :messages="$errors->get('aadhaar_photo')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="permanent_address" :value="__('Permanent Address')" />
            <textarea id="permanent_address" name="permanent_address" rows="3" placeholder="House / Street / Area, City, State, PIN" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm">{{ old('permanent_address', $driver?->permanent_address) }}</textarea>
            <x-input-error :messages="$errors->get('permanent_address')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="current_address" :value="__('Current Address')" />
            <textarea id="current_address" name="current_address" rows="3" placeholder="Where the driver currently stays" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm">{{ old('current_address', $driver?->current_address) }}</textarea>
            <x-input-error :messages="$errors->get('current_address')" class="mt-2" />
        </div>
    </div>

    <!-- Login Access (Driver Dashboard) -->
    <div class="pt-6 border-t border-gray-100 dark:border-gray-800/60 space-y-6">
        <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white font-display">Login Access</h3>
            @if($driver?->user)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">This driver has dashboard access with login id <span class="font-semibold">{{ $driver->user->username }}</span>.</p>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Give the driver a login id and password so they can access their own dashboard.</p>
            @endif
        </div>

        <div>
            <x-input-label for="login_id" :value="__('Login ID (Username)')" />
            <x-text-input id="login_id" class="block mt-1 w-full" type="text" name="login_id" :value="old('login_id', $driver?->user?->username)" />
            <p class="text-xs text-gray-500 mt-1">
                @if($driver)
                    Letters, numbers, dashes and underscores only. Leave blank to keep unchanged.
                @else
                    Letters, numbers, dashes and underscores only. Leave blank to skip dashboard access.
                @endif
            </p>
            <x-input-error :messages="$errors->get('login_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="login_password" :value="__('Login Password')" />
            <x-text-input id="login_password" class="block mt-1 w-full" type="password" name="login_password" autocomplete="new-password" />
            <p class="text-xs text-gray-500 mt-1">
                @if($driver?->user)
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
        @if($driver?->profile_photo)
            <div class="mb-2">
                <img src="{{ asset('storage/'.$driver->profile_photo) }}" alt="Current Photo" class="h-20 w-20 object-cover rounded">
            </div>
        @endif
        <input id="profile_photo" type="file" name="profile_photo" class="block mt-1 w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-300 dark:file:bg-gray-800 dark:file:text-gray-300" />
        @if($driver)
            <p class="text-xs text-gray-500 mt-1">Leave blank to keep current photo</p>
        @endif
        <x-input-error :messages="$errors->get('profile_photo')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="license_document" :value="__('License Document (PDF/Image)')" />
        @if($driver?->license_document)
            <div class="mb-2">
                <a href="{{ asset('storage/'.$driver->license_document) }}" target="_blank" class="text-indigo-600 hover:underline">View Current Document</a>
            </div>
        @endif
        <input id="license_document" type="file" name="license_document" class="block mt-1 w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-300 dark:file:bg-gray-800 dark:file:text-gray-300" />
        @if($driver)
            <p class="text-xs text-gray-500 mt-1">Leave blank to keep current document</p>
        @endif
        <x-input-error :messages="$errors->get('license_document')" class="mt-2" />
    </div>

    <div class="flex items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-800/60">
        <a href="{{ $cancelUrl }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</a>
        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            {{ $submitLabel }}
        </button>
    </div>
</form>