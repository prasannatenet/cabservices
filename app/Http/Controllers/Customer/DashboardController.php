<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The customer panel: the rides the signed in customer booked, and nothing else.
 * Every query is scoped to the account, so a customer can never open another
 * customer's ride even when he guesses its id.
 */
class DashboardController extends Controller
{
    /**
     * Show the rides still to come above the ones that have already run.
     */
    public function index(Request $request): View
    {
        $customer = $request->user();

        $upcomingStatuses = [
            BookingStatus::DRIVER_ASSIGNED->value,
            BookingStatus::CONFIRMED->value,
            BookingStatus::TRIP_STARTED->value,
        ];

        $upcomingBookings = $customer->bookings()
            ->with(['pickupCity', 'dropCity', 'driver', 'vehicle'])
            ->whereIn('status', $upcomingStatuses)
            ->orderBy('pickup_date')
            ->orderBy('pickup_time')
            ->get();

        $pastBookings = $customer->bookings()
            ->with(['pickupCity', 'dropCity', 'driver', 'vehicle'])
            ->whereNotIn('status', $upcomingStatuses)
            ->orderByDesc('pickup_date')
            ->orderByDesc('pickup_time')
            ->get();

        return view('customer.dashboard', [
            'metrics' => [
                'total_rides' => $customer->bookings()->count(),
                'upcoming_rides' => $upcomingBookings->count(),
                'completed_rides' => $customer->bookings()
                    ->where('status', BookingStatus::TRIP_COMPLETED->value)
                    ->count(),
            ],
            'upcomingBookings' => $upcomingBookings,
            'pastBookings' => $pastBookings,
        ]);
    }

    /**
     * Show one ride in full, but only when it belongs to the signed in customer.
     *
     * @throws ModelNotFoundException when the ride
     *                                is not his, so another customer's ride reads as "not found"
     */
    public function show(Request $request, Booking $booking): View
    {
        return view('customer.bookings.show', [
            'booking' => $this->ownBooking($request, $booking),
        ]);
    }

    /**
     * The signed in customer's own copy of this ride.
     */
    protected function ownBooking(Request $request, Booking $booking): Booking
    {
        return $request->user()
            ->bookings()
            ->with([
                'pickupCity',
                'dropCity',
                'serviceType',
                'vehicle',
                'driver',
                'statusHistory',
            ])
            ->whereKey($booking->id)
            ->firstOrFail();
    }
}
