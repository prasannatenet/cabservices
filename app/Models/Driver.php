<?php

namespace App\Models;

use App\Enums\AssignmentResponseStatus;
use App\Enums\DriverStatus;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'whatsapp', 'alternate_phone', 'email', 'address', 'user_id', 'created_by',
        'license_number', 'license_expiry', 'license_document', 'experience_years', 'profile_photo',
        'aadhaar_number', 'aadhaar_photo', 'permanent_address', 'current_address',
        'current_city_id', 'status',
    ];

    /**
     * Aadhaar is a 12 digit number, optionally written as XXXX XXXX XXXX.
     */
    protected static function normalizeAadhaarNumber(?string $aadhaarNumber): ?string
    {
        if (blank($aadhaarNumber)) {
            return null;
        }

        return preg_replace('/\D+/', '', $aadhaarNumber);
    }

    /**
     * Store the Aadhaar number without spaces so the unique index and lookups
     * work regardless of how it was typed.
     */
    public function setAadhaarNumberAttribute(?string $value): void
    {
        $this->attributes['aadhaar_number'] = static::normalizeAadhaarNumber($value);
    }

    /**
     * The Aadhaar number as XXXX XXXX XXXX for display.
     */
    public function getFormattedAadhaarNumberAttribute(): ?string
    {
        if (blank($this->aadhaar_number)) {
            return null;
        }

        return trim(chunk_split($this->aadhaar_number, 4, ' '));
    }

    /**
     * Public URL of the uploaded Aadhaar photo, or null when none was uploaded.
     */
    public function getAadhaarPhotoUrlAttribute(): ?string
    {
        return $this->aadhaar_photo ? asset('storage/'.$this->aadhaar_photo) : null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
        ];
    }

    /**
     * The admin or associate who added the driver.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Restrict the query to drivers currently based in the given cities.
     *
     * @param  list<int>  $cityIds
     */
    public function scopeInCities(Builder $query, array $cityIds): Builder
    {
        return $query->whereIn('current_city_id', $cityIds);
    }

    /**
     * Restrict the query to drivers willing to go to the given drop city.
     *
     * A driver is willing when he explicitly selected the city in
     * "My Cities", or when he has not selected any preferred city yet
     * (backward compatible: no preference means willing everywhere).
     * Drivers who selected other cities but not this one are excluded.
     */
    public function scopeWillingToGoTo(Builder $query, ?int $cityId): Builder
    {
        if (blank($cityId)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($cityId) {
            $q->whereHas('preferredCities', fn (Builder $sq) => $sq->where('cities.id', $cityId))
                ->orWhereDoesntHave('preferredCities');
        });
    }

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

    /**
     * Every ride ever assigned to this driver, newest first.
     */
    public function driverAssignments()
    {
        return $this->hasMany(DriverAssignment::class);
    }

    /**
     * Assignments still waiting for this driver to accept or refuse.
     */
    public function pendingAssignments()
    {
        return $this->driverAssignments()
            ->where('response_status', AssignmentResponseStatus::Pending->value)
            ->where('response_deadline', '>', now());
    }

    /**
     * Rides this driver refused, or never answered, newest answer first.
     * Each row still carries the reason he gave for the rejection.
     */
    public function rejectedAssignments()
    {
        return $this->driverAssignments()->rejected();
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
