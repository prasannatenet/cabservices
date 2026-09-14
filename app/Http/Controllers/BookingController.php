<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckAvailabilityRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\City;
use App\Models\ServiceType;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function search(CheckAvailabilityRequest $request, AvailabilityService $availabilityService)
    {
        $vehicles = $availabilityService->searchAvailableVehicles(
            $request->pickup_city_id,
            $request->pickup_date,
            $request->pickup_time,
            $request->passengers,
            $request->service_type_id,
            $request->vehicle_preference
        );

        $request->flash();

        return view('booking.results', [
            'vehicles' => $vehicles,
            'searchParams' => $request->all(),
            'pickupCity' => City::find($request->pickup_city_id),
            'dropCity' => City::find($request->drop_city_id),
            'serviceType' => ServiceType::find($request->service_type_id),
        ]);
    }

    public function create(Request $request)
    {
        $searchParams = $request->except('_token');

        return view('booking.create', compact('searchParams'));
    }

    public function store(StoreBookingRequest $request, BookingService $bookingService)
    {
        try {
            $booking = $bookingService->createBookingRequest($request->validated());

            return redirect()->route('booking.confirmation', $booking->booking_number)
                ->with('success', 'Booking Request Submitted Successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create booking: '.$e->getMessage());
        }
    }

    public function confirmation($bookingNumber)
    {
        $booking = Booking::with(['pickupCity', 'dropCity', 'serviceType', 'vehicle'])
            ->where('booking_number', $bookingNumber)
            ->firstOrFail();

        return view('booking.confirmation', compact('booking'));
    }
}
