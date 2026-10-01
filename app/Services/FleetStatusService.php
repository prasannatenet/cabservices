<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\DriverStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Vehicle;

/**
 * Keeps the driver and vehicle standing on a booking in step with the booking
 * itself, so the admin never sees an "Available" driver who is actually on a
 * ride and never assigns the same one to two rides at once.
 *
 * The statuses this service writes are the ones the admin cannot manage by
 * hand: Driver::canManageAvailability() refuses to let a driver toggle himself
 * out of Assigned or On Trip, precisely because the booking flow owns them.
 *
 * Releasing is deliberately careful. A resource is only handed back when the
 * booking flow was the one that took it, so a driver the admin put on On Leave
 * or Inactive is never quietly turned Available again behind his back.
 */
class FleetStatusService
{
    /**
     * The statuses this service is allowed to give back, because they are the
     * ones it set in the first place.
     */
    private const BUSY_STATUSES = [
        DriverStatus::ASSIGNED->value,
        DriverStatus::ON_TRIP->value,
    ];

    /**
     * A driver and vehicle have been put on the booking and are not driving it
     * yet.
     */
    public function markAssigned(Booking $booking): void
    {
        $this->driver($booking)?->update(['status' => DriverStatus::ASSIGNED->value]);
        $this->vehicle($booking)?->update(['status' => VehicleStatus::ASSIGNED->value]);
    }

    /**
     * The ride has left the pickup point and the driver is behind the wheel.
     */
    public function markOnTrip(Booking $booking): void
    {
        $this->driver($booking)?->update(['status' => DriverStatus::ON_TRIP->value]);

        // A vehicle that is being driven is on the road rather than booked, so
        // it carries the same "out with a customer" meaning as its driver.
        $this->vehicle($booking)?->update(['status' => VehicleStatus::BOOKED->value]);
    }

    /**
     * Hand the driver and vehicle back, so they can be given another ride.
     *
     * Called whenever a booking leaves a ride without one finishing: cancelled,
     * rejected, refused by the driver, or given to somebody else. Only a
     * resource this flow had marked busy is released, so one an admin has set
     * to On Leave, Inactive or Maintenance keeps the status it was given.
     */
    public function release(Booking $booking): void
    {
        $this->releaseDriver($booking);
        $this->releaseVehicle($booking);
    }

    /**
     * The driver currently standing on the booking, if any.
     */
    private function driver(Booking $booking): ?Driver
    {
        return $booking->driver_id ? Driver::find($booking->driver_id) : null;
    }

    /**
     * The vehicle the booking was booked with, if any.
     */
    private function vehicle(Booking $booking): ?Vehicle
    {
        return $booking->vehicle_id ? Vehicle::find($booking->vehicle_id) : null;
    }

    /**
     * Return a driver to Available, unless something else has since taken him
     * off a ride (On Leave, Unavailable, Inactive, or busy on another booking).
     */
    private function releaseDriver(Booking $booking): void
    {
        $driver = $this->driver($booking);

        if (! $driver || ! in_array($driver->status, self::BUSY_STATUSES, true)) {
            return;
        }

        // A driver may be covering more than one booking, so he is only free
        // once no ride of his is still running.
        $stillBusy = $driver->bookings()
            ->whereIn('status', BookingStatus::occupiesResources())
            ->whereKeyNot($booking->getKey())
            ->exists();

        if ($stillBusy) {
            return;
        }

        $driver->update(['status' => DriverStatus::AVAILABLE->value]);
    }

    /**
     * Return a vehicle to Available under the same rule as the driver.
     */
    private function releaseVehicle(Booking $booking): void
    {
        $vehicle = $this->vehicle($booking);

        if (! $vehicle || ! in_array($vehicle->status, [VehicleStatus::ASSIGNED->value, VehicleStatus::BOOKED->value], true)) {
            return;
        }

        $stillBusy = $vehicle->bookings()
            ->whereIn('status', BookingStatus::occupiesResources())
            ->whereKeyNot($booking->getKey())
            ->exists();

        if ($stillBusy) {
            return;
        }

        $vehicle->update(['status' => VehicleStatus::AVAILABLE->value]);
    }
}
