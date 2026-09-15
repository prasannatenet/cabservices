@props([
    'action',
    'method' => 'POST',
    'associate' => null,
    'cities',
    'cancelUrl',
    'submitLabel' => 'Save Associate',
])

<form action="{{ $action }}" method="POST" class="space-y-6 max-w-3xl">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-input-label for="name" :value="__('Full Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $associate?->name)" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="login_id" :value="__('Login ID (Username)')" />
            <x-text-input id="login_id" class="block mt-1 w-full" type="text" name="login_id" :value="old('login_id', $associate?->username)" required />
            <p class="text-xs text-gray-500 mt-1">Letters, numbers, dashes and underscores only. He logs in with this ID or his email.</p>
            <x-input-error :messages="$errors->get('login_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email Address (Optional)')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $associate?->email)" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select id="status" name="status" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" required>
                <option value="Active" @selected(old('status', $associate?->status ?? 'Active') === 'Active')>Active</option>
                <option value="Inactive" @selected(old('status', $associate?->status) === 'Inactive')>Inactive</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">An inactive associate cannot log into his portal at all.</p>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" autocomplete="new-password" :required="! $associate" />
            <p class="text-xs text-gray-500 mt-1">
                @if($associate)
                    Leave blank to keep the current password.
                @else
                    Minimum 8 characters.
                @endif
            </p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label :value="__('Cities He Manages')" />
        <p class="text-xs text-gray-500 mt-1 mb-3">He becomes the admin of the ticked cities only — fleet, drivers, services and bookings of those cities. At least one city is required.</p>
        @php
            $checkedCityIds = old('city_ids', $associate !== null ? $associate->assignedCities->pluck('id')->toArray() : []);
            $checkedCityIds = array_map(fn ($id) => (int) $id, (array) $checkedCityIds);
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse($cities as $city)
                <label class="flex items-center gap-2 px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-md cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                    <input type="checkbox" name="city_ids[]" value="{{ $city->id }}" @checked(in_array((int) $city->id, $checkedCityIds, true)) class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $city->name }}</span>
                </label>
            @empty
                <p class="text-sm text-gray-500">No cities found. Please create a city first.</p>
            @endforelse
        </div>
        <x-input-error :messages="$errors->get('city_ids')" class="mt-2" />
        <x-input-error :messages="$errors->get('city_ids.*')" class="mt-2" />
    </div>

    <div class="flex items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-800/60">
        <a href="{{ $cancelUrl }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</a>
        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            {{ $submitLabel }}
        </button>
    </div>
</form>