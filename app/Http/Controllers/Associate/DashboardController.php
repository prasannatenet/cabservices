<?php

namespace App\Http\Controllers\Associate;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the associate dashboard. Every metric covers the cities he manages only.
     */
    public function index(): View
    {
        $user = auth()->user();
        $cityIds = $user->assignedCityIds();

        $metrics = [
            'total_bookings' => Booking::inCities($cityIds)->count(),
            'pending_bookings' => Booking::inCities($cityIds)->where('status', BookingStatus::PENDING->value)->count(),
            'active_vehicles' => Vehicle::inCities($cityIds)->where('status', VehicleStatus::AVAILABLE->value)->count(),
            'available_drivers' => Driver::inCities($cityIds)->where('status', 'Available')->count(),
            'services' => ServiceType::inCities($cityIds)->count(),
        ];

        $recentBookings = Booking::inCities($cityIds)
            ->with(['pickupCity', 'dropCity'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('associate.dashboard', [
            'metrics' => $metrics,
            'recentBookings' => $recentBookings,
            'cities' => $user->assignedCities()->orderBy('name')->get(),
        ]);
    }
}
