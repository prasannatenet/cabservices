<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function createBookingRequest(array $data)
    {
        return DB::transaction(function () use ($data) {
            $bookingNumber = 'BKG-'.strtoupper(Str::random(8));

            $booking = Booking::create(array_merge($data, [
                'booking_number' => $bookingNumber,
                'status' => BookingStatus::PENDING->value,
            ]));

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'new_status' => BookingStatus::PENDING->value,
                'remarks' => 'Booking request created by customer',
            ]);

            return $booking;
        });
    }

    public function approveBooking(Booking $booking, $adminId)
    {
        // Re-verify availability
        $availabilityService = new AvailabilityService;
        $available = $availabilityService->searchAvailableVehicles(
            $booking->pickup_city_id,
            $booking->pickup_date,
            $booking->pickup_time,
            $booking->passengers,
            $booking->service_type_id,
            null,
            $booking->drop_date,
            $booking->drop_time
        );

        if (! $available->contains('id', $booking->vehicle_id)) {
            throw new \Exception('Selected vehicle is no longer available for this time slot.');
        }

        return DB::transaction(function () use ($booking, $adminId) {
            $oldStatus = $booking->status;
            $booking->update([
                'status' => BookingStatus::APPROVED->value,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::APPROVED->value,
                'changed_by' => $adminId,
                'remarks' => 'Booking approved. Awaiting driver assignment.',
            ]);

            return $booking;
        });
    }

    public function rejectBooking(Booking $booking, $adminId, $reason)
    {
        return DB::transaction(function () use ($booking, $adminId, $reason) {
            $oldStatus = $booking->status;
            $booking->update([
                'status' => BookingStatus::REJECTED->value,
                'rejection_reason' => $reason,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::REJECTED->value,
                'changed_by' => $adminId,
                'remarks' => 'Booking rejected: '.$reason,
            ]);

            return $booking;
        });
    }

    public function assignDriver(Booking $booking, $driverId, $adminId)
    {
        return DB::transaction(function () use ($booking, $driverId, $adminId) {
            $oldStatus = $booking->status;

            $booking->update([
                'status' => BookingStatus::DRIVER_ASSIGNED->value,
                'driver_id' => $driverId,
            ]);

            DriverAssignment::create([
                'booking_id' => $booking->id,
                'driver_id' => $driverId,
                'vehicle_id' => $booking->vehicle_id,
                'assigned_by' => $adminId,
                'status' => 'Active',
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::DRIVER_ASSIGNED->value,
                'changed_by' => $adminId,
                'remarks' => 'Driver assigned.',
            ]);

            return $booking;
        });
    }

    public function confirmBooking(Booking $booking, $driverId)
    {
        return DB::transaction(function () use ($booking, $driverId) {
            $oldStatus = $booking->status;

            $booking->update([
                'status' => BookingStatus::CONFIRMED->value,
                'driver_id' => $driverId,
            ]);

            if ($driverId) {
                DriverAssignment::create([
                    'booking_id' => $booking->id,
                    'driver_id' => $driverId,
                    'vehicle_id' => $booking->vehicle_id,
                    'assigned_by' => auth()->id() ?? 1, // Fallback for tests
                    'status' => 'Active',
                ]);
            }

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::CONFIRMED->value,
                'changed_by' => auth()->id() ?? 1,
                'remarks' => 'Booking confirmed and driver assigned.',
            ]);

            return $booking;
        });
    }

    public function cancelBooking(Booking $booking)
    {
        return DB::transaction(function () use ($booking) {
            $oldStatus = $booking->status;

            $booking->update([
                'status' => BookingStatus::CANCELLED->value,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::CANCELLED->value,
                'changed_by' => auth()->id() ?? 1,
                'remarks' => 'Booking cancelled.',
            ]);

            return $booking;
        });
    }

    /**
     * Mark the trip as completed and relocate the vehicle and driver to the
     * drop city, so they become available from there (e.g. a Jaipur ->
     * Udaipur trip makes them available from Udaipur afterwards).
     */
    public function completeTrip(Booking $booking, $adminId)
    {
        return DB::transaction(function () use ($booking, $adminId) {
            $oldStatus = $booking->status;

            $booking->update([
                'status' => BookingStatus::TRIP_COMPLETED->value,
            ]);

            if ($booking->vehicle_id && $booking->drop_city_id) {
                Vehicle::whereKey($booking->vehicle_id)->update(['city_id' => $booking->drop_city_id]);
            }

            if ($booking->driver_id && $booking->drop_city_id) {
                Driver::whereKey($booking->driver_id)->update(['current_city_id' => $booking->drop_city_id]);
            }

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::TRIP_COMPLETED->value,
                'changed_by' => $adminId,
                'remarks' => 'Trip completed. Vehicle and driver relocated to '.($booking->dropCity->name ?? 'the drop city').'.',
            ]);

            return $booking;
        });
    }
}
