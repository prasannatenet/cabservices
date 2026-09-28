<?php

namespace App\Enums;

/**
 * Whether a driver has answered an assignment within the response window.
 */
enum AssignmentResponseStatus: string
{
    case Pending = 'Pending';
    case Accepted = 'Accepted';
    case Rejected = 'Rejected';
    case AutoRejected = 'Auto Rejected';

    /**
     * The driver has not answered yet, so the ride is still waiting on him.
     */
    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    /**
     * The assignment was refused, either by the driver or because the response
     * window closed without an answer.
     */
    public function isRejected(): bool
    {
        return in_array($this, [self::Rejected, self::AutoRejected], true);
    }
}
