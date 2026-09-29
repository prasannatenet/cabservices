<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\RejectionSource;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * The status label used when the assigned driver refused the ride. Both an
     * admin rejection and a driver refusal share the Rejected status, so the
     * admin screens label the driver's refusal with this distinct wording.
     */
    public const DRIVER_REJECTED_LABEL = 'Driver Rejected';

    protected $fillable = [
        'booking_number',        'customer_name', 'customer_phone', 'customer_email', 'customer_whatsapp',
        'pickup_city_id', 'pickup_location', 'drop_city_id', 'drop_location',
        'pickup_date', 'pickup_time', 'drop_date', 'drop_time', 'passengers',
        'service_type_id', 'vehicle_id', 'driver_id', 'vehicle_reference',
        'status', 'rejection_reason', 'rejection_source', 'admin_notes',
        'start_odometer_km', 'start_odometer_photo', 'trip_started_at',
        'end_odometer_km', 'end_odometer_photo', 'trip_ended_at',
        'billed_days', 'billed_included_km', 'billed_price_per_day', 'billed_price_per_km',
        'extra_km', 'base_amount', 'extra_km_amount', 'total_amount',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'rejection_source' => RejectionSource::class,
        'pickup_date' => 'date',
        'drop_date' => 'date',
        'trip_started_at' => 'datetime',
        'trip_ended_at' => 'datetime',
        'billed_days' => 'integer',
        'billed_included_km' => 'integer',
        'billed_price_per_day' => 'decimal:2',
        'billed_price_per_km' => 'decimal:2',
        'extra_km' => 'integer',
        'base_amount' => 'decimal:2',
        'extra_km_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Status text for the admin screens: the driver's own refusal is labelled
     * "Driver Rejected", everything else keeps its status name.
     */
    public function displayStatus(): string
    {
        return $this->isRejectedByDriver() ? self::DRIVER_REJECTED_LABEL : $this->status->value;
    }

    /**
     * Whether the ride is rejected because the assigned driver refused it (or
     * never answered within the response window).
     */
    public function isRejectedByDriver(): bool
    {
        return $this->status === BookingStatus::REJECTED
            && $this->rejection_source === RejectionSource::Driver;
    }

    /**
     * Whether the ride is rejected by the admin rather than by a driver.
     */
    public function isRejectedByAdmin(): bool
    {
        return $this->status === BookingStatus::REJECTED
            && $this->rejection_source !== RejectionSource::Driver;
    }

    /**
     * Restrict the query to rides a driver refused, keeping admin rejections out.
     */
    public function scopeRejectedByDriver(Builder $query): Builder
    {
        return $query->where('status', BookingStatus::REJECTED->value)
            ->where('rejection_source', RejectionSource::Driver->value);
    }

    /**
     * Restrict the query to rides whose trip finished successfully.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', BookingStatus::TRIP_COMPLETED->value);
    }

    /**
     * Restrict the query to rides that are still on their way, i.e. assigned,
     * confirmed or started but not finished, cancelled or rejected yet.
     */
    public function scopeOngoing(Builder $query): Builder
    {
        return $query->whereIn('status', [
            BookingStatus::DRIVER_ASSIGNED->value,
            BookingStatus::CONFIRMED->value,
            BookingStatus::TRIP_STARTED->value,
        ]);
    }

    /**
     * Whether the driver has started the ride, i.e. the odometer reading and
     * photo were captured when the trip began.
     */
    public function hasTripStarted(): bool
    {
        return $this->trip_started_at !== null;
    }

    /**
     * A confirmed ride the driver has not started yet is the only one he can
     * open with an odometer reading and photo.
     */
    public function canStartTrip(): bool
    {
        return $this->status === BookingStatus::CONFIRMED && ! $this->hasTripStarted();
    }

    /**
     * Expenses belong to a running ride: once the trip is completed the admin
     * keeps the sheet he signed off on.
     */
    public function canLogExpenses(): bool
    {
        return $this->status === BookingStatus::TRIP_STARTED;
    }

    /**
     * Whether the driver has closed the ride, i.e. the closing odometer reading
     * and photo were captured when he reached the drop point.
     */
    public function hasTripEnded(): bool
    {
        return $this->trip_ended_at !== null;
    }

    /**
     * A running ride is the only one the driver can close with the closing
     * odometer reading and photo.
     */
    public function canEndTrip(): bool
    {
        return $this->status === BookingStatus::TRIP_STARTED
            && $this->hasTripStarted()
            && ! $this->hasTripEnded();
    }

    /**
     * Kilometres the ride covered: the closing reading minus the reading taken
     * when the trip started. Null while either reading is still missing.
     */
    public function tripDistanceKm(): ?int
    {
        if ($this->start_odometer_km === null || $this->end_odometer_km === null) {
            return null;
        }

        return max(0, (int) $this->end_odometer_km - (int) $this->start_odometer_km);
    }

    /**
     * Whether the money side of this ride has been worked out and stored, i.e.
     * the trip was closed with the readings its bill comes out of.
     */
    public function hasTripFare(): bool
    {
        return $this->total_amount !== null;
    }

    /**
     * The bill of the ride: what the customer is charged and the figures it came
     * out of. Null for a ride that has not been billed yet.
     *
     * @return array{
     *     billed_days: int,
     *     billed_included_km: int,
     *     billed_price_per_day: float,
     *     billed_price_per_km: float,
     *     extra_km: int,
     *     base_amount: float,
     *     extra_km_amount: float,
     *     total_amount: float
     * }|null
     */
    public function tripFare(): ?array
    {
        if (! $this->hasTripFare()) {
            return null;
        }

        return [
            'billed_days' => (int) $this->billed_days,
            'billed_included_km' => (int) $this->billed_included_km,
            'billed_price_per_day' => (float) $this->billed_price_per_day,
            'billed_price_per_km' => (float) $this->billed_price_per_km,
            'extra_km' => (int) $this->extra_km,
            'base_amount' => (float) $this->base_amount,
            'extra_km_amount' => (float) $this->extra_km_amount,
            'total_amount' => (float) $this->total_amount,
        ];
    }

    /**
     * Public URL of the odometer photo taken when the trip started.
     */
    public function startOdometerPhotoUrl(): ?string
    {
        return $this->start_odometer_photo ? asset('storage/'.$this->start_odometer_photo) : null;
    }

    /**
     * Public URL of the odometer photo taken when the trip ended.
     */
    public function endOdometerPhotoUrl(): ?string
    {
        return $this->end_odometer_photo ? asset('storage/'.$this->end_odometer_photo) : null;
    }

    /**
     * Total money the driver logged for this ride.
     */
    public function expenseTotal(): float
    {
        return round((float) $this->rideExpenses()->sum('amount'), 2);
    }

    public function pickupCity()
    {
        return $this->belongsTo(City::class, 'pickup_city_id');
    }

    /**
     * Restrict the query to bookings starting in the given cities.
     *
     * @param  list<int>  $cityIds
     */
    public function scopeInCities(Builder $query, array $cityIds): Builder
    {
        return $query->whereIn('pickup_city_id', $cityIds);
    }

    public function dropCity()
    {
        return $this->belongsTo(City::class, 'drop_city_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    public function driverAssignments()
    {
        return $this->hasMany(DriverAssignment::class);
    }

    /**
     * Fuel, gas and other money the driver spent while driving this ride.
     */
    public function rideExpenses()
    {
        return $this->hasMany(RideExpense::class);
    }

    public function driverAssignment()
    {
        return $this->hasOne(DriverAssignment::class)->latestOfMany();
    }
}
