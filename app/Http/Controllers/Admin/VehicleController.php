<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with(['city'])->paginate(15);
        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        $cities = City::all();
        $categories = VehicleCategory::all();
        return view('admin.vehicles.create', compact('cities', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'vehicle_category_id' => 'required|exists:vehicle_categories,id',
            'registration_number' => 'required|string|unique:vehicles',
            'seating_capacity' => 'required|integer|min:1',
            'city_id' => 'required|exists:cities,id',
            'operating_city_id' => 'nullable|exists:cities,id',
            'fuel_type' => 'nullable|string|max:50',
            'has_ac' => 'boolean',
            'luggage_capacity' => 'nullable|integer',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
            'status' => 'required|string',
        ]);

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

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'vehicle_category_id' => 'required|exists:vehicle_categories,id',
            'registration_number' => 'required|string|unique:vehicles,registration_number,'.$vehicle->id,
            'seating_capacity' => 'required|integer|min:1',
            'city_id' => 'required|exists:cities,id',
            'operating_city_id' => 'nullable|exists:cities,id',
            'fuel_type' => 'nullable|string|max:50',
            'has_ac' => 'boolean',
            'luggage_capacity' => 'nullable|integer',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
            'status' => 'required|string',
        ]);

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
}
