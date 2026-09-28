<?php

namespace App\Http\Requests\Driver;

use App\Models\Driver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * What a driver is allowed to change about his own profile.
 *
 * Aadhaar details, the licence and the account status are deliberately absent:
 * they are identity and compliance data owned by the admin, and a driver may
 * only read them. The list here is an allowlist, so anything not named cannot
 * be written even if it is posted.
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * The route sits behind the driver role middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $driver = $this->route('profile');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('drivers', 'email')->ignore($driver?->id),
            ],
            'permanent_address' => ['nullable', 'string', 'max:1000'],
            'current_address' => ['nullable', 'string', 'max:1000'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * The email belongs to the driver record, not to a shared account, so it is
     * optional and must not clash with another driver's address.
     */
    public function attributes(): array
    {
        return [
            'alternate_phone' => 'alternate mobile number',
            'permanent_address' => 'permanent address',
            'current_address' => 'current address',
        ];
    }

    /**
     * Only the fields this driver is allowed to change are handed to the model.
     *
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        return $this->safe()->only([
            'name', 'phone', 'whatsapp', 'alternate_phone', 'email',
            'permanent_address', 'current_address', 'experience_years',
        ]);
    }

    /**
     * The driver profile the request is acting on, if any.
     */
    public function driver(): ?Driver
    {
        $driver = $this->route('profile');

        return $driver instanceof Driver ? $driver : null;
    }
}
