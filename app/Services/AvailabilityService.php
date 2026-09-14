<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Search for available vehicles based on criteria.
     *
     * @param  int  $pickupCityId
     * @param  string  $pickupDate
     * @param  string  $pickupTime
     * @param  int  $passengers
     * @param  int  $serviceTypeId
     * @param  string|null  $vehiclePreference
     * @return Collection
     */
    public function searchAvailableVehicles(
        $pickupCityId,
        $pickupDate,
        $pickupTime,
        $passengers,
        $serviceTypeId,
        $vehiclePreference = null
    ) {
        $dateStr = $pickupDate instanceof \DateTimeInterface ? $pickupDate->format('Y-m-d') : Carbon::parse($pickupDate)->format('Y-m-d');
        $requestedDateTime = Carbon::parse($dateStr.' '.$pickupTime);
        $bufferHours = 12; // Simple assumption: block for 12 hours from pickup

        $requestedStart = $requestedDateTime->copy();
        $requestedEnd = $requestedDateTime->copy()->addHours($bufferHours);

        // 1. Get IDs of vehicles that have overlapping confirmed bookings
        // Overlap logic: existing_start < requested_end AND existing_end > requested_start
        $overlappingBookings = Booking::whereIn('status', [
            BookingStatus::APPROVED->value,
            BookingStatus::DRIVER_ASSIGNED->value,
            BookingStatus::CONFIRMED->value,
            BookingStatus::TRIP_STARTED->value,
        ])->get();

        $unavailableVehicleIds = [];
        foreach ($overlappingBookings as $booking) {
            $existingDateStr = $booking->pickup_date instanceof \DateTimeInterface 
                ? $booking->pickup_date->format('Y-m-d') 
                : Carbon::parse($booking->pickup_date)->format('Y-m-d');
            $existingStart = Carbon::parse($existingDateStr.' '.$booking->pickup_time);
            $existingEnd = $existingStart->copy()->addHours($bufferHours);

            if ($existingStart->lt($requestedEnd) && $existingEnd->gt($requestedStart)) {
                if ($booking->vehicle_id) {
                    $unavailableVehicleIds[] = $booking->vehicle_id;
                }
            }
        }

        // 2. Fetch base active vehicles with enough capacity
        $query = Vehicle::with(['city', 'category', 'images'])
            ->where('status', VehicleStatus::AVAILABLE->value)
            ->where('seating_capacity', '>=', $passengers)
            ->whereNotIn('id', $unavailableVehicleIds);

        // Get vehicles from the pickup city only
        $allVehicles = clone $query;
        $allVehicles = $allVehicles->where('city_id', $pickupCityId)->get();

        if ($vehiclePreference) {
            // Sort to bring preferred vehicle up top (simple string matching)
            $allVehicles = $allVehicles->sortByDesc(function ($vehicle) use ($vehiclePreference) {
                return (stripos($vehicle->name, $vehiclePreference) !== false ||
                        stripos($vehicle->model, $vehiclePreference) !== false ||
                        stripos($vehicle->vehicle_type, $vehiclePreference) !== false) ? 1 : 0;
            });
        }

        return $allVehicles->values();
    }
}
