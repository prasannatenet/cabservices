<?php

namespace App\Models;

use App\Enums\AssignmentResponseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverAssignment extends Model
{
    use HasFactory;

    /**
     * How long a driver has to accept or refuse a new assignment.
     */
    public const RESPONSE_WINDOW_HOURS = 6;

    /**
     * The reason recorded when the response window closes without an answer.
     */
    public const AUTO_REJECTION_REASON = 'Driver did not respond within 6 hours.';

    protected $fillable = [
        'booking_id', 'driver_id', 'vehicle_id',
        'assigned_by', 'assigned_at', 'status',
        'response_status', 'response_deadline', 'responded_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'response_deadline' => 'datetime',
            'responded_at' => 'datetime',
            'response_status' => AssignmentResponseStatus::class,
        ];
    }

    /**
     * Start a fresh response window for a driver who has just been assigned.
     */
    public function startResponseWindow(): void
    {
        $this->forceFill([
            'response_status' => AssignmentResponseStatus::Pending,
            'response_deadline' => now()->addHours(self::RESPONSE_WINDOW_HOURS),
            'responded_at' => null,
            'rejection_reason' => null,
        ])->save();
    }

    /**
     * Whether the driver may still answer, i.e. he has not replied and the six
     * hour window is still open.
     */
    public function isAwaitingResponse(): bool
    {
        return $this->response_status->isPending()
            && $this->response_deadline !== null
            && $this->response_deadline->isFuture();
    }

    /**
     * Whether the window has closed without the driver answering.
     */
    public function hasResponseWindowExpired(): bool
    {
        return $this->response_status->isPending()
            && $this->response_deadline !== null
            && $this->response_deadline->isPast();
    }

    /**
     * Whole minutes left to answer, for the countdown shown to the driver.
     */
    public function minutesRemaining(): int
    {
        if (! $this->isAwaitingResponse()) {
            return 0;
        }

        return max(0, (int) now()->diffInMinutes($this->response_deadline, false));
    }

    /**
     * Assignments the ride was taken off, i.e. the ones a dispatcher took away
     * by giving the ride to another driver.
     */
    public function scopeSuperseded(Builder $query): Builder
    {
        return $query->where('response_status', AssignmentResponseStatus::Superseded->value);
    }

    /**
     * Assignments that count against the driver they were made for.
     *
     * Everything except the superseded ones: a ride taken off a driver before he
     * drove it was never his, so it belongs in neither his ride counts nor his
     * rejection count. This is the scope every count on the driver screens reads.
     */
    public function scopeCounted(Builder $query): Builder
    {
        return $query->where('response_status', '!=', AssignmentResponseStatus::Superseded->value);
    }

    /**
     * Response statuses that mean the driver side turned the ride down.
     *
     * @return list<string>
     */
    public static function refusedResponseValues(): array
    {
        return [
            AssignmentResponseStatus::Rejected->value,
            AssignmentResponseStatus::AutoRejected->value,
        ];
    }

    /**
     * Assignments ended by the driver refusing them or by his window closing.
     * Unordered on purpose so it can be reused inside count() subqueries.
     */
    public function scopeRefused(Builder $query): Builder
    {
        return $query->whereIn('response_status', self::refusedResponseValues())
            ->whereNotNull('responded_at');
    }

    /**
     * Assignments the driver accepted, i.e. he went ahead with the ride.
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('response_status', AssignmentResponseStatus::Accepted->value);
    }

    /**
     * Assignments still waiting for the driver inside his response window.
     */
    public function scopeAwaitingResponse(Builder $query): Builder
    {
        return $query->where('response_status', AssignmentResponseStatus::Pending->value)
            ->where('response_deadline', '>', now());
    }

    /**
     * Assignments whose ride the driver drove all the way to completion.
     */
    public function scopeCompletedTrips(Builder $query): Builder
    {
        return $query->whereHas('booking', fn (Builder $booking) => $booking->completed());
    }

    /**
     * Assignments whose ride is still on its way (assigned, confirmed or started).
     */
    public function scopeOngoingTrips(Builder $query): Builder
    {
        return $query->whereHas('booking', fn (Builder $booking) => $booking->ongoing());
    }

    /**
     * Assignments that ended in a rejection, either refused by the driver or
     * auto-rejected when his window closed. Newest answer first.
     *
     * The booking is freed for another driver once a ride is refused, so this
     * assignment row is what keeps the rejection and its reason visible.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->refused()->orderByDesc('responded_at');
    }

    /**
     * Take this assignment off the driver because the ride was given to somebody
     * else, whether he had answered it or not.
     *
     * The row is kept rather than deleted so the trail still shows the ride was
     * once his, but it is closed without a rejection reason: he never refused
     * anything, and nothing here should ever read as though he did.
     */
    public function supersede(): void
    {
        $this->forceFill([
            'response_status' => AssignmentResponseStatus::Superseded,
            'responded_at' => now(),
            'rejection_reason' => null,
            'status' => 'Cancelled',
        ])->save();
    }

    /**
     * Whether the driver personally refused the ride, as opposed to never
     * answering it.
     */
    public function wasRejectedByDriver(): bool
    {
        return $this->response_status === AssignmentResponseStatus::Rejected;
    }

    /**
     * Whether the response window closed without any answer.
     */
    public function wasAutoRejected(): bool
    {
        return $this->response_status === AssignmentResponseStatus::AutoRejected;
    }

    /**
     * Short label shown next to a rejection in the driver and admin views.
     */
    public function rejectionLabel(): string
    {
        return $this->wasAutoRejected() ? 'No answer in time' : 'Rejected';
    }

    /**
     * Whether the ride was taken off this driver and given to somebody else,
     * which is neither an acceptance nor a refusal of his.
     */
    public function wasSuperseded(): bool
    {
        return $this->response_status->isSuperseded();
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function adminUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
