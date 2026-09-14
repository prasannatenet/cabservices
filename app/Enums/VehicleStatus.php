<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case AVAILABLE = 'Available';
    case BOOKED = 'Booked';
    case ASSIGNED = 'Assigned';
    case MAINTENANCE = 'Maintenance';
    case INACTIVE = 'Inactive';
}
