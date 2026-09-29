<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\City;
use App\Services\TripFareCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Every ride that finished, with the distance it covered and the amount it was
 * billed: the working list of what the fleet earned, one row per closed trip.
 *
 * A ride closed by the driver carries both odometer readings and the bill worked
 * out from them. One the admin closed from the booking screen without readings
 * is still listed, but with no distance and no amount rather than a guess.
 */
class CompletedTripController extends Controller
{
    /**
     * Rows per page on this list.
     */
    private const PER_PAGE = 15;

    /**
     * The finished rides, newest first, narrowed down by the filters.
     */
    public function index(Request $request, TripFareCalculator $calculator)
    {
        $rides = $this->filtered($request)
            ->with(['pickupCity', 'dropCity', 'vehicle', 'driver'])
            // A ride closed by the driver was touched when it ended, one closed by
            // the admin when the booking was updated, so ordering by the last
            // change keeps both in the right place in the list.
            ->latest('updated_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // What every ride of this page comes to, so the amount and the figures it
        // was worked out of can be shown on the row itself.
        $fares = $rides->getCollection()
            ->mapWithKeys(fn (Booking $ride) => [$ride->id => $calculator->fareFor($ride)])
            ->all();

        return view('admin.bookings.completed', [
            'rides' => $rides,
            'fares' => $fares,
            'cities' => City::orderBy('name')->get(),
            'totals' => $this->totals($request, $calculator),
        ]);
    }

    /**
     * What the rides the filters leave add up to.
     *
     * The distances and the billed amounts come straight from the database. A
     * ride that was closed before its vehicle had a rate card carries no amount
     * of its own, so it is priced from the rate card the vehicle carries today
     * and added in: the figures shown here then match the rows below.
     *
     * @return array{rides: int, total_trip_price: float, total_trip_km: int}
     */
    private function totals(Request $request, TripFareCalculator $calculator): array
    {
        $billed = $this->filtered($request)
            ->selectRaw('COUNT(*) as rides, COALESCE(SUM(total_amount), 0) as amount, COALESCE(SUM(end_odometer_km - start_odometer_km), 0) as distance')
            ->first();

        $unbilled = $this->filtered($request)
            ->whereNull('total_amount')
            ->with('vehicle')
            ->get(['id', 'vehicle_id', 'pickup_date', 'drop_date', 'start_odometer_km', 'end_odometer_km']);

        return [
            'rides' => (int) $billed->rides,
            'total_trip_price' => round((float) $billed->amount + $unbilled->sum(
                fn (Booking $ride) => $calculator->fareFor($ride)['total_amount'] ?? 0.0
            ), 2),
            'total_trip_km' => (int) $billed->distance,
        ];
    }

    /**
     * The finished rides matching the filters of this request.
     */
    private function filtered(Request $request): Builder
    {
        return Booking::query()
            ->completed()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');

                $query->where(function ($q) use ($search) {
                    $q->where('booking_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('pickup_city_id'), fn ($query) => $query->where('pickup_city_id', $request->query('pickup_city_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('pickup_date', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('pickup_date', '<=', $request->query('date_to')));
    }
}
