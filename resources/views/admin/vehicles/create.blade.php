<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New Vehicle') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
                <form action="{{ route('admin.vehicles.store') }}" method="POST" class="p-6 space-y-6">
                    @csrf
                    
                    <div>
                        <x-input-label for="name" :value="__('Vehicle Name')" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="model" :value="__('Model (e.g. 2023, 2024)')" />
                        <x-text-input id="model" class="block mt-1 w-full" type="text" name="model" :value="old('model')" required />
                        <x-input-error :messages="$errors->get('model')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="vehicle_type" :value="__('Vehicle Type (e.g. Sedan, SUV)')" />
                        <x-text-input id="vehicle_type" class="block mt-1 w-full" type="text" name="vehicle_type" :value="old('vehicle_type')" required />
                        <x-input-error :messages="$errors->get('vehicle_type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="registration_number" :value="__('Registration Number')" />
                        <x-text-input id="registration_number" class="block mt-1 w-full" type="text" name="registration_number" :value="old('registration_number')" required />
                        <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="seating_capacity" :value="__('Seating Capacity')" />
                        <x-text-input id="seating_capacity" class="block mt-1 w-full" type="number" name="seating_capacity" :value="old('seating_capacity', 4)" min="1" required />
                        <x-input-error :messages="$errors->get('seating_capacity')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="vehicle_category_id" :value="__('Category')" />
                        <select id="vehicle_category_id" name="vehicle_category_id" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('vehicle_category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('vehicle_category_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="city_id" :value="__('Assigned City')" />
                        <select id="city_id" name="city_id" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" required>
                            <option value="">Select a city</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" @selected(old('city_id') == $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('city_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="operating_city_id" :value="__('Operating City')" />
                        <select id="operating_city_id" name="operating_city_id" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm">
                            <option value="">Select Operating City (Optional)</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" @selected(old('operating_city_id') == $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('operating_city_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="fuel_type" :value="__('Fuel Type')" />
                        <x-text-input id="fuel_type" class="block mt-1 w-full" type="text" name="fuel_type" :value="old('fuel_type')" />
                        <x-input-error :messages="$errors->get('fuel_type')" class="mt-2" />
                    </div>

                    <div>
                        <label class="inline-flex items-center">
                            <input type="hidden" name="has_ac" value="0">
                            <input id="has_ac" type="checkbox" name="has_ac" value="1" @checked(old('has_ac')) class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:focus:ring-primary-600">
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Has AC') }}</span>
                        </label>
                        <x-input-error :messages="$errors->get('has_ac')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="luggage_capacity" :value="__('Luggage Capacity (Bags)')" />
                        <x-text-input id="luggage_capacity" class="block mt-1 w-full" type="number" name="luggage_capacity" :value="old('luggage_capacity')" min="0" />
                        <x-input-error :messages="$errors->get('luggage_capacity')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="images" value="Vehicle Images (Multiple Allowed)" />
                        <input type="file" name="images[]" id="images" multiple accept="image/*" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/20 dark:file:text-primary-400 transition-colors cursor-pointer" />
                        <x-input-error :messages="$errors->get('images')" class="mt-2" />
                        <x-input-error :messages="$errors->get('images.*')" class="mt-2" />
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">You can select multiple image files. Max size: 2MB per image.</p>
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" required>
                            <option value="Available" @selected(old('status') == 'Available')>Available</option>
                            <option value="Maintenance" @selected(old('status') == 'Maintenance')>Maintenance</option>
                            <option value="Inactive" @selected(old('status') == 'Inactive')>Inactive</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-4 gap-4">
                        <a href="{{ route('admin.vehicles.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                            Cancel
                        </a>
                        <x-primary-button>
                            {{ __('Save Vehicle') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
