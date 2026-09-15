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
     * Fallback trip duration when a booking has no drop date/time.
     */
    private const TRIP_BUFFER_HOURS = 12;

    /**
     * Booking statuses that occupy (block) a vehicle during the trip window.
     */
    private const BLOCKING_STATUSES = [
        BookingStatus::APPROVED,
        BookingStatus::DRIVER_ASSIGNED,
        BookingStatus::CONFIRMED,
        BookingStatus::TRIP_STARTED,
    ];

    /**
     * Booking statuses taken into account when resolving where a vehicle
     * currently stands (a trip that has ended relocates the vehicle to
     * its drop city).
     */
    private const RELOCATING_STATUSES = [
        BookingStatus::APPROVED,
        BookingStatus::DRIVER_ASSIGNED,
        BookingStatus::CONFIRMED,
        BookingStatus::TRIP_STARTED,
        BookingStatus::TRIP_COMPLETED,
    ];

    /**
     * Search for available vehicles based on criteria.
     *
     * A vehicle is available at the pickup city when it is based there or when
     * its latest ended trip dropped it there. E.g. a Jaipur vehicle that
     * finished a Jaipur -> Udaipur trip becomes available from Udaipur,
     * not Jaipur, from the drop date onwards.
     *
     * @param  int  $pickupCityId
     * @param  string|\DateTimeInterface  $pickupDate
     * @param  string  $pickupTime
     * @param  int  $passengers
     * @param  int  $serviceTypeId
     * @param  string|null  $vehiclePreference
     * @param  string|\DateTimeInterface|null  $dropDate
     * @param  string|null  $dropTime
     * @return Collection
     */
    public function searchAvailableVehicles(
        $pickupCityId,
        $pickupDate,
        $pickupTime,
        $passengers,
        $serviceTypeId,
        $vehiclePreference = null,
        $dropDate = null,
        $dropTime = null
    ) {
        $requestedStart = Carbon::parse($this->dateString($pickupDate).' '.$pickupTime);

        if ($dropDate) {
            $requestedEnd = Carbon::parse($this->dateString($dropDate).' '.($dropTime ?? $pickupTime));
        } else {
            $requestedEnd = $requestedStart->copy()->addHours(self::TRIP_BUFFER_HOURS);
        }

        if ($requestedEnd->lessThanOrEqualTo($requestedStart)) {
            $requestedEnd = $requestedStart->copy()->addHours(self::TRIP_BUFFER_HOURS);
        }

        $trips = Booking::whereNotNull('vehicle_id')
            ->whereIn('status', array_map(fn (BookingStatus $status) => $status->value, self::RELOCATING_STATUSES))
            ->get(['id', 'vehicle_id', 'pickup_date', 'pickup_time', 'drop_date', 'drop_time', 'drop_city_id', 'status']);

        // 1. Vehicles whose active trip overlaps the requested window are busy.
        $busyVehicleIds = [];
        foreach ($trips as $trip) {
            if (! in_array($trip->status, self::BLOCKING_STATUSES, true)) {
                continue;
            }

            [$tripStart, $tripEnd] = $this->tripWindow($trip);

            if ($tripStart->lessThan($requestedEnd) && $tripEnd->greaterThan($requestedStart)) {
                $busyVehicleIds[] = $trip->vehicle_id;
            }
        }

        // 2. Resolve each vehicle's location at the requested pickup time:
        //    the drop city of its latest ended trip, or its base city.
        $locations = [];
        foreach ($trips as $trip) {
            if (! $trip->drop_city_id) {
                continue;
            }

            [, $tripEnd] = $this->tripWindow($trip);

            if ($tripEnd->lessThanOrEqualTo($requestedStart)) {
                if (! isset($locations[$trip->vehicle_id]) || $tripEnd->greaterThan($locations[$trip->vehicle_id]['ended_at'])) {
                    $locations[$trip->vehicle_id] = [
                        'city_id' => $trip->drop_city_id,
                        'ended_at' => $tripEnd,
                    ];
                }
            }
        }

        $relocatedToPickup = array_keys(array_filter($locations, fn (array $location) => $location['city_id'] == $pickupCityId));
        $movedAway = array_keys(array_filter($locations, fn (array $location) => $location['city_id'] != $pickupCityId));

        // 3. Base filters shared by every pool query.
        $baseQuery = fn () => Vehicle::with(['city', 'category', 'images'])
            ->where('status', VehicleStatus::AVAILABLE->value)
            ->where('seating_capacity', '>=', $passengers)
            ->whereNotIn('id', array_unique($busyVehicleIds));

        // 4. Vehicles located at the pickup city: still based there, or
        //    relocated there by their latest ended trip.
        $allVehicles = $baseQuery()
            ->where(function ($query) use ($pickupCityId, $relocatedToPickup, $movedAway) {
                $query->where('city_id', $pickupCityId)
                    ->whereNotIn('id', $movedAway);

                if ($relocatedToPickup !== []) {
                    $query->orWhereIn('id', $relocatedToPickup);
                }
            })
            ->get();

        // 5. Fallback: search enabled nearby cities when the pickup city has none.
        $searchedNearbyCities = false;
        if ($allVehicles->isEmpty()) {
            $nearbyCityIds = $this->getNearbyCityIds($pickupCityId);

            if ($nearbyCityIds !== []) {
                $allVehicles = $baseQuery()
                    ->where(function ($query) use ($nearbyCityIds, $movedAway) {
                        $query->whereIn('city_id', $nearbyCityIds)
                            ->whereNotIn('id', $movedAway);
                    })
                    ->get();

                $searchedNearbyCities = $allVehicles->isNotEmpty();
            }
        }

        // 6. Sort preferred vehicles to the top.
        if ($vehiclePreference) {
            $allVehicles = $allVehicles->sortByDesc(function ($vehicle) use ($vehiclePreference) {
                return (stripos($vehicle->name, $vehiclePreference) !== false ||
                        stripos($vehicle->model, $vehiclePreference) !== false ||
                        stripos($vehicle->vehicle_type, $vehiclePreference) !== false) ? 1 : 0;
            })->values();
        }

        // 7. Metadata for the views: where the vehicle will actually be.
        return $allVehicles->map(function ($vehicle) use ($locations, $searchedNearbyCities, $pickupCityId) {
            $locationCityId = $locations[$vehicle->id]['city_id'] ?? $vehicle->city_id;

            $vehicle->setAttribute('current_location_city_id', $locationCityId);
            $vehicle->setAttribute(
                'is_from_nearby_city',
                $searchedNearbyCities && $locationCityId != $pickupCityId
            );

            return $vehicle;
        })->values();
    }

    /**
     * Get IDs of nearby cities for a given city.
     */
    protected function getNearbyCityIds(int $cityId): array
    {
        $city = City::find($cityId);

        if (! $city) {
            return [];
        }

        // Get enabled nearby city IDs, ordered by priority
        return $city->nearbyCities()
            ->wherePivot('is_enabled', true)
            ->orderByPivot('priority', 'asc')
            ->pluck('cities.id')
            ->toArray();
    }

    /**
     * Resolve the start and end datetime of a booking's trip.
     *
     * The end uses the actual drop date/time when available, otherwise it
     * falls back to a fixed buffer after the pickup.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function tripWindow(Booking $booking): array
    {
        $start = Carbon::parse($this->dateString($booking->pickup_date).' '.$booking->pickup_time);

        if ($booking->drop_date) {
            $end = Carbon::parse($this->dateString($booking->drop_date).' '.($booking->drop_time ?? $booking->pickup_time));
        } else {
            $end = $start->copy()->addHours(self::TRIP_BUFFER_HOURS);
        }

        if ($end->lessThan($start)) {
            $end = $start->copy()->addHours(self::TRIP_BUFFER_HOURS);
        }

        return [$start, $end];
    }

    /**
     * Normalize a date value to a Y-m-d string.
     */
    private function dateString($date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');
    }
}
