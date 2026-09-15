<?php

namespace App\Http\Controllers\Associate;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceTypeController extends Controller
{
    /**
     * List the services that belong to the cities this associate manages.
     */
    public function index(Request $request): View
    {
        $services = ServiceType::inCities($this->cityIds())
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

        return view('associate.service-types.index', [
            'services' => $services,
            'cities' => $this->assignedCities(),
        ]);
    }

    public function create(): View
    {
        return view('associate.service-types.create', [
            'cities' => $this->assignedCities(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedServiceData($request);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('service-types', 'public');
        }

        $validated['created_by'] = auth()->id();

        ServiceType::create($validated);

        return redirect()->route('associate.service-types.index')->with('success', 'Service created successfully.');
    }

    public function edit(ServiceType $serviceType): View
    {
        $this->authorizeService($serviceType);

        return view('associate.service-types.edit', [
            'serviceType' => $serviceType,
            'cities' => $this->assignedCities(),
        ]);
    }

    public function update(Request $request, ServiceType $serviceType)
    {
        $this->authorizeService($serviceType);

        $validated = $this->validatedServiceData($request);

        if ($request->hasFile('image')) {
            if ($serviceType->image) {
                Storage::disk('public')->delete($serviceType->image);
            }
            $validated['image'] = $request->file('image')->store('service-types', 'public');
        }

        $serviceType->update($validated);

        return redirect()->route('associate.service-types.index')->with('success', 'Service updated successfully.');
    }

    public function destroy(ServiceType $serviceType)
    {
        $this->authorizeService($serviceType);

        if ($serviceType->image) {
            Storage::disk('public')->delete($serviceType->image);
        }

        $serviceType->delete();

        return redirect()->route('associate.service-types.index')->with('success', 'Service deleted successfully.');
    }

    /**
     * A service can only be created in one of the associate's own cities.
     *
     * @return array<string, mixed>
     */
    private function validatedServiceData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'required|integer',
            'image' => 'nullable|image|max:2048',
            'city_id' => ['required', Rule::in($this->cityIds())],
            'status' => 'required|in:Active,Inactive',
        ]);
    }

    /**
     * Ids of the cities this associate manages.
     *
     * @return list<int>
     */
    private function cityIds(): array
    {
        return auth()->user()->assignedCityIds();
    }

    /**
     * @return Collection<int, City>
     */
    private function assignedCities()
    {
        return auth()->user()->assignedCities()->orderBy('name')->get();
    }

    /**
     * Stop the associate from touching a service of a city he does not manage.
     */
    private function authorizeService(ServiceType $serviceType): void
    {
        abort_unless(
            auth()->user()->managesCity($serviceType->city_id),
            403,
            'This service belongs to a city you do not manage.'
        );
    }
}
