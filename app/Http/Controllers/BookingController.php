<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckAvailabilityRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\City;
use App\Models\ServiceType;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\LocationCityResolver;
use App\Services\TripFareCalculator;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function search(
        CheckAvailabilityRequest $request,
        AvailabilityService $availabilityService,
        LocationCityResolver $cityResolver
    ) {
        // The first page chooses the pickup city from the ones we run in and
        // lets the customer write the drop city freely. A drop city that is not
        // one of ours is still a valid request: the ride is served from the
        // pickup city, and the name he wrote is carried on the booking.
        $pickupCity = City::find($request->pickup_city_id);
        $dropCityLabel = trim((string) $request->input('drop_city'));
        $matchedDropCity = $cityResolver->resolve($dropCityLabel);
        $dropCity = $matchedDropCity ?? $pickupCity;

        $serviceTypeId = $request->input('service_type_id') ?? $this->defaultServiceTypeId();
        if ($serviceTypeId === null) {
            return back()->withInput()->withErrors([
                'service_type_id' => 'No service type is available to book right now. Please contact us.',
            ]);
        }

        if (! $pickupCity instanceof City || ! $dropCity instanceof City) {
            return back()->withInput()->withErrors([
                'pickup_city_id' => 'We could not work out the pickup city of this trip. Please choose it again.',
            ]);
        }

        // Everything the results page, the booking page and the booking row
        // still need - the two cities, the party size and the service - is put
        // back on the request, so the rest of the flow never asks for them.
        $request->merge([
            'pickup_city_id' => $pickupCity->id,
            'drop_city_id' => $dropCity->id,
            'drop_city' => $dropCityLabel,
            'passengers' => max(1, (int) $request->input('passengers', 1)),
            'service_type_id' => $serviceTypeId,
        ]);

        $vehicles = $availabilityService->searchAvailableVehicles(
            $request->pickup_city_id,
            $request->pickup_date,
            $request->pickup_time,
            $request->passengers,
            $request->service_type_id,
            $request->vehicle_preference,
            $request->drop_date,
            $request->drop_time
        );

        $request->flash();

        return view('booking.results', [
            'vehicles' => $vehicles,
            'searchParams' => $request->all(),
            'pickupCity' => $pickupCity,
            'dropCity' => $dropCity,
            'dropCityLabel' => $dropCityLabel,
            'dropCityIsOurs' => $matchedDropCity instanceof City,
            'serviceType' => ServiceType::find($request->service_type_id),
        ]);
    }

    public function create(Request $request, TripFareCalculator $tripFare)
    {
        $searchParams = $request->except('_token');

        // This page is the second step of a search: it draws the summary the
        // traveller picked on the first one. Arriving without that summary - a
        // bare link, a bookmark or a stale tab - leaves nothing to show, so he
        // is sent back to the search instead of being handed a broken page.
        if (! $this->carriesTripSummary($searchParams)) {
            return redirect()->route('home');
        }

        // Load vehicle with images if vehicle_id is provided
        $vehicle = null;
        if (! empty($searchParams['vehicle_id'])) {
            $vehicle = Vehicle::with('images')->find($searchParams['vehicle_id']);
        }

        // The price structure of the selected vehicle over the requested hire, so
        // the customer sees what the ride will cost before confirming it.
        $priceEstimate = $vehicle instanceof Vehicle
            ? $tripFare->estimate(
                $vehicle,
                $searchParams['pickup_date'] ?? null,
                $searchParams['drop_date'] ?? null
            )
            : null;

        return view('booking.create', [
            'searchParams' => $searchParams,
            'vehicle' => $vehicle,
            'priceEstimate' => $priceEstimate,
            'cities' => City::where('status', 'Active')->orderBy('name')->get(),
            'vehicleCategories' => VehicleCategory::where('status', 'Active')->orderBy('name')->get(),
        ]);
    }

    public function store(
        StoreBookingRequest $request,
        BookingService $bookingService,
        LocationCityResolver $cityResolver
    ) {
        $data = $request->validated();

        // The customer may have corrected the addresses on the review page, so
        // the cities are worked out again from what is being submitted rather
        // than trusted from the search that brought him here.
        $trip = $this->resolveTripCities($data, $cityResolver);

        $serviceTypeId = $data['service_type_id'] ?? $this->defaultServiceTypeId();
        if ($serviceTypeId === null) {
            $trip['errors']['service_type_id'] = 'No service type is available to book right now. Please contact us.';
        }

        if ($trip['errors'] !== []) {
            return back()->withInput()->withErrors($trip['errors']);
        }

        $data['pickup_city_id'] = $trip['pickup']->id;
        $data['drop_city_id'] = $trip['drop']->id;
        $data['passengers'] = max(1, (int) ($data['passengers'] ?? 1));
        $data['service_type_id'] = $serviceTypeId;

        // The drop city the customer wrote is passed straight through: the
        // service matches it against our cities and, when it names none of them,
        // files the ride against the pickup city while keeping the name he asked
        // for on the ride.
        $data['drop_city_label'] = $data['drop_city'] ?? null;
        unset($data['drop_city']);

        try {
            $booking = $bookingService->createBookingRequest($data);

            return redirect()->route('booking.confirmation', $booking->booking_number)
                ->with('success', 'Booking Request Submitted Successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create booking: '.$e->getMessage());
        }
    }

    public function confirmation($bookingNumber)
    {
        $booking = Booking::with(['pickupCity', 'dropCity', 'serviceType', 'vehicle.images'])
            ->where('booking_number', $bookingNumber)
            ->firstOrFail();

        return view('booking.confirmation', compact('booking'));
    }

    /**
     * The cities this trip runs between. The customer picks both on the search
     * page, so the ids he carries are the answer; the addresses he types on the
     * review page are only consulted when an id never made it that far.
     *
     * @param  array<string, mixed>  $data
     * @return array{pickup: ?City, drop: ?City, errors: array<string, string>}
     */
    private function resolveTripCities(array $data, LocationCityResolver $cityResolver): array
    {
        $pickup = $this->tripCity($data['pickup_city_id'] ?? null, $data['pickup_location'] ?? null, $cityResolver);
        $drop = $this->tripCity($data['drop_city_id'] ?? null, $data['drop_location'] ?? null, $cityResolver);

        $errors = [];
        if (! $pickup instanceof City) {
            $errors['pickup_city_id'] = 'We could not work out the pickup city of this trip. Please choose it again.';
        }
        if (! $drop instanceof City) {
            $errors['drop_city_id'] = 'We could not work out the drop city of this trip. Please choose it again.';
        }

        return ['pickup' => $pickup, 'drop' => $drop, 'errors' => $errors];
    }

    /**
     * The city the customer chose, then the one named in the address, then the
     * only city the service runs in.
     */
    private function tripCity($cityId, $address, LocationCityResolver $cityResolver): ?City
    {
        if ($cityId !== null && $cityId !== '') {
            $city = City::find($cityId);

            if ($city instanceof City) {
                return $city;
            }
        }

        return $cityResolver->resolve($address) ?? $cityResolver->soleCity();
    }

    /**
     * The service a booking is filed under when the form no longer asks for
     * one: the first service the customer is shown.
     */
    private function defaultServiceTypeId(): ?int
    {
        return ServiceType::visibleToCustomers()->first()?->id ?? ServiceType::first()?->id;
    }

    /**
     * The fields booking/create reads straight off the query string to rebuild
     * its trip form: the two cities, the pickup date and time and everything
     * that priced the ride. The page can only be drawn once every one of them
     * came along, so a visit that is missing any of them is not a booking yet.
     *
     * @param  array<string, mixed>  $searchParams
     */
    private function carriesTripSummary(array $searchParams): bool
    {
        foreach (['pickup_city_id', 'drop_city_id', 'pickup_date', 'pickup_time'] as $field) {
            if (blank($searchParams[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
