<?php

namespace App\Services;

use App\Enums\AssignmentResponseStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\DriverAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles the driver's answer to a newly assigned ride.
 *
 * A driver has six hours to accept or refuse. Refusing requires a reason, and an
 * unanswered assignment is rejected automatically once the window closes, which
 * frees the booking for the admin to reassign.
 */
class AssignmentResponseService
{
    /**
     * The driver confirms he will take the ride.
     *
     * @throws \Exception when the response window has already closed
     */
    public function accept(DriverAssignment $assignment): Booking
    {
        $this->guardStillOpen($assignment);

        return DB::transaction(function () use ($assignment): Booking {
            $booking = $assignment->booking;
            $oldStatus = $booking->status;

            $assignment->forceFill([
                'response_status' => AssignmentResponseStatus::Accepted,
                'responded_at' => now(),
            ])->save();

            $booking->update([
                'status' => BookingStatus::CONFIRMED->value,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::CONFIRMED->value,
                'changed_by' => $assignment->assigned_by,
                'remarks' => 'Driver accepted the assignment.',
            ]);

            return $booking;
        });
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
     */
    protected function recordRejection(
        DriverAssignment $assignment,
        AssignmentResponseStatus $status,
        string $reason,
        string $remarks
    ): Booking {
        return DB::transaction(function () use ($assignment, $status, $reason, $remarks): Booking {
            $booking = $assignment->booking()->firstOrFail();
            $oldStatus = $booking->status;

            $assignment->forceFill([
                'response_status' => $status,
                'responded_at' => now(),
                'rejection_reason' => $reason,
                'status' => 'Cancelled',
            ])->save();

            $booking->update([
                'status' => BookingStatus::REJECTED->value,
                'rejection_reason' => $reason,
                // Free the driver so he can be given another ride.
                'driver_id' => null,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => BookingStatus::REJECTED->value,
                'changed_by' => $assignment->assigned_by,
                'remarks' => $remarks,
            ]);

            return $booking;
        });
    }

    /**
     * A driver can only answer while his six hour window is still open.
     *
     * @throws \Exception
     */
    protected function guardStillOpen(DriverAssignment $assignment): void
    {
        if (! $assignment->response_status->isPending()) {
            throw new \Exception('You have already responded to this ride.');
        }

        if ($assignment->hasResponseWindowExpired()) {
            throw new \Exception('The 6 hour response window for this ride has closed.');
        }
    }
}
