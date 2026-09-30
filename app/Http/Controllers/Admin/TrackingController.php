<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * Show the tracking map for a specific ride.
     */
    public function show($tracking_id)
    {
        $booking = Booking::where('tracking_id', $tracking_id)->firstOrFail();

        return view('admin.tracking.show', compact('booking'));
    }

    /**
     * API to get the current location of the ride.
     */
    public function location($tracking_id)
    {
        $booking = Booking::where('tracking_id', $tracking_id)->firstOrFail();

        return response()->json([
            'latitude' => $booking->current_latitude,
            'longitude' => $booking->current_longitude,
            'status' => $booking->status,
        ]);
    }

    /**
     * API for driver to update location.
     */
    public function updateLocation(Request $request, Booking $booking)
    {
        $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $booking->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
        ]);

        return response()->json(['success' => true]);
    }
}
