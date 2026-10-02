<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\ReportPeriod;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TripFareCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request, TripFareCalculator $calculator)
    {
        $range = $this->resolveRange($request);

        $metrics = [
            // What was asked for and what is still waiting: both are about when
            // the request arrived, not when the ride ran.
            'total_bookings' => $this->inRange(Booking::query(), $range, 'created_at')->count(),
            'pending_bookings' => $this->inRange(
                Booking::query()->where('status', BookingStatus::PENDING->value),
                $range,
                'created_at'
            )->count(),
            // The fleet as it stands, which no range can narrow.
            'active_vehicles' => Vehicle::where('status', VehicleStatus::AVAILABLE->value)->count(),
            'available_drivers' => Driver::where('status', 'Available')->count(),
            // A refusal is counted on the day the driver turned the ride down.
            'driver_rejections' => $this->inRange(
                DriverAssignment::query()->refused(),
                $range,
                'responded_at'
            )->count(),
            ...$this->revenue($range, $calculator),
        ];

        // What was asked for most recently inside the range, so the table below
        // the cards always describes the same stretch of time they do.
        $recentBookings = $this->inRange(Booking::query(), $range, 'created_at')
            ->with(['pickupCity', 'dropCity', 'associate'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Rides a driver refused (or never answered). The assignment row keeps
        // both the driver and his reason, because the booking itself is freed
        // for reassignment the moment it is refused. refused() already requires
        // a responded_at, so the range has a date to filter that on.
        $recentRejections = $this->inRange(DriverAssignment::query(), $range, 'responded_at')
            ->rejected()
            ->with(['booking.pickupCity', 'booking.dropCity', 'driver'])
            ->take(5)
            ->get();

        return view('dashboard', [
            'metrics' => $metrics,
            'range' => $range,
            'periods' => ReportPeriod::cases(),
            'recentBookings' => $recentBookings,
            'recentRejections' => $recentRejections,
            'associateStats' => $this->associateStats($range),
            // The admin's own totals, so his share is listed next to each
            // associate's rather than only implied by the absence of a name.
            'adminTotals' => [
                'vehicles' => Vehicle::ownedByAssociate(null)->count(),
                'drivers' => Driver::ownedByAssociate(null)->count(),
                'bookings' => $this->inRange(Booking::ownedByAssociate(null), $range, 'created_at')->count(),
            ],
        ]);
    }

    /**
     * The stretch of days the dashboard is reporting on.
     *
     * A named period is the usual answer. The calendar lets the admin pick any
     * range instead, so a pair of dates in the query string wins when it is
     * there. Either way what comes back is the same thing, so nothing below has
     * to know which of the two the admin used.
     *
     * @return array{from: CarbonImmutable, to: CarbonImmutable, custom: bool}
     */
    private function resolveRange(Request $request): array
    {
        $from = $this->parseDay($request->query('date_from'));
        $to = $this->parseDay($request->query('date_to'));

        // A one-sided range is still a range: the picker lets a single day be
        // chosen, and the days before today are not what the admin asked for.
        if ($from !== null && $to === null) {
            $to = $from;
        }

        if ($from !== null && $to !== null) {
            // A range typed the wrong way round is read the other way up rather
            // than returning nothing at all.
            return [
                'from' => $from->min($to),
                'to' => $from->max($to)->endOfDay(),
                'custom' => true,
            ];
        }

        return ReportPeriod::fromRequest($request->query('period'))->range() + ['custom' => false];
    }

    /**
     * A yyyy-mm-dd from the query string as a local midnight, or null when it is
     * absent or not a real date, so a hand-typed value cannot reach the query.
     */
    private function parseDay(?string $value): ?CarbonImmutable
    {
        if ($value === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * What each associate owns, so the admin can see at a glance whose fleet,
     * drivers and rides are whose, and how much of the work is being run by an
     * associate rather than by the admin himself.
     *
     * The rides each of them runs are counted within the range, like the rides
     * on the cards; the vehicles and drivers stay the whole current fleet, since
     * a vehicle does not stop being owned at the end of a week.
     *
     * @param  array{from: CarbonImmutable, to: CarbonImmutable, custom: bool}  $range
     * @return list<array{id: int, name: string, vehicles: int, drivers: int, bookings: int}>
     */
    private function associateStats(array $range): array
    {
        return User::query()
            ->associates()
            ->orderBy('name')
            ->get()
            ->map(fn (User $associate): array => [
                'id' => $associate->id,
                'name' => $associate->name,
                'vehicles' => Vehicle::ownedByAssociate($associate->id)->count(),
                'drivers' => Driver::ownedByAssociate($associate->id)->count(),
                'bookings' => $this->inRange(Booking::ownedByAssociate($associate->id), $range, 'created_at')->count(),
            ])
            ->all();
    }

    /**
     * What the rides finished in this range are worth.
     *
     * A ride a driver closed carries the distance it covered between its two
     * odometer readings and the amount billed from the rate card of the vehicle
     * that drove it, so a single pass over them gives the totals for the range.
     *
     * A ride closed before its vehicle had a rate card was never billed, yet
     * the rate card the vehicle carries today still prices it. Those rides are
     * added in so this total is the same money the completed rides page adds up.
     *
     * @param  array{from: CarbonImmutable, to: CarbonImmutable, custom: bool}  $range
     * @return array{completed_rides: int, total_trip_price: float, total_trip_km: int}
     */
    private function revenue(array $range, TripFareCalculator $calculator): array
    {
        $completed = $this->completedInRange($range)
            ->selectRaw('COUNT(*) as rides, COALESCE(SUM(total_amount), 0) as amount, COALESCE(SUM(end_odometer_km - start_odometer_km), 0) as distance')
            ->first();

        $unbilled = $this->completedInRange($range)
            ->whereNull('total_amount')
            ->with('vehicle')
            ->get(['id', 'vehicle_id', 'pickup_date', 'drop_date', 'start_odometer_km', 'end_odometer_km']);

        return [
            'completed_rides' => (int) $completed->rides,
            'total_trip_price' => round((float) $completed->amount + $unbilled->sum(
                fn (Booking $ride) => $calculator->fareFor($ride)['total_amount'] ?? 0.0
            ), 2),
            'total_trip_km' => (int) $completed->distance,
        ];
    }

    /**
     * The rides finished inside a range.
     *
     * A ride is placed on the day it finished, which is the day the money was
     * earned. A ride the admin closed from the booking screen never had a
     * closing reading and so carries no trip_ended_at, but the booking was
     * touched at the moment it was closed, so updated_at stands in for it. That
     * fallback is what keeps such a ride counted in every range instead of
     * dropping out of the totals here while still being listed further down.
     *
     * @param  array{from: CarbonImmutable, to: CarbonImmutable, custom: bool}  $range
     */
    private function completedInRange(array $range): Builder
    {
        return $this->inRange(
            Booking::completed(),
            $range,
            DB::raw('COALESCE(trip_ended_at, updated_at)')
        );
    }

    /**
     * Keep only the rows whose given date column falls inside the range.
     *
     * The column is passed in rather than assumed, because the cards do not all
     * measure the same thing: a booking belongs to the day it was asked for, a
     * rejection to the day the driver refused it, and revenue to the day the
     * ride finished. Only the range itself is shared, so every card agrees on
     * which days are being reported on.
     *
     * @param  array{from: CarbonImmutable, to: CarbonImmutable, custom: bool}  $range
     * @param  string|Expression  $column  Qualified date column, or an expression when one date stands in for another.
     */
    private function inRange(Builder $query, array $range, string|Expression $column): Builder
    {
        return $query->whereBetween($column, [$range['from'], $range['to']]);
    }
}
