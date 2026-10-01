<?php

namespace App\Enums;

/**
 * The life of a booking, from a customer request to a finished ride.
 *
 * A ride does not move to its next status because somebody picked it from a
 * list: it moves because something happened to it. A driver was put on it, he
 * accepted it or refused it, he opened it with an odometer reading and closed
 * it at the drop point, or the admin cancelled or rejected it. allowedTransitions()
 * spells out which of those may follow which, so an impossible jump (a ride
 * completed without ever being driven) cannot happen even by accident.
 */
enum BookingStatus: string
{
    case PENDING = 'Pending';
    case APPROVED = 'Approved';
    case REJECTED = 'Rejected';
    case DRIVER_REJECTED = 'Driver Rejected';
    case DRIVER_ASSIGNED = 'Driver Assigned';
    case CONFIRMED = 'Confirmed';
    case TRIP_STARTED = 'Trip Started';
    case TRIP_COMPLETED = 'Trip Completed';
    case CANCELLED = 'Cancelled';

    /**
     * The statuses this one may move to, in the order they normally happen.
     *
     * A ride a driver refused or never answered reopens only by being given
     * another driver, and a rejected one likewise, because neither can run. A
     * finished ride is closed for good. Nothing goes back to Pending: a ride
     * that has moved on is a new request, not this one rewound.
     *
     * Confirmed is reachable without going through Driver Assigned because a
     * dispatcher who reaches the driver by phone has the driver's acceptance
     * already and can confirm in one step. Trip Completed is reachable from
     * Confirmed because a driver who drove the ride but never filed the closing
     * reading still has to be closed off by hand. What is never allowed is
     * completing a ride that was never driven: there is no way to Pending.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::APPROVED, self::DRIVER_ASSIGNED, self::CONFIRMED, self::REJECTED, self::CANCELLED],
            self::APPROVED => [self::DRIVER_ASSIGNED, self::CONFIRMED, self::REJECTED, self::CANCELLED],
            // Putting another driver on a ride it already has one for is a real
            // event, so it is allowed and lands back on Driver Assigned, where
            // the new driver has to accept just as the first one did.
            self::DRIVER_ASSIGNED => [self::DRIVER_ASSIGNED, self::CONFIRMED, self::DRIVER_REJECTED, self::REJECTED, self::CANCELLED],
            self::CONFIRMED => [self::DRIVER_ASSIGNED, self::TRIP_STARTED, self::TRIP_COMPLETED, self::CANCELLED],
            self::TRIP_STARTED => [self::TRIP_COMPLETED, self::CANCELLED],
            // A refused or rejected ride is put back in play by handing it to
            // another driver, which moves it to Driver Assigned again.
            self::REJECTED, self::DRIVER_REJECTED => [self::DRIVER_ASSIGNED, self::CANCELLED],
            self::TRIP_COMPLETED, self::CANCELLED => [],
        };
    }

    /**
     * Whether this status may move straight to the given one.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Whether nothing can follow this status, i.e. the ride is over and its
     * record can no longer be changed.
     */
    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * The message shown when a move that the ride's own history does not allow
     * is attempted anyway.
     */
    public function transitionErrorMessage(self $target): string
    {
        if ($target === $this) {
            return "This booking is already {$this->value}.";
        }

        if ($this->isFinal()) {
            return "This booking has ended on {$this->value} and can no longer be changed.";
        }

        $allowed = array_map(fn (self $status) => $status->value, $this->allowedTransitions());

        return sprintf(
            'A booking cannot go from %s to %s. From here it can only move to: %s.',
            $this->value,
            $target->value,
            $allowed === [] ? 'nothing' : implode(', ', $allowed)
        );
    }

    /**
     * The statuses that hold on to a driver and a vehicle: while a booking is
     * in one of them the ride is either about to run or is running, so neither
     * resource may be given to somebody else.
     *
     * AvailabilityService uses the same idea to work out whether a vehicle is
     * free at a given time, and Booking::ongoing() uses it to count the rides
     * still on their way, so all three read from one list.
     *
     * @return list<string>
     */
    public static function occupiesResources(): array
    {
        return [
            self::APPROVED->value,
            self::DRIVER_ASSIGNED->value,
            self::CONFIRMED->value,
            self::TRIP_STARTED->value,
        ];
    }

    /**
     * Whether this status means the ride will not run: refused, rejected or
     * called off. Such a ride stays editable, because the admin reopens it by
     * giving it another driver.
     */
    public function isClosedWithoutRunning(): bool
    {
        return in_array($this, [self::REJECTED, self::DRIVER_REJECTED, self::CANCELLED], true);
    }
}
