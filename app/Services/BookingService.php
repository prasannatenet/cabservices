<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\RejectionSource;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        protected MailNotificationService $mailNotifications,
        protected TripFareCalculator $tripFare,
    ) {}

    public function createBookingRequest(array $data)
    {
        $booking = DB::transaction(function () use ($data) {
            $bookingNumber = 'BKG-'.strtoupper(Str::random(8));

            $booking = Booking::create(array_merge($data, [
                'booking_number' => $bookingNumber,
                'status' => BookingStatus::PENDING->value,
            ]));

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'new_status' => BookingStatus::PENDING->value,
                'remarks' => 'Booking request created by customer',
            ]);

            return $booking;
        });

        $this->mailNotifications->notifyBookingRequested($booking);

        return $booking;
    }

    public function approveBooking(Booking $booking, $adminId)
    {
        // Re-verify availability
        $availabilityService = new AvailabilityService;
        $available = $availabilityService->searchAvailableVehicles(
            $booking->pickup_city_id,
            $booking->pickup_date,
            $booking->pickup_time,
            $booking->passengers,
            $booking->service_type_id,
            null,
            $booking->drop_date,
            $booking->drop_time
        );

        if (! $available->contains('id', $booking->vehicle_id)) {
            throw new \Exception('Selected vehicle is no longer available for this time slot.');
        }

        return DB::transaction(function () use ($booking, $adminId) {
            $oldStatus = $booking->status;
            $booking->update([
                'status' => BookingStatus::APPROVED->value,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::APPROVED->value,
                'changed_by' => $adminId,
                'remarks' => 'Booking approved. Awaiting driver assignment.',
            ]);

            return $booking;
        });
    }

    public function rejectBooking(Booking $booking, $adminId, $reason)
    {
        return DB::transaction(function () use ($booking, $adminId, $reason) {
            $oldStatus = $booking->status;
            $booking->update([
                'status' => BookingStatus::REJECTED->value,
                'rejection_reason' => $reason,
                'rejection_source' => RejectionSource::Admin->value,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::REJECTED->value,
                'changed_by' => $adminId,
                'remarks' => 'Booking rejected: '.$reason,
            ]);

            return $booking;
        });
    }

    public function assignDriver(Booking $booking, $driverId, $adminId)
    {
        $this->ensureDriverWillingToGoTo($booking, $driverId);

        $booking = DB::transaction(function () use ($booking, $driverId, $adminId) {
            $oldStatus = $booking->status;

            $booking->update([
                'status' => BookingStatus::DRIVER_ASSIGNED->value,
                'driver_id' => $driverId,
            ]);

            $assignment = DriverAssignment::create([
                'booking_id' => $booking->id,
                'driver_id' => $driverId,
                'vehicle_id' => $booking->vehicle_id,
                'assigned_by' => $adminId,
                'status' => 'Active',
            ]);

            // The driver now has a limited window to accept or refuse the ride.
            $assignment->startResponseWindow();

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::DRIVER_ASSIGNED->value,
                'changed_by' => $adminId,
                'remarks' => 'Driver assigned.',
            ]);

            return $booking;
        });

        $this->mailNotifications->notifyDriverAssigned($booking);

        return $booking;
    }

    public function confirmBooking(Booking $booking, $driverId)
    {
        $this->ensureDriverWillingToGoTo($booking, $driverId);

        $booking = DB::transaction(function () use ($booking, $driverId) {
            $oldStatus = $booking->status;

            $booking->update([
                'status' => BookingStatus::CONFIRMED->value,
                'driver_id' => $driverId,
            ]);

            if ($driverId) {
                DriverAssignment::create([
                    'booking_id' => $booking->id,
                    'driver_id' => $driverId,
                    'vehicle_id' => $booking->vehicle_id,
                    'assigned_by' => Auth::id() ?? 1, // Fallback for tests
                    'status' => 'Active',
                ]);
            }

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::CONFIRMED->value,
                'changed_by' => Auth::id() ?? 1,
                'remarks' => 'Booking confirmed and driver assigned.',
            ]);

            return $booking;
        });

        if ($driverId) {
            $this->mailNotifications->notifyDriverAssigned($booking);
        }

        return $booking;
    }

    public function cancelBooking(Booking $booking)
    {
        return DB::transaction(function () use ($booking) {
            $oldStatus = $booking->status;

            $booking->update([
                'status' => BookingStatus::CANCELLED->value,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::CANCELLED->value,
                'changed_by' => Auth::id() ?? 1,
                'remarks' => 'Booking cancelled.',
            ]);

            return $booking;
        });
    }

    /**
     * The driver starts the ride. He must give the odometer reading he sees on
     * the meter and a photo of it, so the admin has proof of the vehicle's
     * state and mileage at pickup, and the booking moves to Trip Started.
     *
     * @throws \Exception when the ride is not in a state that can be started
     */
    public function startTrip(Booking $booking, int $odometerKm, string $odometerPhoto, ?int $changedBy = null)
    {
        if (! $booking->canStartTrip()) {
            throw new \Exception('This ride cannot be started from its current status ('.$booking->displayStatus().').');
        }

        return DB::transaction(function () use ($booking, $odometerKm, $odometerPhoto, $changedBy) {
            $oldStatus = $booking->status;

            $booking->update([
                'status' => BookingStatus::TRIP_STARTED->value,
                'start_odometer_km' => $odometerKm,
                'start_odometer_photo' => $odometerPhoto,
                'trip_started_at' => now(),
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus->value,
                'new_status' => BookingStatus::TRIP_STARTED->value,
                'changed_by' => $changedBy,
                'remarks' => 'Trip started by the driver. Odometer at start: '.number_format($odometerKm).' km.',
            ]);

            return $booking;
        });
    }

    /**
     * Mark the trip as completed and relocate the vehicle and driver to the
     * drop city, so they become available from there (e.g. a Jaipur ->
     * Udaipur trip makes them available from Udaipur afterwards).
     *
     * When the driver closes the ride himself the closing odometer reading and
     * photo are stored as well, so the total distance of the ride is the
     * difference between the two readings. An admin can still complete a ride
     * without them.
     *
     * Whenever both readings are in, the amount of the ride is worked out from
     * that distance and the rate card of the vehicle and stored with it.
     */
    public function completeTrip(Booking $booking, $adminId, ?int $endOdometerKm = null, ?string $endOdometerPhoto = null)
    {
        if ($endOdometerKm !== null
            && $booking->start_odometer_km !== null
            && $endOdometerKm < (int) $booking->start_odometer_km) {
            throw new \Exception(
                'The closing odometer reading ('.number_format($endOdometerKm).' km) cannot be lower than the reading at the start ('
                .number_format((int) $booking->start_odometer_km).' km).'
            );
        }

        return DB::transaction(function () use ($booking, $adminId, $endOdometerKm, $endOdometerPhoto) {
            $oldStatus = $booking->status;

            $attributes = ['status' => BookingStatus::TRIP_COMPLETED->value];

            if ($endOdometerKm !== null) {
                $attributes['end_odometer_km'] = $endOdometerKm;
                $attributes['end_odometer_photo'] = $endOdometerPhoto;
                $attributes['trip_ended_at'] = now();
            }

            $booking->fill($attributes);

            // The bill is worked out while the readings are at hand and stored
            // with the figures it came out of, so a later change to the
            // vehicle's rate card cannot rewrite a closed bill.
            $tripDistanceKm = $booking->tripDistanceKm();

            if ($tripDistanceKm !== null) {
                $fare = $this->tripFare->calculate($booking, $tripDistanceKm);

                if ($fare !== null) {
                    $booking->fill($fare);
                }
            }

            $booking->save();

            if ($booking->vehicle_id && $booking->drop_city_id) {
                Vehicle::whereKey($booking->vehicle_id)->update(['city_id' => $booking->drop_city_id]);
            }

            if ($booking->driver_id && $booking->drop_city_id) {
                Driver::whereKey($booking->driver_id)->update(['current_city_id' => $booking->drop_city_id]);
            }

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::TRIP_COMPLETED->value,
                'changed_by' => $adminId,
                'remarks' => $this->completionRemarks($booking)
                    .' Vehicle and driver relocated to '.($booking->dropCity->name ?? 'the drop city').'.',
            ]);

            return $booking;
        });
    }

    /**
     * Close the ride from the driver's phone. The closing odometer reading and
     * photo are the proof of the distance covered, so the trip total comes out
     * of the difference between the two readings.
     *
     * @throws \Exception when the ride is not running or the reading is lower
     *                    than the one recorded at the start
     */
    public function endTrip(Booking $booking, int $endOdometerKm, string $odometerPhoto, ?int $changedBy = null)
    {
        if (! $booking->canEndTrip()) {
            throw new \Exception('This ride cannot be ended from its current status ('.$booking->displayStatus().').');
        }

        return $this->completeTrip($booking, $changedBy, $endOdometerKm, $odometerPhoto);
    }

    /**
     * The odometer and money part of the trip-completed history line.
     */
    protected function completionRemarks(Booking $booking): string
    {
        $remarks = 'Trip completed.';

        if ($booking->start_odometer_km !== null && $booking->end_odometer_km !== null) {
            $remarks .= ' Odometer at start: '.number_format((int) $booking->start_odometer_km)
                .' km, at end: '.number_format((int) $booking->end_odometer_km)
                .' km, total distance: '.number_format((int) $booking->tripDistanceKm()).' km.';
        } elseif ($booking->end_odometer_km !== null) {
            $remarks .= ' Odometer at end: '.number_format((int) $booking->end_odometer_km).' km.';
        }

        if ($booking->hasTripFare()) {
            $remarks .= ' Amount billed: '.number_format((float) $booking->total_amount, 2)
                .' ('.((int) $booking->billed_days).' day(s) at '.number_format((float) $booking->billed_price_per_day, 2)
                .' covering '.number_format((int) $booking->billed_included_km).' km';

            if ((int) $booking->extra_km > 0) {
                $remarks .= ', plus '.number_format((int) $booking->extra_km).' extra km at '
                    .number_format((float) $booking->billed_price_per_km, 2);
            }

            $remarks .= ').';
        }

        return $remarks;
    }

    /**
     * Guard: a driver who selected preferred cities must include the
     * booking's drop city, otherwise he cannot be assigned to that trip.
     * Drivers with no preference at all remain assignable everywhere.
     *
     * @throws \Exception
     */
    protected function ensureDriverWillingToGoTo(Booking $booking, $driverId): void
    {
        if (empty($driverId) || empty($booking->drop_city_id)) {
            return;
        }

        $driver = Driver::with('preferredCities')->find($driverId);

        if (! $driver) {
            return;
        }

        if ($driver->preferredCities->isNotEmpty()
            && ! $driver->preferredCities->contains('id', (int) $booking->drop_city_id)) {
            $dropCity = $booking->dropCity?->name ?? 'the drop city';

            throw new \Exception("Driver {$driver->name} is not willing to go to {$dropCity}.");
        }
    }
}
