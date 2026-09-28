<?php

namespace App\Models;

use App\Enums\DocumentExpiryStatus;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'created_by',
        'name', 'model', 'vehicle_category_id', 'registration_number',
        'reference_number', 'seating_capacity', 'city_id', 'operating_city_id',
        'image', 'features', 'status', 'fuel_type', 'has_ac', 'luggage_capacity',
        'price_per_km', 'price_per_day', 'fixed_km_per_day',
        'insurance_photo', 'rc_photo', 'last_service_date',
        'rc_issue_date', 'rc_expiry_date',
        'insurance_issue_date', 'insurance_expiry_date',
    ];

    /**
     * The admin or associate who added the vehicle.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Restrict the query to vehicles based in the given cities.
     *
     * @param  list<int>  $cityIds
     */
    public function scopeInCities(Builder $query, array $cityIds): Builder
    {
        return $query->whereIn('city_id', $cityIds);
    }

    /**
     * Restrict the query to vehicles whose RC or insurance expires within the
     * given number of days, so the fleet list can surface them.
     */
    public function scopeWithDocumentExpiringWithin(Builder $query, int $days): Builder
    {
        $deadline = now()->addDays($days)->endOfDay();

        return $query->where(function (Builder $q) use ($deadline): void {
            $q->where('rc_expiry_date', '<=', $deadline)
                ->orWhere('insurance_expiry_date', '<=', $deadline);
        });
    }

    /**
     * Whole days left before the RC expires, or null when no date was set.
     */
    public function rcDaysRemaining(): ?int
    {
        return $this->rc_expiry_date
            ? DocumentExpiryStatus::daysRemaining($this->rc_expiry_date)
            : null;
    }

    /**
     * Whole days left before the insurance expires, or null when no date was set.
     */
    public function insuranceDaysRemaining(): ?int
    {
        return $this->insurance_expiry_date
            ? DocumentExpiryStatus::daysRemaining($this->insurance_expiry_date)
            : null;
    }

    public function rcExpiryStatus(): DocumentExpiryStatus
    {
        return DocumentExpiryStatus::fromDaysRemaining($this->rcDaysRemaining());
    }

    public function insuranceExpiryStatus(): DocumentExpiryStatus
    {
        return DocumentExpiryStatus::fromDaysRemaining($this->insuranceDaysRemaining());
    }

    /**
     * The most urgent of the two documents, used for the summary badge on the
     * fleet list.
     */
    public function documentExpiryStatus(): DocumentExpiryStatus
    {
        $statuses = [$this->rcExpiryStatus(), $this->insuranceExpiryStatus()];

        usort($statuses, fn (DocumentExpiryStatus $a, DocumentExpiryStatus $b): int => $a->priority() <=> $b->priority());

        return $statuses[0];
    }

    protected function casts(): array
    {
        return [
            'price_per_km' => 'decimal:2',
            'price_per_day' => 'decimal:2',
            'fixed_km_per_day' => 'integer',
            'has_ac' => 'boolean',
            'insurance_photo' => 'array',
            'rc_photo' => 'array',
            'last_service_date' => 'date',
            'rc_issue_date' => 'date',
            'rc_expiry_date' => 'date',
            'insurance_issue_date' => 'date',
            'insurance_expiry_date' => 'date',
        ];
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function operatingCity()
    {
        return $this->belongsTo(City::class, 'operating_city_id');
    }

    public function category()
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function images()
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function getVehicleTypeAttribute()
    {
        return $this->category ? $this->category->name : 'Standard';
    }

    /**
     * Public URLs for all uploaded insurance photos.
     *
     * @return list<string>
     */
    public function getInsurancePhotoUrlsAttribute(): array
    {
        return collect($this->insurance_photo ?? [])
            ->map(fn (string $path) => asset('storage/'.$path))
            ->all();
    }

    /**
     * Public URLs for all uploaded RC photos.
     *
     * @return list<string>
     */
    public function getRcPhotoUrlsAttribute(): array
    {
        return collect($this->rc_photo ?? [])
            ->map(fn (string $path) => asset('storage/'.$path))
            ->all();
    }
}
