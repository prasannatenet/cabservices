@props([
    'action',
    'method' => 'POST',
    'serviceType' => null,
    'cities' => collect(),
    'cancelUrl',
    'submitLabel' => 'Save Service',
    'cityRequired' => false,
])

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-2xl">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $serviceType?->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="display_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Display Order</label>
        <input type="number" name="display_order" id="display_order" value="{{ old('display_order', $serviceType?->display_order ?? 0) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        @error('display_order') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
        <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="Active" {{ old('status', $serviceType?->status) == 'Active' ? 'selected' : '' }}>Active</option>
            <option value="Inactive" {{ old('status', $serviceType?->status) == 'Inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="city_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            City
            @if(! $cityRequired)
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(leave empty for a global service available in every city)</span>
            @endif
        </label>
        <select name="city_id" id="city_id" @required($cityRequired) class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">{{ $cityRequired ? 'Select a city' : 'Global (all cities)' }}</option>
            @foreach($cities as $city)
                <option value="{{ $city->id }}" {{ old('city_id', $serviceType?->city_id) == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
            @endforeach
        </select>
        @error('city_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Image</label>
        @if($serviceType?->image)
            <div class="mb-2">
                <img src="{{ asset('storage/'.$serviceType->image) }}" alt="Current Image" class="h-20 w-20 object-cover rounded">
            </div>
        @endif
        <input type="file" name="image" id="image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-300">
        @if($serviceType)
            <p class="text-xs text-gray-500 mt-1">Leave blank to keep current image</p>
        @endif
        @error('image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
        <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('description', $serviceType?->description) }}</textarea>
        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex justify-end gap-4">
        <a href="{{ $cancelUrl }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">{{ $submitLabel }}</button>
    </div>
</form>