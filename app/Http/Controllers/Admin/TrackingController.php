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
        // Only the driver the ride was assigned to may move its marker, so one
        // driver cannot report a position for another's trip.
        $driver = $request->user()->driver;

        abort_unless($driver && $booking->driver_id === $driver->id, 403, 'This ride was not assigned to you.');

        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $booking->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
        ]);

        return response()->json(['success' => true]);
    }
}
