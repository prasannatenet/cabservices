<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'Pending';
    case APPROVED = 'Approved';
    case REJECTED = 'Rejected';
    case DRIVER_ASSIGNED = 'Driver Assigned';
    case CONFIRMED = 'Confirmed';
    case TRIP_STARTED = 'Trip Started';
    case TRIP_COMPLETED = 'Trip Completed';
    case CANCELLED = 'Cancelled';
}
