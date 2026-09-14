<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $drivers = Driver::with(['currentCity'])->paginate(15);

        return view('admin.drivers.index', compact('drivers'));
    }

    public function create()
    {
        $cities = City::all();

        return view('admin.drivers.create', compact('cities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'license_number' => 'required|string|unique:drivers',
            'license_expiry' => 'required|date',
            'experience_years' => 'nullable|integer|min:0',
            'current_city_id' => 'required|exists:cities,id',
            'profile_photo' => 'nullable|image|max:2048',
            'license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'status' => 'required|string',
        ]);

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo'] = $request->file('profile_photo')->store('drivers/photos', 'public');
        }
        if ($request->hasFile('license_document')) {
            $validated['license_document'] = $request->file('license_document')->store('drivers/licenses', 'public');
        }

        Driver::create($validated);

        return redirect()->route('admin.drivers.index')->with('success', 'Driver created successfully.');
    }

    public function edit(Driver $driver)
    {
        $cities = City::all();

        return view('admin.drivers.edit', compact('driver', 'cities'));
    }

    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'license_number' => 'required|string|unique:drivers,license_number,'.$driver->id,
            'license_expiry' => 'required|date',
            'experience_years' => 'nullable|integer|min:0',
            'current_city_id' => 'required|exists:cities,id',
            'profile_photo' => 'nullable|image|max:2048',
            'license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'status' => 'required|string',
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($driver->profile_photo) {
                Storage::disk('public')->delete($driver->profile_photo);
            }
            $validated['profile_photo'] = $request->file('profile_photo')->store('drivers/photos', 'public');
        }
        if ($request->hasFile('license_document')) {
            if ($driver->license_document) {
                Storage::disk('public')->delete($driver->license_document);
            }
            $validated['license_document'] = $request->file('license_document')->store('drivers/licenses', 'public');
        }

        $driver->update($validated);

        return redirect()->route('admin.drivers.index')->with('success', 'Driver updated successfully.');
    }

    public function destroy(Driver $driver)
    {
        if ($driver->profile_photo) {
            Storage::disk('public')->delete($driver->profile_photo);
        }
        if ($driver->license_document) {
            Storage::disk('public')->delete($driver->license_document);
        }
        $driver->delete();

        return redirect()->route('admin.drivers.index')->with('success', 'Driver deleted successfully.');
    }
}
