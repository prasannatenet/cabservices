<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VehicleCategoryController extends Controller
{
    public function index()
    {
        $categories = VehicleCategory::paginate(15);

        return view('admin.vehicle-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.vehicle-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_seating_capacity' => 'required|integer|min:1',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:Active,Inactive',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('vehicle-categories', 'public');
        }

        VehicleCategory::create($validated);

        return redirect()->route('admin.vehicle-categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(VehicleCategory $vehicleCategory)
    {
        return view('admin.vehicle-categories.edit', compact('vehicleCategory'));
    }

    public function update(Request $request, VehicleCategory $vehicleCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_seating_capacity' => 'required|integer|min:1',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:Active,Inactive',
        ]);

        if ($request->hasFile('image')) {
            if ($vehicleCategory->image) {
                Storage::disk('public')->delete($vehicleCategory->image);
            }
            $validated['image'] = $request->file('image')->store('vehicle-categories', 'public');
        }

        $vehicleCategory->update($validated);

        return redirect()->route('admin.vehicle-categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(VehicleCategory $vehicleCategory)
    {
        if ($vehicleCategory->image) {
            Storage::disk('public')->delete($vehicleCategory->image);
        }
        $vehicleCategory->delete();

        return redirect()->route('admin.vehicle-categories.index')->with('success', 'Category deleted successfully.');
    }
}
