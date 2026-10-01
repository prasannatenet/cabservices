<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Collection;

/**
 * Works out which city an address stands in.
 *
 * The search form asks the customer only for the pickup and drop address, but
 * the availability search and the booking row both need the city those
 * addresses belong to. The text is matched against the names of the active
 * cities - "Station Road, Jaipur" is a Jaipur address - and the longest name
 * wins, so "New Delhi" is never lost to a city that merely contains "Delhi".
 */
class LocationCityResolver
{
    /**
     * The active cities, longest name first, so a short name never wins over
     * the fuller match it sits inside.
     *
     * @var Collection<int, City>|null
     */
    protected ?Collection $cities = null;

    /**
     * The city the address names, or null when it names none.
     */
    public function resolve(?string $address): ?City
    {
        if (blank($address)) {
            return null;
        }

        foreach ($this->cities() as $city) {
            if (mb_stripos($address, $city->name) !== false) {
                return $city;
            }
        }

        return null;
    }

    /**
     * The only city the service runs in. When there is just one, every ride
     * starts and ends there, so an address that names no city still has an
     * obvious answer.
     */
    public function soleCity(): ?City
    {
        return $this->cities()->count() === 1 ? $this->cities()->first() : null;
    }

    /**
     * @return Collection<int, City>
     */
    protected function cities(): Collection
    {
        return $this->cities ??= City::where('status', 'Active')
            ->get()
            ->sortByDesc(fn (City $city) => mb_strlen(trim($city->name)))
            ->values();
    }
}
