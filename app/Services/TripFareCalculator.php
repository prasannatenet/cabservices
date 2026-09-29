<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Vehicle;

/**
 * Works out what a finished ride costs from the rate card of the vehicle that
 * drove it.
 *
 * A vehicle is hired by the day: the price of one day covers the fixed number of
 * kilometres that comes with it, and every kilometre driven above that is
 * charged at the per kilometre rate. A Tata Nexon on 5,500 a day with 500 km
 * included and 13 per extra km therefore bills a ride of 412 km as 5,500 (all of
 * it inside the day), and a ride of 620 km as 5,500 + 120 x 13 = 7,060.
 *
 * The days counted are the days of the hire: the pickup day and every day up to
 * and including the drop day, so a pickup on 1 October with a drop on
 * 3 October is three days. A ride without a drop date, or one that drops on the
 * day it starts, is a single day.
 */
class TripFareCalculator
{
    /**
     * Every hire is billed for at least one day, even one that starts and ends
     * on the same day.
     */
    public const MINIMUM_BILLED_DAYS = 1;

    /**
     * The fare of the ride, split into the figures the trip sheet and the admin
     * booking screen show. Null when the vehicle has no rate at all, because
     * then there is nothing to bill.
     *
     * @return array{
     *     billed_days: int,
     *     billed_included_km: int,
     *     billed_price_per_day: float,
     *     billed_price_per_km: float,
     *     extra_km: int,
     *     base_amount: float,
     *     extra_km_amount: float,
     *     total_amount: float
     * }|null
     */
    public function calculate(Booking $booking, int $totalKm): ?array
    {
        $vehicle = $booking->vehicle;

        if (! $vehicle instanceof Vehicle) {
            return null;
        }

        $pricePerDay = (float) ($vehicle->price_per_day ?? 0);
        $pricePerKm = (float) ($vehicle->price_per_km ?? 0);

        if ($pricePerDay <= 0 && $pricePerKm <= 0) {
            return null;
        }

        $billedDays = $this->billedDays($booking);
        $includedKm = ((int) $vehicle->fixed_km_per_day) * $billedDays;
        $baseAmount = round($pricePerDay * $billedDays, 2);
        $extraKm = max(0, $totalKm - $includedKm);
        $extraKmAmount = round($extraKm * $pricePerKm, 2);

        return [
            'billed_days' => $billedDays,
            'billed_included_km' => $includedKm,
            'billed_price_per_day' => $pricePerDay,
            'billed_price_per_km' => $pricePerKm,
            'extra_km' => $extraKm,
            'base_amount' => $baseAmount,
            'extra_km_amount' => $extraKmAmount,
            'total_amount' => round($baseAmount + $extraKmAmount, 2),
        ];
    }

    /**
     * The amount to show for a ride: the bill stored when the trip was closed,
     * and otherwise the one the vehicle's rate card comes to now.
     *
     * A ride closed before its vehicle had a rate card was never billed, but its
     * distance is on record, so the rate card the vehicle carries today still
     * prices it. "billed" tells the caller which of the two it is looking at, so
     * the screens never pass a price off for something that was charged.
     *
     * Null when there is nothing to price the ride with: no rate card, or no
     * distance to price it over.
     *
     * @return array{
     *     billed: bool,
     *     billed_days: int,
     *     billed_included_km: int,
     *     billed_price_per_day: float,
     *     billed_price_per_km: float,
     *     extra_km: int,
     *     base_amount: float,
     *     extra_km_amount: float,
     *     total_amount: float
     * }|null
     */
    public function fareFor(Booking $booking): ?array
    {
        if ($booking->hasTripFare()) {
            return ['billed' => true] + $booking->tripFare();
        }

        $distanceKm = $booking->tripDistanceKm();

        if ($distanceKm === null) {
            return null;
        }

        $fare = $this->calculate($booking, $distanceKm);

        return $fare === null ? null : ['billed' => false] + $fare;
    }

    /**
     * Days of the hire the vehicle is billed for: the pickup day counts as the
     * first day and the drop day as the last one.
     */
    public function billedDays(Booking $booking): int
    {
        $pickupDate = $booking->pickup_date;
        $dropDate = $booking->drop_date;

        if ($pickupDate === null || $dropDate === null || ! $dropDate->isAfter($pickupDate)) {
            return self::MINIMUM_BILLED_DAYS;
        }

        return max(self::MINIMUM_BILLED_DAYS, ((int) $pickupDate->diffInDays($dropDate)) + 1);
    }
}
