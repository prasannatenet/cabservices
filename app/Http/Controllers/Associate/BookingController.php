<?php

namespace App\Http\Controllers\Associate;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\City;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Collection;
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

        $validated = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
            // Only this associate's own drivers and vehicles may be assigned, so
            // a ride can never be pushed onto someone else's resource.
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

        $statusChanged = $validated['status'] !== $booking->status->value;
        $driverChanged = (isset($validated['driver_id']) && $booking->driver_id != $validated['driver_id']);
        $vehicleChanged = (isset($validated['vehicle_id']) && $booking->vehicle_id != $validated['vehicle_id']);

        if ($vehicleChanged) {
            $booking->vehicle_id = $validated['vehicle_id'];
            $booking->save();
        }

        // If driver changed but status is not changing, just assign the driver
        if ($driverChanged && ! $statusChanged) {
            $bookingService->assignDriver($booking, $validated['driver_id'], Auth::id() ?? 1);
        }

        if ($statusChanged) {
            if ($validated['status'] === BookingStatus::CONFIRMED->value && $booking->status === BookingStatus::PENDING) {
                $driverId = $validated['driver_id'] ?? $booking->driver_id;
                if (! $driverId) {
                    return back()->with('error', 'A driver must be assigned to confirm the booking.');
                }
                try {
                    $bookingService->confirmBooking($booking, $driverId);

                    return back()->with('success', 'Booking Confirmed successfully.');
                } catch (\Exception $e) {
                    return back()->with('error', $e->getMessage());
                }
            } elseif ($validated['status'] === BookingStatus::TRIP_COMPLETED->value && $booking->status !== BookingStatus::TRIP_COMPLETED) {
                try {
                    $bookingService->completeTrip($booking, Auth::id() ?? 1);

                    return back()->with('success', 'Trip Completed. Vehicle and driver are now available from '.($booking->dropCity->name ?? 'the drop city').'.');
                } catch (\Exception $e) {
                    return back()->with('error', $e->getMessage());
                }
            } elseif ($validated['status'] === BookingStatus::CANCELLED->value && $booking->status !== BookingStatus::CANCELLED) {
                try {
                    $bookingService->cancelBooking($booking);

                    return back()->with('success', 'Booking Cancelled successfully.');
                } catch (\Exception $e) {
                    return back()->with('error', $e->getMessage());
                }
            } else {
                $oldStatus = $booking->status;
                $booking->status = $validated['status'];
                if ($driverChanged) {
                    $booking->driver_id = $validated['driver_id'];
                    DriverAssignment::create([
                        'booking_id' => $booking->id,
                        'driver_id' => $booking->driver_id,
                        'vehicle_id' => $booking->vehicle_id,
                        'assigned_by' => Auth::id() ?? 1,
                        'status' => 'Active',
                    ]);
                }
                $booking->save();

                BookingStatusHistory::create([
                    'booking_id' => $booking->id,
                    'old_status' => $oldStatus->value,
                    'new_status' => $validated['status'],
                    'changed_by' => Auth::id() ?? 1,
                    'remarks' => 'Status manually updated.',
                ]);
            }
        }

        return back()->with('success', 'Booking updated successfully.');
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
