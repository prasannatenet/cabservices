<?php

namespace App\Enums;

enum DriverStatus: string
{
    case AVAILABLE = 'Available';
    case ASSIGNED = 'Assigned';
    case ON_TRIP = 'On Trip';
    case ON_LEAVE = 'On Leave';
    case INACTIVE = 'Inactive';
}
