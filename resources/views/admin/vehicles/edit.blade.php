<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Vehicle') }}: V-{{ $vehicle->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#161615] shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <form action="{{ route('admin.vehicles.update', $vehicle) }}" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-2xl">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $vehicle->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="model" :value="__('Model')" />
                                <x-text-input id="model" class="block mt-1 w-full" type="text" name="model" :value="old('model', $vehicle->model)" required />
                                <x-input-error :messages="$errors->get('model')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="vehicle_category_id" :value="__('Category')" />
                                <select id="vehicle_category_id" name="vehicle_category_id" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('vehicle_category_id', $vehicle->vehicle_category_id) == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('vehicle_category_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="registration_number" :value="__('Registration Number')" />
                                <x-text-input id="registration_number" class="block mt-1 w-full" type="text" name="registration_number" :value="old('registration_number', $vehicle->registration_number)" required />
                                <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="seating_capacity" :value="__('Seating Capacity')" />
                                <x-text-input id="seating_capacity" class="block mt-1 w-full" type="number" name="seating_capacity" :value="old('seating_capacity', $vehicle->seating_capacity)" required min="1" />
                                <x-input-error :messages="$errors->get('seating_capacity')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="city_id" :value="__('Assigned City')" />
                                <select id="city_id" name="city_id" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" required>
                                    <option value="">Select a city</option>
                                    @foreach($cities as $city)
                                        <option value="{{ $city->id }}" @selected(old('city_id', $vehicle->city_id) == $city->id)>{{ $city->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('city_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="operating_city_id" :value="__('Operating City')" />
                                <select id="operating_city_id" name="operating_city_id" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm">
                                    <option value="">Select Operating City (Optional)</option>
                                    @foreach($cities as $city)
                                        <option value="{{ $city->id }}" @selected(old('operating_city_id', $vehicle->operating_city_id) == $city->id)>{{ $city->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('operating_city_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="fuel_type" :value="__('Fuel Type')" />
                                <x-text-input id="fuel_type" class="block mt-1 w-full" type="text" name="fuel_type" :value="old('fuel_type', $vehicle->fuel_type)" />
                                <x-input-error :messages="$errors->get('fuel_type')" class="mt-2" />
                            </div>

                            <div>
                                <label class="inline-flex items-center mt-8">
                                    <input type="hidden" name="has_ac" value="0">
                                    <input id="has_ac" type="checkbox" name="has_ac" value="1" @checked(old('has_ac', $vehicle->has_ac)) class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:focus:ring-primary-600">
                                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Has AC') }}</span>
                                </label>
                                <x-input-error :messages="$errors->get('has_ac')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="luggage_capacity" :value="__('Luggage Capacity (Bags)')" />
                                <x-text-input id="luggage_capacity" class="block mt-1 w-full" type="number" name="luggage_capacity" :value="old('luggage_capacity', $vehicle->luggage_capacity)" min="0" />
                                <x-input-error :messages="$errors->get('luggage_capacity')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="price_per_km" :value="__('Price per KM (₹)')" />
                                <x-text-input id="price_per_km" class="block mt-1 w-full" type="number" name="price_per_km" :value="old('price_per_km', $vehicle->price_per_km)" step="0.01" min="0" />
                                <x-input-error :messages="$errors->get('price_per_km')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="price_per_day" :value="__('Price per Day (₹)')" />
                                <x-text-input id="price_per_day" class="block mt-1 w-full" type="number" name="price_per_day" :value="old('price_per_day', $vehicle->price_per_day)" step="0.01" min="0" />
                                <x-input-error :messages="$errors->get('price_per_day')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="fixed_km_per_day" :value="__('Fixed KM per Day')" />
                                <x-text-input id="fixed_km_per_day" class="block mt-1 w-full" type="number" name="fixed_km_per_day" :value="old('fixed_km_per_day', $vehicle->fixed_km_per_day)" min="0" />
                                <x-input-error :messages="$errors->get('fixed_km_per_day')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="last_service_date" :value="__('Last Service Date')" />
                                <x-text-input id="last_service_date" class="block mt-1 w-full" type="date" name="last_service_date" :value="old('last_service_date', $vehicle->last_service_date?->format('Y-m-d'))" />
                                <x-input-error :messages="$errors->get('last_service_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded-md shadow-sm" required>
                                    <option value="Available" @selected(old('status', $vehicle->status) == 'Available')>Available</option>
                                    <option value="On Trip" @selected(old('status', $vehicle->status) == 'On Trip')>On Trip</option>
                                    <option value="Maintenance" @selected(old('status', $vehicle->status) == 'Maintenance')>Maintenance</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>

                        <div class="col-span-full">
                            <x-input-label value="Current Images" />
                            @if($vehicle->images->count() > 0)
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2 mb-4">
                                    @foreach($vehicle->images as $img)
                                        <div class="relative group rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                                            <img src="{{ asset('storage/'.$img->image_path) }}" alt="Vehicle Image" class="h-32 w-full object-cover">
                                            <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button type="button" onclick="event.preventDefault(); document.getElementById('delete-image-{{ $img->id }}').submit();" class="p-2 bg-red-600 text-white rounded-full hover:bg-red-700">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 mb-4">No images uploaded for this vehicle.</p>
                            @endif
                            
                            <x-input-label for="images" value="Add New Images (Multiple Allowed)" />
                            <input type="file" name="images[]" id="images" multiple accept="image/*" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/20 dark:file:text-primary-400 transition-colors cursor-pointer" />
                            <x-input-error :messages="$errors->get('images')" class="mt-2" />
                            <x-input-error :messages="$errors->get('images.*')" class="mt-2" />
                            <p class="text-xs text-gray-500 mt-2">Max size: 2MB per image</p>
                        </div>

                        <div class="col-span-full">
                            <x-input-label value="Insurance Photos" />
                            @if(count($vehicle->insurance_photo ?? []) > 0)
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2 mb-4">
                                    @foreach($vehicle->insurance_photo_urls as $index => $url)
                                        <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                                            <img src="{{ $url }}" alt="Insurance Photo" class="h-32 w-full object-cover">
                                            <label class="flex items-center gap-2 p-2 text-xs text-gray-600 dark:text-gray-400">
                                                <input type="checkbox" name="remove_insurance[]" value="{{ $vehicle->insurance_photo[$index] }}" class="rounded border-gray-300 dark:border-gray-700 text-red-600 shadow-sm focus:ring-red-500">
                                                Remove
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 mb-4">No insurance photos uploaded.</p>
                            @endif

                            <x-input-label for="insurance_photos" value="Add Insurance Photos (Multiple Allowed)" />
                            <input type="file" name="insurance_photos[]" id="insurance_photos" multiple accept="image/*" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/20 dark:file:text-primary-400 transition-colors cursor-pointer" />
                            <x-input-error :messages="$errors->get('insurance_photos')" class="mt-2" />
                            <x-input-error :messages="$errors->get('insurance_photos.*')" class="mt-2" />
                            <p class="text-xs text-gray-500 mt-2">Max size: 2MB per image</p>
                        </div>

                        <div class="col-span-full">
                            <x-input-label value="RC Photos" />
                            @if(count($vehicle->rc_photo ?? []) > 0)
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2 mb-4">
                                    @foreach($vehicle->rc_photo_urls as $index => $url)
                                        <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                                            <img src="{{ $url }}" alt="RC Photo" class="h-32 w-full object-cover">
                                            <label class="flex items-center gap-2 p-2 text-xs text-gray-600 dark:text-gray-400">
                                                <input type="checkbox" name="remove_rc[]" value="{{ $vehicle->rc_photo[$index] }}" class="rounded border-gray-300 dark:border-gray-700 text-red-600 shadow-sm focus:ring-red-500">
                                                Remove
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 mb-4">No RC photos uploaded.</p>
                            @endif

                            <x-input-label for="rc_photos" value="Add RC Photos (Multiple Allowed)" />
                            <input type="file" name="rc_photos[]" id="rc_photos" multiple accept="image/*" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/20 dark:file:text-primary-400 transition-colors cursor-pointer" />
                            <x-input-error :messages="$errors->get('rc_photos')" class="mt-2" />
                            <x-input-error :messages="$errors->get('rc_photos.*')" class="mt-2" />
                            <p class="text-xs text-gray-500 mt-2">Max size: 2MB per image</p>
                        </div>

                        <div class="flex items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-800/60 mt-6">
                            <a href="{{ route('admin.vehicles.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</a>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                                Update Vehicle
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    
    @foreach($vehicle->images as $img)
        <form id="delete-image-{{ $img->id }}" action="{{ route('admin.vehicles.images.destroy', $img->id) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
</x-app-layout>
