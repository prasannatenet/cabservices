<?php

namespace App\Http\Controllers\Driver;

use App\Enums\AssignmentResponseStatus;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Services\AssignmentResponseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the driver dashboard.
     */
    public function index(): View
    {
        $driver = $this->authDriver();

        $upcomingStatuses = [
            BookingStatus::DRIVER_ASSIGNED->value,
            BookingStatus::CONFIRMED->value,
            BookingStatus::TRIP_STARTED->value,
        ];

        $metrics = [
            'total_rides' => $driver->bookings()->count(),
            'completed_rides' => $driver->bookings()->where('status', BookingStatus::TRIP_COMPLETED->value)->count(),
            'upcoming_rides' => $driver->bookings()->whereIn('status', $upcomingStatuses)->count(),
            'preferred_cities' => $driver->preferredCities()->count(),
        ];

        $upcomingBookings = $driver->bookings()
            ->with(['pickupCity', 'dropCity'])
            ->whereIn('status', $upcomingStatuses)
            ->orderBy('pickup_date')
            ->orderBy('pickup_time')
            ->take(5)
            ->get();

        return view('driver.dashboard', [
            'driver' => $driver->load('currentCity'),
            'metrics' => $metrics,
            'upcomingBookings' => $upcomingBookings,
            'pendingAssignments' => $driver->pendingAssignments()
                ->with('booking.pickupCity', 'booking.dropCity')
                ->orderBy('response_deadline')
                ->get(),
        ]);
    }

    /**
     * Toggle the driver between Available and Unavailable.
     * The change is instantly reflected in the admin panel.
     */
    public function toggleAvailability(): RedirectResponse
    {
        $driver = $this->authDriver();

        if (! $driver->toggleAvailability()) {
            return back()->with('error', 'Your availability is managed by the admin for your current status ('.$driver->status.').');
        }

        return back()->with('success', 'You are now '.$driver->fresh()->status.'.');
    }

    /**
     * Display the driver's ride history.
     */
    public function rides(Request $request): View
    {
        $driver = $this->authDriver();

        $rides = $driver->bookings()
            ->with(['pickupCity', 'dropCity', 'vehicle'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->orderBy('pickup_date', 'desc')
            ->orderBy('pickup_time', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('driver.rides', [
            'driver' => $driver,
            'rides' => $rides,
            'statuses' => array_map(fn (BookingStatus $status) => $status->value, BookingStatus::cases()),
        ]);
    }

    /**
     * The assignments currently waiting for this driver's answer.
     */
    public function pendingAssignments(): View
    {
        $driver = $this->authDriver();

        $assignments = $driver->driverAssignments()
            ->with(['booking.pickupCity', 'booking.dropCity', 'booking.vehicle', 'booking.serviceType'])
            ->where('response_status', AssignmentResponseStatus::Pending->value)
            ->where('response_deadline', '>', now())
            ->latest('response_deadline')
            ->get();

        return view('driver.assignments', [
            'driver' => $driver,
            'assignments' => $assignments,
        ]);
    }

    /**
     * The driver confirms he will take the ride.
     */
    public function acceptAssignment(
        DriverAssignment $assignment,
        AssignmentResponseService $responses
    ): RedirectResponse {
        $this->authorizeAssignment($assignment);

        try {
            $responses->accept($assignment);
        } catch (\Exception $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'You accepted the ride. The customer has been notified.');
    }

    /**
     * The driver refuses the ride and gives the admin a reason.
     */
    public function rejectAssignment(
        Request $request,
        DriverAssignment $assignment,
        AssignmentResponseService $responses
    ): RedirectResponse {
        $this->authorizeAssignment($assignment);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Please tell the admin why you are rejecting this ride.',
            'rejection_reason.min' => 'Please give a little more detail (at least 5 characters).',
        ]);

        try {
            $responses->reject($assignment, $validated['rejection_reason']);
        } catch (\Exception $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'You rejected the ride. The admin has been notified.');
    }

    /**
     * Stop a driver from answering an assignment that belongs to someone else.
     */
    private function authorizeAssignment(DriverAssignment $assignment): void
    {
        abort_unless(
            $assignment->driver_id === $this->authDriver()->id,
            403,
            'This ride was not assigned to you.'
        );
    }

    /**
     * Display the cities (created by admin) the driver can operate in.
     */
    public function cities(): View
    {
        $driver = $this->authDriver();

        $cities = City::where('status', 'Active')->orderBy('name')->get();

        return view('driver.cities', [
            'driver' => $driver,
            'cities' => $cities,
            'selectedCityIds' => $driver->preferredCities()->pluck('cities.id')->all(),
        ]);
    }

    /**
     * Save the cities the driver wants to go to.
     */
    public function syncCities(Request $request): RedirectResponse
    {
        $driver = $this->authDriver();

        $validated = $request->validate([
            'city_ids' => 'nullable|array',
            'city_ids.*' => ['integer', Rule::exists('cities', 'id')],
        ]);

        $driver->preferredCities()->sync($validated['city_ids'] ?? []);

        $count = $driver->preferredCities()->count();

        return redirect()->route('driver.cities')->with('success', 'Your cities have been updated ('.$count.' selected).');
    }

    /**
     * Resolve the driver profile linked to the authenticated user.
     */
    private function authDriver(): Driver
    {
        $driver = Auth::user()->driver;

        abort_unless($driver instanceof Driver, 404, 'No driver profile is linked to this account.');

        return $driver;
    }
}
