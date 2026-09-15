<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceTypeController extends Controller
{
    public function index(Request $request)
    {
        $services = ServiceType::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->paginate(15)
            ->withQueryString();

        return view('admin.service-types.index', compact('services'));
    }

    public function create()
    {
        return view('admin.service-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'required|integer',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:Active,Inactive',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('service-types', 'public');
        }

        ServiceType::create($validated);

        return redirect()->route('admin.service-types.index')->with('success', 'Service created successfully.');
    }

    public function edit(ServiceType $serviceType)
    {
        return view('admin.service-types.edit', compact('serviceType'));
    }

    public function update(Request $request, ServiceType $serviceType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'required|integer',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:Active,Inactive',
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
