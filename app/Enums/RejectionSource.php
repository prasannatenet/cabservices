<?php

namespace App\Enums;

/**
 * Who is responsible for a booking that was refused or called off.
 *
 * A booking can be turned down on either side: the admin rejects the request
 * outright, or the assigned driver refuses it (or never answers within the six
 * hour response window). Each of those is now a status of its own, so this only
 * records who was responsible for the refusal, for the record.
 */
enum RejectionSource: string
{
    case Driver = 'Driver';
    case Admin = 'Admin';

    /**
     * The status a refusal from this source puts the booking into.
     */
    public function resultingStatus(): BookingStatus
    {
        return match ($this) {
            self::Driver => BookingStatus::DRIVER_REJECTED,
            self::Admin => BookingStatus::REJECTED,
        };
    }
}
