<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingStatusHistory;

/**
 * The one place a booking's status changes.
 *
 * Nothing else is allowed to write bookings.status: the admin screens, the
 * driver screens and the assignment sweep all come through here, so the rule
 * about which status may follow which is enforced once instead of being
 * re-implemented per controller.
 *
 * Every move writes a BookingStatusHistory row, so the trail always explains
 * how the ride got where it is.
 */
class BookingStatusService
{
    /**
     * Move a booking to a new status, refusing any jump the booking's own
     * history does not allow.
     *
     * Leaving a rejected state clears the rejection details, because they only
     * describe why that ride was called off: a ride put back in play is no
     * longer rejected, and a stale reason left on it reads as though it were.
     *
     * @param  array<string, mixed>  $attributes  Further booking columns to
     *                                            store alongside the status.
     *
     * @throws \Exception when the move is not one the booking may make
     */
    public function transition(
        Booking $booking,
        BookingStatus $status,
        ?int $changedBy = null,
        ?string $remarks = null,
        array $attributes = [],
    ): Booking {
        if (! $booking->status->canTransitionTo($status)) {
            throw new \Exception($booking->status->transitionErrorMessage($status));
        }

        $oldStatus = $booking->status;

        $attributes['status'] = $status->value;

        if ($oldStatus->isClosedWithoutRunning() && ! $status->isClosedWithoutRunning()) {
            $attributes['rejection_reason'] = null;
            $attributes['rejection_source'] = null;
        }

        $booking->fill($attributes);
        $booking->save();

        BookingStatusHistory::create([
            'booking_id' => $booking->id,
            'old_status' => $oldStatus->value,
            'new_status' => $status->value,
            'changed_by' => $changedBy,
            'remarks' => $remarks ?? $this->defaultRemarks($oldStatus, $status),
        ]);

        return $booking;
    }

    /**
     * The statuses a booking may move to right now, i.e. the actions its screen
     * is allowed to offer.
     *
     * @return list<BookingStatus>
     */
    public function availableActions(Booking $booking): array
    {
        return $booking->status->allowedTransitions();
    }

    /**
     * A fallback sentence for a move nobody supplied a reason for, so no status
     * change is ever recorded without an explanation.
     */
    protected function defaultRemarks(BookingStatus $from, BookingStatus $to): string
    {
        return sprintf('Status changed from %s to %s.', $from->value, $to->value);
    }
}
