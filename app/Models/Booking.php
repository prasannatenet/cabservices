<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\RejectionSource;
use App\Models\Concerns\BelongsToAssociate;
use App\Services\TripFareCalculator;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use BelongsToAssociate, HasFactory;

    protected $fillable = [
        'booking_number',        'customer_name', 'customer_phone', 'customer_email', 'customer_whatsapp',
        'customer_user_id',
        'pickup_city_id', 'pickup_location', 'pickup_landmark', 'pickup_location_link',
        'drop_city_id', 'drop_city_label', 'drop_location',
        'pickup_date', 'pickup_time', 'drop_date', 'drop_time', 'passengers',
        'service_type_id', 'associate_id', 'vehicle_id', 'driver_id', 'vehicle_reference',
        'status', 'rejection_reason', 'rejection_source', 'admin_notes',
        'start_odometer_km', 'start_odometer_photo', 'trip_started_at',
        'end_odometer_km', 'end_odometer_photo', 'trip_ended_at',
        'tracking_id', 'current_latitude', 'current_longitude',
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
     * Status text for the admin screens.
     *
     * A driver's own refusal is a status in its own right now, so this is simply
     * the stored status. It stays as one method so every screen reads the label
     * from a single place.
     */
    public function displayStatus(): string
    {
        return $this->status->value;
    }

    /**
     * The drop city as it should be read on every screen.
     *
     * A customer may write any place at all as the destination, including one
     * the fleet does not run in. When he does, the ride is still served from
     * the pickup city, so the stored drop city says where the cab stands rather
     * than where the traveller is going. The label the customer wrote is the
     * real destination and wins whenever it is there, so a Jaipur to Kishangarh
     * ride never reads as "Jaipur to Jaipur".
     */
    public function displayDropCity(): string
    {
        return $this->drop_city_label
            ?: $this->dropCity?->name
            ?: $this->drop_location;
    }

    /**
     * The route as one line, e.g. "Jaipur → Kishangarh".
     */
    public function displayRoute(): string
    {
        return ($this->pickupCity?->name ?? $this->pickup_location)
            .' → '.$this->displayDropCity();
    }

    /**
     * Whether the customer is going somewhere the fleet does not run.
     *
     * True when he wrote a destination that is not the city the ride is served
     * from, e.g. Kishangarh on a ride served from Jaipur. It drives the
     * "Outside our network" marker so a dispatcher can see at a glance which
     * rides are a long haul rather than a run in our own city.
     */
    public function isOutOfNetwork(): bool
    {
        if (blank($this->drop_city_label)) {
            return false;
        }

        $servingCity = $this->dropCity?->name;

        return $servingCity === null
            || mb_strtolower(trim($this->drop_city_label)) !== mb_strtolower(trim($servingCity));
    }

    /**
     * Whether the ride was refused by the assigned driver, or by nobody
     * answering within the response window.
     */
    public function isRejectedByDriver(): bool
    {
        return $this->status === BookingStatus::DRIVER_REJECTED;
    }

    /**
     * Whether the ride was rejected by the admin rather than by a driver.
     */
    public function isRejectedByAdmin(): bool
    {
        return $this->status === BookingStatus::REJECTED;
    }

    /**
     * Restrict the query to rides a driver refused, keeping admin rejections out.
     */
    public function scopeRejectedByDriver(Builder $query): Builder
    {
        return $query->where('status', BookingStatus::DRIVER_REJECTED->value);
    }

    /**
     * Restrict the query to rides the admin rejected, keeping driver refusals
     * out.
     */
    public function scopeRejectedByAdmin(Builder $query): Builder
    {
        return $query->where('status', BookingStatus::REJECTED->value);
    }

    /**
     * Restrict the query to rides whose trip finished successfully.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', BookingStatus::TRIP_COMPLETED->value);
    }

    /**
     * The statuses that hold on to a driver and a vehicle, i.e. the rides that
     * are about to run or are running.
     */
    public function scopeOngoing(Builder $query): Builder
    {
        return $query->whereIn('status', BookingStatus::occupiesResources());
    }

    /**
     * Restrict the query to rides that will not run as they stand: refused,
     * rejected or cancelled.
     */
    public function scopeClosedWithoutRunning(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            fn (BookingStatus $status) => $status->value,
            array_filter(BookingStatus::cases(), fn (BookingStatus $status) => $status->isClosedWithoutRunning()),
        ));
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
     * Everything money-wise this one ride came to, in one place: what the
     * customer is charged, what the driver earned for driving it, what he spent
     * on it, and what is left over.
     *
     * The charge is the bill stored when the trip was closed, or the one the
     * vehicle's current rate card comes to when the ride was never billed, so a
     * figure can be given even for an older ride. Which of the two it is comes
     * back as "charged", because an estimate must never be read as money taken.
     *
     * The leftover is only worked out when the driver's pay for the ride is
     * actually known, which is the case for a driver paid per day. A driver on a
     * fixed monthly salary has no per-ride cost to subtract, so guessing one
     * would overstate what is left; there the figure is reported as null and
     * the screen says so rather than inventing a profit.
     *
     * @return array{
     *     charged: bool,
     *     has_figure: bool,
     *     total_amount: float,
     *     driver_earnings: array{days: int, rate: float, total: float}|null,
     *     driver_pay: float,
     *     expenses: float,
     *     leftover: float|null
     * }
     */
    public function moneySummary(TripFareCalculator $calculator): array
    {
        $fare = $calculator->fareFor($this);
        $earnings = $this->driver?->earningsFor($this);

        $totalAmount = (float) ($fare['total_amount'] ?? 0.0);
        $driverPay = (float) ($earnings['total'] ?? 0.0);
        $expenses = $this->expenseTotal();

        return [
            'charged' => (bool) ($fare['billed'] ?? false),
            'has_figure' => $fare !== null,
            'total_amount' => $totalAmount,
            'driver_earnings' => $earnings,
            'driver_pay' => $driverPay,
            'expenses' => $expenses,
            'leftover' => $earnings === null
                ? null
                : round($totalAmount - $driverPay - $expenses, 2),
        ];
    }

    /**
     * A ride is only finished and therefore frozen.
     *
     * A completed trip is the record of what actually happened: the odometer
     * readings, the bill worked out from them, the driver who drove it and the
     * distance covered. Once it is closed, nobody may touch it any more, so the
     * figures cannot be quietly rewritten after the fact. Every screen that
     * edits a ride checks this before letting anything be saved. A cancelled
     * ride is frozen for the same reason: nothing follows it either.
     *
     * A rejected or driver rejected ride is deliberately not locked: the admin
     * reopens a refused ride by giving it another driver, so those have to stay
     * editable.
     */
    public function isLocked(): bool
    {
        return $this->status->isFinal();
    }

    /**
     * The message shown wherever a locked ride would otherwise be editable.
     */
    public function lockedMessage(): string
    {
        return 'This trip has ended on '.$this->displayStatus().' and can no longer be changed.';
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

    /**
     * Work out which associate this ride belongs to from what the admin has
     * assigned to it, and store the answer on the ride.
     *
     * A ride is only an associate's once one of his own resources is put on it:
     * the driver if he has one, otherwise the vehicle. The city the ride starts
     * in plays no part, so a Udaipur ride stays with the admin until the admin
     * assigns the Udaipur associate's driver to it.
     *
     * Called whenever a driver or vehicle is assigned, and again when one is
     * taken away, so a ride that loses its associate resource goes back to the
     * admin rather than staying with the associate who no longer runs it.
     */
    public function syncAssociateFromAssignment(): self
    {
        // The relations are re-read rather than reused: they are commonly already
        // loaded from before the swap, and a cached owner would keep the ride on
        // the associate it just lost.
        $this->unsetRelation('driver')->unsetRelation('vehicle');

        $associateId = $this->driver?->associate_id ?? $this->vehicle?->associate_id;

        $this->associate_id = $associateId;
        $this->save();

        return $this;
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

    /**
     * The customer account that follows this ride from the customer panel.
     * Nullable while the ride is only requested: it is given an account when the
     * ride is confirmed.
     */
    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
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
