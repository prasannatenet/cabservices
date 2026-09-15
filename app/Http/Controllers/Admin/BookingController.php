<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\City;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['pickupCity', 'dropCity'])
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

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'cities' => City::orderBy('name')->get(),
            'statuses' => array_map(fn (BookingStatus $status) => $status->value, BookingStatus::cases()),
        ]);
    }

    public function show(Booking $booking)
    {
        $booking->load(['pickupCity', 'dropCity', 'vehicle', 'serviceType', 'driverAssignment.driver']);

        $availableDrivers = Driver::where('status', 'Available')->get();
        $availableVehicles = Vehicle::with(['images', 'category', 'city'])->where('status', 'Available')->get(); // Basic check, ideally use AvailabilityService

        return view('admin.bookings.show', compact('booking', 'availableDrivers', 'availableVehicles'));
    }

    public function update(Request $request, Booking $booking, BookingService $bookingService)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'driver_id' => 'nullable|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
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
}
