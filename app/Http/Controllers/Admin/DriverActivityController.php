<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DriverStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Services\TripFareCalculator;
use Illuminate\Http\Request;

/**
 * Ride activity per driver: how many rides he was given, how many he finished,
 * how many are still with him, and how many he turned down.
 *
 * Refusals are counted from driver_assignments because bookings.driver_id is
 * cleared the moment a driver refuses, so the booking alone no longer shows who
 * said no or why.
 */
class DriverActivityController extends Controller
{
    /**
     * Columns the overview table may be sorted by.
     *
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = [
        'name',
        'assigned_count',
        'awaiting_count',
        'ongoing_count',
        'completed_count',
        'rejected_count',
    ];

    /**
     * Rows per page on this report.
     */
    private const PER_PAGE = 15;

    /**
     * Overview of every driver with his ride counts.
     */
    public function index(Request $request)
    {
        $sort = in_array($request->query('sort'), self::SORTABLE_COLUMNS, true)
            ? $request->query('sort')
            : 'completed_count';

        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $drivers = Driver::query()
            ->with('currentCity')
            ->withCount(['driverAssignments as assigned_count' => fn ($query) => $query->counted()])
            ->withCount(['driverAssignments as awaiting_count' => fn ($query) => $query->awaitingResponse()])
            ->withCount(['driverAssignments as rejected_count' => fn ($query) => $query->refused()])
            ->withCount(['bookings as ongoing_count' => fn ($query) => $query->ongoing()])
            ->withCount(['bookings as completed_count' => fn ($query) => $query->completed()])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');

                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('city_id'), fn ($query) => $query->where('current_city_id', $request->query('city_id')))
            ->orderBy($sort, $direction)
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.drivers.activity.index', [
            'drivers' => $drivers,
            'cities' => City::orderBy('name')->get(),
            'statuses' => array_map(fn (DriverStatus $status) => $status->value, DriverStatus::cases()),
            'sort' => $sort,
            'direction' => $direction,
            'totals' => [
                'drivers' => Driver::count(),
                'assigned' => DriverAssignment::counted()->count(),
                'ongoing' => Booking::ongoing()->count(),
                'completed' => Booking::completed()->count(),
                'rejected' => DriverAssignment::refused()->count(),
            ],
        ]);
    }

    /**
     * One driver's rides, split into finished, ongoing and refused.
     */
    public function show(Driver $driver)
    {
        $driver->load('currentCity');

        $summary = [
            'assigned' => $driver->driverAssignments()->counted()->count(),
            'awaiting' => $driver->driverAssignments()->awaitingResponse()->count(),
            'ongoing' => Booking::where('driver_id', $driver->id)->ongoing()->count(),
            'completed' => Booking::where('driver_id', $driver->id)->completed()->count(),
            'rejected' => $driver->driverAssignments()->refused()->count(),
        ];

        // What this driver has earned, from his own per day rate over the rides
        // he finished. This is the driver's pay and not the price the customer
        // was billed: the two are worked out from different rate cards, and only
        // the first one is the admin's to settle. It is null for a driver who is
        // not paid per day or has no daily rate on record, because then there is
        // no figure of his own to add up.
        $earnings = $this->earnings($driver);

        $completed = Booking::with(['pickupCity', 'dropCity', 'vehicle'])
            ->where('driver_id', $driver->id)
            ->completed()
            ->latest('updated_at')
            ->paginate(self::PER_PAGE, ['*'], 'completed_page')
            ->withQueryString();

        $ongoing = Booking::with(['pickupCity', 'dropCity', 'vehicle'])
            ->where('driver_id', $driver->id)
            ->ongoing()
            ->orderBy('pickup_date')
            ->paginate(self::PER_PAGE, ['*'], 'ongoing_page')
            ->withQueryString();

        $rejected = DriverAssignment::with(['booking.pickupCity', 'booking.dropCity'])
            ->where('driver_id', $driver->id)
            ->rejected()
            ->paginate(self::PER_PAGE, ['*'], 'rejected_page')
            ->withQueryString();

        return view('admin.drivers.activity.show', compact('driver', 'summary', 'completed', 'ongoing', 'rejected', 'earnings'));
    }

    /**
     * What this driver has earned over the rides he finished.
     *
     * Read from the driver's own per day rate, so the figure here is the same one
     * he is shown on his own dashboard rather than a second, different answer.
     * The days are counted the same way the hire is billed, so the two multiply
     * out to the total and the admin can see what it came from.
     *
     * Null when the driver is not paid per day or carries no daily rate, because
     * then nothing of his own can be added up and showing 0.00 would read as
     * "this driver earned nothing" rather than "there is no rate to work from".
     *
     * @return array{total: float, days: int, rate: float}|null
     */
    private function earnings(Driver $driver): ?array
    {
        if (! $driver->isPaidPerDay() || $driver->per_day_salary === null) {
            return null;
        }

        $calculator = app(TripFareCalculator::class);
        $rides = Booking::where('driver_id', $driver->id)->completed()->get();

        return [
            'total' => $driver->earningsAcross($rides),
            'days' => $rides->sum(fn (Booking $booking) => $calculator->billedDays($booking)),
            'rate' => (float) $driver->per_day_salary,
        ];
    }
}
