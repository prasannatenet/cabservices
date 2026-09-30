<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FiltersByAssociate;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ServiceTypeController extends Controller
{
    use FiltersByAssociate;

    public function index(Request $request)
    {
        $services = $this->applyAssociateFilter(
            ServiceType::with(['city', 'creator', 'associate']),
            $request,
        )
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('city_id'), fn ($query) => $query->where('city_id', $request->query('city_id')))
            ->when($request->filled('approval'), function ($query) use ($request) {
                $query->where('is_approved', $request->query('approval') === 'pending' ? false : true);
            })
            ->paginate(15)
            ->withQueryString();

        return view('admin.service-types.index', [
            'services' => $services,
            'cities' => City::orderBy('name')->get(),
            // Lets the admin narrow the list down to one associate's services, or
            // to the ones he created himself.
            ...$this->associateFilterOptions(),
        ]);
    }

    public function create()
    {
        return view('admin.service-types.create', [
            'cities' => City::orderBy('name')->get(),
            'associates' => User::associateOptions(),
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
            // Who owns the service: an existing associate, or the admin himself.
            'associate_id' => $this->associateOwnerRules(),
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('service-types', 'public');
        }

        $validated['created_by'] = Auth::id();
        // An admin-created service is the admin's own unless he hands it to an
        // associate, in which case that associate manages it from then on.
        $validated['associate_id'] = $this->resolveAssociateId($validated['associate_id'] ?? null);
        // Admin-created services are approved by default.
        $validated['is_approved'] = true;

        ServiceType::create($validated);

        return redirect()->route('admin.service-types.index')->with('success', 'Service created successfully.');
    }

    public function edit(ServiceType $serviceType)
    {
        return view('admin.service-types.edit', [
            'serviceType' => $serviceType,
            'cities' => City::orderBy('name')->get(),
            'associates' => User::associateOptions(),
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
            'is_approved' => 'sometimes|boolean',
            // Who owns the service: an existing associate, or the admin himself.
            'associate_id' => $this->associateOwnerRules(),
        ]);

        if ($request->hasFile('image')) {
            if ($serviceType->image) {
                Storage::disk('public')->delete($serviceType->image);
            }
            $validated['image'] = $request->file('image')->store('service-types', 'public');
        }

        // Ownership is only touched when the form actually sent the field, so a
        // form that does not show it cannot silently take a service back.
        if ($request->has('associate_id')) {
            $validated['associate_id'] = $this->resolveAssociateId($validated['associate_id'] ?? null);
        } else {
            unset($validated['associate_id']);
        }

        $serviceType->update($validated);

        return redirect()->route('admin.service-types.index')->with('success', 'Service updated successfully.');
    }

    public function approve(Request $request, ServiceType $serviceType)
    {
        $serviceType->update(['is_approved' => true]);

        return back()->with('success', 'Service approved and published to customers.');
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
