<?php

namespace App\Http\Controllers\Associate;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Rides are handled by the associate the admin handed them to.
 *
 * A customer booking a pickup in one of his cities does not make the ride his:
 * it stays with the admin until the admin assigns one of the associate's own
 * drivers or vehicles to it. From that moment the ride appears in this panel and
 * the associate can confirm, reassign and complete it exactly like the admin.
 */
class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = Booking::with(['pickupCity', 'dropCity'])
            ->ownedByAssociate(auth()->id())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('booking_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('pickup_city_id'), fn ($query) => $query->where('pickup_city_id', $request->query('pickup_city_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('pickup_date', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('pickup_date', '<=', $request->query('date_to')))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('associate.bookings.index', [
            'bookings' => $bookings,
            'cities' => $this->assignedCities(),
            'statuses' => array_map(fn (BookingStatus $status) => $status->value, BookingStatus::cases()),
        ]);
    }

    public function show(Booking $booking): View
    {
        $this->authorizeBooking($booking);

        $booking->load(['pickupCity', 'dropCity', 'vehicle', 'serviceType', 'driverAssignment.driver']);

        $associateId = auth()->id();

        // Only this associate's own drivers and vehicles may be put on one of his
        // rides, so the dropdowns offer his resources rather than everyone in
        // the pickup city.
        $availableDrivers = Driver::with(['currentCity', 'preferredCities'])
            ->ownedByAssociate($associateId)
            ->where('status', 'Available')
            ->willingToGoTo($booking->drop_city_id)
            ->orderBy('name')
            ->get()
            // The driver already on the ride is carried over so that editing the
            // ride cannot silently drop him, but only when he really is this
            // associate's: the admin's own driver must never appear here.
            ->merge($this->assignedDriverWhenItIsMine($booking, $associateId))
            ->unique('id')
            ->values();

        $availableVehicles = Vehicle::ownedByAssociate($associateId)
            ->with(['images', 'category', 'city'])
            ->where('status', 'Available')
            ->get();

        return view('associate.bookings.show', compact('booking', 'availableDrivers', 'availableVehicles'));
    }

    public function update(Request $request, Booking $booking, BookingService $bookingService)
    {
        $this->authorizeBooking($booking);

        // Once the trip has ended it is frozen for everyone, the associate
        // included: the ride, its driver and its fare are the record of what
        // actually happened.
        if ($booking->isLocked()) {
            return back()->with('error', $booking->lockedMessage());
        }

        $associateId = auth()->id();

        // The status is not posted here: choosing a driver or a vehicle is an
        // assignment, and the booking moves to Driver Assigned on its own from
        // there. Cancelling, rejecting and completing are separate endpoints.
        // Only this associate's own drivers and vehicles may be assigned, so a ride can
        // never be pushed onto someone else's resource.
        $validated = $request->validate([
            'driver_id' => [
                'nullable',
                Rule::exists('drivers', 'id')->where('associate_id', $associateId),
            ],
            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where('associate_id', $associateId),
            ],
        ]);

        // Block assigning a driver who is not willing to go to the drop city.
        if (! empty($validated['driver_id'])) {
            $driver = Driver::with('preferredCities')->find($validated['driver_id']);

            if ($driver && $driver->preferredCities->isNotEmpty()
                && ! $driver->preferredCities->contains('id', $booking->drop_city_id)) {
                return back()->with(
                    'error',
                    'Driver '.$driver->name.' is not willing to go to '.($booking->dropCity->name ?? 'the drop city').'. Please select a driver who prefers that city.'
                );
            }
        }

        $driverChanged = (isset($validated['driver_id']) && $booking->driver_id != $validated['driver_id']);
        $vehicleChanged = (isset($validated['vehicle_id']) && $booking->vehicle_id != $validated['vehicle_id']);

        if ($vehicleChanged) {
            $booking->vehicle_id = $validated['vehicle_id'];
            $booking->save();
        }

        if ($driverChanged) {
            try {
                // Assigning puts the ride on Driver Assigned and opens the
                // driver's six hour window to accept or refuse it.
                $bookingService->assignDriver($booking, $validated['driver_id'], Auth::id() ?? 1);
            } catch (\Exception $exception) {
                return back()->with('error', $exception->getMessage());
            }

            return back()->with('success', 'Driver assigned. He has 6 hours to accept or refuse this ride.');
        }

        return back()->with('success', $vehicleChanged
            ? 'Vehicle updated successfully.'
            : 'Nothing to change.');
    }

    /**
     * Call off one of this associate's own rides.
     */
    public function cancel(Booking $booking, BookingService $bookingService): RedirectResponse
    {
        $this->authorizeBooking($booking);

        if ($booking->isLocked()) {
            return back()->with('error', $booking->lockedMessage());
        }

        try {
            $bookingService->cancelBooking($booking);
        } catch (\Exception $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Booking cancelled. The driver and vehicle have been released.');
    }

    /**
     * Turn down one of this associate's own rides, with the reason on record.
     */
    public function reject(Request $request, Booking $booking, BookingService $bookingService): RedirectResponse
    {
        $this->authorizeBooking($booking);

        if ($booking->isLocked()) {
            return back()->with('error', $booking->lockedMessage());
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Please give the reason for rejecting this booking.',
            'rejection_reason.min' => 'Please give a little more detail (at least 5 characters).',
        ]);

        try {
            $bookingService->rejectBooking($booking, Auth::id() ?? 1, $validated['rejection_reason']);
        } catch (\Exception $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Booking rejected. The driver and vehicle have been released.');
    }

    /**
     * Close one of this associate's own rides.
     *
     * Only a ride that is actually running may be completed, which the state
     * machine enforces, so a ride that was never driven cannot be closed here.
     */
    public function complete(Booking $booking, BookingService $bookingService): RedirectResponse
    {
        $this->authorizeBooking($booking);

        if ($booking->isLocked()) {
            return back()->with('error', $booking->lockedMessage());
        }

        try {
            $bookingService->completeTrip($booking, Auth::id() ?? 1);
        } catch (\Exception $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Trip Completed. Vehicle and driver are now available from '.($booking->dropCity->name ?? 'the drop city').'.');
    }

    /**
     * The driver already on the ride, but only when he belongs to this associate.
     *
     * A ride can be handed back to the admin while still carrying a driver the
     * admin assigned, and that driver is not this associate's to see, so he is
     * left out of the list entirely.
     *
     * @return Collection<int, Driver>
     */
    private function assignedDriverWhenItIsMine(Booking $booking, int $associateId): Collection
    {
        if (! $booking->driver_id) {
            return (new Driver)->newCollection();
        }

        $driver = Driver::with(['currentCity', 'preferredCities'])
            ->ownedByAssociate($associateId)
            ->whereKey($booking->driver_id)
            ->first();

        return $driver
            ? $driver->newCollection([$driver])
            : (new Driver)->newCollection();
    }

    /**
     * Ids of the cities this associate may create a record in.
     *
     * Ownership decides which rides he sees; his cities only decide where a new
     * record may be based.
     *
     * @return list<int>
     */
    private function cityIds(): array
    {
        return Auth::user()->assignedCityIds();
    }

    /**
     * The cities shown in every dropdown of this panel.
     *
     * @return Collection<int, City>
     */
    private function assignedCities()
    {
        return Auth::user()->assignedCities()->orderBy('name')->get();
    }

    /**
     * A ride belongs to the associate the admin assigned it to, not to whoever
     * manages the city it starts in. A ride that has not been assigned to
     * anyone is the admin's alone.
     */
    private function authorizeBooking(Booking $booking): void
    {
        abort_unless(
            $booking->isOwnedByAssociate(Auth::id()),
            403,
            'This booking has not been assigned to you.'
        );
    }
}
