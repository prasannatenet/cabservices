<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckAvailabilityRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\VehicleResource;
use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\BookingService;

class BookingController extends Controller
{
    public function checkAvailability(CheckAvailabilityRequest $request, AvailabilityService $availabilityService)
    {
        $vehicles = $availabilityService->searchAvailableVehicles(
            $request->pickup_city_id,
            $request->pickup_date,
            $request->pickup_time,
            $request->passengers,
            $request->service_type_id,
            $request->vehicle_preference
        );

        return VehicleResource::collection($vehicles);
    }

    public function store(StoreBookingRequest $request, BookingService $bookingService)
    {
        try {
            $booking = $bookingService->createBookingRequest($request->validated());

            return new BookingResource($booking);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create booking', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($bookingNumber)
    {
        $booking = Booking::with(['pickupCity', 'dropCity', 'serviceType', 'vehicle', 'driver'])
            ->where('booking_number', $bookingNumber)
            ->firstOrFail();

        return new BookingResource($booking);
    }
}
