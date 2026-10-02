<?php

namespace App\Services;

use App\Enums\AssignmentResponseStatus;
use App\Enums\BookingStatus;
use App\Enums\RejectionSource;
use App\Models\Booking;
use App\Models\DriverAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles the driver's answer to a newly assigned ride.
 *
 * A driver has six hours to accept or refuse. Refusing requires a reason, and an
 * unanswered assignment is rejected automatically once the window closes, which
 * frees the booking for the admin to reassign. Either way the ride lands on
 * Driver Rejected, its own status.
 */
class AssignmentResponseService
{
    public function __construct(
        protected AssignmentNotificationService $notifications,
        protected BookingStatusService $statuses,
        protected FleetStatusService $fleet,
        protected CustomerAccountService $customers,
    ) {}

    /**
     * The driver confirms he will take the ride, which confirms the booking.
     *
     * @throws \Exception when the response window has already closed
     */
    public function accept(DriverAssignment $assignment): Booking
    {
        $this->guardStillOpen($assignment);

        $booking = DB::transaction(function () use ($assignment): Booking {
            $booking = $assignment->booking;

            $assignment->forceFill([
                'response_status' => AssignmentResponseStatus::Accepted,
                'responded_at' => now(),
            ])->save();

            // The driver is now definitely driving, so he and the vehicle stop
            // waiting on an answer and become out on the ride.
            $this->fleet->markAssigned($booking);

            return $this->statuses->transition(
                $booking,
                BookingStatus::CONFIRMED,
                $assignment->assigned_by,
                'Driver accepted the assignment.',
            );
        });

        $this->notifications->notifyAccepted($booking, $assignment);

        // The driver saying yes is what confirms the ride, so this is the moment
        // the customer gets an account and the login details to reach it.
        $this->customers->welcomeConfirmedCustomer($booking);

        return $booking;
    }

    /**
     * The driver refuses the ride, giving a reason for the admin.
     *
     * @throws \Exception when the response window has already closed
     */
    public function reject(DriverAssignment $assignment, string $reason): Booking
    {
        $this->guardStillOpen($assignment);

        return $this->recordRejection(
            $assignment,
            AssignmentResponseStatus::Rejected,
            $reason,
            'Booking rejected by the assigned driver.'
        );
    }

    /**
     * Reject every assignment whose six hour window closed with no answer.
     *
     * @return int the number of assignments that were auto-rejected
     */
    public function rejectExpiredAssignments(): int
    {
        $expired = DriverAssignment::with('booking')
            ->where('response_status', AssignmentResponseStatus::Pending->value)
            ->whereNotNull('response_deadline')
            ->where('response_deadline', '<=', now())
            ->get();

        $rejected = 0;

        foreach ($expired as $assignment) {
            try {
                // A ride that has moved on to another driver is left alone: the
                // assignment is superseded when the swap happens, so this only
                // catches a row the swap somehow missed. Letting it through
                // would reject somebody else's ride over an answer to one that
                // is no longer his.
                if ((int) $assignment->booking?->driver_id !== $assignment->driver_id) {
                    continue;
                }

                $this->recordRejection(
                    $assignment,
                    AssignmentResponseStatus::AutoRejected,
                    DriverAssignment::AUTO_REJECTION_REASON,
                    'Auto-rejected: the driver did not respond within the 6 hour window.'
                );

                $rejected++;
            } catch (\Throwable $exception) {
                // One bad row must not stop the rest of the sweep.
                Log::error('Could not auto-reject an expired driver assignment', [
                    'assignment_id' => $assignment->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return $rejected;
    }

    /**
     * Close an assignment as rejected and release the booking back to the admin.
     *
     * The ride lands on Driver Rejected, a status of its own: an admin rejection
     * is Rejected, so the two are told apart by the booking itself rather than
     * by a second column read alongside it. The driver and vehicle are handed
     * back, because this ride is not going to happen.
     *
     * The dispatchers are notified once the ride is actually released, so a
     * driver refusing or the six hour window closing both raise a desktop
     * notification for whoever has to reassign the ride.
     */
    protected function recordRejection(
        DriverAssignment $assignment,
        AssignmentResponseStatus $status,
        string $reason,
        string $remarks
    ): Booking {
        $booking = DB::transaction(function () use ($assignment, $status, $reason, $remarks): Booking {
            $booking = $assignment->booking()->firstOrFail();

            $assignment->forceFill([
                'response_status' => $status,
                'responded_at' => now(),
                'rejection_reason' => $reason,
                'status' => 'Cancelled',
            ])->save();

            // The driver is not taking this ride after all, so he and the vehicle
            // stand free again before the booking says so.
            $this->fleet->release($booking);

            $booking = $this->statuses->transition(
                $booking,
                BookingStatus::DRIVER_REJECTED,
                $assignment->assigned_by,
                $remarks,
                [
                    'rejection_reason' => $reason,
                    'rejection_source' => RejectionSource::Driver,
                    // Free the driver so he can be given another ride.
                    'driver_id' => null,
                ],
            );

            return $booking;
        });

        $this->notifications->notifyRejected($booking, $assignment, $status, $reason);

        return $booking;
    }

    /**
     * A driver can only answer while his six hour window is still open.
     *
     * @throws \Exception
     */
    protected function guardStillOpen(DriverAssignment $assignment): void
    {
        // The stored row is read again rather than trusting the instance, so an
        // assignment held in memory from before a reassignment cannot be
        // answered after the ride has moved to another driver.
        $assignment->refresh();

        if ($assignment->wasSuperseded()) {
            throw new \Exception('This ride has been given to another driver.');
        }

        if (! $assignment->response_status->isPending()) {
            throw new \Exception('You have already responded to this ride.');
        }

        if ($assignment->hasResponseWindowExpired()) {
            throw new \Exception('The 6 hour response window for this ride has closed.');
        }
    }
}
