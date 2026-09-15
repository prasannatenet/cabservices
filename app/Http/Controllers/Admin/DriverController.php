<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DriverStatus;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $drivers = Driver::with(['currentCity', 'user'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('license_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('city_id'), fn ($query) => $query->where('current_city_id', $request->query('city_id')))
            ->paginate(15)
            ->withQueryString();

        return view('admin.drivers.index', [
            'drivers' => $drivers,
            'cities' => City::orderBy('name')->get(),
            'statuses' => array_map(fn (DriverStatus $status) => $status->value, DriverStatus::cases()),
        ]);
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
            // Driver login account (dashboard access)
            'login_id' => 'nullable|alpha_dash|min:3|max:255|unique:users,username',
            'login_password' => 'nullable|string|min:8|required_with:login_id',
        ]);

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo'] = $request->file('profile_photo')->store('drivers/photos', 'public');
        }
        if ($request->hasFile('license_document')) {
            $validated['license_document'] = $request->file('license_document')->store('drivers/licenses', 'public');
        }

        $driver = Driver::create($validated);

        $this->saveLoginAccount($driver, $validated);

        return redirect()->route('admin.drivers.index')->with('success', 'Driver created successfully.');
    }

    public function show(Driver $driver)
    {
        $driver->load(['currentCity', 'leaves', 'user', 'preferredCities']);

        return view('admin.drivers.show', compact('driver'));
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
            // Driver login account (dashboard access)
            'login_id' => [
                'nullable', 'alpha_dash', 'min:3', 'max:255',
                Rule::unique('users', 'username')->ignore($driver->user?->id),
            ],
            'login_password' => 'nullable|string|min:8',
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

        $this->saveLoginAccount($driver, $validated);

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

    /**
     * Create or update the driver's login account (used for the driver dashboard).
     *
     * @param  array<string, mixed>  $validated
     */
    private function saveLoginAccount(Driver $driver, array $validated): void
    {
        $loginId = $validated['login_id'] ?? null;
        $password = $validated['login_password'] ?? null;

        if (empty($loginId)) {
            return;
        }

        $accountData = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'username' => $loginId,
        ];

        if ($driver->user) {
            if (! empty($password)) {
                $accountData['password'] = $password;
            }

            $driver->user->update($accountData);

            return;
        }

        if (empty($password)) {
            return;
        }

        $user = User::create($accountData + [
            'password' => $password,
            'role' => User::ROLE_DRIVER,
        ]);

        $driver->update(['user_id' => $user->id]);
    }
}
