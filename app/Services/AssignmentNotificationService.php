<?php

namespace App\Services;

use App\Enums\AssignmentResponseStatus;
use App\Models\Booking;
use App\Models\DriverAssignment;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\DriverRespondedToAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Pushes the "the driver answered" event to the desktop and the in-app bell of
 * everyone who dispatches that ride.
 *
 * Delivery is opt-in through the admin settings screen, and a failure is logged
 * instead of thrown: a driver answering his ride must never be rolled back
 * because a push service was unreachable. The notification is sent
 * synchronously so the bell and the toast appear without a queue worker.
 */
class AssignmentNotificationService
{
    /**
     * Tell the dispatchers that the driver accepted the ride.
     */
    public function notifyAccepted(Booking $booking, DriverAssignment $assignment): void
    {
        $this->notify($booking, $assignment, AssignmentResponseStatus::Accepted);
    }

    /**
     * Tell the dispatchers that the ride ended in a refusal, whether the driver
     * turned it down himself or his response window closed with no answer.
     */
    public function notifyRejected(
        Booking $booking,
        DriverAssignment $assignment,
        AssignmentResponseStatus $response,
        string $reason
    ): void {
        $this->notify($booking, $assignment, $response, $reason);
    }

    /**
     * @param  string|null  $reason  Why the ride was refused, for a rejection only.
     */
    protected function notify(
        Booking $booking,
        DriverAssignment $assignment,
        AssignmentResponseStatus $response,
        ?string $reason = null
    ): void {
        if (! Setting::boolean('push.enabled', true)) {
            return;
        }

        $setting = $response->isRejected() ? 'push.notify_on_reject' : 'push.notify_on_accept';

        if (! Setting::boolean($setting, true)) {
            return;
        }

        $recipients = $this->recipients($booking);

        if ($recipients === []) {
            return;
        }

        // Read the driver from the assignment, not the booking: a rejection
        // clears the booking's driver so the driver can take another ride.
        $driverName = $assignment->driver?->name ?? 'The driver';

        try {
            Notification::send($recipients, new DriverRespondedToAssignment(
                $booking,
                $response,
                $driverName,
                $reason,
            ));
        } catch (Throwable $exception) {
            Log::error('Push notification failed: driver assignment response', [
                'booking_id' => $booking->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Everyone who dispatches this ride: the active admins plus the active
     * associate the admin handed the ride to. Deactivated accounts are skipped
     * because they cannot log in to receive anything.
     *
     * A ride that starts in a city some associate manages is not his unless the
     * admin put one of his drivers or vehicles on it, so an associate who does
     * not own this ride is left out even when he manages the pickup city.
     *
     * @return Collection<int, User>
     */
    protected function recipients(Booking $booking): Collection
    {
        return User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->where(function (Builder $query) use ($booking): void {
                $query->where('role', User::ROLE_ADMIN);

                if ($booking->associate_id !== null) {
                    $query->orWhere('id', $booking->associate_id);
                }
            })
            ->get();
    }
}
