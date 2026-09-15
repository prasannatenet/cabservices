<?php

namespace App\Models;

use App\Enums\DriverStatus;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'whatsapp', 'email', 'address', 'user_id',
        'license_number', 'license_expiry', 'license_document', 'experience_years', 'profile_photo',
        'current_city_id', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currentCity()
    {
        return $this->belongsTo(City::class, 'current_city_id');
    }

    public function preferredCities()
    {
        return $this->belongsToMany(City::class, 'driver_city')->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function leaves()
    {
        return $this->hasMany(DriverLeave::class);
    }

    /**
     * Whether the driver can manage his own availability from the dashboard.
     * Statuses like Assigned, On Trip, On Leave and Inactive are managed by the admin.
     */
    public function canManageAvailability(): bool
    {
        return in_array($this->status, [DriverStatus::AVAILABLE->value, DriverStatus::UNAVAILABLE->value], true);
    }

    /**
     * Toggle between Available and Unavailable. Returns false when the
     * current status is controlled by the admin and cannot be toggled.
     */
    public function toggleAvailability(): bool
    {
        if (! $this->canManageAvailability()) {
            return false;
        }

        $this->update([
            'status' => $this->status === DriverStatus::AVAILABLE->value
                ? DriverStatus::UNAVAILABLE->value
                : DriverStatus::AVAILABLE->value,
        ]);

        return true;
    }
}
