<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $vehicles = Vehicle::with(['city', 'category'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('city_id'), fn ($query) => $query->where('city_id', $request->query('city_id')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('vehicle_category_id', $request->query('category_id')))
            ->paginate(15)
            ->withQueryString();

        return view('admin.vehicles.index', [
            'vehicles' => $vehicles,
            'cities' => City::orderBy('name')->get(),
            'categories' => VehicleCategory::orderBy('name')->get(),
            'statuses' => ['Available', 'On Trip', 'Maintenance', 'Inactive'],
        ]);
    }

    public function create()
    {
        $cities = City::all();
        $categories = VehicleCategory::all();

        return view('admin.vehicles.create', compact('cities', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedVehicleData($request);

        $validated['insurance_photo'] = $this->storeDocumentPhotos($request, 'insurance_photos');
        $validated['rc_photo'] = $this->storeDocumentPhotos($request, 'rc_photos');
        $validated = Arr::except($validated, ['insurance_photos', 'rc_photos']);

        $vehicle = Vehicle::create($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('vehicles', 'public');
                $vehicle->images()->create(['image_path' => $path]);
            }
        }

        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle created successfully.');
    }

    public function edit(Vehicle $vehicle)
    {
        $cities = City::all();
        $categories = VehicleCategory::all();
        $vehicle->load('images');

        return view('admin.vehicles.edit', compact('vehicle', 'cities', 'categories'));
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load(['city', 'operatingCity', 'category', 'images']);

        return view('admin.vehicles.show', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $this->validatedVehicleData($request, $vehicle);

        $validated['insurance_photo'] = $this->mergeDocumentPhotos(
            $vehicle->insurance_photo ?? [],
            (array) $request->input('remove_insurance', []),
            $this->storeDocumentPhotos($request, 'insurance_photos'),
        );
        $validated['rc_photo'] = $this->mergeDocumentPhotos(
            $vehicle->rc_photo ?? [],
            (array) $request->input('remove_rc', []),
            $this->storeDocumentPhotos($request, 'rc_photos'),
        );
        $validated = Arr::except($validated, ['insurance_photos', 'rc_photos', 'remove_insurance', 'remove_rc']);

        $vehicle->update($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('vehicles', 'public');
                $vehicle->images()->create(['image_path' => $path]);
            }
        }

        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        foreach ($vehicle->images as $img) {
            Storage::disk('public')->delete($img->image_path);
        }
        $vehicle->delete(); // Cascades to vehicle_images table due to DB constraint

        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle deleted successfully.');
    }

    public function destroyImage(VehicleImage $image)
    {
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return back()->with('success', 'Image deleted successfully.');
    }

    /**
     * Validate vehicle request data shared by store and update.
     *
     * @return array<string, mixed>
     */
    private function validatedVehicleData(Request $request, ?Vehicle $vehicle = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'vehicle_category_id' => 'required|exists:vehicle_categories,id',
            'registration_number' => 'required|string|unique:vehicles,registration_number'.($vehicle ? ','.$vehicle->id : ''),
            'seating_capacity' => 'required|integer|min:1',
            'city_id' => 'required|exists:cities,id',
            'operating_city_id' => 'nullable|exists:cities,id',
            'fuel_type' => 'nullable|string|max:50',
            'has_ac' => 'boolean',
            'luggage_capacity' => 'nullable|integer',
            'price_per_km' => 'nullable|numeric|min:0',
            'price_per_day' => 'nullable|numeric|min:0',
            'fixed_km_per_day' => 'nullable|integer|min:0',
            'last_service_date' => 'nullable|date',
            'insurance_photos' => 'nullable|array',
            'insurance_photos.*' => 'image|max:2048',
            'rc_photos' => 'nullable|array',
            'rc_photos.*' => 'image|max:2048',
            'remove_insurance' => 'nullable|array',
            'remove_insurance.*' => 'string',
            'remove_rc' => 'nullable|array',
            'remove_rc.*' => 'string',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
            'status' => 'required|string',
        ]);
    }

    /**
     * Store uploaded document photos and return their storage paths.
     *
     * @return list<string>
     */
    private function storeDocumentPhotos(Request $request, string $field): array
    {
        return collect($request->file($field) ?? [])
            ->map(fn (UploadedFile $file) => $file->store('vehicles/documents', 'public'))
            ->all();
    }

    /**
     * Merge existing document photos after removing checked ones and adding uploads.
     *
     * @param  list<string>  $existing
     * @param  list<string>  $removed
     * @param  list<string>  $uploaded
     * @return list<string>
     */
    private function mergeDocumentPhotos(array $existing, array $removed, array $uploaded): array
    {
        foreach ($removed as $path) {
            Storage::disk('public')->delete($path);
        }

        return collect($existing)
            ->reject(fn (string $path) => in_array($path, $removed, true))
            ->merge($uploaded)
            ->values()
            ->all();
    }
}
