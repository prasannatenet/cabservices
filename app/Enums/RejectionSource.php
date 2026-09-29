<?php

namespace App\Enums;

/**
 * Who is responsible for a booking sitting in the Rejected state.
 *
 * A booking becomes Rejected in two different ways: the admin rejects the
 * request outright, or the assigned driver refuses it (or never answers within
 * the six hour response window). Both share the same BookingStatus, so this
 * records which of the two happened and lets the admin screens label the ride
 * "Driver Rejected" instead of plain "Rejected".
 */
enum RejectionSource: string
{
    case Driver = 'Driver';
    case Admin = 'Admin';

    /**
     * The label shown in the admin booking screens.
     */
    public function label(): string
    {
        return match ($this) {
            self::Driver => 'Driver Rejected',
            self::Admin => 'Rejected',
        };
    }
}
