<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;

class NearbyCityController extends Controller
{
    public function index(City $city)
    {
        $nearbyCities = $city->nearbyCities()->get();
        // Exclude the current city and already added cities
        $availableCities = City::where('id', '!=', $city->id)
            ->whereNotIn('id', $nearbyCities->pluck('id'))
            ->where('status', 'Active')
            ->get();

        return view('admin.cities.nearby', compact('city', 'nearbyCities', 'availableCities'));
    }

    public function store(Request $request, City $city)
    {
        $validated = $request->validate([
            'nearby_city_id' => 'required|exists:cities,id',
            'priority' => 'required|integer',
            'is_enabled' => 'boolean',
        ]);

        $city->nearbyCities()->attach($validated['nearby_city_id'], [
            'priority' => $validated['priority'],
            'is_enabled' => $request->has('is_enabled'),
        ]);

        return redirect()->route('admin.cities.nearby', $city)->with('success', 'Nearby city added.');
    }

    public function update(Request $request, City $city, $nearbyCityId)
    {
        $validated = $request->validate([
            'priority' => 'required|integer',
            'is_enabled' => 'boolean',
        ]);

        $city->nearbyCities()->updateExistingPivot($nearbyCityId, [
            'priority' => $validated['priority'],
            'is_enabled' => $request->has('is_enabled'),
        ]);

        return redirect()->route('admin.cities.nearby', $city)->with('success', 'Nearby city updated.');
    }

    public function destroy(City $city, $nearbyCityId)
    {
        $city->nearbyCities()->detach($nearbyCityId);

        return redirect()->route('admin.cities.nearby', $city)->with('success', 'Nearby city removed.');
    }
}
