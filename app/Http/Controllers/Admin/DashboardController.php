<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Vehicle;

class DashboardController extends Controller
{
    public function index()
    {
        $metrics = [
            'total_bookings' => Booking::count(),
            'pending_bookings' => Booking::where('status', 'PENDING')->count(),
            'active_vehicles' => Vehicle::where('status', VehicleStatus::AVAILABLE->value)->count(),
            'available_drivers' => Driver::where('status', 'Available')->count(),
        ];

        $recentBookings = Booking::with(['pickupCity', 'dropCity'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', compact('metrics', 'recentBookings'));
    }
}
