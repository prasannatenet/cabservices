<?php

namespace App\Services;

use App\Enums\AssignmentResponseStatus;
use App\Enums\BookingStatus;
use App\Enums\RejectionSource;
use App\Mail\RideTrackingLinkMail;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\City;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        protected MailNotificationService $mailNotifications,
        protected TripFareCalculator $tripFare,
        protected BookingStatusService $statuses,
        protected FleetStatusService $fleet,
        protected CustomerAccountService $customers,
        protected LocationCityResolver $cityResolver,
    ) {}

    /**
     * File a new booking request.
     *
     * The trip may only start in a city the fleet runs in, but the customer is
     * free to write any destination at all. So the two cities are settled here,
     * once, for every caller: the written destination is matched against our
     * cities, and one that names none of them is served from the pickup city
     * with the name the customer wrote kept on the ride. Keeping this here
     * rather than in a controller is what lets the web form and the JSON API
     * behave the same way.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Exception when the pickup city cannot be worked out
     */
    public function createBookingRequest(array $data)
    {
        $data = $this->settleTripCities($data);
        $this->ensureVehicleRunsService($data);

        $booking = DB::transaction(function () use ($data) {
            $bookingNumber = 'BKG-'.strtoupper(Str::random(8));

            $booking = Booking::create(array_merge($data, [
                'booking_number' => $bookingNumber,
                'status' => BookingStatus::PENDING->value,
            ]));

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'new_status' => BookingStatus::PENDING->value,
                'remarks' => 'Booking request created by customer',
            ]);

            return $booking;
        });

        $this->mailNotifications->notifyBookingRequested($booking);

        return $booking;
    }

    public function approveBooking(Booking $booking, $adminId)
    {
        // Re-verify availability
        $availabilityService = new AvailabilityService;
        $available = $availabilityService->searchAvailableVehicles(
            $booking->pickup_city_id,
            $booking->pickup_date,
            $booking->pickup_time,
            $booking->passengers,
            $booking->service_type_id,
            null,
            $booking->drop_date,
            $booking->drop_time
        );

        if (! $available->contains('id', $booking->vehicle_id)) {
            throw new \Exception('Selected vehicle is no longer available for this time slot.');
        }

        $booking = DB::transaction(fn () => $this->statuses->transition(
            $booking,
            BookingStatus::APPROVED,
            $adminId,
            'Booking approved. Awaiting driver assignment.',
        ));

        return $booking;
    }

    /**
     * The admin turns the request down, giving the customer a reason.
     */
    public function rejectBooking(Booking $booking, $adminId, string $reason)
    {
        $booking = DB::transaction(function () use ($booking, $adminId, $reason) {
            // Anything already on the ride is given back before the ride stops,
            // so the driver and vehicle are not left marked as busy for a ride
            // that will never happen.
            $this->fleet->release($booking);

            return $this->statuses->transition(
                $booking,
                BookingStatus::REJECTED,
                $adminId,
                'Booking rejected: '.$reason,
                [
                    'rejection_reason' => $reason,
                    'rejection_source' => RejectionSource::Admin,
                ],
            );
        });

        return $booking;
    }

    /**
     * Put a driver on the ride and open his six hour response window.
     *
     * Assigning rewrites the status to Driver Assigned, which would drag a
     * finished ride back into play, so a closed ride is never reopened. A ride
     * that already has a driver gives the old one back first.
     */
    public function assignDriver(Booking $booking, $driverId, $adminId)
    {
        if ($booking->isLocked()) {
            throw new \Exception($booking->lockedMessage());
        }

        // The driver's willingness is checked first: it is the argument being
        // passed in, so a refusal about him is the useful answer.
        $this->ensureDriverWillingToGoTo($booking, $driverId);

        // Every assignment records the vehicle it was made for, so a ride with
        // no vehicle on it cannot be given a driver. Saying so plainly beats
        // letting the insert fail on a database constraint.
        if (! $booking->vehicle_id) {
            throw new \Exception('Choose a vehicle for this booking before assigning a driver to it.');
        }

        $booking = DB::transaction(function () use ($booking, $driverId, $adminId) {
            // Swapping the driver hands the previous one back before the new one
            // is taken on, so nobody is left marked busy for a ride he is not on.
            $this->fleet->release($booking);

            // A ride given to somebody else was never the previous driver's, so
            // his assignment is closed off here whether he had answered it or
            // was still sitting on it. It stops counting against him and stops
            // his answer from touching a ride he is no longer on.
            $this->supersedeAssignments($booking);

            $booking = $this->statuses->transition(
                $booking,
                BookingStatus::DRIVER_ASSIGNED,
                $adminId,
                'Driver assigned.',
                ['driver_id' => $driverId],
            );

            // Handing a ride to this driver hands it to the associate who owns
            // him, and only to him: the ride's pickup city has no say in it.
            $booking->syncAssociateFromAssignment();

            $assignment = DriverAssignment::create([
                'booking_id' => $booking->id,
                'driver_id' => $driverId,
                'vehicle_id' => $booking->vehicle_id,
                'assigned_by' => $adminId,
                'status' => 'Active',
            ]);

            // The driver now has a limited window to accept or refuse the ride.
            $assignment->startResponseWindow();

            $this->fleet->markAssigned($booking);

            return $booking;
        });

        $this->mailNotifications->notifyDriverAssigned($booking);

        return $booking;
    }

    /**
     * Confirm the ride on the driver's behalf.
     *
     * The driver accepting his assignment confirms the ride on its own, so this
     * exists for the admin who has to confirm without the driver's answer, e.g.
     * when he is reachable by phone. It is only offered from the states that
     * may be confirmed.
     */
    public function confirmBooking(Booking $booking, $driverId)
    {
        $this->ensureDriverWillingToGoTo($booking, $driverId);

        $booking = DB::transaction(function () use ($booking, $driverId) {
            $this->fleet->markAssigned($booking);

            $booking = $this->statuses->transition(
                $booking,
                BookingStatus::CONFIRMED,
                Auth::id() ?? 1, // Fallback for tests
                'Booking confirmed and driver assigned.',
                ['driver_id' => $driverId],
            );

            // Confirming with this driver makes the ride the associate's when he
            // owns the driver, and the admin's when he does not.
            $booking->syncAssociateFromAssignment();

            if ($driverId) {
                DriverAssignment::create([
                    'booking_id' => $booking->id,
                    'driver_id' => $driverId,
                    'vehicle_id' => $booking->vehicle_id,
                    'assigned_by' => Auth::id() ?? 1,
                    'status' => 'Active',
                ]);
            }

            return $booking;
        });

        if ($driverId) {
            $this->mailNotifications->notifyDriverAssigned($booking);
        }

        // Confirming by hand reaches the same place as the driver accepting it,
        // so the customer gets his account and login details here too.
        $this->customers->welcomeConfirmedCustomer($booking);

        return $booking;
    }

    /**
     * Call the ride off.
     *
     * Anything already on it is handed back, because the ride will not now run.
     */
    public function cancelBooking(Booking $booking)
    {
        if ($booking->isLocked()) {
            throw new \Exception($booking->lockedMessage());
        }

        return DB::transaction(function () use ($booking) {
            $this->fleet->release($booking);

            return $this->statuses->transition(
                $booking,
                BookingStatus::CANCELLED,
                Auth::id() ?? 1,
                'Booking cancelled.',
                ['rejection_reason' => null, 'rejection_source' => null],
            );
        });
    }

    /**
     * The driver starts the ride. He must give the odometer reading he sees on
     * the meter and a photo of it, so the admin has proof of the vehicle's
     * state and mileage at pickup, and the booking moves to Trip Started.
     *
     * @throws \Exception when the ride is not in a state that can be started
     */
    public function startTrip(Booking $booking, int $odometerKm, string $odometerPhoto, ?int $changedBy = null)
    {
        if (! $booking->canStartTrip()) {
            throw new \Exception('This ride cannot be started from its current status ('.$booking->displayStatus().').');
        }

        $booking = DB::transaction(function () use ($booking, $odometerKm, $odometerPhoto, $changedBy) {
            $booking = $this->statuses->transition(
                $booking,
                BookingStatus::TRIP_STARTED,
                $changedBy,
                'Trip started by the driver. Odometer at start: '.number_format($odometerKm).' km.',
                [
                    'start_odometer_km' => $odometerKm,
                    'start_odometer_photo' => $odometerPhoto,
                    'trip_started_at' => now(),
                    'tracking_id' => Str::uuid()->toString(),
                ],
            );

            // The driver is behind the wheel from here, so he and the vehicle
            // stop being merely booked and become out on the ride.
            $this->fleet->markOnTrip($booking);

            return $booking;
        });

        // Send tracking link to Admin
        Mail::to(config('mail.from.address', 'admin@example.com'))->send(new RideTrackingLinkMail($booking));

        return $booking;
    }

    /**
     * Mark the trip as completed and relocate the vehicle and driver to the
     * drop city, so they become available from there (e.g. a Jaipur ->
     * Udaipur trip makes them available from Udaipur afterwards).
     *
     * When the driver closes the ride himself the closing odometer reading and
     * photo are stored as well, so the total distance of the ride is the
     * difference between the two readings. An admin can still complete a ride
     * without them.
     *
     * Whenever both readings are in, the amount of the ride is worked out from
     * that distance and the rate card of the vehicle and stored with it.
     *
     * Completing a ride that never started is refused by the state machine: the
     * admin may close one he started by hand or whose driver filed the closing
     * reading, but he cannot mark an undriven ride finished.
     */
    public function completeTrip(Booking $booking, $adminId, ?int $endOdometerKm = null, ?string $endOdometerPhoto = null)
    {
        if ($endOdometerKm !== null
            && $booking->start_odometer_km !== null
            && $endOdometerKm < (int) $booking->start_odometer_km) {
            throw new \Exception(
                'The closing odometer reading ('.number_format($endOdometerKm).' km) cannot be lower than the reading at the start ('
                .number_format((int) $booking->start_odometer_km).' km).'
            );
        }

        return DB::transaction(function () use ($booking, $adminId, $endOdometerKm, $endOdometerPhoto) {
            $attributes = [];

            if ($endOdometerKm !== null) {
                $attributes['end_odometer_km'] = $endOdometerKm;
                $attributes['end_odometer_photo'] = $endOdometerPhoto;
                $attributes['trip_ended_at'] = now();
            }

            // Filled in memory only, so the bill can be worked out from the
            // closing reading before anything is stored.
            $booking->fill($attributes);

            // The bill is worked out while the readings are at hand and stored
            // with the figures it came out of, so a later change to the
            // vehicle's rate card cannot rewrite a closed bill.
            $tripDistanceKm = $booking->tripDistanceKm();

            if ($tripDistanceKm !== null) {
                $fare = $this->tripFare->calculate($booking, $tripDistanceKm);

                if ($fare !== null) {
                    $attributes = array_merge($attributes, $fare);

                    // Filled in memory as well, so the completion note written
                    // below quotes the amount that is actually being stored.
                    $booking->fill($fare);
                }
            }

            // The completion note is written from the finished ride, so it is
            // composed before the status moves rather than after.
            $remarks = $this->completionRemarks($booking)
                .' Vehicle and driver relocated to '.($booking->dropCity->name ?? 'the drop city').'.';

            $booking = $this->statuses->transition(
                $booking,
                BookingStatus::TRIP_COMPLETED,
                $adminId,
                $remarks,
                $attributes,
            );

            if ($booking->vehicle_id && $booking->drop_city_id) {
                Vehicle::whereKey($booking->vehicle_id)->update(['city_id' => $booking->drop_city_id]);
            }

            if ($booking->driver_id && $booking->drop_city_id) {
                Driver::whereKey($booking->driver_id)->update(['current_city_id' => $booking->drop_city_id]);
            }

            // The ride is over, so the driver and the vehicle it was using stand
            // free from here in the drop city.
            $this->fleet->release($booking);

            return $booking;
        });
    }

    /**
     * Close the ride from the driver's phone. The closing odometer reading and
     * photo are the proof of the distance covered, so the trip total comes out
     * of the difference between the two readings.
     *
     * @throws \Exception when the ride is not running or the reading is lower
     *                    than the one recorded at the start
     */
    public function endTrip(Booking $booking, int $endOdometerKm, string $odometerPhoto, ?int $changedBy = null)
    {
        if (! $booking->canEndTrip()) {
            throw new \Exception('This ride cannot be ended from its current status ('.$booking->displayStatus().').');
        }

        return $this->completeTrip($booking, $changedBy, $endOdometerKm, $odometerPhoto);
    }

    /**
     * The odometer and money part of the trip-completed history line.
     */
    protected function completionRemarks(Booking $booking): string
    {
        $remarks = 'Trip completed.';

        if ($booking->start_odometer_km !== null && $booking->end_odometer_km !== null) {
            $remarks .= ' Odometer at start: '.number_format((int) $booking->start_odometer_km)
                .' km, at end: '.number_format((int) $booking->end_odometer_km)
                .' km, total distance: '.number_format((int) $booking->tripDistanceKm()).' km.';
        } elseif ($booking->end_odometer_km !== null) {
            $remarks .= ' Odometer at end: '.number_format((int) $booking->end_odometer_km).' km.';
        }

        if ($booking->hasTripFare()) {
            $remarks .= ' Amount billed: '.number_format((float) $booking->total_amount, 2)
                .' ('.((int) $booking->billed_days).' day(s) at '.number_format((float) $booking->billed_price_per_day, 2)
                .' covering '.number_format((int) $booking->billed_included_km).' km';

            if ((int) $booking->extra_km > 0) {
                $remarks .= ', plus '.number_format((int) $booking->extra_km).' extra km at '
                    .number_format((float) $booking->billed_price_per_km, 2);
            }

            $remarks .= ').';
        }

        return $remarks;
    }

    /**
     * Work out the two cities a ride runs between.
     *
     * The pickup city has to be one of ours - a ride cannot start where we have
     * no cabs - so it comes from the chosen id, the pickup address, or the one
     * city the service runs in, in that order. The destination is free: a
     * written place that names one of our cities is served from that city, and
     * anything else is served from the pickup city with the customer's own
     * wording kept in the label so the screens can show where he is really
     * going.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws \Exception when the pickup city cannot be worked out
     */
    protected function settleTripCities(array $data): array
    {
        $pickupCity = $this->cityFromId($data['pickup_city_id'] ?? null)
            ?? $this->cityResolver->resolve($data['pickup_location'] ?? null)
            ?? $this->cityResolver->soleCity();

        if (! $pickupCity instanceof City) {
            throw new \Exception('We could not work out the pickup city of this trip. Please choose it again.');
        }

        // The destination the customer wrote, whether it arrived as the search
        // field or as the label an earlier step already stored.
        $writtenDropCity = trim((string) ($data['drop_city'] ?? $data['drop_city_label'] ?? ''));

        if ($writtenDropCity !== '') {
            $dropCity = $this->cityResolver->resolve($writtenDropCity) ?? $pickupCity;
            $dropCityLabel = $writtenDropCity;
        } else {
            $dropCity = $this->cityFromId($data['drop_city_id'] ?? null)
                ?? $this->cityResolver->resolve($data['drop_location'] ?? null)
                ?? $pickupCity;
            $dropCityLabel = null;
        }

        $data['pickup_city_id'] = $pickupCity->id;
        $data['drop_city_id'] = $dropCity->id;
        $data['drop_city_label'] = $dropCityLabel;
        unset($data['drop_city']);

        return $data;
    }

    /**
     * The city with this id, or null when no usable id was given.
     */
    protected function cityFromId($cityId): ?City
    {
        if ($cityId === null || $cityId === '') {
            return null;
        }

        return City::find($cityId);
    }

    /**
     * Take every open assignment on this ride off the driver it was made for,
     * because the ride is being given to somebody else.
     *
     * Only assignments still in play are closed: one the driver already refused
     * stays a refusal on his record, because that happened before the swap and
     * is a real thing he did. One he accepted, or never answered at all, is
     * superseded instead, so the ride stops counting against him, drops off his
     * pending list, and his late answer cannot reach a ride he is no longer on.
     */
    protected function supersedeAssignments(Booking $booking): void
    {
        $booking->driverAssignments()
            ->whereIn('response_status', [
                AssignmentResponseStatus::Pending->value,
                AssignmentResponseStatus::Accepted->value,
            ])
            ->get()
            ->each(fn (DriverAssignment $assignment) => $assignment->supersede());
    }

    /**
     * Guard: the booked vehicle has to be one that runs the service that was
     * chosen. The results page only offers those vehicles, so this repeats the
     * rule server-side rather than trusting the form: a stale or hand-written
     * submission cannot book a fleet against a service it does not provide.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Exception
     */
    protected function ensureVehicleRunsService(array $data): void
    {
        if (empty($data['vehicle_id']) || empty($data['service_type_id'])) {
            return;
        }

        $vehicle = Vehicle::find($data['vehicle_id']);

        if (! $vehicle || $vehicle->services()->whereKey($data['service_type_id'])->exists()) {
            return;
        }

        $service = ServiceType::find($data['service_type_id']);

        throw new \Exception(sprintf(
            'The %s does not provide %s. Please choose another cab or a different service.',
            $vehicle->name,
            $service?->name ?? 'the service you picked',
        ));
    }

    /**
     * Guard: a driver who selected preferred cities must include the
     * booking's drop city, otherwise he cannot be assigned to that trip.
     * Drivers with no preference at all remain assignable everywhere.
     *
     * @throws \Exception
     */
    protected function ensureDriverWillingToGoTo(Booking $booking, $driverId): void
    {
        if (empty($driverId) || empty($booking->drop_city_id)) {
            return;
        }

        $driver = Driver::with('preferredCities')->find($driverId);

        if (! $driver) {
            return;
        }

        if ($driver->preferredCities->isNotEmpty()
            && ! $driver->preferredCities->contains('id', (int) $booking->drop_city_id)) {
            // The written destination, so a Kishangarh ride does not report
            // itself as a trip to the pickup city.
            throw new \Exception("Driver {$driver->name} is not willing to go to {$booking->displayDropCity()}.");
        }
    }
}
