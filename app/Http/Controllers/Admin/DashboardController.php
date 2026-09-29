<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Vehicle;
use App\Services\TripFareCalculator;

class DashboardController extends Controller
{
    public function index(TripFareCalculator $calculator)
    {
        // What the finished rides are worth. Every ride a driver closed carries
        // the distance it covered between its two odometer readings and the
        // amount billed from the rate card of the vehicle that drove it, so a
        // single pass over them gives the totals for the whole business.
        $completed = Booking::completed()
            ->selectRaw('COUNT(*) as rides, COALESCE(SUM(total_amount), 0) as amount, COALESCE(SUM(end_odometer_km - start_odometer_km), 0) as distance')
            ->first();

        // A ride closed before its vehicle had a rate card was never billed, yet
        // the rate card the vehicle carries today still prices it. Those rides
        // are added in so this total is the same money the completed rides page
        // adds up.
        $unbilled = Booking::completed()
            ->whereNull('total_amount')
            ->with('vehicle')
            ->get(['id', 'vehicle_id', 'pickup_date', 'drop_date', 'start_odometer_km', 'end_odometer_km']);

        $metrics = [
            'total_bookings' => Booking::count(),
            'pending_bookings' => Booking::where('status', BookingStatus::PENDING->value)->count(),
            'active_vehicles' => Vehicle::where('status', VehicleStatus::AVAILABLE->value)->count(),
            'available_drivers' => Driver::where('status', 'Available')->count(),
            'driver_rejections' => DriverAssignment::rejected()->count(),
            'completed_rides' => (int) $completed->rides,
            'total_trip_price' => round((float) $completed->amount + $unbilled->sum(
                fn (Booking $ride) => $calculator->fareFor($ride)['total_amount'] ?? 0.0
            ), 2),
            'total_trip_km' => (int) $completed->distance,
        ];

        $recentBookings = Booking::with(['pickupCity', 'dropCity'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Rides a driver refused (or never answered). The assignment row keeps
        // both the driver and his reason, because the booking itself is freed
        // for reassignment the moment it is refused.
        $recentRejections = DriverAssignment::rejected()
            ->with(['booking.pickupCity', 'booking.dropCity', 'driver'])
            ->take(5)
            ->get();

        return view('dashboard', compact('metrics', 'recentBookings', 'recentRejections'));
    }
}
