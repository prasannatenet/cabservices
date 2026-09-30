<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\RejectionSource;
use App\Http\Controllers\Concerns\FiltersByAssociate;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\City;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\TripFareCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    use FiltersByAssociate;

    public function index(Request $request)
    {
        $bookings = Booking::with(['pickupCity', 'dropCity', 'driverAssignment.driver', 'associate'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('booking_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                // "Driver Rejected" is a label rather than a stored status: both
                // rejections share BookingStatus::Rejected and differ only in
                // who caused it, so filter on the source as well.
                if ($request->query('status') === Booking::DRIVER_REJECTED_LABEL) {
                    $query->rejectedByDriver();

                    return;
                }

                $query->where('status', $request->query('status'));
            })
            ->when($request->filled('pickup_city_id'), fn ($query) => $query->where('pickup_city_id', $request->query('pickup_city_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('pickup_date', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('pickup_date', '<=', $request->query('date_to')))
            ->when($request->filled('associate'), function ($query) use ($request) {
                // Lets the admin see only the rides one associate is running, or
                // the ones still running themselves.
                $owner = $request->query('associate');

                $owner === FiltersByAssociate::ADMIN_OWNER
                    ? $query->ownedByAssociate(null)
                    : $query->ownedByAssociate((int) $owner);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'cities' => City::orderBy('name')->get(),
            // The same list the fleet and driver filters offer.
            ...$this->associateFilterOptions(),
            // The plain "Rejected" option keeps every rejected ride, the extra
            // option narrows it down to the ones the driver refused.
            'statuses' => [
                ...array_map(fn (BookingStatus $status) => $status->value, BookingStatus::cases()),
                Booking::DRIVER_REJECTED_LABEL,
            ],
        ]);
    }

    public function show(Booking $booking, TripFareCalculator $calculator)
    {
        $booking->load([
            'pickupCity',
            'dropCity',
            'serviceType',
            'associate',
            // Everything the detail panels read, so opening the page does not
            // fire a query per field.
            'vehicle.images',
            'vehicle.category',
            'vehicle.city',
            'vehicle.associate',
            'driver.currentCity',
            'driver.associate',
            'driverAssignment.driver',
            'driverAssignments.driver',
            'rideExpenses.driver',
        ]);

        // Only what is actually standing in the pickup city can be put on this
        // ride, so the admin is never offered a vehicle or driver from a city
        // that cannot serve it.
        $own = $this->assignableIn($booking, isAssociateOwned: false);
        $associates = $this->assignableIn($booking, isAssociateOwned: true);

        return view('admin.bookings.show', [
            'booking' => $booking,
            'availableDrivers' => $own['drivers'],
            'availableVehicles' => $own['vehicles'],
            'associateDrivers' => $associates['drivers'],
            'associateVehicles' => $associates['vehicles'],
            // Everything the money panel shows, worked out in one place.
            'money' => $booking->moneySummary($calculator),
            // The checkbox is only worth showing when this city actually has an
            // associate with something to offer.
            'pickupCityHasAssociate' => $associates['drivers']->isNotEmpty()
                || $associates['vehicles']->isNotEmpty(),
        ]);
    }

    /**
     * Everything that may be put on this ride, in one of the two halves the form
     * offers it in: the admin's own resources, or an associate's.
     *
     * Both halves are drawn from the pickup city only, and the resource already
     * on the ride is kept in whichever half it belongs to, so a ride driven by an
     * associate's driver comes back with the checkbox ticked rather than looking
     * as though the driver had been dropped.
     *
     * @return array{drivers: Collection<int, Driver>, vehicles: Collection<int, Vehicle>}
     */
    private function assignableIn(Booking $booking, bool $isAssociateOwned): array
    {
        $pickupCityId = (int) $booking->pickup_city_id;

        $drivers = Driver::with(['currentCity', 'preferredCities', 'associate'])
            ->inCities([$pickupCityId])
            ->where('status', 'Available')
            ->willingToGoTo($booking->drop_city_id)
            ->when(
                $isAssociateOwned,
                fn ($query) => $query->whereNotNull('associate_id'),
                fn ($query) => $query->whereNull('associate_id'),
            )
            ->orderBy('name')
            ->get();

        $vehicles = Vehicle::with(['images', 'category', 'city', 'associate'])
            ->inCities([$pickupCityId])
            ->where('status', 'Available')
            ->when(
                $isAssociateOwned,
                fn ($query) => $query->whereNotNull('associate_id'),
                fn ($query) => $query->whereNull('associate_id'),
            )
            ->orderBy('name')
            ->get();

        return [
            'drivers' => $drivers
                ->merge($this->intoHalf($booking->driver, Driver::class, $isAssociateOwned))
                ->unique('id')
                ->values(),
            'vehicles' => $vehicles
                ->merge($this->intoHalf($booking->vehicle, Vehicle::class, $isAssociateOwned))
                ->unique('id')
                ->values(),
        ];
    }

    /**
     * The value chosen in one of the two dropdowns, or null when that dropdown
     * was left on its "-- none --" option.
     *
     * @param  array<string, mixed>  $validated
     */
    private function chosenId(array $validated, string $key): ?int
    {
        $value = $validated[$key] ?? null;

        return blank($value) ? null : (int) $value;
    }

    /**
     * The resource already on the ride, but only for the half it belongs to.
     *
     * An admin-owned resource belongs to the admin's half and an associate-owned
     * one to the associate's, so the two never cross: asking for the other half
     * returns nothing. Without this the admin's own vehicle and driver, which are
     * carried over so editing cannot silently drop them, would show up inside the
     * associate dropdowns.
     *
     * @param  class-string<Model>  $modelClass
     * @return Collection<int, Model>
     */
    private function intoHalf(?Model $resource, string $modelClass, bool $isAssociateOwned): Collection
    {
        if (! $resource instanceof $modelClass) {
            return (new Driver)->newCollection();
        }

        // isAdminCreated() is true for the admin's own records, which is the
        // opposite of the flag naming the associate's half.
        if ($resource->isAdminCreated() === $isAssociateOwned) {
            return $resource->newCollection();
        }

        return $resource->newCollection([$resource]);
    }

    public function update(Request $request, Booking $booking, BookingService $bookingService)
    {
        // A finished trip is a permanent record of what happened, so nothing on
        // it can be saved any more, not even by the admin.
        if ($booking->isLocked()) {
            return back()->with('error', $booking->lockedMessage());
        }

        // The form offers two dropdowns per resource: the admin's own, and the
        // associate's behind a checkbox. Whichever one carries a value is the
        // real choice, so both are collapsed into a single driver and vehicle
        // here and everything below works with those.
        $validated = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'driver_id' => 'nullable|exists:drivers,id',
            'associate_driver_id' => 'nullable|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'associate_vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        // An empty select posts "", not null, so each half is checked for a real
        // value: picking from one dropdown wins over the other being left blank.
        $chosenDriverId = $this->chosenId($validated, 'associate_driver_id') ?? $this->chosenId($validated, 'driver_id');
        $chosenVehicleId = $this->chosenId($validated, 'associate_vehicle_id') ?? $this->chosenId($validated, 'vehicle_id');

        // The dropdowns only list what stands in the pickup city, so this repeats
        // that rule server-side rather than trusting the form.
        if ($chosenDriverId && ! Driver::whereKey($chosenDriverId)->inCities([(int) $booking->pickup_city_id])->exists()) {
            return back()->with(
                'error',
                'That driver is not based in '.($booking->pickupCity->name ?? 'the pickup city').'.'
            );
        }

        if ($chosenVehicleId && ! Vehicle::whereKey($chosenVehicleId)->inCities([(int) $booking->pickup_city_id])->exists()) {
            return back()->with(
                'error',
                'That vehicle is not based in '.($booking->pickupCity->name ?? 'the pickup city').'.'
            );
        }

        $validated['driver_id'] = $chosenDriverId;
        $validated['vehicle_id'] = $chosenVehicleId;

        // Block assigning a driver who is not willing to go to the drop city.
        // (UI hides such drivers; this stops forged POST requests.)
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

            // Swapping the vehicle can move the ride to another associate, or
            // back to the admin when the new vehicle has no associate.
            $booking->syncAssociateFromAssignment();
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
                // Choosing Rejected here rejects the ride on the admin's behalf,
                // while moving to any other status retires the earlier refusal,
                // so the label always follows the status the admin just picked.
                $booking->rejection_source = $validated['status'] === BookingStatus::REJECTED->value
                    ? RejectionSource::Admin
                    : null;
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

                // Keep the ride with the associate who owns the assigned driver,
                // so a manual status change never leaves it on the old owner.
                $booking->syncAssociateFromAssignment();

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
}
