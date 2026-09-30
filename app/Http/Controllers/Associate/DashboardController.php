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

/**
 * The associate's dashboard counts only what he owns: the rides the admin
 * assigned to him and the fleet, drivers and services he created himself.
 */
class DashboardController extends Controller
{
    /**
     * Show the associate dashboard. Every metric covers his own records only.
     */
    public function index(): View
    {
        $user = auth()->user();
        $associateId = $user->id;

        $metrics = [
            'total_bookings' => Booking::ownedByAssociate($associateId)->count(),
            'pending_bookings' => Booking::ownedByAssociate($associateId)->where('status', BookingStatus::PENDING->value)->count(),
            'ongoing_bookings' => Booking::ownedByAssociate($associateId)->ongoing()->count(),
            'active_vehicles' => Vehicle::ownedByAssociate($associateId)->where('status', VehicleStatus::AVAILABLE->value)->count(),
            'available_drivers' => Driver::ownedByAssociate($associateId)->where('status', 'Available')->count(),
            'services' => ServiceType::ownedByAssociate($associateId)->count(),
        ];

        $recentBookings = Booking::ownedByAssociate($associateId)
            ->with(['pickupCity', 'dropCity'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $cities = $user->assignedCities()->orderBy('name')->get();

        return view('associate.dashboard', [
            'metrics' => $metrics,
            'recentBookings' => $recentBookings,
            'cities' => $cities,
            // How much the associate owns in total, as opposed to how much of it
            // happens to be free right now. The two numbers answer different
            // questions and the banner shows both.
            'owned' => [
                'vehicles' => Vehicle::ownedByAssociate($associateId)->count(),
                'drivers' => Driver::ownedByAssociate($associateId)->count(),
                'services' => ServiceType::ownedByAssociate($associateId)->count(),
            ],
            // Nothing at all to show, not even an empty-state hint that the
            // associate has work waiting for him.
            'hasAnything' => $metrics['total_bookings'] > 0
                || $metrics['active_vehicles'] > 0
                || $metrics['available_drivers'] > 0
                || $metrics['services'] > 0,
        ]);
    }
}
