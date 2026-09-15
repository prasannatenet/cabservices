<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceTypeController extends Controller
{
    public function index(Request $request)
    {
        $services = ServiceType::with(['city', 'creator'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('city_id'), fn ($query) => $query->where('city_id', $request->query('city_id')))
            ->paginate(15)
            ->withQueryString();

        return view('admin.service-types.index', [
            'services' => $services,
            'cities' => City::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.service-types.create', [
            'cities' => City::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'required|integer',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:Active,Inactive',
            // A service of one of the admin's cities is visible only in that city; a
            // service without a city stays global and admin-only.
            'city_id' => 'nullable|exists:cities,id',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('service-types', 'public');
        }

        $validated['created_by'] = auth()->id();

        ServiceType::create($validated);

        return redirect()->route('admin.service-types.index')->with('success', 'Service created successfully.');
    }

    public function edit(ServiceType $serviceType)
    {
        return view('admin.service-types.edit', [
            'serviceType' => $serviceType,
            'cities' => City::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ServiceType $serviceType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'required|integer',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:Active,Inactive',
            'city_id' => 'nullable|exists:cities,id',
        ]);

        if ($request->hasFile('image')) {
            if ($serviceType->image) {
                Storage::disk('public')->delete($serviceType->image);
            }
            $validated['image'] = $request->file('image')->store('service-types', 'public');
        }

        $serviceType->update($validated);

        return redirect()->route('admin.service-types.index')->with('success', 'Service updated successfully.');
    }

    public function destroy(ServiceType $serviceType)
    {
        if ($serviceType->image) {
            Storage::disk('public')->delete($serviceType->image);
        }
        $serviceType->delete();

        return redirect()->route('admin.service-types.index')->with('success', 'Service deleted successfully.');
    }
}
