<?php

namespace App\Models;

use App\Enums\AssignmentResponseStatus;
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
