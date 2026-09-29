<?php

namespace App\Http\Controllers\Driver;

use App\Enums\RideExpenseCategory;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\RideExpense;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The trip sheet of one ride: the odometer reading and photo the driver sends
 * when he starts the ride, plus the fuel / gas / other expenses he pays for
 * while he is driving. Everything on this page is visible to the admin on the
 * booking screen.
 */
class TripController extends Controller
{
    public function __construct(protected BookingService $bookings) {}

    /**
     * Show the trip sheet of a ride assigned to the authenticated driver.
     */
    public function show(Booking $booking): View
    {
        $driver = $this->authorizedDriver($booking);

        $booking->load(['pickupCity', 'dropCity', 'vehicle', 'serviceType', 'rideExpenses.driver']);

        return view('driver.trips.show', [
            'driver' => $driver,
            'booking' => $booking,
            'categories' => RideExpenseCategory::cases(),
            'expenses' => $booking->rideExpenses->sortByDesc('spent_on')->values(),
            'expenseTotal' => $booking->expenseTotal(),
        ]);
    }

    /**
     * Start the ride: record the odometer reading and store the photo of the
     * meter as proof of the vehicle's state at pickup.
     */
    public function start(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizedDriver($booking);

        if (! $booking->canStartTrip()) {
            return back()->with('error', 'This ride cannot be started from its current status ('.$booking->displayStatus().').');
        }

        $validated = $request->validate([
            'start_odometer_km' => ['required', 'integer', 'min:0', 'max:9999999'],
            'start_odometer_photo' => ['required', 'image', 'max:4096'],
        ], [
            'start_odometer_km.required' => 'Please enter the odometer reading shown on the meter.',
            'start_odometer_km.integer' => 'The odometer reading must be a whole number of kilometres.',
            'start_odometer_photo.required' => 'Please upload a photo of the odometer.',
            'start_odometer_photo.image' => 'The odometer photo must be an image (JPG, PNG or similar).',
            'start_odometer_photo.max' => 'The odometer photo may not be larger than 4 MB.',
        ]);

        $photoPath = $request->file('start_odometer_photo')->store('trips/odometer', 'public');
        $odometerKm = (int) $validated['start_odometer_km'];

        try {
            $this->bookings->startTrip($booking, $odometerKm, $photoPath, Auth::id());
        } catch (\Exception $exception) {
            // The ride did not move, so the uploaded photo has no owner: drop it.
            Storage::disk('public')->delete($photoPath);

            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('driver.trips.show', $booking)
            ->with('success', 'Trip started. Odometer recorded at '.number_format($odometerKm).' km.');
    }

    /**
     * Close the ride at the drop point: record the closing odometer reading,
     * store the photo of the meter and let the total distance come out of the
     * difference between the two readings.
     */
    public function end(Request $request, Booking $booking): RedirectResponse
    {
        $driver = $this->authorizedDriver($booking);

        if (! $booking->canEndTrip()) {
            return back()->with('error', 'This ride cannot be ended from its current status ('.$booking->displayStatus().').');
        }

        $startKm = (int) $booking->start_odometer_km;

        $validated = $request->validate([
            'end_odometer_km' => ['required', 'integer', 'min:'.$startKm, 'max:9999999'],
            'end_odometer_photo' => ['required', 'image', 'max:4096'],
        ], [
            'end_odometer_km.required' => 'Please enter the odometer reading shown on the meter now.',
            'end_odometer_km.integer' => 'The closing odometer reading must be a whole number of kilometres.',
            'end_odometer_km.min' => 'The closing reading must be at least the reading you entered when you started ('.number_format($startKm).' km).',
            'end_odometer_photo.required' => 'Please upload a photo of the odometer at the end of the ride.',
            'end_odometer_photo.image' => 'The odometer photo must be an image (JPG, PNG or similar).',
            'end_odometer_photo.max' => 'The odometer photo may not be larger than 4 MB.',
        ]);

        $photoPath = $request->file('end_odometer_photo')->store('trips/odometer', 'public');
        $endKm = (int) $validated['end_odometer_km'];

        try {
            $this->bookings->endTrip($booking, $endKm, $photoPath, Auth::id());
        } catch (\Exception $exception) {
            // The ride did not get closed, so the uploaded photo has no owner.
            Storage::disk('public')->delete($photoPath);

            return back()->with('error', $exception->getMessage());
        }

        // The price of the ride is the operator's business, so the confirmation
        // only reports the distance. A per day driver is told what the trip earned
        // him from his own daily rate.
        $message = 'Trip completed. Total distance: '.number_format($endKm - $startKm).' km';

        $earnings = $driver->earningsFor($booking->fresh());

        if ($earnings !== null) {
            $message .= ', your earnings: '.number_format($earnings['total'], 2)
                .' ('.$earnings['days'].' day(s) &times; '.number_format($earnings['rate'], 2).')';
        }

        return redirect()->route('driver.trips.show', $booking)->with('success', $message.'.');
    }

    /**
     * Log money the driver paid for the ride (petrol, diesel, gas or anything
     * else) together with the photo of the bill.
     */
    public function storeExpense(Request $request, Booking $booking): RedirectResponse
    {
        $driver = $this->authorizedDriver($booking);

        if (! $booking->canLogExpenses()) {
            return back()->with('error', 'Expenses can only be added while the ride is running.');
        }

        $validated = $request->validate([
            'category' => ['required', Rule::enum(RideExpenseCategory::class)],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999.99'],
            'bill_number' => ['nullable', 'string', 'max:100'],
            'bill_photo' => ['required', 'image', 'max:4096'],
            'spent_on' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'category.required' => 'Please choose what the money was spent on.',
            'amount.required' => 'Please enter the amount you paid.',
            'amount.min' => 'The amount must be at least 1.',
            'bill_photo.required' => 'Please upload a photo of the bill.',
            'bill_photo.image' => 'The bill photo must be an image (JPG, PNG or similar).',
            'bill_photo.max' => 'The bill photo may not be larger than 4 MB.',
            'spent_on.before_or_equal' => 'The expense date cannot be in the future.',
            'notes.max' => 'Please keep the note under 1000 characters.',
        ]);

        $booking->rideExpenses()->create([
            'driver_id' => $driver->id,
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'bill_number' => $validated['bill_number'] ?? null,
            'bill_photo' => $request->file('bill_photo')->store('trips/expenses', 'public'),
            'spent_on' => $validated['spent_on'] ?? now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Expense added. The admin can see it together with your bill photo.');
    }

    /**
     * Remove an expense the driver logged by mistake, as long as the ride is
     * still running.
     */
    public function destroyExpense(Booking $booking, RideExpense $expense): RedirectResponse
    {
        $driver = $this->authorizedDriver($booking);

        abort_unless($expense->booking_id === $booking->id, 404);

        if ($expense->driver_id !== $driver->id) {
            return back()->with('error', 'You can only remove an expense you added yourself.');
        }

        if (! $booking->canLogExpenses()) {
            return back()->with('error', 'The trip is closed, so its expenses can no longer be changed.');
        }

        Storage::disk('public')->delete($expense->bill_photo);

        $expense->delete();

        return back()->with('success', 'Expense removed.');
    }

    /**
     * Resolve the authenticated driver and stop him from opening a ride that
     * was not assigned to him.
     */
    private function authorizedDriver(Booking $booking): Driver
    {
        $driver = Auth::user()->driver;

        abort_unless($driver instanceof Driver, 404, 'No driver profile is linked to this account.');

        abort_unless($booking->driver_id === $driver->id, 403, 'This ride was not assigned to you.');

        return $driver;
    }
}
