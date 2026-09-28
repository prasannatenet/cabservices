<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\UpdateProfileRequest;
use App\Models\Driver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Lets a driver maintain his own contact details from the driver portal.
 *
 * Identity and compliance data (Aadhaar, licence, status, city) is owned by the
 * admin: the profile page shows it read-only, and the update request only
 * accepts the fields listed in UpdateProfileRequest.
 */
class ProfileController extends Controller
{
    public function edit(): View
    {
        $driver = $this->authDriver();

        return view('driver.profile', [
            'driver' => $driver->load('currentCity'),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $driver = $this->authDriver();

        $attributes = $request->profileAttributes();

        if ($request->hasFile('profile_photo')) {
            if ($driver->profile_photo) {
                Storage::disk('public')->delete($driver->profile_photo);
            }

            $attributes['profile_photo'] = $request->file('profile_photo')->store('drivers/photos', 'public');
        }

        $driver->update($attributes);

        // Keep the login account name in step with the driver record.
        $user = Auth::user();

        if ($user && $user->name !== $driver->name) {
            $user->update(['name' => $driver->name]);
        }

        return redirect()->route('driver.profile.edit')
            ->with('success', 'Your profile has been updated.');
    }

    /**
     * Resolve the driver profile linked to the authenticated user.
     */
    private function authDriver(): Driver
    {
        $driver = Auth::user()->driver;

        abort_unless($driver instanceof Driver, 404, 'No driver profile is linked to this account.');

        return $driver;
    }
}
