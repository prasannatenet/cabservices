<?php

namespace App\Http\Controllers\Associate;

use App\Enums\DriverStatus;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The associate manages every driver currently based in one of his cities.
 */
class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $drivers = Driver::with(['currentCity', 'user', 'creator'])
            ->inCities($this->cityIds())
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

        return view('associate.drivers.index', [
            'drivers' => $drivers,
            'cities' => $this->assignedCities(),
            'statuses' => array_map(fn (DriverStatus $status) => $status->value, DriverStatus::cases()),
        ]);
    }

    public function create(): View
    {
        return view('associate.drivers.create', [
            'cities' => $this->assignedCities(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validatedDriverData($request);

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo'] = $request->file('profile_photo')->store('drivers/photos', 'public');
        }
        if ($request->hasFile('license_document')) {
            $validated['license_document'] = $request->file('license_document')->store('drivers/licenses', 'public');
        }
        if ($request->hasFile('aadhaar_photo')) {
            $validated['aadhaar_photo'] = $request->file('aadhaar_photo')->store('drivers/aadhaar', 'public');
        }

        $validated['created_by'] = auth()->id();

        $driver = Driver::create($validated);

        $this->saveLoginAccount($driver, $validated);

        return redirect()->route('associate.drivers.index')->with('success', 'Driver created successfully.');
    }

    public function show(Driver $driver): View
    {
        $this->authorizeDriver($driver);

        $driver->load(['currentCity', 'leaves', 'user', 'preferredCities']);

        return view('associate.drivers.show', compact('driver'));
    }

    public function edit(Driver $driver): View
    {
        $this->authorizeDriver($driver);

        return view('associate.drivers.edit', [
            'driver' => $driver,
            'cities' => $this->assignedCities(),
        ]);
    }

    public function update(Request $request, Driver $driver)
    {
        $this->authorizeDriver($driver);

        $validated = $this->validatedDriverData($request, $driver);

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
        if ($request->hasFile('aadhaar_photo')) {
            if ($driver->aadhaar_photo) {
                Storage::disk('public')->delete($driver->aadhaar_photo);
            }
            $validated['aadhaar_photo'] = $request->file('aadhaar_photo')->store('drivers/aadhaar', 'public');
        }

        $driver->update($validated);

        $this->saveLoginAccount($driver, $validated);

        return redirect()->route('associate.drivers.index')->with('success', 'Driver updated successfully.');
    }

    public function destroy(Driver $driver)
    {
        $this->authorizeDriver($driver);

        if ($driver->profile_photo) {
            Storage::disk('public')->delete($driver->profile_photo);
        }
        if ($driver->license_document) {
            Storage::disk('public')->delete($driver->license_document);
        }
        if ($driver->aadhaar_photo) {
            Storage::disk('public')->delete($driver->aadhaar_photo);
        }

        $driver->delete();

        return redirect()->route('associate.drivers.index')->with('success', 'Driver deleted successfully.');
    }

    /**
     * Shared validation for store and update. The driver may only be based in
     * one of the associate's own cities.
     *
     * @return array<string, mixed>
     */
    private function validatedDriverData(Request $request, ?Driver $driver = null): array
    {
        $this->normalizeAadhaarInput($request);

        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'license_number' => 'required|string|unique:drivers,license_number'.($driver ? ','.$driver->id : ''),
            'license_expiry' => 'required|date',
            'aadhaar_number' => [
                'nullable', 'string', 'size:12',
                Rule::unique('drivers', 'aadhaar_number')->ignore($driver?->id),
            ],
            'aadhaar_photo' => 'nullable|image|max:2048',
            'permanent_address' => 'nullable|string|max:1000',
            'current_address' => 'nullable|string|max:1000',
            'experience_years' => 'nullable|integer|min:0',
            'current_city_id' => ['required', Rule::in($this->cityIds())],
            'profile_photo' => 'nullable|image|max:2048',
            'license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'status' => 'required|string',
            // Driver login account (dashboard access)
            'login_id' => [
                'nullable', 'alpha_dash', 'min:3', 'max:255',
                Rule::unique('users', 'username')->ignore($driver?->user?->id),
            ],
            'login_password' => 'nullable|string|min:8',
        ]);
    }

    /**
     * Strip formatting from the Aadhaar number before validation, so a value
     * typed as "1234 5678 9012" is stored and validated as 12 digits.
     */
    private function normalizeAadhaarInput(Request $request): void
    {
        $aadhaarNumber = $request->input('aadhaar_number');

        $request->merge([
            'aadhaar_number' => blank($aadhaarNumber)
                ? null
                : preg_replace('/\D+/', '', (string) $aadhaarNumber),
        ]);
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
     * The cities shown in every dropdown of this panel.
     *
     * @return Collection<int, City>
     */
    private function assignedCities()
    {
        return auth()->user()->assignedCities()->orderBy('name')->get();
    }

    /**
     * Stop the associate from touching a driver of a city he does not manage.
     */
    private function authorizeDriver(Driver $driver): void
    {
        abort_unless(
            auth()->user()->managesCity($driver->current_city_id),
            403,
            'This driver belongs to a city you do not manage.'
        );
    }
}
