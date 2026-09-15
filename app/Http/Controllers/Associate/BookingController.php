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
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Bookings are handled by the associate for the cities he manages: he sees a
 * booking as soon as a customer books a pickup in one of his cities, and can
 * confirm, assign and complete it exactly like the admin.
 */
class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = Booking::with(['pickupCity', 'dropCity'])
            ->inCities($this->cityIds())
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

        $availableDrivers = Driver::inCities($this->cityIds())->where('status', 'Available')->get();
        $availableVehicles = Vehicle::inCities($this->cityIds())->with(['images', 'category', 'city'])->where('status', 'Available')->get();

        return view('associate.bookings.show', compact('booking', 'availableDrivers', 'availableVehicles'));
    }

    public function update(Request $request, Booking $booking, BookingService $bookingService)
    {
        $this->authorizeBooking($booking);

        $cityIds = $this->cityIds();

        $validated = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
            // Only drivers and vehicles of the associate's own cities may be assigned.
            'driver_id' => ['nullable', Rule::exists('drivers', 'id')->whereIn('current_city_id', $cityIds)],
            'vehicle_id' => ['nullable', Rule::exists('vehicles', 'id')->whereIn('city_id', $cityIds)],
        ]);

        $statusChanged = $validated['status'] !== $booking->status->value;
        $driverChanged = (isset($validated['driver_id']) && $booking->driver_id != $validated['driver_id']);
        $vehicleChanged = (isset($validated['vehicle_id']) && $booking->vehicle_id != $validated['vehicle_id']);

        if ($vehicleChanged) {
            $booking->vehicle_id = $validated['vehicle_id'];
            $booking->save();
        }

        // If driver changed but status is not changing, just assign the driver
        if ($driverChanged && ! $statusChanged) {
            $bookingService->assignDriver($booking, $validated['driver_id'], auth()->id() ?? 1);
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
                    $bookingService->completeTrip($booking, auth()->id() ?? 1);

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
                        'assigned_by' => auth()->id() ?? 1,
                        'status' => 'Active',
                    ]);
                }
                $booking->save();

                BookingStatusHistory::create([
                    'booking_id' => $booking->id,
                    'old_status' => $oldStatus->value,
                    'new_status' => $validated['status'],
                    'changed_by' => auth()->id() ?? 1,
                    'remarks' => 'Status manually updated.',
                ]);
            }
        }

        return back()->with('success', 'Booking updated successfully.');
    }

    /**
     * Ids of the cities this associate manages.
     *
     * @return list<int>
     */
    private function cityIds(): array
    {
        return auth()->user()->assignedCityIds();
    }

    /**
     * The cities shown in every dropdown of this panel.
     *
     * @return Collection<int, City>
     */
    private function assignedCities()
    {
        return auth()->user()->assignedCities()->orderBy('name')->get();
    }

    /**
     * A booking belongs to its pickup city, so the associate only manages
     * bookings that start in one of his cities.
     */
    private function authorizeBooking(Booking $booking): void
    {
        abort_unless(
            auth()->user()->managesCity($booking->pickup_city_id),
            403,
            'This booking belongs to a city you do not manage.'
        );
    }
}
